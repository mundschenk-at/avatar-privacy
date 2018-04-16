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
	 * Creates a new instance.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		if ( \is_admin() ) {
			\add_action( 'plugins_loaded', [ $this, 'add_settings' ] );
		}
	}

	/**
	 * Initialize additional plugin hooks.
	 */
	public function add_settings() {
		// If the admin selected not to display avatars at all, just add a note to the discussions settings page.
		if ( ! get_option( 'show_avatars' ) ) {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
		}
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
		require dirname( $this->plugin_file ) . '/admin/partials/sections/avatars-disabled.php';
	}
}
