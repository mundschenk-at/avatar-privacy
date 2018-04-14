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

namespace Avatar_Privacy\Default_Icons\Generator\Identicon;

/**
 * An SVG polygon.
 */
abstract class Polygon extends Abstract_Shape {
	/**
	 * An array of points.
	 *
	 * @var array
	 */
	private $points;

	/**
	 * Creates a new polygon instance.
	 *
	 * @param array  $points An array of arrays representing x and y cooordinates.
	 * @param string $color  An RGB color string.
	 */
	protected function __construct( array $points, $color ) {
		parent::__construct( $color );

		$this->points = $points;
	}

	/**
	 * Render the shape in the SVG format.
	 *
	 * @param int $x        Optional. The horizontal position. Default 0.
	 * @param int $y        Optional. The vertical position. Default 0.
	 * @param int $rotation Optional. The degree of rotation (0, 90, 180, 270). Default 0.
	 *
	 * @return string
	 */
	public function render( $x = 0, $y = 0, $rotation = 0 ) {
		list( $x_offset, $y_offset ) = self::ROTATION[ $rotation ];

		$polygon   = $this->get_scaled_points( $x_offset, $y_offset );
		$transform = $this->get_transforms( $x, $y, $rotation );
		$open      = empty( $transform ) ? '' : "<g {$transform}>";
		$close     = empty( $transform ) ? '' : '</g>';

		if ( ! empty( $polygon ) ) {
			return "{$open}<path d=\"M {$polygon} Z\" style=\"fill:{$this->foreground_color}\" />{$close}";
		}

		return '';
	}

	/**
	 * Returns the scaled and set-off points list.
	 *
	 * @param int $x_offset Optional. Default 0.
	 * @param int $y_offset Optional. Default 0.
	 *
	 * @return string
	 */
	protected function get_scaled_points( $x_offset = 0, $y_offset = 0 ) {
		// Create polygon with the applied ratio.
		$scaled_points = [];
		foreach ( $this->points as $point ) {
			$scaled_x        = $point[0] * self::SIZE + $x_offset;
			$scaled_y        = $point[1] * self::SIZE + $y_offset;
			$scaled_points[] = "{$scaled_x},{$scaled_y}";
		}

		return \join( ' ', $scaled_points );
	}
}
