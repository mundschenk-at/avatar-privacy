<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2023 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro;

use Avatar_Privacy\Tools\Number_Generator;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Retro
 *
 * @uses ::__construct
 */
class Retro_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Retro
	 */
	private $sut;

	/**
	 * Alias mock of Colors\RandomColor.
	 *
	 * @var \Colors\RandomColor
	 */
	private $random_color;

	/**
	 * The random number generator.
	 *
	 * @var Number_Generator
	 */
	protected $number_generator;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->random_color     = m::mock( 'alias:' . \Colors\RandomColor::class );
		$this->number_generator = m::mock( Number_Generator::class );

		// Partially mock system under test.
		$this->sut = m::mock( Retro::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invoke_method( $this->sut, '__construct', [
			$this->number_generator,
		] );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$number_generator = m::mock( Number_Generator::class );
		$mock             = m::mock( Retro::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $number_generator ] );

		$this->assert_attribute_same( $number_generator, 'number_generator', $mock );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$seed     = 'fake email hash';
		$seed_md5 = \md5( $seed );
		$size     = 42;
		$data     = 'fake SVG image';

		// Intermediate.
		$bright_color = 'A bright color definition';
		$light_color  = 'A light color definition';
		$fake_bitmap  = [ 'not', 'really', 'a', 'bitmap' ]; // A two-dimensional array of booleans.

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->random_color->shouldReceive( 'one' )->once()->with( [ 'luminosity' => 'bright' ] )->andReturn( $bright_color );
		$this->random_color->shouldReceive( 'one' )->once()->with( [ 'luminosity' => 'light' ] )->andReturn( $light_color );

		$this->sut->shouldReceive( 'get_bitmap' )->once()->with( $seed_md5 )->andReturn( $fake_bitmap );
		$this->sut->shouldReceive( 'generate_svg' )->once()->with( $fake_bitmap, $bright_color, $light_color )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}
}
