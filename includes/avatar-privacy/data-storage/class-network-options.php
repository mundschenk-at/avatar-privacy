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

namespace Avatar_Privacy\Data_Storage;

/**
 * A plugin-specific network options handler.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Network_Options extends \Mundschenk\Data_Storage\Network_Options {
	const PREFIX = 'avatar_privacy_';

	/**
	 * The network option key (without the prefix) for using a global table in
	 * multisite installations.
	 *
	 * @var string
	 */
	const USE_GLOBAL_TABLE = 'use_global_table';

	/**
	 * The network option key (without the prefix) for the queue of site IDs to migrate from
	 * global table usage in multisite installations.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const GLOBAL_TABLE_MIGRATION = 'migrate_from_global_table';

	/**
	 * The network option key (without the prefix) serving as a lock for the site ID queue.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const GLOBAL_TABLE_MIGRATION_LOCK = 'migrate_from_global_table_lock';

	/**
	 * The network option key (without the prefix) serving as temporary storagen when
	 * the the site ID queue is locked.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const START_GLOBAL_TABLE_MIGRATION = 'start_global_table_migration';

	/**
	 * The network option key (without the prefix) for storing the network-wide salt.
	 *
	 * @var string
	 */
	const SALT = 'salt';

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		parent::__construct( self::PREFIX );
	}

	/**
	 * Removes the prefix from an option name.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $name The option name including the prefix.
	 *
	 * @return string       The option name without the prefix, or '' if an invalid name was given.
	 */
	public function remove_prefix( $name ) {
		$parts = \explode( self::PREFIX, $name, 2 );
		if ( '' === $parts[0] ) {
			return $parts[1];
		}

		return '';
	}

	/**
	 * Tries to write-lock the given option (using a secondary option with the '_lock'
	 * suffix).
	 *
	 * @since 2.1.0
	 *
	 * @param  string $option   The option name (without the plugin-specific prefix).
	 *
	 * @return bool             True if the option can be safely set, false otherwise.
	 */
	public function lock( $option ) {
		$now    = \microtime( true );
		$secret = \wp_hash( "{$option}|{$now}", 'nonce' );
		$lock   = "{$option}_lock";

		return ! $this->get( $lock ) && $this->set( $lock, $secret ) && $secret === $this->get( $lock );
	}

	/**
	 * Tries to write-unlock the given option (using a secondary option with the '_lock'
	 * suffix).
	 *
	 * @since 2.1.0
	 *
	 * @param  string $option   The option name (without the plugin-specific prefix).
	 *
	 * @return bool             True if the option is now unlocked (either because
	 *                          it was not lockedor because unlock was successful),
	 *                          false otherwise.
	 */
	public function unlock( $option ) {
		$lock = "{$option}_lock";

		return ! $this->get( $lock ) || $this->delete( $lock );
	}
}
