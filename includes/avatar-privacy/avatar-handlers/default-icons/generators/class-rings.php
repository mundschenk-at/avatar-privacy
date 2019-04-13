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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;

/**
 * An icon generator.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 */
class Rings implements Generator {

	/**
	 * The "real" icon generator.
	 *
	 * @since 2.1.0
	 *
	 * @var Ring_Icon
	 */
	private $ring_icon;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0
	 *
	 * @param Ring_Icon $ring_icon The configured Ring_Icon instance.
	 */
	public function __construct( Ring_Icon $ring_icon ) {
		$this->ring_icon = $ring_icon;
	}

	/**
	 * Builds an icon based on the given seed returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size The size in pixels (ignored for SVG images).
	 *
	 * @return string|false
	 */
	public function build( $seed, /* @scrutinizer-ignore */ $size ) {
		return $this->ring_icon->get_svg_image_data( $seed );
	}
}
