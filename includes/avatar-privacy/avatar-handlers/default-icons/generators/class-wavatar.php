<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
 * Copyright 2007-2008 Shamus Young.
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

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Number_Generator;

/**
 * A wavatar generator.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 * @since 2.3.0 Refactored to use standard parts mechanisms, various obsolete
 *              constants removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Shamus Young <shamus@shamusyoung.com>
 */
class Wavatar extends PNG_Parts_Generator {

	/**
	 * A mapping from part types to the seed positions to take their values from.
	 *
	 * @since 2.3.0
	 *
	 * @var int[string]
	 */
	const SEED_INDEX = [
		// Mask and shine form the face, so they use the same random element.
		'mask'           => 1,
		'shine'          => 1,
		'background_hue' => 3, // Not a part type, but part of the sequence.
		'fade'           => 5,
		'wavatar_hue'    => 7, // Not a part type, but part of the sequence.
		'brow'           => 9,
		'eyes'           => 11,
		'pupils'         => 13,
		'mouth'          => 15,
	];

	/**
	 * The seed string used in the last call to `::build()`.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	private $current_seed;

	/**
	 * Creates a new Wavatars generator.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.3.0 Parameter $images renamed to $editor. Parameters $png and
	 *              $number_generator added.
	 *
	 * @param Images\Editor    $editor           The image editing handler.
	 * @param Images\PNG       $png              The PNG image helper.
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Site_Transients  $site_transients  The site transients handler.
	 */
	public function __construct(
		Images\Editor $editor,
		Images\PNG $png,
		Number_Generator $number_generator,
		Site_Transients $site_transients
	) {
		parent::__construct(
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/wavatars',
			[ 'fade', 'mask', 'shine', 'brow', 'eyes', 'pupils', 'mouth' ],
			80,
			$editor,
			$png,
			$number_generator,
			$site_transients
		);
	}

	/**
	 * Prepares additional arguments needed for rendering the avatar image.
	 *
	 * @param  string $seed  The seed data (hash).
	 * @param  int    $size  The size in pixels.
	 * @param  array  $parts The (randomized) avatar parts.
	 *
	 * @return array
	 */
	protected function get_additional_arguments( $seed, $size, array $parts ) {
		// Also randomize the colors.
		$background_hue = $this->seed( $seed, self::SEED_INDEX['background_hue'], 2, 240 ) / 255 * self::DEGREE;
		$wavatar_hue    = $this->seed( $seed, self::SEED_INDEX['wavatar_hue'], 2, 240 ) / 255 * self::DEGREE;

		return [
			'background_hue' => $background_hue,
			'wavatar_hue'    => $wavatar_hue,
		];
	}

	/**
	 * Renders the avatar from its parts, using any of the given additional arguments.
	 *
	 * @param  array $parts The (randomized) avatar parts.
	 * @param  array $args  Any additional arguments defined by the subclass.
	 *
	 * @return resource
	 */
	protected function render_avatar( array $parts, array $args ) {
		// Create background.
		$avatar = $this->create_image( 'white' );

		// Fill in the background color.
		$this->png->fill_hsl( $avatar, $args['background_hue'], 94, 20, 1, 1 );

		// Now add the various layers onto the image.
		foreach ( $parts as $type => $file ) {
			$this->combine_images( $avatar, $file );

			if ( 'mask' === $type ) {
				$this->png->fill_hsl( $avatar, $args['wavatar_hue'], 94, 66, (int) ( $this->size / 2 ), (int) ( $this->size / 2 ) );
			}
		}

		return $avatar;
	}

	/**
	 * Generates a random but valid part index based on the type and number of parts.
	 *
	 * @param  string $type  The part type.
	 * @param  int    $count The number of different parts of the type.
	 *
	 * @return int
	 */
	protected function get_random_part_index( $type, $count ) {
		return $this->seed( $this->current_seed, self::SEED_INDEX[ $type ], 2, $count );
	}

	/**
	 * Extract a "random" value from the seed string.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  string $seed   The seed.
	 * @param  int    $index  The index.
	 * @param  int    $length The number of bytes.
	 * @param  int    $modulo The maximum value of the result.
	 *
	 * @return int
	 */
	protected function seed( $seed, $index, $length, $modulo ) {
		return \hexdec( \substr( $seed, $index, $length ) ) % $modulo;
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
		// Save seed for part randomization.
		$this->current_seed = $seed;

		return parent::build( $seed, $size );
	}
}
