<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Component;

use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table as Database;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Tools\Multisite;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Setup implements Component {

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
	 * The multisite tools.
	 *
	 * @var Multisite
	 */
	private $multisite;

	/**
	 * The settings API.
	 *
	 * @since 2.4.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The user fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private $registered_user;


	/**
	 * Creates a new Setup instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameters $settings, $registered_user added, parameter $core removed.
	 *
	 * @param Settings        $settings        The settings API.
	 * @param User_Fields     $registered_user The user fields API.
	 * @param Transients      $transients      The transients handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 * @param Options         $options         The options handler.
	 * @param Network_Options $network_options The network options handler.
	 * @param Database        $database        The database handler.
	 * @param Multisite       $multisite       The the multisite handler.
	 */
	public function __construct( Settings $settings, User_Fields $registered_user, Transients $transients, Site_Transients $site_transients, Options $options, Network_Options $network_options, Database $database, Multisite $multisite ) {
		$this->settings        = $settings;
		$this->registered_user = $registered_user;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->network_options = $network_options;
		$this->database        = $database;
		$this->multisite       = $multisite;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Register deactivation hook. Activation is handled by the update check instead.
		\register_deactivation_hook( \AVATAR_PRIVACY_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		// Force reading the settings from the DB, but do not cache the result.
		$settings = $this->settings->get_all_settings( true );

		// We can ignore errors here, just carry on as if for a new installation.
		if ( ! empty( $settings[ Options::INSTALLED_VERSION ] ) ) {
			$installed_version = $settings[ Options::INSTALLED_VERSION ];
		} elseif ( ! empty( $settings ) && ! isset( $settings[ Options::INSTALLED_VERSION ] ) ) {
			// Plugin releases before 1.0 did not store the installed version.
			$installed_version = '0.4-or-earlier';
		} else {
			// The plugins was not installed previously.
			$installed_version = '';
		}

		// The current version is (probably) newer than the previously installed one.
		$version = $this->settings->get_version();
		if ( $version !== $installed_version ) {
			// Update plugin settings if necessary.
			$this->plugin_updated( $installed_version, $settings );

			// Clear transients.
			$this->transients->invalidate();
			$this->site_transients->invalidate();
		}

		// Check if our database table needs to created or updated.
		// This also sets up the `$wpdb->avatar_privacy` property, so we have to
		// check on every page load.
		if ( $this->database->maybe_create_table( $installed_version ) ) {
			// We may need to update the contents as well.
			$this->maybe_update_table_data( $installed_version );
		}

		// The tables are set up correctly, but maybe we need to migrate some data
		// from the global table on network installations.
		$this->maybe_prepare_migration_queue();
		$this->maybe_migrate_from_global_table();

		// Update installed version.
		$settings[ Options::INSTALLED_VERSION ] = $version;
		$this->options->set( Settings::OPTION_NAME, $settings );
	}

	/**
	 * Upgrade plugin data.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param string $previous_version The version we are upgrading from.
	 * @param array  $settings         The settings array. Passed by reference to
	 *                                 allow for permanent changes. Saved at a the
	 *                                 end of the upgrade routine.
	 */
	protected function plugin_updated( $previous_version, array &$settings ) {
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
			$this->upgrade_old_avatar_defaults();
		}

		// Upgrade from anything below 2.1.0-alpha.3.
		if ( ! empty( $previous_version ) && \version_compare( $previous_version, '2.1.0-alpha.3', '<' ) ) {
			$this->prefix_usermeta_keys();
		}

		// To be safe, let's always flush the rewrite rules if there has been an update.
		$this->flush_rewrite_rules_soon();
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @since 2.1.0 Parameter `$network_wide` added.
	 *
	 * @param  bool $network_wide A flag indicating if the plugin was network-activated.
	 */
	public function deactivate( $network_wide ) {
		if ( ! $network_wide ) {
			// We've only been activated on this site, all good.
			$this->deactivate_plugin();
		} elseif ( ! \wp_is_large_network() ) {
			// This is a "small" multisite network, so get WordPress to rebuild the rewrite rules.
			$this->multisite->do_for_all_sites_in_network( [ $this, 'deactivate_plugin' ] );
		} else {
			// OK, let's try not to break anything.
			$this->multisite->do_for_all_sites_in_network(
				// We still need to disable our cron jobs, though.
				function() {
					\wp_unschedule_hook( Image_Proxy::CRON_JOB_ACTION );
				}
			);

			return;
		}
	}

	/**
	 * Triggers a rebuild of the rewrite rules on the next page load.
	 *
	 * @since 2.1.0
	 */
	public function flush_rewrite_rules_soon() {
		// Deleting the option forces a rebuild in the proper context on the next load.
		$this->options->delete( 'rewrite_rules', true );
	}

	/**
	 * Adds a prefix to the GRAVATAR_USE_META_KEY.
	 *
	 * This migration method will not work if the standard `wp_usermeta` table is
	 * replaced with something else, but there does not seem to be a good way to
	 * use the `get_user_metadata` filter hook and fulfill the goal of not breaking
	 * future Core use of `use_gravatar` as a meta key.
	 *
	 * @since 2.1.0
	 *
	 * @global wpdb $wpdb The WordPress Database Access Abstraction.
	 */
	public function prefix_usermeta_keys() {
		global $wpdb;

		// Get all users with the `use_gravatar` meta key.
		$affected_users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", 'use_gravatar' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( \count( $affected_users ) > 0 ) {
			// Update the database table.
			$rows = $wpdb->update( $wpdb->usermeta, [ 'meta_key' => User_Fields::GRAVATAR_USE_META_KEY ], [ 'meta_key' => 'use_gravatar' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			// If there were any keys to update, we also have to clear the user_meta cache group.
			if ( false !== $rows && $rows > 0 ) {

				// Clear user_meta cache for all affected users.
				foreach ( $affected_users as $user_id ) {
					\wp_cache_delete( $user_id, 'user_meta' );
				}
			}
		}
	}

	/**
	 * The deactivation tasks for a single site.
	 *
	 * @since 2.1.0
	 */
	public function deactivate_plugin() {
		// Disable cron jobs.
		\wp_unschedule_hook( Image_Proxy::CRON_JOB_ACTION );

		// Reset avatar defaults.
		$this->options->reset_avatar_default();

		// Flush rewrite rules on next page load.
		$this->flush_rewrite_rules_soon();
	}

	/**
	 * Tries to upgrade the `avatar_defaults` option.
	 *
	 * @since 2.1.0 Visibility changed to protected, $options parameter removed.
	 */
	protected function upgrade_old_avatar_defaults() {
		$obsolete_avatar_defaults = self::OBSOLETE_AVATAR_DEFAULTS;
		$old_default              = $this->options->get( 'avatar_default', 'mystery', true );

		if ( ! empty( $obsolete_avatar_defaults[ $old_default ] ) ) {
			$this->options->set( 'avatar_default', $obsolete_avatar_defaults[ $old_default ], true, true );
		}
	}

	/**
	 * Sometimes, the table data needs to updated when upgrading.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.3.0 Now uses Database::maybe_upgrade_table_data()
	 *
	 * @param string $previous_version The previously installed plugin version.
	 */
	protected function maybe_update_table_data( $previous_version ) {

		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			$this->database->maybe_upgrade_table_data();
		}
	}

	/**
	 * Updates user hashes where they don't exist yet.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 */
	protected function maybe_update_user_hashes() {
		$args = [
			'meta_key'     => User_Fields::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
		];

		foreach ( \get_users( $args ) as $user ) {
			\update_user_meta( $user->ID, User_Fields::EMAIL_HASH_META_KEY, $this->registered_user->get_hash( $user->user_email ) );
		}
	}

	/**
	 * Tries set up the migration queue if the trigger is set.
	 */
	protected function maybe_prepare_migration_queue() {
		$queue = $this->network_options->get( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		if ( \is_array( $queue ) && $this->network_options->lock( Network_Options::GLOBAL_TABLE_MIGRATION ) ) {

			if ( ! empty( $queue ) ) {
				// Store new queue, overwriting any existing queue (since this per network
				// and we already got all sites currently in the network).
				$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
			} else {
				// The "start queue" is empty, which means we should cease the migration efforts.
				$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION );
			}

			// Unlock queue and delete trigger.
			$this->network_options->unlock( Network_Options::GLOBAL_TABLE_MIGRATION );
			$this->network_options->delete( Network_Options::START_GLOBAL_TABLE_MIGRATION );
		}
	}

	/**
	 * Tries to migrate global table data if the current site is queued.
	 */
	protected function maybe_migrate_from_global_table() {

		if (
			// The plugin is not network-activated (or not on a multisite installation).
			! \is_plugin_active_for_network( \plugin_basename( \AVATAR_PRIVACY_PLUGIN_FILE ) ) ||
			// The queue is empty.
			! $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION ) ||
			// The queue is locked. Try again next time.
			! $this->network_options->lock( Network_Options::GLOBAL_TABLE_MIGRATION )
		) {
			// Nothing to see here.
			return;
		}

		// Check if we are scheduled to migrate data from the global table.
		$site_id = \get_current_blog_id();
		$queue   = $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION, [] );
		if ( ! empty( $queue[ $site_id ] ) ) {
			// Migrate the data.
			$this->database->migrate_from_global_table( $site_id );

			// Mark this site as done.
			unset( $queue[ $site_id ] );

			if ( ! empty( $queue ) ) {
				// Save the new queue.
				$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
			} else {
				// Delete it.
				$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION );
			}
		}

		// Unlock the queue again.
		$this->network_options->unlock( Network_Options::GLOBAL_TABLE_MIGRATION );
	}
}
