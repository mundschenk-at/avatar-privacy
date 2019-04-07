<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

/**
 * A default icon provider implementation using static images.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Static_Icon_Provider extends Abstract_Icon_Provider {

	/**
	 * The basename of the icon files residing in `public/images`.
	 *
	 * @var string
	 */
	protected $icon_basename;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.0.0 Parameter $name added.
	 * @since 2.1.0 Parameters $name and $plugin_file removed.
	 *
	 * @param string[]|string $types    Either a single identifier string or an array thereof.
	 * @param string          $basename The icon basename (without extension or size suffix).
	 */
	public function __construct( $types, $basename ) {
		parent::__construct( (array) $types );

		$this->icon_basename = $basename;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash. Ignored.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size ) {
		$use_size = ( $size > 64 ) ? '128' : '64';

		return plugins_url( "public/images/{$this->icon_basename}-{$use_size}.png", AVATAR_PRIVACY_PLUGIN_FILE );
	}
}
