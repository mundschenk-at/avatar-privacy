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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Default_Icons\Generator\Jdenticon;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A color theme generator.
 */
abstract class Color_Theme {
	const COLOR_LIGHTNESS_RANGE     = [ 0.4, 0.8 ];
	const GRAYSCALE_LIGHTNESS_RANGE = [ 0.3, 0.9 ];
	const LIGHTNESS_CORRECTORS      = [ 0.55, 0.5, 0.5, 0.46, 0.6, 0.55, 0.55 ];

	const DARK_GRAY   = 0;
	const MID_COLOR   = 1;
	const LIGHT_GRAY  = 2;
	const LIGHT_COLOR = 3;
	const DARK_COLOR  = 4;

	/**
	 * Creates a color theme for the icon.
	 *
	 * @param  float $hue        Hue (range 0.0-1.0).
	 * @param  float $saturation Optional. Saturation (range 0.0-1.0). Default 0.5.
	 *
	 * @return string[]
	 */
	public static function get_rgb_colors( $hue, $saturation = 0.5 ) {
		return [
			self::DARK_GRAY   => self::hsl_to_rgb( 0, 0, self::get_lightness( 0, self::GRAYSCALE_LIGHTNESS_RANGE ) ),
			self::MID_COLOR   => self::corrected_hsl_to_rgb( $hue, $saturation, self::get_lightness( 0.5, self::COLOR_LIGHTNESS_RANGE ) ),
			self::LIGHT_GRAY  => self::hsl_to_rgb( 0, 0, self::get_lightness( 1, self::GRAYSCALE_LIGHTNESS_RANGE ) ),
			self::LIGHT_COLOR => self::corrected_hsl_to_rgb( $hue, $saturation, self::get_lightness( 1, self::COLOR_LIGHTNESS_RANGE ) ),
			self::DARK_COLOR  => self::corrected_hsl_to_rgb( $hue, $saturation, self::get_lightness( 0, self::COLOR_LIGHTNESS_RANGE ) ),
		];
	}


	/**
	 * Calculates a lightness relative the specified value in the specified lightness range.
	 *
	 * @param  float   $value The initial lightness.
	 * @param  float[] $range An array containing the minimum and maximum lightness.
	 *
	 * @return float
	 */
	private static function get_lightness( $value, array $range ) {
		$value = $range[0] + $value * ( $range[1] - $range[0] );

		return $value < 0 ? 0 : $value > 1 ? 1 : $value;
	}

	/**
	 * Converts a HSL color to RGB ("#rrggbb").
	 *
	 * @param  float $h Hue (range 0.0-1.0).
	 * @param  float $s Saturation (range 0.0-1.0).
	 * @param  float $l Lightness (range 0.0-1.0).
	 *
	 * @return string
	 */
	private static function hsl_to_rgb( $h, $s, $l ) {
		$rgb = HSLtoRGB( (int) ( $h * 360 ), (int) ( $s * 100 ), (int) ( $l * 100 ) );

		return '#' . \dechex( $rgb[0] ) . \dechex( $rgb[1] ) . \dechex( $rgb[2] );
	}

	/**
	 * Correct the lightness for the "dark" hues and converts the HSL color to RGB ("#rrggbb").
	 *
	 * @param  float $h Hue (range 0.0-1.0).
	 * @param  float $s Saturation (range 0.0-1.0).
	 * @param  float $l Lightness (range 0.0-1.0).
	 *
	 * @return string
	 */
	private static function corrected_hsl_to_rgb( $h, $s, $l ) {
		// The corrector specifies the perceived middle lightnesses for each hue.
		$corrector = self::LIGHTNESS_CORRECTORS[ (int) ( $h * 6 + 0.5 ) ];

		// Adjust the input lightness relative to the corrector.
		$l = $l < 0.5 ? $l * $corrector * 2 : $corrector + ( $l - 0.5 ) * ( 1 - $corrector ) * 2;

		return self::hsl_to_rgb( $h, $s, $l );
	}
}
