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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\User_Profile;

use Avatar_Privacy\Core;
use Avatar_Privacy\Tools\HTML\User_Form;

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

		$this->sut = m::mock( User_Profile::class, [ $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( User_Profile::class )->makePartial();

		$mock->__construct( $this->form );

		$this->assert_attribute_same( $this->form, 'form', $mock );
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
		Actions\expectAdded( 'user_edit_form_tag' )->once()->with( [ $this->sut, 'print_form_encoding' ] );
		Actions\expectAdded( 'show_user_profile' )->once()->with( [ $this->sut, 'add_user_profile_fields' ] );
		Actions\expectAdded( 'edit_user_profile' )->once()->with( [ $this->sut, 'add_user_profile_fields' ] );
		Actions\expectAdded( 'personal_options_update' )->once()->with( [ $this->form, 'save' ] );
		Actions\expectAdded( 'edit_user_profile_update' )->once()->with( [ $this->form, 'save' ] );

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
		$this->assert_attribute_same( true, 'buffering', $this->sut );

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
		$this->set_value( $this->sut, 'buffering', true );

		$this->assertNull( $this->sut->admin_footer() );
		$this->assert_attribute_same( false, 'buffering', $this->sut );
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
		$this->set_value( $this->sut, 'markup', 'FOOBAR' );

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
		$user     = m::mock( 'WP_User' );
		$user->ID = '55';

		$this->form->shouldReceive( 'get_avatar_uploader' )->once()->with( $user->ID )->andReturn( 'FOO' );
		$this->form->shouldReceive( 'get_use_gravatar_checkbox' )->once()->with( $user->ID )->andReturn( 'BAR' );
		$this->form->shouldReceive( 'get_allow_anonymous_checkbox' )->once()->with( $user->ID )->andReturn( 'BAZ' );

		$this->assertNull( $this->sut->add_user_profile_fields( $user ) );
		$this->assertSame( 'FOOBARBAZ', $this->get_value( $this->sut, 'markup' ) );
	}
}
