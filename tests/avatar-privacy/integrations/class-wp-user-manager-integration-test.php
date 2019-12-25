<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Avatar_Privacy\Integrations\WP_User_Manager_Integration;

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\User_Profile;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;


/**
 * Avatar_Privacy\Integrations\WP_User_Manager_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\WP_User_Manager_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\WP_User_Manager_Integration
 *
 * @uses ::__construct
 */
class WP_User_Manager_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var WP_User_Manager_Integration
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

		$this->sut = m::mock( WP_User_Manager_Integration::class, [ $this->upload ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( WP_User_Manager_Integration::class )->makePartial();

		$mock->__construct( $this->upload );

		$this->assertAttributeSame( $this->upload, 'upload', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		$this->assertFalse( $this->sut->check() );

		$fake_plugin = m::mock( \WP_User_Manager::class );
		Functions\when( 'wpum_get_option' )->justReturn( true );

		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {

		Filters\expectAdded( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( '__return_true' );
		Filters\expectAdded( 'avatar_privacy_pre_get_user_avatar' )->once()->with( [ $this->sut, 'enable_wpusermanager_user_avatars' ], 10, 2 );
		Filters\expectAdded( 'carbon_fields_should_save_field_value' )->once()->with( [ $this->sut, 'maybe_mark_user_avater_for_cache_flushing' ], 9999, 3 );

		Actions\expectAdded( 'carbon_fields_user_meta_container_saved' )->once()->with( [ $this->sut, 'maybe_flush_cache_after_saving_user_avatar' ], 10, 1 );

		$this->assertNull( $this->sut->run() );
	}


	/**
	 * Tests ::enable_wpusermanager_user_avatars.
	 *
	 * @covers ::enable_wpusermanager_user_avatars
	 */
	public function test_enable_wpusermanager_user_avatars() {
		$user_id = 42;
		$file    = '/some/file';
		$type    = 'mime/type';
		$result  = [
			'file' => $file,
			'type' => $type,
		];

		Functions\expect( 'carbon_get_user_meta' )->once()->with( $user_id, WP_User_Manager_Integration::WP_USER_MANAGER_META_KEY )->andReturn( $file );
		Functions\expect( 'wp_check_filetype' )->once()->with( $file )->andReturn( [ 'type' => $type ] );

		$this->assertSame( $result, $this->sut->enable_wpusermanager_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::maybe_mark_user_avater_for_cache_flushing.
	 *
	 * @covers ::maybe_mark_user_avater_for_cache_flushing
	 */
	public function test_maybe_mark_user_avater_for_cache_flushing() {
		$field = m::mock( \Carbon_Fields\Field\Field::class );
		$save  = true;
		$value = 'some value';

		$field->shouldReceive( 'get_base_name' )->once()->andReturn( WP_User_Manager_Integration::WP_USER_MANAGER_META_KEY );

		$this->assertSame( $save, $this->sut->maybe_mark_user_avater_for_cache_flushing( $save, $value, $field ) );

		$this->assertAttributeSame( true, 'flush_cache', $this->sut );
	}

	/**
	 * Tests ::maybe_mark_user_avater_for_cache_flushing.
	 *
	 * @covers ::maybe_mark_user_avater_for_cache_flushing
	 */
	public function test_maybe_mark_user_avater_for_cache_flushing_wrong_field() {
		$field = m::mock( \Carbon_Fields\Field\Field::class );
		$save  = true;
		$value = 'some value';

		$field->shouldReceive( 'get_base_name' )->once()->andReturn( 'some_other_field' );

		$this->assertSame( $save, $this->sut->maybe_mark_user_avater_for_cache_flushing( $save, $value, $field ) );

		$this->assertAttributeSame( false, 'flush_cache', $this->sut );
	}

	/**
	 * Tests ::maybe_flush_cache_after_saving_user_avatar.
	 *
	 * @covers ::maybe_flush_cache_after_saving_user_avatar
	 */
	public function test_maybe_flush_cache_after_saving_user_avatar() {
		$user_id = 42;

		$this->set_value( $this->sut, 'flush_cache', true );

		$this->upload->shouldReceive( 'invalidate_user_avatar_cache' )->once()->with( $user_id );

		$this->assertNull( $this->sut->maybe_flush_cache_after_saving_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::maybe_flush_cache_after_saving_user_avatar.
	 *
	 * @covers ::maybe_flush_cache_after_saving_user_avatar
	 */
	public function test_maybe_flush_cache_after_saving_user_avatar_do_not_flush() {
		$user_id = 42;

		$this->set_value( $this->sut, 'flush_cache', false );

		$this->upload->shouldReceive( 'invalidate_user_avatar_cache' )->never();

		$this->assertNull( $this->sut->maybe_flush_cache_after_saving_user_avatar( $user_id ) );
	}
}
