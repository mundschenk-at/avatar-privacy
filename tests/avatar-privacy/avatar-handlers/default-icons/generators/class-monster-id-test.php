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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images\Editor;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Monster_ID
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::__construct
 */
class Monster_ID_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Monster_ID
	 */
	private $sut;

	/**
	 * The full path of the folder containing the real images.
	 *
	 * @var string
	 */
	private $real_image_path;

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

		// Provide access to the real images.
		$this->real_image_path = \dirname( \dirname( \dirname( \dirname( \dirname( __DIR__ ) ) ) ) ) . '/public/images/monster-id';

		// Helper mocks.
		$editor     = m::mock( Editor::class );
		$transients = m::mock( Site_Transients::class );

		// Partially mock system under test.
		$this->sut = m::mock( Monster_ID::class, [ $editor, $transients ] )->makePartial()->shouldAllowMockingProtectedMethods();

		// Override the parts directory.
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
		$mock       = m::mock( Monster_ID::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $editor, $transients ] );

		// An attribute of the PNG_Generator superclass.
		$this->assertAttributeSame( $editor, 'images', $mock );
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
		$parts        = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png', // SAME_COLOR_PARTS.
			'legs'  => 'legs_1.png', // RANDOM_COLOR_PARTS.
			'mouth' => 'mouth_6.png', // SPECIFIC_COLOR_PARTS.
		];
		$parts_number = \count( $parts );
		$fake_image   = \imageCreateTrueColor( $size, $size );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		$this->sut->shouldReceive( 'create_image_from_file' )->once()->with( vfsStream::url( 'root/plugin/public/images/monster-id/back.png' ) )->andReturn( $fake_image );
		$this->sut->shouldReceive( 'create_image_from_file' )->times( $parts_number )->with( m::type( 'string' ) )->andReturn( $fake_image );

		$this->sut->shouldReceive( 'colorize_image' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'numeric' ), m::type( 'numeric' ), m::type( 'string' ) );
		$this->sut->shouldReceive( 'apply_image' )->times( $parts_number )->with( m::type( 'resource' ), m::type( 'resource' ) );

		$this->sut->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( 'resource' ), $size )->andReturn( $data );

		$this->assertSame( $data, $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_missing_background() {
		$seed = 'fake email hash';
		$size = 42;

		// Intermediate results.
		$parts        = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png', // SAME_COLOR_PARTS.
			'legs'  => 'legs_1.png', // RANDOM_COLOR_PARTS.
			'mouth' => 'mouth_6.png', // SPECIFIC_COLOR_PARTS.
		];
		$parts_number = \count( $parts );

		// Delete the background file.
		\unlink( vfsStream::url( 'root/plugin/public/images/monster-id/back.png' ) );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		$this->sut->shouldReceive( 'create_image_from_file' )->once()->with( vfsStream::url( 'root/plugin/public/images/monster-id/back.png' ) )->andThrow( \RuntimeException::class );

		$this->sut->shouldReceive( 'colorize_image' )->never();
		$this->sut->shouldReceive( 'apply_image' )->never();
		$this->sut->shouldReceive( 'get_resized_image_data' )->never();

		$this->assertFalse( $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::build.
	 *
	 * @covers ::build
	 */
	public function test_build_missing_part() {
		$seed = 'fake email hash';
		$size = 42;

		// Intermediate results.
		$parts        = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_S8.png', // SAME_COLOR_PARTS.
			'legs'  => 'legs_1.png', // RANDOM_COLOR_PARTS.
			'mouth' => 'mouth_6.png', // SPECIFIC_COLOR_PARTS.
		];
		$parts_number = \count( $parts );
		$fake_image   = \imageCreateTrueColor( $size, $size );

		// Delete body part.
		\unlink( vfsStream::url( 'root/plugin/public/images/monster-id/mouth_6.png' ) );

		$this->sut->shouldReceive( 'get_randomized_parts' )->once()->with( m::type( 'callable' ) )->andReturn( $parts );

		$this->sut->shouldReceive( 'create_image_from_file' )->once()->with( vfsStream::url( 'root/plugin/public/images/monster-id/back.png' ) )->andReturn( $fake_image );

		$this->sut->shouldReceive( 'create_image_from_file' )->once()->with( vfsStream::url( 'root/plugin/public/images/monster-id/mouth_6.png' ) )->andThrow( \RuntimeException::class );
		$this->sut->shouldReceive( 'create_image_from_file' )->times( $parts_number - 1 )->with( m::type( 'string' ) )->andReturn( $fake_image, $fake_image, $fake_image, false );

		$this->sut->shouldReceive( 'colorize_image' )->times( $parts_number - 1 )->with( m::type( 'resource' ), m::type( 'numeric' ), m::type( 'numeric' ), m::type( 'string' ) );
		$this->sut->shouldReceive( 'apply_image' )->times( $parts_number - 1 )->with( m::type( 'resource' ), m::type( 'resource' ) );

		$this->sut->shouldReceive( 'get_resized_image_data' )->never();

		$this->assertFalse( $this->sut->build( $seed, $size ) );
	}

	/**
	 * Tests ::colorize_image.
	 *
	 * @covers ::colorize_image
	 *
	 * @uses Scriptura\Color\Helpers\HSLtoRGB
	 */
	public function test_colorize_image() {
		// Input.
		$hue        = 66;
		$saturation = 70;
		$part       = 'arms_S8.png';

		// The image.
		$resource = \imageCreateFromPNG( "{$this->real_image_path}/{$part}" );

		$result = $this->sut->colorize_image( $resource, $hue, $saturation, $part );

		$this->assertInternalType( 'resource', $result );

		// Clean up.
		\imageDestroy( $resource );
	}

	/**
	 * Tests ::colorize_image.
	 *
	 * @covers ::colorize_image
	 *
	 * @uses Scriptura\Color\Helpers\HSLtoRGB
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

		$this->assertInternalType( 'resource', $result );

		// Clean up.
		\imageDestroy( $resource );
	}
}
