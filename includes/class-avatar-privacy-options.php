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

use Avatar_Privacy\Data_Storage\Options;

/**
 * Options class of the Avatar Privacy plugin. Contains all code for the
 * options page. The plugin's options are displayed on the discussion settings
 * page.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */
class Avatar_Privacy_Options implements \Avatar_Privacy\Component {

	/**
	 * The plugin core.
	 *
	 * @var Avatar_Privacy_Core
	 */
	private $core = null;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a Avatar_Privacy_Options instance and registers all necessary
	 * hooks and filters for the settings.
	 *
	 * @param Options $options Required.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		$this->core = $core;

		// Register the settings to be displayed.
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Registers the settings with the settings API.
	 */
	public function register_settings() {
		// Add a section for the 'check for gravatar' mode to the avatar options.
		add_settings_section( 'avatar_privacy_section', __( 'Avatar Privacy', 'avatar-privacy' ) . '<span id="section_avatar_privacy"></span>', [ $this, 'output_settings_header' ], 'discussion' );
		add_settings_field( 'avatar_privacy_checkforgravatar', __( 'Check for gravatars', 'avatar-privacy' ),        [ $this, 'output_checkforgravatar_setting' ], 'discussion', 'avatar_privacy_section' );
		// We save all settings in one variable in the database table; also adds a validation method.
		register_setting( 'discussion', $this->options->get_name( Avatar_Privacy_Core::SETTINGS_NAME ), [ $this, 'validate_settings' ] );
	}

	/**
	 * Validates the plugin's settings, rejects any invalid data.
	 *
	 * @param array $input The array of settings values to save.
	 * @return array The cleaned-up array of user input.
	 */
	public function validate_settings( $input ) {
		// Validate the settings.
		$newinput['mode_checkforgravatar'] = (int) ! empty( $input['mode_checkforgravatar'] );

		// Check if the headers function works on the server (use MD5 of mystery default image).
		if ( ! empty( $newinput['mode_checkforgravatar'] ) ) {
			$uri      = 'https://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=32&d=404';
			$response = wp_remote_head( $uri );

			if ( is_wp_error( $response ) || empty( $response['headers'] ) ) {
				add_settings_error(
					$this->options->get_name( Avatar_Privacy_Core::SETTINGS_NAME ), 'get-headers-failed',
					__( "The gravatar.com servers cannot be reached for some reason (this might be a temporary issue). Check with your server admin if you don't see gravatars for your own Gravatar account and this message keeps popping up after saving the plugin settings.", 'avatar-privacy' ),
					'error'
				);
			}
		}

		return $newinput;
	}

	/**
	 * Outputs the header of the Avatar Privacy settings section.
	 */
	public function output_settings_header() {
		require dirname( __DIR__ ) . '/admin/partials/sections/avatars-enabled.php';
	}

	/**
	 * Outputs the elements for the 'check for gravatar' setting.
	 */
	public function output_checkforgravatar_setting() {
		$options = $this->options->get( Avatar_Privacy_Core::SETTINGS_NAME, [ 'mode_checkforgravatar' => false ] );

		require dirname( __DIR__ ) . '/admin/partials/settings/check-for-gravatar.php';
	}
}
