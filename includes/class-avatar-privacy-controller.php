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

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Avatar_Handling;
use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Components\Images;
use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Settings_Page;
use Avatar_Privacy\Components\User_Profile;
use Avatar_Privacy\Components\Uninstallation;

/**
 * Initialize Avatar Privacy plugin.
 *
 * @since 1.0.0
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
	 * @var Core
	 */
	private $core;

	/**
	 * Creates an instance of the plugin controller.
	 *
	 * @param Core            $core      The core API.
	 * @param Setup           $setup     The (de-)activation handling.
	 * @param Uninstallation  $uninstall The uninstallation handling.
	 * @param Images          $icons     The default icon handler.
	 * @param Avatar_Handling $avatars   The avatar handler.
	 * @param Comments        $comments  The comments handler.
	 * @param User_Profile    $profile   The user profile handler.
	 * @param Settings_Page   $settings  The admin settings handler.
	 */
	public function __construct( Core $core, Setup $setup, Uninstallation $uninstall, Images $icons, Avatar_Handling $avatars, Comments $comments, User_Profile $profile, Settings_Page $settings ) {
		$this->core         = $core;
		$this->components[] = $setup;
		$this->components[] = $uninstall;
		$this->components[] = $avatars;
		$this->components[] = $icons;
		$this->components[] = $comments;
		$this->components[] = $profile;
		$this->components[] = $settings;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		// Set plugin singleton.
		Core::set_instance( $this->core );

		foreach ( $this->components as $component ) {
			$component->run( $this->core );
		}
	}
}
