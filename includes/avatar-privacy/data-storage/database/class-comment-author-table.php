<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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
use Avatar_Privacy\Core\Hasher;
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
	const LAST_UPDATED = '0.5';

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
		'hash'         => '%s',
		'use_gravatar' => '%d',
		'last_updated' => '%s',
		'log_message'  => '%s',
	];

	/**
	 * The columns of this table.
	 *
	 * @var string[]
	 */
	private $columns;

	/**
	 * The hashing helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Hasher
	 */
	private $hasher;

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
	 * @since 2.3.0 Parameter $core replaced with $hasher.
	 *
	 * @param Hasher          $hasher            The hashing helper.
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( Hasher $hasher, Network_Options $network_options ) {
		parent::__construct( self::TABLE_BASENAME, self::LAST_UPDATED, self::COLUMN_FORMATS );

		$this->columns         = \array_keys( self::COLUMN_FORMATS );
		$this->hasher          = $hasher;
		$this->network_options = $network_options;
	}

	/**
	 * Sets up the table, including necessary data upgrades. The method is called
	 * on every page load.
	 *
	 * @since 2.4.0
	 *
	 * @param string $previous_version The previously installed plugin version.
	 */
	public function setup( $previous_version ) {
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
	public function use_global_table() {
		$global_table = $this->network_options->get( Network_Options::USE_GLOBAL_TABLE, false );

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
	protected function get_table_definition( $table_name ) {
		return "CREATE TABLE {$table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				email varchar(100) NOT NULL,
				use_gravatar tinyint(1) NOT NULL,
				last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				log_message varchar(255),
				hash varchar(64),
				PRIMARY KEY (id),
				UNIQUE KEY email (email),
				UNIQUE KEY hash (hash)
			)";
	}

	/**
	 * Tries set up the migration queue if the trigger is set.
	 *
	 * @since 2.4.0 Moved to class Comment_Author_Table.
	 */
	protected function maybe_prepare_migration_queue() {
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
	 */
	protected function maybe_migrate_from_global_table() {

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
		if ( ! empty( $queue[ $site_id ] ) ) {
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
	public function migrate_from_global_table( $site_id ) {
		global $wpdb;

		// Get table names.
		$global_table_name = $this->get_table_name( \get_main_site_id() );
		$site_table_name   = $this->get_table_name( $site_id );

		// Either we are on the main site or the "use global table" option is enabled.
		if ( $global_table_name === $site_table_name ) {
			return false;
		}

		// Select the rows to migrate.
		$like_clause     = "set with comment % (site: %, blog: {$wpdb->esc_like( $site_id )})";
		$rows_to_delete  = [];
		$rows_to_update  = [];
		$rows_to_migrate = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM `{$global_table_name}` WHERE log_message LIKE %s", $like_clause ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

				if ( \strtotime( $row->last_updated ) < \strtotime( $global_row->last_updated ) ) {
					$rows_to_update[ $row->id ] = $global_row;
				}

				$rows_to_delete[ $global_row_id ] = $global_row;

				unset( $rows_to_migrate[ $global_row_id ] );
			}
		}

		// Migrated rows need to be deleted, too.
		$rows_to_delete = $rows_to_delete + $rows_to_migrate;

		// Number of affected rows.
		$migrated = 0;
		$deleted  = 0;

		// Do INSERTs and UPDATEs in one query.
		$insert_query = $this->prepare_insert_update_query(
			$rows_to_update,
			$rows_to_migrate,
			$site_table_name,
			[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ]
		);
		if ( false !== $insert_query ) {
			$migrated = $wpdb->query( $insert_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		}

		// Do DELETEs in one query.
		$delete_query = $this->prepare_delete_query( \array_keys( $rows_to_delete ), $global_table_name );
		if ( false !== $delete_query ) {
			$deleted = $wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		}

		// Don't count updated rows twice, but do count the deleted rows if they
		// were not included in the updated rows because they were too old.
		return \max( $migrated - \count( $rows_to_update ), $deleted );
	}

	/**
	 * Prepares the query for inserting or updating multiple rows at the same time.
	 *
	 * @since 2.3.0 Optional parameter `$fields` added.
	 * @since 2.4.0 Parameter `$fields` made mandatory.
	 *
	 * @global \wpdb    $wpdb            The WordPress Database Access Abstraction.
	 *
	 * @param  object[] $rows_to_update  The table rows to update (existing ID, passed as index).
	 * @param  object[] $rows_to_insert  The table rows to insert (new autoincrement ID).
	 * @param  string   $table_name      The table name.
	 * @param  string[] $fields          The fields to insert/update (not including the autoincrement ID).
	 *
	 * @return string|false              The prepared query, or false.
	 */
	protected function prepare_insert_update_query( array $rows_to_update, array $rows_to_insert, $table_name, array $fields ) {
		global $wpdb;

		// Allow only valid fields.
		$fields = \array_intersect( $fields, $this->columns );

		if ( ( empty( $rows_to_update ) && empty( $rows_to_insert ) ) || empty( $table_name ) || empty( $fields ) ) {
			return false;
		}

		// Prepare placeholders with ID.
		$prepared_update_values = $this->get_prepared_values( $rows_to_update, $fields, true );
		$values_clause_parts    = \array_fill( 0, \count( $rows_to_update ), $this->get_placeholders( $fields, true ) );

		// Prepare placeholders without ID.
		$prepared_insert_values = $this->get_prepared_values( $rows_to_insert, $fields, false );
		$values_clause_parts    = \array_merge(
			$values_clause_parts,
			\array_fill( 0, \count( $rows_to_insert ), $this->get_placeholders( $fields, false ) )
		);

		// Finalize columns, values clause and prepared values array.
		$columns         = '(id,' . \join( ',', $fields ) . ')';
		$prepared_values = \array_merge( $prepared_update_values, $prepared_insert_values );
		$values_clause   = \join( ',', $values_clause_parts );
		$update_clause   = $this->get_update_clause( $fields );

		return $wpdb->prepare(  // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			"INSERT INTO `{$table_name}` {$columns}
			 VALUES {$values_clause}
			 ON DUPLICATE KEY UPDATE {$update_clause}",
			$prepared_values
		); // phpcs:enable WordPress.DB
	}

	/**
	 * Retrieves the update clause based on the updated fields.
	 *
	 * @since 2.3.0
	 *
	 * @param  string[] $fields  An array of database columns.
	 *
	 * @return string
	 */
	protected function get_update_clause( array $fields ) {

		$updated_fields      = \array_flip( $fields );
		$update_clause_parts = [];

		foreach ( $this->columns as $field ) {
			if ( isset( $updated_fields[ $field ] ) ) {
				$update_clause_parts[] = "{$field} = VALUES({$field})";
			} else {
				$update_clause_parts[] = "{$field} = {$field}";
			}
		}

		return \join( ",\n", $update_clause_parts );
	}

	/**
	 * Filters an array of values from a raw database result set based on the given columns.
	 *
	 * @since 2.3.0
	 *
	 * @param  object[] $rows    A query result set.
	 * @param  string[] $fields  An array of database columns.
	 * @param  bool     $with_id Optional. Whether to include the ID as the first field. Default true.
	 *
	 * @return array
	 */
	protected function get_prepared_values( array $rows, array $fields, $with_id = true ) {
		$values = [];

		foreach ( $rows as $id => $row ) {
			// Optionally include ID.
			if ( $with_id ) {
				$values[] = $id;
			}

			// Add the selected fields.
			foreach ( $fields as $field ) {
				$values[] = $row->$field;
			}
		}

		return $values;
	}

	/**
	 * Returns the placeholder string for the given fields.
	 *
	 * @since 2.3.0
	 *
	 * @param  string[] $fields  An array of database columns.
	 * @param  bool     $with_id Optional. Whether to include the ID as the first field. Default true.
	 *
	 * @return string   The comma-separated placeholders inside a pair of parentheses.
	 */
	protected function get_placeholders( array $fields, $with_id = true ) {
		// ID is always included first.
		\array_unshift( $fields, 'id' );

		// Get raw placeholders.
		$placeholders = $this->get_format( \array_flip( $fields ) );

		// Use NULL placeholder when used without ID.
		if ( ! $with_id ) {
			$placeholders[0] = 'NULL';
		}

		return '(' . \join( ',', $placeholders ) . ')';
	}

	/**
	 * Prepares the query for selecting existing rows by email.
	 *
	 * @global \wpdb    $wpdb    The WordPress Database Access Abstraction.
	 *
	 * @param  string[] $emails  An array of email adresses.
	 * @param  string   $table   The table name.
	 *
	 * @return string|false      The prepared query, or false.
	 */
	protected function prepare_email_query( array $emails, $table ) {
		global $wpdb;

		if ( empty( $emails ) || empty( $table ) ) {
			return false;
		}

		$placeholders = \join( ',', \array_fill( 0, \count( $emails ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->prepare( "SELECT * FROM `{$table}` WHERE email IN ({$placeholders})", $emails );
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $ids_to_delete );
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
	public function maybe_upgrade_data( $previous_version ) {
		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			return $this->fix_email_hashes();
		}

		return 0;
	}

	/**
	 * Adds hashes for stored e-mail addresses if necessary.
	 *
	 * @since 2.4.0
	 *
	 * @global \wpdb  $wpdb The WordPress Database Access Abstraction.
	 *
	 * @return int          The number of upgraded rows.
	 */
	protected function fix_email_hashes() {
		global $wpdb;

		// Prepare data used for all upgrade routines.
		$table_name = $this->get_table_name();

		// Add hashes when they are missing.
		$rows      = $wpdb->get_results( "SELECT id, email FROM {$table_name} WHERE hash is null", \OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$row_count = \count( $rows );

		if ( $row_count > 0 ) {
			// Add hashes for all retrieved rows.
			foreach ( $rows as $r ) {
				$r->hash = $this->hasher->get_hash( $r->email );
			}

			// Do UPDATEs in one query.
			$update_query = $this->prepare_insert_update_query( $rows, [], $table_name, [ 'hash' ] );

			// Abort if there was an error. FIXME: Should be replaced with Exception.
			if ( false === $update_query ) {
				return 0;
			}

			$wpdb->query( $update_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		}

		return $row_count;
	}
}
