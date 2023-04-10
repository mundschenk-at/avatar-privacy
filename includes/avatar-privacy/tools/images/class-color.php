<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2021-2023 Peter Putzer.
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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tools\Images;

/**
 * A utility class providing for converting between color spaces.
 *
 * @since 2.7.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type RGBValue int<0,255>
 * @phpstan-type PercentValue int<0,100>
 *
 * @phpstan-type HueDegree int<-360,360>
 * @phpstan-type NormalizedHue int<0,359>
 */
class Color {
	const MAX_DEGREE  = 360;
	const MAX_RGB     = 255;
	const MAX_PERCENT = 100;

	/**
	 * Converts a color specified using HSL to its RGB representation.
	 *
	 * @since  2.5.0
	 * @since  2.7.0 Moved to Color class.
	 *
	 * @param  int $hue        The hue (in degrees, i.e. -360°–+360°).
	 * @param  int $saturation The saturation (in percent, i.e. 0–100%).
	 * @param  int $lightness  The lightness (in percent, i.e. 0–100%).
	 *
	 * @return int[] {
	 *     The RGB color as a tuple.
	 *
	 *     @type int $red   The red component (0–255).
	 *     @type int $green The green component (0–255).
	 *     @type int $blue  The blue component (0–255).
	 * }
	 *
	 * @phpstan-param  NormalizedHue $hue
	 * @phpstan-param  PercentValue  $saturation
	 * @phpstan-param  PercentValue  $lightness
	 * @phpstan-return array{ 0: RGBValue, 1: RGBValue, 2: RGBValue }
	 */
	public function hsl_to_rgb( $hue, $saturation, $lightness ) {
		/**
		 * Convert saturation to decimal notation.
		 *
		 * @var float
		 */
		$saturation = $saturation / self::MAX_PERCENT;

		/**
		 * Convert lightness to decimal notation.
		 *
		 * @var float
		 */
		$lightness = $lightness / self::MAX_PERCENT;

		/**
		 * Conversion function.
		 *
		 * @param  int $n Conversion factor.
		 *
		 * @return float  A floating point number between 0.0 and 1.0.
		 */
		$f = function( $n ) use ( $hue, $saturation, $lightness ) {
			$k = \fmod( $n + $hue / 30, 12 );
			$a = $saturation * \min( $lightness, 1 - $lightness );
			return $lightness - $a * \max( -1, \min( $k - 3, 9 - $k, 1 ) );
		};

		/**
		 * The red component.
		 *
		 * @phpstan-var RGBValue
		 */
		$red = (int) \round( $f( 0 ) * self::MAX_RGB );
		/**
		 * The green component.
		 *
		 * @phpstan-var RGBValue
		 */
		$green = (int) \round( $f( 8 ) * self::MAX_RGB );
		/**
		 * The blue component.
		 *
		 * @phpstan-var RGBValue
		 */
		$blue = (int) \round( $f( 4 ) * self::MAX_RGB );

		// Return result array.
		return [ $red, $green, $blue ];
	}

	/**
	 * Normalizes the hue value.
	 *
	 * @param  int $hue The hue as a positive or negative arc on the color wheel (-360°–+360°).
	 *
	 * @return int      The normalized hue (0–359°).
	 *
	 * @phpstan-param  HueDegree $hue
	 * @phpstan-return NormalizedHue
	 */
	public function normalize_hue( int $hue ) {
		// Ensure a unique, non-negative hue.
		return ( $hue < 0 ? self::MAX_DEGREE + $hue : $hue ) % self::MAX_DEGREE;
	}
}
