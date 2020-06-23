<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Tools\Hasher;

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
	 * @var Comment_Author_Table
	 */
	private $sut;

	/**
	 * Helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Helper object.
	 *
	 * @var Network_Options
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

		$filesystem = [
			'wordpress' => [
				'path' => [
					'wp-admin' => [
						'includes' => [
							'upgrade.php'  => 'UPGRADE_PHP',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->hasher          = m::mock( Hasher::class );
		$this->network_options = m::mock( Network_Options::class );

		// Partially mock system under test.
		$this->sut = m::mock( Comment_Author_Table::class, [ $this->hasher, $this->network_options ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Comment_Author_Table::class )->makePartial();
		$mock->__construct( $this->hasher, $this->network_options );

		$this->assert_attribute_same( $this->hasher, 'hasher', $mock );
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
		$wpdb->shouldReceive( 'prepare' )->once()->with( "SELECT * FROM `{$global_table_name}` WHERE log_message LIKE %s", "set with comment % (site: %, blog: {$site_id})" )->andReturn( 'SELECT_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SELECT_QUERY', \OBJECT_K )->andReturn( $rows_to_migrate );

		Functions\expect( 'wp_list_pluck' )->once()->with( $rows_to_migrate, 'email', 'id' )->andReturn( $emails );
		$this->sut->shouldReceive( 'prepare_email_query' )->once()->with( $emails, $table_name )->andReturn( 'EMAIL_QUERY' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'EMAIL_QUERY' )->andReturn( $existing_rows );

		$this->sut->shouldReceive( 'prepare_insert_update_query' )->once()->with(
			m::on(
				function( $a ) use ( $local_id_1 ) {
					return \array_keys( $a ) === [ $local_id_1 ];
				}
			),
			m::on(
				function( $a ) use ( $global_id_2, $global_id_3 ) {
					return \array_keys( $a ) === [ $global_id_2, $global_id_3 ];
				}
			),
			$table_name,
			[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ]
		)->andReturn( 'INSERT_UPDATE_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'INSERT_UPDATE_QUERY' )->andReturn( 4 );

		$this->sut->shouldReceive( 'prepare_delete_query' )->once()->with(
			m::on(
				function( $a ) use ( $global_id_1, $global_id_2, $global_id_3, $global_id_4 ) {
					// ID1 and ID4 exist, therefore they come first here.
					// ID2 and ID3 are migrated.
					return [ $global_id_1, $global_id_4, $global_id_2, $global_id_3 ] === $a;
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
	 * Tests ::prepare_insert_update_query.
	 *
	 * @covers ::prepare_insert_update_query
	 *
	 * @uses ::get_placeholders
	 * @uses ::get_format
	 * @uses ::get_prepared_values
	 * @uses ::get_update_clause
	 */
	public function test_prepare_insert_update_query() {
		global $wpdb;
		$wpdb            = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$network_id      = 5;
		$site_id         = 3;
		$fields          = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		$rows_to_update  = [
			3  => (object) [
				'id'           => 1,
				'email'        => 'foo@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			11 => (object) [
				'id'           => 7,
				'email'        => 'xxx@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];
		$update_count    = \count( $rows_to_update );
		$rows_to_migrate = [
			(object) [
				'id'           => 32,
				'email'        => 'foobar@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			(object) [
				'id'           => 33,
				'email'        => 'bar@foo.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			(object) [
				'id'           => 66,
				'email'        => 'x@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$migrate_count   = \count( $rows_to_migrate );
		$table_name      = 'my_table';
		$result          = 'INSERT_UPDATE_QUERY';

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/INSERT INTO `{$table_name}` \(id,email,hash,use_gravatar,last_updated,log_message\)\s+VALUES (\(%d,%s,%s,%d,%s,%s\),?){{$update_count}}(\(NULL,%s,%s,%d,%s,%s\),?){{$migrate_count}}\s+ON DUPLICATE KEY UPDATE\s+id = id,\s+email = VALUES\(email\),\s+hash = VALUES\(hash\),\s+use_gravatar = VALUES\(use_gravatar\),\s+last_updated = VALUES\(last_updated\),\s+log_message = VALUES\(log_message\)/mu" ), m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name, $fields ) );
	}

	/**
	 * Tests ::prepare_insert_update_query.
	 *
	 * @covers ::prepare_insert_update_query
	 */
	public function test_prepare_insert_update_query_no_rows() {
		global $wpdb;
		$wpdb            = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$rows_to_update  = [];
		$rows_to_migrate = [];
		$table_name      = 'my_table';
		$fields          = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		$result          = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name, $fields ) );
	}

	/**
	 * Tests ::prepare_insert_update_query.
	 *
	 * @covers ::prepare_insert_update_query
	 */
	public function test_prepare_insert_update_query_empty_table_name() {
		global $wpdb;
		$wpdb            = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$network_id      = 5;
		$site_id         = 3;
		$fields          = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		$rows_to_update  = [
			3  => (object) [
				'id'           => 1,
				'email'        => 'foo@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			11 => (object) [
				'id'           => 7,
				'email'        => 'xxx@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];
		$rows_to_migrate = [
			(object) [
				'id'           => 32,
				'email'        => 'foobar@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			(object) [
				'id'           => 33,
				'email'        => 'bar@foo.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			(object) [
				'id'           => 66,
				'email'        => 'x@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$table_name      = '';
		$result          = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name, $fields ) );
	}

	/**
	 * Tests ::prepare_insert_update_query.
	 *
	 * @covers ::prepare_insert_update_query
	 */
	public function test_prepare_insert_update_query_no_valid_fields() {
		global $wpdb;
		$wpdb            = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$network_id      = 5;
		$site_id         = 3;
		$rows_to_update  = [
			3  => (object) [
				'id'           => 1,
				'email'        => 'foo@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			11 => (object) [
				'id'           => 7,
				'email'        => 'xxx@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];
		$rows_to_migrate = [
			(object) [
				'id'           => 32,
				'email'        => 'foobar@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
			(object) [
				'id'           => 33,
				'email'        => 'bar@foo.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			(object) [
				'id'           => 66,
				'email'        => 'x@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-18 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 0,
			],
		];
		$table_name      = 'foobar';
		$fields          = [ 'invalid_colummn' ];
		$result          = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name, $fields ) );
	}

	/**
	 * Provides data for testing get_update_clause.
	 *
	 * @return array
	 */
	public function provide_get_update_clause_data() {
		return [
			[
				[ 'email', 'hash', 'use_gravatar' ],
				"id = id,\nemail = VALUES(email),\nhash = VALUES(hash),\nuse_gravatar = VALUES(use_gravatar),\nlast_updated = last_updated,\nlog_message = log_message",
			],
			[
				[ 'log_message' ],
				"id = id,\nemail = email,\nhash = hash,\nuse_gravatar = use_gravatar,\nlast_updated = last_updated,\nlog_message = VALUES(log_message)",
			],
			[
				[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ],
				"id = id,\nemail = VALUES(email),\nhash = VALUES(hash),\nuse_gravatar = VALUES(use_gravatar),\nlast_updated = VALUES(last_updated),\nlog_message = VALUES(log_message)",
			],

		];
	}

	/**
	 * Tests ::get_update_clause.
	 *
	 * @covers ::get_update_clause
	 *
	 * @dataProvider provide_get_update_clause_data
	 *
	 * @param string[] $fields  A list of columns.
	 * @param string   $result  The expected result.
	 */
	public function test_get_update_clause( array $fields, $result ) {
		$this->assertSame( $result, $this->sut->get_update_clause( $fields ) );
	}

	/**
	 * Tests ::get_prepared_values.
	 *
	 * @covers ::get_prepared_values
	 */
	public function test_get_prepared_values() {
		$network_id = 5;
		$site_id    = 3;
		$rows       = [
			3  => (object) [
				'id'           => 1,
				'email'        => 'foo@bar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-17 22:23:08',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
			11 => (object) [
				'id'           => 7,
				'email'        => 'xxx@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];
		$fields     = [
			'email',
			'use_gravatar',
			'last_updated',
		];
		$result     = [
			3, // real ID.
			'foo@bar.org',
			1, // use_gravatar.
			'2018-12-17 22:23:08',
			11, // real ID.
			'xxx@foobar.org',
			1, // use_gravatar.
			'2018-12-19 10:00:00',
		];

		$this->assertSame( $result, $this->sut->get_prepared_values( $rows, $fields, true ) );
	}

	/**
	 * Provides data for testing get_placeholders.
	 *
	 * @return array
	 */
	public function provide_get_placeholders_data() {
		return [
			[ [ 'email', 'hash', 'use_gravatar' ], true, '(%d,%s,%s,%d)' ],
			[ [ 'email', 'hash', 'use_gravatar' ], false, '(NULL,%s,%s,%d)' ],
			[ [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ], true, '(%d,%s,%s,%d,%s,%s)' ],
		];
	}

	/**
	 * Tests ::get_placeholders.
	 *
	 * @covers ::get_placeholders
	 *
	 * @uses ::get_format
	 *
	 * @dataProvider provide_get_placeholders_data
	 *
	 * @param string[] $fields  A list of columns.
	 * @param bool     $with_id Whether the ID should be included.
	 * @param string   $result  The expected result.
	 */
	public function test_get_placeholders( array $fields, $with_id, $result ) {
		$this->assertSame( $result, $this->sut->get_placeholders( $fields, $with_id ) );
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

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/SELECT \* FROM `{$table_name}` WHERE email IN \((%s,?){{$email_count}}\)/" ), $emails )->andReturn( $result );

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

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/DELETE FROM `{$table_name}` WHERE id IN \((%d,?){{$id_count}}\)/" ), $ids )->andReturn( $result );

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
	 * Tests ::maybe_upgrade_data.
	 *
	 * @covers ::maybe_upgrade_data
	 */
	public function test_maybe_upgrade_data() {
		$previous = '0.4';
		$rows     = 5;

		$this->sut->shouldReceive( 'fix_email_hashes' )->once()->andReturn( $rows );

		$this->assertSame( $rows, $this->sut->maybe_upgrade_data( $previous ) );
	}

	/**
	 * Tests ::maybe_upgrade_data.
	 *
	 * @covers ::maybe_upgrade_data
	 */
	public function test_maybe_update_data_no_need() {
		$previous = '0.5';

		$this->sut->shouldReceive( 'fix_email_hashes' )->never();

		$this->assertSame( 0, $this->sut->maybe_upgrade_data( $previous ) );
	}

	/**
	 * Tests ::fix_email_hashes.
	 *
	 * @covers ::fix_email_hashes
	 */
	public function test_fix_email_hashes() {
		$rows   = [
			3  => (object) [
				'id'           => 3,
				'email'        => 'foo@bar.org',
			],
			11 => (object) [
				'id'           => 11,
				'email'        => 'xxx@foobar.org',
			],
		];
		$result = 2; // Affected row count.

		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'get_results' )->once()->with( "SELECT id, email FROM {$table_name} WHERE hash is null", \OBJECT_K )->andReturn( $rows );
		$this->hasher->shouldReceive( 'get_hash' )->times( \count( $rows ) )->with( m::type( 'string' ) )->andReturn( 'hashed email' );

		$this->sut->shouldReceive( 'prepare_insert_update_query' )->once()->with( $rows, [], $table_name, [ 'hash' ] )->andReturn( 'UPDATE_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'UPDATE_QUERY' );

		$this->assertSame( $result, $this->sut->fix_email_hashes() );
	}

	/**
	 * Tests ::fix_email_hashes.
	 *
	 * @covers ::fix_email_hashes
	 */
	public function test_fix_email_hashes_error() {
		$rows = [
			3  => (object) [
				'id'           => 3,
				'email'        => 'foo@bar.org',
			],
			11 => (object) [
				'id'           => 11,
				'email'        => 'xxx@foobar.org',
			],
		];

		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'get_results' )->once()->with( "SELECT id, email FROM {$table_name} WHERE hash is null", \OBJECT_K )->andReturn( $rows );
		$this->hasher->shouldReceive( 'get_hash' )->times( \count( $rows ) )->with( m::type( 'string' ) )->andReturn( 'hashed email' );

		$this->sut->shouldReceive( 'prepare_insert_update_query' )->once()->with( $rows, [], $table_name, [ 'hash' ] )->andReturn( false );
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertSame( 0, $this->sut->fix_email_hashes() );
	}
}
