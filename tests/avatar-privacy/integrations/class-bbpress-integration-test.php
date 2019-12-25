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
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Integrations\BBPress_Integration;

use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * Avatar_Privacy\Integrations\BBPress_Integration unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Integrations\BBPress_Integration
 * @usesDefaultClass \Avatar_Privacy\Integrations\BBPress_Integration
 *
 * @uses ::__construct
 */
class BBPress_Integration_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Integrations\BBPress_Integration
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

		$filesystem = [
			'uploads' => [
				'delete' => [
					'existing_file.txt'  => 'CONTENT',
				],
			],
			'plugin'  => [
				'public' => [
					'partials' => [
						'bbpress' => [
							'user-profile-picture.php' => 'PROFILE_PICTURE_MARKUP',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// User form helper mock.
		$this->form = m::mock( User_Form::class );

		// Partially mock system under test.
		$this->sut = m::mock( BBPress_Integration::class, [ $this->form ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( BBPress_Integration::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$form = m::mock( User_Form::class );

		$mock->__construct( $form );

		$this->assertAttributeSame( $form, 'form', $mock );
	}

	/**
	 * Tests ::check.
	 *
	 * @covers ::check
	 */
	public function test_check() {
		$this->assertFalse( $this->sut->check() );

		Functions\when( 'is_bbpress' )->justReturn( true );

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
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'init' ] );

		$this->assertNull( $this->sut->run( $core ) );
	}

	/**
	 * Tests ::init.
	 *
	 * @covers ::init
	 */
	public function test_init() {
		Filters\expectAdded( 'avatar_privacy_parse_id_or_email' )->once()->with( [ $this->sut, 'parse_id_or_email' ] );
		Actions\expectAdded( 'bbp_user_edit_after' )->once()->with( [ $this->sut, 'add_user_profile_fields' ] );
		Actions\expectAdded( 'personal_options_update' )->once()->with( [ $this->form, 'save' ] );
		Actions\expectAdded( 'edit_user_profile_update' )->once()->with( [ $this->form, 'save' ] );

		$this->assertNull( $this->sut->init() );
	}

	/**
	 * Provides data for testing parse_id_or_email.
	 *
	 * @return array
	 */
	public function provide_parse_id_or_email_data() {
		return [
			[
				[ false, 'foo@bar.com', 0 ],
				true,
				(object) [ 'ID' => 60 ],
				[ 60, 'foo@bar.com', 0 ],
			],
			[
				[ false, 'foo@bar.com', 0 ],
				false,
				(object) [ 'ID' => 60 ],
				[ false, 'foo@bar.com', 0 ],
			],
			[
				[ 70, 'foo@bar.com', 0 ],
				true,
				(object) [ 'ID' => 99 ],
				[ 70, 'foo@bar.com', 0 ],
			],
			[
				[ 70, 'foo@bar.com', 0 ],
				false,
				(object) [ 'ID' => 99 ],
				[ 70, 'foo@bar.com', 0 ],
			],
			[
				[ false, 'foo@bar.com', 0 ],
				true,
				false,
				[ false, 'foo@bar.com', 0 ],
			],
		];
	}

	/**
	 * Tests ::parse_id_or_email.
	 *
	 * @covers ::parse_id_or_email
	 *
	 * @dataProvider provide_parse_id_or_email_data
	 *
	 * @param  array   $data              An array of $user_id, $email, $age. Input parameter.
	 * @param  boolean $is_bbpress        The result of is_bbpress().
	 * @param  mixed   $get_user_by_email The result of get_usyer_by_email().
	 * @param  array   $result            The expected result.
	 */
	public function test_parse_id_or_email( $data, $is_bbpress, $get_user_by_email, $result ) {
		list( $user_id, $email, $age ) = $data;

		Functions\expect( 'is_bbpress' )->once()->andReturn( $is_bbpress );

		if ( $is_bbpress && false === $user_id ) {
			Functions\expect( 'get_user_by' )->once()->with( 'email', $email )->andReturn( $get_user_by_email );
		}

		$this->assertSame( $result, $this->sut->parse_id_or_email( $data ) );
	}

	/**
	 * Tests ::add_user_profile_fields.
	 *
	 * @covers ::add_user_profile_fields
	 */
	public function test_add_user_profile_fields() {
		$user_id      = 5;
		$use_gravatar = 'true';

		Functions\expect( 'bbp_get_user_id' )->once()->with( 0, true, false )->andReturn( $user_id );

		$this->expectOutputString( 'PROFILE_PICTURE_MARKUP' );
		$this->assertNull( $this->sut->add_user_profile_fields() );
	}

	/**
	 * Tests ::add_user_profile_fields.
	 *
	 * @covers ::add_user_profile_fields
	 */
	public function test_add_user_profile_fields_failure() {
		Functions\expect( 'bbp_get_user_id' )->once()->with( 0, true, false )->andReturn( false );

		$this->expectOutputString( '' );
		$this->assertNull( $this->sut->add_user_profile_fields() );
	}
}
