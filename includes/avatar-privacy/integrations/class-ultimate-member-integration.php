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
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * An integration for Ultimate Member.
 *
 * @since      2.3.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Ultimate_Member_Integration implements Plugin_Integration {

	/**
	 * The avatar upload handler.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private User_Avatar_Upload_Handler $upload;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Avatar_Upload_Handler $upload  The avatar upload handler.
	 */
	public function __construct( User_Avatar_Upload_Handler $upload ) {
		$this->upload = $upload;
	}

	/**
	 * Check if the Ultimate Member integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \class_exists( \UM::class ) && \function_exists( 'um_get_user_avatar_data' );
	}

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public function run() {
		// Integrate with Ultimate Member's avatar handling.
		\add_action( 'init', [ $this, 'integrate_with_ultimate_member_avatars' ] );

		// Disable Ultimate Member's 'use_gravatars' setting a bit earlier to be sure..
		\add_filter( 'um_settings_structure',    [ $this, 'remove_ultimate_member_gravatar_settings' ], 10, 1 );
		\add_filter( 'um_options_use_gravatars', '__return_false',                                      10, 1 );
	}

	/**
	 * Removes the Ultimate Member avatar filter and disables Avatar Privacy
	 * profile image upload.
	 *
	 * @return void
	 */
	public function integrate_with_ultimate_member_avatars() {
		// Remove Ultimate Member avatar filter.
		\remove_filter( 'get_avatar', 'um_get_avatar', 99999 );

		// Disable profile image uploading.
		\add_filter( 'avatar_privacy_profile_picture_upload_disabled', '__return_true', 10, 0 );

		// Serve Ultime Member profile pictures via the filesystem cache.
		\add_filter( 'avatar_privacy_pre_get_user_avatar', [ $this, 'enable_ultimate_member_user_avatars' ],      10, 2 );

		// Invalidate cache when a new image is uploaded.
		\add_action( 'um_after_upload_db_meta_profile_photo', [ $this->upload, 'invalidate_user_avatar_cache' ], 10, 1 );
	}

	/**
	 * Filters the Ultimate Member settings structure to remove conflicting checkboxes
	 * on Gravatar use.
	 *
	 * @param  array $settings_structure The settings page definition.
	 *
	 * @return array
	 */
	public function remove_ultimate_member_gravatar_settings( array $settings_structure ) {
		if (
			isset( $settings_structure['']['sections']['users']['fields'] ) &&
			\is_array( $settings_structure['']['sections']['users']['fields'] )
		) {
			/**
			 * Iterate over the fields.
			 *
			 * @var array $field An Ultimate Member field.
			 */
			foreach ( $settings_structure['']['sections']['users']['fields'] as &$field ) {
				if ( 'use_gravatars' === $field['id'] ) {
					// Make conditional on non-existing setting (hiding it).
					$field['conditional'] = [ 'avatar_privacy_active', '=', 0 ];

					// That's all.
					break;
				}
			}
		}

		return $settings_structure;
	}

	/**
	 * Retrieves the user avatar from Ultimate Member.
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
	public function enable_ultimate_member_user_avatars( array $avatar = null, $user_id ) {
		// Retrieve Ultimate Member user data.
		\add_filter( 'um_filter_avatar_cache_time', '__return_null' );
		$um_profile = \um_get_user_avatar_data( $user_id, 'original' );
		\remove_filter( 'um_filter_avatar_cache_time', '__return_null' );

		if ( empty( $um_profile['type'] ) || 'upload' !== $um_profile['type'] || empty( $um_profile['url'] ) ) {
			return null;
		}

		$file = \ABSPATH . \wp_make_link_relative( $um_profile['url'] );
		$type = \wp_check_filetype( $file )['type'];

		return [
			'file' => $file,
			'type' => $type,
		];
	}
}
