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
		\add_action( 'avatar_privacy_uninstallation_global', [ $this->file_cache, 'invalidate' ], 10, 0 );

		// Delete uploaded user avatars.
		\add_action( 'avatar_privacy_uninstallation_global', [ $this, 'delete_uploaded_avatars' ], 11, 0 );

		// Delete usermeta for all users.
		\add_action( 'avatar_privacy_uninstallation_global', [ $this, 'delete_user_meta' ], 12, 0 );

		// Delete/change options (from all sites in case of a multisite network).
		\add_action( 'avatar_privacy_uninstallation_site', [ $this, 'delete_options' ], 10, 0 );
		\add_action( 'avatar_privacy_uninstallation_global', [ $this, 'delete_network_options' ], 13, 0 );

		// Delete transients from sitemeta or options table.
		\add_action( 'avatar_privacy_uninstallation_site', [ $this, 'delete_transients' ], 11, 0 );
		\add_action( 'avatar_privacy_uninstallation_global', [ $this, 'delete_network_transients' ], 14, 0 );

		// Drop all our tables.
		\add_action( 'avatar_privacy_uninstallation_site', [ $this->database, 'drop_table' ], 12, 1 );

		// Clean up the site-specific artifacts.
		$this->do_site_cleanups();

		/**
		 * Cleans up any remaining global artifacts.
		 */
		\do_action( 'avatar_privacy_uninstallation_global' );
	}

	/**
	 * Executes all the registered site clean-ups (for all sites if on multisite).
	 *
	 * @since 2.1.0
	 */
	protected function do_site_cleanups() {
		if ( \is_multisite() ) {
			// We want all the sites across all networks.
			$query = [
				'fields'     => 'ids',
				'number'     => '',
			];
			foreach ( \get_sites( $query ) as $site_id ) {
				\switch_to_blog( $site_id );

				/**
				 * Do the registered site clean-ups for the current site.
				 *
				 * @param int|null $site_id Optional. The site (blog) ID or null if not a multisite installation.
				 */
				\do_action( 'avatar_privacy_uninstallation_site', $site_id );

				\restore_current_blog();
			}
		} else {
			/** This action is documented in class-uninstallation.php */
			\do_action( 'avatar_privacy_uninstallation_site', null );
		}
	}

	/**
	 * Deletes uploaded avatar images.
	 *
	 * @since 2.1.0 Visibility changed to public, made non-static.
	 */
	public function delete_uploaded_avatars() {
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
	 * Deletes all user meta data added by the plugin.
	 *
	 * @since 2.1.0 Visibility changed to public, made non-static.
	 */
	public function delete_user_meta() {
		\delete_metadata( 'user', 0, Core::GRAVATAR_USE_META_KEY, null, true );
		\delete_metadata( 'user', 0, Core::ALLOW_ANONYMOUS_META_KEY, null, true );
		\delete_metadata( 'user', 0, User_Avatar_Upload_Handler::USER_META_KEY, null, true );
	}

	/**
	 * Deletes the site-specific plugin options.
	 *
	 * @since 2.1.0 Visibility changed to public, made non-static.
	 */
	public function delete_options() {
		// Delete our settings.
		$this->options->delete( Core::SETTINGS_NAME );

		// Reset avatar_default to working value if necessary.
		$this->options->reset_avatar_default();
	}

	/**
	 * Deletes the global plugin options (except for the salt).
	 *
	 * @since 2.1.0
	 */
	public function delete_network_options() {
		$this->network_options->delete( Network_Options::USE_GLOBAL_TABLE );
		$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION );
		$this->network_options->delete( Network_Options::START_GLOBAL_TABLE_MIGRATION );
	}

	/**
	 * Deletes all the plugin's site-specific transients.
	 *
	 * @since 2.1.0 Visibility changed to public, made non-static.
	 */
	public function delete_transients() {
		// Remove regular transients.
		foreach ( $this->transients->get_keys_from_database() as $key ) {
			$this->transients->delete( $key, true );
		}
	}

	/**
	 * Deletes all the plugin's global transients ("site transients").
	 *
	 * @since 2.1.0
	 */
	public function delete_network_transients() {
		// Remove site transients.
		foreach ( $this->site_transients->get_keys_from_database() as $key ) {
			$this->site_transients->delete( $key, true );
		}
	}
}
