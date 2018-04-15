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

use Avatar_Privacy\Default_Icons\Generator\Jdenticon\Shapes\Shapes;

/**
 * Draws an identicon to a specified renderer.
 */
class Icon_Generator {

	/**
	 * The SVG renderer instance.
	 *
	 * @var SVG_Renderer
	 */
	private $renderer;

	/**
	 * The graphics context.
	 *
	 * @var Context
	 */
	private $graphics;

	/**
	 * The seed string.
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * The cell size.
	 *
	 * @var int
	 */
	private $cell;

	/**
	 * The x coordinate..
	 *
	 * @var float
	 */
	private $x;

	/**
	 * The y coordinate..
	 *
	 * @var float
	 */
	private $y;

	/**
	 * An array of RGB colors.
	 *
	 * @var string[]
	 */
	private $available_colors;

	/**
	 * An array of color indexes.
	 *
	 * @var int[]
	 */
	private $selected_color_indexes;

	/**
	 * Creates a new instance.
	 *
	 * @param SVG_Renderer $renderer   A renderer instance.
	 * @param string       $hash       A hexadecimal string.
	 * @param int          $x          Optional. Default 0.
	 * @param int          $y          Optional. Default 0.
	 * @param int          $size       Optional. Default 128.
	 * @param float        $padding    Optional. Default 0.0.
	 * @param int          $background Optional. An RGB background color. Default ''.
	 */
	public function __construct( SVG_Renderer $renderer, $hash, $x = 0, $y = 0, $size = 128, $padding = 0.0, $background = '' ) {
		$this->renderer = $renderer;
		$this->hash     = $hash;
		$this->graphics = new Context( $this->renderer );

		// Set background color.
		if ( ! empty( $background ) ) {
			$this->renderer->set_background( $background );
		}

		// Calculate padding.
		$padding = (int) ( $size * $padding );
		$size   -= $padding * 2;

		// Calculate cell size and ensure it is an integer.
		$this->cell = (int) ( $size / 4 );

		// Since the cell size is integer based, the actual icon will be slightly smaller than specified => center icon.
		$this->x = $x + ( $padding + $size / 2 - $this->cell * 2 );
		$this->y = $y + ( $padding + $size / 2 - $this->cell * 2 );

		// Available colors for this icon.
		$hue                    = \hexdec( \substr( $this->hash, -7, 1 ) ) / 0xfffffff;
		$this->available_colors = Color_Theme::get_rgb_colors( $hue );
		$number_of_colors       = \count( $this->available_colors );

		// The index of the selected colors.
		$this->selected_color_indexes = [];

		for ( $i = 0; $i < 3; $i++ ) {
			$index = \hexdec( \substr( $this->hash, 8 + $i, 1 ) ) % $number_of_colors;

			if ( // Disallow dark gray and dark color combo.
				$this->is_duplicate( [ Color_Theme::DARK_GRAY, Color_Theme::DARK_COLOR ], $index ) ||
				// Disallow light gray and light color combo.
				$this->is_duplicate( [ Color_Theme::LIGHT_GRAY, Color_Theme::LIGHT_COLOR ], $index )
			) {
				$index = Color_Theme::MID_COLOR;
			}

			$this->selected_color_indexes[] = $index;
		}

		// Render sides.
		$this->render_shape( 0, Shapes::get_outer_shapes(), 2, 3, [
			[ 1, 0 ],
			[ 2, 0 ],
			[ 2, 3 ],
			[ 1, 3 ],
			[ 0, 1 ],
			[ 3, 1 ],
			[ 3, 2 ],
			[ 0, 2 ],
		] );
		// Render corners.
		$this->render_shape( 1, Shapes::get_outer_shapes(), 4, 5, [
			[ 0, 0 ],
			[ 3, 0 ],
			[ 3, 3 ],
			[ 0, 3 ],
		] );
		// Render center.
		$this->render_shape( 2, Shapes::get_center_shapes(), 1, null, [
			[ 1, 1 ],
			[ 2, 1 ],
			[ 2, 2 ],
			[ 1, 2 ],
		] );

		$this->renderer->finish();
	}

	/**
	 * Renders a shape at the given positions.
	 *
	 * @param  int   $color_index    The color to use.
	 * @param  array $shapes         An array of shapes.
	 * @param  int   $index          The shape to use.
	 * @param  int   $rotation_index The initial rotation.
	 * @param  array $positions      An array of cell positions.
	 */
	private function render_shape( $color_index, array $shapes, $index, $rotation_index, array $positions ) {
		$r     = $rotation_index ? \hexdec( \substr( $this->hash, $rotation_index, 1 ) ) : 0;
		$shape = $shapes[ \hexdec( \substr( $this->hash, $index, 1 ) ) % \count( $shapes ) ];

		$this->renderer->begin_shape( $this->available_colors[ $this->selected_color_indexes[ $color_index ] ] );

		foreach ( $positions as $i => $point ) {
			$r++;
			$this->graphics->set_transform( new Transform( new Point( $this->x + $point[0] * $this->cell, $this->y + $point[1] * $this->cell ), $this->cell, $r % 4 ) );
			$shape->render( $this->graphics, $this->cell, $i );
		}

		$this->renderer->end_shape();
	}

	/**
	 * Prevents unsuitable color themes.
	 *
	 * @param  array $values   Required.
	 * @param  int   $index    Required.
	 *
	 * @return bool
	 */
	private function is_duplicate( array $values, $index ) {
		return \in_array( $index, $values, true ) && ! empty( \array_intersect( $this->selected_color_indexes, $values ) );
	}
}
