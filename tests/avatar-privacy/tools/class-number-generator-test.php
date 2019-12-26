<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Tools\Number_Generator;

/**
 * Avatar_Privacy\Tools\Number_Generator unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Number_Generator
 * @usesDefaultClass \Avatar_Privacy\Tools\Number_Generator
 */
class Number_Generator_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Number_Generator
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

		$this->sut = m::mock( Number_Generator::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::seed.
	 *
	 * @covers ::seed
	 */
	public function test_seed() {
		$hash = '0d0412f8a4572c013022c1b18dcae170ef5ac9df26bb45d9b7bb5c1c84b9e9bc';

		// phpcs:disable WordPress.WP.AlternativeFunctions.rand_mt_rand, WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		$n1 = \mt_rand();
		$n2 = \mt_rand();

		$this->assertNotSame( $n1, $n2 );

		$this->sut->seed( $hash );

		$n3 = \mt_rand();

		$this->assertNotSame( $n1, $n3 );
		$this->assertNotSame( $n2, $n3 );

		\mt_srand();

		$n4 = \mt_rand();

		$this->assertNotSame( $n1, $n4 );
		$this->assertNotSame( $n2, $n4 );
		$this->assertNotSame( $n3, $n4 );

		$this->sut->seed( $hash );

		$n4 = \mt_rand();

		$this->assertSame( $n3, $n4 );

		// phpcs:enable WordPress.WP.AlternativeFunctions.rand_mt_rand, WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
	}

	/**
	 * Tests ::reset.
	 *
	 * @covers ::reset
	 *
	 * @uses ::seed
	 */
	public function test_reset() {
		$hash = '0d0412f8a4572c013022c1b18dcae170ef5ac9df26bb45d9b7bb5c1c84b9e9bc';

		// phpcs:disable WordPress.WP.AlternativeFunctions.rand_mt_rand, WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand

		$this->sut->seed( $hash );

		$n1 = \mt_rand();

		$this->sut->reset();

		$n2 = \mt_rand();

		$this->assertNotSame( $n1, $n2 );

		$this->sut->seed( $hash );

		$n3 = \mt_rand();

		$this->assertSame( $n1, $n3 );

		$this->sut->reset();

		$n4 = \mt_rand();

		$this->assertNotSame( $n1, $n4 );
		$this->assertNotSame( $n2, $n4 );

		// phpcs:enable WordPress.WP.AlternativeFunctions.rand_mt_rand, WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
	}

	/**
	 * Provides data fort testing ::get.
	 *
	 * @return array
	 */
	public function provide_get_data() {
		return [
			[ 0, 10 ],
			[ -10, 10 ],
			[ 100, 100000 ],
		];
	}

	/**
	 * Tests ::get.
	 *
	 * @covers ::get
	 *
	 * @dataProvider provide_get_data
	 *
	 * @param  int $min The minimum.
	 * @param  int $max The maximum.
	 */
	public function test_get( $min, $max ) {
		$result1 = $this->sut->get( $min, $max );
		$result2 = $this->sut->get( $min, $max );

		$this->assert_is_int( $result1 );
		$this->assert_is_int( $result2 );
		$this->assertLessThanOrEqual( $max, $result1 );
		$this->assertLessThanOrEqual( $max, $result2 );
		$this->assertGreaterThanOrEqual( $min, $result1 );
		$this->assertGreaterThanOrEqual( $min, $result2 );
	}
}
