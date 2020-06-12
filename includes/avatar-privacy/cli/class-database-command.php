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

namespace Avatar_Privacy\CLI;

use Avatar_Privacy\CLI\Abstract_Command;
use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Database\Table as Database;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Iterators\Table as Table_Iterator;

use function WP_CLI\Utils\format_items;
use function WP_CLI\Utils\get_flag_value;

/**
 * CLI commands for accessing the Avatar Privacy database tables.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Database_Command extends Abstract_Command {

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The DB handler.
	 *
	 * @var Database
	 */
	private $db;


	/**
	 * Creates a new command instance.
	 *
	 * @param  Core     $core The core API.
	 * @param  Database $db   The database handler.
	 */
	public function __construct( Core $core, Database $db ) {
		$this->core = $core;
		$this->db   = $db;
	}

	/**
	 * Registers the command (and any optional subcommands).
	 *
	 * The method assumes that `\WP_CLI` is available.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'avatar-privacy db create', [ $this, 'create' ] );
		WP_CLI::add_command( 'avatar-privacy db show', [ $this, 'show' ] );
		WP_CLI::add_command( 'avatar-privacy db list', [ $this, 'list_' ] );
		WP_CLI::add_command( 'avatar-privacy db upgrade', [ $this, 'upgrade' ] );
	}

	/**
	 * Displays information about the database configuration of the Avatar Privacy installation.
	 *
	 * ## EXAMPLES
	 *
	 *    # Output information on the custom table used by Avatar Privacy.
	 *    $ wp avatar-privacy db show
	 *    Avatar Privacy Database Information
	 *    Version: 2.3.0
	 *    Table name: wp_avatar_privacy
	 *    The database currently contains 13 rows.
	 *
	 * @global \wpdb $wpdb       The WordPress database.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 */
	public function show( /* @scrutinizer ignore-unused */ array $args, /* @scrutinizer ignore-unused */ array $assoc_args ) {
		global $wpdb;

		// Query data.
		$count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->avatar_privacy}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$schema = $wpdb->get_results( "DESCRIBE {$wpdb->avatar_privacy}", \ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Display everything in a nice way.
		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%GAvatar Privacy Database Information%n' ) );
		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( "Version: %g{$this->core->get_version()}%n" ) );
		WP_CLI::line( WP_CLI::colorize( "Table name: %g{$this->db->get_table_name()}%n" ) );
		WP_CLI::line( '' );
		format_items( 'table', $schema, [ 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra' ] );

		if ( \is_multisite() ) {
			if ( $this->db->use_global_table() ) {
				WP_CLI::line( 'The global table is used for all sites in this network.' );
			} else {
				WP_CLI::line( 'Each site in this network uses a separate table.' );
			}
		}

		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( "The table currently contains %g{$count} rows%n." ) );
		WP_CLI::line( '' );
	}

	/**
	 * Lists the contents of Avatar Privacy's consent logging database for comment authors that were not logged in at the time.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see "Available Fields" section).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each row.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - ids
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each row:
	 *
	 * * id
	 * * email
	 * * use_gravatar
	 * * last_updated
	 *
	 * These fields are optionally available:
	 *
	 * * hash
	 * * log_message
	 *
	 * ## EXAMPLES
	 *
	 *    # Output list of email address for which gravatars are enabled.
	 *    $ wp avatar-privacy db list --field=email --use_gravatar=1
	 *    firstname.lastname@example.org
	 *    office@example.com
	 *
	 * @subcommand list
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 */
	public function list_( /* @scrutinizer ignore-unused */ array $args, array $assoc_args ) {
		$assoc_args = \wp_parse_args( $assoc_args, [
			'fields' => [ 'id', 'email', 'use_gravatar', 'last_updated' ],
			'format' => 'table',
		] );

		// Create query data.
		$where   = [];
		$db_cols = [ 'id', 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		foreach ( $db_cols as $col ) {
			if ( isset( $assoc_args[ $col ] ) ) {
				$where[ $col ] = $assoc_args[ $col ];
			}
		}

		// Load table data.
		$iterator = new Table_Iterator( [
			'table'  => $this->db->get_table_name(),
			'where'  => $where,
		] );

		// Optionally load only IDs.
		$items = $iterator;
		if ( 'ids' === $assoc_args['format'] ) {
			$items = \wp_list_pluck( $this->iterator_to_array( $iterator ), 'id' );
		}

		// Display everything in a nice way.
		$formatter = new Formatter( $assoc_args, null );
		$formatter->display_items( /* @scrutinizer ignore-type */ $items );
	}

	/**
	 * Creates the table for logging gravatar use consent for comment authors that are not logged-in WordPress users (e.g. anonymous comments).
	 *
	 * ## OPTIONS
	 *
	 * [ --global ]
	 * Creates the global table. Only valid in a multisite environment with global table use enabled.
	 *
	 * ## EXAMPLES
	 *
	 *    # Creates the database table.
	 *    $ wp avatar-privacy db create
	 *    Success: Table wp_avatar_privacy created/updated successfully.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 */
	public function create( /* @scrutinizer ignore-unused */ array $args, array $assoc_args ) {
		$global    = get_flag_value( $assoc_args, 'global', false );
		$multisite = \is_multisite();
		if ( $global ) {
			if ( ! $multisite ) {
				WP_CLI::error( 'This is not a multisite installation.' );
			} elseif ( ! $this->db->use_global_table() ) {
				WP_CLI::error( 'Cannot create global table because global table use is disabled.' );
			}
		} elseif ( $multisite && $this->db->use_global_table() && ! \is_main_site() ) {
			WP_CLI::error( 'Cannot create site-specific table because the global is used for all sites. Use `--global` switch to create the global table instead.' );
		}

		$table_name = $this->db->get_table_name();

		if ( $this->db->table_exists( $table_name ) ) {
			WP_CLI::error( WP_CLI::colorize( "Table %B{$table_name}%n already exists." ) );
		}

		if ( $this->db->maybe_create_table( '' ) ) {
			WP_CLI::success( WP_CLI::colorize( "Table %B{$table_name}%n created/updated successfully." ) );
		} else {
			WP_CLI::error( WP_CLI::colorize( "An error occured while creating the table %B{$table_name}%n." ) );
		}
	}

	/**
	 * Upgrades the gravatar-use consent data.
	 *
	 * ## OPTIONS
	 *
	 * [ --global ]
	 * Upgrades the global table. Only valid in a multisite environment with global table use enabled.
	 *
	 * ## EXAMPLES
	 *
	 *    # Creates the database table.
	 *    $ wp avatar-privacy db upgrade
	 *    Success: Table wp_avatar_privacy upgraded successfully.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 */
	public function upgrade( /* @scrutinizer ignore-unused */ array $args, array $assoc_args ) {
		$global    = get_flag_value( $assoc_args, 'global', false );
		$multisite = \is_multisite();
		if ( $global ) {
			if ( ! $multisite ) {
				WP_CLI::error( 'This is not a multisite installation.' );
			} elseif ( ! $this->db->use_global_table() ) {
				WP_CLI::error( 'Cannot upgrade global table because global table use is disabled.' );
			}
		} elseif ( $multisite && $this->db->use_global_table() && ! \is_main_site() ) {
			WP_CLI::error( 'Cannot upgrade site-specific table because the global is used for all sites. Use `--global` switch to create the global table instead.' );
		}

		// Check for existence of table.
		$table = $this->db->get_table_name();
		if ( ! $this->db->table_exists( $table ) ) {
			WP_CLI::error( WP_CLI::colorize( "Table %B{$table}%n does not exist. Use `wp avatar-privacy db create` to create it." ) );
		}

		// Upgrade table structure.
		if ( ! $this->db->maybe_create_table( '' ) ) {
			WP_CLI::error( WP_CLI::colorize( "An error occured while creating or updating the table %B{$table}%n." ) );
		}

		// Upgrade data.
		$rows = $this->db->maybe_upgrade_table_data();

		if ( $rows > 0 ) {
			WP_CLI::success( "Upgraded {$rows} rows in table {$table}." );
		} else {
			WP_CLI::success( "No rows to upgrade in table {$table}." );
		}
	}
}
