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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Tools\Images\Editor;
use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Tools\Images\Image_Stream;

/**
 * Avatar_Privacy\Tools\Images\Editor unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Images\Editor
 * @usesDefaultClass \Avatar_Privacy\Tools\Images\Editor
 *
 * @uses ::__construct
 */
class Editor_Test extends \Avatar_Privacy\Tests\TestCase {

	const HANDLE = 'root/folder/fake_image';

	/**
	 * The system-under-test.
	 *
	 * @var Editor
	 */
	private $sut;

	/**
	 * A mocked Image_Stream implementation.
	 *
	 * @var Image_Stream
	 */
	private $stream;

	/**
	 * The stream URL for testing.
	 *
	 * @var string
	 */
	private $stream_url;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'folder' => [],
			'other'  => [
				'mime' => [
					'type' => [
						'check',
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );

		$this->stream = m::mock( Image_Stream::class )->shouldAllowMockingProtectedMethods();
		$this->stream->shouldReceive( 'get_handle_from_url' )->once()->with( m::type( 'string' ) )->andReturn( self::HANDLE );
		$this->stream->shouldReceive( 'register' )->once()->with( m::type( 'string' ) );

		// Use vfsStream since we are not really registering our wrapper.
		$this->stream_url = vfsStream::url( self::HANDLE );
		$this->sut        = m::mock( Editor::class, [ $this->stream_url, \get_class( $this->stream ) ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$url                 = 'fake://stream/with/path';
		$handle              = 'my_handle';
		$mocked_stream_class = \get_class( $this->stream );

		$mock = m::mock( Editor::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->stream->shouldReceive( 'get_handle_from_url' )->once()->with( $url )->andReturn( $handle );
		$this->stream->shouldReceive( 'register' )->once()->with( 'fake' );

		$mock->__construct( $url, $mocked_stream_class );

		$this->assert_attribute_same( $url, 'stream_url', $mock );
		$this->assert_attribute_same( $mocked_stream_class, 'stream_class', $mock );
		$this->assert_attribute_same( $handle, 'handle', $mock );
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor_invalid_url() {
		$url                 = '://stream/with/path';
		$mocked_stream_class = \get_class( $this->stream );

		$mock = m::mock( Editor::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->expect_exception( \InvalidArgumentException::class );

		$this->stream->shouldReceive( 'get_handle_from_url' )->never();
		$this->stream->shouldReceive( 'register' )->never();

		$mock->__construct( $url, $mocked_stream_class );
	}

	/**
	 * Tests ::create_from_stream.
	 *
	 * @covers ::create_from_stream
	 */
	public function test_create_from_stream() {
		$stream = 'fake://stream';

		$this->sut->shouldReceive( 'get_image_editor' )->once()->with( $stream )->andReturn( m::mock( \WP_Image_Editor::class ) );

		$this->sut->shouldReceive( 'delete_stream' )->once()->with( $stream );

		$this->assertInstanceOf( \WP_Image_Editor::class,  $this->sut->create_from_stream( $stream ) );
	}

	/**
	 * Tests ::create_from_string.
	 *
	 * @covers ::create_from_string
	 */
	public function test_create_from_string() {
		$data = 'fake image data';

		$this->sut->shouldReceive( 'create_from_stream' )->once()->with( $this->stream_url )->andReturn( m::mock( \WP_Image_Editor::class ) );

		$this->assertInstanceOf( \WP_Image_Editor::class,  $this->sut->create_from_string( $data ) );
	}

	/**
	 * Tests ::create_from_image_resource.
	 *
	 * @covers ::create_from_image_resource
	 *
	 * @uses is_gd_image
	 */
	public function test_create_from_image_resource() {
		$resource = \imageCreateTrueColor( 20, 20 );

		$this->sut->shouldReceive( 'create_from_stream' )->once()->with( $this->stream_url )->andReturn( m::mock( \WP_Image_Editor::class ) );

		$this->assertInstanceOf( \WP_Image_Editor::class,  $this->sut->create_from_image_resource( $resource ) );
	}

	/**
	 * Tests ::create_from_image_resource.
	 *
	 * @covers ::create_from_image_resource
	 *
	 * @uses is_gd_image
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_create_from_image_resource_not_a_resource() {
		$not_a_resource = '';

		$errors = m::mock( 'overload:' . \WP_Error::class );
		$errors->shouldReceive( '__construct' )->once()->with( 'invalid_image', m::type( 'string' ) );

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( 'An error.' );

		$this->assertInstanceOf( \WP_Error::class,  $this->sut->create_from_image_resource( $not_a_resource ) );
	}

	/**
	 * Tests ::get_image_data.
	 *
	 * @covers ::get_image_data
	 */
	public function test_get_image_data() {
		$editor = m::mock( \WP_Image_Editor::class );
		$format = Image_File::JPEG_IMAGE;
		$data   = 'fake image data';

		$editor->shouldReceive( 'save' )->once()->with( $this->stream_url . '.jpg', $format )->andReturn( true );

		$this->stream->shouldReceive( 'get_data' )->once()->with( m::type( 'string' ), true )->andReturn( $data );

		$this->assertSame( $data, $this->sut->get_image_data( $editor, $format ) );
	}

	/**
	 * Tests ::get_image_data.
	 *
	 * @covers ::get_image_data
	 */
	public function test_get_image_data_wp_error() {
		$editor = m::mock( \WP_Image_Editor::class );
		$format = 'image/foobar';

		$editor->shouldReceive( 'save' )->never();
		$this->stream->shouldReceive( 'get_data' )->never();

		$this->assertSame( '', $this->sut->get_image_data( $editor, $format ) );
	}

	/**
	 * Tests ::get_image_data.
	 *
	 * @covers ::get_image_data
	 */
	public function test_get_image_data_invalid_format() {
		$editor = m::mock( \WP_Error::class );
		$format = Image_File::JPEG_IMAGE;

		$editor->shouldReceive( 'save' )->never();
		$this->stream->shouldReceive( 'get_data' )->never();

		$this->assertSame( '', $this->sut->get_image_data( $editor, $format ) );
	}

	/**
	 * Tests ::get_image_data.
	 *
	 * @covers ::get_image_data
	 */
	public function test_get_image_data_save_fails() {
		$editor = m::mock( \WP_Image_Editor::class );
		$format = Image_File::JPEG_IMAGE;

		$editor->shouldReceive( 'save' )->once()->with( $this->stream_url . '.jpg', $format )->andReturn( m::mock( \WP_Error::class ) );

		$this->stream->shouldReceive( 'get_data' )->never();

		$this->assertSame( '', $this->sut->get_image_data( $editor, $format ) );
	}

	/**
	 * Tests ::get_resized_image_data.
	 *
	 * @covers ::get_resized_image_data
	 */
	public function test_get_resized_image_data() {
		// Inputs.
		$editor = m::mock( \WP_Image_Editor::class );
		$format = Image_File::JPEG_IMAGE;
		$width  = 64;
		$height = 120;

		// Intermediate data.
		$current_width  = 42;
		$current_height = 43;
		$crop           = [
			// Note these values are not realistic, but unique for testing purposes.
			'x'      => 44,
			'y'      => 45,
			'width'  => 46,
			'height' => 47,
		];

		// Result.
		$data = 'fake image data';

		$editor->shouldReceive( 'get_size' )->once()->andReturn(
			[
				'width'  => $current_width,
				'height' => $current_height,
			]
		);

		$this->sut->shouldReceive( 'get_crop_dimensions' )->once()->with( $current_width, $current_height, $width, $height )->andReturn( $crop );

		$editor->shouldReceive( 'crop' )->once()->with( $crop['x'], $crop['y'], $crop['width'], $crop['height'], $width, $height, false )->andReturn( true );

		$this->sut->shouldReceive( 'get_image_data' )->once()->with( $editor, $format )->andReturn( $data );

		$this->assertSame( $data, $this->sut->get_resized_image_data( $editor, $width, $height, $format ) );
	}

	/**
	 * Tests ::get_resized_image_data.
	 *
	 * @covers ::get_resized_image_data
	 */
	public function test_get_resized_image_data_wp_error() {
		// Inputs.
		$editor = m::mock( \WP_Error::class );
		$format = Image_File::JPEG_IMAGE;
		$width  = 64;
		$height = 120;

		$editor->shouldReceive( 'get_size' )->never();
		$editor->shouldReceive( 'crop' )->never();

		$this->sut->shouldReceive( 'get_image_data' )->never();

		$this->assertSame( '', $this->sut->get_resized_image_data( $editor, $width, $height, $format ) );
	}

	/**
	 * Tests ::get_resized_image_data.
	 *
	 * @covers ::get_resized_image_data
	 */
	public function test_get_resized_image_data_crop_fails() {
		// Inputs.
		$editor = m::mock( \WP_Image_Editor::class );
		$format = Image_File::JPEG_IMAGE;
		$width  = 64;
		$height = 120;

		// Intermediate data.
		$current_width  = 42;
		$current_height = 43;
		$crop           = [
			// Note these values are not realistic, but unique for testing purposes.
			'x'      => 44,
			'y'      => 45,
			'width'  => 46,
			'height' => 47,
		];

		$editor->shouldReceive( 'get_size' )->once()->andReturn(
			[
				'width'  => $current_width,
				'height' => $current_height,
			]
		);

		$this->sut->shouldReceive( 'get_crop_dimensions' )->once()->with( $current_width, $current_height, $width, $height )->andReturn( $crop );

		$editor->shouldReceive( 'crop' )->once()->with( $crop['x'], $crop['y'], $crop['width'], $crop['height'], $width, $height, false )->andReturn( m::mock( \WP_Error::class ) );

		$this->sut->shouldReceive( 'get_image_data' )->never();

		$this->assertSame( '', $this->sut->get_resized_image_data( $editor, $width, $height, $format ) );
	}

	/**
	 * Provides data for testing ::get_crop_dimensions.
	 *
	 * @return array
	 */
	public function provide_get_crop_dimensions_data() {
		return [
			// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			[ 100, 100, 200, 200, [ 'x' => 0, 'y' => 0, 'width' => 100, 'height' => 100 ] ],
			[ 200, 100, 200, 200, [ 'x' => 50, 'y' => 0, 'width' => 100, 'height' => 100 ] ],
			[ 101, 100, 200, 200, [ 'x' => 0, 'y' => 0, 'width' => 100, 'height' => 100 ] ],
			[ 102, 100, 200, 200, [ 'x' => 1, 'y' => 0, 'width' => 100, 'height' => 100 ] ],
			[ 100, 105, 200, 200, [ 'x' => 0, 'y' => 2, 'width' => 100, 'height' => 100 ] ],
			[ 1024, 768, 200, 200, [ 'x' => 128, 'y' => 0, 'width' => 768, 'height' => 768 ] ],
			[ 1024, 768, 1400, 900, [ 'x' => 0, 'y' => 55, 'width' => 1024, 'height' => 658 ] ],
			// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		];
	}

	/**
	 * Tests ::get_crop_dimensions.
	 *
	 * @covers ::get_crop_dimensions
	 *
	 * @dataProvider provide_get_crop_dimensions_data
	 *
	 * @param  int   $orig_w Original image width.
	 * @param  int   $orig_h Original image height.
	 * @param  int   $dest_w Destination image width.
	 * @param  int   $dest_h Destination image height.
	 * @param  array $result Expected result.
	 */
	public function test_get_crop_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, array $result ) {
		$this->assertSame( $result, $this->sut->get_crop_dimensions( $orig_w, $orig_h, $dest_w, $dest_h ) );
	}

	/**
	 * Tests ::get_image_editor.
	 *
	 * @covers ::get_image_editor
	 */
	public function test_get_image_editor() {
		// Inputs.
		$path = '/a/file/path';
		$args = [
			'foo' => 'bar',
		];

		$result = m::mock( \WP_Image_Editor::class );

		Filters\expectAdded( 'wp_image_editors' )->once()->with( [ $this->sut, 'prefer_gd_image_editor' ], 9999 );

		Functions\expect( 'wp_get_image_editor' )->once()->with( $path, $args )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_image_editor( $path, $args ) );

		$this->assertFalse( \has_filter( 'wp_image_editors', [ $this->sut, 'prefer_gd_image_editor' ] ) );
	}

	/**
	 * Tests ::prefer_gd_image_editor.
	 *
	 * @covers ::prefer_gd_image_editor
	 */
	public function test_prefer_gd_image_editor() {
		// Inputs.
		$editors = [
			\WP_Image_Editor_Imagick::class,
			\WP_EWWWIO_Imagick_Editor::class,
			\WP_Image_Editor_GD::class,
		];

		$result = $this->sut->prefer_gd_image_editor( $editors );

		$this->assertSame( \WP_Image_Editor_GD::class, $result[0] );
		$this->assertNotSame( \WP_Image_Editor_GD::class, $result[1] );
		$this->assertNotSame( \WP_Image_Editor_GD::class, $result[2] );
	}

	/**
	 * Tests ::get_mime_type.
	 *
	 * @covers ::get_mime_type
	 */
	public function test_get_mime_type() {
		// Input.
		$stream = vfsStream::url( 'root/other' );

		// Result.
		$mime_type = 'image/foobar';

		// Set up instance state.
		$stream_matcher = m::pattern( '#^' . \preg_quote( $stream, '/[\w/]+$#' ) . '#' );
		$this->set_value( $this->sut, 'stream_url', $stream );

		Functions\expect( 'wp_get_image_mime' )->once()->with( $stream_matcher )->andReturn( $mime_type );
		$this->sut->shouldReceive( 'delete_stream' )->once()->with( $stream_matcher );

		$this->assertSame( $mime_type, $this->sut->get_mime_type( $stream ) );
	}

	/**
	 * Tests ::delete_stream.
	 *
	 * @covers ::delete_stream
	 */
	public function test_delete_stream() {
		$stream = 'avprimg://foo/bar';

		$this->stream->shouldReceive( 'get_handle_from_url' )->once()->with( $stream )->andReturn( 'my_handle' );
		$this->stream->shouldReceive( 'delete_handle' )->once()->with( 'my_handle' );

		$this->assertNull( $this->sut->delete_stream( $stream ) );
	}
}
