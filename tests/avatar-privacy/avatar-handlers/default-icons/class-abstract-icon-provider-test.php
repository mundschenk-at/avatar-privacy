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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider
 *
 * @uses ::__construct
 */
class Abstract_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Abstract_Icon_Provider
	 */
	private $sut;

	/**
	 * The array of valid types.
	 *
	 * @var string[]
	 */
	private $valid_types;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		// Helper mocks.
		$this->valid_types = [
			'foo',
			'bar',
		];

		// Partially mock system under test.
		$this->sut = m::mock( Abstract_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invokeMethod( $this->sut, '__construct', [ $this->valid_types ] );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock        = m::mock( Abstract_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$valid_types = [ 'foobar', 'barfoo', 'rhabarber' ];

		$this->invokeMethod( $mock, '__construct', [ $valid_types ] );

		$this->assertAttributeSame( \array_flip( $valid_types ), 'valid_types', $mock );
		$this->assertAttributeSame( 'foobar', 'primary_type', $mock );
	}

	/**
	 * Tests ::provides.
	 *
	 * @covers ::provides
	 */
	public function test_provides() {
		$this->assertTrue( $this->sut->provides( 'foo' ) );
		$this->assertTrue( $this->sut->provides( 'bar' ) );
		$this->assertFalse( $this->sut->provides( 'foobar' ) );
		$this->assertFalse( $this->sut->provides( 'barfoo' ) );
	}

	/**
	 * Tests ::get_provided_types.
	 *
	 * @covers ::get_provided_types
	 */
	public function test_get_provided_types() {
		$this->assertSame( $this->valid_types, $this->sut->get_provided_types() );
	}

	/**
	 * Tests ::get_option_value.
	 *
	 * @covers ::get_option_value
	 */
	public function test_get_option_value() {
		$this->assertSame( $this->valid_types[0], $this->sut->get_option_value() );
	}

	/**
	 * Tests ::get_name.
	 *
	 * @covers ::get_name
	 */
	public function test_get_name() {
		$this->assertSame( '', $this->sut->get_name() );
	}
}
