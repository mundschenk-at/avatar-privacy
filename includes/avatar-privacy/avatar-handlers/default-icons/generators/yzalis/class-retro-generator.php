<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2023 Peter Putzer.
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
 */

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Yzalis;

/**
 * @author Grummfy <grummfy@gmail.com>
 */
class Retro_Generator {

	/**
	 * @var array
	 */
	protected array $color;

	/**
	 * @var string
	 */
	private string $hash;

	/**
	 * @var array
	 */
	private array $array_of_square = [];

	/**
	 * Convert the hash into an multidimensional array of boolean.
	 *
	 * @return $this
	 */
	private function convert_hash_to_array_of_boolean(): self {
		preg_match_all( '/(\w)(\w)/', $this->hash, $chars );

		foreach ( $chars[1] as $i => $char ) {
			$index = (int) ( $i / 3 );
			$data  = $this->convert_hexa_to_boolean( $char );

			$items = [
				0 => [ 0, 4 ],
				1 => [ 1, 3 ],
				2 => [ 2 ],
			];

			foreach ( $items[ $i % 3 ] as $item ) {
				$this->array_of_square[ $index ][ $item ] = $data;
			}

			ksort( $this->array_of_square[ $index ] );
		}

		$this->color = array_map(function ( $data ) {
			return hexdec( $data ) * 16;
		}, array_reverse( $chars[1] ));

		return $this;
	}

	/**
	 * Convert an hexadecimal number into a boolean.
	 *
	 * @param string $hexa
	 *
	 * @return bool
	 */
	private function convert_hexa_to_boolean( string $hexa ): bool {
		return (bool) round( hexdec( $hexa ) / 10 );
	}

	/**
	 * @return array
	 */
	public function get_array_of_square(): array {
		return $this->array_of_square;
	}

	/**
	 * Generate a hash from the original string.
	 *
	 * @param string $string
	 *
	 * @throws \Exception
	 *
	 * @return $this
	 */
	public function set_string( string $string ): self {
		if ( null === $string ) {
			throw new \Exception( 'The string cannot be null.' );
		}

		$this->hash = md5( $string );

		$this->convert_hash_to_array_of_boolean();

		return $this;
	}

	/**
	 * @param string $string           The seed string.
	 * @param int    $size             The image size in pixels.
	 * @param string $color            The pixel color as hexadecimal RGB color string (e.g. '#000000').
	 * @param string $background_color The background color as a hexadecimal RGB color string (e.g. '#FFFFFF').
	 *
	 * @return string
	 */
	public function get_image_binary_data( string $string, int $size, ?string $color = null, ?string $background_color = null ) {
		$this
			->set_string( $string );

		// Prepare colors.
		$background_color ??= '#FFF';

		// Prepare image.
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $size . '" height="' . $size . '" viewBox="0 0 5 5">';
		$svg .= '<rect width="5" height="5" fill="' . $background_color . '" stroke-width="0"/>';

		// Draw content.
		$rects = [];
		foreach ( $this->get_array_of_square() as $line_key => $line_value ) {
			foreach ( $line_value as $col_key => $col_value ) {
				if ( true === $col_value ) {
					$rects[] = 'M' . $col_key . ',' . $line_key . 'h1v1h-1v-1';
				}
			}
		}

		$svg .= '<path fill="' . $color . '" stroke-width="0" d="' . \implode( '', $rects ) . '"/>';
		$svg .= '</svg>';

		return $svg;
	}
}
