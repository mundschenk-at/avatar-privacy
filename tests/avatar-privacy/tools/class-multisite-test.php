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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Multisite;

/**
 * Avatar_Privacy\Tools\Multisite unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Multisite
 * @usesDefaultClass \Avatar_Privacy\Tools\Multisite
 */
class Multisite_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Multisite
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

		$this->sut = m::mock( Multisite::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::do_for_all_sites_in_network.
	 *
	 * @covers ::do_for_all_sites_in_network
	 */
	public function test_do_for_all_sites_in_network() {
		$fake_function = 'foobar';
		$network_id    = 5;
		$site_ids      = [ 1, 3, 5 ];
		$site_count    = \count( $site_ids );

		$this->sut->shouldReceive( 'get_site_ids' )->once()->with( $network_id )->andReturn( $site_ids );

		Functions\expect( 'switch_to_blog' )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( $fake_function )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( 'restore_current_blog' )->times( $site_count );

		$this->assertNull( $this->sut->do_for_all_sites_in_network( $fake_function, $network_id ) );
	}

	/**
	 * Tests ::do_for_all_sites_in_network with null as the network ID.
	 *
	 * @covers ::do_for_all_sites_in_network
	 */
	public function test_do_for_all_sites_in_network_without_network_id() {
		$fake_function = 'foobar';
		$network_id    = null;
		$site_ids      = [ 1, 3, 5 ];
		$site_count    = \count( $site_ids );

		$this->sut->shouldReceive( 'get_site_ids' )->once()->with( $network_id )->andReturn( $site_ids );

		Functions\expect( 'switch_to_blog' )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( $fake_function )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( 'restore_current_blog' )->times( $site_count );

		$this->assertNull( $this->sut->do_for_all_sites_in_network( $fake_function ) );
	}

	/**
	 * Tests ::get_site_ids.
	 *
	 * @covers ::get_site_ids
	 */
	public function test_get_site_ids() {
		$network_id = 5;
		$site_ids   = [ 1, 3, 5 ];

		Functions\expect( 'get_current_network_id' )->never();
		Functions\expect( 'get_sites' )->once()->with(
			[
				'fields'     => 'ids',
				'network_id' => $network_id,
				'number'     => '',
			]
		)->andReturn( $site_ids );

		$this->assertSame( $site_ids, $this->sut->get_site_ids( $network_id ) );
	}

	/**
	 * Tests ::get_site_ids with null as the network ID.
	 *
	 * @covers ::get_site_ids
	 */
	public function test_get_site_ids_without_network_id() {
		$network_id = 5;
		$site_ids   = [ 1, 3, 5 ];

		Functions\expect( 'get_current_network_id' )->once()->andReturn( $network_id );
		Functions\expect( 'get_sites' )->once()->with(
			[
				'fields'     => 'ids',
				'network_id' => $network_id,
				'number'     => '',
			]
		)->andReturn( $site_ids );

		$this->assertSame( $site_ids, $this->sut->get_site_ids() );
	}

	/**
	 * Tests ::get_site_ids with null as the network ID.
	 *
	 * @covers ::get_site_ids
	 */
	public function test_get_site_ids_invalid_get_sites_result() {
		$network_id = 5;

		Functions\expect( 'get_current_network_id' )->never();
		Functions\expect( 'get_sites' )->once()->with(
			[
				'fields'     => 'ids',
				'network_id' => $network_id,
				'number'     => '',
			]
		)->andReturn( 0 );

		$this->assertSame( [], $this->sut->get_site_ids( $network_id ) );
	}
}
