<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Tools\Images\Image_File;

/**
 * Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler
 * @usesDefaultClass \Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Upload_Handlers\Upload_Handler::__construct
 */
class User_Avatar_Upload_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Image_File
	 */
	private $image_file;

	/**
	 * Required helper object.
	 *
	 * @var User_Fields
	 */
	private $registered_user;

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
				'admin' => [
					'partials' => [
						'profile' => [
							'user-avatar-upload.php' => 'USER_AVATAR_UPLOAD_<?php echo $user->ID; ?>',
						],
					],
				],
			],
			'uploads'   => [
				'some.png'            => '',
				'Jane-Doe_avatar.gif' => '',
				'Foobar_avatar.png'   => '',
				'Foobar_avatar_1.png' => '',
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Mock required helpers.
		$this->image_file      = m::mock( Image_File::class );
		$this->registered_user = m::mock( User_Fields::class );

		$this->sut = m::mock( User_Avatar_Upload_Handler::class, [ $this->image_file, $this->registered_user ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Upload_Handlers\Upload_Handler::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( User_Avatar_Upload_Handler::class )->makePartial();

		$mock->__construct( $this->image_file, $this->registered_user );

		$this->assert_attribute_same( User_Avatar_Upload_Handler::UPLOAD_DIR, 'upload_dir', $mock );
		$this->assert_attribute_same( true, 'global_upload', $mock );
		$this->assert_attribute_same( $this->registered_user, 'registered_user', $mock );
	}

	/**
	 * Tests ::get_avatar_upload_markup.
	 *
	 * @covers ::get_avatar_upload_markup
	 */
	public function test_get_avatar_upload_markup() {
		$user     = m::mock( '\WP_User' );
		$user->ID = 666;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), 'Avatar Privacy 2.4.0' );

		$this->assertSame( 'USER_AVATAR_UPLOAD_666', $this->sut->get_avatar_upload_markup( $user ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar.
	 *
	 * @covers ::save_uploaded_user_avatar
	 */
	public function test_save_uploaded_user_avatar() {
		// Input data.
		$user_id = 4711;
		$nonce   = 'my_nonce';
		$action  = 'my_action';
		$field   = 'my_upload_input';
		$erase   = 'my_erase_checkbox';

		$this->sut->shouldReceive( 'maybe_save_data' )->once()->with( m::on( function ( $args ) use ( $nonce, $action, $field, $erase, $user_id ) {
			return ! empty( $args['nonce'] ) && "{$nonce}{$user_id}" === $args['nonce']
			&& ! empty( $args['action'] ) && $action === $args['action']
			&& ! empty( $args['upload_field'] ) && $field === $args['upload_field']
			&& ! empty( $args['erase_field'] ) && $erase === $args['erase_field']
			&& ! empty( $args['user_id'] ) && $user_id === $args['user_id'];
		} ) );

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id, $nonce, $action, $field, $erase ) );
	}

	/**
	 * Tests ::get_file_slice.
	 *
	 * @covers ::get_file_slice
	 */
	public function test_get_file_slice() {
		$upload_field = 'my_upload_field';
		$args         = [
			'upload_field' => $upload_field,
		];

		// Intermediate data.
		$uploaded_file = [
			'name' => [ 'filename' ],
			'type' => [ 'image/gif' ],
			'foo'  => [ 'bar' ],
		];

		// Set up fake request.
		$files = [ $upload_field => $uploaded_file ];

		$this->assertSame( $uploaded_file, $this->sut->get_file_slice( $files, $args ) );
	}

	/**
	 * Tests ::get_file_slice.
	 *
	 * @covers ::get_file_slice
	 */
	public function test_get_file_slice_upload_field_missing() {
		$upload_field = 'my_upload_field';
		$args         = [
			'upload_field' => $upload_field,
		];

		// Intermediate data.
		$uploaded_file = [
			'name' => [ 'filename' ],
			'type' => [ 'image/gif' ],
			'foo'  => [ 'bar' ],
		];

		// Set up fake request.
		$files = [ 'foo' => $uploaded_file ];

		$this->assertSame( [], $this->sut->get_file_slice( $files, $args ) );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_handle_upload_errors_data() {
		return [
			[ 'Sorry, this file type is not permitted for security reasons.' ],
			[ 'Something else.' ],
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
	 */
	public function test_handle_upload_errors( $error_string ) {
		$upload_result = [ 'error' => $error_string ];
		$args          = [
			'foo' => 'bar',
		];

		Functions\expect( 'esc_attr' )->atMost()->once()->with( m::type( 'string' ) )->andReturnFirstArg();

		Actions\expectAdded( 'user_profile_update_errors' )->once()->with( m::type( 'Closure' ) );

		$this->assertNull( $this->sut->handle_upload_errors( $upload_result, $args ) );
	}

	/**
	 * Tests ::handle_upload_errors.
	 *
	 * @covers ::handle_upload_errors
	 */
	public function test_handle_upload_errors_no_error_message() {
		$upload_result = [];
		$args          = [
			'foo' => 'bar',
		];

		Functions\expect( 'esc_attr' )->never();

		Actions\expectAdded( 'user_profile_update_errors' )->once()->with( m::type( 'Closure' ) );

		$this->assertNull( $this->sut->handle_upload_errors( $upload_result, $args ) );
	}

	/**
	 * Tests ::store_file_data.
	 *
	 * @covers ::store_file_data
	 */
	public function test_store_file_data() {
		$upload_result = [
			'name' => 'some/image.png',
			'type' => 'image/png',
		];
		$user_id       = 4711;
		$args          = [
			'foo'     => 'bar',
			'user_id' => $user_id,
		];

		$this->registered_user->shouldReceive( 'set_uploaded_local_avatar' )->once()->with( $user_id, $upload_result );

		$this->assertNull( $this->sut->store_file_data( $upload_result, $args ) );
	}

	/**
	 * Tests ::delete_file_data.
	 *
	 * @covers ::delete_file_data
	 */
	public function test_delete_file_data() {
		$user_id = 4711;
		$args    = [
			'foo'     => 'bar',
			'user_id' => $user_id,
		];

		$this->registered_user->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id );

		$this->assertNull( $this->sut->delete_file_data( $args ) );
	}

	/**
	 * Tests ::get_filename.
	 *
	 * @covers ::get_filename
	 */
	public function test_get_filename() {
		// Set up arguments.
		$user_id  = 666;
		$args     = [
			'foo'     => 'bar',
			'user_id' => $user_id,
		];
		$filename = '/some/file.png';
		$result   = 'Foobar_avatar.png';

		$this->registered_user->shouldReceive( 'get_local_avatar_filename' )->once()->with( $user_id, $filename )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_filename( $filename, $args ) );
	}

	/**
	 * Provides the data for testing delete_uploaded_avatar.
	 *
	 * @return array
	 */
	public function provide_delete_uploaded_avatar_data() {
		return [
			[ 1, 'root/uploads/some.png', true ],
			[ 2, 'root/uploads/notthere.png', false ],
		];
	}

	/**
	 * Tests ::delete_uploaded_avatar.
	 *
	 * @covers ::delete_uploaded_avatar
	 *
	 * @dataProvider provide_delete_uploaded_avatar_data
	 *
	 * @param  int    $user_id The user ID.
	 * @param  string $file    The icon path.
	 * @param  bool   $result  The expected result.
	 */
	public function xtest_delete_uploaded_avatar( $user_id, $file, $result ) {
		$avatar = [ 'file' => vfsStream::url( $file ) ];

		$this->sut->shouldReceive( 'invalidate_user_avatar_cache' )->once()->with( $user_id );

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( $avatar );
		Functions\expect( 'delete_user_meta' )->times( (int) $result )->with( $user_id, User_Fields::USER_AVATAR_META_KEY );

		$this->assertNull( $this->sut->delete_uploaded_avatar( $user_id ) );
	}

	/**
	 * Tests ::delete_uploaded_avatar.
	 *
	 * @covers ::delete_uploaded_avatar
	 */
	public function test_delete_uploaded_avatar() {
		$user_id = 4711;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), 'Avatar Privacy 2.4.0', 'Avatar_Privacy\Core:delete_user_avatar' );

		$this->registered_user->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id );

		$this->assertNull( $this->sut->delete_uploaded_avatar( $user_id ) );
	}


	/**
	 * Tests ::invalidate_user_avatar_cache.
	 *
	 * @covers ::invalidate_user_avatar_cache
	 */
	public function test_invalidate_user_avatar_cache() {
		$user_id = 4711;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), 'Avatar Privacy 2.4.0', 'Avatar_Privacy\Core::invalidate_user_avatar_cache' );

		$this->registered_user->shouldReceive( 'invalidate_local_avatar_cache' )->once()->with( $user_id );

		$this->assertNull( $this->sut->invalidate_user_avatar_cache( $user_id ) );
	}
}
