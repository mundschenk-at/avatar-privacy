<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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
use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * The database table used for storing (anonymous) comment author data.
 *
 * @since 2.4.0 Extracted from Avatar_Privacy\Data_Storage\Database\Table.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Comment_Author_Table extends Table {

	/**
	 * The table basename without the prefix.
	 *
	 * @var string
	 */
	const TABLE_BASENAME = 'avatar_privacy';

	/**
	 * The minimum version not needing a table update.
	 *
	 * @var string
	 */
	const LAST_UPDATED = '2.4.0';

	/**
	 * A column/field to placeholder mapping.
	 *
	 * @since 2.3.0
	 *
	 * @var string[]
	 */
	const COLUMN_FORMATS = [
		'id'           => '%d',
		'email'        => '%s',
		'use_gravatar' => '%d',
		'last_updated' => '%s',
		'log_message'  => '%s',
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
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.3.0 Parameter $core added.
	 * @since 2.4.0 Parameter $core removed.
	 *
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( Network_Options $network_options ) {
		parent::__construct( self::TABLE_BASENAME, self::LAST_UPDATED, self::COLUMN_FORMATS, self::AUTO_UPDATE_COLS );

		$this->network_options = $network_options;
	}

	/**
	 * Sets up the table, including necessary data upgrades. The method is called
	 * on every page load.
	 *
	 * @since 2.4.0
	 *
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return void
	 */
	public function setup( string $previous_version ): void {
		parent::setup( $previous_version );

		// The table is set up correctly, but maybe we need to migrate some data
		// from the global table on network installations.
		$this->maybe_prepare_migration_queue();
		$this->maybe_migrate_from_global_table();
	}

	/**
	 * Determines whether this (multisite) installation uses the global table.
	 * Result is ignored for single-site installations.
	 *
	 * @since 2.3.0 Visibility changed to public.
	 *
	 * @return bool
	 */
	public function use_global_table(): bool {
		$global_table = (bool) $this->network_options->get( Network_Options::USE_GLOBAL_TABLE, false );

		/**
		 * Filters whether a global table should be enabled for multisite installations.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enable Default false, unless this is a multisite installation
		 *                     upgraded from version 0.4 or earlier.
		 */
		return \apply_filters( 'avatar_privacy_enable_global_table', $global_table );
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
	 * @since 2.4.0
	 *
	 * @param  string $table_name The table name including any prefixes.
	 *
	 * @return string
	 */
	protected function get_table_definition( string $table_name ): string {
		return "CREATE TABLE {$table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				email varchar(100) NOT NULL,
				use_gravatar tinyint(1) DEFAULT NULL,
				last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				log_message varchar(255),
				PRIMARY KEY (id),
				UNIQUE KEY email (email)
			)";
	}

	/**
	 * Tries set up the migration queue if the trigger is set.
	 *
	 * @since 2.4.0 Moved to class Comment_Author_Table.
	 *
	 * @return void
	 */
	protected function maybe_prepare_migration_queue(): void {
		$queue = $this->network_options->get( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		if ( \is_array( $queue ) && $this->network_options->lock( Network_Options::GLOBAL_TABLE_MIGRATION ) ) {
			if ( ! empty( $queue ) ) {
				// Store new queue, overwriting any existing queue (since this per network
				// and we already got all sites currently in the network).
				$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
			} else {
				// The "start queue" is empty, which means we should cease the migration efforts.
				$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION );
			}

			// Unlock queue and delete trigger.
			$this->network_options->unlock( Network_Options::GLOBAL_TABLE_MIGRATION );
			$this->network_options->delete( Network_Options::START_GLOBAL_TABLE_MIGRATION );
		}
	}

	/**
	 * Tries to migrate global table data if the current site is queued.
	 *
	 * @since 2.4.0 Moved to class Comment_Author_Table.
	 *
	 * @return void
	 */
	protected function maybe_migrate_from_global_table(): void {
		if (
			// The plugin is not network-activated (or not on a multisite installation).
			! \is_plugin_active_for_network( \plugin_basename( \AVATAR_PRIVACY_PLUGIN_FILE ) ) ||
			// The queue is empty.
			! $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION ) ||
			// The queue is locked. Try again next time.
			! $this->network_options->lock( Network_Options::GLOBAL_TABLE_MIGRATION )
		) {
			// Nothing to see here.
			return;
		}

		// Check if we are scheduled to migrate data from the global table.
		$site_id = \get_current_blog_id();
		$queue   = $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION, [] );

		if ( \is_array( $queue ) && ! empty( $queue[ $site_id ] ) ) {
			// Migrate the data.
			$this->migrate_from_global_table( $site_id );

			// Mark this site as done.
			unset( $queue[ $site_id ] );

			if ( ! empty( $queue ) ) {
				// Save the new queue.
				$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
			} else {
				// Delete it.
				$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION );
			}
		}

		// Unlock the queue again.
		$this->network_options->unlock( Network_Options::GLOBAL_TABLE_MIGRATION );
	}

	/**
	 * Migrates data from the global database to the given site database.
	 *
	 * @since 2.4.0 Parameter $site_id made mandatory.
	 *
	 * @global \wpdb    $wpdb    The WordPress Database Access Abstraction.
	 *
	 * @param  int|null $site_id The site ID. Null means the current $blog_id.
	 *
	 * @return int|false         The number of migrated rows or false on error.
	 */
	public function migrate_from_global_table( ?int $site_id ) {
		global $wpdb;

		// Get table names.
		$global_table_name = $this->get_table_name( \get_main_site_id() );
		$site_table_name   = $this->get_table_name( $site_id );

		// Either we are on the main site or the "use global table" option is enabled.
		if ( $global_table_name === $site_table_name ) {
			return false;
		}

		// Select the rows to migrate.

		/**
		 * Rows to delete indexed by the ID column in the global table.
		 *
		 * @var \stdClass[]
		 */
		$rows_to_delete = [];
		/**
		 * Rows to migrate indexed by the ID column in the global table.
		 *
		 * @var \stdClass[]
		 */
		$rows_to_migrate = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM %i WHERE log_message LIKE %s',
				$global_table_name,
				"set with comment % (site: %, blog: {$wpdb->esc_like( $site_id )})"
			),
			\OBJECT_K
		);

		// Check for existing rows for the same email addresses.
		$emails        = \wp_list_pluck( $rows_to_migrate, 'email', 'id' );
		$emails_to_ids = \array_flip( $emails );
		$existing_rows = (array) $wpdb->get_results( $this->prepare_email_query( $emails, $site_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		foreach ( $existing_rows as $row ) {
			$global_row_id = $emails_to_ids[ $row->email ];

			if ( ! empty( $rows_to_migrate[ $global_row_id ] ) ) {
				$global_row = $rows_to_migrate[ $global_row_id ];

				if ( (int) \strtotime( $row->last_updated ) >= (int) \strtotime( $global_row->last_updated ) ) {
					unset( $rows_to_migrate[ $global_row_id ] );

					// Just delete this row.
					$rows_to_delete[ $global_row_id ] = $global_row;
				}
			}
		}

		// Migrated rows need to be deleted, too.
		$rows_to_delete = $rows_to_delete + $rows_to_migrate;

		// Do INSERTs and UPDATEs in one query.
		$migrated = $this->insert_or_update( [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ], $rows_to_migrate, $site_id );
		if ( false !== $migrated ) {
			// Do DELETEs in one query.
			$deleted      = 0;
			$delete_query = $this->prepare_delete_query( \array_keys( $rows_to_delete ), $global_table_name );
			if ( false !== $delete_query ) {
				$deleted = $wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			}

			if ( false !== $deleted ) {
				// Count the deleted rows if they were not included in the migrated
				// rows because they were too old.
				return \max( $migrated, $deleted );
			}
		}

		return false;
	}

	/**
	 * Prepares the query for selecting existing rows by email.
	 *
	 * @global \wpdb    $wpdb    The WordPress Database Access Abstraction.
	 *
	 * @param  string[] $emails  An array of email addresses.
	 * @param  string   $table   The table name.
	 *
	 * @return string|false      The prepared query, or false.
	 */
	protected function prepare_email_query( array $emails, string $table ) {
		global $wpdb;

		if ( empty( $emails ) || empty( $table ) ) {
			return false;
		}

		$placeholders = \join( ',', \array_fill( 0, \count( $emails ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->prepare( "SELECT * FROM %i WHERE email IN ({$placeholders})", \array_merge( [ $table ], $emails ) );
	}

	/**
	 * Prepares the query for deleting obsolete rows from the database.
	 *
	 * @global \wpdb  $wpdb          The WordPress Database Access Abstraction.
	 *
	 * @param  int[]  $ids_to_delete The IDs to delete.
	 * @param  string $table         The table name.
	 *
	 * @return string|false          The prepared query, or false.
	 */
	protected function prepare_delete_query( array $ids_to_delete, $table ) {
		global $wpdb;

		if ( empty( $ids_to_delete ) || empty( $table ) ) {
			return false;
		}

		$placeholders = \join( ',', \array_fill( 0, \count( $ids_to_delete ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->prepare( "DELETE FROM %i WHERE id IN ({$placeholders})", \array_merge( [ $table ], $ids_to_delete ) );
	}

	/**
	 * Fixes the table schema when dbDelta cannot cope with the changes.
	 *
	 * The table itself is already guaranteed to exist.
	 *
	 * @since 2.4.0
	 *
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return bool                    True if the schema was modified, false otherwise.
	 */
	public function maybe_upgrade_schema( string $previous_version ): bool {
		$result = false;

		if ( \version_compare( $previous_version, '2.4.0', '<' ) ) {
			$result = $this->maybe_drop_hash_column() || $result; // @phpstan-ignore-line -- to make copy & paste less error prone.
			$result = $this->maybe_fix_last_updated_column_default() || $result;
		}

		return $result;
	}

	/**
	 * Drops the obsolete 'hash' column from the table (if it exists).
	 *
	 * @since 2.4.0
	 *
	 * @return bool
	 */
	protected function maybe_drop_hash_column(): bool {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		if ( 'hash' === $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'hash' ) ) ) {
			return (bool) $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN hash', $table_name ) );
		}
		// phpcs:enable WordPress.DB

		return false;
	}

	/**
	 * Drops the obsolete 'hash' column from the table.
	 *
	 * @since 2.4.0
	 *
	 * @return bool
	 */
	protected function maybe_fix_last_updated_column_default(): bool {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$column_definition = $wpdb->get_row( $wpdb->prepare( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'last_updated' ), \ARRAY_A );
		if ( 'CURRENT_TIMESTAMP' !== $column_definition['Default'] ) {
			return (bool) $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i MODIFY COLUMN `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL', $table_name ) );
		}
		// phpcs:enable WordPress.DB

		return false;
	}

	/**
	 * Upgrades the table data if necessary.
	 *
	 * @since 2.3.0
	 * @since 2.4.0 Renamed to maybe_upgrade_data. Parameter $previous_version added.
	 *
	 * @global \wpdb  $wpdb             The WordPress Database Access Abstraction.
	 *
	 * @param  string $previous_version The previously installed plugin version.
	 *
	 * @return int                      The number of upgraded rows.
	 */
	public function maybe_upgrade_data( string $previous_version ): int {
		return 0;
	}
}
