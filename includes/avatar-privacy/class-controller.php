<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Avatar_Privacy\Component;

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
	 * @var Component[]
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
	 * @since 2.3.0 Component parameters replaced with factory-cofigured array.
	 *
	 * @param Core        $core       The core API.
	 * @param Component[] $components An array of plugin components.
	 */
	public function __construct( Core $core, array $components ) {
		$this->core       = $core;
		$this->components = $components;
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
