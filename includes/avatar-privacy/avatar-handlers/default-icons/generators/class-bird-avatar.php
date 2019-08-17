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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Data_Storage\Site_Transients;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A bird avatar generator for the images created by David Revoy.
 *
 * See https://www.peppercarrot.com/extras/html/2019_bird-generator/
 *
 * @since 2.3.0
 */
class Bird_Avatar extends PNG_Parts_Generator {
	/**
	 * Creates a new instance.
	 *
	 * @param Images\Editor   $images          The image editing handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 */
	public function __construct( Images\Editor $images, Site_Transients $site_transients ) {
		parent::__construct(
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/birds',
			[ 'tail', 'hoop', 'body', 'wing', 'eyes', 'beak', 'accessoire' ],
			512,
			$images,
			$site_transients
		);
	}

	/**
	 * Builds an icon based on the given seed returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size The size in pixels.
	 *
	 * @return string|false
	 */
	public function build( $seed, $size ) {
		try {
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
			$bird = $this->create_image( 'transparent' );

			// Add parts.
			foreach ( $parts as $part_type => $file ) {
				$this->apply_image( $bird, $file );
			}
		} catch ( \RuntimeException $e ) {
			// Something went wrong but don't want to mess up blog layout.
			return false;
		} finally {
			// Reset randomness.
			\mt_srand(); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		}

		// Resize if necessary.
		return $this->get_resized_image_data( $bird, $size );
	}
}
