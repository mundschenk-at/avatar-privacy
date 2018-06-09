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
use Avatar_Privacy\Components\;

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
	 * Creates a new instance.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
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
		// Load user data from email for bbPress.
		\add_filter( 'avatar_privacy_parse_id_or_email', [ $this, 'parse_id_or_email' ] );
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
}
