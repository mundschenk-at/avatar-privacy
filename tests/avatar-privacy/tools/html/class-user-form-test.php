<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Core;
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

	/**
	 * The system-under-test.
	 *
	 * @var User_Form
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

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
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->sut = m::mock( User_Form::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$upload          = m::mock( Upload::class );
		$use_gravatar    = [ 'a' ]; // The contents don't matter here.
		$allow_anonymous = [ 'b' ]; // The contents don't matter here.
		$user_avatar     = [ 'c' ]; // The contents don't matter here.

		$mock = m::mock( User_Form::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->__construct( $upload, $use_gravatar, $allow_anonymous, $user_avatar );

		$this->assertAttributeSame( $upload, 'upload', $mock );
		$this->assertAttributeSame( $use_gravatar, 'use_gravatar', $mock );
		$this->assertAttributeSame( $allow_anonymous, 'allow_anonymous', $mock );
		$this->assertAttributeSame( $user_avatar, 'user_avatar', $mock );
	}

	/**
	 * Tests ::get_use_gravatar_checkbox.
	 *
	 * @covers ::get_use_gravatar_checkbox
	 */
	public function test_get_use_gravatar_checkbox() {
		// Input parameters.
		$user_id = 5;

		$this->sut->shouldReceive( 'use_gravatar_checkbox' )->once()->with( $user_id )->andReturnUsing(
			function() {
				echo 'USE_GRAVATAR';
			}
		);

		$this->assertSame( 'USE_GRAVATAR', $this->sut->get_use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::use_gravatar_checkbox.
	 *
	 * @covers ::use_gravatar_checkbox
	 */
	public function test_use_gravatar_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';
		$partial    = '/some/fake/partial.php';

		$this->sut->__construct(
			m::mock( Upload::class ), // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'partial' => $partial,
			],
			[], // Ignored for this testcase.
			[] // Ignored for this testcase.
		);

		$this->sut->shouldReceive( 'checkbox' )->once()->with(
			$user_id,
			$nonce,
			$action,
			$field_name,
			Core::GRAVATAR_USE_META_KEY,
			$partial
		)->andReturnUsing(
			function() {
				echo 'USE_GRAVATAR';
			}
		);

		$this->expectOutputString( 'USE_GRAVATAR' );

		$this->assertNull( $this->sut->use_gravatar_checkbox( $user_id ) );
	}

	/**
	 * Tests ::get_allow_anonymous_checkbox.
	 *
	 * @covers ::get_allow_anonymous_checkbox
	 */
	public function test_get_allow_anonymous_checkbox() {
		// Input parameters.
		$user_id = 5;

		$this->sut->shouldReceive( 'allow_anonymous_checkbox' )->once()->with( $user_id )->andReturnUsing(
			function() {
				echo 'ALLOW_ANON';
			}
		);

		$this->assertSame( 'ALLOW_ANON', $this->sut->get_allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Tests ::avatar_uploader.
	 *
	 * @covers ::avatar_uploader
	 */
	public function test_avatar_uploader() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_input_id';
		$erase_name = 'my_erase_checkbox_id';
		$partial    = '/some/fake/partial.php';

		$upload = m::mock( Upload::class );

		$this->sut->__construct(
			$upload,
			[], // Ignored for this testcase.
			[], // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'erase'   => $erase_name,
				'partial' => $partial,
			]
		);

		Functions\expect( 'get_user_meta' )->once()->with( $user_id,  Core::USER_AVATAR_META_KEY, true )->andReturn( [ 'fake avatar' ] );
		Functions\expect( 'current_user_can' )->once()->with( 'upload_files' )->andReturn( true );

		$this->expectOutputString( 'MY_PARTIAL' );

		$this->assertNull( $this->sut->avatar_uploader( $user_id ) );
	}

	/**
	 * Tests ::get_avatar_uploader.
	 *
	 * @covers ::get_avatar_uploader
	 */
	public function test_get_avatar_uploader() {
		// Input parameters.
		$user_id = 5;

		$this->sut->shouldReceive( 'avatar_uploader' )->once()->with( $user_id )->andReturnUsing(
			function() {
				echo 'UPLOADER';
			}
		);

		$this->assertSame( 'UPLOADER', $this->sut->get_avatar_uploader( $user_id ) );
	}


	/**
	 * Tests ::allow_anonymous_checkbox.
	 *
	 * @covers ::allow_anonymous_checkbox
	 */
	public function test_allow_anonymous_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';
		$partial    = '/some/fake/partial.php';

		$this->sut->__construct(
			m::mock( Upload::class ), // Ignored for this testcase.
			[],                       // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'partial' => $partial,
			],
			[] // Ignored for this testcase.
		);

		$this->sut->shouldReceive( 'checkbox' )->once()->with(
			$user_id,
			$nonce,
			$action,
			$field_name,
			Core::ALLOW_ANONYMOUS_META_KEY,
			$partial
		)->andReturnUsing(
			function() {
				echo 'ALLOW_ANON';
			}
		);

		$this->expectOutputString( 'ALLOW_ANON' );

		$this->assertNull( $this->sut->allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Tests ::checkbox.
	 *
	 * @covers ::checkbox
	 */
	public function test_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';
		$meta_key   = 'my_meta_key';
		$partial    = '/some/fake/partial.php';

		Functions\expect( 'get_user_meta' )->once()->with( $user_id, $meta_key, true )->andReturn( 'true' );

		$this->expectOutputString( 'MY_PARTIAL' );

		$this->assertNull( $this->sut->checkbox( $user_id, $nonce, $action, $field_name, $meta_key, $partial ) );
	}

	/**
	 * Tests ::save_use_gravatar_checkbox.
	 *
	 * @covers ::save_use_gravatar_checkbox
	 */
	public function test_save_use_gravatar_checkbox() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';

		$this->sut->__construct(
			m::mock( Upload::class ), // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'partial' => '', // Ignored for this testcase.
			],
			[], // Ignored for this testcase.
			[] // Ignored for this testcase.
		);

		$this->sut->shouldReceive( 'save_checkbox' )->once()->with(
			$user_id,
			$nonce,
			$action,
			$field_name,
			Core::GRAVATAR_USE_META_KEY
		);

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
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';

		$this->sut->__construct(
			m::mock( Upload::class ), // Ignored for this testcase.
			[],                       // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'partial' => '', // Ignored for this testcase.
			],
			[] // Ignored for this testcase.
		);

		$this->sut->shouldReceive( 'save_checkbox' )->once()->with(
			$user_id,
			$nonce,
			$action,
			$field_name,
			Core::ALLOW_ANONYMOUS_META_KEY
		);

		$this->assertNull( $this->sut->save_allow_anonymous_checkbox( $user_id ) );
	}

	/**
	 * Provides data for testing ::save_checkbox.
	 *
	 * @return array
	 */
	public function provide_save_checkbox_data() {
		return [
			[ true, 'true', 'true' ],
			[ false, 'true', null ],
			[ null, 'true', null ],
			[ true, 'false', 'false' ],
			[ true, 0, 'false' ],
		];
	}

	/**
	 * Tests ::save_checkbox.
	 *
	 * @covers ::save_checkbox
	 *
	 * @dataProvider provide_save_checkbox_data
	 *
	 * @param  bool|null   $verify       The result of verify_nonce (or null, if it should not be set at all).
	 * @param  string|null $checkbox     The checkbox value, or null if it should not be set at all.
	 * @param  string|null $result       The expected result for `use_gravatar` (or null if the value should not be updated).
	 */
	public function test_save_checkbox( $verify, $checkbox, $result ) {
		global $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_checkbox_id';
		$meta_key   = 'my_meta_key';

		// Set up fake request.
		$nonce_value = '12345';
		$_POST       = [];
		if ( null !== $checkbox ) {
			$_POST[ $field_name ] = $checkbox;
		}

		// Great Expectations.
		if ( null !== $verify ) {
			$_POST[ "{$nonce}{$user_id}" ] = $nonce_value;
			Functions\expect( 'sanitize_key' )->once()->with( $nonce_value )->andReturn( 'sanitized_nonce' );
			Functions\expect( 'wp_verify_nonce' )->once()->with( 'sanitized_nonce', $action )->andReturn( $verify );
		} else {
			Functions\expect( 'sanitize_key' )->never();
			Functions\expect( 'wp_verify_nonce' )->never();
		}
		if ( null !== $result ) {
			Functions\expect( 'update_user_meta' )->once()->with( $user_id, $meta_key, $result );
		} else {
			Functions\expect( 'update_user_meta' )->never();
		}

		$this->assertNull( $this->sut->save_checkbox( $user_id, $nonce, $action, $field_name, $meta_key ) );
	}

	/**
	 * Tests ::save_uploaded_user_avatar.
	 *
	 * @covers ::save_uploaded_user_avatar
	 */
	public function test_save_uploaded_user_avatar() {
		// Input parameters.
		$user_id    = 5;
		$nonce      = 'my_nonce';
		$action     = 'my_action';
		$field_name = 'my_input_id';
		$erase_name = 'my_erase_checkbox_id';
		$partial    = '/some/fake/partial.php';

		$upload = m::mock( Upload::class );

		$this->sut->__construct(
			$upload,
			[], // Ignored for this testcase.
			[], // Ignored for this testcase.
			[
				'nonce'   => $nonce,
				'action'  => $action,
				'field'   => $field_name,
				'erase'   => $erase_name,
				'partial' => $partial,
			]
		);

		$upload->shouldReceive( 'save_uploaded_user_avatar' )->once()->with( $user_id, $nonce, $action, $field_name, $erase_name );

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
}
