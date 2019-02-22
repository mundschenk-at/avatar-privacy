<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

// We can't rely on autoloading for the requirements check.
require_once dirname( dirname( __FILE__ ) ) . '/vendor/mundschenk-at/check-wp-requirements/class-mundschenk-wp-requirements.php'; // @codeCoverageIgnore

/**
 * A custom requirements class to check for the minimum PHP version (and nothing
 * else) during the uninstallation process .
 *
 * @since 2.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Avatar_Privacy_Uninstallation_Requirements extends Mundschenk_WP_Requirements {

	/**
	 * Creates a new requirements instance.
	 *
	 * @param string $plugin_file The full path to the plugin file.
	 */
	public function __construct( $plugin_file ) {
		$requirements = array(
			'php'              => '5.6.0',
		);

		parent::__construct( 'Avatar Privacy', $plugin_file, 'avatar-privacy', $requirements );
	}
}
