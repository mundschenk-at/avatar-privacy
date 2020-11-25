<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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

use Carbon_Fields\Field\Field;

/**
 * An integration for WP User Manager.
 *
 * @since  2.2.0
 * @since  2.3.0 Methods and properties related to obsolete profile picture upload hnadling removed.
 * @since  2.4.0 The $upload property has been replaced by the new user data API ($user_fields).
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class WP_User_Manager_Integration implements Plugin_Integration {

	const WP_USER_MANAGER_META_KEY = 'current_user_avatar';

	/**
	 * The user data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * A flag indicating that the user avatar cache should be invalidated soon.
	 *
	 * @var bool
	 */
	private $flush_cache = false;

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
	 * Check if the WP_User_Manager integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \class_exists( \WP_User_Manager::class ) && \function_exists( 'wpum_get_option' ) && \wpum_get_option( 'custom_avatars' );
	}

	/**
	 * Activate the integration.
	 */
	public function run() {
		// Disable profile image uploading.
		\add_filter( 'avatar_privacy_profile_picture_upload_disabled', '__return_true' );

		// Serve WP User Manager profile pictures via the filesystem cache.
		\add_filter( 'avatar_privacy_pre_get_user_avatar',      [ $this, 'enable_wpusermanager_user_avatars' ], 10, 2 );
		\add_filter( 'carbon_fields_should_save_field_value',   [ $this, 'maybe_mark_user_avater_for_cache_flushing' ], 9999, 3 );
		\add_action( 'carbon_fields_user_meta_container_saved', [ $this, 'maybe_flush_cache_after_saving_user_avatar' ], 10, 1 );
	}

	/**
	 * Retrieves the user avatar from WP User Manager.
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
	public function enable_wpusermanager_user_avatars( array $avatar = null, $user_id ) {
		$file = /* @scrutinizer ignore-call */ \carbon_get_user_meta( $user_id, self::WP_USER_MANAGER_META_KEY );
		$type = \wp_check_filetype( $file )['type'];

		return [
			'file' => $file,
			'type' => $type,
		];
	}

	/**
	 * Marks the user avatar cache for flushing if the corresponding WP_User_Manager
	 * field is about to been changed.
	 *
	 * @param  bool  $save  Whether the field should be saved. Passed on as-is.
	 * @param  mixed $value The field value. Ignored.
	 * @param  Field $field A Carbon Fields object.
	 *
	 * @return bool
	 */
	public function maybe_mark_user_avater_for_cache_flushing( $save, $value, Field $field ) {
		if ( $field->get_base_name() === self::WP_USER_MANAGER_META_KEY ) {
			$this->flush_cache = true;
		}

		return $save;
	}

	/**
	 * Flushes the user avatar cache if necessary.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return void
	 */
	public function maybe_flush_cache_after_saving_user_avatar( $user_id ) {
		if ( $this->flush_cache ) {
			$this->user_fields->invalidate_local_avatar_cache( $user_id );
		}
	}
}
