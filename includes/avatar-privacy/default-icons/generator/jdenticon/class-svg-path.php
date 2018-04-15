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
 * A point.
 */
class SVG_Path {

	/**
	 * The path data.
	 *
	 * @var string
	 */
	private $data = '';

	/**
	 * Prepares a measure to be used as a measure in an SVG path, by
	 * rounding the measure to a single decimal. This reduces the file
	 * size of the generated SVG with more than 50% in some cases.
	 *
	 * @param  float $value A value.
	 *
	 * @return float
	 */
	private function get_svg_value( $value ) {
		return \round( $value, 1 );
	}

	/**
	 * Adds a polygon with the current fill color to the SVG path.
	 *
	 * @param Point[] $points An array of Point objects.
	 */
	public function add_polygon( array $points ) {
		$first = \array_shift( $points );
		$data  = "M{$this->get_svg_value( $first->x )} {$this->get_svg_value( $first->y )}";

		foreach ( $points as $p ) {
			$data .= "L{$this->get_svg_value( $p->x )} {$this->get_svg_value( $p->y )}";
		}

		$this->data .= "{$data}Z";
	}

	/**
	 * Adds a circle with the current fill color to the SVG path.
	 *
	 * @param Point   $origin            The upper left corner of the circle bounding box.
	 * @param float   $diameter          The diameter of the circle.
	 * @param boolean $counter_clockwise True if the circle is drawn counter-clockwise (will result in a hole if rendered on a clockwise path).
	 */
	public function add_circle( $origin, $diameter, $counter_clockwise ) {
		$sweep_flag   = $counter_clockwise ? 0 : 1;
		$svg_radius   = $this->get_svg_value( $diameter / 2 );
		$svg_diameter = $this->get_svg_value( $diameter );

		$this->data .= "M{$this->get_svg_value( $origin->x )} {$this->get_svg_value( $origin->y + $diameter / 2 )}a{$svg_radius},{$svg_radius} 0 1,{$sweep_flag} {$svg_diameter},0a{$svg_radius},{$svg_radius} 0 1,{$sweep_flag} -{$svg_diameter},0";
	}

	/**
	 * Retrieves the path data.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->data;
	}
}
