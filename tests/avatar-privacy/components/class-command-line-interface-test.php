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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\Command_Line_Interface;

use Avatar_Privacy\CLI\Commmand;

/**
 * Avatar_Privacy\Components\Command_Line_Interface unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Command_Line_Interface
 * @usesDefaultClass \Avatar_Privacy\Components\Command_Line_Interface
 *
 * @uses ::__construct
 */
class Command_Line_Interface_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Command_Line_Interface
	 */
	private $sut;

	/**
	 * A mocked CLI command.
	 *
	 * @var Command
	 */
	private $cmd_one;

	/**
	 * A mocked CLI command.
	 *
	 * @var Command
	 */
	private $cmd_two;

	/**
	 * A mocked CLI command.
	 *
	 * @var Command
	 */
	private $cmd_three;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		// API class mock.
		$this->wp_cli = m::mock( 'alias:' . \WP_CLI::class );

		$this->cmd_one   = m::mock( Command::class );
		$this->cmd_two   = m::mock( Command::class );
		$this->cmd_three = m::mock( Command::class );
		$commands        = [
			$this->cmd_one,
			$this->cmd_two,
			$this->cmd_three,
		];

		$this->sut = m::mock( Command_Line_Interface::class, [ $commands ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$commands = [
			$this->cmd_one,
			$this->cmd_two,
			$this->cmd_three,
		];

		$mock = m::mock( Command_Line_Interface::class )->makePartial();

		$mock->__construct( $commands );

		$this->assertAttributeSame( $commands, 'commands', $mock );
	}


	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'cli_init' )->once()->with( [ $this->sut, 'register_commands' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::register_commands.
	 *
	 * @covers ::register_commands
	 */
	public function test_register_commands() {
		$this->cmd_one->shouldReceive( 'register' )->once();
		$this->cmd_two->shouldReceive( 'register' )->once();
		$this->cmd_three->shouldReceive( 'register' )->once();

		$this->assertNull( $this->sut->register_commands() );
	}
}
