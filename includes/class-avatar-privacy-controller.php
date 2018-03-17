<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
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

/**
 * Initialize Avatar Privacy plugin.
 *
 * @since 0.4
 */
class Avatar_Privacy_Controller {

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Checks some requirements and then loads the plugin core.
	 */
	public function plugins_loaded() {
		global $avapr_core;
		$failed        = false;
		$settings_page = false;

		// If the admin selected not to display avatars at all, just add a note to the discussions settings page.
		if ( ! get_option( 'show_avatars' ) ) {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			$failed        = true;
			$settings_page = true;
		}

		// Load the plugin core.
		if ( ! $failed ) {
			// Frontend.
			$core       = new Avatar_Privacy_Core();
			$avapr_core = $core; // save in global variable so that the template function avapr_get_avatar_checkbox works
			// Backend.
			if ( is_admin() ) {
				new Avatar_Privacy_Options( $core );
				$settings_page = true;
			}
		}

		// Display a settings link on the plugin page.
		if ( $settings_page ) {
			add_filter( 'plugin_row_meta', [ $this, 'display_settings_link' ], 10, 2 );
		}
	}

	/**
	 * Displays a settings link next to the plugin on the plugins page.
	 *
	 * @param array  $links The array of links.
	 * @param string $file The current plugin file.
	 *
	 * @return array The modified array or links.
	 */
	public function display_settings_link( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$links[] = '<a href="' . admin_url( 'options-discussion.php#section_avatar_privacy' ) . '">' . __( 'Settings', 'avatar-privacy' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Registers the settings with the settings API. This is only used to display
	 * an explanation of the wrong gravatar settings.
	 */
	public function register_settings() {
		add_settings_section( 'avatar_privacy_section', __( 'Avatar Privacy', 'avatar-privacy' ), [ $this, 'output_settings_header' ], 'discussion' );
	}

	/**
	 * Outputs a short explanation on the discussion settings page.
	 */
	public function output_settings_header() {
		require dirname( __DIR__ ) . '/admin/partials/sections/avatars-disabled.php';
	}
}

/**
 * Template function for older themes: Returns the 'use gravatar' checkbox for
 * the comment form. Output the result with echo or print!
 *
 * @return string The HTML code for the checkbox or an empty string.
 */
function avapr_get_avatar_checkbox() {
	global $avapr_core;

	if ( ! class_exists( 'Avatar_Privacy_Core', false ) || ! isset( $avapr_core ) ) {
		return;
	}
	$settings = get_option( Avatar_Privacy_Core::SETTINGS_NAME );
	if ( ! $settings || ! is_array( $settings ) || ( count( $settings ) === 0 ) ) {
		return;
	}
	if ( isset( $settings['mode_optin'] ) && ( '1' === $settings['mode_optin'] ) ) {
		$result = $avapr_core->comment_form_default_fields( null );
		if ( is_array( $result ) && array_key_exists( Avatar_Privacy_Core::CHECKBOX_FIELD_NAME, $result ) ) {
			return $result[ Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ];
		}
	}
}
