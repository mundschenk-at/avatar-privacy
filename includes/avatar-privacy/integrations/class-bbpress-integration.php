<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * An integration for bbPress.
 *
 * @since      1.1.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class BBPress_Integration implements Plugin_Integration {

	/**
	 * The form helper.
	 *
	 * @var User_Form
	 */
	private User_Form $form;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.3.0 Parameter $user_profile replaced by $form.
	 *
	 * @param User_Form $form The form handling helper.
	 */
	public function __construct( User_Form $form ) {
		$this->form = $form;
	}

	/**
	 * Check if the bbPress integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \function_exists( 'is_bbpress' );
	}

	/**
	 * Activate the integration.
	 *
	 * @@return void
	 */
	public function run() {
		if ( ! \is_admin() ) {
			\add_action( 'init', [ $this, 'init' ] );
		}
	}

	/**
	 * Init action handler.
	 *
	 * @return void
	 */
	public function init() {
		// Load user data from email for bbPress.
		\add_filter( 'avatar_privacy_parse_id_or_email', [ $this, 'parse_id_or_email' ] );

		// Add profile picture upload and `use_gravatar` checkbox to frontend profile editor.
		\add_action( 'bbp_user_edit_after',      [ $this, 'add_user_profile_fields' ] );
		\add_action( 'personal_options_update',  [ $this->form, 'save' ] );
		\add_action( 'edit_user_profile_update', [ $this->form, 'save' ] );
	}

	/**
	 * Loads user ID from email if using bbPress.
	 *
	 * @param array $data {
	 *     The information parsed from $id_or_email.
	 *
	 *     @type int|false $user_id The WordPress user ID, or false.
	 *     @type string    $email   The email address.
	 *     @type int       $age     The seconds since the post or comment was first created, or 0 if $id_or_email was not one of these object types.
	 * }
	 *
	 * @return array {
	 *     The filtered data.
	 *
	 *     @type int|false $user_id The WordPress user ID, or false.
	 *     @type string    $email   The email address.
	 *     @type int       $age     The seconds since the post or comment was first created, or 0 if $id_or_email was not one of these object types.
	 * }
	 */
	public function parse_id_or_email( $data ) {
		list( $user_id, $email, $age ) = $data;

		if ( \is_bbpress() && false === $user_id ) {
			$user = \get_user_by( 'email', $email );

			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
			}
		}

		return [ $user_id, $email, $age ];
	}

	/**
	 * Add user profile fields for bbPress.
	 *
	 * @return void
	 */
	public function add_user_profile_fields() {
		// Get user ID from bbPress.
		$user_id = \bbp_get_user_id( 0, true, false );
		if ( empty( $user_id ) ) {
			return;
		}

		// Include partial.
		$this->form->print_form( 'public/partials/bbpress/user-profile-picture.php', $user_id );
	}
}
