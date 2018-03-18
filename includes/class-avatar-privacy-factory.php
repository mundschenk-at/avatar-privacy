<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
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

use Dice\Dice;

use Mundschenk\Data_Storage\Cache;
use Mundschenk\Data_Storage\Options;
use Mundschenk\Data_Storage\Transients;

/**
 * A factory for creating Avatar_Privacy instances via dependency injection.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Avatar_Privacy_Factory {

	/**
	 * The factory instance.
	 *
	 * @var Dice
	 */
	private static $factory;

	/**
	 * Retrieves a factory set up for creating Avatar_Privacy instances.
	 *
	 * @param string $full_plugin_path The full path to the main plugin file (i.e. __FILE__).
	 *
	 * @return Dice
	 */
	public static function get( $full_plugin_path ) {
		if ( ! isset( self::$factory ) ) {
			self::$factory = new Dice();

			// Shared helpers.
			self::$factory->addRule( Cache::class, [
				'shared' => true,
			] );
			self::$factory->addRule( Transients::class, [
				'shared' => true,
			] );
			self::$factory->addRule( Options::class, [
				'shared' => true,
			] );

			// Load version from plugin data.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			self::$factory->addRule( Avatar_Privacy_Core::class, [
				'constructParams' => [ get_plugin_data( $full_plugin_path, false, false )['Version'] ],
			] );

			// Additional parameters for components.
			// $plugin_basename = \plugin_basename( $full_plugin_path );
			// self::$factory->addRule( Admin_Interface::class, [
			// 	'constructParams' => [ $plugin_basename, $full_plugin_path ],
			// ] );
		}

		return self::$factory;
	}
}
