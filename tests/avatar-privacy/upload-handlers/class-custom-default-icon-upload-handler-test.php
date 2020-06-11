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

use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler;

use Avatar_Privacy\Core;
use Avatar_Privacy\Core\Hasher;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;


/**
 * Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler
 * @usesDefaultClass \Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Upload_Handlers\Upload_Handler::__construct
 */
class Custom_Default_Icon_Upload_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Custom_Default_Icon_Upload_Handler
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Required helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

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
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Mock required helpers.
		$this->core       = m::mock( Core::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->hasher     = m::mock( Hasher::class );
		$this->options    = m::mock( Options::class );

		$this->sut = m::mock( Custom_Default_Icon_Upload_Handler::class, [ $this->core, $this->file_cache, $this->hasher, $this->options ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Upload_Handlers\Upload_Handler::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Custom_Default_Icon_Upload_Handler::class )->makePartial();

		$mock->__construct( $this->core, $this->file_cache, $this->hasher, $this->options );

		$this->assert_attribute_same( $this->options, 'options', $mock );
	}

	/**
	 * Provides data for testing save_uploaded_default_icon.
	 *
	 * @return array
	 */
	public function provide_save_uploaded_default_icon_data() {
		return [
			[
				6,
				[
					'name' => 'filename',
					'foo'  => 'bar',
				],
			],
			[
				7,
				[
					'name' => [ 'filename', 'filename2' ],
					'type' => [ 'image/gif', 'application/x-photoshop' ],
					'foo'  => [ 'bar', 'baz' ],
				],
			],

		];
	}

	/**
	 * Tests ::save_uploaded_default_icon.
	 *
	 * @covers ::save_uploaded_default_icon
	 * @covers ::assign_new_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce            = '12345';
		$normalized_files = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'name' => 'filename',
				'foo'  => 'bar',
			],
		];
		$icon             = [ 'file' => 'filename' ];

		// Set up fake request.
		$_POST[ Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD . $site_id ] = $nonce;
		$_FILES['upload_index'] = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'upload_index' );

		Functions\expect( 'wp_unslash' )->never();
		$this->sut->shouldReceive( 'normalize_files_array' )->once()->with( $uploaded_file )->andReturn( $normalized_files );
		$this->sut->shouldReceive( 'upload' )->once()->with( $normalized_files[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ], false )->andReturn( $icon );
		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( true );
		$this->sut->shouldReceive( 'handle_errors' )->never();

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertSame( $icon, $value );
	}

	/**
	 * Tests ::save_uploaded_default_icon with an error occurring during upload.
	 *
	 * @covers ::save_uploaded_default_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon_with_error( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce            = '12345';
		$normalized_files = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'name' => 'filename',
				'foo'  => 'bar',
			],
		];
		$icon             = [];

		// Set up fake request.
		$_POST[ Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD . $site_id ] = $nonce;
		$_FILES['upload_index'] = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'upload_index' );

		Functions\expect( 'wp_unslash' )->never();
		$this->sut->shouldReceive( 'normalize_files_array' )->once()->with( $uploaded_file )->andReturn( $normalized_files );
		$this->sut->shouldReceive( 'upload' )->once()->with( $normalized_files[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ], false )->andReturn( $icon );
		$this->sut->shouldReceive( 'delete_uploaded_icon' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->once()->with( $icon );

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertNull( $value );
	}

	/**
	 * Tests ::save_uploaded_default_icon when no nonce is present.
	 *
	 * @covers ::save_uploaded_default_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon_no_nonce( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce            = '12345';
		$normalized_files = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'name' => 'filename',
				'foo'  => 'bar',
			],
		];
		$icon             = [];

		// Set up fake request.
		$_POST                  = [];
		$_FILES['upload_index'] = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->never();
		Functions\expect( 'wp_verify_nonce' )->never();

		$this->options->shouldReceive( 'get_name' )->never();

		$this->sut->shouldReceive( 'normalize_files_array' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'delete_uploaded_icon' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertNull( $value );
	}

	/**
	 * Tests ::save_uploaded_default_icon with an incorrect nonce.
	 *
	 * @covers ::save_uploaded_default_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon_incorrect_nonce( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce            = '12345';
		$normalized_files = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'name' => 'filename',
				'foo'  => 'bar',
			],
		];
		$icon             = [];

		// Set up fake request.
		$_POST[ Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD . $site_id ] = $nonce;
		$_FILES['upload_index'] = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD )->andReturn( false );

		$this->options->shouldReceive( 'get_name' )->never();

		$this->sut->shouldReceive( 'normalize_files_array' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'delete_uploaded_icon' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertNull( $value );
	}

	/**
	 * Tests ::save_uploaded_default_icon when used to delete the current icon.
	 *
	 * @covers ::save_uploaded_default_icon
	 * @covers ::assign_new_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon_delete_icon( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce = '12345';

		// Set up fake request.
		$_POST  = [
			Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD . $site_id => $nonce,
			Custom_Default_Icon_Upload_Handler::CHECKBOX_ERASE => 'true',
		];
		$_FILES = [];

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'upload_index' );

		$this->sut->shouldReceive( 'normalize_files_array' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( true );

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertSame( [], $value );
	}

	/**
	 * Tests ::save_uploaded_default_icon when used to delete the current icon.
	 *
	 * @covers ::save_uploaded_default_icon
	 * @covers ::assign_new_icon
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon_delete_icon_incorrect_var( $site_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce = '12345';

		// Set up fake request.
		$_POST  = [
			Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD . $site_id => $nonce,
			Custom_Default_Icon_Upload_Handler::CHECKBOX_ERASE => true, // This should be a string, not a boolean.
		];
		$_FILES = [];

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'upload_index' );

		$this->sut->shouldReceive( 'normalize_files_array' )->never();
		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->never();

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
		$this->assertNull( $value );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_handle_errors_data() {
		return [
			[ 'Sorry, this file type is not permitted for security reasons.', 'default_avatar_invalid_image_type' ],
			[ 'Something else.', 'default_avatar_other_error' ],
		];
	}

	/**
	 * Tests ::handle_errors.
	 *
	 * @covers ::handle_errors
	 *
	 * @dataProvider provide_handle_errors_data
	 *
	 * @param  string $error_string Original error message.
	 * @param  string $error_type   Resulting error type.
	 */
	public function test_handle_errors( $error_string, $error_type ) {
		$result = [ 'error' => $error_string ];

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'settings_name' );

		Functions\expect( 'esc_attr' )->atMost()->once()->andReturn( 'escaped_string' );
		Functions\expect( 'add_settings_error' )->once()->with( m::pattern( '/^settings_name\[.*\]$/' ), $error_type, m::type( 'string' ), 'error' );

		$this->assertNull( $this->sut->handle_errors( $result ) );
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
	 * @uses Avatar_Privacy\Upload_Handlers\Upload_Handler::get_unique_filename
	 *
	 * @dataProvider provide_get_unique_filename_data
	 *
	 * @param string $filename  The proposed filename.
	 * @param string $extension The file extension (including leading dot).
	 * @param string $result    The resulting filename.
	 */
	public function test_get_unique_filename( $filename, $extension, $result ) {
		Functions\expect( 'sanitize_file_name' )->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return $arg;
			}
		);

		// Regardless of the input filename, we use the blogname option.
		$this->options->shouldReceive( 'get' )->once()->with( 'blogname', 'custom-default-icon', true )->andReturn( 'some' );

		// The result depends on the extension.
		$result = '.gif' === $extension ? 'some_1.gif' : 'some_3.png';

		$this->assertSame( $result, $this->sut->get_unique_filename( vfsStream::url( 'root/uploads' ), $filename, $extension ) );
	}

	/**
	 * Provides the data for testing delete_uploaded_icon.
	 *
	 * @return array
	 */
	public function provide_delete_uploaded_icon_data() {
		return [
			[ 1, 'root/uploads/some.png', true ],
			[ 2, 'root/uploads/notthere.png', false ],
		];
	}

	/**
	 * Tests ::delete_uploaded_icon.
	 *
	 * @covers ::delete_uploaded_icon
	 *
	 * @dataProvider provide_delete_uploaded_icon_data
	 *
	 * @param  int    $site_id The site ID.
	 * @param  string $file    The icon path.
	 * @param  bool   $result  The expected result.
	 */
	public function test_delete_uploaded_icon( $site_id, $file, $result ) {
		$hash     = 'some_hash';
		$settings = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'file' => vfsStream::url( $file ),
			],
		];

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $site_id )->andReturn( $hash );
		$this->file_cache->shouldReceive( 'invalidate' )->once()->with( 'custom', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );
		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $settings );

		$this->assertSame( $result, $this->sut->delete_uploaded_icon( $site_id ) );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$site_id = 123;
		$hash    = 'fake hash';

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( "custom-default-{$site_id}" )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $site_id ) );
	}
}
