<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Integrations\BuddyPress_Integration;

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;


/**
 * Avatar_Privacy\Integrations\BuddyPress_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\BuddyPress_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\BuddyPress_Integration
 *
 * @uses ::__construct
 */
class BuddyPress_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var BuddyPress_Integration
	 */
	private $sut;

	/**
	 * Mocked helper object.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $upload;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->upload = m::mock( User_Avatar_Upload_Handler::class );

		$this->sut = m::mock( BuddyPress_Integration::class, [ $this->upload ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( BuddyPress_Integration::class )->makePartial();

		$mock->__construct( $this->upload );

		$this->assert_attribute_same( $this->upload, 'upload', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		$this->assertFalse( $this->sut->check() );

		$fake_plugin = m::mock( \BuddyPress::class ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'integrate_with_buddypress_avatars' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::integrate_with_buddypress_avatars.
	 *
	 * @covers ::integrate_with_buddypress_avatars
	 */
	public function test_integrate_with_buddypress_avatars() {

		Filters\expectRemoved( 'get_avatar_url' )->once()->with( 'bp_core_get_avatar_data_url_filter', m::type( 'int' ) );
		Filters\expectAdded( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( '__return_true', 10, 0 );
		Filters\expectAdded( 'avatar_privacy_pre_get_user_avatar' )->once()->with( [ $this->sut, 'enable_buddypress_user_avatars' ], 10, 2 );
		Actions\expectAdded( 'xprofile_avatar_uploaded' )->once()->with( [ $this->sut, 'invalidate_cache_after_avatar_upload' ], 10, 3 );

		$this->assertNull( $this->sut->integrate_with_buddypress_avatars() );
	}

	/**
	 * Tests ::enable_buddypress_user_avatars.
	 *
	 * @covers ::enable_buddypress_user_avatars
	 */
	public function test_enable_buddypress_user_avatars() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file    = 'some/file';
		$url     = "https://foobar/{$file}";
		$type    = 'mime/type';
		$absfile = \ABSPATH . $file;

		// Expected result.
		$result = [
			'file' => $absfile,
			'type' => $type,
		];

		Filters\expectAdded( 'bp_core_default_avatar_user' )->once()->with( '__return_empty_string' );
		Functions\expect( 'bp_core_fetch_avatar' )->once()->with(  [
			'item_id' => $user_id,
			'object'  => 'user',
			'type'    => 'full',
			'html'    => false,
			'no_grav' => true,
		] )->andReturn( $url );
		Filters\expectRemoved( 'bp_core_default_avatar_user' )->once()->with( '__return_empty_string' );

		Functions\expect( 'wp_make_link_relative' )->once()->with( $url )->andReturn( $file );
		Functions\expect( 'wp_check_filetype' )->once()->with( $absfile )->andReturn( [ 'type' => $type ] );

		$this->assertSame( $result, $this->sut->enable_buddypress_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::enable_buddypress_user_avatars.
	 *
	 * @covers ::enable_buddypress_user_avatars
	 */
	public function test_enable_buddypress_user_avatars_no_avatar() {
		// Input.
		$user_id = 42;

		Filters\expectAdded( 'bp_core_default_avatar_user' )->once()->with( '__return_empty_string' );
		Functions\expect( 'bp_core_fetch_avatar' )->once()->with(  [
			'item_id' => $user_id,
			'object'  => 'user',
			'type'    => 'full',
			'html'    => false,
			'no_grav' => true,
		] )->andReturn( '' );
		Filters\expectRemoved( 'bp_core_default_avatar_user' )->once()->with( '__return_empty_string' );

		Functions\expect( 'wp_make_link_relative' )->never();
		Functions\expect( 'wp_check_filetype' )->never();

		$this->assertNull( $this->sut->enable_buddypress_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_upload.
	 *
	 * @covers ::invalidate_cache_after_avatar_upload
	 */
	public function test_iinvalidate_cache_after_avatar_upload() {
		// Input.
		$item_id = 42;
		$type    = 'crop';
		$args    = [
			'foo'     => 'bar',
			'object'  => 'user',
			'item_id' => $item_id,
		];

		$this->upload->shouldReceive( 'invalidate_user_avatar_cache' )->once()->with( $item_id );

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_upload( $item_id, $type, $args ) );
	}

	/**
	 * Tests ::invalidate_cache_after_avatar_upload.
	 *
	 * @covers ::invalidate_cache_after_avatar_upload
	 */
	public function test_invalidate_cache_after_avatar_upload_wrong_object_type() {
		// Input.
		$item_id = 42;
		$type    = 'crop';
		$args    = [
			'foo'     => 'bar',
			'object'  => 'group',
			'item_id' => $item_id,
		];

		$this->upload->shouldReceive( 'invalidate_user_avatar_cache' )->never();

		$this->assertNull( $this->sut->invalidate_cache_after_avatar_upload( $item_id, $type, $args ) );
	}
}
