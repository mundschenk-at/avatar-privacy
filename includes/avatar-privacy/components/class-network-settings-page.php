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
	 * Creates a new instance.
	 *
	 * @param string          $plugin_file     The full path to the base plugin file.
	 * @param Core            $core            The core API.
	 * @param Network_Options $network_options The options handler.
	 * @param Settings        $settings        The default settings.
	 */
	public function __construct( $plugin_file, Core $core, Network_Options $network_options, Settings $settings ) {
		$this->plugin_file     = $plugin_file;
		$this->core            = $core;
		$this->network_options = $network_options;
		$this->settings        = $settings;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_network_admin() ) {
			// Initialize the controls.
			$this->controls = Control_Factory::initialize( $this->settings->get_network_fields(), $this->network_options, '' );

			// Add some actions.
			\add_action( 'network_admin_menu', [ $this, 'register_network_settings' ] );
			\add_action( 'network_admin_edit_' . self::ACTION, [ $this, 'save_network_settings' ] );
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
			\register_setting( self::OPTION_GROUP, $this->network_options->get_name( $option ), [ $control, 'sanitize' ] );

			$control->register( self::OPTION_GROUP );
		}

		// Use the registered $page handle to hook stylesheet loading.
		\add_action( 'admin_print_styles-' . $page, [ $this, 'print_admin_styles' ] );
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

		// At last we redirect back to our options page.
		\wp_safe_redirect(
			\add_query_arg(
				[
					'page'    => self::OPTION_GROUP,
					'updated' => 'true',
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
	public function print_admin_styles() {
		\wp_enqueue_style( 'wp-typography-settings', \plugins_url( 'admin/css/settings.css', $this->plugin_file ), [], $this->core->get_version(), 'all' );
	}
}
