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

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Tools\Hasher;

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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		$this->hasher = m::mock( Hasher::class );

		// Partially mock system under test.
		$this->sut = m::mock( User_Fields::class, [ $this->hasher ] )
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
		$hasher = m::mock( Hasher::class )->makePartial();

		$user_fields = m::mock( User_Fields::class )->makePartial();
		$user_fields->__construct( $hasher );

		$this->assert_attribute_same( $hasher, 'hasher', $user_fields );
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
}
