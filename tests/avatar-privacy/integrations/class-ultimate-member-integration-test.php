<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2023 Peter Putzer.
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

use Avatar_Privacy\Integrations\Ultimate_Member_Integration;

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;


/**
 * Avatar_Privacy\Integrations\Ultimate_Member_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\Ultimate_Member_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\Ultimate_Member_Integration
 *
 * @uses ::__construct
 */
class Ultimate_Member_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Ultimate_Member_Integration
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

		$this->sut = m::mock( Ultimate_Member_Integration::class, [ $this->upload ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Ultimate_Member_Integration::class )->makePartial();

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

		$fake_plugin = m::mock( \UM::class ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		Functions\when( 'um_get_user_avatar_data' )->justReturn( true );

		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'integrate_with_ultimate_member_avatars' ] );

		Filters\expectAdded( 'um_settings_structure' )->once()->with( [ $this->sut, 'remove_ultimate_member_gravatar_settings' ], 10, 1 );
		Filters\expectAdded( 'um_options_use_gravatars' )->once()->with( '__return_false', 10, 1 );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::integrate_with_ultimate_member_avatars.
	 *
	 * @covers ::integrate_with_ultimate_member_avatars
	 */
	public function test_integrate_with_ultimate_member_avatars() {

		Filters\expectRemoved( 'get_avatar' )->once()->with( 'um_get_avatar', m::type( 'int' ) );
		Filters\expectAdded( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( '__return_true', 10, 0 );
		Filters\expectAdded( 'avatar_privacy_pre_get_user_avatar' )->once()->with( [ $this->sut, 'enable_ultimate_member_user_avatars' ], 10, 2 );
		Actions\expectAdded( 'um_after_upload_db_meta_profile_photo' )->once()->with( [ $this->upload, 'invalidate_user_avatar_cache' ], 10, 1 );

		$this->assertNull( $this->sut->integrate_with_ultimate_member_avatars() );
	}

	/**
	 * Tests ::remove_ultimate_member_gravatar_settings.
	 *
	 * @covers ::remove_ultimate_member_gravatar_settings
	 */
	public function test_remove_ultimate_member_gravatar_settings() {
		$structure = [
			'' => [
				'sections' => [
					'foo'   => [],
					'users' => [
						'fields' => [
							[ 'id' => 'foobar' ],
							[ 'id' => 'use_gravatars' ],
							[ 'id' => 'barfoo' ],
						],
					],
				],
			],
		];

		$conditional = [ 'avatar_privacy_active', '=', 0 ];

		$result = $this->sut->remove_ultimate_member_gravatar_settings( $structure );

		$this->assert_is_array( $result );
		$this->assertNotEmpty( $result['']['sections']['users']['fields'] );
		$this->assert_is_array( $result['']['sections']['users']['fields'] );
		$this->assertContains( $conditional, $result['']['sections']['users']['fields'][1] );
	}

	/**
	 * Tests ::enable_ultimate_member_user_avatars.
	 *
	 * @covers ::enable_ultimate_member_user_avatars
	 */
	public function test_enable_ultimate_member_user_avatars() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file      = 'some/file';
		$url       = "https://foobar/{$file}";
		$type      = 'mime/type';
		$um_avatar = [
			'url'  => $url,
			'type' => 'upload',
		];
		$absfile   = \ABSPATH . $file;

		// Expected result.
		$result = [
			'file' => $absfile,
			'type' => $type,
		];

		Filters\expectAdded( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );
		Functions\expect( 'um_get_user_avatar_data' )->once()->with( $user_id, 'original' )->andReturn( $um_avatar );
		Filters\expectRemoved( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );

		Functions\expect( 'wp_make_link_relative' )->once()->with( $url )->andReturn( $file );
		Functions\expect( 'wp_check_filetype' )->once()->with( $absfile )->andReturn( [ 'type' => $type ] );

		$this->assertSame( $result, $this->sut->enable_ultimate_member_user_avatars( null, $user_id ) );
	}

	/**
	 * Tests ::enable_ultimate_member_user_avatars.
	 *
	 * @covers ::enable_ultimate_member_user_avatars
	 */
	public function test_enable_ultimate_member_user_avatars_no_avatar() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file      = 'some/file';
		$url       = "https://foobar/{$file}";
		$um_avatar = [
			'url'  => $url,
		];

		Filters\expectAdded( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );
		Functions\expect( 'um_get_user_avatar_data' )->once()->with( $user_id, 'original' )->andReturn( $um_avatar );
		Filters\expectRemoved( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );

		Functions\expect( 'wp_make_link_relative' )->never();
		Functions\expect( 'wp_check_filetype' )->never();

		$this->assertNull( $this->sut->enable_ultimate_member_user_avatars( null, $user_id ) );
	}

		/**
	 * Tests ::enable_ultimate_member_user_avatars.
	 *
	 * @covers ::enable_ultimate_member_user_avatars
	 */
	public function test_enable_ultimate_member_user_avatars_failed_mimetype_check() {
		// Input.
		$user_id = 42;

		// Intermediary.
		$file      = 'some/file';
		$url       = "https://foobar/{$file}";
		$type      = 'mime/type';
		$um_avatar = [
			'url'  => $url,
			'type' => 'upload',
		];
		$absfile   = \ABSPATH . $file;

		Filters\expectAdded( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );
		Functions\expect( 'um_get_user_avatar_data' )->once()->with( $user_id, 'original' )->andReturn( $um_avatar );
		Filters\expectRemoved( 'um_filter_avatar_cache_time' )->once()->with( '__return_null' );

		Functions\expect( 'wp_make_link_relative' )->once()->with( $url )->andReturn( $file );
		Functions\expect( 'wp_check_filetype' )->once()->with( $absfile )->andReturn( [ 'type' => false ] );

		$this->assertNull( $this->sut->enable_ultimate_member_user_avatars( null, $user_id ) );
	}
}
