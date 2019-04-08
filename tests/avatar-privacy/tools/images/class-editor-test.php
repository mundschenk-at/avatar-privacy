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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Tools\Images\Editor;
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
	 */
	protected function setUp() {
		parent::setUp();

		$filesystem = [
			'folder' => [],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );

		$this->stream = m::mock( Image_Stream::class )->shouldAllowMockingProtectedMethods();
		$this->stream->shouldReceive( 'register' )->once()->with( m::type( 'string' ) );

		// Use vfsStream since we are not really registering our wrapper.
		$this->stream_url = vfsStream::url( 'root/folder/fake_image.png' );
		$this->sut        = m::mock( Editor::class, [ $this->stream_url, \get_class( $this->stream ) ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$url                 = 'fake://stream/with/path';
		$mocked_stream_class = \get_class( $this->stream );

		$mock = m::mock( Editor::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->stream->shouldReceive( 'register' )->once()->with( 'fake' );

		$mock->__construct( $url, $mocked_stream_class );

		$this->assertAttributeSame( $url, 'stream_url', $mock );
		$this->assertAttributeSame( $mocked_stream_class, 'stream_class', $mock );
		$this->assertAttributeSame( 'stream/with/path', 'handle', $mock );
	}


	/**
	 * Tests ::create_from_stream.
	 *
	 * @covers ::create_from_stream
	 */
	public function test_create_from_stream() {
		$stream = 'fake://stream';

		$this->sut->shouldReceive( 'get_image_editor' )->once()->with( $stream )->andReturn( m::mock( \WP_Image_Editor::class ) );

		$this->stream->shouldReceive( 'get_handle_from_url' )->once()->with( $stream )->andReturn( 'my_handle' );
		$this->stream->shouldReceive( 'delete_handle' )->once()->with( 'my_handle' );

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
	 */
	public function test_create_from_image_resource() {
		$resource = \imagecreatetruecolor( 20, 20 );

		$this->sut->shouldReceive( 'create_from_stream' )->once()->with( $this->stream_url )->andReturn( m::mock( \WP_Image_Editor::class ) );

		$this->assertInstanceOf( \WP_Image_Editor::class,  $this->sut->create_from_image_resource( $resource ) );
	}

	/**
	 * Tests ::create_from_image_resource.
	 *
	 * @covers ::create_from_image_resource
	 *
	 * @runInSeparateProcess
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
		$format = 'image/jpeg';
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
		$format = 'image/jpeg';

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
		$format = 'image/jpeg';

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
		$format = 'image/jpeg';
		$width  = 64;
		$height = 120;

		// Intermediate data.
		$current_width  = 42;
		$current_height = 43;

		// Result.
		$data = 'fake image data';

		$editor->shouldReceive( 'get_size' )->once()->andReturn(
			[
				'width'  => $current_width,
				'height' => $current_height,
			]
		);
		$editor->shouldReceive( 'crop' )->once()->with( 0, 0, $current_width, $current_height, $width, $height, false )->andReturn( true );

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
		$format = 'image/jpeg';
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
		$format = 'image/jpeg';
		$width  = 64;
		$height = 120;

		// Intermediate data.
		$current_width  = 42;
		$current_height = 43;

		$editor->shouldReceive( 'get_size' )->once()->andReturn(
			[
				'width'  => $current_width,
				'height' => $current_height,
			]
		);
		$editor->shouldReceive( 'crop' )->once()->with( 0, 0, $current_width, $current_height, $width, $height, false )->andReturn( m::mock( \WP_Error::class ) );

		$this->sut->shouldReceive( 'get_image_data' )->never();

		$this->assertSame( '', $this->sut->get_resized_image_data( $editor, $width, $height, $format ) );
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
}
