<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2022 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Core;

use Avatar_Privacy\Core\API;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Exceptions\File_Deletion_Exception;
use Avatar_Privacy\Exceptions\Upload_Handling_Exception;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler as Upload_Handler;

/**
 * The API for handling data attached to registered users as part of the
 * Avatar Privacy Core API.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Default_Avatars implements API {

	/**
	 * The settings API.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The hashing helper.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The image file handler.
	 *
	 * @var Image_File
	 */
	private $image_file;

	/**
	 * Creates a new instance.
	 *
	 * @param Settings         $settings   The settings API.
	 * @param Options          $options    The options handler.
	 * @param Hasher           $hasher     The hashing helper..
	 * @param Filesystem_Cache $file_cache The file cache handler.
	 * @param Image_File       $image_file The image file handler.
	 */
	public function __construct( Settings $settings, Options $options, Hasher $hasher, Filesystem_Cache $file_cache, Image_File $image_file ) {
		$this->settings   = $settings;
		$this->options    = $options;
		$this->hasher     = $hasher;
		$this->file_cache = $file_cache;
		$this->image_file = $image_file;
	}

	/**
	 * Retrieves the hash for the custom default avatar for the given site.
	 *
	 * @param  int $site_id The site ID.
	 *
	 * @return string
	 */
	public function get_hash( $site_id ) {
		return $this->hasher->get_hash( "custom-default-{$site_id}" );
	}

	/**
	 * Retrieves the full-size custom default avatar for a site (if one exists).
	 *
	 * @return array {
	 *     An avatar definition, or the empty array.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 */
	public function get_custom_default_avatar() {
		$avatar = $this->settings->get( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR );
		if ( ! \is_array( $avatar ) || empty( $avatar['file'] ) ) {
			$avatar = [];
		}

		return $avatar;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- until PHPCS bug is fixed.
	/**
	 * Sets the custom default avatar for the current site.
	 *
	 * Please note that the calling function is responsible for cleaning up the
	 * provided image if it is a temporary file (i.e the image is copied before
	 * being used as the new avatar).
	 *
	 * @param  string $image_url The image URL or filename.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException An exception is thrown if the image URL
	 *                                   is invalid.
	 * @throws Upload_Handling_Exception An exception is thrown if there was an
	 *                                   while processing the image sideloading.
	 * @throws File_Deletion_Exception   An exception is thrown if the previously
	 *                                   set image could not be deleted.
	 */
	public function set_custom_default_avatar( $image_url ) {
		$filename = \parse_url( $image_url, \PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- we only support PHP 7.0 and higher.
		if ( empty( $filename ) ) {
			throw new \InvalidArgumentException( "Malformed URL {$image_url}" );
		}

		// Prepare arguments.
		$overrides = [
			'global_upload' => false,
			'upload_dir'    => Upload_Handler::UPLOAD_DIR,
			'filename'      => $this->get_custom_default_avatar_filename( $filename ),
		];

		// Sideload file and validate result.
		$sideloaded_avatar = $this->image_file->handle_sideload( $image_url, $overrides );
		if ( empty( $sideloaded_avatar['file'] ) ) {
			throw new Upload_Handling_Exception( 'Missing upload file path' );
		} elseif ( empty( $sideloaded_avatar['type'] ) ) {
			throw new Upload_Handling_Exception( "Could not determine MIME type for {$image_url}" );
		} elseif ( ! isset( Image_File::FILE_EXTENSION[ $sideloaded_avatar['type'] ] ) ) {
			throw new Upload_Handling_Exception( "Invalid MIME type {$sideloaded_avatar['type']}" );
		}

		$this->store_custom_default_avatar_data( $sideloaded_avatar );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- until PHPCS bug is fixed.

	/**
	 * Deletes the custom default avatar set for the current site (including the setting).
	 *
	 * @return void
	 *
	 * @throws File_Deletion_Exception An exception is thrown if the previously
	 *                                 set image could not be deleted.
	 */
	public function delete_custom_default_avatar() {
		$this->store_custom_default_avatar_data( [] );
	}

	/**
	 * Stores the given avatar data and cleans up existing image files.
	 *
	 * @param  string[] $avatar_data The avatar data. May be empty.
	 *
	 * @return void
	 *
	 * @throws File_Deletion_Exception An exception is thrown if the previously
	 *                                 set image could not be deleted.
	 */
	protected function store_custom_default_avatar_data( array $avatar_data ) {
		// Delete old images.
		if ( ! $this->delete_custom_default_avatar_image_file() ) {
			throw new File_Deletion_Exception( 'Could not delete previous avatar image.' );
		}

		// Invalidate cached thumbnails.
		$this->invalidate_custom_default_avatar_cache( \get_current_blog_id() );

		// Save the sideloaded default avatar.
		$this->settings->set( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR, $avatar_data );
	}

	/**
	 * Deletes the custom default avatar image file for the current site (but not
	 * cached thumbnails).
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function delete_custom_default_avatar_image_file() {
		// Delete original upload if it exists.
		$icon = $this->get_custom_default_avatar();
		if ( empty( $icon['file'] ) || \file_exists( $icon['file'] ) && \unlink( $icon['file'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Invalidates cached avatar images.
	 *
	 * @internal
	 *
	 * @param  int $site_id The site ID.
	 *
	 * @return void
	 */
	public function invalidate_custom_default_avatar_cache( $site_id ) {
		$this->file_cache->invalidate( 'custom', "#/{$this->get_hash( $site_id )}-[1-9][0-9]*\.[a-z]{3}\$#" );
	}

	/**
	 * Retrieves the base filename (without the extension) for the custom avatar
	 * image for the current site.
	 *
	 * @internal
	 *
	 * @param  string $filename The original filename.
	 *
	 * @return string
	 */
	public function get_custom_default_avatar_filename( $filename ) {
		$extension = \pathinfo( $filename, \PATHINFO_EXTENSION );
		$filename  = 'custom-default-icon';

		$blogname = $this->options->get( 'blogname', '', true );
		if ( \is_string( $blogname ) && ! empty( $blogname ) ) {
			$filename = \htmlspecialchars_decode( $blogname );
		}

		return \sanitize_file_name( "{$filename}.{$extension}" );
	}
}
