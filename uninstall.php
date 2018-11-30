<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

require_once dirname( __FILE__ ) . '/includes/class-avatar-privacy-uninstallation-requirements.php';

/**
 * Uninstall the plugin after checking for the necessary PHP version.
 *
 * It's necessary to do this here because our classes rely on namespaces.
 */
function uninstall_avatar_privacy() {

	$requirements = new Avatar_Privacy_Uninstallation_Requirements( __FILE__ );

	if ( $requirements->check() ) {
		// Autoload the rest of your classes.
		require_once __DIR__ . '/vendor/autoload.php';

		// Create and start the plugin.
		$plugin = Avatar_Privacy_Factory::get( __FILE__ )->create( 'Avatar_Privacy\Components\Uninstallation' );
		$plugin->run();
	}
}
uninstall_avatar_privacy();