<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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
 * Version: 2.4.0-alpha.1
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * License: GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avatar-privacy
 */

namespace Avatar_Privacy;

use Avatar_Privacy\Controller;
use Avatar_Privacy\Factory;
use Avatar_Privacy\Requirements;

// Don't do anything if called directly.
if ( ! \defined( 'ABSPATH' ) || ! \defined( 'WPINC' ) ) {
	die;
}

// Make plugin file path available globally.
if ( ! \defined( 'AVATAR_PRIVACY_PLUGIN_FILE' ) ) {
	\define( 'AVATAR_PRIVACY_PLUGIN_FILE', __FILE__ );
}
if ( ! \defined( 'AVATAR_PRIVACY_PLUGIN_PATH' ) ) {
	\define( 'AVATAR_PRIVACY_PLUGIN_PATH', __DIR__ );
}

// Initialize autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load the plugin after checking for the necessary PHP version (& other requirements).
 *
 * @since  2.4.0 Moved to Avatar_Privacy\run_avatar_privacy.
 *
 * @return void
 */
function run_avatar_privacy() {

	// Check plugin requirements.
	$requirements = new Requirements();
	if ( $requirements->check() ) {

		/**
		 * Create and start the plugin.
		 *
		 * @var Controller
		 */
		$plugin = Factory::get()->create( Controller::class );
		$plugin->run();
	}
}
run_avatar_privacy();
