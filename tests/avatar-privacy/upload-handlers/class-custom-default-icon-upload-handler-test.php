<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

use Avatar_Privacy\Core\Default_Avatars;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Data_Storage\Options;
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
	 * @var Default_Avatars
	 */
	private $default_avatars;

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
		$this->image_file      = m::mock( Image_File::class );
		$this->default_avatars = m::mock( Default_Avatars::class );
		$this->options         = m::mock( Options::class );

		$this->sut = m::mock( Custom_Default_Icon_Upload_Handler::class, [ $this->image_file, $this->default_avatars, $this->options ] )->makePartial()->shouldAllowMockingProtectedMethods();
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

		$mock->__construct( $this->image_file, $this->default_avatars, $this->options );

		$this->assert_attribute_same( $this->default_avatars, 'default_avatars', $mock );
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
			[ 'Sorry, this file type is not permitted for security reasons.', Custom_Default_Icon_Upload_Handler::ERROR_INVALID_IMAGE ],
			[ 'Something else.', Custom_Default_Icon_Upload_Handler::ERROR_OTHER ],
			[ null, Custom_Default_Icon_Upload_Handler::ERROR_UNKNOWN ],
		];
	}

	/**
	 * Tests ::handle_upload_errors.
	 *
	 * @covers ::handle_upload_errors
	 *
	 * @dataProvider provide_handle_upload_errors_data
	 *
	 * @param  string|null $error_string Original error message.
	 * @param  string      $error_type   Resulting error type.
	 */
	public function test_handle_upload_errors( $error_string, $error_type ) {
		$upload_result = [ 'error' => $error_string ];
		$args          = [
			'foo'          => 'bar',
			'site_id'      => 42,
		];

		Functions\expect( 'esc_attr' )->atMost()->once()->with( m::type( 'string' ) )->andReturnFirstArg();

		$this->sut->shouldReceive( 'raise_settings_error' )->once()->with( $error_type, m::type( 'string' ) );

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

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( true );

		$this->assertNull( $this->sut->store_file_data( $icon, $args ) );
		$this->assertSame( $icon, $option_value );
	}

	/**
	 * Tests ::store_file_data.
	 *
	 * @covers ::store_file_data
	 */
	public function test_store_file_data_error_during_delete() {
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

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( false );

		$this->sut->shouldReceive( 'handle_file_delete_error' )->once();

		$this->assertNull( $this->sut->store_file_data( $icon, $args ) );
		$this->assertNotSame( $icon, $option_value );
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

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( true );

		$this->assertNull( $this->sut->delete_file_data( $args ) );
		$this->assertSame( [], $option_value );
	}

	/**
	 * Tests ::delete_file_data.
	 *
	 * @covers ::delete_file_data
	 */
	public function test_delete_file_data_error_during_delete() {
		$option_value = null;
		$site_id      = 42;

		$args = [
			'foo'          => 'bar',
			'site_id'      => $site_id,
			'option_value' => &$option_value,
		];

		$this->sut->shouldReceive( 'delete_uploaded_icon' )->once()->with( $site_id )->andReturn( false );
		$this->sut->shouldReceive( 'handle_file_delete_error' )->once();

		$this->assertNull( $this->sut->delete_file_data( $args ) );
		$this->assertNotSame( [], $option_value );
	}

	/**
	 * Tests ::get_filename.
	 *
	 * @covers ::get_filename
	 */
	public function test_get_filename() {
		$args     = [
			'foo' => 'bar',
		];
		$filename = '/some/file.png';
		$result   = 'custom-default-icon.png';

		$this->default_avatars->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_filename( $filename, $args ) );
	}

	/**
	 * Provides the data for testing delete_uploaded_icon.
	 *
	 * @return array
	 */
	public function provide_delete_uploaded_icon_data() {
		return [
			[ 1, true ],
			[ 2, false ],
		];
	}

	/**
	 * Tests ::delete_uploaded_icon.
	 *
	 * @covers ::delete_uploaded_icon
	 *
	 * @dataProvider provide_delete_uploaded_icon_data
	 *
	 * @param  int  $site_id The site ID.
	 * @param  bool $result  The expected result.
	 */
	public function test_delete_uploaded_icon( $site_id, $result ) {

		$this->default_avatars->shouldReceive( 'delete_custom_default_avatar_image_file' )->once()->andReturn( $result );

		if ( $result ) {
			$this->default_avatars->shouldReceive( 'invalidate_custom_default_avatar_cache' )->once()->with( $site_id );
		}

		$this->assertSame( $result, $this->sut->delete_uploaded_icon( $site_id ) );
	}

	/**
	 * Tests ::raise_settings_error.
	 *
	 * @covers ::raise_settings_error
	 */
	public function test_raise_settings_error() {
		$id      = 'my_error_id';
		$message = 'some error message';
		$type    = 'my-error-type';

		$setting = 'my-setting';

		$this->options->shouldReceive( 'get_name' )->once()->with( Settings::OPTION_NAME )->andReturn( $setting );

		Functions\expect( 'add_settings_error' )->once()->with( m::pattern( '/^my-setting\[.*\]$/' ), $id, $message, $type );

		$this->assertNull( $this->sut->raise_settings_error( $id, $message, $type ) );
	}

	/**
	 * Tests ::handle_file_delete_error.
	 *
	 * @covers ::handle_file_delete_error
	 */
	public function test_handle_file_delete_error() {
		$file = '/some/image.png';
		$icon = [
			'file' => $file,
			'type' => 'image/png',
		];

		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $icon );

		Functions\expect( 'esc_attr' )->atMost()->once()->andReturn( 'escaped_string' );

		$this->sut->shouldReceive( 'raise_settings_error' )->once()->with( Custom_Default_Icon_Upload_Handler::ERROR_FILE, m::type( 'string' ) );

		$this->assertNull( $this->sut->handle_file_delete_error() );
	}
}
