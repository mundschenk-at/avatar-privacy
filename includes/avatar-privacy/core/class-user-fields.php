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

namespace Avatar_Privacy\Core;

use Avatar_Privacy\Core\API;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * The API for handling data attached to registered users as part of the
 * Avatar Privacy Core API.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Fields implements API {

	/**
	 * The user meta key for the hashed email.
	 *
	 * @var string
	 */
	const EMAIL_HASH_META_KEY = 'avatar_privacy_hash';

	/**
	 * The user meta key for the gravatar use flag.
	 *
	 * @var string
	 */
	const GRAVATAR_USE_META_KEY = 'avatar_privacy_use_gravatar';

	/**
	 * The user meta key for the gravatar use flag.
	 *
	 * @var string
	 */
	const ALLOW_ANONYMOUS_META_KEY = 'avatar_privacy_allow_anonymous';

	/**
	 * The user meta key for the local avatar.
	 *
	 * @var string
	 */
	const USER_AVATAR_META_KEY = 'avatar_privacy_user_avatar';

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
	 * @param Hasher           $hasher     The hashing helper..
	 * @param Filesystem_Cache $file_cache The file cache handler.
	 * @param Image_File       $image_file The image file handler.
	 */
	public function __construct( Hasher $hasher, Filesystem_Cache $file_cache, Image_File $image_file ) {
		$this->hasher     = $hasher;
		$this->file_cache = $file_cache;
		$this->image_file = $image_file;
	}

	/**
	 * Retrieves the hash for the given user ID. If there currently is no hash,
	 * a new one is generated.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string|false The hashed email, or `false` on failure.
	 */
	public function get_hash( $user_id ) {
		$hash = \get_user_meta( $user_id, self::EMAIL_HASH_META_KEY, true );

		if ( empty( $hash ) ) {
			$user = \get_user_by( 'ID', $user_id );

			if ( ! empty( $user->user_email ) ) {
				$hash = $this->hasher->get_hash( $user->user_email );
				\update_user_meta( $user_id, self::EMAIL_HASH_META_KEY, $hash );
			}
		}

		return $hash;
	}

	/**
	 * Retrieves a user by email hash.
	 *
	 * @param string $hash The user's email hash.
	 *
	 * @return \WP_User|null
	 */
	public function get_user_by_hash( $hash ) {
		// No extra caching necessary, WP Core already does that for us.
		$args  = [
			'number'       => 1,
			'meta_key'     => self::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => '=',
		];
		$users = \get_users( $args );

		if ( empty( $users ) ) {
			return null;
		}

		return $users[0];
	}

	/**
	 * Retrieves the full-size local avatar for a user (if one exists).
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return array {
	 *     An avatar definition, or the empty array.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 */
	public function get_local_avatar( $user_id ) {
		/**
		 * Filters whether to retrieve the user avatar early. If the filtered result
		 * contains both a filename and a MIME type, those will be returned immediately.
		 *
		 * @since 2.2.0
		 *
		 * @param array|null $avatar {
		 *     Optional. The user avatar information. Default null.
		 *
		 *     @type string  $file The local filename.
		 *     @type string  $type The MIME type.
		 * }
		 * @param int        $user_id The user ID.
		 */
		$avatar = \apply_filters( 'avatar_privacy_pre_get_user_avatar', null, $user_id );
		if ( ! empty( $avatar ) && ! empty( $avatar['file'] ) && ! empty( $avatar['type'] ) ) {
			return $avatar;
		}

		$avatar = \get_user_meta( $user_id, self::USER_AVATAR_META_KEY, true );
		if ( empty( $avatar ) ) {
			$avatar = [];
		}

		return $avatar;
	}

	/**
	 * Sets the local avatar for the given user.
	 *
	 * Please note that the calling function is responsible for cleaning up the
	 * provided image if it is a temporary file (i.e the image is copied before
	 * being used as the new avatar).
	 *
	 * @param  int    $user_id   The user ID.
	 * @param  string $image_url The image URL or filename.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException An exception is thrown if the user ID does
	 *                                   not exist or the upload result does not
	 *                                   contain the 'file' key.
	 * @throws \RuntimeException         A `RuntimeException` is thrown if the sideloading
	 *                                   fails for some reason.
	 */
	public function set_local_avatar( $user_id, $image_url ) {
		// Prepare arguments.
		$overrides = [
			'global_upload' => true,
			'upload_dir'    => User_Avatar_Upload_Handler::UPLOAD_DIR,
		];

		$sideloaded_avatar = $this->image_file->handle_sideload( $image_url, $overrides );

		$this->set_uploaded_local_avatar( $user_id, $sideloaded_avatar );
	}

	/**
	 * Sets the local avatar to the uploaded image.
	 *
	 * @internal
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_avatar {
	 *     The uploaded avatar information (the result of Image_File::handle_upload()).
	 *
	 *     @type string $file The image file path.
	 *     @type string $type The MIME type of the uploaded image.
	 * }
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException An exception is thrown if the user ID does
	 *                                   not exist or the upload result does not
	 *                                   contain the 'file' key.
	 */
	public function set_uploaded_local_avatar( $user_id, $uploaded_avatar ) {
		if ( ! $this->user_exists( $user_id ) ) {
			throw new \InvalidArgumentException( "Invalid user ID {$user_id}" );
		} elseif ( empty( $uploaded_avatar['file'] ) ) {
			throw new \InvalidArgumentException( 'Missing upload file path' );
		} elseif ( empty( $uploaded_avatar['type'] ) ) {
			throw new \InvalidArgumentException( 'Missing image MIME type' );
		} elseif ( ! isset( Image_File::FILE_EXTENSION[ $uploaded_avatar['type'] ] ) ) {
			throw new \InvalidArgumentException( "Invalid MIME type {$uploaded_avatar['type']}" );
		}

		// Delete old images.
		$this->delete_local_avatar( $user_id );

		// Save user information (overwriting previous).
		\update_user_meta( $user_id, self::USER_AVATAR_META_KEY, $uploaded_avatar );
	}

	/**
	 * Checks whether the given user ID is valid.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return bool
	 */
	protected function user_exists( $user_id ) {
		return (bool) \get_users(
			[
				'include' => $user_id,
				'fields'  => 'ID',
			]
		);
	}

	/**
	 * Deletes the local avatar of the given user.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function delete_local_avatar( $user_id ) {
		// Invalidate cached avatar images.
		$this->invalidate_local_avatar_cache( $user_id );

		// Delete original upload.
		$avatar = \get_user_meta( $user_id, self::USER_AVATAR_META_KEY, true );
		if ( ! empty( $avatar['file'] ) && \file_exists( $avatar['file'] ) && \unlink( $avatar['file'] ) ) {
			return \delete_user_meta( $user_id, self::USER_AVATAR_META_KEY );
		}

		return false;
	}

	/**
	 * Invalidates cached avatar images.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return void
	 */
	public function invalidate_local_avatar_cache( $user_id ) {
		$hash = $this->get_hash( $user_id );
		if ( ! empty( $hash ) ) {
			$this->file_cache->invalidate( 'user', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );
		}
	}
}
