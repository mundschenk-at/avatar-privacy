<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Data_Storage\Database;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table;
use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * Avatar_Privacy\Data_Storage\Database\Comment_Author_Table unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Database\Comment_Author_Table
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Database\Comment_Author_Table
 *
 * @uses ::__construct
 * @uses Avatar_Privacy\Data_Storage\Database\Table::__construct
 */
class Comment_Author_Table_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Comment_Author_Table&m\MockInterface
	 */
	private $sut;

	/**
	 * Helper object.
	 *
	 * @var Network_Options&m\MockInterface
	 */
	private $network_options;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$this->network_options = m::mock( Network_Options::class );

		// Partially mock system under test.
		$this->sut = m::mock( Comment_Author_Table::class, [ $this->network_options ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Comment_Author_Table::class )->makePartial();
		$mock->__construct( $this->network_options );

		$this->assert_attribute_same( $this->network_options, 'network_options', $mock );
	}

	/**
	 * Tests ::setup.
	 *
	 * @covers ::setup
	 *
	 * @uses Avatar_Privacy\Data_Storage\Database\Table::setup
	 */
	public function test_setup() {
		$previous_version = '1.1.0';

		$this->sut->shouldReceive( 'maybe_create_table' )->once()->with( $previous_version )->andReturn( true );
		$this->sut->shouldReceive( 'maybe_upgrade_schema' )->once()->with( $previous_version );
		$this->sut->shouldReceive( 'maybe_upgrade_data' )->once()->with( $previous_version );

		$this->sut->shouldReceive( 'maybe_prepare_migration_queue' )->once();
		$this->sut->shouldReceive( 'maybe_migrate_from_global_table' )->once();

		$this->assertNull( $this->sut->setup( $previous_version ) );
	}

	/**
	 * Tests ::setup.
	 *
	 * @covers ::setup
	 *
	 * @uses Avatar_Privacy\Data_Storage\Database\Table::setup
	 */
	public function test_setup_table_exists() {
		$previous_version = '1.1.0';

		$this->sut->shouldReceive( 'maybe_create_table' )->once()->with( $previous_version )->andReturn( false );
		$this->sut->shouldReceive( 'maybe_upgrade_schema' )->never();
		$this->sut->shouldReceive( 'maybe_upgrade_data' )->never();

		$this->sut->shouldReceive( 'maybe_prepare_migration_queue' )->once();
		$this->sut->shouldReceive( 'maybe_migrate_from_global_table' )->once();

		$this->assertNull( $this->sut->setup( $previous_version ) );
	}

	/**
	 * Provides data for testing use_global_table.
	 *
	 * @return array
	 */
	public function provide_use_global_table_data() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * Tests ::use_global_table.
	 *
	 * @covers ::use_global_table
	 *
	 * @dataProvider provide_use_global_table_data
	 *
	 * @param bool $result The expected result.
	 */
	public function test_use_global_table( $result ) {
		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::USE_GLOBAL_TABLE, false )->andReturn( $result );

		Filters\expectApplied( 'avatar_privacy_enable_global_table' )->once()->with( $result );

		$this->assertSame( $result, $this->sut->use_global_table( $result ) );
	}

	/**
	 * Tests ::get_table_definition.
	 *
	 * @covers ::get_table_definition
	 */
	public function test_get_table_definition() {
		$table_name = 'my_table';

		$this->assert_matches_regular_expression( "/^CREATE TABLE {$table_name} \(.*\)\$/sum", $this->sut->get_table_definition( $table_name ) );
	}

	/**
	 * Tests ::maybe_prepare_migration_queue.
	 *
	 * @covers ::maybe_prepare_migration_queue
	 */
	public function test_maybe_prepare_migration_queue() {
		$queue = [
			15 => 15,
			17 => 17,
		];

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION )->andReturn( $queue );
		$this->network_options->shouldReceive( 'lock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( true );
		$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION, $queue );
		$this->network_options->shouldReceive( 'unlock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( true );
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		$this->assertNull( $this->sut->maybe_prepare_migration_queue() );
	}

	/**
	 * Tests ::maybe_prepare_migration_queue.
	 *
	 * @covers ::maybe_prepare_migration_queue
	 */
	public function test_maybe_prepare_migration_queue_locked() {
		$queue = [
			15 => 15,
			17 => 17,
		];

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION )->andReturn( $queue );
		$this->network_options->shouldReceive( 'lock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( false );
		$this->network_options->shouldReceive( 'set' )->never();
		$this->network_options->shouldReceive( 'unlock' )->never();
		$this->network_options->shouldReceive( 'delete' )->never();

		$this->assertNull( $this->sut->maybe_prepare_migration_queue() );
	}

	/**
	 * Tests ::maybe_prepare_migration_queue.
	 *
	 * @covers ::maybe_prepare_migration_queue
	 */
	public function test_maybe_prepare_migration_nothing_to_do() {
		$queue = false;

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION )->andReturn( $queue );
		$this->network_options->shouldReceive( 'lock' )->never();
		$this->network_options->shouldReceive( 'set' )->never();
		$this->network_options->shouldReceive( 'unlock' )->never();
		$this->network_options->shouldReceive( 'delete' )->never();

		$this->assertNull( $this->sut->maybe_prepare_migration_queue() );
	}

	/**
	 * Tests ::maybe_prepare_migration_queue.
	 *
	 * @covers ::maybe_prepare_migration_queue
	 */
	public function test_maybe_prepare_migration_queue_empty() {
		$queue = [];

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION )->andReturn( $queue );
		$this->network_options->shouldReceive( 'lock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( true );
		$this->network_options->shouldReceive( 'set' )->never();
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION );
		$this->network_options->shouldReceive( 'unlock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( true );
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		$this->assertNull( $this->sut->maybe_prepare_migration_queue() );
	}

	/**
	 * Provides data for testing maybe_migrate_from_global_table.
	 *
	 * @return array
	 */
	public function provide_maybe_migrate_from_global_table_data() {
		return [
			[
				// Active and not locked.
				6,   // site ID.
				[    // queue.
					1 => 1,
					3 => 3,
					6 => 6,
				],
				true,  // active.
				false, // not locked.
			],
			[
				// Not active.
				6,   // site ID.
				[    // queue.
					1 => 1,
					3 => 3,
					6 => 6,
				],
				false, // not active.
				false, // not locked.
			],
			[
				// Active, but locked.
				6,   // site ID.
				[    // queue.
					1 => 1,
					3 => 3,
					6 => 6,
				],
				true,  // active.
				true,  // but locked.
			],
			[
				// Active and not locked, but not queued.
				6,   // site ID.
				[    // queue.
					1 => 1,
					3 => 3,
				],
				true,  // active.
				false, // not locked.
			],
			[
				// Active and not locked, last site to be migrated.
				6,   // site ID.
				[    // queue.
					6 => 6,
				],
				true,  // active.
				false, // not locked.
			],
			[
				// Active and not locked, but empty queue.
				6,     // site ID.
				[],    // queue.
				true,  // active.
				false, // not locked.
			],
		];
	}

	/**
	 * Tests ::maybe_migrate_from_global_table.
	 *
	 * @covers ::maybe_migrate_from_global_table
	 *
	 * @dataProvider provide_maybe_migrate_from_global_table_data
	 *
	 * @param  int   $site_id The site ID.
	 * @param  int[] $queue   The queue.
	 * @param  bool  $active  Whether the plugin is network active.
	 * @param  bool  $locked  Whether the option is currently locked.
	 */
	public function test_maybe_migrate_from_global_table( $site_id, $queue, $active, $locked ) {

		Functions\expect( 'plugin_basename' )->once()->with( 'plugin/file' )->andReturn( 'plugin/basename' );
		Functions\expect( 'is_plugin_active_for_network' )->once()->with( 'plugin/basename' )->andReturn( $active );

		if ( $active ) {
			$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( $queue );

			if ( ! empty( $queue ) ) {
				$this->network_options->shouldReceive( 'lock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( ! $locked );

				if ( ! $locked ) {
					Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

					$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION, [] )->andReturn( $queue );

					if ( ! empty( $queue[ $site_id ] ) ) {
						$this->sut->shouldReceive( 'migrate_from_global_table' )->once()->with( $site_id );

						if ( \count( $queue ) > 1 ) {
							$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION, m::not( m::hasKey( $site_id ) ) );
						} else {
							$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION );
						}
					} else {
						$this->sut->shouldReceive( 'migrate_from_global_table' )->never();
						$this->network_options->shouldReceive( 'set' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, m::not( m::hasKey( $site_id ) ) );
					}

					$this->network_options->shouldReceive( 'unlock' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION )->andReturn( true );
				} else {
					$this->network_options->shouldReceive( 'get' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, [] );
					$this->sut->shouldReceive( 'migrate_from_global_table' )->never();
					$this->network_options->shouldReceive( 'set' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, m::not( m::hasKey( $site_id ) ) );
					$this->network_options->shouldReceive( 'unlock' )->never();
				}
			} else {
				$this->network_options->shouldReceive( 'lock' )->never();
				$this->network_options->shouldReceive( 'get' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, [] );
				$this->sut->shouldReceive( 'migrate_from_global_table' )->never();
				$this->network_options->shouldReceive( 'set' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, m::not( m::hasKey( $site_id ) ) );
				$this->network_options->shouldReceive( 'unlock' )->never();
			}
		} else {
			$this->network_options->shouldReceive( 'lock' )->never();
			$this->network_options->shouldReceive( 'get' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, [] )->andReturn( $queue );
			$this->sut->shouldReceive( 'migrate_from_global_table' )->never();
			$this->network_options->shouldReceive( 'set' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION, m::not( m::hasKey( $site_id ) ) );
			$this->network_options->shouldReceive( 'unlock' )->never();
			$this->network_options->shouldReceive( 'get' )->never()->with( Network_Options::GLOBAL_TABLE_MIGRATION );
		}

		$this->assertNull( $this->sut->maybe_migrate_from_global_table() );
	}

	/**
	 * Tests ::migrate_from_global_table.
	 *
	 * @covers ::migrate_from_global_table
	 */
	public function test_migrate_from_global_table() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$site_id           = 7;
		$main_site_id      = 1;
		$network_id        = 5;
		$table_name        = 'my_custom_table';
		$global_table_name = 'my_global_table';
		$result            = 4;

		// Interim results.
		$global_id_1 = 2;
		$global_id_2 = 4;
		$global_id_3 = 5;
		$global_id_4 = 13;
		$email_1     = 'foo@bar.org';
		$email_2     = 'foobar@bar.org';
		$email_3     = 'bar@foo.org';
		$email_4     = 'x@foobar.org';
		$local_id_1  = 3;
		$local_id_2  = 11;

		$rows_to_migrate = [
			$global_id_1 => (object) [
				'id'           => $global_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_2 => (object) [
				'id'           => $global_id_2,
				'email'        => $email_2,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			$global_id_3 => (object) [
				'id'           => $global_id_3,
				'email'        => $email_3,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_4 => (object) [
				'id'           => $global_id_4,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$emails          = [
			$global_id_1 => $email_1,
			$global_id_2 => $email_2,
			$global_id_3 => $email_3,
			$global_id_4 => $email_4,
		];
		$existing_rows   = [
			$local_id_1  => (object) [
				'id'           => $local_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$local_id_2  => (object) [
				'id'           => $local_id_2,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];

		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $main_site_id )->andReturn( $global_table_name );

		$wpdb->shouldReceive( 'esc_like' )->once()->with( $site_id )->andReturn( $site_id );
		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SELECT * FROM %i WHERE log_message LIKE %s', $global_table_name, "set with comment % (site: %, blog: {$site_id})" )->andReturn( 'SELECT_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SELECT_QUERY', \OBJECT_K )->andReturn( $rows_to_migrate );

		Functions\expect( 'wp_list_pluck' )->once()->with( $rows_to_migrate, 'email', 'id' )->andReturn( $emails );
		$this->sut->shouldReceive( 'prepare_email_query' )->once()->with( $emails, $table_name )->andReturn( 'EMAIL_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'EMAIL_QUERY' )->andReturn( $existing_rows );

		$this->sut->shouldReceive( 'insert_or_update' )->once()->with(
			[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ],
			m::on(
				function ( $a ) {
					return 3 === \count( $a );
				}
			),
			$site_id
		)->andReturn( 3 );

		$this->sut->shouldReceive( 'prepare_delete_query' )->once()->with(
			m::on(
				function ( $a ) use ( $global_id_1, $global_id_2, $global_id_3, $global_id_4 ) {
					// ID1 and ID4 exist, but only ID4 is old enough to not be migrated, so it comes first.
					return [ $global_id_4, $global_id_1, $global_id_2, $global_id_3 ] === $a;
				}
			),
			$global_table_name
		)->andReturn( 'DELETE_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'DELETE_QUERY' )->andReturn( 4 );

		$this->assertSame( $result, $this->sut->migrate_from_global_table( $site_id ) );
	}

	/**
	 * Tests ::migrate_from_global_table.
	 *
	 * @covers ::migrate_from_global_table
	 */
	public function test_migrate_from_global_table_error() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$site_id           = 7;
		$main_site_id      = 1;
		$network_id        = 5;
		$table_name        = 'my_custom_table';
		$global_table_name = 'my_global_table';

		// Interim results.
		$global_id_1 = 2;
		$global_id_2 = 4;
		$global_id_3 = 5;
		$global_id_4 = 13;
		$email_1     = 'foo@bar.org';
		$email_2     = 'foobar@bar.org';
		$email_3     = 'bar@foo.org';
		$email_4     = 'x@foobar.org';
		$local_id_1  = 3;
		$local_id_2  = 11;

		$rows_to_migrate = [
			$global_id_1 => (object) [
				'id'           => $global_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_2 => (object) [
				'id'           => $global_id_2,
				'email'        => $email_2,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			$global_id_3 => (object) [
				'id'           => $global_id_3,
				'email'        => $email_3,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_4 => (object) [
				'id'           => $global_id_4,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$emails          = [
			$global_id_1 => $email_1,
			$global_id_2 => $email_2,
			$global_id_3 => $email_3,
			$global_id_4 => $email_4,
		];
		$existing_rows   = [
			$local_id_1  => (object) [
				'id'           => $local_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$local_id_2  => (object) [
				'id'           => $local_id_2,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];

		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $main_site_id )->andReturn( $global_table_name );

		$wpdb->shouldReceive( 'esc_like' )->once()->with( $site_id )->andReturn( $site_id );
		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SELECT * FROM %i WHERE log_message LIKE %s', $global_table_name, "set with comment % (site: %, blog: {$site_id})" )->andReturn( 'SELECT_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SELECT_QUERY', \OBJECT_K )->andReturn( $rows_to_migrate );

		Functions\expect( 'wp_list_pluck' )->once()->with( $rows_to_migrate, 'email', 'id' )->andReturn( $emails );
		$this->sut->shouldReceive( 'prepare_email_query' )->once()->with( $emails, $table_name )->andReturn( 'EMAIL_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'EMAIL_QUERY' )->andReturn( $existing_rows );

		$this->sut->shouldReceive( 'insert_or_update' )->once()->with(
			[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ],
			m::on(
				function ( $a ) {
					return 3 === \count( $a );
				}
			),
			$site_id
		)->andReturn( false );

		$this->sut->shouldReceive( 'prepare_delete_query' )->never();
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertFalse( $this->sut->migrate_from_global_table( $site_id ) );
	}

	/**
	 * Tests ::migrate_from_global_table.
	 *
	 * @covers ::migrate_from_global_table
	 */
	public function test_migrate_from_global_table_delete_error() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$site_id           = 7;
		$main_site_id      = 1;
		$network_id        = 5;
		$table_name        = 'my_custom_table';
		$global_table_name = 'my_global_table';

		// Interim results.
		$global_id_1 = 2;
		$global_id_2 = 4;
		$global_id_3 = 5;
		$global_id_4 = 13;
		$email_1     = 'foo@bar.org';
		$email_2     = 'foobar@bar.org';
		$email_3     = 'bar@foo.org';
		$email_4     = 'x@foobar.org';
		$local_id_1  = 3;
		$local_id_2  = 11;

		$rows_to_migrate = [
			$global_id_1 => (object) [
				'id'           => $global_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_2 => (object) [
				'id'           => $global_id_2,
				'email'        => $email_2,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			$global_id_3 => (object) [
				'id'           => $global_id_3,
				'email'        => $email_3,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$global_id_4 => (object) [
				'id'           => $global_id_4,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$emails          = [
			$global_id_1 => $email_1,
			$global_id_2 => $email_2,
			$global_id_3 => $email_3,
			$global_id_4 => $email_4,
		];
		$existing_rows   = [
			$local_id_1  => (object) [
				'id'           => $local_id_1,
				'email'        => $email_1,
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			$local_id_2  => (object) [
				'id'           => $local_id_2,
				'email'        => $email_4,
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];

		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $main_site_id )->andReturn( $global_table_name );

		$wpdb->shouldReceive( 'esc_like' )->once()->with( $site_id )->andReturn( $site_id );
		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SELECT * FROM %i WHERE log_message LIKE %s', $global_table_name, "set with comment % (site: %, blog: {$site_id})" )->andReturn( 'SELECT_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SELECT_QUERY', \OBJECT_K )->andReturn( $rows_to_migrate );

		Functions\expect( 'wp_list_pluck' )->once()->with( $rows_to_migrate, 'email', 'id' )->andReturn( $emails );
		$this->sut->shouldReceive( 'prepare_email_query' )->once()->with( $emails, $table_name )->andReturn( 'EMAIL_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'EMAIL_QUERY' )->andReturn( $existing_rows );

		$this->sut->shouldReceive( 'insert_or_update' )->once()->with(
			[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ],
			m::on(
				function ( $a ) {
					return 3 === \count( $a );
				}
			),
			$site_id
		)->andReturn( 3 );

		$this->sut->shouldReceive( 'prepare_delete_query' )->once()->with(
			m::on(
				function ( $a ) use ( $global_id_1, $global_id_2, $global_id_3, $global_id_4 ) {
					// ID1 and ID4 exist, but only ID4 is old enough to not be migrated, so it comes first.
					return [ $global_id_4, $global_id_1, $global_id_2, $global_id_3 ] === $a;
				}
			),
			$global_table_name
		)->andReturn( 'DELETE_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'DELETE_QUERY' )->andReturn( false );

		$this->assertFalse( $this->sut->migrate_from_global_table( $site_id ) );
	}

	/**
	 * Tests ::migrate_from_global_table.
	 *
	 * @covers ::migrate_from_global_table
	 */
	public function test_migrate_from_global_table_same_table_name() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$site_id           = 2;
		$main_site_id      = 2;
		$table_name        = 'my_global_table';
		$global_table_name = 'my_global_table';
		$result            = false;

		Functions\expect( 'get_main_site_id' )->once()->andReturn( $main_site_id );

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $main_site_id )->andReturn( $global_table_name );

		$this->assertSame( $result, $this->sut->migrate_from_global_table( $site_id ) );
	}

	/**
	 * Tests ::prepare_email_query.
	 *
	 * @covers ::prepare_email_query
	 */
	public function test_prepare_email_query() {
		global $wpdb;
		$wpdb        = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$emails      = [ 'foo@bar.org', 'foobar@example.org', 'bar@foo.com' ];
		$email_count = \count( $emails );
		$table_name  = 'my_table';
		$result      = 'EMAILS_QUERY';

		$query_args = \array_merge( [ $table_name ], $emails );

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/SELECT \* FROM %i WHERE email IN \((%s,?){{$email_count}}\)/" ), $query_args )->andReturn( $result );

		$this->assertSame( $result, $this->sut->prepare_email_query( $emails, $table_name ) );
	}

	/**
	 * Tests ::prepare_email_query.
	 *
	 * @covers ::prepare_email_query
	 */
	public function test_prepare_email_query_no_emails() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$emails     = [];
		$table_name = 'my_table';
		$result     = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_email_query( $emails, $table_name ) );
	}

	/**
	 * Tests ::prepare_email_query.
	 *
	 * @covers ::prepare_email_query
	 */
	public function test_prepare_email_query_empty_table_name() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$emails     = [ 'foo@bar.org', 'foobar@example.org', 'bar@foo.com' ];
		$table_name = '';
		$result     = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_email_query( $emails, $table_name ) );
	}

	/**
	 * Tests ::prepare_delete_query.
	 *
	 * @covers ::prepare_delete_query
	 */
	public function test_prepare_delete_query() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$ids        = [ 5, 10, 11, 17 ];
		$id_count   = \count( $ids );
		$table_name = 'my_table';
		$result     = 'DELETE_QUERY';

		$query_args = \array_merge( [ $table_name ], $ids );

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/DELETE FROM %i WHERE id IN \((%d,?){{$id_count}}\)/" ), $query_args )->andReturn( $result );

		$this->assertSame( $result, $this->sut->prepare_delete_query( $ids, $table_name ) );
	}

	/**
	 * Tests ::prepare_delete_query.
	 *
	 * @covers ::prepare_delete_query
	 */
	public function test_prepare_delete_query_no_ids() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$ids        = [];
		$table_name = 'my_table';
		$result     = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_delete_query( $ids, $table_name ) );
	}

	/**
	 * Tests ::prepare_delete_query.
	 *
	 * @covers ::prepare_delete_query
	 */
	public function test_prepare_delete_query_empty_table_name() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$ids        = [ 5, 10, 11, 17 ];
		$table_name = '';
		$result     = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_delete_query( $ids, $table_name ) );
	}

	/**
	 * Tests ::maybe_upgrade_schema.
	 *
	 * @covers ::maybe_upgrade_schema
	 */
	public function test_maybe_upgrade_schema() {
		$previous = '2.3.9';

		$this->sut->shouldReceive( 'maybe_drop_hash_column' )->once()->andReturn( true );
		$this->sut->shouldReceive( 'maybe_fix_last_updated_column_default' )->once()->andReturn( true );

		$this->assertTrue( $this->sut->maybe_upgrade_schema( $previous ) );
	}

	/**
	 * Tests ::maybe_drop_hash_column.
	 *
	 * @covers ::maybe_drop_hash_column
	 */
	public function test_maybe_drop_hash_column() {
		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'hash' )->andReturn( 'COLUMNS_QUERY' );
		$wpdb->shouldReceive( 'get_var' )->once()->with( 'COLUMNS_QUERY' )->andReturn( 'hash' );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'ALTER TABLE %i DROP COLUMN hash', $table_name )->andReturn( 'ALTER_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'ALTER_QUERY' )->andReturn( 1 );

		$this->assertTrue( $this->sut->maybe_drop_hash_column() );
	}

	/**
	 * Tests ::maybe_drop_hash_column.
	 *
	 * @covers ::maybe_drop_hash_column
	 */
	public function test_maybe_drop_hash_column_no_need() {
		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'hash' )->andReturn( 'COLUMNS_QUERY' );
		$wpdb->shouldReceive( 'get_var' )->once()->with( 'COLUMNS_QUERY' )->andReturn( false );

		$wpdb->shouldReceive( 'prepare' )->never()->with( 'ALTER TABLE `%1$s` DROP COLUMN hash', $table_name );
		$wpdb->shouldReceive( 'query' )->never()->with( 'ALTER_QUERY' );

		$this->assertFalse( $this->sut->maybe_drop_hash_column() );
	}

	/**
	 * Tests ::maybe_fix_last_updated_column_default.
	 *
	 * @covers ::maybe_fix_last_updated_column_default
	 */
	public function test_maybe_fix_last_updated_column_default() {
		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';
		$column_def = [
			'Default' => '0000-00-00 00:00:00',
			'Foo'     => 'bar',
		];

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'last_updated' )->andReturn( 'COLUMN_DEFINITION_QUERY' );
		$wpdb->shouldReceive( 'get_row' )->once()->with( 'COLUMN_DEFINITION_QUERY', \ARRAY_A )->andReturn( $column_def );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'ALTER TABLE %i MODIFY COLUMN `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL', $table_name )->andReturn( 'ALTER_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'ALTER_QUERY' )->andReturn( 1 );

		$this->assertTrue( $this->sut->maybe_fix_last_updated_column_default() );
	}

	/**
	 * Tests ::maybe_fix_last_updated_column_default.
	 *
	 * @covers ::maybe_fix_last_updated_column_default
	 */
	public function test_maybe_fix_last_updated_column_default_no_need() {
		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';
		$column_def = [
			'Default' => 'CURRENT_TIMESTAMP',
			'Foo'     => 'bar',
		];

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW COLUMNS FROM %i WHERE FIELD = %s', $table_name, 'last_updated' )->andReturn( 'COLUMN_DEFINITION_QUERY' );
		$wpdb->shouldReceive( 'get_row' )->once()->with( 'COLUMN_DEFINITION_QUERY', \ARRAY_A )->andReturn( $column_def );

		$wpdb->shouldReceive( 'prepare' )->never()->with( 'ALTER TABLE %i MODIFY COLUMN `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL', $table_name );
		$wpdb->shouldReceive( 'query' )->never()->with( 'ALTER_QUERY' );

		$this->assertFalse( $this->sut->maybe_fix_last_updated_column_default() );
	}

	/**
	 * Tests ::maybe_upgrade_data.
	 *
	 * @covers ::maybe_upgrade_data
	 */
	public function test_maybe_update_data_no_need() {
		$previous = '0.4';

		$this->sut->shouldReceive( 'fix_email_hashes' )->never();

		$this->assertSame( 0, $this->sut->maybe_upgrade_data( $previous ) );
	}
}
