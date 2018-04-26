<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
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

use Avatar_Privacy\User_Avatar_Upload;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Handles plugin activation, deactivation and uninstallation.
 *
 * @since 1.0.0
 */
class Setup implements \Avatar_Privacy\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

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
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The core API.
	 *
	 * @var \Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string          $plugin_file     The full path to the base plugin file.
	 * @param Transients      $transients      The transients handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 * @param Options         $options         The options handler.
	 */
	public function __construct( $plugin_file, Transients $transients, Site_Transients $site_transients, Options $options ) {
		$this->plugin_file     = $plugin_file;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		$this->core    = $core;
		$this->version = $core->get_version();

		// Register various hooks.
		\register_activation_hook( $this->plugin_file,   [ $this, 'activate' ] );
		\register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );
		\register_uninstall_hook( $this->plugin_file,    [ __CLASS__, 'uninstall' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		$settings = $this->options->get( \Avatar_Privacy_Core::SETTINGS_NAME );

		// We can ignore errors here, just carry on as if for a new installation.
		$installed_version = empty( $settings['installed_version'] ) ? '' : $settings['installed_version'];

		if ( $this->version !== $installed_version ) {
			$this->plugin_updated( $installed_version );
		}

		// Check if our database table needs to created or updated.
		if ( $this->maybe_create_table( $installed_version ) ) {
			// We may need to update the contents as well.
			$this->maybe_update_table_data( $installed_version );
		}

		// Update 'installed_version'.
		$settings['installed_version'] = $this->version;
		$this->options->set( \Avatar_Privacy_Core::SETTINGS_NAME, $settings );
	}

	/**
	 * Upgrade plugin data.
	 *
	 * @param string $previous_version The version we are upgrading from.
	 */
	private function plugin_updated( $previous_version ) {
		// Upgrade from version 0.3 or lower.
		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			// Run upgrade command.
			$this->maybe_update_user_hashes();
		}

		// To be safe, let's flush the rewrite rules if there has been an update.
		\add_action( 'init', [ __CLASS__, 'flush_rewrite_rules' ] );
	}

	/**
	 * Handles plugin activation.
	 */
	public function activate() {
		self::flush_rewrite_rules();
	}

	/**
	 * Handles plugin deactivation.
	 */
	public function deactivate() {
		self::reset_avatar_default( $this->options );
		self::flush_rewrite_rules();
	}

	/**
	 * Uninstalls all the plugin's information from the database.
	 */
	public static function uninstall() {
		// Delete cached files.
		self::delete_cached_files();

		// Delete uploaded user avatars.
		self::delete_uploaded_avatars();

		// Delete usermeta for all users.
		self::delete_user_meta();

		// Delete/change options (from all sites in case of a  multisite network).
		self::delete_options();

		// Delete transients from sitemeta or options table.
		self::delete_transients();

		// Drop global table.
		self::drop_table();
	}

	/**
	 * Deletes uploaded avatar images.
	 */
	private static function delete_uploaded_avatars() {
		$user_avatar = User_Avatar_Upload::USER_META_KEY;
		$users       = \get_users( [
			'meta_key'     => $user_avatar, // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		] );

		foreach ( $users as $user ) {
			$path = $user->$user_avatar;

			if ( \file_exists( $path ) ) {
				\unlink( $path ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow
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
	 * Drops the global table.
	 */
	private static function drop_table() {
		global $wpdb;

		$table_name = self::get_table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.VIP.DirectDatabaseQuery,WordPress.WP.PreparedSQL.NotPrepared
	}

	/**
	 * Delete all user meta data added by the plugin.
	 */
	private static function delete_user_meta() {
		\delete_metadata( 'user', 0, \Avatar_Privacy_Core::GRAVATAR_USE_META_KEY, null, true );
		\delete_metadata( 'user', 0, User_Avatar_Upload::USER_META_KEY, null, true );
	}

	/**
	 * Delete the plugin options (from all sites).
	 */
	private static function delete_options() {
		$options = new Options();

		// Delete/change options for main blog.
		$options->delete( \Avatar_Privacy_Core::SETTINGS_NAME );
		self::reset_avatar_default( $options );

		// Delete/change options for all other blogs (multisite).
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $blog_id ) {
				\switch_to_blog( $blog_id );

				// Delete our settings.
				$options->delete( \Avatar_Privacy_Core::SETTINGS_NAME );

				// Reset avatar_default to working value if necessary.
				self::reset_avatar_default( $options );

				\restore_current_blog();
			}
		}
	}

	/**
	 * Delete all the plugins transients.
	 */
	private static function delete_transients() {
		if ( \is_multisite() ) {
			// Stored in sitemeta.
			$transients = new Site_Transients();
		} else {
			// Stored in wp_options.
			$transients = new Transients();
		}

		foreach ( $transients->get_keys_from_database() as $key ) {
			$transients->delete( $key, true );
		}
	}

	/**
	 * Resets the `avatar_default` option to a safe value.
	 *
	 * @param Options $options The Options handler.
	 */
	private static function reset_avatar_default( Options $options ) {
		switch ( $options->get( 'avatar_default', null, true ) ) {
			case 'rings':
			case 'comment':
			case 'im-user-offline':
			case 'view-media-artist':
				$options->set( 'avatar_default', 'mystery', true, true );
				break;
		}
	}

	/**
	 * Flushes the rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
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
	private function maybe_create_table( $previous_version ) {
		global $wpdb;

		// Force DB update?
		$db_needs_update = \version_compare( $previous_version, '0.5', '<' );

		// Check if the table exists.
		if ( ! $db_needs_update && property_exists( $wpdb, 'avatar_privacy' ) ) {
			return false;
		}

		// Set up table name.
		$table_name = self::get_table_name();

		// Fix $wpdb object if table already exists, unless we need an update.
		if ( ! $db_needs_update && $this->table_exists( $table_name ) ) {
			$wpdb->avatar_privacy = $table_name;
			return false;
		}

		// Load upgrade.php for the dbDelta function.
		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

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
		dbDelta( $sql );

		if ( $this->table_exists( $table_name ) ) {
			$wpdb->avatar_privacy = $table_name;
			return true;
		}

		// Should not ever happen.
		return false;
	}

	/**
	 * Sometimes, the table data needs to updated when upgrading.
	 *
	 * @param string $previous_version The previously installed plugin version.
	 */
	private function maybe_update_table_data( $previous_version ) {
		global $wpdb;

		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			$rows = $wpdb->get_results( "SELECT id, email FROM {$wpdb->avatar_privacy} WHERE hash is null" ); // WPCS: db call ok, cache ok.
			foreach ( $rows as $r ) {
				$this->core->update_comment_author_hash( $r->id, $r->email );
			}
		}
	}

	/**
	 * Updates user hashes where they don't exist yet.
	 */
	private function maybe_update_user_hashes() {
		$users = \get_users( [
			'meta_key'     => \Avatar_Privacy_Core::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
		] );

		foreach ( $users as $user ) {
			\update_user_meta( $user->ID, \Avatar_Privacy_Core::EMAIL_HASH_META_KEY, $this->core->get_hash( $user->user_email ) );
		}
	}

	/**
	 * Checks if the given table exists.
	 *
	 * @param  string $table_name A table name.
	 *
	 * @return bool
	 */
	private function table_exists( $table_name ) {
		global $wpdb;

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW tables LIKE %s', $table_name ) ); // WPCS: db call ok, cache ok.
	}


	/**
	 * Retrieves the table prefix to use (for a given site or the current site).
	 *
	 * @param int|null $site_id Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 *
	 * @return string
	 */
	private static function get_table_prefix( $site_id = null ) {
		global $wpdb;

		if ( ! self::uses_global_table() ) {
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
	private static function get_table_name( $site_id = null ) {
		return self::get_table_prefix( $site_id ) . 'avatar_privacy';
	}

	/**
	 * Determines whether this (multisite) installation uses the global table.
	 * Result is ignored for single-site installations.
	 *
	 * @return bool
	 */
	private static function uses_global_table() {
		/**
		 * Filters whether a global table should be enabled for multisite installations.
		 *
		 * @param bool $enable Default false.
		 */
		$global_table = \apply_filters( 'avatar_privacy_enable_global_table', false );

		return $global_table;
	}
}
