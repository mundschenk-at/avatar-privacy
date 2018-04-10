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

namespace Avatar_Privacy\Default_Icons\Generator;

use Colors\RandomColor;

/**
 * An icon generator.
 *
 * @since 1.0.0
 */
class Rings extends \Bitverse\Identicon\Generator\RingsGenerator implements Generator {

	/**
	 * Builds an icon based on the given seed returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size The size in pixels.
	 *
	 * @return string|false
	 */
	public function build( $seed, $size ) {
		// Initialize random number with seed.
		\mt_srand( (int) hexdec( substr( $seed, 0, 8 ) ) );

		$this->setBackgroundColor( RandomColor::one( [ 'luminosity' => 'light' ] ) );
		$this->setForegroundColor( RandomColor::one( [ 'luminosity' => 'bright' ] ) );

		$result = $this->generate( $seed );

		// Restore randomness.
		\mt_srand();

		// Return result.
		return $result;
	}
}
