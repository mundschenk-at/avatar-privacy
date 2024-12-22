<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database\Hashes_Table;
use Avatar_Privacy\Data_Storage\Database\Table; // phpcs:ignore ImportDetection.Imports.RequireImports.Import -- needed for type annotations.
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
 *
 * @phpstan-import-type SettingsFields from \Avatar_Privacy\Core\Settings
 *
 * @phpstan-type ObsoleteSettingsFields array<self::OBSOLETE_SETTINGS_FILES, mixed>
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
	private Options $options;

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private Network_Options $network_options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private Transients $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private Site_Transients $site_transients;

	/**
	 * The database table handlers.
	 *
	 * @var Table[]
	 */
	private array $tables;

	/**
	 * The multisite tools.
	 *
	 * @var Multisite
	 */
	private Multisite $multisite;

	/**
	 * The settings API.
	 *
	 * @since 2.4.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * The user fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private User_Fields $registered_user;

	/**
	 * The comment author fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var Comment_Author_Fields
	 */
	private Comment_Author_Fields $comment_author;

	/**
	 * Creates a new Setup instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameters $settings, $registered_user, $comment_author, and
	 *              $tables added, parameters $core and $database removed.
	 *
	 * @param Settings              $settings        The settings API.
	 * @param User_Fields           $registered_user The user fields API.
	 * @param Comment_Author_Fields $comment_author  The comment author fields API.
	 * @param Transients            $transients      The transients handler.
	 * @param Site_Transients       $site_transients The site transients handler.
	 * @param Options               $options         The options handler.
	 * @param Network_Options       $network_options The network options handler.
	 * @param Table[]               $tables          The database table handlers, indexed by their base name.
	 * @param Multisite             $multisite       The the multisite handler.
	 */
	public function __construct( Settings $settings, User_Fields $registered_user, Comment_Author_Fields $comment_author, Transients $transients, Site_Transients $site_transients, Options $options, Network_Options $network_options, array $tables, Multisite $multisite ) {
		$this->settings        = $settings;
		$this->registered_user = $registered_user;
		$this->comment_author  = $comment_author;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->network_options = $network_options;
		$this->tables          = $tables;
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

		// Clean up uploaded files after deleting the refering metadata.
		\add_action( 'deleted_user_meta', [ $this->registered_user, 'remove_orphaned_local_avatar' ], 10, 4 );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 *
	 * @return void
	 */
	public function update_check() {
		// Force reading the settings from the DB, but do not cache the result.
		$current_settings = $this->settings->get_all_settings( true );

		// We can ignore errors here, just carry on as if for a new installation.
		if ( ! empty( $current_settings[ Options::INSTALLED_VERSION ] ) ) {
			$installed_version = $current_settings[ Options::INSTALLED_VERSION ];
		} elseif ( ! empty( $current_settings ) && ! isset( $current_settings[ Options::INSTALLED_VERSION ] ) ) {
			// Plugin releases before 1.0 did not store the installed version.
			$installed_version = '0.4-or-earlier';
		} else {
			// The plugins was not installed previously.
			$installed_version = '';
		}

		// Check if the plugin data needs to be updated.
		$version       = $this->settings->get_version();
		$update_needed = $version !== $installed_version;
		$new_install   = empty( $installed_version );

		if ( $update_needed ) {
			if ( ! $new_install ) {
				// Update plugin settings if necessary.
				$current_settings = $this->update_settings( $installed_version, $current_settings );

				// Preserve previous multisite behavior.
				// This needs to happen before the database tables are set up.
				if ( \is_multisite() && \version_compare( $installed_version, '0.5', '<' ) ) {
					$this->network_options->set( Network_Options::USE_GLOBAL_TABLE, true );
				}
			}

			// Clear transients.
			$this->transients->invalidate();
			$this->site_transients->invalidate();

			// To be safe, let's always flush the rewrite rules if there has been an update.
			$this->flush_rewrite_rules_soon();
		}

		// Check if our database tables need to created or updated.
		// This also sets up the `$wpdb->avatar_privacy*` properties, so we have
		// to check on every page load.
		foreach ( $this->tables as $table ) {
			$table->setup( $installed_version );
		}

		// Run additional upgrade routines now that the tables are set up, but
		// only if this plugin has been previously installed.
		if ( $update_needed && ! $new_install ) {
			$this->update_plugin_data( $installed_version );
		}

		// Update installed version.
		$current_settings[ Options::INSTALLED_VERSION ] = $version;
		$this->options->set( Settings::OPTION_NAME, $current_settings );
	}

	/**
	 * Updates the plugin settings.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.4.0 Renamed to update_settings. Parameter $settings passed by value
	 *              and returned as the result of of the function.
	 *
	 * @param  string $previous_version The version we are upgrading from.
	 * @param  array  $settings         The settings array.
	 *
	 * @return array                    The updated settings array.
	 *
	 * @phpstan-param ObsoleteSettingsFields $settings
	 * @phpstan-return mixed[]
	 */
	protected function update_settings( $previous_version, array $settings ) {
		// Upgrade from version 0.4 or lower.
		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			// Drop old settings.
			foreach ( self::OBSOLETE_SETTINGS as $key ) {
				unset( $settings[ $key ] );
			}
		}

		return $settings;
	}

	/**
	 * Upgrades existing plugin data.
	 *
	 * @since 2.4.0
	 *
	 * @param string $previous_version The version we are upgrading from.
	 *
	 * @return void
	 */
	protected function update_plugin_data( $previous_version ) {
		// Upgrade from version 0.4 or lower.
		if ( \version_compare( $previous_version, '0.5', '<' ) ) {
			$this->maybe_update_user_hashes();
		}

		// Upgrade from anything below 1.0-rc.1.
		if ( \version_compare( $previous_version, '1.0-rc.1', '<' ) ) {
			$this->upgrade_old_avatar_defaults();
		}

		// Upgrade from anything below 2.1.0-alpha.3.
		if ( \version_compare( $previous_version, '2.1.0-alpha.3', '<' ) ) {
			$this->prefix_usermeta_keys();
		}

		// Upgrade from anything below 2.4.0.
		if ( \version_compare( $previous_version, '2.4.0', '<' ) ) {
			$this->maybe_add_email_hashes();
		}
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @since 2.1.0 Parameter `$network_wide` added.
	 *
	 * @param  bool $network_wide A flag indicating if the plugin was network-activated.
	 *
	 * @return void
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
				function () {
					\wp_unschedule_hook( Image_Proxy::CRON_JOB_ACTION );
				}
			);
		}
	}

	/**
	 * Triggers a rebuild of the rewrite rules on the next page load.
	 *
	 * @since 2.1.0
	 *
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	protected function upgrade_old_avatar_defaults() {
		$obsolete_avatar_defaults = self::OBSOLETE_AVATAR_DEFAULTS;
		$old_default              = $this->options->get( 'avatar_default', 'mystery', true );

		if ( ! empty( $obsolete_avatar_defaults[ $old_default ] ) ) {
			$this->options->set( 'avatar_default', $obsolete_avatar_defaults[ $old_default ], true, true );
		}
	}

	/**
	 * Updates user hashes where they don't exist yet.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @return void
	 */
	protected function maybe_update_user_hashes() {
		$args = [
			'meta_key'     => User_Fields::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'NOT EXISTS',
		];

		foreach ( \get_users( $args ) as $user ) {
			// Ensure that there is a user hash - retrieving the hash updates the meta field.
			$this->registered_user->get_hash( $user->ID );
		}
	}

	/**
	 * Adds hashes for stored e-mail addresses if necessary.
	 *
	 * @since 2.4.0
	 *
	 * @global \wpdb  $wpdb The WordPress Database Access Abstraction.
	 *
	 * @return int          The number of upgraded rows.
	 */
	protected function maybe_add_email_hashes() {
		global $wpdb;

		// Add hashes when they are missing.
		$emails = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB and column name.
				'SELECT c.email FROM `%1$s` c LEFT OUTER JOIN `%2$s` h ON c.email = h.identifier AND h.type = "comment" AND h.hash IS NULL',
				$wpdb->avatar_privacy,
				$wpdb->avatar_privacy_hashes
			)
		);
		$email_count = \count( $emails );

		if ( $email_count > 0 ) {
			// Add hashes for all retrieved rows.
			$rows = [];
			foreach ( $emails as $email ) {
				$rows[] = [
					'identifier' => $email,
					'hash'       => $this->comment_author->get_hash( $email ),
					'type'       => 'comment',
				];
			}

			return (int) $this->tables[ Hashes_Table::TABLE_BASENAME ]->insert_or_update( [ 'identifier', 'hash', 'type' ], $rows );
		}

		return 0;
	}
}
