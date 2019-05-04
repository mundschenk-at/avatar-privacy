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

use Avatar_Privacy\Core;
use Avatar_Privacy\Components\User_Profile;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

use Carbon_Fields\Field\Field;

/**
 * An integration for WP User Manager.
 *
 * @since      2.2.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class WP_User_Manager_Integration implements Plugin_Integration {

	const WP_USER_MANAGER_META_KEY = 'current_user_avatar';

	/**
	 * The user profile component.
	 *
	 * @var User_Profile
	 */
	private $profile;

	/**
	 * The avatar upload handler.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $upload;

	/**
	 * A flag indicating that the user avatar cache should be invalidated soon.
	 *
	 * @var bool
	 */
	private $flush_cache = false;

	/**
	 * Indiciates whether the settings page is buffering its output.
	 *
	 * @var bool
	 */
	private $buffering;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Profile               $profile The user profile component.
	 * @param User_Avatar_Upload_Handler $upload  The avatar upload handler.
	 */
	public function __construct( User_Profile $profile, User_Avatar_Upload_Handler $upload ) {
		$this->profile = $profile;
		$this->upload  = $upload;
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
	 *
	 * @param Core $core The plugin instance.
	 */
	public function run( Core $core ) {
		if ( \is_admin() ) {
			\add_action( 'admin_init', [ $this, 'remove_profile_picture_upload' ] );
		}

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
		$file = \carbon_get_user_meta( $user_id, self::WP_USER_MANAGER_META_KEY );
		$type = \wp_check_filetype( $file )['type'];

		return [
			'file' => $file,
			'type' => $type,
		];
	}

	/**
	 * Enables output buffering.
	 */
	public function admin_head() {
		if ( \ob_start( [ $this, 'remove_profile_picture_section' ] ) ) {
			$this->buffering = true;
		}
	}

	/**
	 * Cleans up any output buffering.
	 */
	public function admin_footer() {
		// Clean up output buffering.
		if ( $this->buffering && \ob_get_level() > 0 ) {
			\ob_end_flush();
			$this->buffering = false;
		}
	}

	/**
	 * Remove the profile picture section from the user profile screen.
	 *
	 * @param  string $content The captured HTML output.
	 *
	 * @return string
	 */
	public function remove_profile_picture_section( $content ) {
		return \preg_replace( '#(<tr class="user-profile-picture">.*<p class="description">).*(</p>.*</tr>)#Usi', '$1$2', $content );
	}

	/**
	 * Removes the profile picture upload handling.
	 */
	public function remove_profile_picture_upload() {
		// Unhook the default handlers.
		\remove_action( 'admin_head-profile.php',     [ $this->profile, 'admin_head' ] );
		\remove_action( 'admin_head-user-edit.php',   [ $this->profile, 'admin_head' ] );
		\remove_action( 'admin_footer-profile.php',   [ $this->profile, 'admin_footer' ] );
		\remove_action( 'admin_footer-user-edit.php', [ $this->profile, 'admin_footer' ] );

		// Remove the profile picture setting completely.
		\add_action( 'admin_head-profile.php',     [ $this, 'admin_head' ] );
		\add_action( 'admin_head-user-edit.php',   [ $this, 'admin_head' ] );
		\add_action( 'admin_footer-profile.php',   [ $this, 'admin_footer' ] );
		\add_action( 'admin_footer-user-edit.php', [ $this, 'admin_footer' ] );
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
	 */
	public function maybe_flush_cache_after_saving_user_avatar( $user_id ) {
		if ( ! empty( $this->flush_cache ) ) {
			$this->upload->invalidate_user_avatar_cache( $user_id );
		}
	}
}
