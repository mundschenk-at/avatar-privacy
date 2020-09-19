<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Upload_Handlers;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;


/**
 * Avatar_Privacy\Upload_Handlers\Upload_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Upload_Handlers\Upload_Handler
 * @usesDefaultClass \Avatar_Privacy\Upload_Handlers\Upload_Handler
 *
 * @uses ::__construct
 */
class Upload_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Upload_Handler
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin'    => [
				'public' => [
					'partials' => [
						'comments' => [
							'use-gravatar.php' => 'USE_GRAVATAR',
						],
					],
				],
			],
			'uploads'   => [
				'some.png'   => '',
				'some_1.png' => '',
				'some_2.png' => '',
				'some.gif'   => '',
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Mock required helpers.
		$this->file_cache = m::mock( Filesystem_Cache::class );

		$this->sut = m::mock( Upload_Handler::class, [ 'uploads', $this->file_cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Upload_Handler::class )->makePartial();

		$mock->__construct( 'uploads', $this->file_cache );

		$this->assert_attribute_same( $this->file_cache, 'file_cache', $mock );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_get_unique_filename_data() {
		return [
			[ 'some.png', '.png', 'some_3.png' ],
			[ 'some.gif', '.gif', 'some_1.gif' ],
			[ 'other.png', '.png', 'other.png' ],
		];
	}

	/**
	 * Tests ::get_unique_filename.
	 *
	 * @covers ::get_unique_filename
	 *
	 * @dataProvider provide_get_unique_filename_data
	 *
	 * @param string $filename  The proposed filename.
	 * @param string $extension The file extension (including leading dot).
	 * @param string $result    The resulting filename.
	 */
	public function test_get_unique_filename( $filename, $extension, $result ) {
		$this->assertSame( $result, $this->sut->get_unique_filename( vfsStream::url( 'root/uploads' ), $filename, $extension ) );
	}

	/**
	 * Tests ::maybe_save_data (success).
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ] = $nonce_value;

		// Intermediary data.
		$file_slice    = [
			'name'   => 'foobar',
			'foobaz' => 'barfoo',
		];
		$upload_result = [
			'file' => '/some/path/image.png',
			'type' => 'image/png',
		];

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( true );

		$this->sut->shouldReceive( 'get_file_slice' )->once()->with( $args )->andReturn( $file_slice );
		$this->sut->shouldReceive( 'upload' )->once()->with( $file_slice, $args )->andReturn( $upload_result );
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->once()->with( $upload_result, $args );
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data (doing it wrong).
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_doing_it_wrong() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ] = $nonce_value;

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), 'Required arguments missing', 'Avatar Privacy 2.4.0' );
		Functions\expect( 'sanitize_key' )->never();
		Functions\expect( 'wp_verify_nonce' )->never();

		$this->sut->shouldReceive( 'get_file_slice' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data (upload error).
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_upload_error() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ] = $nonce_value;

		// Intermediary data.
		$file_slice    = [
			'name'   => 'foobar',
			'foobaz' => 'barfoo',
		];
		$upload_result = [
			'error' => 'Invalid file type.',
		];

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( true );

		$this->sut->shouldReceive( 'get_file_slice' )->once()->with( $args )->andReturn( $file_slice );
		$this->sut->shouldReceive( 'upload' )->once()->with( $file_slice, $args )->andReturn( $upload_result );
		$this->sut->shouldReceive( 'handle_upload_errors' )->once()->with( $upload_result, $args );
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data (missing nonce).
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_no_nonce() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST = [];

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->never();
		Functions\expect( 'wp_verify_nonce' )->never();

		$this->sut->shouldReceive( 'get_file_slice' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data (incorrect nonce).
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_incorrect_nonce() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ] = $nonce_value;

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( false );

		$this->sut->shouldReceive( 'get_file_slice' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data when used to delete the current icon.
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_delete() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ]       = $nonce_value;
		$_POST[ $erase_field ] = 'true';

		// Intermediary data.
		$file_slice = [
			'foobaz' => 'barfoo',
		];

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( true );

		$this->sut->shouldReceive( 'get_file_slice' )->once()->with( $args )->andReturn( $file_slice );
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->once()->with( $args );

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::maybe_save_data when used to delete the current icon.
	 *
	 * @covers ::maybe_save_data
	 */
	public function test_maybe_save_data_delete_incorrect_var() {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input data.
		$nonce        = 'my_nonce';
		$nonce_value  = '12345';
		$action       = 'my_action';
		$upload_field = 'my-upload-field';
		$erase_field  = 'my-erase-field';
		$args         = [
			'nonce'        => $nonce,
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'foo'          => 'bar',
		];

		// Set up fake request.
		$_POST[ $nonce ]       = $nonce_value;
		$_POST[ $erase_field ] = true; // This should be a string, not a boolean.

		// Intermediary data.
		$file_slice = [
			'foobaz' => 'barfoo',
		];

		// Great Expectations.
		Functions\expect( '_doing_it_wrong' )->never();
		Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( true );

		$this->sut->shouldReceive( 'get_file_slice' )->once()->with( $args )->andReturn( $file_slice );
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_upload_errors' )->never();
		$this->sut->shouldReceive( 'store_file_data' )->never();
		$this->sut->shouldReceive( 'delete_file_data' )->never();

		// Check results.
		$this->assertNull( $this->sut->maybe_save_data( $args ) );
	}

	/**
	 * Tests ::upload.
	 *
	 * @covers ::upload
	 */
	public function test_upload() {
		$file   = [ 'foo' => 'bar' ];
		$args   = [ 'bar' => 'baz' ];
		$result = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		$overrides = [
			'mimes'                    => Upload_Handler::ALLOWED_MIME_TYPES,
			'test_form'                => false,
			'unique_filename_callback' => [ $this->sut, 'get_unique_filename' ],
		];

		$this->sut->shouldReceive( 'handle_upload' )->once()->with( $file, $overrides )->andReturn( $result );

		$this->assertSame( $result, $this->sut->upload( $file, $args ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload() {
		$file   = [ 'foo' => 'bar' ];
		$result = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->set_value( $this->sut, 'global_upload', true );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( [ $this->sut, 'custom_upload_dir' ] );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, m::type( 'array' ) )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file ) );
		$this->assertFalse( Filters\has( 'upload_dir', [ $this->sut, 'custom_upload_dir' ] ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_error() {
		$file   = [ 'foo' => 'bar' ];
		$result = [
			'bar'  => 'foo',
		];

		$this->set_value( $this->sut, 'global_upload', true );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( [ $this->sut, 'custom_upload_dir' ] );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, m::type( 'array' ) )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->never();

		$this->assertSame( $result, $this->sut->handle_upload( $file ) );
		$this->assertFalse( Filters\has( 'upload_dir', [ $this->sut, 'custom_upload_dir' ] ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_global_upload() {
		$file         = [ 'foo' => 'bar' ];
		$result       = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];
		$main_site_id = 5;

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->set_value( $this->sut, 'global_upload', true );
		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );
		Functions\expect( 'switch_to_blog' )->once()->with( $main_site_id );
		Functions\expect( 'restore_current_blog' )->once();

		Filters\expectAdded( 'upload_dir' )->once()->with( [ $this->sut, 'custom_upload_dir' ] );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, m::type( 'array' ) )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file ) );
		$this->assertFalse( Filters\has( 'upload_dir', [ $this->sut, 'custom_upload_dir' ] ) );
	}

	/**
	 * Tests ::handle_upload.
	 *
	 * @covers ::handle_upload
	 */
	public function test_handle_upload_global_upload_no_multisite() {
		$file   = [ 'foo' => 'bar' ];
		$result = [
			'bar'  => 'foo',
			'file' => '/my/path',
		];

		$normalized_result         = $result;
		$normalized_result['file'] = '/my/normalized/path';

		$this->set_value( $this->sut, 'global_upload', true );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Filters\expectAdded( 'upload_dir' )->once()->with( [ $this->sut, 'custom_upload_dir' ] );
		Functions\expect( 'wp_handle_upload' )->once()->with( $file, m::type( 'array' ) )->andReturn( $result );
		Functions\expect( 'wp_normalize_path' )->once()->with( $result['file'] )->andReturn( $normalized_result['file'] );

		$this->assertSame( $normalized_result, $this->sut->handle_upload( $file ) );
		$this->assertFalse( Filters\has( 'upload_dir', [ $this->sut, 'custom_upload_dir' ] ) );
	}

	/**
	 * Tests ::custom_upload_dir.
	 *
	 * @covers ::custom_upload_dir
	 */
	public function test_custom_upload_dir() {
		$result = $this->sut->custom_upload_dir(
			[
				'path'   => 'FOO/SUB',
				'url'    => 'https://FOO/SUB',
				'subdir' => 'SUB',
			]
		);

		$this->assertArrayHasKey( 'path', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'subdir', $result );
		$this->assertSame( 'FOO/uploads', $result['path'] );
		$this->assertSame( 'https://FOO/uploads', $result['url'] );
		$this->assertSame( 'uploads', $result['subdir'] );
	}

	/**
	 * Tests ::normalize_files_array.
	 *
	 * @covers ::normalize_files_array
	 */
	public function test_normalize_files_array_single() {
		$slice = [
			'name'     => 'facepalm.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/phpn3FmFr',
			'error'    => 0,
			'size'     => 15476,
		];

		$target = [ $slice ];

		$this->assertSame( $target, $this->sut->normalize_files_array( $slice ) );
	}

	/**
	 * Tests ::normalize_files_array.
	 *
	 * @covers ::normalize_files_array
	 */
	public function test_normalize_files_array_multiple() {
		$slice = [
			'name'     => [
				'facepalm.jpg',
				'other.gif',
			],
			'type'     => [
				'image/jpeg',
				'image/gif',
			],
			'tmp_name' => [
				'/tmp/phpn3FmFr',
				'/tmp/phpn3FmXX',
			],
			'error'    => [
				0,
				0,
			],
			'size'     => [
				15476,
				4000,
			],
		];

		$target = [
			[
				'name'     => 'facepalm.jpg',
				'type'     => 'image/jpeg',
				'tmp_name' => '/tmp/phpn3FmFr',
				'error'    => 0,
				'size'     => 15476,
			],
			[
				'name'     => 'other.gif',
				'type'     => 'image/gif',
				'tmp_name' => '/tmp/phpn3FmXX',
				'error'    => 0,
				'size'     => 4000,
			],
		];

		$this->assertSame( $target, $this->sut->normalize_files_array( $slice ) );
	}
}
