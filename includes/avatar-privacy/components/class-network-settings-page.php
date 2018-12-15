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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Transients;

use Mundschenk\UI\Control_Factory;
use Mundschenk\UI\Control;
use Mundschenk\UI\Controls;

/**
 * Handles the network settings page on multisite installations.
 *
 * @since 2.1.0
 */
class Network_Settings_Page implements \Avatar_Privacy\Component {

	const OPTION_GROUP = 'avatar-privacy-network-settings';
	const SECTION      = 'general';
	const ACTION       = 'edit-avatar-privacy-network-settings';

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * The (standard) transietns handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The UI controls for the settings.
	 *
	 * @var Control[]
	 */
	private $controls;

	/**
	 * An array to keep track of triggered admin notices.
	 *
	 * @var bool[]
	 */
	private $triggered_notice = [];

	/**
	 * Creates a new instance.
	 *
	 * @param string          $plugin_file     The full path to the base plugin file.
	 * @param Core            $core            The core API.
	 * @param Network_Options $network_options The network options handler.
	 * @param Transients      $transients      The transients handler.
	 * @param Settings        $settings        The default settings.
	 */
	public function __construct( $plugin_file, Core $core, Network_Options $network_options, Transients $transients, Settings $settings ) {
		$this->plugin_file     = $plugin_file;
		$this->core            = $core;
		$this->network_options = $network_options;
		$this->transients      = $transients;
		$this->settings        = $settings;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_network_admin() ) {
			// Load the field definitions.
			$fields = $this->settings->get_network_fields();
			if ( $this->network_options->get( Network_Options::USE_GLOBAL_TABLE ) ) {
				$fields[ Network_Options::MIGRATE_FROM_GLOBAL_TABLE ]['attributes'] = [
					'disabled' => 'disabled',
				];
			}

			// Initialize the controls.
			$this->controls = Control_Factory::initialize( $fields, $this->network_options, '' );

			// Add some actions.
			\add_action( 'network_admin_menu', [ $this, 'register_network_settings' ] );
			\add_action( 'network_admin_edit_' . self::ACTION, [ $this, 'save_network_settings' ] );
			\add_action( 'network_admin_notices', 'settings_errors' );
		}
	}

	/**
	 * Registers the settings with the settings API. This is only used to display
	 * an explanation of the wrong gravatar settings.
	 */
	public function register_network_settings() {
		// Create our options page.
		$page = \add_submenu_page( 'settings.php', \__( 'My Network Options', 'avatar-privacy' ), \__( 'Avatar Privacy', 'avatar-privacy' ), 'manage_network_options', self::OPTION_GROUP, [ $this, 'print_settings_page' ] );

		// Add the section(s).
		\add_settings_section( self::SECTION, '', [ $this, 'print_settings_section' ], self::OPTION_GROUP );

		// Register control render callbacks.
		foreach ( $this->controls as $option => $control ) {
			$option_name = $this->network_options->get_name( $option );
			$sanitize    = [ $control, 'sanitize' ];
			if ( Network_Options::MIGRATE_FROM_GLOBAL_TABLE === $option ) {
				$sanitize = [ $this, 'sanitize_migrate_from_global_table' ];
			} else {
				// Prevent spurious saves.
				\add_filter( "pre_update_site_option_{$option_name}", [ $this, 'filter_update_option' ], 10, 3 );
			}

			// Register the setting ...
			\register_setting( self::OPTION_GROUP, $option_name, $sanitize );

			// ... and the control
			$control->register( self::OPTION_GROUP );
		}

		// Use the registered $page handle to hook stylesheet and script loading.
		\add_action( "admin_print_styles-{$page}", [ $this, 'print_styles' ] );
	}

	/**
	 * Displays the network options page.
	 */
	public function print_settings_page() {
		// Load the settings page HTML.
		require \dirname( $this->plugin_file ) . '/admin/partials/network/settings-page.php';
	}

	/**
	 * Saves the network settings.
	 *
	 * @global $new_whitelist_options The options whitelisted by the settings API.
	 */
	public function save_network_settings() {
		// Check if the user has the correct permissions.
		if ( ! \current_user_can( 'manage_network_options' ) ) {
			\wp_die( \esc_html( \__( 'Sorry, you are not allowed to edit network options.', 'avatar-privacy' ) ), 403 );
		}

		// Make sure we are posting from our options page.
		\check_admin_referer( self::OPTION_GROUP . '-options' );

		// This is the list of registered options.
		global $new_whitelist_options;
		$options = $new_whitelist_options[ self::OPTION_GROUP ];

		// Go through the posted data and save only our options.
		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				// The registered callback function to sanitize the option's value will be called here.
				$this->network_options->set( $option, \wp_unslash( $_POST[ $option ] ), false, true );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				// Set false for checkboxes and unset everything else.
				$id = $this->network_options->remove_prefix( $option );
				if ( $this->controls[ $id ] instanceof Controls\Checkbox_Input ) {
					$this->network_options->set( $option, false, false, true );
				} else {
					$this->network_options->delete( $option, true );
				}
			}
		}

		$settings_errors = \get_settings_errors();
		if ( empty( $settings_errors ) ) {
			\add_settings_error( self::OPTION_GROUP, 'settings_updated', \__( 'Settings updated.', 'avatar-privacy' ), 'updated' );
		}

		// Save the settings errors until after the redirect.
		$this->persist_settings_errors();

		// At last we redirect back to our options page.
		\wp_safe_redirect(
			\add_query_arg(
				[
					'page'             => self::OPTION_GROUP,
					'settings-updated' => 'true',
				],
				\network_admin_url( 'settings.php' )
			)
		);

		// And we are done.
		$this->exit_request();
	}

	/**
	 * Prints any additional markup for the given form section.
	 *
	 * @param array $section The section information.
	 */
	public function print_settings_section( $section ) {
		$section_id  = ! empty( $section['id'] ) ? $section['id'] : '';
		$description = \__( 'General settings applying to all sites in the network.', 'avatar-privacy' );

		// Load the settings page HTML.
		require \dirname( $this->plugin_file ) . '/admin/partials/network/section.php';
	}

	/**
	 * Stops executing the current request early.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param  int $status Optional. A status code in the range 0 to 254. Default 0.
	 */
	protected function exit_request( $status = 0 ) {
		exit( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueue stylesheet for options page.
	 */
	public function print_styles() {
		\wp_enqueue_style( 'avatar-privacy-settings', \plugins_url( 'admin/css/settings.css', $this->plugin_file ), [], $this->core->get_version(), 'all' );
	}

	/**
	 * Add proper notification for Migrate from Global Table button.
	 *
	 * @param mixed $input Ignored.
	 *
	 * @return bool
	 */
	public function sanitize_migrate_from_global_table( $input ) {
		return $this->trigger_admin_notice( Network_Options::MIGRATE_FROM_GLOBAL_TABLE, 'migrated-to-site-tables', \__( 'Consent data migrated to site-specific tables.', 'avatar-privacy' ), 'notice-info', $input );
	}

	/**
	 * Use sanitization callback to trigger an admin notice.
	 *
	 * @param  string $setting_name The setting used to trigger the notice (without the prefix).
	 * @param  string $notice_id    HTML ID attribute for the notice.
	 * @param  string $message      Translated message string.
	 * @param  string $notice_level 'updated', 'notice-info', etc.
	 * @param  mixed  $input        Passed back.
	 *
	 * @return bool The $input parameter cast to a boolean value.
	 */
	protected function trigger_admin_notice( $setting_name, $notice_id, $message, $notice_level, $input ) {
		if (
			! empty( $_POST[ $this->network_options->get_name( $setting_name ) ] ) && // WPCS: CSRF ok. Input var okay.
			empty( $this->triggered_notice[ $setting_name ] )
		) {
			\add_settings_error( self::OPTION_GROUP, $notice_id, $message, $notice_level );

			// Workaround for https://core.trac.wordpress.org/ticket/21989.
			$this->triggered_notice[ $setting_name ] = true;
		}

		return (bool) $input;
	}

	/**
	 * Persists the settings errors across the redirect.
	 *
	 * Uses a regular transient to stay compatible with core.
	 */
	protected function persist_settings_errors() {
		// A regular transient is used here, since it is automatically cleared right after the redirect.
		$this->transients->set( 'settings_errors', \get_settings_errors(), 30, true );
	}

	/**
	 * Prevents settings from being saved if we are migrating table data.
	 *
	 * @param mixed  $value      New value of the network option.
	 * @param mixed  $old_value  Old value of the network option.
	 * @param string $option     Option name.
	 *
	 * @return mixed
	 */
	public function filter_update_option( $value, $old_value, $option ) {
		// Check if one of the auxiliary buttons was clicked and ignore changes in that case.
		if ( ! empty( $_POST[ $this->network_options->get_name( Network_Options::MIGRATE_FROM_GLOBAL_TABLE ) ] ) ) { // WPCS: CSRF ok. Input var okay.
			return $old_value;
		} else {
			return $value;
		}
	}
}
