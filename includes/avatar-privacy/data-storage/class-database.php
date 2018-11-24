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
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
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
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
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

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW tables LIKE %s', $table_name ) ); // WPCS: db call ok, cache ok.
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
	 * table is created as a global table for multisite installations. Makes the
	 * name of the table available through $wpdb->avatar_privacy.
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
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
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

}
