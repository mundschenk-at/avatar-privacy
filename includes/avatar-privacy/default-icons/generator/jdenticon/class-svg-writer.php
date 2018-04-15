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

namespace Avatar_Privacy\Default_Icons\Generator\Jdenticon;

/**
 * A renderer producing SVG output.
 */
class SVG_Writer {

	/**
	 * The SVG data.
	 *
	 * @var string
	 */
	private $svg;

	/**
	 * The icon size.
	 *
	 * @var int
	 */
	private $size;

	/**
	 * Creates a new instance.
	 *
	 * @param int $size The icon size.
	 */
	public function __construct( $size ) {
		$this->size = $size;
		$this->svg  = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 {$size} {$size}\" preserveAspectRatio=\"xMidYMid meet\">";
	}

	/**
	 * Fills the background with the specified color.
	 *
	 * @param string $color   Fill color in #rrggbb format.
	 * @param float  $opacity Opacity in the range [0.0, 1.0].
	 */
	public function set_background( $color, $opacity ) {
		if ( ! empty( $opacity ) ) {
			$svg_opacity = \round( $opacity, 2 );
			$this->svg  .= "<rect width=\"100%\" height=\"100%\" fill=\"{$color}\" opacity=\"{$svg_opacity}\"/>";

		}
	}

	/**
	 * Writes a path to the SVG string.
	 *
	 * @param  string $color Fill color in #rrggbb format.
	 * @param  string $data  The SVG path data string.
	 */
	public function append( $color, $data ) {
		$this->svg .= "<path fill=\"{$color}\" d=\"{$data}\"/>";
	}

	/**
	 * Retrieves the rendered image as an SVG string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->svg . '</svg>';
	}
}
