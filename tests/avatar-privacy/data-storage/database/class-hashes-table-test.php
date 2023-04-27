<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2023 Peter Putzer.
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

use Avatar_Privacy\Data_Storage\Database\Hashes_Table;

/**
 * Avatar_Privacy\Data_Storage\Database\Hashes_Table unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Database\Hashes_Table
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Database\Hashes_Table
 */
class Hashes_Table_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Hashes_Table
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

		// Partially mock system under test.
		$this->sut = m::mock( Hashes_Table::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses Avatar_Privacy\Data_Storage\Database\Table::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Hashes_Table::class )->makePartial();
		$mock->__construct();

		$this->assert_attribute_same( Hashes_Table::COLUMN_FORMATS, 'column_formats', $mock );
	}

	/**
	 * Tests ::use_global_table.
	 *
	 * @covers ::use_global_table
	 */
	public function test_use_global_table() {
		$this->assertFalse( $this->sut->use_global_table() );
	}

	/**
	 * Provides data for testing get_table_definition.
	 *
	 * @return mixed[]
	 */
	public function provide_get_table_definition_data(): array {
		return [
			[ false, 175 ],
			[ true, 256 ],
		];
	}

	/**
	 * Tests ::get_table_definition.
	 *
	 * @covers ::get_table_definition
	 *
	 * @dataProvider provide_get_table_definition_data
	 *
	 * @param bool $supports_large_index Whether large indices are supported by the DB server.
	 * @param int  $identifier_length    The expected length of the `identifier` column.
	 */
	public function test_get_table_definition( bool $supports_large_index, int $identifier_length ) {
		$table_name = 'my_table';

		$this->sut->shouldReceive( 'database_supports_large_index' )->once()->andReturn( $supports_large_index );

		$this->assert_matches_regular_expression( "/^CREATE TABLE {$table_name} \(\s+identifier varchar\({$identifier_length}\).*\)\$/sum", $this->sut->get_table_definition( $table_name ) );
	}

	/**
	 * Provides data for testing database_supports_large_index.
	 *
	 * @return mixed[]
	 */
	public function provide_database_supports_large_index_data(): array {
		return [
			[ null, false ],
			[ '5.6.1', false ],
			[ '5.7.0', true ],
			[ '10.6.12', true ],
		];
	}

	/**
	 * Tests ::database_supports_large_index.
	 *
	 * @covers ::database_supports_large_index
	 *
	 * @dataProvider provide_database_supports_large_index_data
	 *
	 * @param null|string $innodb_version The InnoDB storage engine version.
	 * @param bool        $result         The expected result.
	 */
	public function test_database_supports_large_index( ?string $innodb_version, bool $result ): void {
		global $wpdb;
		$wpdb = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wpdb->shouldReceive( 'prepare' )->once()->with( 'SHOW VARIABLES LIKE "innodb_version"',  )->andReturn( 'PREPARED_SQL' );
		$wpdb->shouldReceive( 'get_var' )->once()->with( 'PREPARED_SQL', 1 )->andReturn( $innodb_version );

		$this->assertSame( $result, $this->sut->database_supports_large_index() );
	}


	/**
	 * Tests ::maybe_upgrade_schema.
	 *
	 * @covers ::maybe_upgrade_schema
	 */
	public function test_maybe_upgrade_schema() {
		$previous = '0.4';

		$this->assertFalse( $this->sut->maybe_upgrade_schema( $previous ) );
	}

	/**
	 * Tests ::maybe_upgrade_data.
	 *
	 * @covers ::maybe_upgrade_data
	 */
	public function test_maybe_update_data_no_need() {
		$previous = '0.4';

		$this->assertSame( 0, $this->sut->maybe_upgrade_data( $previous ) );
	}
}
