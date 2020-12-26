<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

namespace Avatar_Privacy\Data_Storage;

/**
 * A plugin-specific options handler.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Options extends \Mundschenk\Data_Storage\Options {
	/**
	 * The prefix for the plugin options.
	 *
	 * @var string
	 */
	const PREFIX = 'avatar_privacy_';

	/**
	 * The name of the option containing the installed plugin version.
	 *
	 * @var string
	 */
	const INSTALLED_VERSION = 'installed_version';

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		parent::__construct( self::PREFIX );
	}

	/**
	 * Resets the `avatar_default` option to a safe value.
	 *
	 * @since 2.1.0 Moved from \Avatar_Privacy\Components\Setup and made non-static.
	 *
	 * @return void
	 */
	public function reset_avatar_default() {
		switch ( $this->get( 'avatar_default', null, true ) ) {
			case 'rings':
			case 'comment':
			case 'bubble':
			case 'im-user-offline':
			case 'bowling-pin':
			case 'view-media-artist':
			case 'silhouette':
			case 'custom':
				$this->set( 'avatar_default', 'mystery', true, true );
				break;

			default:
				return;
		}
	}
}
