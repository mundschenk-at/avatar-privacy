<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2017-2021 Peter Putzer.
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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tests;

/**
 * Abstract base class for Avatar Privacy unit tests.
 *
 * @since 2.4.0 Refactored to use \Mundschenk\PHPUnit_Cross_Version\TestCase.
 */
abstract class TestCase extends \Mundschenk\PHPUnit_Cross_Version\TestCase {

	/**
	 * Asserts the the argument is a valid GD image.
	 *
	 * @since  2.5.0
	 *
	 * @param  mixed $image The variable to assert.
	 *
	 * @return bool
	 *
	 * @phpstan-assert GdImage|resource $image
	 */
	public function assert_is_gd_image( $image ) {
		return $this->assertTrue( $this->is_gd_image( $image ) );
	}

	/**
	 * Tests whether the parameter is a GD image (resource or object).
	 *
	 * @since 2.6.0
	 *
	 * @param  mixed $image The variable to check.
	 * @return bool
	 */
	public function is_gd_image( $image ) {
		return \is_resource( $image ) && 'gd' === \get_resource_type( $image ) ||
			\is_object( $image ) && $image instanceof \GdImage;
	}
}
