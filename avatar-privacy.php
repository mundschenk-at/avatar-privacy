<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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
 *
 * @wordpress-plugin
 * Plugin Name: Avatar Privacy
 * Plugin URI: https://code.mundschenk.at/avatar-privacy/
 * Description: Adds options to enhance the privacy when using avatars.
 * Author: Peter Putzer
 * Author URI: https://code.mundschenk.at
 * Version: 2.3.4-beta.1
 * Requires at least: 4.9
 * Requires PHP: 5.6
 * License: GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avatar-privacy
 */

// Don't do anything if called directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) ) {
	die();
}

// Make plugin file path available globally.
if ( ! defined( 'AVATAR_PRIVACY_PLUGIN_FILE' ) ) {
	define( 'AVATAR_PRIVACY_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AVATAR_PRIVACY_PLUGIN_PATH' ) ) {
	define( 'AVATAR_PRIVACY_PLUGIN_PATH', dirname( __FILE__ ) );
}

require_once dirname( __FILE__ ) . '/includes/class-avatar-privacy-requirements.php';

/**
 * Load the plugin after checking for the necessary PHP version.
 *
 * It's necessary to do this here because main class relies on namespaces.
 */
function avatar_privacy_run() {

	$requirements = new Avatar_Privacy_Requirements();

	if ( $requirements->check() ) {
		// Autoload the rest of your classes.
		require_once __DIR__ . '/vendor/autoload.php'; // phpcs:ignore PHPCompatibility.Keywords.NewKeywords.t_dirFound

		// Create and start the plugin.
		$plugin = Avatar_Privacy_Factory::get()->create( 'Avatar_Privacy\Controller' );
		$plugin->run();
	}
}
avatar_privacy_run();
