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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator;

use Avatar_Privacy\Tools\Images\Editor;


/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Generator
 *
 * @uses ::__construct
 */
class PNG_Generator_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var PNG_Generator
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
				'my_parts_dir' => [
					'somefile.png' => $png_data,
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );

		// Helper mocks.
		$this->editor = m::mock( Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock( PNG_Generator::class, [ vfsStream::url( 'root/plugin/my_parts_dir' ), $this->editor ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$fake_path = 'some/fake/path';
		$editor    = m::mock( Editor::class );
		$mock      = m::mock( PNG_Generator::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $fake_path, $editor ] );

		// An attribute of the PNG_Generator superclass.
		$this->assertAttributeSame( $fake_path, 'parts_dir', $mock );
		$this->assertAttributeSame( $editor, 'images', $mock );
	}

	/**
	 * Tests ::apply_image.
	 *
	 * @covers ::apply_image
	 */
	public function test_apply_image() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imagecreatetruecolor( $width, $height );

		// Make the base image white.
		\imagefill( $base, 0, 0, \imagecolorallocate( $base, 255, 255, 255 ) );

		// The second image.
		$image = 'somefile.png';

		// Store base image data for comparison.
		\ob_start();
		\imagepng( $base );
		$orig_base_data = ob_get_clean();

		// Run the test.
		$this->assertTrue( $this->sut->apply_image( $base, $image, $width, $height ) );

		// Get the new base image data.
		\ob_start();
		\imagepng( $base );
		$new_base_data = ob_get_clean();

		// Check that they are different because of the applied image.
		$this->assertNotSame( $orig_base_data, $new_base_data );

		// Clean up.
		\imagedestroy( $base );
	}

	/**
	 * Tests ::apply_image.
	 *
	 * @covers ::apply_image
	 */
	public function test_apply_image_error() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imagecreatetruecolor( $width, $height );

		// Make the base image white.
		\imagefill( $base, 0, 0, \imagecolorallocate( $base, 255, 255, 255 ) );

		// The second image does not exist.
		$image = 'fakename.png';

		// Store base image data for comparison.
		\ob_start();
		\imagepng( $base );
		$orig_base_data = ob_get_clean();

		// Run the test.
		$this->assertFalse( $this->sut->apply_image( $base, $image, $width, $height ) );

		// Get the new base image data.
		\ob_start();
		\imagepng( $base );
		$new_base_data = ob_get_clean();

		// Check that they are different because of the applied image.
		$this->assertSame( $orig_base_data, $new_base_data );

		// Clean up.
		\imagedestroy( $base );
	}

	/**
	 * Tests ::fill.
	 *
	 * @covers ::fill
	 */
	public function test_fill() {
		// Input.
		$hue        = 345;
		$saturation = 99;
		$lightness  = 10;
		$x          = 23;
		$y          = 42;

		// The image.
		$width    = 200;
		$height   = 100;
		$resource = \imagecreate( $width, $height );

		$this->assertTrue( $this->sut->fill( $resource, $hue, $saturation, $lightness, $x, $y ) );

		// Clean up.
		\imagedestroy( $resource );
	}


	/**
	 * Tests ::fill.
	 *
	 * @covers ::fill
	 */
	public function test_fill_error() {
		// Input.
		$hue        = 0;
		$saturation = 99;
		$lightness  = 10;
		$x          = 23;
		$y          = 42;

		// The image.
		$width    = 200;
		$height   = 100;
		$resource = \imagecreate( $width, $height );

		// Eat up all color slots.
		for ( $i = 0; $i < 256; ++$i ) {
			\imagecolorallocate( $resource, 0, 0, 0 );
		}

		$this->assertFalse( $this->sut->fill( $resource, $hue, $saturation, $lightness, $x, $y ) );

		// Clean up.
		\imagedestroy( $resource );
	}

	/**
	 * Tests ::get_resized_image_data.
	 *
	 * @covers ::get_resized_image_data
	 */
	public function test_get_resized_image_data() {
		$fake_resource = 'should be a resource';
		$size          = 42;
		$result        = 'the image data';

		$this->editor->shouldReceive( 'create_from_image_resource' )->once()->with( $fake_resource )->andReturn( m::mock( \WP_Image_Editor::class ) );
		$this->editor->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( \WP_Image_Editor::class ), $size, $size, 'image/png' )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_resized_image_data( $fake_resource, $size ) );
	}
}
