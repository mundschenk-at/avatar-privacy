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

use Avatar_Privacy\CLI\Database_Command;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table;

/**
 * Avatar_Privacy\CLI\Database_Command unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\CLI\Database_Command
 * @usesDefaultClass \Avatar_Privacy\CLI\Database_Command
 *
 * @uses ::__construct
 */
class Database_Command_Test extends TestCase {

	/**
	 * The system under test.
	 *
	 * @var Database_Command&m\MockInterface
	 */
	private $sut;

	/**
	 * The core API.
	 *
	 * @var Core&m\MockInterface
	 */
	private $core;

	/**
	 * The table handler.
	 *
	 * @var Comment_Author_Table&m\MockInterface
	 */
	private $comment_author_table;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->core                 = m::mock( Core::class );
		$this->comment_author_table = m::mock( Comment_Author_Table::class );

		$this->sut = m::mock(
			Database_Command::class,
			[
				$this->core,
				$this->comment_author_table,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();

		// We don't care about colorize.
		$this->wp_cli->shouldReceive( 'colorize' )->zeroOrMoreTimes()->with( m::type( 'string' ) )->andReturnUsing(
			function ( $arg ) {
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

		$mock->__construct( $this->core, $this->comment_author_table );

		$this->assert_attribute_same( $this->core, 'core', $mock );
		$this->assert_attribute_same( $this->comment_author_table, 'comment_author_table', $mock );
	}

	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db create', [ $this->sut, 'create' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db show', [ $this->sut, 'show' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db list', [ $this->sut, 'list_' ] );
		$this->wp_cli->shouldReceive( 'add_command' )->once()->with( 'avatar-privacy db upgrade', [ $this->sut, 'upgrade' ] );

		$this->assertNull( $this->sut->register() );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show() {
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

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		$this->comment_author_table->shouldReceive( 'use_global_table' )->never();

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show() );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show_multisite() {
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

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$this->comment_author_table->shouldReceive( 'use_global_table' )->once()->andReturn( false );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( 'Each site in this network uses a separate table.' );

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show() );
	}

	/**
	 * Tests ::show.
	 *
	 * @covers ::show
	 */
	public function test_show_multisite_global_table() {
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

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$this->comment_author_table->shouldReceive( 'use_global_table' )->once()->andReturn( true );
		$this->wp_cli->shouldReceive( 'line' )->once()->with( 'The global table is used for all sites in this network.' );

		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->with( 'table', $schema, m::type( 'array' ) );

		$this->wp_cli->shouldReceive( 'line' )->atLeast()->once()->with( m::type( 'string' ) );

		$this->assertNull( $this->sut->show() );
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
		$table = 'fake_table';

		Functions\expect( 'wp_parse_args' )->once()->with( $assoc_args, m::type( 'array' ) )->andReturn( $assoc_args );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
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
		$table = 'fake_table';

		Functions\expect( 'wp_parse_args' )->once()->with( $assoc_args, m::type( 'array' ) )->andReturn( $assoc_args );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$table_iterator->shouldReceive( '__construct' )->once()->with(
			[
				'table' => $table,
				'where' => [],
			]
		);

		// Return expected result [ 'iterator' => 'array' ] from native iterator_to_array.
		$table_iterator->shouldReceive( 'rewind' )->once();
		$table_iterator->shouldReceive( 'valid' )->twice()->andReturn( true, false );
		$table_iterator->shouldReceive( 'current' )->once()->andReturn( 'array' );
		$table_iterator->shouldReceive( 'key' )->once()->andReturn( 'iterator' );
		$table_iterator->shouldReceive( 'next' )->once();

		Functions\expect( 'wp_list_pluck' )->once()->with( m::type( 'array' ), 'id' )->andReturn( [ 'id' => 'fake' ] );
		$formatter->shouldReceive( '__construct' )->once()->with( $assoc_args, null );
		$formatter->shouldReceive( 'display_items' )->once()->with( m::type( 'array' ) );

		$this->assertNull( $this->sut->list_( $args, $assoc_args ) );
	}

	/**
	 * Provides data for testing ::create.
	 *
	 * @return array
	 */
	public function provide_create_data() {
		return [
			// Not --global, singlesite.
			[ false, false, true ],
			// Not --global, multisite.
			[ false, true, false ],
			[ false, true, true ],
			// --global, singlesite.
			[ true, false, true ],
			// --global, multisite.
			[ true, true, false ],
			[ true, true, true ],
		];
	}

	/**
	 * Tests ::create.
	 *
	 * @covers ::create
	 *
	 * @dataProvider provide_create_data
	 *
	 * @param  bool $global_flag  The --global flag.
	 * @param  bool $multisite    Optional. Whether this is a multisite installation. Default false.
	 * @param  bool $global_table Optional. Whether the installation uses the global table. Default true.
	 */
	public function test_create( $global_flag, $multisite, $global_table ) {
		$args       = [];
		$assoc_args = [
			'global' => $global_flag,
		];

		// Intermediary data.
		$table = 'fake_table';

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( $global_flag );
		Functions\expect( 'is_multisite' )->once()->andReturn( $multisite );

		// May not be triggered.
		$this->comment_author_table->shouldReceive( 'use_global_table' )->zeroOrMoreTimes()->andReturn( $global_table );

		if ( $global_flag && ( ! $multisite || ! $global_table ) ) {
			$this->expect_wp_cli_error();
		} else {
			// May not be triggered.
			Functions\expect( 'is_main_site' )->zeroOrMoreTimes()->andReturn( true );

			$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
			$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( false );
			$this->comment_author_table->shouldReceive( 'maybe_create_table' )->once()->with( '' )->andReturn( true );

			$this->expect_wp_cli_success();
		}

		$this->assertNull( $this->sut->create( $args, $assoc_args ) );
	}

	/**
	 * Tests ::create.
	 *
	 * @covers ::create
	 */
	public function test_create_already_exists() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table = 'fake_table';

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( true );
		$this->expect_wp_cli_error();
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->never();

		$this->assertNull( $this->sut->create( $args, $assoc_args ) );
	}

	/**
	 * Tests ::create.
	 *
	 * @covers ::create
	 */
	public function test_create_failure() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table = 'fake_table';

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( false );
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->once()->with( '' )->andReturn( false );

		$this->expect_wp_cli_error();
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->never();

		$this->assertNull( $this->sut->create( $args, $assoc_args ) );
	}

	/**
	 * Tests ::create.
	 *
	 * @covers ::create
	 */
	public function test_create_not_main_site() {
		$args       = [];
		$assoc_args = [
			'global' => false,
		];

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'is_main_site' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'use_global_table' )->once()->andReturn( true );

		$this->expect_wp_cli_error();

		$this->comment_author_table->shouldReceive( 'get_table_name' )->never();
		$this->comment_author_table->shouldReceive( 'table_exists' )->never();
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->never();

		$this->assertNull( $this->sut->create( $args, $assoc_args ) );
	}

	/**
	 * Tests ::upgrade.
	 *
	 * @covers ::upgrade
	 *
	 * @dataProvider provide_create_data
	 *
	 * @param  bool $global_flag  The --global flag.
	 * @param  bool $multisite    Optional. Whether this is a multisite installation. Default false.
	 * @param  bool $global_table Optional. Whether the installation uses the global table. Default true.
	 */
	public function test_upgrade( $global_flag, $multisite, $global_table ) {
		$args       = [];
		$assoc_args = [
			'global' => $global_flag,
		];

		// Intermediary data.
		$table = 'fake_table';
		$rows  = 6;

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( $global_flag );
		Functions\expect( 'is_multisite' )->once()->andReturn( $multisite );

		// May not be triggered.
		$this->comment_author_table->shouldReceive( 'use_global_table' )->zeroOrMoreTimes()->andReturn( $global_table );

		if ( $global_flag && ( ! $multisite || ! $global_table ) ) {
			$this->expect_wp_cli_error();
		} else {
			// May not be triggered.
			Functions\expect( 'is_main_site' )->zeroOrMoreTimes()->andReturn( true );

			$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
			$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( true );
			$this->comment_author_table->shouldReceive( 'maybe_create_table' )->once()->with( '' )->andReturn( true );
			$this->comment_author_table->shouldReceive( 'maybe_upgrade_data' )->once()->with( '' )->andReturn( $rows );

			$this->expect_wp_cli_success();
		}

		$this->assertNull( $this->sut->upgrade( $args, $assoc_args ) );
	}

	/**
	 * Tests ::upgrade.
	 *
	 * @covers ::upgrade
	 */
	public function test_upgrade_does_not_exist() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table = 'fake_table';

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( false );

		$this->expect_wp_cli_error();

		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->never();
		$this->comment_author_table->shouldReceive( 'maybe_upgrade_data' )->never();

		$this->assertNull( $this->sut->upgrade( $args, $assoc_args ) );
	}

	/**
	 * Tests ::upgrade.
	 *
	 * @covers ::upgrade
	 */
	public function test_upgrade_error_during_structure_upgrade() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table = 'fake_table';

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( true );
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->once()->with( '' )->andReturn( false );

		$this->expect_wp_cli_error();

		$this->comment_author_table->shouldReceive( 'maybe_upgrade_data' )->never();

		$this->assertNull( $this->sut->upgrade( $args, $assoc_args ) );
	}

	/**
	 * Tests ::upgrade.
	 *
	 * @covers ::upgrade
	 */
	public function test_upgrade_no_rows() {
		$args       = [];
		$assoc_args = [];

		// Intermediary data.
		$table = 'fake_table';
		$rows  = 0;

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'get_table_name' )->once()->andReturn( $table );
		$this->comment_author_table->shouldReceive( 'table_exists' )->once()->with( $table )->andReturn( true );
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->once()->with( '' )->andReturn( true );
		$this->comment_author_table->shouldReceive( 'maybe_upgrade_data' )->once()->with( '' )->andReturn( $rows );

		$this->expect_wp_cli_success();

		$this->assertNull( $this->sut->upgrade( $args, $assoc_args ) );
	}

	/**
	 * Tests ::upgrade.
	 *
	 * @covers ::upgrade
	 */
	public function test_upgrade_not_main_site() {
		$args       = [];
		$assoc_args = [
			'global' => false,
		];

		Functions\expect( 'WP_CLI\Utils\get_flag_value' )->once()->with( $assoc_args, 'global', false )->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'is_main_site' )->once()->andReturn( false );

		$this->comment_author_table->shouldReceive( 'use_global_table' )->once()->andReturn( true );

		$this->expect_wp_cli_error();

		$this->comment_author_table->shouldReceive( 'get_table_name' )->never();
		$this->comment_author_table->shouldReceive( 'table_exists' )->never();
		$this->comment_author_table->shouldReceive( 'maybe_create_table' )->never();
		$this->comment_author_table->shouldReceive( 'maybe_upgrade_data' )->never();

		$this->assertNull( $this->sut->upgrade( $args, $assoc_args ) );
	}
}
