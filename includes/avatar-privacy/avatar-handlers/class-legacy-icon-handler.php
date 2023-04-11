<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2023 Peter Putzer.
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

namespace Avatar_Privacy\Avatar_Handlers;

use Avatar_Privacy\Avatar_Handlers\Avatar_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Tools\Network\Remote_Image_Service;

/**
 * Handles retrieving and caching legacy icons (including remote images).
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <giGrthub@mundschenk.at>
 *
 * @phpstan-import-type AvatarArguments from Avatar_Handler
 */
class Legacy_Icon_Handler implements Avatar_Handler {

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private Filesystem_Cache $file_cache;

	/**
	 * The remote image service.
	 *
	 * @var Remote_Image_Service
	 */
	private Remote_Image_Service $remote_images;

	/**
	 * Creates a new instance.
	 *
	 * @param Filesystem_Cache     $file_cache    The file cache handler.
	 * @param Remote_Image_Service $remote_images The remote image service.
	 */
	public function __construct( Filesystem_Cache $file_cache, Remote_Image_Service $remote_images ) {
		$this->file_cache    = $file_cache;
		$this->remote_images = $remote_images;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $url   The legacy image URL.
	 * @param  string $hash  The hashed URL.
	 * @param  int    $size  The size of the avatar image in pixels.
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type string $mimetype Optional. The expected MIME type of the avatar image. Default based on the URL file extension.
	 *     @type bool   $force    Optional. Whether to force re-caching of the image file. Default false.
	 * }
	 *
	 * @return string
	 *
	 * @phpstan-param AvatarArguments $args
	 */
	public function get_url( $url, $hash, $size, array $args ) {
		$defaults = [
			'mimetype' => $this->get_target_mime_type( $url ),
			'force'    => false,
		];

		$args     = \wp_parse_args( $args, $defaults );
		$filename = "legacy/{$this->get_sub_dir( $hash )}/{$hash}-{$size}." . Image_File::FILE_EXTENSION[ $args['mimetype'] ];

		// Only retrieve new Gravatar if necessary.
		if ( $args['force'] || ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) ) {
			// Retrieve the legacy icon.
			$icon = $this->remote_images->get_image( $url, $size, $args['mimetype'] );

			// Store it (empty files will fail this check).
			if ( ! $this->file_cache->set( $filename, $icon, $args['force'] ) ) {
				// Something went wrong..
				return $url;
			}
		}

		return $this->file_cache->get_url( $filename );
	}

	/**
	 * Retrieves the target MIME type for our resized image.
	 *
	 * @param  string $url The image URL.
	 *
	 * @return string      The target MIME type ('image/png' if the URL extension
	 *                     is not one of our supported image formats).
	 */
	protected function get_target_mime_type( $url ) {
		$mimetype  = Image_File::PNG_IMAGE;
		$path      = \wp_parse_url( $url, \PHP_URL_PATH );
		$extension = \pathinfo( \is_string( $path ) ? $path : '', \PATHINFO_EXTENSION );

		// Determine MIME type based on the file extension.
		if ( isset( Image_File::CONTENT_TYPE[ $extension ] ) ) {
			$mimetype = Image_File::CONTENT_TYPE[ $extension ];
		}

		return $mimetype;
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 *
	 * @return string
	 */
	protected function get_sub_dir( $identity ) {
		return \implode( '/', \str_split( \substr( $identity, 0, 2 ) ) );
	}

	/**
	 * Caches the image specified by the parameters.
	 *
	 * @param  string $type      The image (sub-)type. Ignored.
	 * @param  string $hash      The hashed mail address.
	 * @param  int    $size      The requested size in pixels.
	 * @param  string $subdir    The requested sub-directory.
	 * @param  string $extension The requested file extension.
	 *
	 * @return bool              Returns `true` if successful, `false` otherwise.
	 */
	public function cache_image( $type, $hash, $size, $subdir, $extension ) {

		// Lookup image URL.
		$url = $this->remote_images->get_image_url( $hash );

		// Could not find legacy image URL.
		if ( empty( $url ) ) {
			return false;
		}

		// Prepare arguments.
		$args = [
			'mimetype' => Image_File::CONTENT_TYPE[ $extension ],
		];

		// Try to cache the icon.
		return ! empty( $this->get_url( $url, $hash, $size, $args ) );
	}

	/**
	 * Retrieves the name of the cache subdirectory for avatars provided by this
	 * handler (e.g. 'gravatar'). Implementations may return an empty string if
	 * the actual type can vary.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'legacy';
	}
}
