<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2024 Peter Putzer.
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

use Avatar_Privacy\Tools\Images\Image_File;

use Avatar_Privacy\Exceptions\Upload_Handling_Exception;

/**
 * Avatar_Privacy\Tools\Images\Image_File unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Images\Image_File
 * @usesDefaultClass \Avatar_Privacy\Tools\Images\Image_File
 */
class Image_File_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Image_File
	 */
	private $sut;

	/**
	 * The mocked filesystem.
	 *
	 * @var vfsStreamDirectory
	 */
	private vfsStreamDirectory $root;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
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
			'tmp'       => [],
			'upload'    => [],
			'other'     => [
				'invalid_image.gif' => 'xxx',
				'valid_image.png'   => $png_data,
			],
		];

		// Set up virtual filesystem.
		$this->root = vfsStream::setup( 'root', null, $filesystem );

		$this->sut = m::mock( Image_File::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'validate_image_size' )->once()->with( $file )->andReturn( $file );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_error() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
		];

		$this->sut->shouldReceive( 'validate_image_size' )->once()->with( $file )->andReturn( $file );

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->never();
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_global_upload() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$prep_overrides = [
			'upload_dir' => $upload_dir,
			'foo'        => 'bar',
		];
		$result         = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];
		$main_site_id   = 5;

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->sut->shouldReceive( 'validate_image_size' )->once()->with( $file )->andReturn( $file );

		$this->sut->shouldReceive( 'is_global_upload' )->once()->with( $overrides )->andReturn( true );
		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );
		Functions\expect( 'switch_to_blog' )->once()->with( $main_site_id );
		Functions\expect( 'restore_current_blog' )->once();

		Filters\expectAdded( 'upload_dir' )->once()->with( m::type( 'Closure' ) );
		$this->sut->shouldReceive( 'prepare_overrides' )->once()->with( $overrides )->andReturn( $prep_overrides );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, $prep_overrides )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );
		Filters\expectRemoved( 'upload_dir' )->once()->with( m::type( 'Closure' ) );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_invalid_size() {
		$file           = [ 'foo' => 'bar' ];
		$upload_dir     = '/some/upload/directory';
		$overrides      = [
			'upload_dir' => $upload_dir,
		];
		$result         = [
			'error' => 'Invalid size',
		];

		$this->sut->shouldReceive( 'validate_image_size' )->once()->with( $file )->andReturn( $result );
		$this->sut->shouldReceive( 'is_global_upload' )->never();
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->never();
		$this->sut->shouldReceive( 'prepare_overrides' )->never();
		Functions\expect( 'wp_handle_upload' )->never();
		Functions\expect( 'wp_normalize_path' )->never();
		Filters\expectRemoved( 'upload_dir' )->never();

		$this->assertSame( $result, $this->sut->handle_upload( $file, $overrides ) );
	}

	/**
	 * Tests ::handle_sideload.
	 *
	 * @covers ::handle_sideload
	 */
	public function test_handle_sideload() {
		$image_url  = vfsStream::url( 'root/other/valid_image.png' );
		$temp_file  = vfsStream::url( 'root/tmp/temp_image' );
		$upload_dir = vfsStream::url( 'root/upload/' );

		$overrides = [
			'upload_dir' => $upload_dir,
		];

		$overrides_with_action           = $overrides;
		$overrides_with_action['action'] = 'avatar_privacy_sideload';

		$result = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		Functions\expect( 'wp_tempnam' )->once()->with( $image_url )->andReturn( $temp_file );

		$this->sut->shouldReceive( 'handle_upload' )->once()->with( m::type( 'array' ), $overrides_with_action )->andReturn( $result );

		$this->assertSame( $result, $this->sut->handle_sideload( $image_url, $overrides ) );
	}

	/**
	 * Tests ::handle_sideload.
	 *
	 * @covers ::handle_sideload
	 */
	public function test_handle_sideload_copy_error() {
		$image_url  = vfsStream::url( 'root/other/valid_image.png' );
		$temp_file  = vfsStream::url( 'root/tmp/temp_image' );
		$upload_dir = vfsStream::url( 'root/upload/' );

		$overrides = [
			'upload_dir' => $upload_dir,
		];

		$overrides_with_action           = $overrides;
		$overrides_with_action['action'] = 'avatar_privacy_sideload';

		// Make sure that the temporary file is not writable.
		\touch( $temp_file );
		\chmod( $temp_file, 0444 );

		Functions\expect( 'wp_tempnam' )->once()->with( $image_url )->andReturn( $temp_file );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->sut->shouldReceive( 'handle_upload' )->never();

		// Prevent underlying filesystem error.
		$this->assertNull( @$this->sut->handle_sideload( $image_url, $overrides ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	/**
	 * Tests ::handle_sideload.
	 *
	 * @covers ::handle_sideload
	 */
	public function test_handle_sideload_with_filename_override() {
		$image_url  = vfsStream::url( 'root/other/valid_image.png' );
		$temp_file  = vfsStream::url( 'root/tmp/temp_image' );
		$upload_dir = vfsStream::url( 'root/upload/' );

		$new_filename = 'some-other-name.png';
		$overrides    = [
			'upload_dir' => $upload_dir,
			'filename'   => $new_filename,
		];

		$overrides_with_action           = $overrides;
		$overrides_with_action['action'] = 'avatar_privacy_sideload';

		$result = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		Functions\expect( 'wp_tempnam' )->once()->with( $image_url )->andReturn( $temp_file );

		$this->sut->shouldReceive( 'handle_upload' )->once()->with( m::on( function( $file_data ) use ( $temp_file, $new_filename ) {
			return (
				! empty( $file_data['tmp_name'] ) && $file_data['tmp_name'] === $temp_file &&
				! empty( $file_data['name'] ) && $file_data['name'] === $new_filename
			);
		} ), m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->handle_sideload( $image_url, $overrides ) );
	}


	/**
	 * Tests ::handle_sideload.
	 *
	 * @covers ::handle_sideload
	 *
	 * @uses Avatar_Privacy\Tools\delete_file
	 */
	public function test_handle_sideload_error() {
		$image_url  = vfsStream::url( 'root/other/valid_image.png' );
		$temp_file  = vfsStream::url( 'root/tmp/temp_image' );
		$upload_dir = vfsStream::url( 'root/upload/' );

		$overrides = [
			'upload_dir' => $upload_dir,
		];

		$overrides_with_action           = $overrides;
		$overrides_with_action['action'] = 'avatar_privacy_sideload';

		$error_message = 'some error occured';
		$result        = [
			'bar'   => 'foo',
			'file'  => '/my/path',
			'error' => $error_message,
		];

		Functions\expect( 'wp_tempnam' )->once()->with( $image_url )->andReturn( $temp_file );

		$this->sut->shouldReceive( 'handle_upload' )->once()->with( m::type( 'array' ), $overrides_with_action )->andReturn( $result );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->assertNull( $this->sut->handle_sideload( $image_url, $overrides ) );
		$this->assertFalse( \file_exists( $temp_file ) );
	}

	/**
	 * Provides data for testing ::is_global_upload.
	 *
	 * @return array
	 */
	public function provide_is_global_upload_data() {
		return [
			'No global upload, singlesite' => [ false, false, false ],
			'No global upload, multisite'  => [ false, true, false ],
			'Global upload, singlesite'    => [ true, false, false ],
			'Global upload, singlesite'    => [ true, true, true ],
		];
	}

	/**
	 * Tests ::is_global_upload.
	 *
	 * @covers ::is_global_upload
	 *
	 * @dataProvider provide_is_global_upload_data
	 *
	 * @param  bool $global_upload Whether global uploads are enabled.
	 * @param  bool $multisite     Whether this is a multisite.
	 * @param  bool $result        The expected result.
	 */
	public function test_is_global_upload( $global_upload, $multisite, $result ) {
		$overrides = [
			'global_upload' => $global_upload,
		];

		if ( $global_upload ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( $multisite );
		} else {
			Functions\expect( 'is_multisite' )->never();
		}

		$this->assertSame( $result, $this->sut->is_global_upload( $overrides ) );
	}

	/**
	 * Tests ::prepare_overrides.
	 *
	 * @covers ::prepare_overrides
	 */
	public function test_prepare_overrides() {
		$overrides = [
			'global_upload' => true,
			'foo'           => 'bar',
		];

		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing( function( $overrides, $defaults ) {
			return \array_merge( $defaults, $overrides );
		} );

		$result = $this->sut->prepare_overrides( $overrides );

		$this->assertArrayHasKey( 'global_upload', $result );
		$this->assertArrayHasKey( 'foo', $result );
		$this->assertArrayHasKey( 'mimes', $result );
		$this->assertArrayHasKey( 'action', $result );
		$this->assertArrayHasKey( 'test_form', $result );
	}

	/**
	 * Tests ::validate_image_size.
	 *
	 * @covers ::validate_image_size
	 */
	public function test_validate_image_size_ok() {
		$file = [
			'name'     => 'some_nice_filename',
			'type'     => Image_File::PNG_IMAGE,
			'tmp_name' => vfsStream::url( 'root/other/valid_image.png' ),
			'size'     => 666,
		];

		Functions\when( '__' )->returnArg();

		Filters\expectApplied( 'avatar_privacy_upload_min_width' )->once()->with( 0 );
		Filters\expectApplied( 'avatar_privacy_upload_min_height' )->once()->with( 0 );
		Filters\expectApplied( 'avatar_privacy_upload_max_width' )->once()->with( 2000 );
		Filters\expectApplied( 'avatar_privacy_upload_max_height' )->once()->with( 2000 );

		$result = $this->sut->validate_image_size( $file );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertSame( $file['name'], $result['name'] );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertSame( $file['type'], $result['type'] );
		$this->assertArrayHasKey( 'tmp_name', $result );
		$this->assertSame( $file['tmp_name'], $result['tmp_name'] );
		$this->assertArrayHasKey( 'size', $result );
		$this->assertSame( $file['size'], $result['size'] );

		$this->assertArrayNotHasKey( 'error', $result );
	}

	/**
	 * Tests ::validate_image_size.
	 *
	 * @covers ::validate_image_size
	 */
	public function test_validate_image_size_invalid_size() {
		$file = [
			'name'     => 'some_nice_filename',
			'type'     => Image_File::PNG_IMAGE,
			'tmp_name' => vfsStream::url( 'root/other/invalid_image.gif' ),
			'size'     => 666,
		];

		Functions\when( '__' )->returnArg();

		Filters\expectApplied( 'avatar_privacy_upload_min_width' )->never();
		Filters\expectApplied( 'avatar_privacy_upload_min_height' )->never();
		Filters\expectApplied( 'avatar_privacy_upload_max_width' )->never();
		Filters\expectApplied( 'avatar_privacy_upload_max_height' )->never();

		$result = $this->sut->validate_image_size( $file );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringStartsWith( 'Error reading dimensions of image file', $result['error'] );
	}

	/**
	 * Tests ::validate_image_size.
	 *
	 * @covers ::validate_image_size
	 */
	public function test_validate_image_size_too_small() {
		$file = [
			'name'     => 'some_nice_filename',
			'type'     => Image_File::PNG_IMAGE,
			'tmp_name' => vfsStream::url( 'root/other/valid_image.png' ),
			'size'     => 666,
		];

		Functions\when( '__' )->returnArg();

		Filters\expectApplied( 'avatar_privacy_upload_min_width' )->once()->with( 0 )->andReturn( 999 );
		Filters\expectApplied( 'avatar_privacy_upload_min_height' )->once()->with( 0 )->andReturn( 999 );
		Filters\expectApplied( 'avatar_privacy_upload_max_width' )->once()->with( 2000 );
		Filters\expectApplied( 'avatar_privacy_upload_max_height' )->once()->with( 2000 );

		$result = $this->sut->validate_image_size( $file );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringStartsWith( 'Image dimensions are too small.', $result['error'] );
	}

	/**
	 * Tests ::validate_image_size.
	 *
	 * @covers ::validate_image_size
	 */
	public function test_validate_image_size_too_large() {
		$file = [
			'name'     => 'some_nice_filename',
			'type'     => Image_File::PNG_IMAGE,
			'tmp_name' => vfsStream::url( 'root/other/valid_image.png' ),
			'size'     => 666,
		];

		Functions\when( '__' )->returnArg();

		Filters\expectApplied( 'avatar_privacy_upload_min_width' )->once()->with( 0 );
		Filters\expectApplied( 'avatar_privacy_upload_min_height' )->once()->with( 0 );
		Filters\expectApplied( 'avatar_privacy_upload_max_width' )->once()->with( 2000 )->andReturn( 1 );
		Filters\expectApplied( 'avatar_privacy_upload_max_height' )->once()->with( 2000 )->andReturn( 1 );

		$result = $this->sut->validate_image_size( $file );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringStartsWith( 'Image dimensions are too large.', $result['error'] );
	}
}
