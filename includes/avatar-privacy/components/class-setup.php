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

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database;
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
	 * The DB handler.
	 *
	 * @var Database
	 */
	private $database;

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
	 */
	public function __construct( $plugin_file, Core $core, Transients $transients, Site_Transients $site_transients, Options $options, Network_Options $network_options, Database $database ) {
		$this->plugin_file     = $plugin_file;
		$this->core            = $core;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->network_options = $network_options;
		$this->database        = $database;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {

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
		// Force reading the settings from the DB, but do not cache the result.
		$settings = $this->core->get_settings( true );

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
		if ( $this->database->maybe_create_table( $installed_version ) ) {
			// We may need to update the contents as well.
			$this->maybe_update_table_data( $installed_version );
		}

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
		\add_action( 'init', 'flush_rewrite_rules' );
	}

	/**
	 * Handles plugin activation.
	 */
	public function activate() {
		\flush_rewrite_rules();
	}

	/**
	 * Handles plugin deactivation.
	 */
	public function deactivate() {
		$this->disable_cron_jobs();
		$this->options->reset_avatar_default();
		\flush_rewrite_rules();
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
	 * Ensures that the cron jobs are disabled on each site.
	 *
	 * @since 2.1.0 Made non-static, visibility changed to protected.
	 */
	protected function disable_cron_jobs() {
		if ( \is_multisite() ) {
			foreach ( \get_sites( [ 'fields' => 'ids' ] ) as $site_id ) {
				\switch_to_blog( $site_id );
				\wp_unschedule_hook( Image_Proxy::CRON_JOB_ACTION );
				\restore_current_blog();
			}
		} else {
			\wp_unschedule_hook( Image_Proxy::CRON_JOB_ACTION );
		}
	}
}
