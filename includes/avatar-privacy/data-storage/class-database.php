<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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
 * A plugin-specific database handler.
 *
 * @since 2.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Database {

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Creates a new instance.
	 *
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( Network_Options $network_options ) {
		$this->network_options = $network_options;
	}

	/**
	 * Retrieves the table prefix to use (for a given site or the current site).
	 *
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Default null.
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
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Default null.
	 *
	 * @return string
	 */
	protected function get_table_name( $site_id = null ) {
		return $this->get_table_prefix( $site_id ) . 'avatar_privacy';
	}

	/**
	 * Checks if the given table exists.
	 *
	 * @param  string $table_name A table name.
	 *
	 * @return bool
	 */
	protected function table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // WPCS: db call ok, cache ok.
	}

	/**
	 * Determines whether this (multisite) installation uses the global table.
	 * Result is ignored for single-site installations.
	 *
	 * @return bool
	 */
	protected function use_global_table() {
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
	 * @param string $previous_version The previously installed plugin version.
	 *
	 * @return bool                    Returns true if the table was created/updated.
	 */
	public function maybe_create_table( $previous_version ) {
		global $wpdb;

		// Force DB update?
		$db_needs_update = \version_compare( $previous_version, '0.5', '<' );

		// Check if the table exists.
		if ( ! $db_needs_update && \property_exists( $wpdb, 'avatar_privacy' ) ) {
			return false;
		}

		// Set up table name.
		$table_name = $this->get_table_name();

		// Fix $wpdb object if table already exists, unless we need an update.
		if ( ! $db_needs_update && $this->table_exists( $table_name ) ) {
			$wpdb->avatar_privacy = $table_name;
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
			$wpdb->avatar_privacy = $table_name;
			return true;
		}

		// Should not ever happen.
		return false;
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
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 */
	public function drop_table( $site_id = null ) {
		global $wpdb;

		$table_name = $this->get_table_name( $site_id );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrates data from the global database to the given site database.
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
			$wpdb->prepare( "SELECT * FROM `{$global_table_name}` WHERE log_message LIKE %s", $like_clause ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
	 * @param  object[] $rows_to_update  The table rows to update (existing ID).
	 * @param  object[] $rows_to_migrate The table rows to insert (new autoincrement ID).
	 * @param  string   $table           The table name.
	 *
	 * @return string|false              The prepared query, or false.
	 */
	protected function prepare_insert_update_query( array $rows_to_update, array $rows_to_migrate, $table ) {
		global $wpdb;

		if ( ( empty( $rows_to_update ) && empty( $rows_to_migrate ) ) || empty( $table ) ) {
			return false;
		}

		$values_clause_parts = [];
		$prepared_values     = [];

		foreach ( $rows_to_update as $id => $row ) {
			$values_clause_parts[] = '(%d,%s,%s,%d,%s,%s)';
			$prepared_values[]     = $id;
			$prepared_values[]     = $row->email;
			$prepared_values[]     = $row->hash;
			$prepared_values[]     = $row->use_gravatar;
			$prepared_values[]     = $row->last_updated;
			$prepared_values[]     = $row->log_message;
		}

		foreach ( $rows_to_migrate as $row ) {
			$values_clause_parts[] = '(NULL,%s,%s,%d,%s,%s)';
			$prepared_values[]     = $row->email;
			$prepared_values[]     = $row->hash;
			$prepared_values[]     = $row->use_gravatar;
			$prepared_values[]     = $row->last_updated;
			$prepared_values[]     = $row->log_message;
		}

		$values_clause = \join( ',', $values_clause_parts );

		return $wpdb->prepare(  // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			"INSERT INTO `{$table}` (id,email,hash,use_gravatar,last_updated,log_message)
			 VALUES {$values_clause}
			 ON DUPLICATE KEY UPDATE
				 email = VALUES(email),
				 hash = VALUES(hash),
				 use_gravatar = VALUES(use_gravatar),
				 last_updated = VALUES(last_updated),
				 log_message = VALUES(log_message)",
			$prepared_values
		); // phpcs:enable WordPress.DB
	}

	/**
	 * Prepares the query for selecting existing rows by email.
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

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->prepare( "SELECT * FROM `{$table}` WHERE email IN ({$placeholders})", $emails );
	}

	/**
	 * Prepares the query for deleting obsolete rows from the database.
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

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		return $wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $ids_to_delete );
	}
}
