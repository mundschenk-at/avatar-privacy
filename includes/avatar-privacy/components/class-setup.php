<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Tools\Multisite;

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
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string          $plugin_file     The full path to the base plugin file.
	 * @param Core            $core            The core API.
	 * @param Transients      $transients      The transients handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 * @param Options         $options         The options handler.
	 * @param Network_Options $network_options The network options handler.
	 * @param Database        $database        The database handler.
	 * @param Multisite       $multisite       The the multisite handler.
	 */
	public function __construct( $plugin_file, Core $core, Transients $transients, Site_Transients $site_transients, Options $options, Network_Options $network_options, Database $database, Multisite $multisite ) {
		$this->plugin_file     = $plugin_file;
		$this->core            = $core;
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
		\register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		// Force reading the settings from the DB, but do not cache the result.
		$settings = $this->core->get_settings( true );

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
		$version = $this->core->get_version();
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
		$this->maybe_load_migration_queue();
		$this->maybe_migrate_from_global_table();

		// Update installed version.
		$settings[ Options::INSTALLED_VERSION ] = $version;
		$this->options->set( Core::SETTINGS_NAME, $settings );
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
	 *
	 * @param string $previous_version The previously installed plugin version.
	 */
	protected function maybe_update_table_data( $previous_version ) {
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
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 */
	protected function maybe_update_user_hashes() {
		$args = [
			'meta_key'     => Core::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
		];

		foreach ( \get_users( $args ) as $user ) {
			\update_user_meta( $user->ID, Core::EMAIL_HASH_META_KEY, $this->core->get_hash( $user->user_email ) );
		}
	}

	/**
	 * Tries set up the migration queue if the trigger is set.
	 */
	protected function maybe_load_migration_queue() {
		$queue = $this->network_options->get( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		if ( ! empty( $queue ) && ! $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK ) ) {
			// Lock queue.
			$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK, true );

			// Store new queue, overwriting any existing queue (since this per network
			// and we already got all sites currently in the network).
			$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );

			// Unlock queue and delete trigger.
			$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK );
			$this->network_options->delete( Network_Options::START_GLOBAL_TABLE_MIGRATION );
		}
	}

	/**
	 * Tries to migrate global table data if the current site is queued.
	 */
	protected function maybe_migrate_from_global_table() {
		if ( ! \is_plugin_active_for_network( \plugin_basename( $this->plugin_file ) ) ) {
			// Nothing to see here.
			return;
		}

		if ( $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK ) ) {
			// The queue is currently locked. Try again next time.
			return;
		}

		// Lock the queue.
		$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK, true );

		// Check if we are scheduled to migrate data from the global table.
		$site_id = \get_current_blog_id();
		$queue   = $this->network_options->get( Network_Options::GLOBAL_TABLE_MIGRATION, [] );
		if ( ! empty( $queue[ $site_id ] ) ) {
			// Migrate the data.
			$this->database->migrate_from_global_table( $site_id );

			// Mark this site as done.
			unset( $queue[ $site_id ] );

			// Save the new queue.
			$this->network_options->set( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
		}

		// Unlock the queue again.
		$this->network_options->delete( Network_Options::GLOBAL_TABLE_MIGRATION_LOCK );
	}
}
