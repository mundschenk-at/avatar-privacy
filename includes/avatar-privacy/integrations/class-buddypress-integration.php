<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2023 Peter Putzer.
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

/**
 * An integration for BuddyPress.
 *
 * @since      2.3.0
 * @author     Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-import-type AvatarDefinition from User_Fields
 *
 * @phpstan-type BuddyPressAvatarParams array{
 *    item_id: int|false,
 *    object: string,
 *    type: string,
 *    avatar_dir: string|false,
 *    width: int|false,
 *    height: int|false,
 *    class: string,
 *    css_id: string|false,
 *    title: string,
 *    alt: string,
 *    email: string|false,
 *    no_grav: bool,
 *    html: bool,
 *    extra_attr: string,
 *    scheme: string,
 *    rating: string,
 *    force_default: bool
 * }
 */
class BuddyPress_Integration implements Plugin_Integration {

	/**
	 * The user data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private User_Fields $user_fields;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0 Parameter $uploads removed, $user_fields added.
	 *
	 * @param User_Fields $user_fields The user data API.
	 */
	public function __construct( User_Fields $user_fields ) {
		$this->user_fields = $user_fields;
	}

	/**
	 * Check if the BuddyPress integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \class_exists( \BuddyPress::class );
	}

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public function run() {
		// Integrate with BuddyPress's avatar handling.
		\add_action( 'init', [ $this, 'integrate_with_buddypress_avatars' ] );
	}

	/**
	 * Removes the BuddyPress avatar filter and disables Avatar Privacy
	 * profile image upload.
	 *
	 * @return void
	 */
	public function integrate_with_buddypress_avatars() {
		if ( ! \function_exists( 'bp_get_version' ) ) {
			return;
		}

		// Set version dependent hooks.
		$version = \bp_get_version();
		if ( \version_compare( $version, '6.0.0', '<' ) ) {
			$avatar_uploaded_hook = 'xprofile_avatar_uploaded';
		} else {
			$avatar_uploaded_hook = 'bp_members_avatar_uploaded';
		}

		// Remove BuddyPress avatar filter.
		\remove_filter( 'get_avatar_url', 'bp_core_get_avatar_data_url_filter', 10 );

		// Disable BuddyPress Gravatar usage.
		\add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true', 10, 0 );

		// Disable profile image uploading.
		\add_filter( 'avatar_privacy_profile_picture_upload_disabled', '__return_true', 10, 0 );

		// Serve BuddyPress profile pictures via the filesystem cache.
		\add_filter( 'avatar_privacy_pre_get_user_avatar', [ $this, 'enable_buddypress_user_avatars' ], 10, 2 );

		// Add our own default avatars instead (for users).
		\add_filter( 'bp_core_default_avatar_user', [ $this, 'add_default_avatars_to_buddypress' ], 10, 2 );
		\add_filter( 'bp_get_user_has_avatar', [ $this, 'has_buddypress_avatar' ], 10, 2 );

		// Invalidate cache when a new image is uploaded or deleted.
		\add_action( $avatar_uploaded_hook, [ $this, 'invalidate_cache_after_avatar_upload' ], 10, 3 );
		\add_action( 'bp_core_delete_existing_avatar', [ $this, 'invalidate_cache_after_avatar_deletion' ], 10, 1 );
	}

	/**
	 * Retrieves the user avatar from BuddyPress.
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
	public function enable_buddypress_user_avatars( array $avatar = null, $user_id ) {
		// Prevent loops.
		if ( \doing_filter( 'bp_core_default_avatar_user' ) ) {
			return $avatar;
		}

		// Retrieve BuddyPress user data.
		$avatar = $this->get_buddypress_avatar( $user_id );

		if ( ! empty( $avatar ) ) {
			$file = \ABSPATH . \wp_make_link_relative( $avatar );
			$type = \wp_check_filetype( $file )['type'];

			if ( ! empty( $type ) ) {
				return [
					'file' => $file,
					'type' => $type,
				];
			}
		}

		return null;
	}

	/**
	 * Invalidates the file cache after a new BuddyPress avatar has been uploaded.
	 *
	 * @param  int    $item_id The user ID (if `$args['object']` is `user` ).
	 * @param  string $type    Information about the capture method for the avatar.
	 * @param  array  $args    Arguments for the avatar function.
	 *
	 * @return void
	 *
	 * @phpstan-param BuddyPressAvatarParams $args
	 */
	public function invalidate_cache_after_avatar_upload( $item_id, $type, array $args ) {
		if ( ! empty( $args['object'] ) && 'user' === $args['object'] && ! empty( $item_id ) ) {
			$this->user_fields->invalidate_local_avatar_cache( $item_id );
		}
	}

	/**
	 * Invalidates the file cache after a new BuddyPress avatar has been deleted.
	 *
	 * @since  2.4.0
	 *
	 * @param  array $args Array of arguments used for avatar deletion.
	 *
	 * @return void
	 *
	 * @phpstan-param BuddyPressAvatarParams $args
	 */
	public function invalidate_cache_after_avatar_deletion( array $args ) {
		if ( ! empty( $args['item_id'] ) ) {
			$this->invalidate_cache_after_avatar_upload( $args['item_id'], 'delete', $args );
		}
	}

	/**
	 * Adds Avatar Privacy's default avatars to BuddyPress.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $default Default avatar for non-gravatar requests.
	 * @param  array  $params  Array of parameters for the avatar request.
	 *
	 * @return string
	 *
	 * @phpstan-param BuddyPressAvatarParams $params
	 */
	public function add_default_avatars_to_buddypress( $default, array $params ) {
		// Retrieve default avatar URL (Gravatar or local default avatar).
		$args           = [
			'rating' => $params['rating'],
			'size'   => (int) $params['width'],
		];
		$default_avatar = \get_avatar_url( $params['item_id'], $args );

		return $default_avatar ?: $default;
	}

	/**
	 * Determines whether a user has an avatar that has been uploaded in BuddyPress.
	 *
	 * @since  2.4.0
	 *
	 * @param  bool $retval  The return value calculated by BuddyPress (ignored).
	 * @param  int  $user_id The user ID.
	 *
	 * @return bool
	 */
	public function has_buddypress_avatar( $retval, $user_id ) {
		return ! empty( $this->get_buddypress_avatar( $user_id ) );
	}

	/**
	 * Retrieves the user avatar uploaded in BuddyPress (if any).
	 *
	 * @since  2.4.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string
	 */
	protected function get_buddypress_avatar( $user_id ) {
		\add_filter( 'bp_core_default_avatar_user', '__return_empty_string' );
		$avatar = \bp_core_fetch_avatar( [
			'item_id' => $user_id,
			'object'  => 'user',
			'type'    => 'full',
			'html'    => false,
			'no_grav' => true,
		] );
		\remove_filter( 'bp_core_default_avatar_user', '__return_empty_string' );

		return $avatar;
	}
}
