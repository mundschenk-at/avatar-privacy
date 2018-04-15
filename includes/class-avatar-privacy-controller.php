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

use Avatar_Privacy\Components\Avatar_Handling;
use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Components\Images;
use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\User_Profile;

/**
 * Initialize Avatar Privacy plugin.
 *
 * @since 0.4
 */
class Avatar_Privacy_Controller {

	/**
	 * The settings page handler.
	 *
	 * @var Avatar_Privacy\Component[]
	 */
	private $components = [];

	/**
	 * The core plugin API.
	 *
	 * @var Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * Creates an instance of the plugin controller.
	 *
	 * @param Avatar_Privacy_Core $core     The core API.
	 * @param Setup               $setup    The (de-)activation/uninstallation handling.
	 * @param Images              $icons    The default icon handler.
	 * @param Avatar_Handling     $avatars  The avatar handler.
	 * @param Comments            $comments The comments handler.
	 * @param User_Profile        $profile  The user profile handler.
	 */
	public function __construct( Avatar_Privacy_Core $core, Setup $setup, Images $icons, Avatar_Handling $avatars, Comments $comments, User_Profile $profile ) {
		$this->core         = $core;
		$this->components[] = $setup;
		$this->components[] = $avatars;
		$this->components[] = $icons;
		$this->components[] = $comments;
		$this->components[] = $profile;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		// Set plugin singleton.
		\Avatar_Privacy_Core::set_instance( $this->core );

		foreach ( $this->components as $component ) {
			$component->run( $this->core );
		}

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Checks some requirements and then loads the plugin core.
	 */
	public function plugins_loaded() {
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
		require dirname( __DIR__ ) . '/admin/partials/sections/avatars-disabled.php';
	}
}
