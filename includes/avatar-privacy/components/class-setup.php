<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
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

use Mundschenk\Data_Storage\Options;
use Mundschenk\Data_Storage\Site_Transients;
use Mundschenk\Data_Storage\Transients;

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

		// Register various hooks.
		\register_activation_hook( $this->plugin_file,   [ $this, 'activate' ] );
		\register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );
		\register_uninstall_hook( $this->plugin_file,    [ __CLASS__, 'uninstall' ] );
	}

	/**
	 * Handles plugin activation.
	 */
	public function activate() {

	}

	/**
	 * Handles plugin deactivation.
	 */
	public function deactivate() {

	}

	/**
	 * Uninstalls all the plugin's information from the database.
	 */
	public static function uninstall() {
		global $wpdb;

		$options = new Options( 'avatar_privacy_' );

		// Drop global table.
		$table_name = $wpdb->base_prefix . 'avatar_privacy';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.VIP.DirectDatabaseQuery,WordPress.WP.PreparedSQL.NotPrepared

		// Delete usermeta for all users.
		delete_metadata( 'user', 0, 'use_gravatar', null, true );

		// Delete/change options for main blog.
		$options->delete( \Avatar_Privacy_Core::SETTINGS_NAME );
		switch ( $options->get( 'avatar_default', null, true ) ) {
			case 'comment':
			case 'im-user-offline':
			case 'view-media-artist':
				$options->set( 'avatar_default', 'mystery', true, true );
				break;
		}

		// Delete/change options for all other blogs (multisite).
		if ( is_multisite() ) {
			foreach ( get_sites( [ 'fields' => 'ids' ] ) as $blog_id ) {
				switch_to_blog( $blog_id );

				// Delete our settings.
				$options->delete( \Avatar_Privacy_Core::SETTINGS_NAME );

				// Reset avatar_default to working value if necessary.
				switch ( $options->get( 'avatar_default', null, true ) ) {
					case 'comment':
					case 'im-user-offline':
					case 'view-media-artist':
						$options->set( 'avatar_default', 'mystery', true, true );
						break;
				}

				restore_current_blog();
			}
		}

		// Delete transients from sitemeta or options table.
		if ( is_multisite() ) {
			// Stored in sitemeta.
			$site_transients = new Site_Transients( 'avatar_privacy_' );
			foreach ( $site_transients->get_keys_from_database() as $key ) {
				$site_transients->delete( $key, true );
			}
		} else {
			// Stored in wp_options.
			$transients = new Transients( 'avatar_privacy_' );
			foreach ( $transients->get_keys_from_database() as $key ) {
				$transients->delete( $key, true );
			}
		}
	}
}
