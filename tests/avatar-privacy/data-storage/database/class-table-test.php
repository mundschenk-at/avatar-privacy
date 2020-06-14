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
}
