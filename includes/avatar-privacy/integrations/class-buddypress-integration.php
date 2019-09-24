<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * An integration for BuddyPress.
 *
 * @since      2.3.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class BuddyPress_Integration implements Plugin_Integration {

	/**
	 * The avatar upload handler.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $upload;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Avatar_Upload_Handler $upload  The avatar upload handler.
	 */
	public function __construct( User_Avatar_Upload_Handler $upload ) {
		$this->upload = $upload;
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
	 */
	public function run() {
		// Integrate with BuddyPress's avatar handling.
		\add_action( 'init', [ $this, 'integrate_with_buddypress_avatars' ] );
	}

	/**
	 * Removes the BuddyPress avatar filter and disables Avatar Privacy
	 * profile image upload.
	 */
	public function integrate_with_buddypress_avatars() {
		// Remove BuddyPress avatar filter.
		\remove_filter( 'get_avatar_url', 'bp_core_get_avatar_data_url_filter', 10 );

		// Disable profile image uploading.
		\add_filter( 'avatar_privacy_profile_picture_upload_disabled', '__return_true', 10, 0 );

		// Serve BuddyPress profile pictures via the filesystem cache.
		\add_filter( 'avatar_privacy_pre_get_user_avatar', [ $this, 'enable_buddypress_user_avatars' ], 10, 2 );

		// Invalidate cache when a new image is uploaded.
		\add_action( 'xprofile_avatar_uploaded', [ $this, 'invalidate_cache_after_avatar_upload' ], 10, 3 );
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
	 */
	public function enable_buddypress_user_avatars( array $avatar = null, $user_id ) {
		// Retrieve BuddyPress user data.
		\add_filter( 'bp_core_default_avatar_user', '__return_empty_string' );
		$avatar = /* @scrutinizer ignore-call */ \bp_core_fetch_avatar( [
			'item_id' => $user_id,
			'object'  => 'user',
			'type'    => 'full',
			'html'    => false,
			'no_grav' => true,
		] );
		\remove_filter( 'bp_core_default_avatar_user', '__return_empty_string' );

		if ( empty( $avatar ) ) {
			return null;
		}

		$file = \ABSPATH . \wp_make_link_relative( $avatar );
		$type = \wp_check_filetype( $file )['type'];

		return [
			'file' => $file,
			'type' => $type,
		];
	}

	/**
	 * Invalidates the file cache after a new BuddyPress avatar has been uploaded.
	 *
	 * @param  int    $item_id The user ID (if `$args['object']` is `user` ).
	 * @param  string $type    Information about the capture method for the avatar.
	 * @param  array  $args    Arguments for the avatar function.
	 */
	public function invalidate_cache_after_avatar_upload( $item_id, $type, array $args ) {
		if ( ! empty( $args['object'] ) && 'user' === $args['object'] && ! empty( $args['item_id'] ) ) {
			$this->upload->invalidate_user_avatar_cache( $args['item_id'] );
		}
	}
}
