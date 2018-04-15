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
 * Translates and rotates a point before being passed on to the canvas context. This was previously done by the canvas context itself,
 * but this caused a rendering issue in Chrome on sizes > 256 where the rotation transformation of inverted paths was not done properly.
 */
class Transform {

	/**
	 * The upper left corner of the transformed rectangle.
	 *
	 * @var Point
	 */
	private $origin;

	/**
	 * The size of the transformed rectangle.
	 *
	 * @var float
	 */
	private $size;

	/**
	 * Rotation specified as 0 = 0 rad, 1 = 0.5π rad, 2 = π rad, 3 = 1.5π rad.
	 *
	 * @var int
	 */
	private $rotation;

	/**
	 * Creates a new transform instance.
	 *
	 * @param Point $origin   The upper left corner of the transformed rectangle.
	 * @param float $size     The size of the transformed rectangle.
	 * @param int   $rotation Rotation specified as 0 = 0 rad, 1 = 0.5π rad, 2 = π rad, 3 = 1.5π rad.
	 */
	public function __construct( Point $origin, $size, $rotation ) {
		$this->origin   = $origin;
		$this->size     = $size;
		$this->rotation = $rotation;
	}

	/**
	 * Transforms the specified point based on the translation and rotation specification for this Transform.
	 *
	 * @param  Point $point  The point.
	 * @param  float $width  Optional. The width of the transformed rectangle. If greater than 0, this will ensure the returned point is of the upper left corner of the transformed rectangle. Default 0.
	 * @param  float $height Optional. The height of the transformed rectangle. If greater than 0, this will ensure the returned point is of the upper left corner of the transformed rectangle. Default 0.
	 *
	 * @return Point
	 */
	public function transform_point( Point $point, $width = 0, $height = 0 ) {
		$right  = $this->origin->x + $this->size;
		$bottom = $this->origin->y + $this->size;

		if ( 1 === $this->rotation ) {
			return new Point( $right - $point->y - $height, $this->origin->y + $point->x );
		} elseif ( 2 === $this->rotation ) {
			return new Point( $right - $point->x - $width, $bottom - $point->y - $height );
		} elseif ( 3 === $this->rotation ) {
			return new Point( $this->origin->x + $point->y, $bottom - $point->x - $width );
		} else {
			return new Point( $this->origin->x + $point->x, $this->origin->y + $point->y );
		}
	}

	/**
	 * Transforms an array of points.
	 *
	 * @param  Point[] $points An array of points.
	 * @param  float   $width  Optional. The width of the transformed rectangle. If greater than 0, this will ensure the returned point is of the upper left corner of the transformed rectangle. Default 0.
	 * @param  float   $height Optional. The height of the transformed rectangle. If greater than 0, this will ensure the returned point is of the upper left corner of the transformed rectangle. Default 0.
	 *
	 * @return Point[]
	 */
	public function transform_many( array $points, $width = 0, $height = 0 ) {
		$result = [];
		foreach ( $points as $point ) {
			$result[] = $this->transform_point( $point, $width, $height );
		}

		return $result;
	}
}
