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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Images\Editor;
use Avatar_Privacy\Tools\Images\PNG;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar
 */
class Wavatar_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Wavatar
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
		$root = vfsStream::setup( 'root', null, $filesystem );

		// Mocked helpers.
		$this->png              = m::mock( PNG::class );
		$this->number_generator = m::mock( Number_Generator::class );

		// Partially mock system under test.
		$this->sut = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Override necessary properties as the constructor is never invoked.
		$this->set_value( $this->sut, 'png', $this->png );
		$this->set_value( $this->sut, 'number_generator', $this->number_generator );
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
		$mock             = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

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
			'body'  => 'fake_part.png',
			'arms'  => 'fake_part.png',
		];

		$this->sut->shouldReceive( 'seed' )->times( 2 )->with( $seed, m::type( 'int' ), 2, 240 )->andReturn( 123, 200 );

		$result = $this->sut->get_additional_arguments( $seed, $size, $parts );

		$this->assertInternalType( 'array', $result );
		$this->assertArrayHasKey( 'background_hue', $result );
		$this->assertArrayHasKey( 'wavatar_hue', $result );
	}

	/**
	 * Tests ::render_avatar.
	 *
	 * @covers ::render_avatar
	 */
	public function test_render_avatar() {
		// Input.
		$parts        = [
			'mask'  => 'mask_2.png',
			'shine' => 'shine_2.png',
			'eyes'  => 'eyes_8.png',
			'mouth' => 'mouth_6.png',
		];
		$args         = [
			'background_hue' => 239,
			'wavatar_hue'    => 110,
		];
		$parts_number = \count( $parts );
		$background   = \imageCreateTrueColor( 50, 50 );

		$this->sut->shouldReceive( 'create_image' )->once()->with( 'white' )->andReturn( $background );
		$this->png->shouldReceive( 'fill_hsl' )->once()->with( $background, $args['background_hue'], m::type( 'int' ), m::type( 'int' ), 1, 1 );

		$this->sut->shouldReceive( 'combine_images' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'string' ) );
		$this->png->shouldReceive( 'fill_hsl' )->once()->with( $background, $args['wavatar_hue'], m::type( 'int' ), m::type( 'int' ), m::type( 'int' ), m::type( 'int' ) );

		$this->assertSame( $background, $this->sut->render_avatar( $parts, $args ) );
	}

	/**
	 * Provides data for testing ::seed.
	 *
	 * @return array
	 */
	public function provide_seed_data() {
		return [
			[ 'd41d8cd98f00b204e9800998ecf8427e', 1, 2, 11, 10 ],
			[ 'd41d8cd98f00b204e9800998ecf8427e', 5, 2, 4, 1 ],
			[ 'd41d8cd98f00b204e9800998ecf8427e', 9, 2, 8, 0 ],
			[ 'd41d8cd98f00b204e9800998ecf8427e', 11, 2, 13, 11 ],
			[ 'd41d8cd98f00b204e9800998ecf8427e', 13, 2, 11, 10 ],
			[ 'd41d8cd98f00b204e9800998ecf8427e', 15, 2, 19, 2 ],
			[ '00000000000000000000000000000000', 1, 2, 11, 0 ],
			[ 'ffffffffffffffffffffffffffffffff', 1, 2, 11, 2 ],
			[ 'ffffffffffffffffffffffffffffffff', 5, 2, 4, 3 ],
			[ 'ffffffffffffffffffffffffffffffff', 9, 2, 8, 7 ],
			[ 'ffffffffffffffffffffffffffffffff', 11, 2, 13, 8 ],
			[ 'ffffffffffffffffffffffffffffffff', 13, 2, 11, 2 ],
			[ 'ffffffffffffffffffffffffffffffff', 15, 2, 19, 8 ],
		];
	}

	/**
	 * Tests ::seed.
	 *
	 * @covers ::seed
	 *
	 * @dataProvider provide_seed_data
	 *
	 * @param  string $seed   The seed (hexadecimal hash).
	 * @param  int    $index  The index to use.
	 * @param  int    $length The number of bytes.
	 * @param  int    $modulo The maximum value of the result.
	 * @param  int    $result Expected result.
	 */
	public function test_seed( $seed, $index, $length, $modulo, $result ) {
		$this->assertSame( $result, $this->sut->seed( $seed, $index, $length, $modulo, $result ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 *
	 * @uses \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::build
	 *
	 * @return Wavatar The system under test.
	 */
	public function test_build() {
		// Input.
		$seed = 'a3cca2b2aa1e3b5b3b5aad99a8529074';
		$size = 42;

		// Intermediate results.
		$data  = 'fake image';
		$parts = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png',
			'legs'  => 'legs_1.png',
			'mouth' => 'mouth_6.png',
		];
		$args  = [
			'fake' => 'args',
			'we'   => 'do not actually care for',
		];

		$this->number_generator->shouldReceive( 'seed' )->once()->with( $seed );
		$this->number_generator->shouldReceive( 'reset' )->once();

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->andReturn( $parts );
		$this->sut->shouldReceive( 'get_additional_arguments' )->once()->with( $seed, $size, $parts )->andReturn( $args );
		$this->sut->shouldReceive( 'get_avatar' )->once()->with( $size, $parts, $args )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );

		$this->assert_attribute_same( $seed, 'current_seed', $this->sut );

		return $this->sut;
	}

	/**
	 * Tests ::get_random_part_index.
	 *
	 * @covers ::get_random_part_index
	 *
	 * @depends test_build
	 *
	 * @param  Wavatar $sut The system under test.
	 */
	public function test_get_random_part_index( Wavatar $sut ) {
		$count = 731;
		$type  = 'mask';

		$result = 60;

		$sut->shouldReceive( 'seed' )->once()->with( m::type( 'string' ), m::type( 'int' ), 2, $count )->andReturn( $result );

		$this->assertSame( $result, $sut->get_random_part_index( $type, $count ) );
	}
}
