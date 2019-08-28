<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Uninstallation;
use Avatar_Privacy\Data_Storage\Database;

use WP_CLI;

use function WP_CLI\Utils\get_flag_value;

/**
 * CLI commands for reoving data added by Avatar Privacy.
 *
 * @since 2.3.0
 */
class Uninstall_Command extends Abstract_Command {

	/**
	 * The setup component.
	 *
	 * @var Setup
	 */
	private $setup;

	/**
	 * The uninstallation component.
	 *
	 * @var Uninstallation
	 */
	private $uninstall;

	/**
	 * The DB handler.
	 *
	 * @var Database
	 */
	private $db;

	/**
	 * Creates a new command instance.
	 *
	 * @param  Setup          $setup     The setup component.
	 * @param  Uninstallation $uninstall The uninstallation component.
	 * @param  Database       $db        The database handler.
	 */
	public function __construct( Setup $setup, Uninstallation $uninstall, Database $db ) {
		$this->setup     = $setup;
		$this->uninstall = $uninstall;
		$this->db        = $db;
	}

	/**
	 * Registers the command (and any optional subcommands).
	 *
	 * The method assumes that `\WP_CLI` is available.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'avatar-privacy uninstall', [ $this, 'uninstall' ] );
	}

	/**
	 * Removes all data from the current site. Optionally, also removes global data on multisite.
	 *
	 * Data that will be removed:
	 * * Cached avatar images
	 * * Uploaded user avatars
	 * * Uploaded custom default images
	 * * Avatar privacy user settings
	 * * Options created by Avatar Privacy
	 * * Transients created by Avatar Privacy
	 * * Network options (on multisite)
	 * * Network transients (on multisite)
	 * * The custom database table used for non-logged-in comment author consent logging.
	 *
	 * ## OPTIONS
	 *
	 * [--live]
	 * : Actually remove the data (instead of only listing it).
	 *
	 * [--yes]
	 * : Do not ask for confirmation when removing data.
	 *
	 * [--global]
	 * : Also uninstall global data (only applicable on multisite installations).
	 *
	 * ## EXAMPLES
	 *
	 *    # Remove all data from a non-multisite installatin.
	 *    $ wp avatar-privacy uninstall
	 *
	 *    # Remove site-specific and global data from a multisite installation
	 *    # (site-specific data needs to be deleted from each site seperately).
	 *    $ wp avatar-privacy uninstall --global
	 *
	 * @global \wpdb $wpdb       The WordPress database.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 */
	public function uninstall( /* @scrutinizer ignore-unused */ array $args, array $assoc_args ) {
		$live          = get_flag_value( $assoc_args, 'live', false );
		$remove_global = get_flag_value( $assoc_args, 'global', false );
		$multisite     = \is_multisite();

		// Abort early if we're not on a multsitie installation.
		if ( $remove_global && ! $multisite ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		// On non-multisite installations, global data is always removed.
		$remove_global = $remove_global || ! $multisite;

		if ( ! $live ) {
			WP_CLI::warning( 'Starting dry run.' );
		}

		// Marker text for site-specific data.
		$site_id  = \get_current_blog_id();
		$for_site = $multisite ? " for site {$site_id}" : '';

		// List the data that will be deleted.
		$this->print_data_to_delete( $for_site, $remove_global );

		// OK, let's do this.
		if ( $live ) {
			// Get confirmation.
			WP_CLI::confirm( 'Are you sure you want to delete this data?', $assoc_args );

			// Actually delete data.
			$this->delete_data( $site_id, $for_site, $remove_global );
		} else {
			WP_CLI::success( 'Dry run finished.' );
		}
	}

	/**
	 * Deletes the data and prints a confirmation message.
	 *
	 * @param  int    $site_id       The site ID.
	 * @param  string $for_site      Label part describing the site ("for site <ID>").
	 * @param  bool   $remove_global A flag indicating that global data should be removed as well.
	 */
	protected function delete_data( $site_id, $for_site, $remove_global ) {

		// Act as if deactivating plugin.
		$this->setup->deactivate_plugin();

		// Add tasks to uninstallation actions.
		$this->uninstall->enqueue_cleanup_tasks();

		if ( $remove_global ) {
			// Remove global data.
			/** This action is documented in class-uninstallation.php */
			\do_action( 'avatar_privacy_uninstallation_global' );

			if ( \is_multisite() ) {
				// Only show extra message on multsite.
				WP_CLI::success( 'Global data deleted.' );
			}
		}

		// Remove site data.
		/** This action is documented in class-uninstallation.php */
		\do_action( 'avatar_privacy_uninstallation_site', $site_id );

		WP_CLI::success( "Site data{$for_site} deleted." );
	}

	/**
	 * Prints the list of data to be deleted.
	 *
	 * @param  string $for_site      Label part describing the site ("for site <ID>").
	 * @param  bool   $remove_global A flag indicating that global data should be removed as well.
	 */
	protected function print_data_to_delete( $for_site, $remove_global ) {
		if ( $remove_global ) {
			// List global data to delete.
			WP_CLI::line( 'Deleting cached avatar images.' );
			WP_CLI::line( 'Deleting uploaded user avatar and custom default images.' );
			WP_CLI::line( 'Deleting avatar privacy user settings (user_meta).' );

			if ( \is_multisite() ) {
				// These do not make sense in single-site environment, even though
				// they technically do exist.
				WP_CLI::line( 'Deleting network options.' );
				WP_CLI::line( 'Deleting network transients.' );
			}
		}

		// List site data to delete.
		WP_CLI::line( "Deleting options{$for_site}." );
		WP_CLI::line( "Deleting transients{$for_site}." );

		// Show dropped table.
		$table_name = $this->db->get_table_name();
		if ( ! $this->db->use_global_table() ) {
			WP_CLI::line( "Dropping table {$table_name}{$for_site}." );
		} else {
			WP_CLI::line( "Dropping global table {$table_name}." );
		}
	}
}
