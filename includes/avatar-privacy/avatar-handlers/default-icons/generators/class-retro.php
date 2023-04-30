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
use Avatar_Privacy\Tools\Template;

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
	 * The templating handler.
	 *
	 * @since 2.7.0
	 *
	 * @var Template
	 */
	protected Template $template;


	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter `$identicon` added.
	 * @since 2.3.0 Parameter `$number_generator` added.
	 * @since 2.7.0 Parameter `$template` added.
	 *
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Template         $template         The templating handler.
	 */
	public function __construct( Number_Generator $number_generator, Template $template ) {
		$this->number_generator = $number_generator;
		$this->template         = $template;
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
		$bitmap = $this->get_bitmap( \md5( $seed ) ); // The seed is already hashed, but we want to generate the same result as earlier versions did using `yzalis/identicon`.
		$args   = [
			'rows'     => \count( $bitmap ),
			'columns'  => \count( $bitmap[1] ),
			'path'     => $this->draw_path( $bitmap ),
			'color'    => RandomColor::one( [ 'luminosity' => 'bright' ] ),
			'bg_color' => RandomColor::one( [ 'luminosity' => 'light' ] ),
		];
		$result = $this->template->get_partial( 'public/partials/retro/svg.php', $args );

		// Restore randomness.
		$this->number_generator->reset();

		return $result;
	}

	/**
	 * Converts the hash into an two-dimensional array of boolean.
	 *
	 * @since  2.7.0
	 *
	 * @param  string $hash The MD5 hash.
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
	 * @since  2.7.0
	 *
	 * @param  string $hex_digit A hexadecimal digit.
	 *
	 * @return bool
	 */
	protected function get_pixel_value( string $hex_digit ): bool {
		return (bool) \round( \hexdec( $hex_digit ) / 10 );
	}

	/**
	 * Draws an SVG path from the given bitmap.
	 *
	 * @since  2.7.0
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
