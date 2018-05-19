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

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Images;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 1.0.0
 */
class Setup implements \Avatar_Privacy\Component {

	/**
	 * Obsolete settings keys.
	 *
	 * @var string[]
	 */
	const OBSOLETE_SETTINGS = [
		'mode_optin',
		'use_gravatar',
		'mode_checkforgravatar',
		'default_show',
		'checkbox_default',
	];

	/**
	 * Obsolete avatar defaults and replacement values.
	 *
	 * @var string[]
	 */
	const OBSOLETE_AVATAR_DEFAULTS = [
		'comment'           => 'bubble',
		'im-user-offline'   => 'bowling-pin',
		'view-media-artist' => 'silhouette',
	];

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
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string          $plugin_file     The full path to the base plugin file.
	 * @param Transients      $transients      The transients handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 * @param Options         $options         The options handler.
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( $plugin_file, Transients $transients, Site_Transients $site_transients, Options $options, Network_Options $network_options ) {
		$this->plugin_file     = $plugin_file;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->network_options = $network_options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( Core $core ) {
		$this->core    = $core;
		$this->version = $core->get_version();

		// Register various hooks.
		\register_activation_hook( $this->plugin_file,   [ $this, 'activate' ] );
		\register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		// Don't use Core::get_settings to prevent cache pollution.
		$settings = $this->options->get( Core::SETTINGS_NAME, [] );

		// We can ignore errors here, just carry on as if for a new installation.
		if ( ! empty( $settings[ Options::INSTALLED_VERSION ] ) ) {
			$installed_version = $settings[ Options::INSTALLED_VERSION ];
		} elseif ( ! empty( $settings ) ) {
			// Plugin releases before 1.0 did not store the installed version.
			$installed_version = '0.4-or-earlier';
		} else {
			// The plugins was not installed previously.
			$installed_version = '';
		}

		if ( $this->version !== $installed_version ) {
			$this->plugin_updated( $installed_version, $settings );
		}

		// Check if our database table needs to created or updated.
		if ( $this->maybe_create_table( $installed_version ) ) {
			// We may need to update the contents as well.
			$this->maybe_update_table_data( $installed_version );
		}

		// Update installed version.
		$settings[ Options::INSTALLED_VERSION ] = $this->version;
		$this->options->set( Core::SETTINGS_NAME, $settings );
	}

	/**
	 * Upgrade plugin data.
	 *
	 * @param string $previous_version The version we are upgrading from.
	 * @param array  $settings         The settings array. Passed by reference to
	 *                                 allow for permanent changes. Saved at a the
	 *                                 end of the upgrade routine.
	 */
	private function plugin_updated( $previous_version, array &$settings ) {
		// Upgrade from version 0.4 or lower.
		if ( ! empty( $previous_version ) && \version_compare( $previous_version, '0.5', '<' ) ) {
			// Preserve previous multisite behavior.
			if ( \is_multisite() ) {
				$this->network_options->set( Network_Options::USE_GLOBAL_TABLE, true );
			}

			// Run upgrade command.
			$this->maybe_update_user_hashes();

			// Drop old settings.
			foreach ( self::OBSOLETE_SETTINGS as $key ) {
				unset( $settings[ $key ] );
			}
		}

		// Upgrade from anything below 1.0.RC.1.
		if ( ! empty( $previous_version ) && \version_compare( $previous_version, '1.0-rc.1', '<' ) ) {
			$this->upgrade_old_avatar_defaults( $this->options );
		}

		// To be safe, let's always flush the rewrite rules if there has been an update.
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
		self::disable_cron_jobs();
		self::reset_avatar_default( $this->options );
		self::flush_rewrite_rules();
	}

	/**
	 * Resets the `avatar_default` option to a safe value.
	 *
	 * @param Options $options The Options handler.
	 */
	public static function reset_avatar_default( Options $options ) {
		switch ( $options->get( 'avatar_default', null, true ) ) {
			case 'rings':
			case 'comment':
			case 'bubble':
			case 'im-user-offline':
			case 'bowling-pin':
			case 'view-media-artist':
			case 'silhouette':
				$options->set( 'avatar_default', 'mystery', true, true );
				break;
		}
	}

	/**
	 * Tries to upgrade the `avatar_defaults` option.
	 *
	 * @param  Options $options The Options handler.
	 */
	private function upgrade_old_avatar_defaults( Options $options ) {
		$old_default = $options->get( 'avatar_default', 'mystery', true );

		if ( ! empty( self::OBSOLETE_AVATAR_DEFAULTS[ $old_default ] ) ) {
			$options->set( 'avatar_default', self::OBSOLETE_AVATAR_DEFAULTS[ $old_default ], true, true );
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
		if ( ! $db_needs_update && \property_exists( $wpdb, 'avatar_privacy' ) ) {
			return false;
		}

		// Set up table name.
		$table_name = self::get_table_name( $this->network_options );

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
			'meta_key'     => Core::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
			'meta_compare' => 'NOT EXISTS',
		] );

		foreach ( $users as $user ) {
			\update_user_meta( $user->ID, Core::EMAIL_HASH_META_KEY, $this->core->get_hash( $user->user_email ) );
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
	 * @param Network_Options $network_options A network options handler.
	 * @param int|null        $site_id         Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 *
	 * @return string
	 */
	private static function get_table_prefix( Network_Options $network_options, $site_id = null ) {
		global $wpdb;

		if ( ! self::uses_global_table( $network_options ) ) {
			return $wpdb->get_blog_prefix( $site_id );
		} else {
			return $wpdb->base_prefix;
		}
	}

	/**
	 * Retrieves the table name to use (for a given site or the current site).
	 *
	 * @param Network_Options $network_options A network options handler.
	 * @param int|null        $site_id         Optional. The site ID. Null means the current $blog_id. Ddefault null.
	 *
	 * @return string
	 */
	public static function get_table_name( Network_Options $network_options, $site_id = null ) {
		return self::get_table_prefix( $network_options, $site_id ) . 'avatar_privacy';
	}

	/**
	 * Determines whether this (multisite) installation uses the global table.
	 * Result is ignored for single-site installations.
	 *
	 * @param Network_Options $network_options A network options handler.
	 *
	 * @return bool
	 */
	private static function uses_global_table( Network_Options $network_options ) {
		$global_table = $network_options->get( Network_Options::USE_GLOBAL_TABLE, false );

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
	 * Disables any scheduled cron jobs.
	 *
	 * This is a copy of wp_unschedule_hook(), introduced in WordPress 4.9.0.
	 * When we raise the minimum WP version to 4.9, this method can be replaced
	 * with a call to `wp_unschedule_hook()`.
	 *
	 * @param  string $hook The hook name.
	 */
	private static function unschedule_hook( $hook ) {
		$crons = _get_cron_array();
		foreach ( $crons as $timestamp => $args ) {
			unset( $crons[ $timestamp ][ $hook ] );

			if ( empty( $crons[ $timestamp ] ) ) {
				unset( $crons[ $timestamp ] );
			}
		}
		_set_cron_array( $crons );
	}

	/**
	 * Ensures that the cron jobs are disabled on each site.
	 */
	private static function disable_cron_jobs() {
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $site_id ) {
				\switch_to_blog( $site_id );
				self::unschedule_hook( Images::CRON_JOB_ACTION );
				\restore_current_blog();
			}
		} else {
			self::unschedule_hook( Images::CRON_JOB_ACTION );
		}
	}
}
