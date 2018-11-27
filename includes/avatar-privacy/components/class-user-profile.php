<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Core;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * Handles privacy-specific updates of the user profile.
 *
 * @since 1.0.0
 */
class User_Profile implements \Avatar_Privacy\Component {

	/**
	 * The name of the checkbox field in the user profile.
	 */
	const CHECKBOX_FIELD_NAME = 'use_gravatar';

	/**
	 * The nonce action for updating the 'use_gravatar' meta field.
	 */
	const ACTION_EDIT_USE_GRAVATAR = 'avatar_privacy_edit_use_gravatar';

	/**
	 * The nonce used for updating the 'use_gravatar' meta field.
	 */
	const NONCE_USE_GRAVATAR = 'avatar_privacy_use_gravatar_nonce_';

	/**
	 * The nonce action for updating the 'allow_anonymous' meta field.
	 */
	const ACTION_EDIT_ALLOW_ANONYMOUS = 'avatar_privacy_edit_allow_anonymous';

	/**
	 * The nonce used for updating the 'allow_anonymous' meta field.
	 */
	const NONCE_ALLOW_ANONYMOUS = 'avatar_privacy_allow_anonymous_nonce_';

	/**
	 * The name of the checkbox field in the user profile.
	 */
	const CHECKBOX_ALLOW_ANONYMOUS = 'avatar_privacy_allow_anonymous_gravatar';



	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The markup to inject.
	 *
	 * @var string
	 */
	private $markup;

	/**
	 * The avatar upload handler.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $upload;

	/**
	 * Indiciates whether the settings page is buffering its output.
	 *
	 * @var bool
	 */
	private $buffering;

	/**
	 * Creates a new instance.
	 *
	 * @param string                     $plugin_file The full path to the base plugin file.
	 * @param User_Avatar_Upload_Handler $upload      The avatar upload handler.
	 */
	public function __construct( $plugin_file, User_Avatar_Upload_Handler $upload ) {
		$this->plugin_file = $plugin_file;
		$this->upload      = $upload;
		$this->buffering   = false;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_admin() ) {
			\add_action( 'admin_init', [ $this, 'admin_init' ] );
		}
	}

	/**
	 * Initialize additional plugin hooks.
	 */
	public function admin_init() {
		// Add the checkbox to the user profile form if we're in the WP backend.
		\add_action( 'show_user_profile',        [ $this, 'add_user_profile_fields' ] );
		\add_action( 'edit_user_profile',        [ $this, 'add_user_profile_fields' ] );
		\add_action( 'personal_options_update',  [ $this, 'save_user_profile_fields' ] );
		\add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_fields' ] );
		\add_action( 'user_edit_form_tag',       [ $this, 'print_form_encoding' ] );

		// Replace profile picture setting with our own settings.
		\add_action( 'admin_head-profile.php',     [ $this, 'admin_head' ] );
		\add_action( 'admin_head-user-edit.php',   [ $this, 'admin_head' ] );
		\add_action( 'admin_footer-profile.php',   [ $this, 'admin_footer' ] );
		\add_action( 'admin_footer-user-edit.php', [ $this, 'admin_footer' ] );
	}

	/**
	 * Enables output buffering.
	 */
	public function admin_head() {
		if ( \ob_start( [ $this, 'replace_profile_picture_section' ] ) ) {
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
	 * Prints the enctype "multipart/form-data".
	 */
	public function print_form_encoding() {
		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Remove the profile picture section from the user profile screen.
	 *
	 * @param  string $content The captured HTML output.
	 *
	 * @return string
	 */
	public function replace_profile_picture_section( $content ) {
		if ( ! empty( $this->markup ) ) {
			return \preg_replace( '#<tr class="user-profile-picture">.*</tr>#Usi', $this->markup, $content );
		}

		return $content;
	}

	/**
	 * Stores the profile fields markup for later use.
	 *
	 * @param \WP_User $user The current user whose profile to modify.
	 */
	public function add_user_profile_fields( \WP_User $user ) {
		$this->markup =
			$this->upload->get_avatar_upload_markup( $user ) .
			$this->get_use_gravatar_markup( $user );
	}

	/**
	 * Retrieves the markup for the `use_gravatar` checkbox.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  \WP_User $user The profile user.
	 *
	 * @return string
	 */
	protected function get_use_gravatar_markup( \WP_User $user ) {
		$use_gravatar    = 'true' === \get_user_meta( $user->ID, Core::GRAVATAR_USE_META_KEY, true );
		$allow_anonymous = 'true' === \get_user_meta( $user->ID, Core::ALLOW_ANONYMOUS_META_KEY, true );

		\ob_start();
		require \dirname( $this->plugin_file ) . '/admin/partials/profile/use-gravatar.php';
		require \dirname( $this->plugin_file ) . '/admin/partials/profile/allow-anonymous.php';
		return \ob_get_clean();
	}

	/**
	 * Saves the custom fields from the user profile in
	 * the database.
	 *
	 * @param int $user_id The ID of the user that has just been saved.
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! \current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$this->save_use_gravatar_checkbox( $user_id );
		$this->save_allow_anonymous_checkbox( $user_id );
		$this->upload->save_uploaded_user_avatar( $user_id );
	}

	/**
	 * Saves the value of the 'use gravatar' checkbox from the user profile in
	 * the database.
	 *
	 * @param int $user_id The ID of the user that has just been saved.
	 */
	public function save_use_gravatar_checkbox( $user_id ) {
		// Use true/false instead of 1/0 since a '0' value is removed from the database and then
		// we can't differentiate between opted-out and never saved a value.
		if ( isset( $_POST[ self::NONCE_USE_GRAVATAR . $user_id ] ) && \wp_verify_nonce( \sanitize_key( $_POST[ self::NONCE_USE_GRAVATAR . $user_id ] ), self::ACTION_EDIT_USE_GRAVATAR ) ) {
			$use_gravatar = isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ self::CHECKBOX_FIELD_NAME ] ) ? 'true' : 'false'; // WPCS:  Input var okay.
			\update_user_meta( $user_id, Core::GRAVATAR_USE_META_KEY, $use_gravatar );
		}
	}

	/**
	 * Saves the value of the 'allow_anonymous' checkbox from the user profile in
	 * the database.
	 *
	 * @param int $user_id The ID of the user that has just been saved.
	 */
	public function save_allow_anonymous_checkbox( $user_id ) {
		// Use true/false instead of 1/0 since a '0' value is removed from the database and then
		// we can't differentiate between opted-out and never saved a value.
		if ( isset( $_POST[ self::NONCE_ALLOW_ANONYMOUS . $user_id ] ) && \wp_verify_nonce( \sanitize_key( $_POST[ self::NONCE_ALLOW_ANONYMOUS . $user_id ] ), self::ACTION_EDIT_ALLOW_ANONYMOUS ) ) {
			$allow_anonymous = isset( $_POST[ self::CHECKBOX_ALLOW_ANONYMOUS ] ) && ( 'true' === $_POST[ self::CHECKBOX_ALLOW_ANONYMOUS ] ) ? 'true' : 'false'; // WPCS:  Input var okay.
			\update_user_meta( $user_id, Core::ALLOW_ANONYMOUS_META_KEY, $allow_anonymous );
		}
	}
}
