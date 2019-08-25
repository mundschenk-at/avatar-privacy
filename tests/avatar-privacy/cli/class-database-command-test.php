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

use Avatar_Privacy\CLI\Database_Command;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Database;

/**
 * Avatar_Privacy\CLI\Database_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Database_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Database_Command
 *
 * @uses ::__construct
 */
class Database_Command_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system under test.
	 *
	 * @var Database_Command
	 */
	private $sut;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The database handler.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Alias mock for static WP_CLI methods.
	 *
	 * @var \WP_CLI
	 */
	private $wp_cli;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		// API class mock.
		$this->wp_cli = m::mock( 'alias:' . \WP_CLI::class );

		// Helper mocks.
		$this->core     = m::mock( Core::class );
		$this->database = m::mock( Database::class );

		$this->sut = m::mock(
			Database_Command::class,
			[
				$this->core,
				$this->database,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();

		// We don't care about colorize.
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return $arg;
			}
		);
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Database_Command::class )->makePartial();

		$mock->__construct( $this->core, $this->database );

		$this->assertAttributeSame( $this->core, 'core', $mock );
		$this->assertAttributeSame( $this->database, 'db', $mock );
	}

	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {

		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db show', [ $this->sut, 'show' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db list', [ $this->sut, 'list_' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table   = 'fake_table';
		$version = '6.6.6';
		$count   = 23;
		$schema  = 'SOME DB SCHEMA DEFINITION';

		// Fake globals.
		global $wpdb;
		$wpdb                 = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = $table;

		$wpdb->shouldReceive( 'get_var' )->once()->with( "SELECT COUNT(*) FROM {$table}" )->andReturn( $count );
		$wpdb->shouldReceive( 'get_results' )->once()->with( "DESCRIBE {$wpdb->avatar_privacy}", \ARRAY_A )->andReturn( $schema );

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		$this->database->shouldReceive( 'use_global_table' )->never();

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show( $args, $assoc_args ) );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show_multisite() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table   = 'fake_table';
		$version = '6.6.6';
		$count   = 23;
		$schema  = 'SOME DB SCHEMA DEFINITION';

		// Fake globals.
		global $wpdb;
		$wpdb                 = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = $table;

		$wpdb->shouldReceive( 'get_var' )->once()->with( "SELECT COUNT(*) FROM {$table}" )->andReturn( $count );
		$wpdb->shouldReceive( 'get_results' )->once()->with( "DESCRIBE {$wpdb->avatar_privacy}", \ARRAY_A )->andReturn( $schema );

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$this->database->shouldReceive( 'use_global_table' )->once()->andReturn( false );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( 'Each site in this network uses a separate table.' );

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show( $args, $assoc_args ) );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show_multisite_global_table() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table   = 'fake_table';
		$version = '6.6.6';
		$count   = 23;
		$schema  = 'SOME DB SCHEMA DEFINITION';

		// Fake globals.
		global $wpdb;
		$wpdb                 = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = $table;

		$wpdb->shouldReceive( 'get_var' )->once()->with( "SELECT COUNT(*) FROM {$table}" )->andReturn( $count );
		$wpdb->shouldReceive( 'get_results' )->once()->with( "DESCRIBE {$wpdb->avatar_privacy}", \ARRAY_A )->andReturn( $schema );

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$this->database->shouldReceive( 'use_global_table' )->once()->andReturn( true );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( 'The global table is used for all sites in this network.' );

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show( $args, $assoc_args ) );
	}

	/**
	 * Tests ::list_.
	 *
	 * @covers ::list_
	 */
	public function test_list() {
		// Input.
		$email  = 'foo@bar.org';
		$format = 'json';
		$id     = 42;

		// Arguments.
		$args       = [];
		$assoc_args = [
			'format' => $format,
			'email'  => $email,
			'id'     => $id,
		];

		// Overloaded helper class.
		$table_iterator = m::mock( 'overload:' . \WP_CLI\Iterators\Table::class, \Iterator::class );
		$formatter      = m::mock( 'overload:' . \WP_CLI\Formatter::class );

		// Intermediary data.
		$table   = 'fake_table';
		$version = '6.6.6';
		$count   = 23;
		$schema  = 'SOME DB SCHEMA DEFINITION';

		Functions\expect( 'wp_parse_args' )->once()->with( $assoc_args, m::type( 'array' ) )->andReturn( $assoc_args );

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$table_iterator->shouldReceive( '__construct' )->once()->with(
			[
				'table' => $table,
				'where' => [
					'email' => $email,
					'id'    => $id,
				],
			]
		);

		$formatter->shouldReceive( '__construct' )->once()->with( $assoc_args, null );
		$formatter->shouldReceive( 'display_items' )->once()->with( m::type( \WP_CLI\Iterators\Table::class ) );

		$this->assertNull( $this->sut->list_( $args, $assoc_args ) );
	}

	/**
	 * Tests ::list_.
	 *
	 * @covers ::list_
	 */
	public function test_list_ids() {
		$args       = [];
		$assoc_args = [
			'format' => 'ids',
		];

		// Overloaded helper class.
		$table_iterator = m::mock( 'overload:' . \WP_CLI\Iterators\Table::class, \Iterator::class );
		$formatter      = m::mock( 'overload:' . \WP_CLI\Formatter::class );

		// Intermediary data.
		$table   = 'fake_table';
		$version = '6.6.6';
		$count   = 23;
		$schema  = 'SOME DB SCHEMA DEFINITION';

		Functions\expect( 'wp_parse_args' )->once()->with( $assoc_args, m::type( 'array' ) )->andReturn( $assoc_args );

		$this->database->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$table_iterator->shouldReceive( '__construct' )->once()->with(
			[
				'table' => $table,
				'where' => [],
			]
		);

		$this->sut->shouldReceive( 'iterator_to_array' )->once()->with( m::type( \WP_CLI\Iterators\Table::class ) )->andReturn( [ 'iterator' => 'array' ] );
		Functions\expect( 'wp_list_pluck' )->once()->with( m::type( 'array' ), 'id' )->andReturn( [ 'id' => 'fake' ] );

		$formatter->shouldReceive( '__construct' )->once()->with( $assoc_args, null );
		$formatter->shouldReceive( 'display_items' )->once()->with( m::type( 'array' ) );

		$this->assertNull( $this->sut->list_( $args, $assoc_args ) );
	}
}
