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

namespace Avatar_Privacy;

use Avatar_Privacy\Components\Avatar_Handling;
use Avatar_Privacy\Components\Block_Editor;
use Avatar_Privacy\Components\Command_Line_Interface;
use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Components\Image_Proxy;
use Avatar_Privacy\Components\Integrations;
use Avatar_Privacy\Components\Network_Settings_Page;
use Avatar_Privacy\Components\Privacy_Tools;
use Avatar_Privacy\Components\REST_API;
use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Settings_Page;
use Avatar_Privacy\Components\Shortcodes;
use Avatar_Privacy\Components\User_Profile;

/**
 * Initialize Avatar Privacy plugin.
 *
 * @since 1.0.0
 * @since 2.1.0 Renamed to Avatar_Privacy\Controller.
 */
class Controller {

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
	 * @param Core                   $core             The core API.
	 * @param Setup                  $setup            The (de-)activation handling.
	 * @param Image_Proxy            $image_proxy      The image handler.
	 * @param Avatar_Handling        $avatars          The avatar handler.
	 * @param Comments               $comments         The comments handler.
	 * @param User_Profile           $profile          The user profile handler.
	 * @param Settings_Page          $settings         The admin settings handler.
	 * @param Network_Settings_Page  $network_settings The network settings handler.
	 * @param Privacy_Tools          $privacy          The privacy tools handler.
	 * @param REST_API               $rest_api         The REST API handler.
	 * @param Integrations           $integrations     The third-party plugin integrations handler.
	 * @param Shortcodes             $shortcodes       The shortcodes handler.
	 * @param Block_Editor           $block_editor     The block editor handler.
	 * @param Command_Line_Interface $cli             The CLI handler.
	 */
	public function __construct(
		Core $core,
		Setup $setup,
		Image_Proxy $image_proxy,
		Avatar_Handling $avatars,
		Comments $comments,
		User_Profile $profile,
		Settings_Page $settings,
		Network_Settings_Page $network_settings,
		Privacy_Tools $privacy,
		REST_API $rest_api,
		Integrations $integrations,
		Shortcodes $shortcodes,
		Block_Editor $block_editor,
		Command_Line_Interface $cli
	) {
		$this->core         = $core;
		$this->components[] = $setup;
		$this->components[] = $avatars;
		$this->components[] = $image_proxy;
		$this->components[] = $comments;
		$this->components[] = $profile;
		$this->components[] = $settings;
		$this->components[] = $network_settings;
		$this->components[] = $privacy;
		$this->components[] = $rest_api;
		$this->components[] = $integrations;
		$this->components[] = $shortcodes;
		$this->components[] = $block_editor;
		$this->components[] = $cli;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		// Set plugin singleton.
		Core::set_instance( $this->core );

		foreach ( $this->components as $component ) {
			$component->run();
		}
	}
}
