<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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
use Avatar_Privacy\CLI\Command; // phpcs:ignore ImportDetection.Imports.RequireImports.Import -- needed for type hinting.

/**
 * The component providing CLI commands.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Command_Line_Interface implements Component {

	/**
	 * An array of CLI commands.
	 *
	 * @var Command[]
	 */
	private $commands;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Command[] $commands An array of CLI commands to register.
	 */
	public function __construct( array $commands ) {
		$this->commands = $commands;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'cli_init', [ $this, 'register_commands' ] );
	}

	/**
	 * Registeres all the different CLI commands.
	 *
	 * @return void
	 */
	public function register_commands() {
		foreach ( $this->commands as $cmd ) {
			$cmd->register();
		}
	}
}
