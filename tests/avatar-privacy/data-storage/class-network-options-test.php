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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Data_Storage;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * Avatar_Privacy\Data_Storage\Network_Options unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Network_Options
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Network_Options
 */
class Network_Options_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Network_Options&m\MockInterface
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

		Functions\when( '__' )->returnArg();

		$this->sut = m::mock( Network_Options::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		Functions\expect( 'get_current_network_id' )->andReturn( 0 );

		$result = m::mock( Network_Options::class, [] );

		$this->assert_attribute_same( Network_Options::PREFIX, 'prefix', $result );
	}

	/**
	 * Provides data for testing remove_prefix.
	 *
	 * @return array
	 */
	public function provide_test_remove_prefix_data() {
		return [
			[ 'avatar_privacy_test', 'test' ],
			[ 'foobar', '' ],
		];
	}

	/**
	 * Tests ::remove_prefix.
	 *
	 * @covers ::remove_prefix
	 *
	 * @dataProvider provide_test_remove_prefix_data
	 *
	 * @param  string $input  Option name.
	 * @param  string $result Result.
	 */
	public function test_remove_prefix( $input, $result ) {
		$this->assertSame( $result, $this->sut->remove_prefix( $input ) );
	}

	/**
	 * Tests ::lock.
	 *
	 * @covers ::lock
	 */
	public function test_lock() {
		$option = 'my_option_name';
		$hash   = 'some hash';
		$lock   = "{$option}_lock";

		Functions\expect( 'wp_hash' )->once()->with( m::pattern( "/{$option}\|\d+\.\d+$/" ), 'nonce' )->andReturn( $hash );

		$this->sut->shouldReceive( 'get' )->twice()->with( $lock )->andReturn( false, $hash );
		$this->sut->shouldReceive( 'set' )->once()->with( $lock, $hash )->andReturn( true );

		$this->assertTrue( $this->sut->lock( $option ) );
	}

	/**
	 * Tests ::lock.
	 *
	 * @covers ::lock
	 */
	public function test_lock_already_locked() {
		$option = 'my_option_name';
		$hash   = 'some hash';
		$lock   = "{$option}_lock";

		Functions\expect( 'wp_hash' )->once()->with( m::pattern( "/{$option}\|\d+\.\d+$/" ), 'nonce' )->andReturn( $hash );

		$this->sut->shouldReceive( 'get' )->once()->with( $lock )->andReturn( true );
		$this->sut->shouldReceive( 'set' )->never();

		$this->assertFalse( $this->sut->lock( $option ) );
	}

	/**
	 * Tests ::lock.
	 *
	 * @covers ::lock
	 */
	public function test_lock_race_condition() {
		$option = 'my_option_name';
		$hash   = 'some hash';
		$lock   = "{$option}_lock";

		Functions\expect( 'wp_hash' )->once()->with( m::pattern( "/{$option}\|\d+\.\d+$/" ), 'nonce' )->andReturn( $hash );

		$this->sut->shouldReceive( 'get' )->twice()->with( $lock )->andReturn( false, 'some other hash' );
		$this->sut->shouldReceive( 'set' )->once()->with( $lock, $hash )->andReturn( true );

		$this->assertFalse( $this->sut->lock( $option ) );
	}

	/**
	 * Tests ::unlock.
	 *
	 * @covers ::unlock
	 */
	public function test_unlock() {
		$option = 'my_option_name';
		$lock   = "{$option}_lock";

		$this->sut->shouldReceive( 'get' )->once()->with( $lock )->andReturn( 'some hash' );
		$this->sut->shouldReceive( 'delete' )->once()->with( $lock )->andReturn( true );

		$this->assertTrue( $this->sut->unlock( $option ) );
	}

	/**
	 * Tests ::unlock.
	 *
	 * @covers ::unlock
	 */
	public function test_unlock_not_locked() {
		$option = 'my_option_name';
		$lock   = "{$option}_lock";

		$this->sut->shouldReceive( 'get' )->once()->with( $lock )->andReturn( false );
		$this->sut->shouldReceive( 'delete' )->never();

		$this->assertTrue( $this->sut->unlock( $option ) );
	}

	/**
	 * Tests ::unlock.
	 *
	 * @covers ::unlock
	 */
	public function test_unlock_error() {
		$option = 'my_option_name';
		$lock   = "{$option}_lock";

		$this->sut->shouldReceive( 'get' )->once()->with( $lock )->andReturn( 'some hash' );
		$this->sut->shouldReceive( 'delete' )->once()->with( $lock )->andReturn( false );

		$this->assertFalse( $this->sut->unlock( $option ) );
	}
}
