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
use Avatar_Privacy\Tools\Images\Editor;


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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

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

		// Partially mock system under test.
		$this->sut = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Override the parts directory as the constructor is never invoked.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id' ) );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::__construct
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
	 */
	public function test_constructor() {
		$editor     = m::mock( Editor::class );
		$transients = m::mock( Site_Transients::class );
		$mock       = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $editor, $transients ] );

		// An attribute of the PNG_Generator superclass.
		$this->assertAttributeSame( $editor, 'images', $mock );
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
	 */
	public function test_build() {
		$seed = 'fake email hash';
		$size = 42;
		$data = 'fake SVG image';

		// Intermediary results.
		$parts       = [
			'foo'  => '/some/path/foo_1.png',
			'mask' => '/some/path/mask_23.png',
			'bar'  => '/some/path/baz_2.png',
		];
		$parts_count = \count( $parts );
		$bg_hue      = 23;
		$wavatar_hue = 42;
		$fake_image  = \imageCreateTrueColor( $size, $size );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, Wavatar::SEED_INDEX['background_hue'], 2, 240 )->andReturn( $bg_hue );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, Wavatar::SEED_INDEX['wavatar_hue'], 2, 240 )->andReturn( $wavatar_hue );

		// Account for transformation of hues into degree format.
		$bg_hue      = $bg_hue / 255 * Wavatar::DEGREE;
		$wavatar_hue = $wavatar_hue / 255 * Wavatar::DEGREE;

		$this->sut->shouldReceive( 'create_image' )->once()->with( 'white' )->andReturn( $fake_image );
		$this->sut->shouldReceive( 'fill' )->once()->with( m::type( 'resource' ), $bg_hue, 94, 20, 1, 1 );

		$this->sut->shouldReceive( 'apply_image' )->times( $parts_count )->with( m::type( 'resource' ), m::type( 'string' ) );
		$this->sut->shouldReceive( 'fill' )->once()->with( m::type( 'resource' ), $wavatar_hue, 94, 66, m::type( 'int' ), m::type( 'int' ) );

		$this->sut->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( 'resource' ), $size )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_failed() {
		$seed = 'fake email hash';
		$size = 42;
		$data = 'fake SVG image';

		// Intermediary results.
		$parts       = [
			'foo'  => '/some/path/foo_1.png',
			'mask' => '/some/path/mask_23.png',
			'bar'  => '/some/path/baz_2.png',
		];
		$parts_count = \count( $parts );
		$bg_hue      = 23;
		$wavatar_hue = 42;
		$fake_image  = \imageCreateTrueColor( $size, $size );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, Wavatar::SEED_INDEX['background_hue'], 2, 240 )->andReturn( $bg_hue );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, Wavatar::SEED_INDEX['wavatar_hue'], 2, 240 )->andReturn( $wavatar_hue );

		// Account for transformation of hues into degree format.
		$bg_hue      = $bg_hue / 255 * Wavatar::DEGREE;
		$wavatar_hue = $wavatar_hue / 255 * Wavatar::DEGREE;

		$this->sut->shouldReceive( 'create_image' )->once()->with( 'white' )->andThrow( \RuntimeException::class );
		$this->sut->shouldReceive( 'fill' )->never();

		$this->sut->shouldReceive( 'apply_image' )->never();
		$this->sut->shouldReceive( 'fill' )->never();

		$this->sut->shouldReceive( 'get_resized_image_data' )->never();

		$this->assertSame( false, $this->sut->build( $seed, $size ) );
	}
}
