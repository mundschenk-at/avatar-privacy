<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *     Copyright (c) 2013, 2014, 2016 Benjamin Laugueux <benjamin@yzalis.com>
 *     Copyright (c) 2015 Grummfy <grummfy@gmail.com>
 *     Copyright (c) 2016, 2017 Lucas Michot
 *     Copyright (c) 2019 Arjen van der Meijden
 *
 *     Permission is hereby granted, free of charge, to any person obtaining a copy
 *     of this software and associated documentation files (the "Software"), to deal
 *     in the Software without restriction, including without limitation the rights
 *     to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *     copies of the Software, and to permit persons to whom the Software is furnished
 *     to do so, subject to the following conditions:
 *
 *     The above copyright notice and this permission notice shall be included in all
 *     copies or substantial portions of the Software.
 *
 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *     IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *     FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *     AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *     LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *     OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *     THE SOFTWARE.
 *
 *  ***
 *
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;

use Avatar_Privacy\Tools\Number_Generator;

use Colors\RandomColor;

/**
 * Generates a "retro" SVG icon based on a hash.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 * @since 2.7.0 Now directly incorporates the SVG generation code from the
 *              deprecated package `yzalis/identicon`.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Grummfy <grummfy@gmail.com>
 */
class Retro implements Generator {

	private const NUMBER_OF_PIXELS = 5;

	private const DIVIDER = 3;

	private const POSSIBLE_COLUMNS = [
		0 => [ 0, 4 ],
		1 => [ 1, 3 ],
		2 => [ 2 ],
	];

	/**
	 * The random number generator.
	 *
	 * @since 2.3.0
	 *
	 * @var Number_Generator
	 */
	protected Number_Generator $number_generator;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter `$identicon` added.
	 * @since 2.3.0 Parameter `$number_generator` added.
	 *
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 */
	public function __construct( Number_Generator $number_generator ) {
		$this->number_generator = $number_generator;
	}

	/**
	 * Builds an icon based on the given seed returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size Optional. The size in pixels. Default 128 (but really ignored).
	 *
	 * @return string
	 */
	public function build( $seed, $size = 128 ) {
		// Initialize random number with seed.
		$this->number_generator->seed( $seed );

		// Generate icon.
		$result = $this->generate_svg(
			// The seed is already hashed, but we want to generate the same
			// result as earlier versions did using `yzalis/identicon`.
			$this->get_bitmap( \md5( $seed ) ),
			// The foreground color should be bright.
			RandomColor::one( [ 'luminosity' => 'bright' ] ),
			// The background color should be very light.
			RandomColor::one( [ 'luminosity' => 'light' ] )
		);

		// Restore randomness.
		$this->number_generator->reset();

		return $result;
	}

	/**
	 * Converts the hash into an two-dimensional array of boolean.
	 *
	 * @param string $hash The MD5 hash.
	 *
	 * @return array<int, array<int, bool>>
	 */
	protected function get_bitmap( string $hash ): array {
		$bitmap = [];

		foreach ( \array_slice( \str_split( $hash, 2 ), 0, self::NUMBER_OF_PIXELS * self::DIVIDER ) as $i => $hex_tuple ) {
			$row   = (int) ( $i / self::DIVIDER );
			$pixel = $this->get_pixel_value( $hex_tuple[0] );

			foreach ( self::POSSIBLE_COLUMNS[ $i % self::DIVIDER ] as $column ) {
				$bitmap[ $row ][ $column ] = $pixel;
			}

			\ksort( $bitmap[ $row ] );
		}

		return $bitmap;
	}

	/**
	 * Converts a one-digit hexadecimal number into a boolean value.
	 *
	 * @param string $hex_digit A hexadecimal digit.
	 *
	 * @return bool
	 */
	protected function get_pixel_value( string $hex_digit ): bool {
		return (bool) \round( \hexdec( $hex_digit ) / 10 );
	}

	/**
	 * Generates an SVG image from the given bitmap.
	 *
	 * @param  array  $bitmap           A two-dimensional array of boolean pixel values.
	 * @param  string $color            The pixel color as hexadecimal RGB color string (e.g. '#000000').
	 * @param  string $background_color The background color as a hexadecimal RGB color string (e.g. '#FFFFFF').
	 *
	 * @return string
	 *
	 * @phpstan-param array<int, array<int, bool>> $bitmap
	 */
	protected function generate_svg( array $bitmap, string $color, string $background_color ): string {
		$rows    = \count( $bitmap );
		$columns = \count( $bitmap[1] );

		// Prepare image.
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="320" height="320" viewBox="0 0 ' . "{$columns} {$rows}" . '">';
		$svg .= '<rect width="' . $columns . '" height="' . $rows . '" fill="' . $background_color . '" stroke-width="0"/>';
		$svg .= '<path fill="' . $color . '" stroke-width="0" d="' . $this->draw_path( $bitmap ) . '"/>';
		$svg .= '</svg>';

		return $svg;
	}

	/**
	 * Draws an SVG path from the given bitmap.
	 *
	 * @param  array $bitmap A two-dimensional array of boolean pixel values.
	 *
	 * @return string
	 *
	 * @phpstan-param array<int, array<int, bool>> $bitmap
	 */
	protected function draw_path( array $bitmap ): string {
		$rects = [];
		foreach ( $bitmap as $line_key => $line_value ) {
			foreach ( $line_value as $col_key => $col_value ) {
				if ( true === $col_value ) {
					$rects[] = 'M' . $col_key . ',' . $line_key . 'h1v1h-1v-1';
				}
			}
		}

		return \implode( '', $rects );
	}
}
