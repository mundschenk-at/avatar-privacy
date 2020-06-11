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
use Avatar_Privacy\Core\Hasher;


/**
 * The API for handling data attached to registered users as part of the
 * Avatar_Privacy Core API.
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
	 * Creates a \Avatar_Privacy\Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @param Hasher $hasher  Required.
	 */
	public function __construct( Hasher $hasher ) {
		$this->hasher = $hasher;
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
		 * @param array|null {
		 *     Optional. The user avatar information. Default null.
		 *
		 *     @type string $file The local filename.
		 *     @type string $type The MIME type.
		 * }
		 * @param int $user_id The user ID.
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
}
