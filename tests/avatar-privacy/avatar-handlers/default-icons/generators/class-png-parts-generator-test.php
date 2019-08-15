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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator;

use Avatar_Privacy\Tools\Images\Editor;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::__construct
 */
class PNG_Parts_Generator_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var PNG_Parts_Generator
	 */
	private $sut;

	/**
	 * The Images\Editor mock.
	 *
	 * @var Editor
	 */
	private $editor;

	/**
	 * The full path of the folder containing the real images.
	 *
	 * @var string
	 */
	private $real_image_path;

	/**
	 * The set image size.
	 *
	 * @var int
	 */
	private $size;

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
				'my_parts_dir' => [
					'somefile.png' => $png_data,
				],
				'public'       => [
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

		// Image size (width/height).
		$this->size = 300;

		// Helper mocks.
		$this->editor = m::mock( Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock( PNG_Parts_Generator::class, [
			vfsStream::url( 'root/plugin/my_parts_dir' ),
			[ 'foo', 'bar' ],
			$this->size,
			$this->editor,
		] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$fake_path  = 'some/fake/path';
		$part_types = [ 'foo', 'bar', 'baz' ];
		$size       = 300;
		$editor     = m::mock( Editor::class );
		$mock       = m::mock( PNG_Parts_Generator::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $fake_path, $part_types, $size, $editor ] );

		// An attribute of the PNG_Parts_Generator superclass.
		$this->assertAttributeSame( $fake_path, 'parts_dir', $mock );
		$this->assertAttributeSame( $part_types, 'part_types', $mock );
		$this->assertAttributeSame( $size, 'size', $mock );
	}


	/**
	 * Tests ::get_randomized_parts.
	 *
	 * @covers ::get_randomized_parts
	 */
	public function test_get_randomized_parts() {
		// Input data.
		$randomize  = 'mt_rand';
		$part_types = [
			'body',
			'arms',
			'legs',
			'mouth',
		];

		$found_parts = [
			'body'  => [ 'foo', 'bar' ],
			'arms'  => [],
			'legs'  => [],
			'mouth' => [ 'baz', 'foobar', 'barfoo' ],
		];

		// Expected result.
		$randomized_parts = [
			'body'  => [ 'foo' ],
			'arms'  => [],
			'legs'  => [],
			'mouth' => [ 'baz' ],
		];

		// Override the parts directory.
		$this->setValue( $this->sut, 'part_types', $part_types );

		$this->sut->shouldReceive( 'locate_parts' )->once()->andReturn( $found_parts );
		$this->sut->shouldReceive( 'randomize_parts' )->once()->with( $found_parts, $randomize )->andReturn( $randomized_parts );

		// Run test.
		$this->assertSame( $randomized_parts, $this->sut->get_randomized_parts( $randomize ) );
	}

	/**
	 * Tests ::create_image.
	 *
	 * @covers ::create_image
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::create_image
	 */
	public function test_create_image_default_size() {
		$image = $this->sut->create_image( 'white' );

		$this->assertInternalType( 'resource', $image );
		$this->assertSame( $this->size, \imageSX( $image ) );
		$this->assertSame( $this->size, \imageSY( $image ) );
		$this->assertSame(
			[
				'red'   => 255,
				'green' => 255,
				'blue'  => 255,
				'alpha' => 0,
			],
			\imageColorsForIndex( $image, \imageColorAt( $image, 1, 1 ) )
		);

		// Clean up.
		\imageDestroy( $image );
	}

	/**
	 * Tests ::apply_image.
	 *
	 * @covers ::apply_image
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::apply_image
	 */
	public function test_apply_image() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imageCreateTrueColor( $width, $height );

		// Make the base image white.
		\imageFill( $base, 0, 0, \imageColorAllocate( $base, 255, 255, 255 ) );

		// The second image.
		$image = 'somefile.png';

		// Store base image data for comparison.
		\ob_start();
		\imagePNG( $base );
		$orig_base_data = \ob_get_clean();

		// Run the test.
		$this->assertNull( $this->sut->apply_image( $base, $image, $width, $height ) );

		// Get the new base image data.
		\ob_start();
		\imagePNG( $base );
		$new_base_data = \ob_get_clean();

		// Check that they are different because of the applied image.
		$this->assertNotSame( $orig_base_data, $new_base_data );

		// Clean up.
		\imageDestroy( $base );
	}

	/**
	 * Tests ::apply_image.
	 *
	 * @covers ::apply_image
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::apply_image
	 */
	public function test_apply_image_no_size() {
		// The base image.
		$base = \imageCreateTrueColor( $this->size, $this->size );

		// Make the base image white.
		\imageFill( $base, 0, 0, \imageColorAllocate( $base, 255, 255, 255 ) );

		// The second image.
		$image = \imageCreateFromPNG( vfsStream::url( 'root/plugin/my_parts_dir/somefile.png' ) );

		// Store base image data for comparison.
		\ob_start();
		\imagePNG( $base );
		$orig_base_data = ob_get_clean();

		// Run the test.
		$this->assertNull( $this->sut->apply_image( $base, $image ) );

		// Get the new base image data.
		\ob_start();
		\imagePNG( $base );
		$new_base_data = ob_get_clean();

		// Check that they are different because of the applied image.
		$this->assertNotSame( $orig_base_data, $new_base_data );

		// Clean up.
		\imageDestroy( $base );
	}

	/**
	 * Tests ::apply_image.
	 *
	 * @covers ::apply_image
	 *
	 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator::apply_image
	 */
	public function test_apply_image_error() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imageCreateTrueColor( $width, $height );

		// Make the base image white.
		\imageFill( $base, 0, 0, \imageColorAllocate( $base, 255, 255, 255 ) );

		// The second image does not exist.
		$image = 'fakename.png';

		// Store base image data for comparison.
		\ob_start();
		\imagePNG( $base );
		$orig_base_data = ob_get_clean();

		// Expect failure.
		$this->expectException( \RuntimeException::class );

		// Run the test.
		$this->sut->apply_image( $base, $image, $width, $height );

		// Get the new base image data.
		\ob_start();
		\imagePNG( $base );
		$new_base_data = ob_get_clean();

		// Check that they are different because of the applied image.
		$this->assertSame( $orig_base_data, $new_base_data );

		// Clean up.
		\imageDestroy( $base );
	}

	/**
	 * Tests ::locate_parts.
	 *
	 * @covers ::locate_parts
	 */
	public function test_locate_parts() {
		// Input data.
		$parts = [
			'body',
			'arms',
			'legs',
			'mouth',
		];

		// Expected result.
		$result = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_S8.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		// Override necessary properties.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id' ) );
		$this->setValue( $this->sut, 'part_types', $parts );

		// Run test.
		$this->assertSame( $result, $this->sut->locate_parts() );
	}

	/**
	 * Tests ::locate_parts.
	 *
	 * @covers ::locate_parts
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Could not find parts images
	 */
	public function test_locate_parts_incorrect_parts_dir() {
		// Input data.
		$parts = [
			'body',
			'arms',
			'legs',
			'mouth',
		];

		// Expected result.
		$result = [];

		// Override necessary properties.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id-empty' ) );
		$this->setValue( $this->sut, 'part_types', $parts );

		// Run test.
		$this->assertSame( $result, $this->sut->locate_parts() );
	}
	/**
	 * Tests ::randomize_parts.
	 *
	 * @covers ::randomize_parts
	 */
	public function test_randomize_parts() {
		// Input data.
		$parts     = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_1.png',
				'arms_2.png',
				'arms_3.png',
				'arms_4.png',
				'arms_5.png',
			],
			'mouth' => [
				'mouth_1.png',
				'mouth_2.png',
				'mouth_3.png',
			],
		];
		$randomize = 'my_randomize';

		// Expected result.
		$result = [
			'body'  => 'body_2.png',
			'arms'  => 'arms_4.png',
			'mouth' => 'mouth_3.png',
		];

		Functions\expect( $randomize )->times( \count( $parts ) )->with( 0, m::type( 'int' ), m::type( 'string' ) )->andReturn( 1, 3, 2 );

		// Run test.
		$this->assertSame( $result, $this->sut->randomize_parts( $parts, $randomize ) );
	}

	/**
	 * Tests ::get_parts_dimensions.
	 *
	 * @covers ::get_parts_dimensions
	 */
	public function test_get_parts_dimensions() {
		// Input data.
		$part_types = [
			'body',
			'arms',
		];

		// Intermediate results.
		$parts = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_FOOBAR.png',  // Does not exist and will be ignored.
				'arms_S8.png',
			],
		];

		// Expected result.
		$expected = [
			'body_1.png'  => [
				[ 22, 99 ],
				[ 17, 90 ],
			],
			'body_2.png'  => [
				[ 14, 104 ],
				[ 16, 89 ],
			],
			'arms_S8.png' => [
				[ 2, 119 ],
				[ 18, 98 ],
			],
		];

		// Override the parts directory and types.
		$this->setValue( $this->sut, 'parts_dir', $this->real_image_path );
		$this->setValue( $this->sut, 'part_types', $part_types );

		$this->sut->shouldReceive( 'locate_parts' )->once()->andReturn( $parts );

		$result = $this->sut->get_parts_dimensions();
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests ::get_parts_dimensions_as_text.
	 *
	 * @covers ::get_parts_dimensions_as_text
	 *
	 * @uses ::get_parts_dimensions
	 */
	public function test_get_parts_dimensions_as_text() {
		// Input data.
		$part_types = [
			'body',
			'arms',
		];

		// Intermediate results.
		$parts = [
			'body'  => [
				'body_1.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_FOOBAR.png', // Does not exist and will be ignored.
				'arms_S8.png',
			],
		];

		// Expected result.
		$expected = "'body_1.png' => [ [ 22, 99 ], [ 17, 90 ] ],\n'body_2.png' => [ [ 14, 104 ], [ 16, 89 ] ],\n'arms_S8.png' => [ [ 2, 119 ], [ 18, 98 ] ],\n";

		// Override the parts directory and types.
		$this->setValue( $this->sut, 'parts_dir', $this->real_image_path );
		$this->setValue( $this->sut, 'part_types', $part_types );

		$this->sut->shouldReceive( 'locate_parts' )->once()->andReturn( $parts );

		$this->assertSame( $expected, $this->sut->get_parts_dimensions_as_text() );
	}
}
