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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\User_Profile;

use Avatar_Privacy\Core;

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * Avatar_Privacy\Components\User_Profile unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\User_Profile
 * @usesDefaultClass \Avatar_Privacy\Components\User_Profile
 *
 * @uses ::__construct
 */
class User_Profile_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var User_Profile
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
	 */
	protected function setUp() {
		parent::setUp();

		$filesystem = [
			'plugin'    => [
				'admin' => [
					'partials' => [
						'profile' => [
							'use-gravatar.php'    => 'USE_GRAVATAR',
							'allow-anonymous.php' => 'ALLOW_ANONYMOUS',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->upload = m::mock( User_Avatar_Upload_Handler::class );

		$this->sut = m::mock( User_Profile::class, [ 'plugin/file', $this->upload ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( User_Profile::class )->makePartial();

		$mock->__construct( 'path/file', $this->upload );

		$this->assertAttributeSame( 'path/file', 'plugin_file', $mock );
		$this->assertAttributeSame( $this->upload, 'upload', $mock );
	}


	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		Actions\expectAdded( 'admin_init' )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_admin() {
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'admin_init' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::admin_init.
	 *
	 * @covers ::admin_init
	 */
	public function test_admin_init() {
		Actions\expectAdded( 'show_user_profile' )->once()->with( [ $this->sut, 'add_user_profile_fields' ] );
		Actions\expectAdded( 'edit_user_profile' )->once()->with( [ $this->sut, 'add_user_profile_fields' ] );
		Actions\expectAdded( 'personal_options_update' )->once()->with( [ $this->sut, 'save_user_profile_fields' ] );
		Actions\expectAdded( 'edit_user_profile_update' )->once()->with( [ $this->sut, 'save_user_profile_fields' ] );
		Actions\expectAdded( 'user_edit_form_tag' )->once()->with( [ $this->sut, 'print_form_encoding' ] );

		Actions\expectAdded( 'admin_head-profile.php' )->once()->with( [ $this->sut, 'admin_head' ] );
		Actions\expectAdded( 'admin_head-user-edit.php' )->once()->with( [ $this->sut, 'admin_head' ] );
		Actions\expectAdded( 'admin_footer-profile.php' )->once()->with( [ $this->sut, 'admin_footer' ] );
		Actions\expectAdded( 'admin_footer-user-edit.php' )->once()->with( [ $this->sut, 'admin_footer' ] );

		$this->assertNull( $this->sut->admin_init() );
	}

	/**
	 * Tests ::admin_head.
	 *
	 * @covers ::admin_head
	 *
	 * @uses ::replace_profile_picture_section
	 */
	public function test_admin_head() {
		$this->assertNull( $this->sut->admin_head() );
		// $this->assertAttributeSame( true, 'buffering', $this->sut );

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
		//$this->setValue( $this->sut, 'buffering', true, User_Profile::class );

		$this->assertNull( $this->sut->admin_footer() );
		//$this->assertAttributeSame( false, 'buffering', $this->sut );
	}

	/**
	 * Tests ::print_form_encoding.
	 *
	 * @covers ::print_form_encoding
	 */
	public function test_print_form_encoding() {

		$this->expectOutputString( ' enctype="multipart/form-data"' );

		$this->assertNull( $this->sut->print_form_encoding() );
	}

	/**
	 * Tests ::replace_profile_picture_section.
	 *
	 * @covers ::replace_profile_picture_section
	 */
	public function test_replace_profile_picture_section() {
		// Content should be unchanged, as `markup` is empty.
		$content = 'some content with <tr class="user-profile-picture">foobar</tr>';
		$this->assertSame( $content, $this->sut->replace_profile_picture_section( $content ) );

		// Set `markup`.
		$this->setValue( $this->sut, 'markup', 'FOOBAR', User_Profile::class );

		// Content should be unchanged because the pattern does not match.
		$content = 'some content with <tr class="foobar">foobar</tr>';
		$this->assertSame( $content, $this->sut->replace_profile_picture_section( $content ) );

		// Finally, the content should be modified.
		$content = 'some content with <tr class="user-profile-picture">foobar</tr>';
		$this->assertSame( 'some content with FOOBAR', $this->sut->replace_profile_picture_section( $content ) );
	}

	/**
	 * Tests ::add_user_profile_fields.
	 *
	 * @covers ::add_user_profile_fields
	 */
	public function test_add_user_profile_fields() {
		$user = m::mock( 'WP_User' );

		$this->upload->shouldReceive( 'get_avatar_upload_markup' )->once()->with( $user )->andReturn( 'FOO' );
		$this->sut->shouldReceive( 'get_use_gravatar_markup' )->once()->with( $user )->andReturn( 'BAR' );

		$this->assertNull( $this->sut->add_user_profile_fields( $user ) );
		$this->assertSame( 'FOOBAR', $this->getValue( $this->sut, 'markup', User_Profile::class ) );
	}

	/**
	 * Tests ::get_use_gravatar_markup.
	 *
	 * @covers ::get_use_gravatar_markup
	 */
	public function test_get_use_gravatar_markup() {
		$user     = m::mock( 'WP_User' );
		$user->ID = 5;

		Functions\expect( 'get_user_meta' )->once()->with( m::type( 'int' ), Core::GRAVATAR_USE_META_KEY, true )->andReturn( 'true' );
		Functions\expect( 'get_user_meta' )->once()->with( m::type( 'int' ), Core::ALLOW_ANONYMOUS_META_KEY, true )->andReturn( 'true' );

		$this->assertSame( 'USE_GRAVATARALLOW_ANONYMOUS', $this->sut->get_use_gravatar_markup( $user ) );
	}

	/**
	 * Tests ::save_user_profile_fields.
	 *
	 * @covers ::save_user_profile_fields
	 */
	public function test_save_user_profile_fields() {
		$user_id = 5;

		Functions\expect( 'current_user_can' )->once()->with( 'edit_user', $user_id )->andReturn( true );

		$this->sut->shouldReceive( 'save_use_gravatar_checkbox' )->once()->with( $user_id );
		$this->sut->shouldReceive( 'save_allow_anonymous_checkbox' )->once()->with( $user_id );
		$this->upload->shouldReceive( 'save_uploaded_user_avatar' )->once()->with( $user_id );

		$this->assertNull( $this->sut->save_user_profile_fields( $user_id ) );
	}

	/**
	 * Tests ::save_user_profile_fields.
	 *
	 * @covers ::save_user_profile_fields
	 */
	public function test_save_user_profile_fields_no_permissions() {
		$user_id = 5;

		Functions\expect( 'current_user_can' )->once()->with( 'edit_user', $user_id )->andReturn( false );

		$this->sut->shouldReceive( 'save_use_gravatar_checkbox' )->never();
		$this->sut->shouldReceive( 'save_allow_anonymous_checkbox' )->never();
		$this->upload->shouldReceive( 'save_uploaded_user_avatar' )->never();

		// FIXME: Should probably just return.
		$this->assertFalse( $this->sut->save_user_profile_fields( $user_id ) );
	}

	/**
	 * Provides data for testing save_use_gravatar_checkbox.
	 *
	 * @return array
	 */
	public function provide_save_some_checkbox_data() {
		return [
			[ true, 'true', 'true' ],
			[ false, 'true', null ],
			[ null, 'true', null ],
			[ true, 'false', 'false' ],
			[ true, 0, 'false' ],
		];
	}

	/**
	 * Tests ::save_use_gravatar_checkbox.
	 *
	 * @covers ::save_use_gravatar_checkbox
	 *
	 * @dataProvider provide_save_some_checkbox_data
	 *
	 * @param  bool|null   $verify       The result of verify_nonce (or null, if it should not be set at all).
	 * @param  string|null $checkbox     The checkbox value, or null if it should not be set at all.
	 * @param  string|null $result       The expected result for `use_gravatar` (or null if the value should not be updated).
	 */
	public function test_save_use_gravatar_checkbox( $verify, $checkbox, $result ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification

		// Input data.
		$user_id = 5;

		// Set up fake request.
		$nonce = '12345';
		$_POST = [];
		if ( null !== $checkbox ) {
			$_POST[ User_Profile::CHECKBOX_FIELD_NAME ] = $checkbox;
		}

		// Great Expectations.
		if ( null !== $verify ) {
			$_POST[ User_Profile::NONCE_USE_GRAVATAR . $user_id ] = $nonce;
			Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
			Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Profile::ACTION_EDIT_USE_GRAVATAR )->andReturn( $verify );
		} else {
			Functions\expect( 'sanitize_key' )->never();
			Functions\expect( 'wp_verify_nonce' )->never();
		}
		if ( null !== $result ) {
			Functions\expect( 'update_user_meta' )->once()->with( $user_id, Core::GRAVATAR_USE_META_KEY, $result );
		} else {
			Functions\expect( 'update_user_meta' )->never();
		}

		$this->assertNull( $this->sut->save_use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_allow_anonymous_checkbox.
	 *
	 * @covers ::save_allow_anonymous_checkbox
	 *
	 * @dataProvider provide_save_some_checkbox_data
	 *
	 * @param  bool|null   $verify       The result of verify_nonce (or null, if it should not be set at all).
	 * @param  string|null $checkbox     The checkbox value, or null if it should not be set at all.
	 * @param  string|null $result       The expected result for `use_gravatar` (or null if the value should not be updated).
	 */
	public function test_save_allow_anonymous_checkbox( $verify, $checkbox, $result ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification

		// Input data.
		$user_id = 5;

		// Set up fake request.
		$nonce = '12345';
		$_POST = [];
		if ( null !== $checkbox ) {
			$_POST[ User_Profile::CHECKBOX_ALLOW_ANONYMOUS ] = $checkbox;
		}

		// Great Expectations.
		if ( null !== $verify ) {
			$_POST[ User_Profile::NONCE_ALLOW_ANONYMOUS . $user_id ] = $nonce;
			Functions\expect( 'sanitize_key' )->once()->with( $nonce )->andReturn( 'sanitized_nonce' );
			Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', User_Profile::ACTION_EDIT_ALLOW_ANONYMOUS )->andReturn( $verify );
		} else {
			Functions\expect( 'sanitize_key' )->never();
			Functions\expect( 'wp_verify_nonce' )->never();
		}
		if ( null !== $result ) {
			Functions\expect( 'update_user_meta' )->once()->with( $user_id, Core::ALLOW_ANONYMOUS_META_KEY, $result );
		} else {
			Functions\expect( 'update_user_meta' )->never();
		}

		$this->assertNull( $this->sut->save_allow_anonymous_checkbox( $user_id ) );
	}
}
