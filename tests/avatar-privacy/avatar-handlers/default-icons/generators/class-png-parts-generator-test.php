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

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Images\Editor;
use Avatar_Privacy\Tools\Images\PNG;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator::__construct
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
	 * The site transients handler mock.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

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
		$this->editor           = m::mock( Editor::class );
		$this->png              = m::mock( PNG::class );
		$this->number_generator = m::mock( Number_Generator::class );
		$this->site_transients  = m::mock( Site_Transients::class );

		// Partially mock system under test.
		$this->sut = m::mock( PNG_Parts_Generator::class, [
			vfsStream::url( 'root/plugin/my_parts_dir' ),
			[ 'foo', 'bar' ],
			$this->size,
			$this->editor,
			$this->png,
			$this->number_generator,
			$this->site_transients,
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
		$png        = m::mock( PNG::class );
		$numbers    = m::mock( Number_Generator::class );
		$transients = m::mock( Site_Transients::class );
		$mock       = m::mock( PNG_Parts_Generator::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod(
			$mock,
			'__construct',
			[ $fake_path, $part_types, $size, $editor, $png, $numbers, $transients ]
		);

		$this->assertAttributeSame( $size, 'size', $mock );
		$this->assertAttributeSame( $editor, 'editor', $mock );
		$this->assertAttributeSame( $png, 'png', $mock );
	}

	/**
	 * Tests ::get_avatar.
	 *
	 * @covers ::get_avatar
	 */
	public function test_get_avatar() {
		$parts = [
			'some'  => 'fake',
			'parts' => 'foobar',
		];
		$args  = [
			'foo' => 'bar',
		];
		$size  = 42;

		// Generated image.
		$avatar = 'fake resource';
		$image  = 'fake image data';

		$this->sut->shouldReceive( 'render_avatar' )->once()->with( $parts, $args )->andReturn( $avatar );
		$this->sut->shouldReceive( 'get_resized_image_data' )->once()->with( $avatar, $size )->andReturn( $image );

		$this->assertSame( $image, $this->sut->get_avatar( $size, $parts, $args ) );
	}

	/**
	 * Tests ::read_parts_from_filesystem.
	 *
	 * @covers ::read_parts_from_filesystem
	 */
	public function test_read_parts_from_filesystem() {
		// Input data.
		$parts = \array_fill_keys( [ 'body', 'arms', 'legs', 'mouth' ], [] );

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

		// Run test.
		$this->assertSame( $result, $this->sut->read_parts_from_filesystem( $parts ) );
	}

	/**
	 * Tests ::read_parts_from_filesystem.
	 *
	 * @covers ::read_parts_from_filesystem
	 */
	public function test_read_parts_from_filesystem_incorrect_parts_dir() {
		// Input data.
		$parts = \array_fill_keys( [ 'body', 'arms', 'legs', 'mouth' ], [] );

		// Override necessary properties.
		$this->setValue( $this->sut, 'parts_dir', vfsStream::url( 'root/plugin/public/images/monster-id-empty' ) );

		// Run test.
		$this->assertSame( $parts, $this->sut->read_parts_from_filesystem( $parts ) );
	}

	/**
	 * Tests ::sort_parts.
	 *
	 * @covers ::sort_parts
	 */
	public function test_sort_parts() {
		// Input data.
		$parts = [
			'body'  => [
				'body_1.png',
				'body_3.png',
				'body_2.png',
			],
			'arms'  => [
				'arms_S1.png',
				'arms_S10.png',
				'arms_S2.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		// Expected result.
		$result = [
			'body'  => [
				'body_1.png',
				'body_2.png',
				'body_3.png',
			],
			'arms'  => [
				'arms_S1.png',
				'arms_S2.png',
				'arms_S10.png',
			],
			'legs'  => [
				'legs_1.png',
			],
			'mouth' => [
				'mouth_6.png',
			],
		];

		// Run test.
		$this->assertSame( $result, $this->sut->sort_parts( $parts ) );
	}

	/**
	 * Tests ::create_image.
	 *
	 * @covers ::create_image
	 */
	public function test_create_image() {
		$type  = 'white';
		$image = 'fake resource';

		$this->png->shouldReceive( 'create' )->once()->with( $type, $this->size, $this->size )->andReturn( $image );

		$this->assertSame( $image, $this->sut->create_image( $type ) );
	}

	/**
	 * Tests ::combine_images.
	 *
	 * @covers ::combine_images
	 */
	public function test_combine_images() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imageCreateTrueColor( $width, $height );

		// The second image.
		$image = \imageCreateFromPNG( vfsStream::url( 'root/plugin/my_parts_dir/somefile.png' ) );

		$this->png->shouldReceive( 'combine' )->once()->with( $base, $image, $this->size, $this->size );

		$this->assertNull( $this->sut->combine_images( $base, $image ) );

		// Clean up.
		\imageDestroy( $base );
		\imageDestroy( $image );
	}

	/**
	 * Tests ::combine_images.
	 *
	 * @covers ::combine_images
	 */
	public function test_combine_images_with_filename() {
		// The base image.
		$width  = 200;
		$height = 100;
		$base   = \imageCreateTrueColor( $width, $height );

		// The second image.
		$file  = 'filename.png';
		$image = \imageCreateFromPNG( vfsStream::url( 'root/plugin/my_parts_dir/somefile.png' ) );

		$this->png->shouldReceive( 'create_from_file' )->once()->with( m::type( 'string' ) )->andReturn( $image );
		$this->png->shouldReceive( 'combine' )->once()->with( $base, $image, $this->size, $this->size );

		$this->assertNull( $this->sut->combine_images( $base, $file ) );

		// Clean up.
		\imageDestroy( $base );
		\imageDestroy( $image );
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

		$this->sut->shouldReceive( 'get_parts' )->once()->andReturn( $parts );

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

		$this->sut->shouldReceive( 'get_parts' )->once()->andReturn( $parts );

		$this->assertSame( $expected, $this->sut->get_parts_dimensions_as_text() );
	}
}
