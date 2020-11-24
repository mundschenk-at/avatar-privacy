<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\CLI;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tests\Avatar_Privacy\CLI\TestCase;

use Avatar_Privacy\CLI\User_Command;
use Avatar_Privacy\Core\User_Fields;

/**
 * Avatar_Privacy\CLI\User_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\User_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\User_Command
 *
 * @uses ::__construct
 */
class User_Command_Test extends TestCase {

	/**
	 * The system under test.
	 *
	 * @var User_Command
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->user_fields = m::mock( User_Fields::class );

		$this->sut = m::mock(
			User_Command::class,
			[ $this->user_fields ]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( User_Command::class )->makePartial();

		$mock->__construct( $this->user_fields );

		$this->assert_attribute_same( $this->user_fields, 'user_fields', $mock );
	}


	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy user set-local-avatar', [ $this->sut, 'set_local_avatar' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy user delete-local-avatar', [ $this->sut, 'delete_local_avatar' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->user_fields->shouldReceive( 'set_local_avatar' )->once()->with( $user_id, $image_url );
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->set_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar_not_live() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = false;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->once()->with( m::type( 'string' ) );
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'set_local_avatar' )->never();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->set_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar_invalid_user_id() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$user = false;

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );
		Functions\expect( 'esc_url_raw' )->never();

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->never();
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'set_local_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->set_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar_invalid_image_url() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/söme invalid_image.png';
		$live      = true;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( 'escaped URL' );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->never();
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'set_local_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->set_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_local_avatar.
	 *
	 * @covers ::set_local_avatar
	 */
	public function test_set_local_avatar_error() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->user_fields->shouldReceive( 'set_local_avatar' )->once()->with( $user_id, $image_url )->andThrow( \RuntimeException::class );
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->set_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar() {
		// Parameters.
		$user_id = 42;
		$live    = true;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->user_fields->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id );
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->delete_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar_not_live() {
		// Parameters.
		$user_id = 42;
		$live    = false;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );

		$this->wp_cli->shouldReceive( 'warning' )->once()->with( m::type( 'string' ) );
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'delete_local_avatar' )->never();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->delete_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar_invalid_user_id() {
		// Parameters.
		$user_id   = 42;
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$user = false;

		// Prepare arguments.
		$args       = [ $user_id, $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->never();
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'delete_local_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar_no_local_avatar() {
		// Parameters.
		$user_id = 42;
		$live    = false;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [];

		// Prepare arguments.
		$args       = [ $user_id ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->user_fields->shouldReceive( 'delete_local_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete_local_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_local_avatar.
	 *
	 * @covers ::delete_local_avatar
	 */
	public function test_delete_local_avatar_error() {
		// Parameters.
		$user_id = 42;
		$live    = true;

		// Intermediate values.
		$user_login       = 'some_user';
		$user             = m::mock( \WP_User::class );
		$user->user_login = $user_login;
		$avatar           = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $user_id ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'get_user_by' )->once()->with( 'id',  $user_id )->andReturn( $user );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->user_fields->shouldReceive( 'delete_local_avatar' )->once()->with( $user_id )->andThrow( \RuntimeException::class );
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete_local_avatar( $args, $assoc_args ) );
	}
}
