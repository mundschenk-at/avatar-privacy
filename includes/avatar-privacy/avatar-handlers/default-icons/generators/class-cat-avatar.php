<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
 * Copyright 2016 David Revoy.
 * Copyright 2007-2014 Scott Sherrill-Mix.
 * Copyright 2007 Andreas Gohr.
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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Tools\Images;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A cat avatar generator.
 *
 * @since 2.3.0
 */
class Cat_Avatar extends PNG_Parts_Generator {
	const SIZE = 512;

	/**
	 * Creates a new instance.
	 *
	 * @param Images\Editor $images The image editing handler.
	 */
	public function __construct( Images\Editor $images ) {
		parent::__construct(
			\dirname( AVATAR_PRIVACY_PLUGIN_FILE ) . '/public/images/cats',
			[ 'body', 'fur', 'eyes', 'mouth', 'accessoire' ],
			$images
		);
	}

	/**
	 * Builds a monster icon and returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size The size in pixels.
	 *
	 * @return string|false
	 */
	public function build( $seed, $size ) {

		// Set randomness from seed.
		\mt_srand( (int) \hexdec( \substr( $seed, 0, 8 ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- we need deterministic "randomness".

		// Throw the dice for body parts.
		$parts = $this->get_randomized_parts(
			// Wrapper function needed because \mt_rand complains about the
			// extraneous $type parameter if used directly.
			function( $min, $max ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
				return \mt_rand( $min, $max ); // @codeCoverageIgnore
			}
		);

		// Create background.
		$cat = \imageCreateTrueColor( self::SIZE, self::SIZE );
		if ( false === $cat ) {
			// Something went wrong but don't want to mess up blog layout.
			return false; // @codeCoverageIgnore
		}

		// Fix transparent background.
		\imageAlphaBlending( $cat, true );
		\imageSaveAlpha( $cat, true );
		\imageFill( $cat, 0, 0, \imageColorAllocateAlpha( $cat, 0, 0, 0, 127 ) );

		// Add parts.
		foreach ( $parts as $part_type => $file ) {
			$this->apply_image( $cat, $file, self::SIZE, self::SIZE );
		}

		// Reset randomness.
		\mt_srand(); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand

		// Resize if necessary.
		return $this->get_resized_image_data( $cat, $size );
	}
}
