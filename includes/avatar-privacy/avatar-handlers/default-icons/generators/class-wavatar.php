<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Number_Generator;

use GdImage; // phpcs:ignore ImportDetection.Imports -- PHP 8.0 compatibility.

/**
 * A Wavatar generator, based on the original WordPress plugin by Shamus Young.
 *
 * @link https://www.shamusyoung.com/twentysidedtale/?p=1462
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 * @since 2.3.0 Refactored to use standard parts mechanisms, various obsolete
 *              constants removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Shamus Young <shamus@shamusyoung.com>
 *
 * @phpstan-import-type HueDegree from Images\Color
 * @phpstan-import-type NormalizedHue from Images\Color
 *
 * @phpstan-type PartType value-of<self::PARTS>
 * @phpstan-type PartsTemplate array<PartType, array{}>
 * @phpstan-type AllPossibleParts array<PartType, string[]>
 * @phpstan-type RandomizedParts array<PartType, string>
 * @phpstan-type AdditionalArguments array<self::HUE_*, NormalizedHue>
 */
class Wavatar extends PNG_Parts_Generator {

	// Wavatar parts.
	private const PART_MASK   = 'mask';
	private const PART_SHINE  = 'shine';
	private const PART_FADE   = 'fade';
	private const PART_BROW   = 'brow';
	private const PART_EYES   = 'eyes';
	private const PART_PUPILS = 'pupils';
	private const PART_MOUTH  = 'mouth';

	/**
	 * All Wavatar parts in their natural order.
	 *
	 * @since 2.7.0
	 */
	private const PARTS = [
		self::PART_FADE,
		self::PART_MASK,
		self::PART_SHINE,
		self::PART_BROW,
		self::PART_EYES,
		self::PART_PUPILS,
		self::PART_MOUTH,
	];

	// Hues.
	private const HUE_BACKGROUND = 'background_hue';
	private const HUE_WAVATAR    = 'wavatar_hue';

	/**
	 * A mapping from part types to the seed positions to take their values from.
	 *
	 * @since 2.3.0
	 *
	 * @var array<string, int>
	 */
	const SEED_INDEX = [
		// Mask and shine form the face, so they use the same random element.
		self::PART_MASK      => 1,
		self::PART_SHINE     => 1,
		self::HUE_BACKGROUND => 3, // Not a part type, but part of the sequence.
		self::PART_FADE      => 5,
		self::HUE_WAVATAR    => 7, // Not a part type, but part of the sequence.
		self::PART_BROW      => 9,
		self::PART_EYES      => 11,
		self::PART_PUPILS    => 13,
		self::PART_MOUTH     => 15,
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
	 * The color conversion helper.
	 *
	 * @since 2.7.0
	 *
	 * @var Images\Color
	 */
	protected Images\Color $color;

	/**
	 * Creates a new Wavatars generator.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.3.0 Parameter $images renamed to $editor. Parameters $png and
	 *              $number_generator added.
	 * @since 2.7.0 Parameter $color added.
	 *
	 * @param Images\Editor    $editor           The image editing handler.
	 * @param Images\PNG       $png              The PNG image helper.
	 * @param Images\Color     $color            The color conversion helper.
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Site_Transients  $site_transients  The site transients handler.
	 */
	public function __construct(
		Images\Editor $editor,
		Images\PNG $png,
		Images\Color $color,
		Number_Generator $number_generator,
		Site_Transients $site_transients
	) {
		parent::__construct(
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/wavatars',
			self::PARTS,
			80,
			$editor,
			$png,
			$number_generator,
			$site_transients
		);

		$this->color = $color;
	}

	/**
	 * Prepares additional arguments needed for rendering the avatar image.
	 *
	 * @param  string $seed  The seed data (hash).
	 * @param  int    $size  The size in pixels.
	 * @param  array  $parts The (randomized) avatar parts.
	 *
	 * @return array
	 *
	 * @phpstan-param  RandomizedParts $parts
	 * @phpstan-return AdditionalArguments
	 */
	protected function get_additional_arguments( $seed, $size, array $parts ) {
		// Also randomize the colors.
		return [
			self::HUE_BACKGROUND => $this->get_hue( $seed, self::HUE_BACKGROUND ),
			self::HUE_WAVATAR    => $this->get_hue( $seed, self::HUE_WAVATAR ),
		];
	}

	/**
	 * Renders the avatar from its parts, using any of the given additional arguments.
	 *
	 * @since  2.5.0 Returns a resource or GdImage instance, depending on the PHP version.
	 *
	 * @param  array $parts The (randomized) avatar parts.
	 * @param  array $args  Any additional arguments defined by the subclass.
	 *
	 * @return resource|GdImage
	 *
	 * @phpstan-param RandomizedParts     $parts
	 * @phpstan-param AdditionalArguments $args
	 */
	protected function render_avatar( array $parts, array $args ) {
		// Create background.
		$avatar = $this->create_image( 'white' );

		// Fill in the background color.
		$this->png->fill_hsl( $avatar, $args[ self::HUE_BACKGROUND ], 94, 20, 1, 1 );

		// Now add the various layers onto the image.
		foreach ( $parts as $type => $file ) {
			$this->combine_images( $avatar, $file );

			if ( self::PART_MASK === $type ) {
				$this->png->fill_hsl( $avatar, $args[ self::HUE_WAVATAR ], 94, 66, (int) ( $this->size / 2 ), (int) ( $this->size / 2 ) );
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
	 * Generate pseudo-random hue from the seed.
	 *
	 * @since  2.7.0
	 *
	 * @param  string $seed       The seed data (hash).
	 * @param  string $seed_index The seed index to use for the generated hue.
	 *
	 * @return int
	 *
	 * @phpstan-param  self::HUE_* $seed_index
	 * @phpstan-return NormalizedHue
	 */
	protected function get_hue( string $seed, string $seed_index ) {
		/**
		 * Generate hue from seed.
		 *
		 * @phpstan-var HueDegree
		 */
		$seeded_hue = (int) ( $this->seed( $seed, self::SEED_INDEX[ $seed_index ], 2, 240 ) / 255 * Images\Color::MAX_DEGREE );

		return $this->color->normalize_hue( $seeded_hue );
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
