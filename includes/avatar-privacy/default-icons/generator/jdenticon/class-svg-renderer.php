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
class SVG_Renderer {

	/**
	 * An array of SVG paths indexed by fill color.
	 *
	 * @var SVG_Path[]
	 */
	private $paths_by_color = [];

	/**
	 * The current SVG path.
	 *
	 * @var SVG_Path
	 */
	private $path;

	/**
	 * The target SVG writer.
	 *
	 * @var SVG_Writer
	 */
	private $target;

	/**
	 * Creates a new instance.
	 *
	 * @param SVG_Writer $target The target SVG writer.
	 */
	public function __construct( SVG_Writer $target ) {
		$this->target = $target;

		// FIXME: SVG_Writer and SVG_Renderer should be merged.
	}

	/**
	 * Fills the background with the specified color.
	 *
	 * @param string $color Fill color on the format #rrggbb[aa].
	 */
	public function set_background( $color ) {
		if ( \preg_match( '/^#([0-9a-f]{6})([0-9a-f]{2})?$/', $color, $matches ) ) {
			$opacity = isset( $matches[2] ) ? \hexdec( $matches[2] ) / 255 : 1;
			$this->target->set_background( $matches[1], $opacity );
		}
	}

	/**
	 * Marks the beginning of a new shape of the specified color. Should be ended with a call to end_shape().
	 *
	 * @param  string $color Fill color on format #xxxxxx.
	 */
	public function begin_shape( $color ) {
		if ( empty( $this->paths_by_color[ $color ] ) ) {
			$this->paths_by_color[ $color ] = new SVG_Path();
		}

		$this->path = $this->paths_by_color[ $color ];
	}

	/**
	 * Marks the end of the currently drawn shape.
	 */
	public function end_shape() {
		$this->path = null;
	}

	/**
	 * Adds a polygon with the current fill color to the SVG path.
	 *
	 * @param Point[] $points An array of Point objects.
	 */
	public function add_polygon( array $points ) {
		$this->path->add_polygon( $points );
	}

	/**
	 * Adds a circle with the current fill color to the SVG path.
	 *
	 * @param Point   $origin            The upper left corner of the circle bounding box.
	 * @param float   $diameter          The diameter of the circle.
	 * @param boolean $counter_clockwise True if the circle is drawn counter-clockwise (will result in a hole if rendered on a clockwise path).
	 */
	public function add_circle( $origin, $diameter, $counter_clockwise ) {
		$this->path->add_circle( $origin, $diameter, $counter_clockwise );
	}

	/**
	 * Called when the icon has been completely drawn.
	 */
	public function finish() {
		foreach ( $this->paths_by_color as $color => $path ) {
			$this->target->append( $color, (string) $path );
		}
	}
}
