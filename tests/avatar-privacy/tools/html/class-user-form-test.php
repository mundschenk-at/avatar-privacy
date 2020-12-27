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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\HTML;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Tools\HTML\User_Form;

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Exceptions\Form_Field_Not_Found_Exception;
use Avatar_Privacy\Exceptions\Invalid_Nonce_Exception;
use Avatar_Privacy\Tools\Template;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler as Upload;

/**
 * Avatar_Privacy\Tools\HTML\User_Form unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\HTML\User_Form
 * @usesDefaultClass \Avatar_Privacy\Tools\HTML\User_Form
 *
 * @uses ::__construct
 */
class User_Form_Test extends \Avatar_Privacy\Tests\TestCase {

	const USE_GRAVATAR_NONCE      = 'my_use_gravatar_nonce';
	const USE_GRAVATAR_ACTION     = 'my_use_gravatar_action';
	const USE_GRAVATAR_FIELD      = 'my_use_gravatar_checkbox_id';
	const USE_GRAVATAR_PARTIAL    = '/my/use_gravatar/partial.php';
	const ALLOW_ANON_NONCE        = 'my_uallow_anon_nonce';
	const ALLOW_ANON_ACTION       = 'my_allow_anon_action';
	const ALLOW_ANON_FIELD        = 'my_allow_anon_checkbox_id';
	const ALLOW_ANON_PARTIAL      = '/my/allow_anon/partial.php';
	const USER_AVATAR_NONCE       = 'my_user_avatar_nonce';
	const USER_AVATAR_ACTION      = 'my_user_avatar_action';
	const USER_AVATAR_FIELD       = 'my_user_avatar_file_selector_id';
	const USER_AVATAR_ERASE_FIELD = 'my_user_avatar_erase_checkbox_id';
	const USER_AVATAR_PARTIAL     = '/my/user_avatar/partial.php';

	/**
	 * The system-under-test.
	 *
	 * @var User_Form
	 */
	private $sut;

	/**
	 * The user avatar upload handler mock.
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * THe user fields API mock.
	 *
	 * @var User_Fields
	 */
	private $registered_user;

	/**
	 * The Template alias mock.
	 *
	 * @var Template;
	 */
	private $template;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin'    => [
				'some' => [
					'fake' => [
						'partial.php'    => 'MY_PARTIAL',
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->upload          = m::mock( Upload::class );
		$this->registered_user = m::mock( User_Fields::class );
		$this->template        = m::mock( Template::class );
		$this->sut             = m::mock( User_Form::class, [
			$this->upload,
			$this->registered_user,
			$this->template,
			[
				'nonce'   => self::USE_GRAVATAR_NONCE,
				'action'  => self::USE_GRAVATAR_ACTION,
				'field'   => self::USE_GRAVATAR_FIELD,
				'partial' => self::USE_GRAVATAR_PARTIAL,
			],
			[
				'nonce'   => self::ALLOW_ANON_NONCE,
				'action'  => self::ALLOW_ANON_ACTION,
				'field'   => self::ALLOW_ANON_FIELD,
				'partial' => self::ALLOW_ANON_PARTIAL,
			],
			[
				'nonce'   => self::USER_AVATAR_NONCE,
				'action'  => self::USER_AVATAR_ACTION,
				'field'   => self::USER_AVATAR_FIELD,
				'erase'   => self::USER_AVATAR_ERASE_FIELD,
				'partial' => self::USER_AVATAR_PARTIAL,
			],

		] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$upload          = m::mock( Upload::class );
		$registered_user = m::mock( User_Fields::class );
		$template        = m::mock( Template::class );
		$use_gravatar    = [ 'a' ]; // The contents don't matter here.
		$allow_anonymous = [ 'b' ]; // The contents don't matter here.
		$user_avatar     = [ 'c' ]; // The contents don't matter here.

		$mock = m::mock( User_Form::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->__construct( $upload, $registered_user, $template, $use_gravatar, $allow_anonymous, $user_avatar );

		$this->assert_attribute_same( $upload, 'upload', $mock );
		$this->assert_attribute_same( $registered_user, 'registered_user', $mock );
		$this->assert_attribute_same( $template, 'template', $mock );
		$this->assert_attribute_same( $use_gravatar, 'use_gravatar', $mock );
		$this->assert_attribute_same( $allow_anonymous, 'allow_anonymous', $mock );
		$this->assert_attribute_same( $user_avatar, 'user_avatar', $mock );
	}

	/**
	 * Tests ::use_gravatar_checkbox.
	 *
	 * @covers ::use_gravatar_checkbox
	 */
	public function test_use_gravatar_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::USE_GRAVATAR_NONCE;
		$action     = self::USE_GRAVATAR_ACTION;
		$field_name = self::USE_GRAVATAR_FIELD;
		$partial    = self::USE_GRAVATAR_PARTIAL;
		$args       = [
			'foo' => 'bar',
		];

		// Intermediate values.
		$value = 'my value'; // Would be `true` or `false` in reality, but as a marker we use this.

		$this->registered_user->shouldReceive( 'allows_gravatar_use' )->once()->with( $user_id )->andReturn( $value );
		$this->sut->shouldReceive( 'checkbox' )->once()->with(
			$value,
			"{$nonce}{$user_id}",
			$action,
			$field_name,
			$partial,
			m::type( 'array' )
		)->andReturnUsing(
			function() {
				echo 'USE_GRAVATAR';
			}
		);

		$this->expectOutputString( 'USE_GRAVATAR' );

		$this->assertNull( $this->sut->use_gravatar_checkbox( $user_id, $args ) );
	}

	/**
	 * Tests ::get_use_gravatar_checkbox.
	 *
	 * @covers ::get_use_gravatar_checkbox
	 */
	public function test_get_use_gravatar_checkbox() {
		// Input parameters.
		$user_id = 5;
		$args    = [
			'foo' => 'bar',
		];

		$this->sut->shouldReceive( 'use_gravatar_checkbox' )->once()->with( $user_id, $args )->andReturnUsing(
			function() {
				echo 'USE_GRAVATAR';
			}
		);

		$this->assertSame( 'USE_GRAVATAR', $this->sut->get_use_gravatar_checkbox( $user_id, $args ) );
	}

	/**
	 * Tests ::allow_anonymous_checkbox.
	 *
	 * @covers ::allow_anonymous_checkbox
	 */
	public function test_allow_anonymous_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::ALLOW_ANON_NONCE;
		$action     = self::ALLOW_ANON_ACTION;
		$field_name = self::ALLOW_ANON_FIELD;
		$partial    = self::ALLOW_ANON_PARTIAL;
		$args       = [
			'foo' => 'bar',
		];

		// Intermediate values.
		$value = 'my value'; // Would be `true` or `false` in reality, but as a marker we use this.

		$this->registered_user->shouldReceive( 'allows_anonymous_commenting' )->once()->with( $user_id )->andReturn( $value );
		$this->sut->shouldReceive( 'checkbox' )->once()->with(
			$value,
			"{$nonce}{$user_id}",
			$action,
			$field_name,
			$partial,
			$args
		)->andReturnUsing(
			function() {
				echo 'ALLOW_ANON';
			}
		);

		$this->expectOutputString( 'ALLOW_ANON' );

		$this->assertNull( $this->sut->allow_anonymous_checkbox( $user_id, $args ) );
	}

	/**
	 * Tests ::get_allow_anonymous_checkbox.
	 *
	 * @covers ::get_allow_anonymous_checkbox
	 */
	public function test_get_allow_anonymous_checkbox() {
		// Input parameters.
		$user_id = 5;
		$args    = [
			'foo' => 'bar',
		];

		$this->sut->shouldReceive( 'allow_anonymous_checkbox' )->once()->with( $user_id, $args )->andReturnUsing(
			function() {
				echo 'ALLOW_ANON';
			}
		);

		$this->assertSame( 'ALLOW_ANON', $this->sut->get_allow_anonymous_checkbox( $user_id, $args ) );
	}

	/**
	 * Tests ::avatar_uploader.
	 *
	 * @covers ::avatar_uploader
	 */
	public function test_avatar_uploader() {
		// Input parameters.
		$user_id = 5;
		$args    = [
			'avatar_size' => 666,
		];

		// Object state.
		$partial = self::USER_AVATAR_PARTIAL;

		// FIXME: We should check for template variables.
		Filters\expectApplied( 'avatar_privacy_profile_picture_upload_disabled' )->once()->with( false )->andReturn( false );

		$this->registered_user->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( [ 'fake avatar' ] );
		Functions\expect( 'current_user_can' )->once()->with( 'upload_files' )->andReturn( true );
		Functions\expect( 'wp_parse_args' )->once()->with(
			$args,
			[
				'avatar_size'       => 96,
				'show_descriptions' => true,
			]
		)->andReturnUsing( function( $args, $defaults ) {
			return \array_merge( $defaults, $args );
		} );

		$this->template->shouldReceive( 'print_partial' )->once()->with( $partial, m::type( 'array' ) );

		$this->assertNull( $this->sut->avatar_uploader( $user_id, $args ) );
	}

	/**
	 * Tests ::get_avatar_uploader.
	 *
	 * @covers ::get_avatar_uploader
	 */
	public function test_get_avatar_uploader() {
		// Input parameters.
		$user_id = 5;
		$args    = [
			'avatar_size' => 555,
		];

		$this->sut->shouldReceive( 'avatar_uploader' )->once()->with( $user_id, $args )->andReturnUsing(
			function() {
				echo 'UPLOADER';
			}
		);

		$this->assertSame( 'UPLOADER', $this->sut->get_avatar_uploader( $user_id, $args ) );
	}

	/**
	 * Tests ::checkbox.
	 *
	 * @covers ::checkbox
	 */
	public function test_checkbox() {
		// Input parameters.
		$value      = 'my value'; // Would be `true` or `false` in reality, but as a marker we use this.
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';
		$partial    = '/some/fake/partial.php';
		$args       = [
			'foo' => 'bar',
		];

		Functions\expect( 'wp_parse_args' )->once()->with( $args, [ 'show_descriptions' => true ] )->andReturnUsing( function( $args, $defaults ) {
			return \array_merge( $defaults, $args );
		} );

		$this->template->shouldReceive( 'print_partial' )->once()->with( $partial, m::type( 'array' ) );

		$this->assertNull( $this->sut->checkbox( $value, $nonce, $action, $field_name, $partial, $args ) );
	}

	/**
	 * Tests ::save_use_gravatar_checkbox.
	 *
	 * @covers ::save_use_gravatar_checkbox
	 */
	public function test_save_use_gravatar_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::USE_GRAVATAR_NONCE;
		$action     = self::USE_GRAVATAR_ACTION;
		$field_name = self::USE_GRAVATAR_FIELD;

		// Intermediate values.
		$value = 'my value'; // Would be `true` or `false` in reality, but as a marker we use this.

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andReturn( $value );
		$this->registered_user->shouldReceive( 'update_gravatar_use' )->once()->with( $user_id, $value );

		$this->assertNull( $this->sut->save_use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_use_gravatar_checkbox.
	 *
	 * @covers ::save_use_gravatar_checkbox
	 */
	public function test_save_use_gravatar_checkbox_invalid_nonce() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::USE_GRAVATAR_NONCE;
		$action     = self::USE_GRAVATAR_ACTION;
		$field_name = self::USE_GRAVATAR_FIELD;

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andThrow( Invalid_Nonce_Exception::class );
		$this->registered_user->shouldReceive( 'update_gravatar_use' )->never();

		$this->expect_exception( Invalid_Nonce_Exception::class );

		$this->assertNull( $this->sut->save_use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_use_gravatar_checkbox.
	 *
	 * @covers ::save_use_gravatar_checkbox
	 */
	public function test_save_use_gravatar_checkbox_no_form() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::USE_GRAVATAR_NONCE;
		$action     = self::USE_GRAVATAR_ACTION;
		$field_name = self::USE_GRAVATAR_FIELD;

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andThrow( Form_Field_Not_Found_Exception::class );
		$this->registered_user->shouldReceive( 'update_gravatar_use' )->never();

		$this->assertNull( $this->sut->save_use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_allow_anonymous_checkbox.
	 *
	 * @covers ::save_allow_anonymous_checkbox
	 */
	public function test_save_allow_anonymous_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::ALLOW_ANON_NONCE;
		$action     = self::ALLOW_ANON_ACTION;
		$field_name = self::ALLOW_ANON_FIELD;

		// Intermediate values.
		$value = 'my value'; // Would be `true` or `false` in reality, but as a marker we use this.

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andReturn( $value );
		$this->registered_user->shouldReceive( 'update_anonymous_commenting' )->once()->with( $user_id, $value );

		$this->assertNull( $this->sut->save_allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_allow_anonymous_checkbox.
	 *
	 * @covers ::save_allow_anonymous_checkbox
	 */
	public function test_save_allow_anonymous_checkbox_invalid_nonce() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::ALLOW_ANON_NONCE;
		$action     = self::ALLOW_ANON_ACTION;
		$field_name = self::ALLOW_ANON_FIELD;

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andThrow( Invalid_Nonce_Exception::class );
		$this->registered_user->shouldReceive( 'update_anonymous_commenting' )->never();

		$this->expect_exception( Invalid_Nonce_Exception::class );

		$this->assertNull( $this->sut->save_allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Tests ::save_allow_anonymous_checkbox.
	 *
	 * @covers ::save_allow_anonymous_checkbox
	 */
	public function test_save_allow_anonymous_checkbox_no_form() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::ALLOW_ANON_NONCE;
		$action     = self::ALLOW_ANON_ACTION;
		$field_name = self::ALLOW_ANON_FIELD;

		$this->sut->shouldReceive( 'get_submitted_checkbox_value' )->once()->with( "{$nonce}{$user_id}", $action, $field_name )->andThrow( Form_Field_Not_Found_Exception::class );
		$this->registered_user->shouldReceive( 'update_anonymous_commenting' )->never();

		$this->assertNull( $this->sut->save_allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Provides data for testing ::save_checkbox.
	 *
	 * @return array
	 */
	public function provide_get_submitted_checkbox_value_data() {
		return [
			[ true, 'true', true ],
			[ false, 'true', null ],
			[ null, 'true', null ],
			[ true, 'false', false ],
			[ true, 0, false ],
			[ true, 1, false ],
			[ true, null, null ],
			[ false, null, null ],
			[ null, null, null ],
		];
	}

	/**
	 * Tests ::get_submitted_checkbox_value.
	 *
	 * @covers ::get_submitted_checkbox_value
	 *
	 * @dataProvider provide_get_submitted_checkbox_value_data
	 *
	 * @param  bool|null   $verify       The result of verify_nonce (or null, if it should not be set at all).
	 * @param  string|null $checkbox     The checkbox value, or null if it should not be set at all.
	 * @param  string|null $result       The expected result.
	 */
	public function test_get_submitted_checkbox_value( $verify, $checkbox, $result ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input parameters.
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';

		// Set up fake request.
		$_POST = [];
		if ( null !== $checkbox ) {
			$_POST[ $field_name ] = $checkbox;
		}
		if ( null !== $verify ) {
			$nonce_value     = '12345';
			$_POST[ $nonce ] = $nonce_value;
		}

		// Great Expectations.
		if ( isset( $checkbox ) && ! empty( $nonce_value ) ) {
			Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
			Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( $verify );
		} else {
			Functions\expect( 'sanitize_key' )->never();
			Functions\expect( 'wp_verify_nonce' )->never();
		}

		if ( null === $checkbox ) {
			$this->expect_exception( Form_Field_Not_Found_Exception::class );
		} elseif ( ! $verify ) {
			$this->expect_exception( Invalid_Nonce_Exception::class );
		}

		$this->assertSame( $result, $this->sut->get_submitted_checkbox_value( $nonce, $action, $field_name ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar.
	 *
	 * @covers ::save_uploaded_user_avatar
	 */
	public function test_save_uploaded_user_avatar() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = self::USER_AVATAR_NONCE;
		$action     = self::USER_AVATAR_ACTION;
		$field_name = self::USER_AVATAR_FIELD;
		$erase_name = self::USER_AVATAR_ERASE_FIELD;

		$this->upload->shouldReceive( 'save_uploaded_user_avatar' )->once()->with( $user_id, $nonce, $action, $field_name, $erase_name );

		$this->assertNull( $this->sut->save_uploaded_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::save.
	 *
	 * @covers ::save
	 */
	public function test_save() {
		$user_id = 5;

		Functions\expect( 'current_user_can' )->once()->with( 'edit_user', $user_id )->andReturn( true );

		$this->sut->shouldReceive( 'save_use_gravatar_checkbox' )->once()->with( $user_id );
		$this->sut->shouldReceive( 'save_allow_anonymous_checkbox' )->once()->with( $user_id );
		$this->sut->shouldReceive( 'save_uploaded_user_avatar' )->once()->with( $user_id );

		$this->assertNull( $this->sut->save( $user_id ) );
	}

	/**
	 * Tests ::save.
	 *
	 * @covers ::save
	 */
	public function test_save_user_profile_fields_no_permissions() {
		$user_id = 5;

		Functions\expect( 'current_user_can' )->once()->with( 'edit_user', $user_id )->andReturn( false );

		$this->sut->shouldReceive( 'save_use_gravatar_checkbox' )->never();
		$this->sut->shouldReceive( 'save_allow_anonymous_checkbox' )->never();
		$this->sut->shouldReceive( 'save_uploaded_user_avatar' )->never();

		$this->assertNull( $this->sut->save( $user_id ) );
	}

	/**
	 * Tests ::process_form_submission.
	 *
	 * @covers ::process_form_submission
	 */
	public function test_process_form_submission() {
		$user_id = 5;

		Functions\expect( 'get_current_user_id' )->once()->andReturn( $user_id );

		$this->sut->shouldReceive( 'save' )->once()->with( $user_id );

		$this->assertNull( $this->sut->process_form_submission() );
	}

	/**
	 * Tests ::process_form_submission.
	 *
	 * @covers ::process_form_submission
	 */
	public function test_process_form_submission_not_logged_in() {
		Functions\expect( 'get_current_user_id' )->once()->andReturn( 0 );

		$this->sut->shouldReceive( 'save' )->never();

		$this->assertNull( $this->sut->process_form_submission() );
	}

	/**
	 * Tests ::register_form_submission.
	 *
	 * @covers ::register_form_submission
	 */
	public function test_register_form_submission() {
		$callable = [ $this->sut, 'process_form_submission' ];

		Functions\expect( 'has_action' )->once()->with( 'init', $callable )->andReturn( false );
		Functions\expect( 'add_action' )->once()->with( 'init', $callable );

		$this->assertNull( $this->sut->register_form_submission() );
	}

	/**
	 * Tests ::register_form_submission.
	 *
	 * @covers ::register_form_submission
	 */
	public function test_register_form_submission_already_registered() {
		$callable = [ $this->sut, 'process_form_submission' ];

		Functions\expect( 'has_action' )->once()->with( 'init', $callable )->andReturn( true );
		Functions\expect( 'add_action' )->never();

		$this->assertNull( $this->sut->register_form_submission() );
	}
}
