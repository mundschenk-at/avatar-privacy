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
 * Avatar_Privacy_Factory unit test.
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
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar() {
		// Set up arguments.
		$user_id    = 666;
		$image_url  = 'https://example.org/path/image.png';
		$sideloaded = [
			'file' => '/stored/avatar.png',
			'type' => 'image/png',
		];

		$this->image_file->shouldReceiVe( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $sideloaded );
		$this->sut->shouldReceive( 'set_uploaded_local_avatar' )->once()->with( $user_id, $sideloaded );

		$this->assertNull( $this->sut->set_local_avatar( $user_id, $image_url ) );
	}
}
