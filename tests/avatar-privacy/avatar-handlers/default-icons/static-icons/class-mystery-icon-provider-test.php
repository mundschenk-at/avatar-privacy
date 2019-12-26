<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider
 *
 * @uses ::__construct
 */
class Mystery_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\SVG_Icon_Provider::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor() {
		$sut   = m::mock( Mystery_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$types = \array_flip( [ 'mystery', 'mystery-man', 'mm' ] );

		$this->invoke_method( $sut, '__construct', [] );

		$this->assert_attribute_same( $types, 'valid_types', $sut );
		$this->assert_attribute_same( 'mystery', 'icon_basename', $sut );
	}
}
