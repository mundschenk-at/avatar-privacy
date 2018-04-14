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
 * A composite SVG shape.
 */
class Composite_Shape extends Abstract_Shape {

	/**
	 * The shapes to combine.
	 *
	 * @var Shape[]
	 */
	protected $shapes = [];

	/**
	 * Creates a new rectangle instance.
	 *
	 * @param Shape[] $shapes The composite shapes.
	 * @param string  $color  An RGB color string.
	 */
	public function __construct( array $shapes, $color ) {
		parent::__construct( $color );

		$this->shapes = $shapes;
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
		$transform = $this->get_transforms( $x, $y, $rotation );
		$shapes    = '';

		foreach ( $this->shapes as $shape ) {
			$shapes .= $shape->render( 0, 0, 0 );
		}

		return "<g {$transform}>{$shapes}</g>";
	}
}
