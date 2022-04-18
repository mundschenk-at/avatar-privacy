<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2022 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Image_File;

/**
 * User_Fields unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core\User_Fields
 * @usesDefaultClass \Avatar_Privacy\Core\User_Fields
 *
 * @uses ::__construct
 */
class User_Fields_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var User_Fields
	 */
	private $sut;

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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		$filesystem = [
			'uploads'   => [
				'some.png'            => '',
				'Jane-Doe_avatar.gif' => '',
				'Foobar_avatar.png'   => '',
				'Foobar_avatar_1.png' => '',
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );

		$this->hasher     = m::mock( Hasher::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->image_file = m::mock( Image_File::class );

		// Partially mock system under test.
		$this->sut = m::mock( User_Fields::class, [ $this->hasher, $this->file_cache, $this->image_file ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		// Mock required helpers.
		$hasher     = m::mock( Hasher::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$image_file = m::mock( Image_File::class );

		$user_fields = m::mock( User_Fields::class )->makePartial();
		$user_fields->__construct( $hasher, $file_cache, $image_file );

		$this->assert_attribute_same( $hasher, 'hasher', $user_fields );
		$this->assert_attribute_same( $file_cache, 'file_cache', $user_fields );
		$this->assert_attribute_same( $image_file, 'image_file', $user_fields );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$user_id = '666';
		$hash    = 'hashed_email';

		Functions\expect( 'get_user_meta' )->with( $user_id, User_Fields::EMAIL_HASH_META_KEY, true )->once()->andReturn( $hash );
		Functions\expect( 'get_user_by' )->never();
		$this->hasher->shouldReceive( 'get_hash' )->never();
		Functions\expect( 'update_user_meta' )->never();

		$this->assertSame( $hash, $this->sut->get_hash( $user_id ) );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash_new() {
		$user_id = '666';
		$email   = 'foobar@email.org';
		$user    = (object) [ 'user_email' => $email ];
		$hash    = 'hashed_email';

		Functions\expect( 'get_user_meta' )->with( $user_id, User_Fields::EMAIL_HASH_META_KEY, true )->once()->andReturn( false );
		Functions\expect( 'get_user_by' )->with( 'ID', $user_id )->once()->andReturn( $user );
		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		Functions\expect( 'update_user_meta' )->with( $user_id, User_Fields::EMAIL_HASH_META_KEY, $hash )->once();

		$this->assertSame( $hash, $this->sut->get_hash( $user_id ) );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash_invalid_user_id() {
		$user_id = 55;

		Functions\expect( 'get_user_meta' )->with( $user_id, User_Fields::EMAIL_HASH_META_KEY, true )->once()->andReturn( false );
		Functions\expect( 'get_user_by' )->with( 'ID', $user_id )->once()->andReturn( false );

		$this->assertFalse( $this->sut->get_hash( $user_id ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash() {
		$hash     = 'some hashed email';
		$expected = m::mock( 'WP_User' );
		$users    = [ $expected ];

		Functions\expect( 'get_users' )->once()->with( m::type( 'array' ) )->andReturn( $users );

		$this->assertSame( $expected, $this->sut->get_user_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash_not_found() {
		$hash  = 'some hashed email';
		$users = [];

		Functions\expect( 'get_users' )->once()->with( m::type( 'array' ) )->andReturn( $users );

		$this->assertNull( $this->sut->get_user_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_by_email.
	 *
	 * @covers ::get_user_by_email
	 */
	public function test_get_user_by_email() {
		$email = 'email@example.org';
		$user  = m::mock( 'WP_User' );

		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( $user );

		// The first time queries get_user_by.
		$this->assertSame( $user, $this->sut->get_user_by_email( $email ) );

		// The second time just returns the cached value.
		$this->assertSame( $user, $this->sut->get_user_by_email( $email ) );
	}

	/**
	 * Tests ::get_user_by_email.
	 *
	 * @covers ::get_user_by_email
	 */
	public function test_get_user_by_email_does_not_exist() {
		$email = 'email@example.org';

		Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( false );

		// The first time queries get_user_by.
		$this->assertNull( $this->sut->get_user_by_email( $email ) );

		// The second time just returns the cached value.
		$this->assertNull( $this->sut->get_user_by_email( $email ) );
	}

	/**
	 * Tests ::get_local_avatar.
	 *
	 * @covers ::get_local_avatar
	 */
	public function test_get_local_avatar() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( null );
		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_local_avatar.
	 *
	 * @covers ::get_local_avatar
	 */
	public function test_get_local_avatar_invalid_filter_result() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( [ 'file' => '/some/other/file' ] );
		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_local_avatar.
	 *
	 * @covers ::get_local_avatar
	 */
	public function test_get_local_avatar_invalid_filtered() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( $avatar );
		Functions\expect( 'get_user_meta' )->never();

		$this->assertSame( $avatar, $this->sut->get_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_local_avatar.
	 *
	 * @covers ::get_local_avatar
	 */
	public function test_get_local_avatar_empty_user_meta() {
		$user_id = 42;

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( null );
		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( false );

		$this->assertSame( [], $this->sut->get_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar() {
		// Set up arguments.
		$user_id    = 666;
		$image_url  = 'https://example.org/path/image.png';
		$filename   = '/path/image.png';
		$sideloaded = [
			'file' => '/stored/avatar.png',
			'type' => 'image/png',
		];

		$this->sut->shouldReceive( 'get_local_avatar_filename' )->once()->with( $user_id, $filename )->andReturn( 'Some-User_avatar.png' );
		$this->image_file->shouldReceiVe( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $sideloaded );
		$this->sut->shouldReceive( 'set_uploaded_local_avatar' )->once()->with( $user_id, $sideloaded );

		$this->assertNull( $this->sut->set_local_avatar( $user_id, $image_url ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar_malformed_url() {
		// Set up arguments.
		$user_id   = 666;
		$image_url = 'https://?malformed.example.org';

		$this->expect_exception( \InvalidArgumentException::class );

		$this->sut->shouldReceive( 'get_local_avatar_filename' )->never();
		$this->image_file->shouldReceiVe( 'handle_sideload' )->never();
		$this->sut->shouldReceive( 'set_uploaded_local_avatar' )->never();

		$this->assertNull( $this->sut->set_local_avatar( $user_id, $image_url ) );
	}

	/**
	 * Tests ::set_uploaded_local_avatar.
	 *
	 * @covers ::set_uploaded_local_avatar
	 */
	public function test_set_uploaded_local_avatar() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		$this->sut->shouldReceive( 'user_exists' )->once()->with( $user_id )->andReturn( true );
		$this->sut->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id );

		Functions\expect( 'update_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, $avatar );

		$this->assertNull( $this->sut->set_uploaded_local_avatar( $user_id, $avatar ) );
	}

	/**
	 * Tests ::set_uploaded_local_avatar.
	 *
	 * @covers ::set_uploaded_local_avatar
	 */
	public function test_set_uploaded_local_avatar_user_does_not_exist() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		$this->sut->shouldReceive( 'user_exists' )->once()->with( $user_id )->andReturn( false );
		$this->sut->shouldReceive( 'delete_local_avatar' )->never();

		Functions\expect( 'update_user_meta' )->never();

		$this->expect_exception( \InvalidArgumentException::class );

		$this->assertNull( $this->sut->set_uploaded_local_avatar( $user_id, $avatar ) );
	}

	/**
	 * Tests ::set_uploaded_local_avatar.
	 *
	 * @covers ::set_uploaded_local_avatar
	 */
	public function test_set_uploaded_local_avatar_missing_file() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
		];

		$this->sut->shouldReceive( 'user_exists' )->once()->with( $user_id )->andReturn( true );
		$this->sut->shouldReceive( 'delete_local_avatar' )->never();

		Functions\expect( 'update_user_meta' )->never();

		$this->expect_exception( \InvalidArgumentException::class );

		$this->assertNull( $this->sut->set_uploaded_local_avatar( $user_id, $avatar ) );
	}

	/**
	 * Tests ::set_uploaded_local_avatar.
	 *
	 * @covers ::set_uploaded_local_avatar
	 */
	public function test_set_uploaded_local_avatar_missing_mimetype() {
		$user_id = 42;
		$avatar  = [
			'file' => '/some/fake/file.png',
		];

		$this->sut->shouldReceive( 'user_exists' )->once()->with( $user_id )->andReturn( true );
		$this->sut->shouldReceive( 'delete_local_avatar' )->never();

		Functions\expect( 'update_user_meta' )->never();

		$this->expect_exception( \InvalidArgumentException::class );

		$this->assertNull( $this->sut->set_uploaded_local_avatar( $user_id, $avatar ) );
	}

	/**
	 * Tests ::set_uploaded_local_avatar.
	 *
	 * @covers ::set_uploaded_local_avatar
	 */
	public function test_set_uploaded_local_avatar_invalid_mimetype() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/tiff',
			'file' => '/some/fake/file.tif',
		];

		$this->sut->shouldReceive( 'user_exists' )->once()->with( $user_id )->andReturn( true );
		$this->sut->shouldReceive( 'delete_local_avatar' )->never();

		Functions\expect( 'update_user_meta' )->never();

		$this->expect_exception( \InvalidArgumentException::class );

		$this->assertNull( $this->sut->set_uploaded_local_avatar( $user_id, $avatar ) );
	}

	/**
	 * Tests ::user_exists.
	 *
	 * @covers ::user_exists
	 */
	public function test_user_exists() {
		$user_id = 42;

		Functions\expect( 'get_users' )->once()->with( [
			'include' => $user_id,
			'fields'  => 'ID',
		] )->andReturn( [ $user_id ] );

		$this->assertTrue( $this->sut->user_exists( $user_id ) );
	}

	/**
	 * Tests ::user_exists.
	 *
	 * @covers ::user_exists
	 */
	public function test_user_exists_does_not_exist() {
		$user_id = 42;

		Functions\expect( 'get_users' )->once()->with( [
			'include' => $user_id,
			'fields'  => 'ID',
		] )->andReturn( [] );

		$this->assertFalse( $this->sut->user_exists( $user_id ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/some.png' ),
		];

		$this->sut->shouldReceive( 'invalidate_local_avatar_cache' )->once()->with( $user_id );

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( $avatar );
		Functions\expect( 'delete_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY )->andReturn( true );

		$this->assertSame( true, $this->sut->delete_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar_invalid_file() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/does-not-exist.png' ),
		];

		$this->sut->shouldReceive( 'invalidate_local_avatar_cache' )->once()->with( $user_id );

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::USER_AVATAR_META_KEY, true )->andReturn( $avatar );
		Functions\expect( 'delete_user_meta' )->never();

		$this->assertSame( false, $this->sut->delete_local_avatar( $user_id ) );
	}

	/**
	 * Tests ::invalidate_local_avatar_cache.
	 *
	 * @covers ::invalidate_local_avatar_cache
	 */
	public function test_invalidate_local_avatar_cache() {
		$user_id = 42;
		$hash    = 'fake hash';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $user_id )->andReturn( $hash );
		$this->file_cache->shouldReceive( 'invalidate' )->once()->with( 'user', m::pattern( "/#\/{$hash}-/" ) );

		$this->assertNull( $this->sut->invalidate_local_avatar_cache( $user_id ) );
	}

	/**
	 * Tests ::invalidate_local_avatar_cache.
	 *
	 * @covers ::invalidate_local_avatar_cache
	 */
	public function test_invalidate_local_avatar_no_hash() {
		$user_id = 42;

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $user_id )->andReturn( '' );
		$this->file_cache->shouldReceive( 'invalidate' )->never();

		$this->assertNull( $this->sut->invalidate_local_avatar_cache( $user_id ) );
	}

	/**
	 * Provides the data for testing get_local_avatar_filename.
	 *
	 * @return array
	 */
	public function provide_get_local_avatar_filename_data() {
		return [
			[ '/some/dir/some.png', 'Jack-Straw_avatar.png', 'Jack Straw' ],
			[ 'some/dir/some.gif', 'Jane-Doe_avatar.gif', 'Jane Doe' ],
			[ 'other.png', 'Foobar_avatar.png', 'Foobar' ],
		];
	}

	/**
	 * Tests ::get_local_avatar_filename.
	 *
	 * @covers ::get_local_avatar_filename
	 *
	 * @dataProvider provide_get_local_avatar_filename_data
	 *
	 * @param string $filename     The proposed filename.
	 * @param string $result       The resulting filename.
	 * @param string $display_name The user object.
	 */
	public function test_get_local_avatar_filename( $filename, $result, $display_name ) {
		// Set up dummy user ID.
		$user_id            = 666;
		$user               = m::mock( \WP_User::class );
		$user->ID           = $user_id;
		$user->display_name = $display_name;

		Functions\expect( 'get_user_by' )->once()->with( 'id', $user_id )->andReturn( $user );
		Functions\expect( 'sanitize_file_name' )->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return \strtr(
					$arg,
					[
						' '  => '-',
						'&'  => '',
						'--' => '-',
					]
				);
			}
		);

		$this->assertSame( $result, $this->sut->get_local_avatar_filename( $user_id, $filename ) );
	}

	/**
	 * Tests ::get_local_avatar_filename.
	 *
	 * @covers ::get_local_avatar_filename
	 *
	 * @dataProvider provide_get_local_avatar_filename_data
	 *
	 * @param string $filename     The proposed filename.
	 */
	public function test_get_local_avatar_filename_user_does_not_exist( $filename ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'get_user_by' )->once()->with( 'id', $user_id )->andReturn( false );
		Functions\expect( 'sanitize_file_name' )->never();

		$this->assertSame( $filename, $this->sut->get_local_avatar_filename( $user_id, $filename ) );
	}

	/**
	 * Provides data for testing ::allows_gravatar_use, ::allows_anonymous_commenting
	 *
	 * @return array
	 */
	public function provide_allows_metafield_data() {
		return [
			'True value'       => [ 'true', true ],
			'False value'      => [ 'false', false ],
			'Missing value'    => [ '', false ],
			'Unexpected value' => [ 5, false ],
		];
	}

	/**
	 * Tests ::allows_gravatar_use.
	 *
	 * @covers ::allows_gravatar_use
	 *
	 * @dataProvider provide_allows_metafield_data
	 *
	 * @param string $meta   The stored meta data.
	 * @param bool   $result The expected result.
	 */
	public function test_allows_gravatar_use( $meta, $result ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::GRAVATAR_USE_META_KEY, true )->andReturn( $meta );

		$this->assertSame( $result, $this->sut->allows_gravatar_use( $user_id ) );
	}

	/**
	 * Provides data for testing ::has_gravatar_policy, ::has_anonymous_comment_policy
	 *
	 * @return array
	 */
	public function provide_has_metafield_policy_data() {
		return [
			'True value'       => [ 'true', true ],
			'False value'      => [ 'false', true ],
			'Missing value'    => [ '', false ],
		];
	}

	/**
	 * Tests ::has_gravatar_policy.
	 *
	 * @covers ::has_gravatar_policy
	 *
	 * @dataProvider provide_has_metafield_policy_data
	 *
	 * @param string $meta   The stored meta data.
	 * @param bool   $result The expected result.
	 */
	public function test_has_gravatar_policy( $meta, $result ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::GRAVATAR_USE_META_KEY, true )->andReturn( $meta );

		$this->assertSame( $result, $this->sut->has_gravatar_policy( $user_id ) );
	}

	/**
	 * Provides data for testing ::update_gravatar_use, ::update_anonymous_commenting
	 *
	 * @return array
	 */
	public function provide_update_metafield_data() {
		return [
			'True value'       => [ true, 'true' ],
			'False value'      => [ false, 'false' ],
		];
	}

	/**
	 * Tests ::update_gravatar_use.
	 *
	 * @covers ::update_gravatar_use
	 *
	 * @dataProvider provide_update_metafield_data
	 *
	 * @param string $value         The value to be set.
	 * @param bool   $expected_meta The expected meta value stored in the database.
	 */
	public function test_update_gravatar_use( $value, $expected_meta ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'update_user_meta' )->once()->with( $user_id, User_Fields::GRAVATAR_USE_META_KEY, $expected_meta );

		$this->assertNull( $this->sut->update_gravatar_use( $user_id, $value ) );
	}

	/**
	 * Tests ::allows_anonymous_commenting.
	 *
	 * @covers ::allows_anonymous_commenting
	 *
	 * @dataProvider provide_allows_metafield_data
	 *
	 * @param string $meta   The stored meta data.
	 * @param bool   $result The expected result.
	 */
	public function test_allows_anonymous_commenting( $meta, $result ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::ALLOW_ANONYMOUS_META_KEY, true )->andReturn( $meta );

		$this->assertSame( $result, $this->sut->allows_anonymous_commenting( $user_id ) );
	}

	/**
	 * Tests ::has_anonymous_commenting_policy.
	 *
	 * @covers ::has_anonymous_commenting_policy
	 *
	 * @dataProvider provide_has_metafield_policy_data
	 *
	 * @param string $meta   The stored meta data.
	 * @param bool   $result The expected result.
	 */
	public function test_has_has_anonymous_policy( $meta, $result ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, User_Fields::ALLOW_ANONYMOUS_META_KEY, true )->andReturn( $meta );

		$this->assertSame( $result, $this->sut->has_anonymous_commenting_policy( $user_id ) );
	}

	/**
	 * Tests ::update_anonymous_commenting.
	 *
	 * @covers ::update_anonymous_commenting
	 *
	 * @dataProvider provide_update_metafield_data
	 *
	 * @param string $value         The value to be set.
	 * @param bool   $expected_meta The expected meta value stored in the database.
	 */
	public function test_update_anonymous_commenting( $value, $expected_meta ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'update_user_meta' )->once()->with( $user_id, User_Fields::ALLOW_ANONYMOUS_META_KEY, $expected_meta );

		$this->assertNull( $this->sut->update_anonymous_commenting( $user_id, $value ) );
	}

	/**
	 * Provides data for testing ::delete.
	 *
	 * @return array
	 */
	public function provide_delete_data() {
		return [
			'everything'              => [ 4, true, true, true, true ],
			'no hash'                 => [ 3, false, true, true, true ],
			'no gravatar'             => [ 3, true, false, true, true ],
			'no anonymous'            => [ 3, true, true, false, true ],
			'no avatar'               => [ 3, true, true, true, false ],
			'no anonymous, no avatar' => [ 2, true, true, false, false ],
			'only hash'               => [ 1, true, false, false, false ],
			'nothing'                 => [ 0, false, false, false, false ],
		];
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 *
	 * @dataProvider provide_delete_data
	 *
	 * @param  int  $result       The expected result.
	 * @param  bool $hash_del     Whether the email hash was deleted.
	 * @param  bool $gravatar_del Whether the Gravatar usage policy was deleted.
	 * @param  bool $anon_del     Whether the anoymous commenting policy was deleted.
	 * @param  bool $avatar_del   Whether the local avatar was deleted.
	 */
	public function test_delete( $result, $hash_del, $gravatar_del, $anon_del, $avatar_del ) {
		// Set up parameters..
		$user_id = 666;

		Functions\expect( 'delete_user_meta' )->once()->with( $user_id, User_Fields::EMAIL_HASH_META_KEY )->andReturn( $hash_del );
		Functions\expect( 'delete_user_meta' )->once()->with( $user_id, User_Fields::GRAVATAR_USE_META_KEY )->andReturn( $gravatar_del );
		Functions\expect( 'delete_user_meta' )->once()->with( $user_id, User_Fields::ALLOW_ANONYMOUS_META_KEY )->andReturn( $anon_del );

		$this->sut->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id )->andReturn( $avatar_del );

		$this->assertSame( $result, $this->sut->delete( $user_id ) );
	}

	/**
	 * Tests ::remove_orphaned_local_avatar.
	 *
	 * @covers ::remove_orphaned_local_avatar
	 */
	public function test_remove_orphaned_local_avatar() {
		$file       = vfsStream::url( 'root/uploads/some.png' );
		$meta_ids   = [ '47', '11' ];
		$meta_key   = User_Fields::USER_AVATAR_META_KEY;
		$object_id  = 42;
		$meta_value = [
			'type' => 'image/png',
			'file' => $file,
		];

		$this->assertNull( $this->sut->remove_orphaned_local_avatar( $meta_ids, $object_id, $meta_key, $meta_value ) );

		$this->assertFalse( \file_exists( $file ) );
	}

	/**
	 * Tests ::remove_orphaned_local_avatar.
	 *
	 * @covers ::remove_orphaned_local_avatar
	 */
	public function test_remove_orphaned_local_avatar_invalid_file() {
		$file       = vfsStream::url( 'root/uploads/some.png' );
		$meta_ids   = [ '47', '11' ];
		$meta_key   = User_Fields::USER_AVATAR_META_KEY;
		$object_id  = 42;
		$meta_value = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/invalid_file.png' ),
		];

		$this->assertNull( $this->sut->remove_orphaned_local_avatar( $meta_ids, $object_id, $meta_key, $meta_value ) );

		$this->assertTrue( \file_exists( $file ) );
	}

	/**
	 * Tests ::remove_orphaned_local_avatar.
	 *
	 * @covers ::remove_orphaned_local_avatar
	 */
	public function test_remove_orphaned_local_avatar_invalid_meta_value() {
		$file       = vfsStream::url( 'root/uploads/some.png' );
		$meta_ids   = [ '47', '11' ];
		$meta_key   = User_Fields::USER_AVATAR_META_KEY;
		$object_id  = 42;
		$meta_value = $file;

		$this->assertNull( $this->sut->remove_orphaned_local_avatar( $meta_ids, $object_id, $meta_key, $meta_value ) );

		$this->assertTrue( \file_exists( $file ) );
	}

	/**
	 * Tests ::remove_orphaned_local_avatar.
	 *
	 * @covers ::remove_orphaned_local_avatar
	 */
	public function test_remove_orphaned_local_avatar_invalid_meta_key() {
		$file       = vfsStream::url( 'root/uploads/some.png' );
		$meta_ids   = [ '47', '11' ];
		$meta_key   = 'foobar';
		$object_id  = 42;
		$meta_value = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/invalid_file.png' ),
		];

		$this->assertNull( $this->sut->remove_orphaned_local_avatar( $meta_ids, $object_id, $meta_key, $meta_value ) );

		$this->assertTrue( \file_exists( $file ) );
	}
}
