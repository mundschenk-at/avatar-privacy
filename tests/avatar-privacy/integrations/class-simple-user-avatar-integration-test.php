<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2021 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Integrations;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Integrations\Simple_User_Avatar_Integration;
use Avatar_Privacy\Core\User_Fields;

/**
 * Avatar_Privacy\Integrations\Simple_User_Avatar_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\Simple_User_Avatar_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\Simple_User_Avatar_Integration
 *
 * @uses ::__construct
 */
class Simple_User_Avatar_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Simple_User_Avatar_Integration
	 */
	private $sut;

	/**
	 * Mocked helper object.
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->user_fields = m::mock( User_Fields::class );

		$this->sut = m::mock( Simple_User_Avatar_Integration::class, [ $this->user_fields ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Simple_User_Avatar_Integration::class )->makePartial();

		$mock->__construct( $this->user_fields );

		$this->assert_attribute_same( $this->user_fields, 'user_fields', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		// First check fails.
		$this->assertFalse( $this->sut->check() );

		// Ensure class exists.
		m::mock( \SimpleUserAvatar_Public::class );

		// Now the check succeeds.
		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectRemoved( 'plugins_loaded' )->once()->with( [ 'SimpleUserAvatar_Public', 'init' ] );
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'init' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::init.
	 *
	 * @covers ::init
	 */
	public function test_init() {
		Filters\expectAdded( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( '__return_true', 10, 0 );
		Filters\expectAdded( 'avatar_privacy_pre_get_user_avatar' )->once()->with( [ $this->sut, 'enable_user_avatars' ], 10, 2 );
		Actions\expectAdded( 'deleted_user_meta' )->once()->with( [ $this->sut, 'invalidate_cache_after_avatar_change' ], 10, 3 );

		$this->assertNull( $this->sut->init() );
	}

	/**
	 * Tests ::enable_user_avatars.
	 *
	 * @covers ::enable_user_avatars
	 */
	public function test_enable_user_avatars() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file = \ABSPATH . 'some/file';
		$type = 'mime/type';

		// Expected result.
		$result = [
			'file' => $file,
			'type' => $type,
		];

		$this->sut->shouldReceive( 'get_simple_user_avatar_avatar' )->once()->with( $user_id )->andReturn( $file );

		Functions\expect( 'wp_check_filetype' )->once()->with( $file )->andReturn( [ 'type' => $type ] );

		$this->assertSame( $result, $this->sut->enable_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::enable_user_avatars.
	 *
	 * @covers ::enable_user_avatars
	 */
	public function test_enable_user_avatars_no_avatar() {
		// Input.
		$user_id = 42;

		$this->sut->shouldReceive( 'get_simple_user_avatar_avatar' )->once()->with( $user_id )->andReturn( '' );

		Functions\expect( 'wp_check_filetype' )->never();

		$this->assertNull( $this->sut->enable_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::get_simple_user_avatar_avatar.
	 *
	 * @covers ::get_simple_user_avatar_avatar
	 */
	public function test_get_simple_user_avatar_avatar() {
		// Input.
		$user_id = 42;

		// Result.
		$attachment_id = 4711;
		$file          = '/foobar/some/path/image.png';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, \SUA_USER_META_KEY, true )->andReturn( $attachment_id );
		Functions\expect( 'get_attached_file' )->once()->with( $attachment_id )->andReturn( $file );

		$this->assertSame( $file, $this->sut->get_simple_user_avatar_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_simple_user_avatar_avatar.
	 *
	 * @covers ::get_simple_user_avatar_avatar
	 */
	public function test_get_simple_user_avatar_avatar_none_set() {
		// Input.
		$user_id = 42;

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, \SUA_USER_META_KEY, true )->andReturn( false );
		Functions\expect( 'get_attached_file' )->never();

		$this->assertSame( '', $this->sut->get_simple_user_avatar_avatar( $user_id ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_change.
	 *
	 * @covers ::invalidate_cache_after_avatar_change
	 */
	public function test_invalidate_cache_after_avatar_change() {
		// Input.
		$meta_id  = 4711;
		$user_id  = 42;
		$meta_key = \SUA_USER_META_KEY;

		$this->user_fields->shouldReceive( 'invalidate_local_avatar_cache' )->once()->with( $user_id );

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_change( $meta_id, $user_id, $meta_key ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_change.
	 *
	 * @covers ::invalidate_cache_after_avatar_change
	 */
	public function test_invalidate_cache_after_avatar_change_other_metadata() {
		// Input.
		$meta_id  = 4711;
		$user_id  = 42;
		$meta_key = 'foobar';

		$this->user_fields->shouldReceive( 'invalidate_local_avatar_cache' )->never();

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_change( $meta_id, $user_id, $meta_key ) );
	}
}
