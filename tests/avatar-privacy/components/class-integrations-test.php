<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\Integrations;

use Avatar_Privacy\Integrations\Plugin_Integration;

/**
 * Avatar_Privacy\Components\Integrations unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Integrations
 * @usesDefaultClass \Avatar_Privacy\Components\Integrations
 *
 * @uses ::__construct
 */
class Integrations_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Integrations
	 */
	private $sut;

	/**
	 * An array of mocked integrations.
	 *
	 * @var Plugin_Integration[]
	 */
	private $integrations;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Mock required helpers.
		$this->integrations = [
			m::mock( Plugin_Integration::class ),
			m::mock( Plugin_Integration::class ),
			m::mock( Plugin_Integration::class ),
		];

		$this->sut = m::mock( Integrations::class, [ $this->integrations ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Integrations::class )->makePartial();

		$mock->__construct( $this->integrations );

		$this->assert_attribute_same( $this->integrations, 'integrations', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'plugins_loaded' )->once()->with( [ $this->sut, 'activate' ], 1 );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::activate.
	 *
	 * @covers ::activate
	 */
	public function test_activate() {
		foreach ( $this->integrations as $plugin ) {
			$plugin->shouldReceive( 'check' )->once()->andReturn( true );
			$plugin->shouldReceive( 'run' )->once()->with();
		}

		$this->assertNull( $this->sut->activate() );
	}
}
