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

namespace Avatar_Privacy\Default_Icons\Generator\Jdenticon\Shapes;

use Avatar_Privacy\Default_Icons\Generator\Jdenticon\Shape;

/**
 * A collection of shapes.
 */
abstract class Shapes {

	/**
	 * An array of shapes.
	 *
	 * @var Shape[]
	 */
	private static $outer;

	/**
	 * An array of shapes.
	 *
	 * @var Shape[]
	 */
	private static $center;

	/**
	 * Retrieves the outer shapes.
	 *
	 * @return Shape[]
	 */
	public static function get_outer_shapes() {
		if ( empty( self::$outer ) ) {
			self::$outer = [
				new Outer\Triangle(),
				new Outer\Bottom_Half_Triangle(),
				new Outer\Rhombus(),
				new Outer\Circle(),
			];
		}

		return self::$outer;
	}

	/**
	 * Retrieves the center shapes.
	 *
	 * @return Shape[]
	 */
	public static function get_center_shapes() {
		if ( empty( self::$center ) ) {
			self::$center = [
				new Center\Cut_Corner(),
				new Center\Side_Triangle(),
				new Center\Middle_Square(),
				new Center\Corner_Square(),
				new Center\Off_Center_Circle(),
				new Center\Negative_Triangle(),
				new Center\Cut_Square(),
				new Center\Half_Triangle(),
				new Center\Corner_Plus_Triangle(),
				new Center\Negative_Square(),
				new Center\Negative_Circle(),
				new Center\Half_Triangle(),
				new Center\Negative_Rhombus(),
				new Center\Conditional_Circle(),
			];
		}

		return self::$center;
	}
}
