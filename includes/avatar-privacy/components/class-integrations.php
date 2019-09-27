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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Core;

/**
 * A registry for plugin integrations.
 *
 * @since      1.1.0
 *
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Integrations implements \Avatar_Privacy\Component {

	/**
	 * An array of plugin integration instances.
	 *
	 * @var Plugin_Integration[]
	 */
	private $integrations = [];

	/**
	 * An array of activated plugin integrations.
	 *
	 * @var Plugin_Integration[]
	 */
	private $active_integrations = [];

	/**
	 * Creates a new instance.
	 *
	 * @since 2.2.0 Parameter $core removed.
	 *
	 * @param Plugin_Integration[] $integrations An array of plugin integration instances.
	 */
	public function __construct( array $integrations ) {
		$this->integrations = $integrations;
	}

	/**
	 * Activate all applicable plugin integrations.
	 */
	public function activate() {
		foreach ( $this->integrations as $integration ) {
			if ( $integration->check() ) {
				$this->active_integrations[] = $integration;
				$integration->run();
			}
		}
	}

	/**
	 * Start up enabled integrations.
	 */
	public function run() {
		\add_action( 'plugins_loaded', [ $this, 'activate' ] );
	}
}
