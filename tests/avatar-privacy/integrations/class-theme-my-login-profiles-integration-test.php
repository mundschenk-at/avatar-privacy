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

use Avatar_Privacy\Integrations\Theme_My_Login_Profiles_Integration;

use Avatar_Privacy\Core;
use Avatar_Privacy\Tools\HTML\User_Form;


/**
 * Avatar_Privacy\Integrations\Theme_My_Login_Profiles_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\Theme_My_Login_Profiles_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\Theme_My_Login_Profiles_Integration
 *
 * @uses ::__construct
 */
class Theme_My_Login_Profiles_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Theme_My_Login_Profiles_Integration
	 */
	private $sut;

	/**
	 * A test fixture.
	 *
	 * @var User_Form
	 */
	private $form;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->form = m::mock( User_Form::class );

		$this->sut = m::mock( Theme_My_Login_Profiles_Integration::class, [ $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Theme_My_Login_Profiles_Integration::class )->makePartial();

		$mock->__construct( $this->form );

		$this->assert_attribute_same( $this->form, 'form', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		$this->assertFalse( $this->sut->check() );

		$fake_plugin = m::mock( \Theme_My_Login_Profiles::class );
		Functions\when( 'tml_get_form' )->justReturn( null );
		Functions\when( 'tml_add_form_field' )->justReturn( null );

		$this->assertTrue( $this->sut->check() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'integrate_with_theme_my_login' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::integrate_with_theme_my_login.
	 *
	 * @covers ::integrate_with_theme_my_login
	 */
	public function test_integrate_with_theme_my_login() {
		// Mocked objects.
		$profile_form = m::mock( \Theme_My_Login_Form::class );
		$field        = m::mock( \Theme_My_Login_Form_Field::class );

		Functions\expect( 'tml_get_form' )->once()->with( 'profile' )->andReturn( $profile_form );

		$profile_form->shouldReceive( 'get_field' )->once()->with( 'avatar' )->andReturn( $field );
		$profile_form->shouldReceive( 'add_attribute' )->once()->with( 'enctype', 'multipart/form-data' );

		$field->shouldReceive( 'set_content' )->once()->with( [ $this->sut, 'render_avatar_field' ] );

		Functions\expect( 'tml_add_form_field' )->once()->with( $profile_form, 'avatar_privacy_use_gravatar', m::type( 'array' ) );
		Functions\expect( 'tml_add_form_field' )->once()->with( $profile_form, 'avatar_privacy_allow_anonymous', m::type( 'array' ) );

		Actions\expectAdded( 'personal_options_update' )->once()->with( [ $this->form, 'save' ] );

		$this->assertNull( $this->sut->integrate_with_theme_my_login() );
	}

	/**
	 * Tests ::integrate_with_theme_my_login.
	 *
	 * @covers ::integrate_with_theme_my_login
	 */
	public function test_integrate_with_theme_my_login_no_form() {
		Functions\expect( 'tml_get_form' )->once()->with( 'profile' )->andReturn( false );
		Functions\expect( 'tml_add_form_field' )->never();
		Functions\expect( 'tml_add_form_field' )->never();

		Actions\expectAdded( 'personal_options_update' )->never();

		$this->assertNull( $this->sut->integrate_with_theme_my_login() );
	}

	/**
	 * Tests ::integrate_with_theme_my_login.
	 *
	 * @covers ::integrate_with_theme_my_login
	 */
	public function test_integrate_with_theme_my_login_no_field() {
		// Mocked objects.
		$profile_form = m::mock( \Theme_My_Login_Form::class );

		Functions\expect( 'tml_get_form' )->once()->with( 'profile' )->andReturn( $profile_form );

		$profile_form->shouldReceive( 'get_field' )->once()->with( 'avatar' )->andReturn( false );
		$profile_form->shouldReceive( 'add_attribute' )->never();

		Functions\expect( 'tml_add_form_field' )->never();
		Functions\expect( 'tml_add_form_field' )->never();

		Actions\expectAdded( 'personal_options_update' )->never();

		$this->assertNull( $this->sut->integrate_with_theme_my_login() );
	}

	/**
	 * Tests ::render_avatar_field.
	 *
	 * @covers ::render_avatar_field
	 */
	public function test_render_avatar_field() {
		$user_id = 42;
		$result  = 'AVATAR_UPLOAD';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->form->shouldReceive( 'get_avatar_uploader' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->render_avatar_field() );
	}

	/**
	 * Tests ::render_use_gravatar_checkbox.
	 *
	 * @covers ::render_use_gravatar_checkbox
	 */
	public function test_render_use_gravatar_checkbox() {
		$user_id = 42;
		$result  = 'AVATAR_USE_GRAVATAR';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->form->shouldReceive( 'get_use_gravatar_checkbox' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->render_use_gravatar_checkbox() );
	}

	/**
	 * Tests ::render_allow_anonymous_checkbox.
	 *
	 * @covers ::render_allow_anonymous_checkbox
	 */
	public function test_render_allow_anonymous_checkbox() {
		$user_id = 42;
		$result  = 'AVATAR_ALLOW_ANONYMOUS';

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->form->shouldReceive( 'get_allow_anonymous_checkbox' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, $this->sut->render_allow_anonymous_checkbox() );
	}
}
