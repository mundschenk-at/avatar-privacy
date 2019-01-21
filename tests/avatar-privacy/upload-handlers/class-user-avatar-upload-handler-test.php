<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;


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
	 * @var Core
	 */
	private $core;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

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
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Ubiquitous functions.
		Functions\when( '__' )->returnArg();

		// Mock required helpers.
		$this->core       = m::mock( Core::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );

		$this->sut = m::mock( User_Avatar_Upload_Handler::class, [ 'plugin/file', $this->core, $this->file_cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
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

		$mock->__construct( 'a/plugin/file', $this->core, $this->file_cache );

		$this->assertAttributeSame( User_Avatar_Upload_Handler::UPLOAD_DIR, 'upload_dir', $mock );
	}

	/**
	 * Tests ::get_avatar_upload_markup.
	 *
	 * @covers ::get_avatar_upload_markup
	 */
	public function test_get_avatar_upload_markup() {
		$user     = m::mock( '\WP_User' );
		$user->ID = 666;

		$this->assertSame( 'USER_AVATAR_UPLOAD_666', $this->sut->get_avatar_upload_markup( $user ) );
	}

	/**
	 * Provides data for testing save_uploaded_user_avatar.
	 *
	 * @return array
	 */
	public function provide_save_uploaded_user_avatar_data() {
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
	 * Tests ::save_uploaded_user_avatar.
	 *
	 * @covers ::save_uploaded_user_avatar
	 * @covers ::assign_new_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce  = '12345';
		$avatar = [ 'file' => 'filename' ];

		// Set up fake request.
		$_POST[ User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id ] = $nonce;
		$_FILES[ User_Avatar_Upload_Handler::FILE_UPLOAD ]            = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Avatar_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->sut->shouldReceive( 'upload' )->once()->with( $uploaded_file )->andReturn( $avatar );
		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->once()->with( $user_id )->andReturn( true );
		$this->sut->shouldReceive( 'handle_errors' )->never();

		Functions\expect( 'update_user_meta' )->once()->with( $user_id, User_Avatar_Upload_Handler::USER_META_KEY, $avatar );

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar with an error occurring during upload.
	 *
	 * @covers ::save_uploaded_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar_with_error( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce  = '12345';
		$avatar = [];

		// Set up fake request.
		$_POST[ User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id ] = $nonce;
		$_FILES[ User_Avatar_Upload_Handler::FILE_UPLOAD ]            = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Avatar_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->sut->shouldReceive( 'upload' )->once()->with( $uploaded_file )->andReturn( $avatar );
		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->once()->with( $avatar );

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar when no nonce is present.
	 *
	 * @covers ::save_uploaded_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar_no_nonce( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Set up fake request.
		$_POST = [];
		$_FILES[ User_Avatar_Upload_Handler::FILE_UPLOAD ] = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->never();
		Functions\expect( 'wp_verify_nonce' )->never();

		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar with an incorrect nonce.
	 *
	 * @covers ::save_uploaded_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar_incorrect_nonce( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce = '12345';

		// Set up fake request.
		$_POST[ User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id ] = $nonce;
		$_FILES[ User_Avatar_Upload_Handler::FILE_UPLOAD ]            = $uploaded_file;

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Avatar_Upload_Handler::ACTION_UPLOAD )->andReturn( false );

		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar when used to delete the current icon.
	 *
	 * @covers ::save_uploaded_user_avatar
	 * @covers ::assign_new_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar_delete_icon( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce = '12345';

		// Set up fake request.
		$_POST  = [
			User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id => $nonce,
			User_Avatar_Upload_Handler::CHECKBOX_ERASE          => 'true',
		];
		$_FILES = [];

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Avatar_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->once()->with( $user_id );

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar when used to delete the current icon.
	 *
	 * @covers ::save_uploaded_user_avatar
	 * @covers ::assign_new_user_avatar
	 *
	 * @dataProvider provide_save_uploaded_user_avatar_data
	 *
	 * @param  int      $user_id       The user ID.
	 * @param  string[] $uploaded_file The files array.
	 */
	public function test_save_uploaded_user_avatar_delete_icon_incorrect_var( $user_id, $uploaded_file ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_FILES;

		// Intermediate data.
		$nonce = '12345';

		// Set up fake request.
		$_POST  = [
			User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id => $nonce,
			User_Avatar_Upload_Handler::CHECKBOX_ERASE          => true, // This should be a string, not a boolean.
		];
		$_FILES = [];

		// Great Expectations.
		Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
		Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Avatar_Upload_Handler::ACTION_UPLOAD )->andReturn( true );

		$this->sut->shouldReceive( 'upload' )->never();
		$this->sut->shouldReceive( 'handle_errors' )->never();

		$this->sut->shouldReceive( 'delete_uploaded_avatar' )->never();

		// Check results.
		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_handle_errors_data() {
		return [
			[ 'Sorry, this file type is not permitted for security reasons.' ],
			[ 'Something else.' ],
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
	 */
	public function test_handle_errors( $error_string ) {
		$result = [ 'error' => $error_string ];

		Actions\expectAdded( 'user_profile_update_errors' )->once()->with( m::type( 'Closure' ) );

		$this->assertNull( $this->sut->handle_errors( $result ) );
	}

	/**
	 * Provides the data for testing get_unique_filename.
	 *
	 * @return array
	 */
	public function provide_get_unique_filename_data() {
		return [
			[ 'some.png', '.png', 'Jack-Straw_avatar.png', (object) [ 'display_name' => 'Jack Straw' ] ],
			[ 'some.gif', '.gif', 'Jane-Doe_avatar_1.gif', (object) [ 'display_name' => 'Jane Doe' ] ],
			[ 'other.png', '.png', 'Foobar_avatar_2.png', (object) [ 'display_name' => 'Foobar' ] ],
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
	 * @param object $user      The user object.
	 */
	public function test_get_unique_filename( $filename, $extension, $result, $user ) {
		// Set up dummy user ID.
		$user_id = 666;
		$this->setValue( $this->sut, 'user_id_being_edited', $user_id, User_Avatar_Upload_Handler::class );

		Functions\expect( 'get_user_by' )->once()->with( 'id', $user_id )->andReturn( $user );
		Functions\expect( 'sanitize_file_name' )->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return \str_replace( ' ', '-', $arg );
			}
		);

		$this->assertSame( $result, $this->sut->get_unique_filename( vfsStream::url( 'root/uploads' ), $filename, $extension ) );
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
	public function test_delete_uploaded_avatar( $user_id, $file, $result ) {
		$hash   = 'some_hash';
		$avatar = [ 'file' => vfsStream::url( $file ) ];

		$this->core->shouldReceive( 'get_user_hash' )->once()->with( $user_id )->andReturn( $hash );
		$this->file_cache->shouldReceive( 'invalidate' )->once()->with( 'user', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Avatar_Upload_Handler::USER_META_KEY, true )->andReturn( $avatar );
		Functions\expect( 'delete_user_meta' )->times( (int) $result )->with( $user_id, User_Avatar_Upload_Handler::USER_META_KEY );

		$this->assertNull( $this->sut->delete_uploaded_avatar( $user_id ) );
	}
}
