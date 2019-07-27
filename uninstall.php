<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

namespace Avatar_Privacy;

use Avatar_Privacy\Factory;
use Avatar_Privacy\Components\Uninstallation;


// Don't do anything if called directly.
if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

// Make plugin file path available globally (even if we probably don't need it during uninstallaton).
if ( ! \defined( 'AVATAR_PRIVACY_PLUGIN_FILE' ) ) {
	\define( 'AVATAR_PRIVACY_PLUGIN_FILE', __DIR__ . '/avatar-privacy.php' );
}
if ( ! \defined( 'AVATAR_PRIVACY_PLUGIN_PATH' ) ) {
	\define( 'AVATAR_PRIVACY_PLUGIN_PATH', __DIR__ );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Uninstall the plugin.
 *
 * @since  2.4.0 Moved to Avatar_Privacy\uninstall_avatar_privacy.
 *
 * @return void
 */
function uninstall_avatar_privacy() {
	/**
	 * Create and start the uninstallation handler.
	 *
	 * @var Uninstallation
	 */
	$uninstaller = Factory::get()->create( Uninstallation::class );
	$uninstaller->run();
}
uninstall_avatar_privacy();
