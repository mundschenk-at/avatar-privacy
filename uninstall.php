<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

// Don't do anything if called directly.
if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Make plugin file path available globally (even if we probably don't need it during uninstallaton).
const AVATAR_PRIVACY_PLUGIN_FILE = __DIR__ . '/avatar-privacy.php';

// Initialize autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Uninstalls the plugin.
 *
 * @since 2.3.0 WordPress now requires PHP 5.6, so no further requirements check is necessary.
 */
function avatar_privacy_uninstall() {

	// Create and start the uninstallation handler.
	$uninstaller = \Avatar_Privacy\Factory::get()->create( \Avatar_Privacy\Components\Uninstallation::class );
	$uninstaller->run();
}
avatar_privacy_uninstall();
