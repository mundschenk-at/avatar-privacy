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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Data_Storage;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Core\Hasher;
use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * Avatar_Privacy\Data_Storage\Database unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Database
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Database
 *
 * @uses ::__construct
 */
class Database_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Data_Storage\Database
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
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->hasher          = m::mock( Hasher::class );
		$this->network_options = m::mock( Network_Options::class );

		// Partially mock system under test.
		$this->sut = m::mock( Database::class, [ $this->hasher, $this->network_options ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Database::class )->makePartial();
		$mock->__construct( $this->hasher, $this->network_options );

		$this->assert_attribute_same( $this->hasher, 'hasher', $mock );
		$this->assert_attribute_same( $this->network_options, 'network_options', $mock );
		$this->assert_is_array( $this->get_value( $mock, 'placeholder' ) );
	}

	/**
	 * Tests ::get_table_prefix.
	 *
	 * @covers ::get_table_prefix
	 */
	public function test_get_table_prefix() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->base_prefix = 'base_prefix';
		$site_id           = 5;

		$this->sut->shouldReceive( 'use_global_table' )->once()->andReturn( true );
		$wpdb->shouldReceive( 'get_blog_prefix' )->never();

		$this->assertSame( 'base_prefix', $this->sut->get_table_prefix( $site_id ) );
	}

	/**
	 * Tests ::get_table_prefix.
	 *
	 * @covers ::get_table_prefix
	 */
	public function test_get_table_prefix_no_global_table() {
		global $wpdb;
		$wpdb              = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->base_prefix = 'base_prefix';
		$site_id           = 5;

		$this->sut->shouldReceive( 'use_global_table' )->once()->andReturn( false );
		$wpdb->shouldReceive( 'get_blog_prefix' )->once()->with( $site_id )->andReturn( 'site_prefix' );

		$this->assertSame( 'site_prefix', $this->sut->get_table_prefix( $site_id ) );
	}

	/**
	 * Tests ::get_table_name.
	 *
	 * @covers ::get_table_name
	 */
	public function test_get_table_name() {
		$site_id = 5;

		$this->sut->shouldReceive( 'get_table_prefix' )->once()->with( $site_id )->andReturn( 'prefix_' );

		$this->assertSame( 'prefix_avatar_privacy', $this->sut->get_table_name( $site_id ) );
	}

	/**
	 * Tests ::table_exists.
	 *
	 * @covers ::table_exists
	 */
	public function test_table_exists() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'foo';

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW TABLES LIKE %s', $table_name )->andReturn( 'PREPARED_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->with( 'PREPARED_SQL' )->andReturn( $table_name );

		$this->assertTrue( $this->sut->table_exists( $table_name ) );
	}

	/**
	 * Tests ::table_exists.
	 *
	 * @covers ::table_exists
	 */
	public function test_table_exists_does_not_exist() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'foo';

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW TABLES LIKE %s', $table_name )->andReturn( 'PREPARED_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->with( 'PREPARED_SQL' )->andReturn( null );

		$this->assertFalse( $this->sut->table_exists( $table_name ) );
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
	 * Provides data for testing maybe_create_table.
	 *
	 * @return array
	 */
	public function provide_maybe_create_table_data() {
		return [
			[ '0.4.1', true, true, true, true ],
			[ '0.4.2', false, true, true, true ],
			[ '0.4.3', false, false, true, true ],
			[ '0.5.1', false, false, false, true ],
			[ '0.5.2', false, true, false, false ],
			[ '0.5.3', true, true, false, false ],
		];
	}

	/**
	 * Tests ::maybe_create_table.
	 *
	 * @covers ::maybe_create_table
	 *
	 * @dataProvider provide_maybe_create_table_data
	 *
	 * @param  string $previous        A version string.
	 * @param  bool   $property_exists Whether the property $wpdb->avatar_privacy exists.
	 * @param  bool   $table_exists    Whether the database table exists.
	 * @param  bool   $update          Whether the table needs to be updated.
	 * @param  bool   $result          The expected result.
	 */
	public function test_maybe_create_table( $previous, $property_exists, $table_exists, $update, $result ) {
		global $wpdb;

		$table_name = 'my_table_name';

		// Global objects.
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $property_exists ) {
			$wpdb->avatar_privacy = $table_name;
		}

		if ( ! $update && $property_exists ) {
			$this->sut->shouldReceive( 'get_table_name' )->never();
			$this->sut->shouldReceive( 'table_exists' )->never();
			$this->sut->shouldReceive( 'db_delta' )->never();
			$this->sut->shouldReceive( 'register_table' )->never();
		} else {
			$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

			if ( ! $update && $table_exists ) {
				$this->sut->shouldReceive( 'table_exists' )->once()->with( $table_name )->andReturn( $table_exists );
				$this->sut->shouldReceive( 'db_delta' )->never();
			} else {
				$wpdb->shouldReceive( 'get_charset_collate' )->once()->andReturn( 'my_collation' );
				$this->sut->shouldReceive( 'db_delta' )->once()->with( m::type( 'string' ) );

				if ( ! $update ) {
					$this->sut->shouldReceive( 'table_exists' )->twice()->with( $table_name )->andReturn( $table_exists, true );
				} else {
					$this->sut->shouldReceive( 'table_exists' )->once()->with( $table_name )->andReturn( true );
				}
			}

			$this->sut->shouldReceive( 'register_table' )->once()->with( $wpdb, $table_name );
		}

		$this->assertSame( $result, $this->sut->maybe_create_table( $previous ) );
	}

	/**
	 * Tests ::maybe_create_table.
	 *
	 * @covers ::maybe_create_table
	 */
	public function test_maybe_create_table_failure() {
		global $wpdb;

		$previous   = '0.5';
		$table_name = 'my_table_name';

		// Global objects.
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );
		$this->sut->shouldReceive( 'table_exists' )->twice()->with( $table_name )->andReturn( false );

		$wpdb->shouldReceive( 'get_charset_collate' )->once()->andReturn( 'my_collation' );

		$this->sut->shouldReceive( 'db_delta' )->once()->with( m::type( 'string' ) );

		$this->sut->shouldReceive( 'register_table' )->never();

		// This should never happen in the real world.
		$this->assertFalse( $this->sut->maybe_create_table( $previous ) );
		$this->assertFalse( isset( $wpdb->avatar_privacy ) );
	}

	/**
	 * Tests ::register_table.
	 *
	 * @covers ::register_table
	 */
	public function test_register_table() {
		$table_name = 'my_table_name';
		$db         = m::mock( \wpdb::class );

		// Make sure the $wpdb properties exist for the test.
		$db->tables           = [];
		$db->ms_global_tables = [];

		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		$this->sut->shouldReceive( 'use_global_table' )->never();

		$this->assertNull( $this->sut->register_table( $db, $table_name ) );

		$this->assert_attribute_contains( Database::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_not_contains( Database::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, Database::TABLE_BASENAME, $db );
	}

	/**
	 * Tests ::register_table.
	 *
	 * @covers ::register_table
	 */
	public function test_register_table_multisite_no_global_table() {
		$table_name = 'my_table_name';
		$db         = m::mock( \wpdb::class );

		// Make sure the $wpdb properties exist for the test.
		$db->tables           = [];
		$db->ms_global_tables = [];

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		$this->sut->shouldReceive( 'use_global_table' )->once()->andReturn( false );

		$this->assertNull( $this->sut->register_table( $db, $table_name ) );

		$this->assert_attribute_contains( Database::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_not_contains( Database::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, Database::TABLE_BASENAME, $db );
	}

	/**
	 * Tests ::register_table.
	 *
	 * @covers ::register_table
	 */
	public function test_register_table_multisite_with_global_table() {
		$table_name = 'my_table_name';
		$db         = m::mock( \wpdb::class );

		// Make sure the $wpdb properties exist for the test.
		$db->tables          = [];
		$db->ms_globaltables = [];

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		$this->sut->shouldReceive( 'use_global_table' )->once()->andReturn( true );

		$this->assertNull( $this->sut->register_table( $db, $table_name ) );

		$this->assert_attribute_not_contains( Database::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_contains( Database::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, Database::TABLE_BASENAME, $db );
	}

	/**
	 * Tests ::db_delta.
	 *
	 * @covers ::db_delta
	 */
	public function test_db_delta() {
		$queries = [ 'foo' ];
		$execute = true;
		$result  = [ 'foo' ];

		// Does not work on PHP 5.6.
		if ( \version_compare( \phpversion(), '7.0', '>=' ) ) {
			// Function undefined.
			$this->expectOutputString( 'UPGRADE_PHP' );
			$this->expectExceptionMessage( 'Call to undefined function dbDelta()' );
			$this->assertSame( $result, $this->sut->db_delta( $queries, $execute ) );
		}

		// Function defined.
		Functions\expect( 'dbDelta' )->once()->with( $queries, $execute )->andReturn( $result );
		$this->assertSame( $result, $this->sut->db_delta( $queries, $execute ) );
	}

	/**
	 * Tests ::drop_table.
	 *
	 * @covers ::drop_table
	 */
	public function test_drop_table() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$site_id    = 7;
		$table_name = 'my_custom_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$wpdb->shouldReceive( 'query' )->once()->with( "DROP TABLE IF EXISTS {$table_name};" );

		$this->assertNull( $this->sut->drop_table( $site_id ) );
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
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SELECT_QUERY', OBJECT_K )->andReturn( $rows_to_migrate );

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
			$table_name
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
		$network_id        = 5;
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
	 * @uses Avatar_Privacy\Data_Storage\Database::get_placeholders
	 * @uses Avatar_Privacy\Data_Storage\Database::get_prepared_values
	 * @uses Avatar_Privacy\Data_Storage\Database::get_update_clause
	 */
	public function test_prepare_insert_update_query() {
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
		$total_count     = $update_count + $migrate_count;
		$table_name      = 'my_table';
		$result          = 'INSERT_UPDATE_QUERY';

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/INSERT INTO `{$table_name}` \(id,email,hash,use_gravatar,last_updated,log_message\)\s+VALUES (\(%d,%s,%s,%d,%s,%s\),?){{$update_count}}(\(NULL,%s,%s,%d,%s,%s\),?){{$migrate_count}}\s+ON DUPLICATE KEY UPDATE\s+email = VALUES\(email\),\s+hash = VALUES\(hash\),\s+use_gravatar = VALUES\(use_gravatar\),\s+last_updated = VALUES\(last_updated\),\s+log_message = VALUES\(log_message\)/mu" ), m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name ) );
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
		$result          = false;

		$wpdb->shouldReceive( 'prepare' )->never();

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name ) );
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

		$this->assertSame( $result, $this->sut->prepare_insert_update_query( $rows_to_update, $rows_to_migrate, $table_name ) );
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
		$fields          = [ 'foobar' ];
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
				"email = VALUES(email),\nhash = VALUES(hash),\nuse_gravatar = VALUES(use_gravatar),\nlast_updated = last_updated,\nlog_message = log_message",
			],
			[
				[ 'log_message' ],
				"email = email,\nhash = hash,\nuse_gravatar = use_gravatar,\nlast_updated = last_updated,\nlog_message = VALUES(log_message)",
			],
			[
				[ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ],
				"email = VALUES(email),\nhash = VALUES(hash),\nuse_gravatar = VALUES(use_gravatar),\nlast_updated = VALUES(last_updated),\nlog_message = VALUES(log_message)",
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
	 * Tests ::maybe_upgrade_table_data.
	 *
	 * @covers ::maybe_upgrade_table_data
	 */
	public function test_maybe_upgrade_table_data() {
		$network_id = 5;
		$site_id    = 3;
		$rows       = [
			3  => (object) [
				'id'           => 3,
				'email'        => 'foo@bar.org',
			],
			11 => (object) [
				'id'           => 11,
				'email'        => 'xxx@foobar.org',
			],
		];
		$fields     = [
			'email',
			'use_gravatar',
			'last_updated',
		];
		$result     = 2; // Affected row count.

		// Fake global.
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

		$wpdb->shouldReceive( 'get_results' )->once()->with( "SELECT id, email FROM {$table_name} WHERE hash is null", \OBJECT_K )->andReturn( $rows );
		$this->hasher->shouldReceive( 'get_hash' )->times( \count( $rows ) )->with( m::type( 'string' ) )->andReturn( 'hashed email' );

		$this->sut->shouldReceive( 'prepare_insert_update_query' )->once()->with( $rows, [], $table_name, [ 'hash' ] )->andReturn( 'UPDATE_QUERY' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'UPDATE_QUERY' );

		$this->assertSame( $result, $this->sut->maybe_upgrade_table_data() );
	}

	/**
	 * Tests ::maybe_upgrade_table_data.
	 *
	 * @covers ::maybe_upgrade_table_data
	 */
	public function test_maybe_upgrade_table_data_error() {
		$network_id = 5;
		$site_id    = 3;
		$rows       = [
			3  => (object) [
				'id'           => 3,
				'email'        => 'foo@bar.org',
			],
			11 => (object) [
				'id'           => 11,
				'email'        => 'xxx@foobar.org',
			],
		];
		$fields     = [
			'email',
			'use_gravatar',
			'last_updated',
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

		$this->assertSame( 0, $this->sut->maybe_upgrade_table_data() );
	}
}
