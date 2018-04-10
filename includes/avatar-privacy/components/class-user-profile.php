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

/**
 * Handles privacy-specific updates of the user profile.
 *
 * @since 1.0.0
 */
class User_Profile implements \Avatar_Privacy\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * Creates a new instance.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
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
	}

	/**
	 * Adds the 'use gravatar' checkbox to the user profile form.
	 *
	 * @param \WP_User $user The current user whose profile to modify.
	 */
	public function add_user_profile_fields( \WP_User $user ) {
		$val = (bool) \get_the_author_meta( \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME, $user->ID );

		require \dirname( $this->plugin_file ) . '/admin/partials/profile/use-gravatar.php';
	}

	/**
	 * Saves the value of the 'use gravatar' checkbox from the user profile in
	 * the database.
	 *
	 * @param int $user_id The ID of the user that has just been saved.
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! \current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// Use true/false instead of 1/0 since a '0' value is removed from the database and then
		// we can't differentiate between opted-out and never saved a value.
		$value = isset( $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ) ? 'true' : 'false'; // WPCS: CSRF ok, Input var okay.
		\update_user_meta( $user_id, \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME, $value );
	}

}
