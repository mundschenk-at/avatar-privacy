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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Core;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Uninstallation implements \Avatar_Privacy\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Register various hooks.
		\register_uninstall_hook( $this->plugin_file, [ self::class, 'uninstall' ] );
	}

	/**
	 * Uninstalls all the plugin's information from the database.
	 */
	public static function uninstall() {
		// The plugin is not running anymore, so we have to create handlers.
		$options         = new Options();
		$network_options = new Network_Options();
		$transients      = new Transients();
		$site_transients = new Site_Transients();

		// Delete cached files.
		self::delete_cached_files();

		// Delete uploaded user avatars.
		self::delete_uploaded_avatars();

		// Delete usermeta for all users.
		self::delete_user_meta();

		// Delete/change options (from all sites in case of a  multisite network).
		self::delete_options( $options, $network_options );

		// Delete transients from sitemeta or options table.
		self::delete_transients( $transients, $site_transients );

		// Drop all our tables.
		self::drop_all_tables( $network_options );
	}

	/**
	 * Deletes uploaded avatar images.
	 */
	private static function delete_uploaded_avatars() {
		$user_avatar = User_Avatar_Upload_Handler::USER_META_KEY;
		$users       = \get_users( [
			'meta_key'     => $user_avatar, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		] );

		foreach ( $users as $user ) {
			$avatar = $user->$user_avatar;

			if ( ! empty( $avatar['file'] ) && \file_exists( $avatar['file'] ) ) {
				\unlink( $avatar['file'] );
			}
		}
	}

	/**
	 * Deletes all cached files.
	 */
	private static function delete_cached_files() {
		$file_cache = new Filesystem_Cache();
		$file_cache->invalidate();
	}

	/**
	 * Drops the table for the given site.
	 *
	 * @param Network_Options $network_options A network options handler.
	 * @param int|null        $site_id         Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 */
	private static function drop_table( Network_Options $network_options, $site_id = null ) {
		global $wpdb;

		$table_name = Setup::get_table_name( $network_options, $site_id );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Drops all tables.
	 *
	 * @param Network_Options $network_options The network options handler.
	 */
	private static function drop_all_tables( Network_Options $network_options ) {
		// Delete/change options for all other blogs (multisite).
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $site_id ) {
				self::drop_table( $network_options, $site_id );
			}
		} else {
			self::drop_table( $network_options );
		}
	}

	/**
	 * Delete all user meta data added by the plugin.
	 */
	private static function delete_user_meta() {
		\delete_metadata( 'user', 0, Core::GRAVATAR_USE_META_KEY, null, true );
		\delete_metadata( 'user', 0, Core::ALLOW_ANONYMOUS_META_KEY, null, true );
		\delete_metadata( 'user', 0, User_Avatar_Upload_Handler::USER_META_KEY, null, true );
	}

	/**
	 * Delete the plugin options (from all sites).
	 *
	 * @param Options         $options         The options handler.
	 * @param Network_Options $network_options The network options handler.
	 */
	private static function delete_options( Options $options, Network_Options $network_options ) {
		// Delete/change options for main blog.
		$options->delete( Core::SETTINGS_NAME );
		Setup::reset_avatar_default( $options );

		// Delete/change options for all other blogs (multisite).
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $blog_id ) {
				\switch_to_blog( $blog_id );

				// Delete our settings.
				$options->delete( Core::SETTINGS_NAME );

				// Reset avatar_default to working value if necessary.
				Setup::reset_avatar_default( $options );

				\restore_current_blog();
			}
		}

		// Delete site options as well (except for the salt).
		$network_options->delete( Network_Options::USE_GLOBAL_TABLE );
	}

	/**
	 * Delete all the plugins transients.
	 *
	 * @param  Transients      $transients      The transients handler.
	 * @param  Site_Transients $site_transients The site transients handler.
	 */
	private static function delete_transients( Transients $transients, Site_Transients $site_transients ) {
		// Remove regular transients.
		foreach ( $transients->get_keys_from_database() as $key ) {
			$transients->delete( $key, true );
		}

		// Remove site transients.
		foreach ( $site_transients->get_keys_from_database() as $key ) {
			$site_transients->delete( $key, true );
		}
	}
}
