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

use Avatar_Privacy\Data_Storage\Database\Table;

/**
 * Avatar_Privacy\Data_Storage\Database\Table unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Database\Table
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Database\Table
 *
 * @uses ::__construct
 */
class Table_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Table
	 */
	private $sut;

	const TABLE_BASENAME   = 'my_table';
	const UPDATE_THRESHOLD = '0.5';

	// Re-use actual fields from Column_Author_Table to keep test cases close to reality.
	const COLUMN_FORMATS = [
		'id'           => '%d',
		'email'        => '%s',
		'hash'         => '%s',
		'use_gravatar' => '%d',
		'last_updated' => '%s',
		'log_message'  => '%s',
	];

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

		// Partially mock system under test.
		$this->sut = m::mock( Table::class, [ self::TABLE_BASENAME, self::UPDATE_THRESHOLD, self::COLUMN_FORMATS ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$table_basename   = 'foobar';
		$update_threshold = '0.6';
		$column_formats   = [
			'foobar' => '%s',
			'foobaz' => '%d',
		];

		$mock = m::mock( Table::class )->makePartial();
		$mock->__construct( $table_basename, $update_threshold, $column_formats );

		$this->assert_attribute_same( $table_basename, 'table_basename', $mock );
		$this->assert_attribute_same( $update_threshold, 'update_threshold', $mock );
		$this->assert_attribute_same( $column_formats, 'column_formats', $mock );
	}

	/**
	 * Tests ::setup.
	 *
	 * @covers ::setup
	 */
	public function test_setup() {
		$previous_version = '1.1.0';

		$this->sut->shouldReceive( 'maybe_create_table' )->once()->with( $previous_version )->andReturn( true );
		$this->sut->shouldReceive( 'maybe_upgrade_data' )->once()->with( $previous_version );

		$this->assertNull( $this->sut->setup( $previous_version ) );
	}

	/**
	 * Tests ::setup.
	 *
	 * @covers ::setup
	 */
	public function test_setup_table_exists() {
		$previous_version = '1.1.0';

		$this->sut->shouldReceive( 'maybe_create_table' )->once()->with( $previous_version )->andReturn( false );
		$this->sut->shouldReceive( 'maybe_upgrade_data' )->never();

		$this->assertNull( $this->sut->setup( $previous_version ) );
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
		$prefix  = 'my_prefix_';

		$this->sut->shouldReceive( 'get_table_prefix' )->once()->with( $site_id )->andReturn( $prefix );

		$this->assertSame( $prefix . self::TABLE_BASENAME, $this->sut->get_table_name( $site_id ) );
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
			$table_basename        = self::TABLE_BASENAME;
			$wpdb->$table_basename = $table_name;
		}

		if ( ! $update && $property_exists ) {
			$this->sut->shouldReceive( 'get_table_name' )->never();
			$this->sut->shouldReceive( 'table_exists' )->never();
			$this->sut->shouldReceive( 'get_table_definition' )->never();
			$this->sut->shouldReceive( 'db_delta' )->never();
			$this->sut->shouldReceive( 'register_table' )->never();
		} else {
			$this->sut->shouldReceive( 'get_table_name' )->once()->andReturn( $table_name );

			if ( ! $update && $table_exists ) {
				$this->sut->shouldReceive( 'table_exists' )->once()->with( $table_name )->andReturn( $table_exists );
				$this->sut->shouldReceive( 'get_table_definition' )->never();
				$this->sut->shouldReceive( 'db_delta' )->never();
			} else {
				$this->sut->shouldReceive( 'get_table_definition' )->once()->with( $table_name )->andReturn( 'SQL string' );
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

		$this->sut->shouldReceive( 'get_table_definition' )->once()->with( $table_name )->andReturn( 'SQL string' );
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

		$this->assert_attribute_contains( self::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_not_contains( self::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, self::TABLE_BASENAME, $db );
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

		$this->assert_attribute_contains( self::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_not_contains( self::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, self::TABLE_BASENAME, $db );
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

		$this->assert_attribute_not_contains( self::TABLE_BASENAME, 'tables', $db );
		$this->assert_attribute_contains( self::TABLE_BASENAME, 'ms_global_tables', $db );
		$this->assert_attribute_same( $table_name, self::TABLE_BASENAME, $db );
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
	 * Tests ::get_format.
	 *
	 * @covers ::get_format
	 */
	public function test_get_format() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->assertSame( $expected, $this->sut->get_format( $columns ) );
	}

	/**
	 * Tests ::get_format.
	 *
	 * @covers ::get_format
	 */
	public function test_get_format_invalid_column() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
			'foo'          => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->expectException( \RuntimeException::class );

		$this->assertSame( $expected, $this->sut->get_format( $columns ) );
	}

	/**
	 * Tests ::insert.
	 *
	 * @covers ::insert
	 */
	public function test_insert() {
		$data       = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$site_id    = 23;
		$result     = 1;
		$table_name = 'my_table';
		$formats    = [ '%s', '%d', '%s' ];

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andReturn( $formats );
		$wpdb->shouldReceive( 'insert' )->once()->with( $table_name, $data, $formats )->andReturn( $result );

		$this->assertSame( $result, $this->sut->insert( $data, $site_id ) );
	}

	/**
	 * Tests ::insert.
	 *
	 * @covers ::insert
	 */
	public function test_insert_invalid_column() {
		$data       = [
			'foo'          => 'bar',
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$site_id    = 23;
		$table_name = 'my_table';

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andThrow( \RuntimeException::class );
		$wpdb->shouldReceive( 'insert' )->never();

		$this->assertFalse( $this->sut->insert( $data, $site_id ) );
	}

	/**
	 * Tests ::replace.
	 *
	 * @covers ::replace
	 */
	public function test_replace() {
		$data       = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$site_id    = 23;
		$result     = 1;
		$table_name = 'my_table';
		$formats    = [ '%s', '%d', '%s' ];

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andReturn( $formats );
		$wpdb->shouldReceive( 'replace' )->once()->with( $table_name, $data, $formats )->andReturn( $result );

		$this->assertSame( $result, $this->sut->replace( $data, $site_id ) );
	}

	/**
	 * Tests ::replace.
	 *
	 * @covers ::replace
	 */
	public function test_replace_invalid_column() {
		$data       = [
			'foo'          => 'bar',
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$site_id    = 23;
		$table_name = 'my_table';

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andThrow( \RuntimeException::class );
		$wpdb->shouldReceive( 'replace' )->never();

		$this->assertFalse( $this->sut->replace( $data, $site_id ) );
	}

	/**
	 * Tests ::update.
	 *
	 * @covers ::update
	 */
	public function test_update() {
		$data          = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$site_id       = 23;
		$where         = [
			'email' => 'foo@bar',
		];
		$result        = 1;
		$table_name    = 'my_table';
		$formats       = [ '%s', '%d', '%s' ];
		$where_formats = [ '%s' ];

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andReturn( $formats );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $where )->andReturn( $where_formats );
		$wpdb->shouldReceive( 'update' )->once()->with( $table_name, $data, $where, $formats, $where_formats )->andReturn( $result );

		$this->assertSame( $result, $this->sut->update( $data, $where, $site_id ) );
	}

	/**
	 * Tests ::update.
	 *
	 * @covers ::update
	 */
	public function test_update_invalid_column() {
		$data       = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$where      = [
			'foo' => 'bar',
		];
		$site_id    = 23;
		$table_name = 'my_table';
		$formats    = [ '%s', '%d', '%s' ];

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $data )->andReturn( $formats );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $where )->andThrow( \RuntimeException::class );
		$wpdb->shouldReceive( 'update' )->never();

		$this->assertFalse( $this->sut->update( $data, $where, $site_id ) );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete() {
		$where      = [
			'id' => 66,
		];
		$site_id    = 23;
		$result     = 1;
		$table_name = 'my_table';
		$formats    = [ '%d' ];

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $where )->andReturn( $formats );
		$wpdb->shouldReceive( 'delete' )->once()->with( $table_name, $where, $formats )->andReturn( $result );

		$this->assertSame( $result, $this->sut->delete( $where, $site_id ) );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete_invalid_column() {
		$where      = [
			'foo' => 'bar',
		];
		$site_id    = 23;
		$table_name = 'my_table';

		global $wpdb;
		$wpdb = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );
		$this->sut->shouldReceive( 'get_format' )->once()->with( $where )->andThrow( \RuntimeException::class );
		$wpdb->shouldReceive( 'delete' )->never();

		$this->assertFalse( $this->sut->delete( $where, $site_id ) );
	}

	/**
	 * Tests ::insert_or_update.
	 *
	 * @covers ::insert_or_update
	 *
	 * @uses ::prepare_rows
	 * @uses ::get_format
	 * @uses ::prepare_values
	 * @uses ::get_update_clause
	 */
	public function test_insert_or_update() {
		global $wpdb;
		$wpdb          = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$network_id    = 5;
		$site_id       = 3;
		$fields        = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		$rows          = [
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
		$migrate_count = \count( $rows );
		$table_name    = 'my_table';
		$query         = 'INSERT_UPDATE_QUERY';

		$this->sut->shouldReceive( 'get_table_name' )->once()->with( $site_id )->andReturn( $table_name );

		$wpdb->shouldReceive( 'prepare' )->once()->with( m::pattern( "/INSERT INTO `{$table_name}` \( email,hash,use_gravatar,last_updated,log_message \)\s+VALUES (\(%s,%s,%d,%s,%s\),?){{$migrate_count}}\s+ON DUPLICATE KEY UPDATE\s+id = id,\s+email = VALUES\(email\),\s+hash = VALUES\(hash\),\s+use_gravatar = VALUES\(use_gravatar\),\s+last_updated = VALUES\(last_updated\),\s+log_message = VALUES\(log_message\)/mu" ), m::type( 'array' ) )->andReturn( $query );
		$wpdb->shouldReceive( 'query' )->once()->with( $query )->andReturn( $migrate_count );

		$this->assertSame( $migrate_count, $this->sut->insert_or_update( $fields, $rows, $site_id ) );
	}

	/**
	 * Tests ::insert_or_update.
	 *
	 * @covers ::insert_or_update
	 */
	public function test_insert_or_update_no_rows() {
		global $wpdb;
		$wpdb    = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$rows    = [];
		$fields  = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
		$site_id = 23;

		$this->sut->shouldReceive( 'get_table_name' )->never();
		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertFalse( $this->sut->insert_or_update( $fields, $rows, $site_id ) );
	}

	/**
	 * Tests ::insert_or_update.
	 *
	 * @covers ::insert_or_update
	 */
	public function test_insert_or_update_no_valid_fields() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
		$fields     = [ 'invalid_colummn' ];

		$this->sut->shouldReceive( 'get_table_name' )->never();

		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertFalse( $this->sut->insert_or_update( $fields, $rows, $site_id ) );
	}

	/**
	 * Tests ::insert_or_update.
	 *
	 * @covers ::insert_or_update
	 *
	 * @uses ::prepare_rows
	 * @uses ::prepare_values
	 * @uses ::get_update_clause
	 */
	public function test_insert_or_update_get_format_exception() {
		global $wpdb;
		$wpdb       = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$network_id = 5;
		$site_id    = 3;
		$fields     = [ 'email', 'hash', 'use_gravatar', 'last_updated', 'log_message' ];
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

		$this->sut->shouldReceive( 'get_format' )->once()->andThrow( \RuntimeException::class );
		$this->sut->shouldReceive( 'get_table_name' )->never();

		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertFalse( $this->sut->insert_or_update( $fields, $rows, $site_id ) );
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
	 * Tests ::prepare_rows.
	 *
	 * @covers ::prepare_rows
	 */
	public function test_prepare_rows() {
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
		$fields     = [ 'email', 'log_message', 'last_updated', 'foo' ];
		$result     = [
			[
				'email'        => 'foo@bar.org',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'last_updated' => '2018-12-17 22:23:08',
				'foo'          => null,
			],
			[
				'email'        => 'xxx@foobar.org',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'last_updated' => '2018-12-19 10:00:00',
				'foo'          => null,
			],
			[
				'email'        => 'foobar@bar.org',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'last_updated' => '2018-12-18 10:00:00',
				'foo'          => null,
			],
			[
				'email'        => 'bar@foo.org',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'last_updated' => '2018-12-18 10:00:00',
				'foo'          => null,
			],
			[
				'email'        => 'x@foobar.org',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'last_updated' => '2018-12-18 10:00:00',
				'foo'          => null,
			],
		];

		$this->assertSame( $result, $this->sut->prepare_rows( $rows, $fields ) );
	}

	/**
	 * Tests ::prepare_values.
	 *
	 * @covers ::prepare_values
	 */
	public function test_prepare_values() {
		$network_id = 5;
		$site_id    = 3;
		$rows       = [
			[
				'id'           => null,
				'email'        => 'foo@bar.org',
				'last_updated' => '2018-12-17 22:23:08',
				'use_gravatar' => 1,
			],
			[
				'id'           => 7,
				'email'        => 'xxx@foobar.org',
				'hash'         => 'hash',
				'last_updated' => '2018-12-19 10:00:00',
				'log_message'  => "set with comment 8 (site: {$network_id}, blog: {$site_id})",
				'use_gravatar' => 1,
			],
		];
		$result     = [
			// no ID.
			'foo@bar.org',
			'2018-12-17 22:23:08',
			// no log_message.
			1, // use_gravatar.
			7, // ID.
			'xxx@foobar.org',
			'hash',
			'2018-12-19 10:00:00',
			"set with comment 8 (site: {$network_id}, blog: {$site_id})",
			1, // use_gravatar.
		];

		$this->assertSame( $result, $this->sut->prepare_values( $rows ) );
	}
}
