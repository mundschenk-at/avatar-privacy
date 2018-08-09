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

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler as Upload;

use Avatar_Privacy\Data_Storage\Options;

use Mundschenk\UI\Control_Factory;
use Mundschenk\UI\Controls;

/**
 * Handles privacy-specific additions to the "Discussion" settings page.
 *
 * @since 1.0.0
 */
class Settings_Page implements \Avatar_Privacy\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

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
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Creates a new instance.
	 *
	 * @param string  $plugin_file The full path to the base plugin file.
	 * @param Core    $core        The core API.
	 * @param Options $options     The options handler.
	 * @param Upload  $upload      The file upload handler.
	 */
	public function __construct( $plugin_file, Core $core, Options $options, Upload $upload ) {
		$this->plugin_file = $plugin_file;
		$this->core        = $core;
		$this->options     = $options;
		$this->upload      = $upload;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_admin() ) {
			\add_action( 'admin_init', [ $this, 'register_settings' ] );
			\add_action( 'admin_footer-options-discussion.php', [ $this, 'settings_footer' ] );

			// Add form encoding.
			\add_action( 'admin_head-options-discussion.php', function() {
				\ob_start( [ $this, 'add_form_encoding' ] );
			} );
		}
	}

	/**
	 * Run tasks in the settings footer.
	 */
	public function settings_footer() {
		// Add show/hide javascript.
		if ( \wp_script_is( 'jquery', 'done' ) ) {
			require dirname( $this->plugin_file ) . '/admin/partials/sections/avatars-disabled-script.php';
		}

		// Clean up output buffering.
		if ( \ob_get_level() > 0 ) {
			\ob_end_flush();
		}
	}

	/**
	 * Registers the settings with the settings API. This is only used to display
	 * an explanation of the wrong gravatar settings.
	 */
	public function register_settings() {
		\register_setting( 'discussion', $this->options->get_name( Core::SETTINGS_NAME ), [ $this, 'sanitize_settings' ] );

		// Register control render callbacks.
		$controls = Control_Factory::initialize( Settings::get_fields( $this->get_settings_header() ), $this->options, Core::SETTINGS_NAME );
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
		$show_avatars = $this->options->get( 'show_avatars', false, true );

		\ob_start();
		require dirname( $this->plugin_file ) . '/admin/partials/sections/avatars-disabled.php';
		require dirname( $this->plugin_file ) . '/admin/partials/sections/avatars-enabled.php';
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
		foreach ( Settings::get_fields() as $key => $info ) {
			if ( Controls\Checkbox_Input::class === $info['ui'] ) {
				$input[ $key ] = ! empty( $input[ $key ] );
			}
		}

		if ( ! isset( $input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] ) ) {
			$previous                                        = $this->core->get_settings();
			$input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] = $previous[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ];
		}

		$this->upload->save_uploaded_default_icon( \get_current_blog_id(), $input[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );

		return $input;
	}
}
