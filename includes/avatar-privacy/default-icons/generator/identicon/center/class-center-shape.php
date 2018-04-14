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

namespace Avatar_Privacy\Default_Icons\Generator\Identicon\Center;

use Avatar_Privacy\Default_Icons\Generator\Identicon\Shape;
use Avatar_Privacy\Default_Icons\Generator\Identicon\Composite_Shape;
use Avatar_Privacy\Default_Icons\Generator\Identicon\Rectangle;

/**
 * The center sprite.
 */
class Center_Shape extends Composite_Shape {

	/**
	 * Creates a new instance.
	 *
	 * @param Shape  $shape      The center shape.
	 * @param string $background An RGB color string.
	 */
	public function __construct( Shape $shape, $background ) {
		parent::__construct( [
			new Rectangle( self::SIZE, self::SIZE, $background ),
			$shape,
		], $background );
	}
}
