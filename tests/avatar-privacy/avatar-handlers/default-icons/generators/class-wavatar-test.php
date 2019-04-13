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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator;
use Avatar_Privacy\Tools\Images\Editor;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Wavatar
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::__construct
 */
class Wavatar_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Wavatar
	 */
	private $sut;

	/**
	 * The Images\Editor mock.
	 *
	 * @var Editor
	 */
	private $editor;

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

		// Helper mocks.
		$this->editor = m::mock( Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		// Manually invoke the constructor as it is protected.
		$this->invokeMethod( $this->sut, '__construct', [ $this->editor ] );

		// Override the parts directory.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id' ) );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$editor = m::mock( Editor::class );
		$mock   = m::mock( Wavatar::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $editor ] );

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

		// Intermediate results.
		$face      = 0;
		$bg_color  = 1;
		$fade      = 2;
		$wav_color = 3;
		$brow      = 4;
		$eyes      = 5;
		$pupil     = 6;
		$mouth     = 7;

		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 1, 2, Wavatar::WAVATAR_FACES )->andReturn( $face );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 3, 2, 240 )->andReturn( $bg_color );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 5, 2, Wavatar::WAVATAR_BACKGROUNDS )->andReturn( $fade );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 7, 2, 240 )->andReturn( $wav_color );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 9, 2, Wavatar::WAVATAR_BROWS )->andReturn( $brow );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 11, 2, Wavatar::WAVATAR_EYES )->andReturn( $eyes );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 13, 2, Wavatar::WAVATAR_PUPILS )->andReturn( $pupil );
		$this->sut->shouldReceive( 'seed' )->once()->with( $seed, 15, 2, Wavatar::WAVATAR_MOUTHS )->andReturn( $mouth );

		$this->sut->shouldReceive( 'fill' )->once()->with( m::type( 'resource' ), m::type( 'numeric' ), 94, 20, 1, 1 );

		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'fade' . ( $fade + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );
		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'mask' . ( $face + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );

		$this->sut->shouldReceive( 'fill' )->once()->with( m::type( 'resource' ), m::type( 'numeric' ), 94, 66, (int) ( Wavatar::SIZE / 2 ), (int) ( Wavatar::SIZE / 2 ) );

		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'shine' . ( $face + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );
		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'brow' . ( $brow + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );
		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'eyes' . ( $eyes + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );
		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'pupils' . ( $pupil + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );
		$this->sut->shouldReceive( 'apply_image' )->once()->with( m::type( 'resource' ), 'mouth' . ( $mouth + 1 ) . '.png', Wavatar::SIZE, Wavatar::SIZE );

		$this->sut->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( 'resource' ), $size )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}
}
