<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2021 Peter Putzer.
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

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Images\Editor;
use Avatar_Privacy\Tools\Images\PNG;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator::__construct
 */
class Monster_ID_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Monster_ID
	 */
	private $sut;

	/**
	 * The Images\PNG mock.
	 *
	 * @var PNG
	 */
	private $png;

	/**
	 * The Number_Generator mock.
	 *
	 * @var Number_Generator
	 */
	private $number_generator;

	/**
	 * The full path of the folder containing the real images.
	 *
	 * @var string
	 */
	private $real_image_path;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$png_data = \base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl' .
			'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr' .
			'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r' .
			'8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg=='
		);

		$filesystem = [
			'plugin' => [
				'public' => [
					'images' => [
						'monster-id'       => [
							'back.png'    => $png_data,
							'body_1.png'  => $png_data,
							'body_2.png'  => $png_data,
							'arms_S8.png' => $png_data,
							'legs_1.png'  => $png_data,
							'mouth_6.png' => $png_data,
						],
						'monster-id-empty' => [],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );

		// Provide access to the real images.
		$this->real_image_path = \dirname( \dirname( \dirname( \dirname( \dirname( __DIR__ ) ) ) ) ) . '/public/images/monster-id';

		// Helper mocks.
		$editor                 = m::mock( Editor::class );
		$this->png              = m::mock( PNG::class )->makePartial();
		$this->number_generator = m::mock( Number_Generator::class );
		$transients             = m::mock( Site_Transients::class );

		// Partially mock system under test.
		$this->sut = m::mock( Monster_ID::class, [ $editor, $this->png, $this->number_generator, $transients ] )->makePartial()->shouldAllowMockingProtectedMethods();

		// Override necessary properties.
		$this->set_value( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id' ) );
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
		$mock             = m::mock( Monster_ID::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invoke_method( $mock, '__construct', [ $editor, $png, $number_generator, $transients ] );

		// An attribute of the PNG_Parts_Generator superclass.
		$this->assert_attribute_same( $editor, 'editor', $mock );
	}

	/**
	 * Tests ::get_additional_arguments.
	 *
	 * @covers ::get_additional_arguments
	 */
	public function test_get_additional_arguments() {
		$seed  = 'fake email hash';
		$size  = 42;
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png', // SAME_COLOR_PARTS.
			'legs'  => 'legs_1.png', // RANDOM_COLOR_PARTS.
			'mouth' => 'mouth_6.png', // SPECIFIC_COLOR_PARTS.
		];

		$this->number_generator->shouldReceive( 'get' )->times( 2 )->with( m::type( 'int' ), m::type( 'int' ) )->andReturn( 8000, 25500 );

		$result = $this->sut->get_additional_arguments( $seed, $size, $parts );

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( 'hue', $result );
		$this->assertArrayHasKey( 'saturation', $result );
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
			'arms'  => 'arms_S8.png', // SAME_COLOR_PARTS.
			'legs'  => 'legs_1.png',  // RANDOM_COLOR_PARTS.
			'mouth' => 'mouth_6.png', // SPECIFIC_COLOR_PARTS.
		];
		$args         = [
			'hue'        => 350,
			'saturation' => 77,
		];
		$parts_number = \count( $parts );
		$background   = \imageCreateTrueColor( 50, 50 );
		$fake_image   = \imageCreateTrueColor( 50, 50 );

		$this->number_generator->shouldReceive( 'get' )->times( 4 )->with( m::type( 'int' ), m::type( 'int' ) )->andReturn( 8008000, 25500, 10000, 606000 );

		$this->png->shouldReceive( 'create_from_file' )->once()->with( m::pattern( '/\bback\.png$/' ) )->andReturn( $background );
		$this->png->shouldReceive( 'create_from_file' )->times( $parts_number )->with( m::type( 'string' ) )->andReturn( $fake_image );

		$this->sut->shouldReceive( 'colorize_image' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'numeric' ), m::type( 'numeric' ), m::type( 'string' ) );
		$this->sut->shouldReceive( 'combine_images' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'resource' ) );

		$this->assertSame( $background, $this->sut->render_avatar( $parts, $args ) );
	}

	/**
	 * Tests ::colorize_image.
	 *
	 * @covers ::colorize_image
	 *
	 * @uses Avatar_Privacy\Tools\Images\PNG::hsl_to_rgb
	 */
	public function test_colorize_image() {
		// Input.
		$hue        = 66;
		$saturation = 70;
		$part       = 'arms_S8.png';

		// The image.
		$resource = \imageCreateFromPNG( "{$this->real_image_path}/{$part}" );

		$result = $this->sut->colorize_image( $resource, $hue, $saturation, $part );

		$this->assert_is_resource( $result );

		// Clean up.
		\imageDestroy( $resource );
	}

	/**
	 * Tests ::colorize_image.
	 *
	 * @covers ::colorize_image
	 *
	 * @uses Avatar_Privacy\Tools\Images\PNG::hsl_to_rgb
	 */
	public function test_colorize_image_no_optimization() {
		// Input.
		$hue        = 66;
		$saturation = 70;
		$part       = 'fake.png';

		// The image.
		$size     = 200;
		$resource = \imageCreate( $size, $size );

		$result = $this->sut->colorize_image( $resource, $hue, $saturation, $part );

		$this->assert_is_resource( $result );

		// Clean up.
		\imageDestroy( $resource );
	}
}
