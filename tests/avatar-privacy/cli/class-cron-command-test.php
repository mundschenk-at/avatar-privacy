<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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

use Avatar_Privacy\CLI\Cron_Command;
use Avatar_Privacy\Components\Image_Proxy;

/**
 * Avatar_Privacy\CLI\Cron_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Cron_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Cron_Command
 *
 * @usesx ::__construct
 */
class Cron_Command_Test extends TestCase {

	/**
	 * The system under test.
	 *
	 * @var Cron_Command&m\MockInterface
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->sut = m::mock(
			Cron_Command::class,
			[]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy cron list', [ $this->sut, 'list_' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy cron delete', [ $this->sut, 'delete' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Tests ::list_.
	 *
	 * @covers ::list_
	 */
	public function test_list_() {
		Functions\expect( 'wp_next_scheduled' )->once()->with( Image_Proxy::CRON_JOB_ACTION )->andReturn( \time() );

		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->list_() );
	}

	/**
	 * Tests ::list_.
	 *
	 * @covers ::list_
	 */
	public function test_list_not_scheduled() {
		Functions\expect( 'wp_next_scheduled' )->once()->with( Image_Proxy::CRON_JOB_ACTION )->andReturn( false );

		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->list_() );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete() {
		Functions\expect( 'wp_unschedule_hook' )->once()->with( Image_Proxy::CRON_JOB_ACTION )->andReturn( 1 );

		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes();
		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->delete() );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete_no_events() {
		Functions\expect( 'wp_unschedule_hook' )->once()->with( Image_Proxy::CRON_JOB_ACTION )->andReturn( false );

		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes();
		$this->expect_wp_cli_error();

		$this->assertNull( $this->sut->delete() );
	}
}
