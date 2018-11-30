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

use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 1.0.0
 * @since 2.1.0 Class is now instantiated from `uninstall.php` all methods have been made non-static.
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
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The DB handler.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string           $plugin_file The full path to the base plugin file.
	 * @param Options          $options         The options handler.
	 * @param Network_Options  $network_options The network options handler.
	 * @param Transients       $transients      The transients handler.
	 * @param Site_Transients  $site_transients The site transients handler.
	 * @param Database         $database        The database handler.
	 * @param Filesystem_Cache $file_cache      The filesystem cache handler.
	 */
	public function __construct( $plugin_file, Options $options, Network_Options $network_options, Transients $transients, Site_Transients $site_transients, Database $database, Filesystem_Cache $file_cache ) {
		$this->plugin_file     = $plugin_file;
		$this->options         = $options;
		$this->network_options = $network_options;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->database        = $database;
		$this->file_cache      = $file_cache;

	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Delete cached files.
		static::delete_cached_files( $this->file_cache );

		// Delete uploaded user avatars.
		static::delete_uploaded_avatars();

		// Delete usermeta for all users.
		static::delete_user_meta();

		// Delete/change options (from all sites in case of a  multisite network).
		static::delete_options( $this->options, $this->network_options );

		// Delete transients from sitemeta or options table.
		static::delete_transients( $this->transients, $this->site_transients );

		// Drop all our tables.
		static::drop_all_tables( $this->database );
	}

	/**
	 * Deletes uploaded avatar images.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 */
	protected static function delete_uploaded_avatars() {
		$user_avatar = User_Avatar_Upload_Handler::USER_META_KEY;
		$query       = [
			'meta_key'     => $user_avatar, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		];

		foreach ( \get_users( $query ) as $user ) {
			$avatar = $user->$user_avatar;

			if ( ! empty( $avatar['file'] ) && \file_exists( $avatar['file'] ) ) {
				\unlink( $avatar['file'] );
			}
		}
	}

	/**
	 * Deletes all cached files.
	 *
	 * @since 2.1.0 Visibility changed to protected, parameter $file_cache added.
	 *
	 * @param Filesystem_Cache $file_cache A fileystem cache handler.
	 */
	protected static function delete_cached_files( Filesystem_Cache $file_cache ) {
		$file_cache->invalidate();
	}

	/**
	 * Drops all tables.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param Database $database The database handler.
	 */
	protected static function drop_all_tables( Database $database ) {
		// Delete/change options for all other blogs (multisite).
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $site_id ) {
				$database->drop_table( $site_id );
			}
		} else {
			$database->drop_table();
		}
	}

	/**
	 * Delete all user meta data added by the plugin.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 */
	protected static function delete_user_meta() {
		\delete_metadata( 'user', 0, Core::GRAVATAR_USE_META_KEY, null, true );
		\delete_metadata( 'user', 0, Core::ALLOW_ANONYMOUS_META_KEY, null, true );
		\delete_metadata( 'user', 0, User_Avatar_Upload_Handler::USER_META_KEY, null, true );
	}

	/**
	 * Delete the plugin options (from all sites).
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param Options         $options         The options handler.
	 * @param Network_Options $network_options The network options handler.
	 */
	protected static function delete_options( Options $options, Network_Options $network_options ) {
		// Delete/change options for main blog.
		$options->delete( Core::SETTINGS_NAME );
		$options->reset_avatar_default();

		// Delete/change options for all other blogs (multisite).
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $blog_id ) {
				\switch_to_blog( $blog_id );

				// Delete our settings.
				$options->delete( Core::SETTINGS_NAME );

				// Reset avatar_default to working value if necessary.
				$options->reset_avatar_default();

				\restore_current_blog();
			}
		}

		// Delete site options as well (except for the salt).
		$network_options->delete( Network_Options::USE_GLOBAL_TABLE );
	}

	/**
	 * Delete all the plugins transients.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  Transients      $transients      The transients handler.
	 * @param  Site_Transients $site_transients The site transients handler.
	 */
	protected static function delete_transients( Transients $transients, Site_Transients $site_transients ) {
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
