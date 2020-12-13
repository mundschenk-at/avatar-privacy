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

use Avatar_Privacy\CLI\Default_Command;
use Avatar_Privacy\Core\Default_Avatars;

/**
 * Avatar_Privacy\CLI\Default_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Default_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Default_Command
 *
 * @uses ::__construct
 */
class Default_Command_Test extends TestCase {

	/**
	 * The system under test.
	 *
	 * @var Default_Command
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Default_Avatars
	 */
	private $default_avatars;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->default_avatars = m::mock( Default_Avatars::class );

		$this->sut = m::mock(
			Default_Command::class,
			[ $this->default_avatars ]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Default_Command::class )->makePartial();

		$mock->__construct( $this->default_avatars );

		$this->assert_attribute_same( $this->default_avatars, 'default_avatars', $mock );
	}


	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy default get-custom-default-avatar', [ $this->sut, 'get_custom_default_avatar' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy default set-custom-default-avatar', [ $this->sut, 'set_custom_default_avatar' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy default delete-custom-default-avatar', [ $this->sut, 'delete_custom_default_avatar' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar() {
		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->get_custom_default_avatar() );
	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar_none_set() {
		// Intermediate values.
		$avatar = [];

		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->get_custom_default_avatar() );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar() {
		// Parameters.
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->default_avatars->shouldReceive( 'set_custom_default_avatar' )->once()->with( $image_url );
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->set_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_not_live() {
		// Parameters.
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = false;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->once()->with( m::type( 'string' ) );
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->default_avatars->shouldReceive( 'set_custom_default_avatar' )->never();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->set_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_invalid_image_url() {
		// Parameters.
		$image_url = 'https://example.org/somewhere/söme invalid_image.png';
		$live      = true;

		// Prepare arguments.
		$args       = [ $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( 'escaped URL' );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->never();
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->default_avatars->shouldReceive( 'set_custom_default_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->set_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_error() {
		// Parameters.
		$image_url = 'https://example.org/somewhere/someimage.png';
		$live      = true;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [ $image_url ];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'esc_url_raw' )->once()->with( $image_url )->andReturn( $image_url );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->default_avatars->shouldReceive( 'set_custom_default_avatar' )->once()->with( $image_url )->andThrow( \RuntimeException::class );
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->set_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar.
	 *
	 * @covers ::delete_custom_default_avatar
	 */
	public function test_delete_custom_default_avatar() {
		// Parameters.
		$live = true;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->default_avatars->shouldReceive( 'delete_custom_default_avatar' )->once();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->delete_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar.
	 *
	 * @covers ::delete_custom_default_avatar
	 */
	public function test_delete_custom_default_avatar_not_live() {
		// Parameters.
		$live = false;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );

		$this->wp_cli->shouldReceive( 'warning' )->once()->with( m::type( 'string' ) );
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->default_avatars->shouldReceive( 'delete_custom_default_avatar' )->never();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->delete_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar.
	 *
	 * @covers ::delete_custom_default_avatar
	 */
	public function test_delete_custom_default_avatar_no_local_avatar() {
		// Parameters.
		$live = false;

		// Intermediate values.
		$avatar = [];

		// Prepare arguments.
		$args       = [];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->never();
		$this->wp_cli->shouldReceive( 'line' )->never();
		$this->wp_cli->shouldReceive( 'confirm' )->never();
		$this->default_avatars->shouldReceive( 'delete_custom_default_avatar' )->never();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete_custom_default_avatar( $args, $assoc_args ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar.
	 *
	 * @covers ::delete_custom_default_avatar
	 */
	public function test_delete_custom_default_avatar_error() {
		// Parameters.
		$live = true;

		// Intermediate values.
		$avatar = [
			'file' => '/some/other/image_file.png',
			'type' => 'image/png',
		];

		// Prepare arguments.
		$args       = [];
		$assoc_args = [
			'live' => $live,
		];
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );

		$this->wp_cli->shouldReceive( 'warning' )->never();
		$this->default_avatars->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->andReturn( 'colorized string' );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( m::type( 'string' ) );
		$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
		$this->default_avatars->shouldReceive( 'delete_custom_default_avatar' )->once()->with()->andThrow( \RuntimeException::class );
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete_custom_default_avatar( $args, $assoc_args ) );
	}
}
