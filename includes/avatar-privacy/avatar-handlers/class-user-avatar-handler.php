<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Tools\Images;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Handles image caching for uploaded user avatars.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Avatar_Handler implements Avatar_Handler {

	/**
	 * The core API.
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The uploads base directory.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $images;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0 Parameter $core replaced by $user_fields.
	 *
	 * @param User_Fields      $user_fields The user data API.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 * @param Images\Editor    $images      The image editing handler.
	 */
	public function __construct( User_Fields $user_fields, Filesystem_Cache $file_cache, Images\Editor $images ) {
		$this->user_fields = $user_fields;
		$this->file_cache  = $file_cache;
		$this->images      = $images;
	}

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @param  string $url  The fallback image URL.
	 * @param  string $hash The hashed mail address.
	 * @param  int    $size The size of the avatar image in pixels.
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type string $type     The avatar/icon type.
	 *     @type string $avatar   The full-size avatar image path.
	 *     @type string $mimetype The expected MIME type of the avatar image.
	 *     @type bool   $force    Optional. Whether to force the regeneration of the image file. Default false.
	 * }
	 *
	 * @return string
	 */
	public function get_url( $url, $hash, $size, array $args ) {
		// Cache base directory.
		if ( empty( $this->base_dir ) ) {
			$this->base_dir = $this->file_cache->get_base_dir();
		}

		// Default arguments.
		$defaults = [
			'avatar'   => '',
			'mimetype' => Images\Type::PNG_IMAGE,
			'force'    => false,
		];

		$args      = \wp_parse_args( $args, $defaults );
		$extension = Images\Type::FILE_EXTENSION[ $args['mimetype'] ];
		$filename  = "user/{$this->get_sub_dir( $hash )}/{$hash}-{$size}.{$extension}";

		if ( $args['force'] || ! \file_exists( "{$this->base_dir}{$filename}" ) ) {
			$data = $this->images->get_resized_image_data(
				$this->images->get_image_editor( $args['avatar'] ), $size, $size, $args['mimetype']
			);

			// Save the generated PNG file (empty files will fail this check).
			if ( ! $this->file_cache->set( $filename, $data, $args['force'] ) ) {
				// Something went wrong..
				return $url;
			}
		}

		return $this->file_cache->get_url( $filename );
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @since 2.1.0 Visibility changed to protected.
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
	 * @since 2.0.0
	 *
	 * @param  string $type      The image (sub-)type.
	 * @param  string $hash      The hashed mail address.
	 * @param  int    $size      The requested size in pixels.
	 * @param  string $subdir    The requested sub-directory.
	 * @param  string $extension The requested file extension.
	 *
	 * @return bool              Returns `true` if successful, `false` otherwise.
	 */
	public function cache_image( $type, $hash, $size, $subdir, $extension ) {
		$user = $this->user_fields->get_user_by_hash( $hash );
		if ( ! empty( $user ) ) {
			$local_avatar = $this->user_fields->get_local_avatar( $user->ID );
		}

		// Could not find user or uploaded avatar.
		if ( empty( $local_avatar ) ) {
			return false;
		}

		// Prepare arguments.
		$args = [
			'type'      => $type,
			'avatar'    => $local_avatar['file'],
			'mimetype'  => $local_avatar['type'],
			'subdir'    => $subdir,
			'extension' => $extension,
		];

		// Try to cache the icon.
		return ! empty( $this->get_url( '', $hash, $size, $args ) );
	}
}
