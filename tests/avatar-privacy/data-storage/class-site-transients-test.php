<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Data_Storage;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Data_Storage\Site_Transients;

/**
 * Avatar_Privacy\Data_Storage\Site_Transients unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Site_Transients
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Site_Transients
 */
class Site_Transients_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( '__' )->returnArg();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$transients = m::mock( Site_Transients::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$transients->shouldReceive( 'get' )->once()->andReturn( false );
		$transients->shouldReceive( 'invalidate' )->once();

		$transients->__construct();

		$this->assert_attribute_same( Site_Transients::PREFIX, 'prefix', $transients );
	}
}
