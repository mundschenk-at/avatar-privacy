<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2021-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Tools\Images\Color;

/**
 * Avatar_Privacy\Tools\Images\Color unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Images\Color
 * @usesDefaultClass \Avatar_Privacy\Tools\Images\Color
 *
 * @phpstan-import-type HueDegree from Color
 * @phpstan-import-type NormalizedHue from Color
 * @phpstan-import-type PercentValue from Color
 * @phpstan-import-type RGBValue from Color
 *
 * @phpstan-type RGBTriple array{ 0: RGBValue, 1: RGBValue, 2: RGBValue }
 */
class Color_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Color&m\MockInterface
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

		$this->sut = m::mock( Color::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Provides data for testing ::hsl_to_rgb.
	 *
	 * @return list<array{ 0: RGBTriple, 1: NormalizedHue, 2: PercentValue, 3: PercentValue }>
	 */
	public function provide_hsl_to_rgb_data() {
		$testdata = \json_decode( (string) \file_get_contents( __DIR__ . '/hsl_to_rgb.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( \is_array( $testdata ) ) {
			/**
			 * Our data should be valid.
			 *
			 * @phpstan-var list<array{ 0: RGBTriple, 1: NormalizedHue, 2: PercentValue, 3: PercentValue }>
			 */
			return $testdata;
		} else {
			return [];
		}
	}

	/**
	 * Tests ::hsl_to_rgb.
	 *
	 * @covers ::hsl_to_rgb
	 *
	 * @dataProvider provide_hsl_to_rgb_data
	 *
	 * @param  array $result     The expected RGB array.
	 * @param  int   $hue        The hue (-360°–+360°).
	 * @param  int   $saturation The saturation (0-100).
	 * @param  int   $lightness  The lightness (0-100).
	 *
	 * @phpstan-param RGBTriple     $result
	 * @phpstan-param NormalizedHue $hue
	 * @phpstan-param PercentValue  $saturation
	 * @phpstan-param PercentValue  $lightness
	 */
	public function test_hsl_to_rgb( $result, $hue, $saturation, $lightness ): void {
		$this->assertSame( $result, $this->sut->hsl_to_rgb( $hue, $saturation, $lightness ) );
	}

	/**
	 * Provides data for testing normalize_hue
	 *
	 * @return array
	 *
	 * @phpstan-return list<array{0: HueDegree, 1: NormalizedHue}>
	 */
	public function provide_normalize_hue_data(): array {
		return [
			[ 0, 0 ],
			[ -1, 359 ],
			[ -360, 0 ],
			[ 360, 0 ],
		];
	}

	/**
	 * Tests ::normalize_hue.
	 *
	 * @covers ::normalize_hue
	 *
	 * @dataProvider provide_normalize_hue_data
	 *
	 * @param int $hue    The input hue.
	 * @param int $result The expected result hue.
	 *
	 * @phpstan-param HueDegree     $hue
	 * @phpstan-param NormalizedHue $result
	 */
	public function test_normalize_hue( int $hue, int $result ): void {
		$this->assertSame( $result, $this->sut->normalize_hue( $hue ) );
	}
}
