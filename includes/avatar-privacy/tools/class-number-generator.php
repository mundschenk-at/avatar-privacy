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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tools;

/**
 * A deterministic "random" number generator used for seeding generated default
 * avatars.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Number_Generator {

	/**
	 * Initializes the pseudo-random number generator with the seed hash.
	 *
	 * @param  string $hash A string of hexadecimal digits (i.e. the result of a
	 *                      hash function).
	 */
	public function seed( $hash ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- we need deterministic "randomness".
		\mt_srand( (int) \hexdec( \substr( $hash, 0, 8 ) ) );
	}

	/**
	 * Resets the pseudo-random number generator to a less predictable value.
	 */
	public function reset() {
		\mt_srand(); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
	}

	/**
	 * Retrieves a pseudo-random number falling into the given interval.
	 *
	 * @param  int $min The minimum.
	 * @param  int $max The maximum.
	 *
	 * @return int
	 */
	public function get( $min, $max ) {
		return \mt_rand( $min, $max ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
	}
}
