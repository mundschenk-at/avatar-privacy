<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

use Avatar_Privacy\Data_Storage\Options;

/**
 * Avatar_Privacy\Data_Storage\Options unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Options
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Options
 */
class Options_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Data_Storage\Options
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		Functions\when( '__' )->returnArg();

		$this->sut = m::mock( Options::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$result = new Options();

		$this->assertAttributeSame( Options::PREFIX, 'prefix', $result );
	}

	/**
	 * Provides data for testing reset_avatar_default.
	 *
	 * @return array
	 */
	public function provide_reset_avatar_default_data() {
		return [
			[ 'rings', true ],
			[ 'comment', true ],
			[ 'bubble', true ],
			[ 'bubble', true ],
			[ 'im-user-offline', true ],
			[ 'bowling-pin', true ],
			[ 'view-media-artist', true ],
			[ 'silhouette', true ],
			[ 'custom', true ],
			[ 'mystery', false ],
			[ 'foobar', false ],
		];
	}

	/**
	 * Tests ::reset_avatar_default.
	 *
	 * @covers ::reset_avatar_default
	 *
	 * @dataProvider provide_reset_avatar_default_data
	 *
	 * @param  string $old_default The old value of `avatar_default`.
	 * @param  bool   $reset       Whether the value should be reset.
	 */
	public function test_reset_avatar_default( $old_default, $reset ) {
		$this->sut->shouldReceive( 'get' )->once()->with( 'avatar_default', null, true )->andReturn( $old_default );

		if ( $reset ) {
			$this->sut->shouldReceive( 'set' )->once()->with( 'avatar_default', 'mystery', true, true );
		} else {
			$this->sut->shouldReceive( 'set' )->never();
		}

		$this->assertNull( $this->sut->reset_avatar_default() );
	}
}
