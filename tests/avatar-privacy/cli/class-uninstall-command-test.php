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

namespace Avatar_Privacy\Tests\Avatar_Privacy\CLI;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\CLI\Uninstall_Command;

use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Uninstallation;
use Avatar_Privacy\Data_Storage\Database;

/**
 * Avatar_Privacy\CLI\Uninstall_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Uninstall_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Uninstall_Command
 *
 * @uses ::__construct
 */
class Uninstall_Command_Test extends TestCase {

	/**
	 * The system under test.
	 *
	 * @var Uninstall_Command
	 */
	private $sut;

	/**
	 * The setup API.
	 *
	 * @var Setup
	 */
	private $setup;

	/**
	 * The uninstall API.
	 *
	 * @var Uninstallation
	 */
	private $uninstall;

	/**
	 * The database handler.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->setup     = m::mock( Setup::class );
		$this->uninstall = m::mock( Uninstallation::class );
		$this->database  = m::mock( Database::class );

		$this->sut = m::mock(
			Uninstall_Command::class,
			[
				$this->setup,
				$this->uninstall,
				$this->database,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Uninstall_Command::class )->makePartial();

		$mock->__construct( $this->setup, $this->uninstall, $this->database );

		$this->assertAttributeSame( $this->setup, 'setup', $mock );
		$this->assertAttributeSame( $this->uninstall, 'uninstall', $mock );
		$this->assertAttributeSame( $this->database, 'db', $mock );
	}

	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {

		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy uninstall', [ $this->sut, 'uninstall' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Provides data for testing ::uninstall.
	 *
	 * @return array
	 */
	public function provide_uninstall_data() {
		return [
			// Not --live, not --global.
			[ false, false, false ],
			[ false, false, true ],
			// Not --live, but --global.
			[ false, true, false ],
			[ false, true, true ],
			// --live, not --global.
			[ true, false, false ],
			[ true, false, true ],
			// --live, --global.
			[ true, true, false ],
			[ true, true, true ],
		];
	}

	/**
	 * Tests ::uninstall.
	 *
	 * @covers ::uninstall
	 *
	 * @dataProvider provide_uninstall_data
	 *
	 * @param  bool $live         The --live flag.
	 * @param  bool $global       The --global flag.
	 * @param  bool $multi        Optional. Whether this is a multisite installation. Default false.
	 */
	public function test_uninstall( $live, $global, $multi = false ) {
		// Parameter arrays.
		$args       = [];
		$assoc_args = [
			'live'   => $live,
			'global' => $global,
		];

		// Intermediary data.
		$blog_id       = 1;
		$remove_global = $global || ! $multi;

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'live', false )->andReturn( $live );
		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( $global );
		Functions\expect( 'is_multisite' )->once()->andReturn( $multi );

		if ( $global && ! $multi ) {
			// The script ends prematurely.
			$this->expect_wp_cli_error( m::type( 'string' ) );

			$this->sut->shouldReceive( 'print_data_to_delete' )->never();
			$this->sut->shouldReceive( 'delete_data' )->never();
		} else {
			Functions\expect( 'get_current_blog_id' )->once()->andReturn( $blog_id );

			$this->sut->shouldReceive( 'print_data_to_delete' )->once()->with( m::type( 'string' ), $remove_global );

			if ( ! $live ) {
				// Dry run.
				$this->wp_cli->shouldReceive( 'warning' )->once()->with( 'Starting dry run.' );
				$this->expect_wp_cli_success( 'Dry run finished.' );
				$this->wp_cli->shouldReceive( 'confirm' )->never();
				$this->sut->shouldReceive( 'delete_data' )->never();
			} else {
				// Modify data.
				$this->wp_cli->shouldReceive( 'confirm' )->once()->with( m::type( 'string' ), $assoc_args );
				$this->sut->shouldReceive( 'delete_data' )->once()->with( $blog_id, m::type( 'string' ), $remove_global );
			}
		}

		// Run test.
		$this->assertNull( $this->sut->uninstall( $args, $assoc_args ) );
	}

	/**
	 * Provides data for testing ::delete_data.
	 *
	 * @return array
	 */
	public function provide_delete_data_data() {
		return [
			[ false, false ],
			[ false, true ],
			[ true, false ],
			[ true, true ],
		];
	}


	/**
	 * Tests ::delete_data.
	 *
	 * @covers ::delete_data
	 *
	 * @dataProvider provide_delete_data_data
	 *
	 * @param  bool $global The --global flag.
	 * @param  bool $multi  Optional. Whether this is a multisite installation. Default false.
	 */
	public function test_delete_data( $global, $multi = false ) {
		// Input data.
		$blog_id  = 1;
		$for_site = $multi ? "for site {$blog_id}" : '';

		// Modify data.
		$this->setup->shouldReceive( 'deactivate_plugin' )->once();
		$this->uninstall->shouldReceive( 'enqueue_cleanup_tasks' )->once();
		Actions\expectDone( 'avatar_privacy_uninstallation_site' )->once()->with( $blog_id );

		if ( $global ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( $multi );
			Actions\expectDone( 'avatar_privacy_uninstallation_global' )->once();
		} else {
			Functions\expect( 'is_multisite' )->never();
			Actions\expectDone( 'avatar_privacy_uninstallation_global' )->never();
		}

		// Signalling success.
		$this->expect_wp_cli_success( m::type( 'string' ), true );

		// Run test.
		$this->assertNull( $this->sut->delete_data( $blog_id, $for_site, $global ) );
	}

	/**
	 * Provides data for testing ::print_data_to_delete.
	 *
	 * @return array
	 */
	public function provide_print_data_to_delete_data() {
		return [
			[ false, false, false ],
			[ false, false, true ],
			[ false, true, false ],
			[ false, true, true ],
			[ true, false, false ],
			[ true, false, true ],
			[ true, true, false ],
			[ true, true, true ],
		];
	}

	/**
	 * Tests ::print_data_to_delete.
	 *
	 * @covers ::print_data_to_delete
	 *
	 * @dataProvider provide_print_data_to_delete_data
	 *
	 * @param  bool $global       The --global flag.
	 * @param  bool $multi        Optional. Whether this is a multisite installation. Default false.
	 * @param  bool $global_table Optional. Whether the installation uses the global table. Default true.
	 */
	public function test_print_data_to_delete( $global, $multi = false, $global_table = true ) {
		// Input data.
		$blog_id  = 1;
		$for_site = $multi ? "for site {$blog_id}" : '';

		// Intermediary data.
		$table_name = 'fake_table';

		if ( $global ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( $multi );
		} else {
			Functions\expect( 'is_multisite' )->never();
		}

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );
		$this->database->shouldReceive( 'use_global_table' )->once()->andReturn( $global_table );

		// Script output.
		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		// Run test.
		$this->assertNull( $this->sut->print_data_to_delete( $for_site, $global ) );
	}
}
