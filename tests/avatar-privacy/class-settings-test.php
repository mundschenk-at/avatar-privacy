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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Options;

/**
 * Avatar_Privacy\Settings unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Settings
 * @usesDefaultClass \Avatar_Privacy\Settings
 */
class Settings_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		Functions\when( '__' )->returnArg();

		$this->sut = m::mock( Settings::class )->makePartial();
	}

	/**
	 * Tests ::get_fields.
	 *
	 * @covers ::get_fields
	 */
	public function test_get_fields() {
		$information_header = 'INFORMATION_HEADER';

		$result = $this->sut->get_fields( $information_header );

		$this->assertInternalType( 'array', $result );
		$this->assertContainsOnly( 'string', \array_keys( $result ) );
		$this->assertContainsOnly( 'array', $result );
		$this->assertSame( $result[ Settings::INFORMATION_HEADER ]['elements'], [ $information_header ] );
	}

	/**
	 * Tests ::get_defaults.
	 *
	 * @covers ::get_defaults
	 *
	 * @uses ::get_fields
	 */
	public function test_get_defaults() {
		$result = $this->sut->get_defaults();

		$this->assertInternalType( 'array', $result );
		$this->assertNotContainsOnly( 'array', $result );
		$this->assertSame( '', $result[ Options::INSTALLED_VERSION ] );
	}

	/**
	 * Tests ::get_network_fields.
	 *
	 * @covers ::get_network_fields
	 */
	public function test_get_network_fields() {
		$result = $this->sut->get_network_fields();

		$this->assertInternalType( 'array', $result );
		$this->assertContainsOnly( 'string', \array_keys( $result ) );
		$this->assertContainsOnly( 'array', $result );
	}
}
