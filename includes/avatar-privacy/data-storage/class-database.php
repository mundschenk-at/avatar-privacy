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

use Avatar_Privacy\Core;

/**
 * A plugin-specific database handler.
 *
 * @since 2.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Database {

	/**
	 * The table basename without the prefix.
	 *
	 * @var string
	 */
	const TABLE_BASENAME = 'avatar_privacy';

	/**
	 * Column names.
	 *
	 * @since 2.3.0
	 *
	 * @var string[]
	 */
	const COLUMNS = [
		'email',
		'hash',
		'use_gravatar',
		'last_updated',
		'log_message',
	];

	/**
	 * A column/field to placeholder mapping.
	 *
	 * @since 2.3.0
	 *
	 * @var string[]
	 */
	const PLACEHOLDER = [
		'id'           => '%d',
		'email'        => '%s',
		'hash'         => '%s',
		'use_gravatar' => '%d',
		'last_updated' => '%s',
		'log_message'  => '%s',
	];

	/**
	 * The core API.
	 *
	 * @since 2.3.0
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * A column/field to placeholder mapping.
	 *
	 * @since 2.3.0
	 *
	 * @var string[]
	 */
	private $placeholder;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.3.0 Parameter $core added.
	 *
	 * @param Core            $core            The core API.
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( Core $core, Network_Options $network_options ) {
		$this->core            = $core;
		$this->network_options = $network_options;

		// Workaround for PHP 5.6.
		$this->placeholder = self::PLACEHOLDER;
	}

	/**
	 * Retrieves the table prefix to use (for a given site or the current site).
	 *
	 * @global \wpdb    $wpdb    The WordPress Database Access Abstraction.
	 *
	 * @param  int|null $site_id Optional. The site ID. Null means the current $blog_id. Default null.
	 *
	 * @return string
	 */
	protected function get_table_prefix( $site_id = null ) {
		global $wpdb;

		if ( ! $this->use_global_table() ) {
			return $wpdb->get_blog_prefix( $site_id );
		} else {
			return $wpdb->base_prefix;
		}
	}

	/**
	 * Retrieves the table name to use (for a given site or the current site).
	 *
	 * @since 2.3.0 Visibility changed to public.
	 *
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Default null.
	 *
	 * @return string
	 */
	public function get_table_name( $site_id = null ) {
		return $this->get_table_prefix( $site_id ) . self::TABLE_BASENAME;
	}

	/**
	 * Checks if the given table exists.
	 *
	 * @since 2.3.0 Visibility changed to public.
	 *
	 * @param  string $table_name A table name.
	 *
	 * @return bool
	 */
	public function table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // WPCS: db call ok, cache ok.
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
	 * Creates the plugin's database table if it doesn't already exist. The
	 * table may be created as a global table for legacy multisite installations.
	 * Makes the name of the table available through $wpdb->avatar_privacy.
	 *
	 * @global \wpdb $wpdb             The WordPress Database Access Abstraction.
	 *
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return bool                    Returns true if the table was created/updated.
	 */
	public function maybe_create_table( $previous_version ) {
		global $wpdb;

		// Force DB update?
		$db_needs_update = \version_compare( $previous_version, '0.5', '<' );

		// Check if the table exists.
		if ( ! $db_needs_update && \property_exists( $wpdb, self::TABLE_BASENAME ) ) {
			return false;
		}

		// Set up table name.
		$table_name = $this->get_table_name();

		// Fix $wpdb object if table already exists, unless we need an update.
		if ( ! $db_needs_update && $this->table_exists( $table_name ) ) {
			$this->register_table( $wpdb, $table_name );
			return false;
		}

		// Create the plugin's table.
		$sql = "CREATE TABLE {$table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				email varchar(100) NOT NULL,
				use_gravatar tinyint(2) NOT NULL,
				last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				log_message varchar(255),
				hash varchar(64),
				PRIMARY KEY (id),
				UNIQUE KEY email (email),
				UNIQUE KEY hash (hash)
			) {$wpdb->get_charset_collate()};";
		$this->db_delta( $sql );

		if ( $this->table_exists( $table_name ) ) {
			$this->register_table( $wpdb, $table_name );
			return true;
		}

		// Should not ever happen.
		return false;
	}

	/**
	 * Registers the table with the given \wpdb instance.
	 *
	 * @param  \wpdb  $db         The database instance.
	 * @param  string $table_name The table name (with prefix).
	 */
	protected function register_table( \wpdb $db, $table_name ) {
		$basename = self::TABLE_BASENAME;

		// Make sure that $wpdb knows about our table.
		if ( \is_multisite() && $this->use_global_table() ) {
			$db->ms_global_tables[] = $basename;
		} else {
			$db->tables[] = $basename;
		}

		// Also register the "shortcut" property.
		$db->$basename = $table_name;
	}

	/**
	 * Applies the `dbDelta` function to the given queries.
	 *
	 * @param  string|string[] $queries The query to run. Can be multiple queries in an array, or a string of queries separated by semicolons.
	 * @param  bool            $execute Optional. Whether or not to execute the query right away. Default `true`.
	 *
	 * @return string[]                           Strings containing the results of the various update queries.
	 */
	protected function db_delta( $queries, $execute = true ) {
		if ( ! function_exists( 'dbDelta' ) ) {
			// Load upgrade.php for the dbDelta function.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		return \dbDelta( $queries, $execute );
	}

	/**
	 * Drops the table for the given site.
	 *
	 * @global \wpdb $wpdb The WordPress Database Access Abstraction.
	 *
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 */
	public function drop_table( $site_id = null ) {
		global $wpdb;

		$table_name = $this->get_table_name( $site_id );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Migrates data from the global database to the given site database.
	 *
	 * @global \wpdb    $wpdb    The WordPress Database Access Abstraction.
	 *
	 * @param  int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 *
	 * @return int|false         The number of migrated rows or false on error.
	 */
	public function migrate_from_global_table( $site_id = null ) {
		global $wpdb;

		// Retrieve site ID.
		$site_id = $site_id ?: \get_current_blog_id();

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
			OBJECT_K
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
		$insert_query = $this->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $site_table_name );
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
	 * Prepares the query for inserting or updating the database.
	 *
	 * @since 2.3.0 Optional parameter `$fields` added.
	 *
	 * @global \wpdb    $wpdb            The WordPress Database Access Abstraction.
	 *
	 * @param  object[] $rows_to_update  The table rows to update (existing ID, passed as index).
	 * @param  object[] $rows_to_migrate The table rows to insert (new autoincrement ID).
	 * @param  string   $table           The table name.
	 * @param  string[] $fields          Optional. The fields to migrate. Default [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ].
	 *
	 * @return string|false              The prepared query, or false.
	 */
	protected function prepare_insert_update_query( array $rows_to_update, array $rows_to_migrate, $table, array $fields = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ] ) {
		global $wpdb;

		// Allow only valid fields.
		$fields = \array_intersect( $fields, self::COLUMNS );

		if ( ( empty( $rows_to_update ) && empty( $rows_to_migrate ) ) || empty( $table ) || empty( $fields ) ) {
			return false;
		}

		// Prepare placeholders with ID.
		$prepared_update_values = $this->get_prepared_values( $rows_to_update, $fields, true );
		$values_clause_parts    = \array_fill( 0, \count( $rows_to_update ), $this->get_placeholders( $fields, true ) );

		// Prepare placeholders without ID.
		$prepared_insert_values = $this->get_prepared_values( $rows_to_migrate, $fields, false );
		$values_clause_parts    = \array_merge(
			$values_clause_parts,
			\array_fill( 0, \count( $rows_to_migrate ), $this->get_placeholders( $fields, false ) )
		);

		// Finalize columns, values clause and prepared values array.
		$columns         = '(id,' . \join( ',', $fields ) . ')';
		$prepared_values = \array_merge( $prepared_update_values, $prepared_insert_values );
		$values_clause   = \join( ',', $values_clause_parts );
		$update_clause   = $this->get_update_clause( $fields );

		return $wpdb->prepare(  // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			"INSERT INTO `{$table}` {$columns}
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

		foreach ( self::COLUMNS as $field ) {
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
		$placeholders = [];

		// ID is always included first.
		if ( $with_id ) {
			$placeholders[] = $this->placeholder['id'];
		} else {
			$placeholders[] = 'NULL';
		}

		// Add the selected fields.
		foreach ( $fields as $field ) {
			$placeholders[] = $this->placeholder[ $field ];
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
	 *
	 * @global \wpdb $wpdb The WordPress Database Access Abstraction.
	 *
	 * @return int  The number of upgraded rows.
	 */
	public function maybe_upgrade_table_data() {
		global $wpdb;

		// Prepare data used for all upgrade routines.
		$table_name = $this->get_table_name();

		// Add hashes when they are missing.
		$rows      = $wpdb->get_results( "SELECT id, email FROM {$table_name} WHERE hash is null", \OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$row_count = \count( $rows );

		if ( $row_count > 0 ) {
			// Add hashes for all retrieved rows.
			foreach ( $rows as $r ) {
				$r->hash = $this->core->get_hash( $r->email );
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
