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

use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Image_File;


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
	 * @var Settings
	 */
	private $settings;

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
	 * @var Image_File
	 */
	private $image_file;

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
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Mock required helpers.
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->image_file = m::mock( Image_File::class );
		$this->settings   = m::mock( Settings::class );
		$this->hasher     = m::mock( Hasher::class );
		$this->options    = m::mock( Options::class );

		$this->sut = m::mock( Custom_Default_Icon_Upload_Handler::class, [ $this->file_cache, $this->image_file, $this->settings, $this->hasher, $this->options ] )->makePartial()->shouldAllowMockingProtectedMethods();
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

		$mock->__construct( $this->file_cache, $this->image_file, $this->settings, $this->hasher, $this->options );

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
	 *
	 * @dataProvider provide_save_uploaded_default_icon_data
	 *
	 * @param  int      $site_id       The site ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_default_icon( $site_id, $uploaded_file ) {
		$this->sut->shouldReceive( 'maybe_save_data' )->once()->with( m::on( function ( $args ) use ( $site_id ) {
			return ! empty( $args['nonce'] )
			&& ! empty( $args['action'] )
			&& ! empty( $args['upload_field'] )
			&& ! empty( $args['erase_field'] )
			&& ! empty( $args['site_id'] )
			&& $args['site_id'] === $site_id
			&& \array_key_exists( 'option_value', $args );
		} ) );

		// Check results.
		$value = null;
		$this->assertNull( $this->sut->save_uploaded_default_icon( $site_id, $value ) );
	}

	/**
	 * Tests ::get_file_slice.
	 *
	 * @covers ::get_file_slice
	 */
	public function test_get_file_slice() {
		global $_FILES;

		$upload_field = 'my_upload_field';
		$args         = [
			'upload_field' => $upload_field,
		];

		// Intermediate data.
		$uploaded_file    = [
			'name' => [ 'filename', 'filename2' ],
			'type' => [ 'image/gif', 'application/x-photoshop' ],
			'foo'  => [ 'bar', 'baz' ],
		];
		$upload_index     = 'some_file_index';
		$result           = [
			'name' => 'filename',
			'foo'  => 'bar',
		];
		$normalized_files = [
			$upload_field => $result,
		];

		// Set up fake request.
		$_FILES = [ $upload_index => $uploaded_file ];

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( $upload_index );
		$this->sut->shouldReceive( 'normalize_files_array' )->once()->with( $uploaded_file )->andReturn( $normalized_files );

		$this->assertSame( $result, $this->sut->get_file_slice( $args ) );
	}

	/**
	 * Tests ::get_file_slice.
	 *
	 * @covers ::get_file_slice
	 */
	public function test_get_file_slice_empty_uploads() {
		global $_FILES;

		$upload_field = 'my_upload_field';
		$args         = [
			'upload_field' => $upload_field,
		];

		// Intermediate data.
		$uploaded_file = [
			'type' => [ 'image/gif', 'application/x-photoshop' ],
			'foo'  => [ 'bar', 'baz' ],
		];
		$upload_index  = 'some_file_index';

		// Set up fake request.
		$_FILES = [ $upload_index => $uploaded_file ];

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( $upload_index );
		$this->sut->shouldReceive( 'normalize_files_array' )->never();

		$this->assertSame( [], $this->sut->get_file_slice( $args ) );
	}

	/**
	 * Tests ::get_file_slice.
	 *
	 * @covers ::get_file_slice
	 */
	public function test_get_file_slice_upload_field_missing() {
		global $_FILES;

		$upload_field = 'my_upload_field';
		$args         = [
			'upload_field' => $upload_field,
		];

		// Intermediate data.
		$uploaded_file    = [
			'name' => [ 'filename', 'filename2' ],
			'type' => [ 'image/gif', 'application/x-photoshop' ],
			'foo'  => [ 'bar', 'baz' ],
		];
		$upload_index     = 'some_file_index';
		$result           = [
			'name' => 'filename',
			'foo'  => 'bar',
		];
		$normalized_files = [
			'some_other_upload_field' => $result,
		];

		// Set up fake request.
		$_FILES = [ $upload_index => $uploaded_file ];

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( $upload_index );
		$this->sut->shouldReceive( 'normalize_files_array' )->once()->with( $uploaded_file )->andReturn( $normalized_files );

		$this->assertSame( [], $this->sut->get_file_slice( $args ) );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_handle_upload_errors_data() {
		return [
			[ 'Sorry, this file type is not permitted for security reasons.', 'default_avatar_invalid_image_type' ],
			[ 'Something else.', 'default_avatar_other_error' ],
		];
	}

	/**
	 * Tests ::handle_upload_errors.
	 *
	 * @covers ::handle_upload_errors
	 *
	 * @dataProvider provide_handle_upload_errors_data
	 *
	 * @param  string $error_string Original error message.
	 * @param  string $error_type   Resulting error type.
	 */
	public function test_handle_upload_errors( $error_string, $error_type ) {
		$upload_result = [ 'error' => $error_string ];
		$args          = [
			'foo'          => 'bar',
			'site_id'      => 42,
		];

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( 'settings_name' );

		Functions\expect( 'esc_attr' )->atMost()->once()->andReturn( 'escaped_string' );
		Functions\expect( 'add_settings_error' )->once()->with( m::pattern( '/^settings_name\[.*\]$/' ), $error_type, m::type( 'string' ), 'error' );

		$this->assertNull( $this->sut->handle_upload_errors( $upload_result, $args ) );
	}

	/**
	 * Tests ::store_file_data.
	 *
	 * @covers ::store_file_data
	 */
	public function test_store_file_data() {
		$option_value = null;
		$site_id      = 42;

		$icon = [
			'file' => '/some/path/image.png',
			'type' => 'image/png',
		];
		$args = [
			'foo'          => 'bar',
			'site_id'      => $site_id,
			'option_value' => &$option_value,
		];

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id );

		$this->assertNull( $this->sut->store_file_data( $icon, $args ) );
		$this->assertSame( $icon, $option_value );
	}

	/**
	 * Tests ::delete_file_data.
	 *
	 * @covers ::delete_file_data
	 */
	public function test_delete_file_data() {
		$option_value = null;
		$site_id      = 42;

		$args = [
			'foo'          => 'bar',
			'site_id'      => $site_id,
			'option_value' => &$option_value,
		];

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id );

		$this->assertNull( $this->sut->delete_file_data( $args ) );
		$this->assertSame( [], $option_value );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_get_filename_data() {
		return [
			[ 'some.png', 'Foobar', 'Foobar.png' ],
			[ 'some.gif', 'Foo &amp; Bar', 'Foo-Bar.gif' ],
			[ 'other.png', 'custom-default-icon', 'custom-default-icon.png' ],
		];
	}

	/**
	 * Tests ::get_filename.
	 *
	 * @covers ::get_filename
	 *
	 * @dataProvider provide_get_filename_data
	 *
	 * @param string $filename  The proposed filename.
	 * @param string $site_name The site name (blogname).
	 * @param string $result    The resulting filename.
	 */
	public function test_get_filename( $filename, $site_name, $result ) {
		$args = [
			'foo' => 'bar',
		];

		Functions\expect( 'sanitize_file_name' )->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return \preg_replace( [ '/ /', '/&/', '/-{2,}/' ], [ '-', '', '-' ], $arg );
			}
		);

		// Regardless of the input filename, we use the blogname option.
		$this->options->shouldReceive( 'get' )->once()->with( 'blogname', 'custom-default-icon', true )->andReturn( $site_name );

		$this->assertSame( $result, $this->sut->get_filename( $filename, $args ) );
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
		$hash = 'some_hash';
		$icon = [
			'file' => vfsStream::url( $file ),
		];

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $site_id )->andReturn( $hash );
		$this->file_cache->shouldReceive( 'invalidate' )->once()->with( 'custom', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR )->andReturn( $icon );

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
