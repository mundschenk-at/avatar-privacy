<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2021-2023 Peter Putzer.
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

namespace Avatar_Privacy\Integrations;

use Avatar_Privacy\Integrations\Plugin_Integration;
use Avatar_Privacy\Core\User_Fields;

use Simple_Local_Avatars;

/**
 * An integration for Simple Local Avatars.
 *
 * @since      2.5.0
 * @author     Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-import-type AvatarDefinition from User_Fields
 */
class Simple_Local_Avatars_Integration implements Plugin_Integration {

	const USER_META_KEY = 'simple_local_avatar';

	/**
	 * The user data helper.
	 *
	 * @var User_Fields
	 */
	private User_Fields $user_fields;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Fields $user_fields The user data API.
	 */
	public function __construct( User_Fields $user_fields ) {
		$this->user_fields = $user_fields;
	}

	/**
	 * Check if the bbPress integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \class_exists( Simple_Local_Avatars::class ) && \method_exists( Simple_Local_Avatars::class, 'get_avatar_rest' );
	}

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Init action handler.
	 *
	 * @global Simple_Local_Avatars $simple_local_avatars The Simple Local Avatars singleton.
	 *
	 * @return void
	 */
	public function init() {
		global $simple_local_avatars;

		// Disable profile image uploading.
		\add_filter( 'avatar_privacy_profile_picture_upload_disabled', '__return_true', 10, 0 );

		// Disable Simple Local Avatars' avatar integration.
		\remove_filter( 'pre_get_avatar_data', [ $simple_local_avatars, 'get_avatar_data' ], 10 );

		// Serve Simple Local Avatars profile pictures via the filesystem cache.
		\add_filter( 'avatar_privacy_pre_get_user_avatar', [ $this, 'enable_user_avatars' ], 10, 2 );

		// Invalidate cache when a new image is uploaded or deleted.
		\add_action( 'added_user_meta', [ $this, 'invalidate_cache_after_avatar_change' ], 10, 3 );
		\add_action( 'updated_user_meta', [ $this, 'invalidate_cache_after_avatar_change' ], 10, 3 );
		\add_action( 'deleted_user_meta', [ $this, 'invalidate_cache_after_avatar_change' ], 10, 3 );
	}

	/**
	 * Retrieves the user avatar from Simple Local Avatars.
	 *
	 * @param  array|null $avatar  Optional. The user avatar information. Default null.
	 * @param  int        $user_id The user ID.
	 *
	 * @return array|null {
	 *     Optional. The user avatar information. Default null.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 *
	 * @phpstan-param  AvatarDefinition|null $avatar
	 * @phpstan-return AvatarDefinition|null
	 */
	public function enable_user_avatars( array $avatar = null, $user_id ) {
		// Retrieve Simple Local Avatars image.
		$local_avatar = $this->get_simple_local_avatars_avatar( $user_id );

		if ( ! empty( $local_avatar ) ) {
			$type = \wp_check_filetype( $local_avatar )['type'];
			if ( ! empty( $type ) ) {
				return [
					'file' => $local_avatar,
					'type' => $type,
				];
			}
		}

		return $avatar;
	}

	/**
	 * Retrieves the user avatar uploaded in Simple Local Avatars (if any).
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string
	 */
	protected function get_simple_local_avatars_avatar( $user_id ) {
		global $simple_local_avatars;

		$local_avatar = $simple_local_avatars->get_avatar_rest( [ 'id' => $user_id ] );
		if ( ! \is_array( $local_avatar ) || empty( $local_avatar['full'] ) || ! \is_string( $local_avatar['full'] ) ) {
			return '';
		}

		return $local_avatar['full'];
	}

	/**
	 * Invalidates the file cache after a new Simple Local Avatars avatar has been
	 * uploaded or deleted.
	 *
	 * @param  int|string[] $meta_id  The ID(s) of the modified metadata entries.
	 * @param  int          $user_id  The user ID.
	 * @param  string       $meta_key The metadata key.
	 *
	 * @return void
	 */
	public function invalidate_cache_after_avatar_change( $meta_id, $user_id, $meta_key ) {
		if ( self::USER_META_KEY !== $meta_key || empty( $meta_id ) ) {
			return;
		}

		$this->user_fields->invalidate_local_avatar_cache( $user_id );
	}
}
