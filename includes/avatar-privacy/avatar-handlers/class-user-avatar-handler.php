<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Images\Image_File;


/**
 * Handles image caching for uploaded user avatars.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type AvatarArguments array{
 *     avatar?: string,
 *     mimetype?: string,
 *     timestamp?: bool,
 *     force?: bool
 * }
 */
class User_Avatar_Handler implements Avatar_Handler {

	/**
	 * The core API.
	 *
	 * @var User_Fields
	 */
	private User_Fields $user_fields;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private Filesystem_Cache $file_cache;

	/**
	 * The uploads base directory.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private Images\Editor $images;

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
	 * Note: Not all of the arguments provided by the `avatar_privacy_user_avatar_icon_url`
	 * filter hook are actually required.
	 *
	 * @since  2.7.0 Unused arguments key 'type' removed.
	 *
	 * @param  string $url  The fallback image URL.
	 * @param  string $hash The hashed mail address.
	 * @param  int    $size The size of the avatar image in pixels.
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type string $avatar    The full-size avatar image path.
	 *     @type string $mimetype  Optional. The expected MIME type of the avatar image. Default 'image/png'.
	 *     @type bool   $timestamp Optional. Whether to add a timestamp for cache busting. Default false.
	 *     @type bool   $force     Optional. Whether to force the regeneration of the image file. Default false.
	 * }
	 *
	 * @return string
	 *
	 * @phpstan-param AvatarArguments $args
	 */
	public function get_url( $url, $hash, $size, array $args ) {
		// Cache base directory.
		if ( empty( $this->base_dir ) ) {
			$this->base_dir = $this->file_cache->get_base_dir();
		}

		// Default arguments.
		$defaults = [
			'avatar'    => '',
			'mimetype'  => Image_File::PNG_IMAGE,
			'timestamp' => false,
			'force'     => false,
		];

		$args      = \wp_parse_args( $args, $defaults );
		$extension = Image_File::FILE_EXTENSION[ $args['mimetype'] ];
		$filename  = "user/{$this->get_sub_dir( $hash )}/{$hash}-{$size}.{$extension}";
		$abspath   = "{$this->base_dir}{$filename}";

		if ( $args['force'] || ! \file_exists( $abspath ) ) {
			$data = $this->images->get_resized_image_data(
				$this->images->get_image_editor( $args['avatar'] ), $size, $size, $args['mimetype']
			);

			// Save the generated PNG file (empty files will fail this check).
			if ( ! $this->file_cache->set( $filename, $data, $args['force'] ) ) {
				// Something went wrong..
				return $url;
			}
		}

		// Optionally add file modification time as `ts` query argument to bust caches.
		$query_args = [
			'ts' => $args['timestamp'] ? @\filemtime( $abspath ) : false,
		];

		return \add_query_arg(
			\rawurlencode_deep( \array_filter( $query_args ) ),
			$this->file_cache->get_url( $filename )
		);
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
			'avatar'    => $local_avatar['file'],
			'mimetype'  => $local_avatar['type'],
			'subdir'    => $subdir,
			'extension' => $extension,
		];

		// Try to cache the icon.
		return ! empty( $this->get_url( '', $hash, $size, $args ) );
	}

	/**
	 * Retrieves the name of the cache subdirectory for avatars provided by this
	 * handler (e.g. 'gravatar'). Implementations may return an empty string if
	 * the actual type can vary.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_type() {
		return 'user';
	}
}
