<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icon_Provider;

/**
 * A default icon provider implementation using static SVG images.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class SVG_Icon_Provider extends Static_Icon_Provider {

	/**
	 * Retrieves the default icon.
	 *
	 * @since  2.7.0 Parameter `$force` added.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  int    $size     The requested size in pixels.
	 * @param  bool   $force    Whether the icon cache should be invalidated (if applicable).
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size, bool $force = false ) {
		return \plugins_url( "public/images/{$this->icon_basename}.svg", \AVATAR_PRIVACY_PLUGIN_FILE );
	}
}
