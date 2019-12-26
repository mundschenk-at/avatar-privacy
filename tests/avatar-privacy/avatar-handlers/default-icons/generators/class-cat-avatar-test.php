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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Images\Editor;
use Avatar_Privacy\Tools\Images\PNG;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Cat_Avatar
 */
class Cat_Avatar_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Cat_Avatar
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Partially mock system under test.
		$this->sut = m::mock( Cat_Avatar::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
	 */
	public function test_constructor() {
		$editor           = m::mock( Editor::class );
		$png              = m::mock( PNG::class );
		$number_generator = m::mock( Number_Generator::class );
		$transients       = m::mock( Site_Transients::class );
		$mock             = m::mock( Cat_Avatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $editor, $png, $number_generator, $transients ] );

		// An attribute of the PNG_Parts_Generator superclass.
		$this->assert_attribute_same( $editor, 'editor', $mock );
	}

	/**
	 * Tests ::render_avatar.
	 *
	 * @covers ::render_avatar
	 */
	public function test_render_avatar() {
		// Input.
		$parts        = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];
		$parts_number = \count( $parts );
		$background   = \imageCreateTrueColor( 50, 50 );

		$this->sut->shouldReceive( 'create_image' )->once()->with( 'transparent' )->andReturn( $background );

		$this->sut->shouldReceive( 'combine_images' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'string' ) );

		$this->assertSame( $background, $this->sut->render_avatar( $parts, [] ) );
	}
}
