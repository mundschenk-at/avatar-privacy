<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

/**
 * An integration for bbPress.
 *
 * @since      1.1.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class BBPress_Integration implements Plugin_Integration {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The user profile component.
	 *
	 * @var User_Profile
	 */
	private $profile;

	/**
	 * Creates a new instance.
	 *
	 * @param string       $plugin_file The full path to the base plugin file.
	 * @param User_Profile $profile     The user profile component.
	 */
	public function __construct( $plugin_file, User_Profile $profile ) {
		$this->plugin_file = $plugin_file;
		$this->profile     = $profile;
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
	 * @param Core $core The plugin instance.
	 */
	public function run( Core $core ) {
		if ( ! \is_admin() ) {
			\add_action( 'init', [ $this, 'init' ] );
		}
	}

	/**
	 * Init action handler.
	 */
	public function init() {
		// Load user data from email for bbPress.
		\add_filter( 'avatar_privacy_parse_id_or_email', [ $this, 'parse_id_or_email' ] );

		// Add profile picture upload and `use_gravatar` checkbox to frontend profile editor.
		\add_action( 'bbp_user_edit_after',      [ $this, 'add_user_profile_fields' ] );
		\add_action( 'personal_options_update',  [ $this->profile, 'save_user_profile_fields' ] );
		\add_action( 'edit_user_profile_update', [ $this->profile, 'save_user_profile_fields' ] );
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

		if ( /* @scrutinizer ignore-call */ \is_bbpress() && false === $user_id ) {
			$user = \get_user_by( 'email', $email );

			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
			}
		}

		return [ $user_id, $email, $age ];
	}

	/**
	 * Add user profile fields for bbPress.
	 */
	public function add_user_profile_fields() {
		$user_id = /* @scrutinizer ignore-call */ \bbp_get_user_id( 0, true, false );
		if ( empty( $user_id ) ) {
			return;
		}

		// Include partials.
		$use_gravatar = 'true' === \get_user_meta( $user_id, Core::GRAVATAR_USE_META_KEY, true );
		require \dirname( $this->plugin_file ) . '/public/partials/bbpress/user-profile-picture.php';
	}
}
