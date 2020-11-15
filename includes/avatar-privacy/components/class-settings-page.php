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

use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Data_Storage\Options;

use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler as Upload;

use Mundschenk\UI\Control_Factory;
use Mundschenk\UI\Controls;

/**
 * Handles privacy-specific additions to the "Discussion" settings page.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Settings_Page implements Component {

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The file upload handler.
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * The settings API.
	 *
	 * @var Settings
	 */
	private $settings;

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
	 * @since 2.4.0 Paraemter $core removed.
	 *
	 * @param Options  $options     The options handler.
	 * @param Upload   $upload      The file upload handler.
	 * @param Settings $settings    The settings API.
	 */
	public function __construct( Options $options, Upload $upload, Settings $settings ) {
		$this->options   = $options;
		$this->upload    = $upload;
		$this->settings  = $settings;
		$this->buffering = false;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_admin() ) {
			// Register scripts.
			\add_action( 'admin_init', [ $this, 'register_settings' ] );

			// Add form encoding.
			\add_action( 'admin_head-options-discussion.php', [ $this, 'settings_head' ] );

			// Print scripts.
			\add_action( 'admin_footer-options-discussion.php', [ $this, 'settings_footer' ] );
		}
	}

	/**
	 * Run tasks in the settings header.
	 *
	 * @return void
	 */
	public function settings_head() {
		if ( \ob_start( [ $this, 'add_form_encoding' ] ) ) {
			$this->buffering = true;
		}
	}

	/**
	 * Run tasks in the settings footer.
	 *
	 * @return void
	 */
	public function settings_footer() {
		// Add show/hide javascript.
		if ( \wp_script_is( 'jquery', 'done' ) ) {
			require \AVATAR_PRIVACY_PLUGIN_PATH . '/admin/partials/sections/avatars-disabled-script.php';
		}

		// Clean up output buffering.
		if ( $this->buffering && \ob_get_level() > 0 ) {
			\ob_end_flush();
			$this->buffering = false;
		}
	}

	/**
	 * Registers the settings with the settings API. This is only used to display
	 * an explanation of the wrong gravatar settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		\register_setting( 'discussion', $this->options->get_name( Settings::OPTION_NAME ), [ $this, 'sanitize_settings' ] );

		// Register control render callbacks.
		$controls = Control_Factory::initialize( $this->settings->get_fields( $this->get_settings_header() ), $this->options, Settings::OPTION_NAME );
		foreach ( $controls as $control ) {
			$control->register( 'discussion' );
		}
	}

	/**
	 * Adds the enctype "multipart/form-data" to the form tag.
	 *
	 * @param  string $content The captured HTML output.
	 *
	 * @return string
	 */
	public function add_form_encoding( $content ) {
		return \preg_replace( '#(<form method="post") (action="options.php">)#Usi', '\1 enctype="multipart/form-data" \2', $content );
	}

	/**
	 * Adds a short explanation on the discussion settings page.
	 *
	 * @return string
	 */
	public function get_settings_header() {
		// Set up variables used by the included partial.
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$show_avatars = $this->options->get( 'show_avatars', false, true );
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

		\ob_start();
		require \AVATAR_PRIVACY_PLUGIN_PATH . '/admin/partials/sections/avatars-disabled.php';
		require \AVATAR_PRIVACY_PLUGIN_PATH . '/admin/partials/sections/avatars-enabled.php';
		return \ob_get_clean();
	}

	/**
	 * Sanitize plugin settings array.
	 *
	 * @param  array $input The plugin settings.
	 *
	 * @return array The sanitized plugin settings.
	 */
	public function sanitize_settings( $input ) {
		foreach ( $this->settings->get_fields() as $key => $info ) {
			if ( Controls\Checkbox_Input::class === $info['ui'] ) {
				$input[ $key ] = ! empty( $input[ $key ] );
			}
		}

		if ( ! isset( $input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] ) ) {
			$input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] = $this->settings->get( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR );
		}

		$this->upload->save_uploaded_default_icon( \get_current_blog_id(), $input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );

		return $input;
	}
}
