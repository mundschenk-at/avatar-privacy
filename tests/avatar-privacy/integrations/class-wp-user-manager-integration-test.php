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
	 * @var User_Profile
	 */
	private $profile;

	/**
	 * Mocked helper object.
	 *
	 * @var User_Avatar_Upload_Handler
	 */
	private $upload;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->profile = m::mock( User_Profile::class );
		$this->upload  = m::mock( User_Avatar_Upload_Handler::class );

		$this->sut = m::mock( WP_User_Manager_Integration::class, [ $this->profile, $this->upload ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( WP_User_Manager_Integration::class )->makePartial();

		$mock->__construct( $this->profile, $this->upload );

		$this->assertAttributeSame( $this->profile, 'profile', $mock );
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
		$core = m::mock( \Avatar_Privacy\Core::class );

		Functions\expect( 'is_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'admin_init' )->never();

		$this->assertNull( $this->sut->run( $core ) );
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
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_admin() {
		$core = m::mock( \Avatar_Privacy\Core::class );

		Functions\expect( 'is_admin' )->once()->andReturn( true );

		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'remove_profile_picture_upload' ] );

		$this->assertNull( $this->sut->run( $core ) );
	}

	/**
	 * Tests ::admin_head.
	 *
	 * @covers ::admin_head
	 */
	public function test_admin_head() {
		$this->assertNull( $this->sut->admin_head() );
		$this->assertAttributeSame( true, 'buffering', $this->sut );

		$this->sut->shouldReceive( 'remove_profile_picture_section' )->once();

		// Clean up.
		\ob_end_flush();
	}

	/**
	 * Tests ::admin_footer.
	 *
	 * @covers ::admin_footer
	 */
	public function test_admin_footer() {
		// Fake settings_head.
		\ob_start();
		$this->setValue( $this->sut, 'buffering', true, WP_User_Manager_Integration::class );

		$this->assertNull( $this->sut->admin_footer() );
		$this->assertAttributeSame( false, 'buffering', $this->sut );
	}


	/**
	 * Tests ::remove_profile_picture_section.
	 *
	 * @covers ::remove_profile_picture_section
	 */
	public function test_remove_profile_picture_section() {
		// Content should be unchanged, as `markup` is empty.
		$content = 'some content with <tr class="user-profile-picture">foobar</tr>';
		$this->assertSame( $content, $this->sut->remove_profile_picture_section( $content ) );

		// Content should be unchanged because the pattern does not match.
		$content = 'some content with <tr class="foobar">foobar<p class="description">FOOBAR</p></tr>';
		$this->assertSame( $content, $this->sut->remove_profile_picture_section( $content ) );

		// Finally, the content should be modified.
		$content = 'some content with <tr class="user-profile-picture">foobar<p class="description">FOOBAR</p></tr>';
		$this->assertSame( 'some content with <tr class="user-profile-picture">foobar<p class="description"></p></tr>', $this->sut->remove_profile_picture_section( $content ) );
	}

	/**
	 * Tests ::remove_profile_picture_upload.
	 *
	 * @covers ::remove_profile_picture_upload
	 */
	public function test_remove_profile_picture_upload() {
		Functions\expect( 'remove_action' )->once()->with( 'admin_head-profile.php', [ $this->profile, 'admin_head' ] );
		Functions\expect( 'remove_action' )->once()->with( 'admin_head-user-edit.php', [ $this->profile, 'admin_head' ] );
		Functions\expect( 'remove_action' )->once()->with( 'admin_footer-profile.php', [ $this->profile, 'admin_footer' ] );
		Functions\expect( 'remove_action' )->once()->with( 'admin_footer-user-edit.php', [ $this->profile, 'admin_footer' ] );

		Actions\expectAdded( 'admin_head-profile.php' )->once()->with( [ $this->sut, 'admin_head' ] );
		Actions\expectAdded( 'admin_head-user-edit.php' )->once()->with( [ $this->sut, 'admin_head' ] );
		Actions\expectAdded( 'admin_footer-profile.php' )->once()->with( [ $this->sut, 'admin_footer' ] );
		Actions\expectAdded( 'admin_footer-user-edit.php' )->once()->with( [ $this->sut, 'admin_footer' ] );

		$this->assertNull( $this->sut->remove_profile_picture_upload() );
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

		$this->setValue( $this->sut, 'flush_cache', true, WP_User_Manager_Integration::class );

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

		$this->setValue( $this->sut, 'flush_cache', false, WP_User_Manager_Integration::class );

		$this->upload->shouldReceive( 'invalidate_user_avatar_cache' )->never();

		$this->assertNull( $this->sut->maybe_flush_cache_after_saving_user_avatar( $user_id ) );
	}
}
