<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Component;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * Handles privacy-specific updates of the user profile.
 *
 * @since 1.0.0
 * @since 2.3.0 Public methods save_use_gravatar_checkbox,
 *              save_allow_anonymous_checkbox and save_user_profile_fields have
 *              been removed. The obsolete class constants have also been removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Profile implements Component {

	/**
	 * The markup to inject.
	 *
	 * @var string
	 */
	private $markup;

	/**
	 * The profile form helper.
	 *
	 * @var User_Form
	 */
	private $form;

	/**
	 * Indiciates whether the settings page is buffering its output.
	 *
	 * @var bool
	 */
	private $buffering;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.3.0 Parameter $upload removed, parameter $form added.
	 *
	 * @param User_Form $form The profile form helper.
	 */
	public function __construct( User_Form $form ) {
		$this->form      = $form;
		$this->buffering = false;
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
	 *
	 * @return void
	 */
	public function admin_init() {
		// Add the checkbox to the user profile form if we're in the WP backend.
		\add_action( 'user_edit_form_tag',       [ $this, 'print_form_encoding' ] );
		\add_action( 'show_user_profile',        [ $this, 'add_user_profile_fields' ] );
		\add_action( 'edit_user_profile',        [ $this, 'add_user_profile_fields' ] );
		\add_action( 'personal_options_update',  [ $this->form, 'save' ] );
		\add_action( 'edit_user_profile_update', [ $this->form, 'save' ] );

		// Replace profile picture setting with our own settings.
		\add_action( 'admin_head-profile.php',     [ $this, 'admin_head' ] );
		\add_action( 'admin_head-user-edit.php',   [ $this, 'admin_head' ] );
		\add_action( 'admin_footer-profile.php',   [ $this, 'admin_footer' ] );
		\add_action( 'admin_footer-user-edit.php', [ $this, 'admin_footer' ] );
	}

	/**
	 * Enables output buffering.
	 *
	 * @return void
	 */
	public function admin_head() {
		if ( \ob_start( [ $this, 'replace_profile_picture_section' ] ) ) {
			$this->buffering = true;
		}
	}

	/**
	 * Cleans up any output buffering.
	 *
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function add_user_profile_fields( \WP_User $user ) {
		$this->markup =
			$this->form->get_avatar_uploader( $user->ID ) .
			$this->form->get_use_gravatar_checkbox( $user->ID ) .
			$this->form->get_allow_anonymous_checkbox( $user->ID );
	}
}
