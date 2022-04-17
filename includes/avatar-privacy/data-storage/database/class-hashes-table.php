<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2022 Peter Putzer.
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

namespace Avatar_Privacy\Data_Storage\Database;

use Avatar_Privacy\Data_Storage\Database\Table;

/**
 * The new database table used for storing and looking up hashed identifiers
 * (e-mail addresses mostly, but might be URLs for trackbacks and other special
 * comment types).
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Hashes_Table extends Table {

	/**
	 * The table basename without the prefix.
	 *
	 * @var string
	 */
	const TABLE_BASENAME = 'avatar_privacy_hashes';

	/**
	 * The minimum version not needing a table update.
	 *
	 * @var string
	 */
	const LAST_UPDATED = '2.4';

	/**
	 * A column/field to placeholder mapping.
	 *
	 * @var string[]
	 */
	const COLUMN_FORMATS = [
		'identifier'   => '%s',
		'hash'         => '%s',
		'type'         => '%s',
		'last_updated' => '%s',
	];

	/**
	 * A list auto-update columns (e.g. date-/timestamps).
	 *
	 * @since 2.6.0
	 *
	 * @var string[]
	 */
	const AUTO_UPDATE_COLS = [
		'last_updated',
	];

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		parent::__construct( self::TABLE_BASENAME, self::LAST_UPDATED, self::COLUMN_FORMATS, self::AUTO_UPDATE_COLS );
	}

	/**
	 * Determines whether this (multisite) installation uses the global table.
	 * Result is ignored for single-site installations.
	 *
	 * @return bool
	 */
	public function use_global_table() {
		return false;
	}

	/**
	 * Retrieves the CREATE TABLE definition formatted for use by \db_delta(),
	 * without the charset collate clause.
	 *
	 * Example:
	 * `CREATE TABLE some_table (
	 *  id mediumint(9) NOT NULL AUTO_INCREMENT,
	 *  some_column varchar(100) NOT NULL,
	 *  PRIMARY KEY (id)
	 * )`
	 *
	 * @param string $table_name The table name including any prefixes.
	 *
	 * @return string
	 */
	protected function get_table_definition( $table_name ) {
		// TODO: The identifier length should be increased to at least 200 (or
		// better 256 as in 2.4.0) in one of the next versions.
		return "CREATE TABLE {$table_name} (
				identifier varchar(175) NOT NULL,
				hash char(64) CHARACTER SET ascii NOT NULL,
				type varchar(20) CHARACTER SET ascii NOT NULL,
				last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY (hash, type),
				UNIQUE KEY identifier (identifier, type)
			)";
	}

	/**
	 * Fixes the table schema when dbDelta cannot cope with the changes.
	 *
	 * The table itself is already guaranteed to exist.
	 *
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return bool                    True if the schema was modified, false otherwise.
	 */
	public function maybe_upgrade_schema( $previous_version ) {
		return false;
	}

	/**
	 * Sometimes, the table data needs to updated when upgrading.
	 *
	 * The table itself is already guarantueed to exist.
	 *
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return int                     The number of upgraded rows.
	 */
	public function maybe_upgrade_data( $previous_version ) {
		return 0;
	}
}
