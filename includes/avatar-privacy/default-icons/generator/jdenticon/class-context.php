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
 * A graphics context.
 */
class Context {

	/**
	 * The a transform operation.
	 *
	 * @var Transform
	 */
	private $transform;

	/**
	 * The renderer.
	 *
	 * @var SVG_Renderer
	 */
	private $renderer;

	/**
	 * Creates a new instance.
	 *
	 * @param SVG_Renderer $renderer The renderer.
	 */
	public function __construct( SVG_Renderer $renderer ) {
		$this->renderer  = $renderer;
		$this->transform = new Transform( new Point( 0, 0 ), 0, 0 );
	}

	/**
	 * Adds a polygon.
	 *
	 * @param Point[] $points An array of points.
	 * @param boolean $invert Optional. A flag whether the polygon should be inverted. Default false.
	 */
	public function add_polygon( array $points, $invert = false ) {
		if ( $invert ) {
			$points = \array_reverse( $points );
		}

		$this->renderer->add_polygon( $this->transform->transform_many( $points ) );
	}

	/**
	 * Adds a circle.
	 *
	 * @param Point   $origin The center of the circle.
	 * @param float   $size   The diameter of the circle.
	 * @param boolean $invert Optional. A flag whether the polygon should be inverted. Default false.
	 */
	public function add_circle( Point $origin, $size, $invert = false ) {
		$this->renderer->add_circle( $this->transform->transform_point( $origin, $size, $size ), $size, $invert );
	}

	/**
	 * Adds a rectangle.
	 *
	 * @param Point   $origin The upper left corner.
	 * @param float   $width  The width.
	 * @param float   $height The height.
	 * @param boolean $invert Optional. A flag whether the polygon should be inverted. Default false.
	 */
	public function add_rectangle( Point $origin, $width, $height, $invert = false ) {
		$this->add_polygon( [
			new Point( $origin->x,          $origin->y ),
			new Point( $origin->x + $width, $origin->y ),
			new Point( $origin->x + $width, $origin->y + $height ),
			new Point( $origin->x,          $origin->y + $height ),
		], $invert );
	}

	/**
	 * Adds a rectangle.
	 *
	 * @param Point   $origin   The upper left corner of the rectangle holding the triangle.
	 * @param float   $width    The width.
	 * @param float   $height   The height.
	 * @param int     $rotation The rotation of the triangle (clockwise). 0 = right corner of the triangle in the lower left corner of the bounding rectangle.
	 * @param boolean $invert   Optional. A flag whether the polygon should be inverted. Default false.
	 */
	public function add_triangle( Point $origin, $width, $height, $rotation, $invert = false ) {
		$points = [
			new Point( $origin->x + $width, $origin->y ),
			new Point( $origin->x + $width, $origin->y + $height ),
			new Point( $origin->x,          $origin->y + $height ),
			new Point( $origin->x,          $origin->y ),
		];
		unset( $points[ $rotation % 4 ] );

		$this->add_polygon( $points, $invert );
	}

	/**
	 * Adds a rhombus.
	 *
	 * @param Point   $origin The upper left corner of the rectangle holding the rhomobus.
	 * @param float   $width  The width.
	 * @param float   $height The height.
	 * @param boolean $invert Optional. A flag whether the polygon should be inverted. Default false.
	 */
	public function add_rhombus( Point $origin, $width, $height, $invert = false ) {
		$this->add_polygon( [
			new Point( $origin->x + $width / 2, $origin->y ),
			new Point( $origin->x + $width,     $origin->y + $height / 2 ),
			new Point( $origin->x + $width / 2, $origin->y + $height ),
			new Point( $origin->x,              $origin->y + $height / 2 ),
		], $invert );
	}

	/**
	 * Changes the transform for this graphics context.
	 *
	 * @param Transform $transform A tranform object.
	 */
	public function set_transform( Transform $transform ) {
		$this->transform = $transform;
	}
}
