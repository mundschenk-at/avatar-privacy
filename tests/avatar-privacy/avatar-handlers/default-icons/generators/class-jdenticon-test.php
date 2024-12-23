<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Jdenticon;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Jdenticon unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Jdenticon
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Jdenticon
 *
 * @uses ::__construct
 */
class Jdenticon_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Jdenticon&m\MockInterface
	 */
	private $sut;

	/**
	 * The identicon mock.
	 *
	 * @var \Jdenticon\Identicon&m\MockInterface
	 */
	private $identicon;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->identicon = m::mock( \Jdenticon\Identicon::class );

		// Partially mock system under test.
		$this->sut = m::mock( Jdenticon::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invoke_method( $this->sut, '__construct', [ $this->identicon ] );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$identicon = m::mock( \Jdenticon\Identicon::class );
		$mock      = m::mock( Jdenticon::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $identicon ] );

		$this->assert_attribute_same( $identicon, 'identicon', $mock );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$seed = 'fake email hash';
		$size = 42;
		$data = 'fake SVG image';

		$this->identicon->shouldReceive( 'setHash' )->once()->with( $seed );
		$this->identicon->shouldReceive( 'setSize' )->once()->with( $size );
		$this->identicon->shouldReceive( 'getImageData' )->once()->with( 'svg' )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}
}
