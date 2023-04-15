<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
 * Copyright 2007-2014 Scott Sherrill-Mix.
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
 * A monster generator based on the WordPress implementation by Scott Sherrill-Mix
 * and the original algorithm designed by Andreas Gohr, based on an idea by Don Park.
 *
 * Artwork by Katherine Garner (Lemm).
 *
 * @link http://scott.sherrillmix.com/blog/blogger/wp_monsterid-update-hand-drawn-monsters/
 * @link https://www.splitbrain.org/projects/monsterid
 * @link http://kathgarner.com
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 * @since 2.3.0 Refactored to use standard parts mechanisms, various obsolete
 *              constants removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Scott Sherrill-Mix
 *
 * @phpstan-type PartType value-of<self::PARTS>
 * @phpstan-type PartsTemplate array<PartType, array{}>
 * @phpstan-type AllPossibleParts array<PartType, string[]>
 * @phpstan-type RandomizedParts array<PartType, string>
 *
 * @phpstan-type Hue int<-359, 359>
 * @phpstan-type Saturation int<0, 100>
 * @phpstan-type AdditionalArguments array{ hue: Hue, saturation: Saturation }
 */
class Monster_ID extends PNG_Parts_Generator {
	// Monster ports.
	private const PART_LEGS  = 'legs';
	private const PART_HAIR  = 'hair';
	private const PART_ARMS  = 'arms';
	private const PART_BODY  = 'body';
	private const PART_EYES  = 'eyes';
	private const PART_MOUTH = 'mouth';

	/**
	 * All Monster parts in their natural order.
	 *
	 * @since 2.7.0
	 */
	private const PARTS = [
		self::PART_LEGS,
		self::PART_HAIR,
		self::PART_ARMS,
		self::PART_BODY,
		self::PART_EYES,
		self::PART_MOUTH,
	];

	const COLOR_HUE        = 'hue';
	const COLOR_SATURATION = 'saturation';

	const SAME_COLOR_PARTS     = [
		'arms_S8.png'  => true,
		'legs_S5.png'  => true,
		'legs_S13.png' => true,
		'mouth_S5.png' => true,
		'mouth_S4.png' => true,
	];
	const SPECIFIC_COLOR_PARTS = [
		// Blue (values are hue degrees).
		'hair_S4.png'  => [ 216, 270 ],
		// Red (values are hue degrees).
		'arms_S2.png'  => [ -18, 18 ],
		'hair_S6.png'  => [ -18, 18 ],
		'mouth_9.png'  => [ -18, 18 ],
		'mouth_6.png'  => [ -18, 18 ],
		'mouth_S2.png' => [ -18, 18 ],
	];
	const RANDOM_COLOR_PARTS = [
		'arms_3.png'   => true,
		'arms_4.png'   => true,
		'arms_5.png'   => true,
		'arms_S1.png'  => true,
		'arms_S3.png'  => true,
		'arms_S5.png'  => true,
		'arms_S6.png'  => true,
		'arms_S7.png'  => true,
		'arms_S9.png'  => true,
		'hair_S1.png'  => true,
		'hair_S2.png'  => true,
		'hair_S3.png'  => true,
		'hair_S5.png'  => true,
		'legs_1.png'   => true,
		'legs_2.png'   => true,
		'legs_3.png'   => true,
		'legs_5.png'   => true,
		'legs_S1.png'  => true,
		'legs_S2.png'  => true,
		'legs_S3.png'  => true,
		'legs_S4.png'  => true,
		'legs_S6.png'  => true,
		'legs_S7.png'  => true,
		'legs_S10.png' => true,
		'legs_S12.png' => true,
		'mouth_3.png'  => true,
		'mouth_4.png'  => true,
		'mouth_7.png'  => true,
		'mouth_10.png' => true,
		'mouth_S6.png' => true,
	];
	// Generated from get_parts_dimensions.
	const PART_OPTIMIZATION = [
		'legs_1.png'   => [ [ 17, 99 ], [ 58, 119 ] ],
		'legs_2.png'   => [ [ 25, 94 ], [ 54, 119 ] ],
		'legs_3.png'   => [ [ 34, 99 ], [ 48, 117 ] ],
		'legs_4.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'legs_5.png'   => [ [ 28, 91 ], [ 64, 119 ] ],
		'legs_S1.png'  => [ [ 17, 105 ], [ 53, 118 ] ],
		'legs_S10.png' => [ [ 42, 88 ], [ 54, 118 ] ],
		'legs_S11.png' => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'legs_S12.png' => [ [ 15, 107 ], [ 60, 115 ] ],
		'legs_S13.png' => [ [ 8, 106 ], [ 69, 119 ] ],
		'legs_S2.png'  => [ [ 23, 99 ], [ 56, 117 ] ],
		'legs_S3.png'  => [ [ 30, 114 ], [ 53, 118 ] ],
		'legs_S4.png'  => [ [ 12, 100 ], [ 50, 116 ] ],
		'legs_S5.png'  => [ [ 17, 109 ], [ 63, 118 ] ],
		'legs_S6.png'  => [ [ 10, 100 ], [ 56, 119 ] ],
		'legs_S7.png'  => [ [ 33, 78 ], [ 73, 114 ] ],
		'legs_S8.png'  => [ [ 33, 95 ], [ 102, 116 ] ],
		'legs_S9.png'  => [ [ 42, 75 ], [ 72, 116 ] ],
		'hair_1.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'hair_2.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'hair_3.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'hair_4.png'   => [ [ 34, 84 ], [ 0, 41 ] ],
		'hair_5.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'hair_S1.png'  => [ [ 25, 96 ], [ 2, 58 ] ],
		'hair_S2.png'  => [ [ 45, 86 ], [ 3, 51 ] ],
		'hair_S3.png'  => [ [ 15, 105 ], [ 4, 48 ] ],
		'hair_S4.png'  => [ [ 15, 102 ], [ 1, 51 ] ],
		'hair_S5.png'  => [ [ 16, 95 ], [ 4, 65 ] ],
		'hair_S6.png'  => [ [ 28, 88 ], [ 1, 48 ] ],
		'hair_S7.png'  => [ [ 51, 67 ], [ 6, 49 ] ],
		'arms_1.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'arms_2.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'arms_3.png'   => [ [ 2, 119 ], [ 20, 72 ] ],
		'arms_4.png'   => [ [ 2, 115 ], [ 14, 98 ] ],
		'arms_5.png'   => [ [ 5, 119 ], [ 17, 90 ] ],
		'arms_S1.png'  => [ [ 0, 117 ], [ 23, 109 ] ],
		'arms_S2.png'  => [ [ 2, 118 ], [ 8, 75 ] ],
		'arms_S3.png'  => [ [ 2, 116 ], [ 17, 93 ] ],
		'arms_S4.png'  => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'arms_S5.png'  => [ [ 1, 115 ], [ 6, 40 ] ],
		'arms_S6.png'  => [ [ 3, 117 ], [ 7, 90 ] ],
		'arms_S7.png'  => [ [ 1, 116 ], [ 21, 67 ] ],
		'arms_S8.png'  => [ [ 2, 119 ], [ 18, 98 ] ],
		'arms_S9.png'  => [ [ 8, 110 ], [ 18, 65 ] ],
		'body_1.png'   => [ [ 22, 99 ], [ 17, 90 ] ],
		'body_10.png'  => [ [ 37, 85 ], [ 22, 98 ] ],
		'body_11.png'  => [ [ 23, 108 ], [ 10, 106 ] ],
		'body_12.png'  => [ [ 9, 113 ], [ 6, 112 ] ],
		'body_13.png'  => [ [ 29, 98 ], [ 26, 97 ] ],
		'body_14.png'  => [ [ 31, 93 ], [ 25, 94 ] ],
		'body_15.png'  => [ [ 23, 100 ], [ 20, 97 ] ],
		'body_2.png'   => [ [ 14, 104 ], [ 16, 89 ] ],
		'body_3.png'   => [ [ 22, 102 ], [ 22, 93 ] ],
		'body_4.png'   => [ [ 18, 107 ], [ 22, 103 ] ],
		'body_5.png'   => [ [ 22, 101 ], [ 12, 99 ] ],
		'body_6.png'   => [ [ 24, 103 ], [ 10, 92 ] ],
		'body_7.png'   => [ [ 22, 99 ], [ 7, 92 ] ],
		'body_8.png'   => [ [ 21, 103 ], [ 12, 95 ] ],
		'body_9.png'   => [ [ 20, 99 ], [ 9, 91 ] ],
		'body_S1.png'  => [ [ 22, 102 ], [ 25, 96 ] ],
		'body_S2.png'  => [ [ 35, 94 ], [ 17, 96 ] ],
		'body_S3.png'  => [ [ 30, 100 ], [ 20, 102 ] ],
		'body_S4.png'  => [ [ 26, 104 ], [ 14, 92 ] ],
		'body_S5.png'  => [ [ 26, 100 ], [ 16, 97 ] ],
		'eyes_1.png'   => [ [ 43, 76 ], [ 31, 48 ] ],
		'eyes_10.png'  => [ [ 40, 80 ], [ 32, 50 ] ],
		'eyes_11.png'  => [ [ 41, 82 ], [ 31, 54 ] ],
		'eyes_12.png'  => [ [ 45, 78 ], [ 30, 50 ] ],
		'eyes_13.png'  => [ [ 10, 111 ], [ 10, 34 ] ],
		'eyes_14.png'  => [ [ 40, 79 ], [ 21, 56 ] ],
		'eyes_15.png'  => [ [ 49, 72 ], [ 38, 43 ] ],
		'eyes_2.png'   => [ [ 37, 72 ], [ 36, 53 ] ],
		'eyes_3.png'   => [ [ 47, 75 ], [ 31, 53 ] ],
		'eyes_4.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'eyes_5.png'   => [ [ 44, 77 ], [ 43, 52 ] ],
		'eyes_6.png'   => [ [ 43, 57 ], [ 35, 49 ] ],
		'eyes_7.png'   => [ [ 62, 76 ], [ 35, 49 ] ],
		'eyes_8.png'   => [ [ 45, 72 ], [ 23, 51 ] ],
		'eyes_9.png'   => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'eyes_S1.png'  => [ [ 41, 82 ], [ 29, 52 ] ],
		'eyes_S2.png'  => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'eyes_S3.png'  => [ [ 34, 88 ], [ 39, 52 ] ],
		'eyes_S4.png'  => [ [ 47, 74 ], [ 39, 51 ] ],
		'eyes_S5.png'  => [ [ 41, 76 ], [ 36, 51 ] ],
		'mouth_1.png'  => [ [ 999999, 0 ], [ 999999, 0 ] ],
		'mouth_10.png' => [ [ 40, 84 ], [ 56, 89 ] ],
		'mouth_2.png'  => [ [ 57, 65 ], [ 56, 61 ] ],
		'mouth_3.png'  => [ [ 38, 85 ], [ 54, 72 ] ],
		'mouth_4.png'  => [ [ 44, 77 ], [ 56, 81 ] ],
		'mouth_5.png'  => [ [ 53, 72 ], [ 59, 76 ] ],
		'mouth_6.png'  => [ [ 48, 74 ], [ 56, 77 ] ],
		'mouth_7.png'  => [ [ 51, 70 ], [ 57, 80 ] ],
		'mouth_8.png'  => [ [ 44, 81 ], [ 64, 78 ] ],
		'mouth_9.png'  => [ [ 49, 75 ], [ 52, 103 ] ],
		'mouth_S1.png' => [ [ 47, 82 ], [ 57, 73 ] ],
		'mouth_S2.png' => [ [ 45, 71 ], [ 65, 84 ] ],
		'mouth_S3.png' => [ [ 48, 77 ], [ 56, 86 ] ],
		'mouth_S4.png' => [ [ 46, 77 ], [ 56, 73 ] ],
		'mouth_S5.png' => [ [ 55, 69 ], [ 55, 98 ] ],
		'mouth_S6.png' => [ [ 40, 79 ], [ 56, 72 ] ],
		'mouth_S7.png' => [ [ 999999, 0 ], [ 999999, 0 ] ],
	];

	/**
	 * The color conversion helper.
	 *
	 * @since 2.7.0
	 *
	 * @var Images\Color
	 */
	protected Images\Color $color;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
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
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/monster-id',
			self::PARTS,
			120,
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
		return [
			self::COLOR_HUE        => $this->get_hue(),
			self::COLOR_SATURATION => $this->get_saturation( 25, 100 ),
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
		$monster = $this->png->create_from_file( "{$this->parts_dir}/back.png" );

		// Add parts.
		foreach ( $parts as $part => $file ) {
			$im = $this->png->create_from_file( "{$this->parts_dir}/{$file}" );

			// Randomly color body parts.
			if ( self::PART_BODY === $part || isset( self::SAME_COLOR_PARTS[ $file ] ) ) {
				// Use the main color.
				$this->colorize_image( $im, $args[ self::COLOR_HUE ], $args[ self::COLOR_SATURATION ], $file );
			} elseif ( isset( self::RANDOM_COLOR_PARTS[ $file ] ) ) {
				$this->colorize_image(
					$im,
					$this->get_hue(),
					$this->get_saturation( 25, 100 ),
					$file
				);
			} elseif ( isset( self::SPECIFIC_COLOR_PARTS[ $file ] ) ) {
				// Retrieve specific hue range.
				list( $low, $high ) = self::SPECIFIC_COLOR_PARTS[ $file ];

				$this->colorize_image(
					$im,
					$this->get_hue( $low, $high ),
					$this->get_saturation( 25, 100 ),
					$file
				);
			}

			$this->combine_images( $monster, $im );
		}

		return $monster;
	}

	/**
	 * Adds color to the given image.
	 *
	 * @since  2.1.0 Visibility changed to protected.
	 * @since  2.3.0 Name changed to colorize_image() for consistency.
	 * @since  2.5.0 Parameter $image can now also be a GdImage. Returns a resource
	 *               or GdImage instance, depending on the PHP version.
	 * @since  2.7.0 Default values removed and PHPStan annotation added.
	 *               Parameter $part renamed to $file.
	 *
	 * @param  resource|GdImage $image      The image.
	 * @param  int              $hue        The hue (0-360).
	 * @param  int              $saturation The saturation (0-100).
	 * @param  string           $file       The image filename.
	 *
	 * @return resource|GdImage             The image, for chaining.
	 *
	 * @phpstan-param Hue        $hue
	 * @phpstan-param Saturation $saturation
	 */
	protected function colorize_image( $image, $hue, $saturation, $file ) {
		// Ensure non-negative hue.
		$hue = $this->color->normalize_hue( $hue );

		\imageAlphaBlending( $image, false );
		if ( isset( self::PART_OPTIMIZATION[ $file ] ) ) {
			$xmin = self::PART_OPTIMIZATION[ $file ][0][0];
			$xmax = self::PART_OPTIMIZATION[ $file ][0][1];
			$ymin = self::PART_OPTIMIZATION[ $file ][1][0];
			$ymax = self::PART_OPTIMIZATION[ $file ][1][1];
		} else {
			$xmin = 0;
			$xmax = \imageSX( $image ) - 1;
			$ymin = 0;
			$ymax = \imageSY( $image ) - 1;
		}

		for ( $i = $xmin; $i <= $xmax; $i++ ) {
			for ( $j = $ymin; $j <= $ymax; $j++ ) {
				$rgb       = \imageColorAt( $image, $i, $j );
				$r         = ( $rgb >> 16 ) & 0xFF;
				$g         = ( $rgb >> 8 ) & 0xFF;
				$b         = $rgb & 0xFF;
				$alpha     = ( $rgb & 0x7F000000 ) >> 24;
				$lightness = (int) ( ( $r + $g + $b ) / 3 / 255 * Images\Color::MAX_PERCENT );
				if ( $lightness > 10 && $lightness < 99 && $alpha < 115 ) {
					// Convert HSL color to RGB.
					list( $r, $g, $b ) = $this->color->hsl_to_rgb( $hue, $saturation, $lightness );

					// Change color of pixel.
					$color = \imageColorAllocateAlpha( $image, $r, $g, $b, $alpha );
					if ( false !== $color ) {
						\imageSetPixel( $image, $i, $j, $color );
					}
				}
			}
		}
		\imageAlphaBlending( $image, true );

		return $image;
	}

	/**
	 * Generates a random hue.
	 *
	 * @param  int $min Optional. The lower bound. Default 0.
	 * @param  int $max Optional. The upper bound. Default 359.
	 *
	 * @return int
	 *
	 * @phpstan-param  Hue $min
	 * @phpstan-param  Hue $max
	 * @phpstan-return Hue
	 */
	protected function get_hue( int $min = 0, int $max = Images\Color::MAX_DEGREE - 1 ) {
		assert( $min > - Images\Color::MAX_DEGREE && $max < Images\Color::MAX_DEGREE && $min < $max );

		/**
		 * Return a pseudo-random hue between the lower and the upper bound.
		 *
		 * @phpstan-var Hue
		 */
		return $this->number_generator->get( $min, $max );
	}

	/**
	 * Generates a random saturation level.
	 *
	 * @param  int $min Optional. The lower bound. Default 0 percent.
	 * @param  int $max Optional. The upper bound. Default 100 percent.
	 *
	 * @return int
	 *
	 * @phpstan-param  Saturation $min
	 * @phpstan-param  Saturation $max
	 * @phpstan-return Saturation
	 */
	protected function get_saturation( int $min = 0, int $max = Images\Color::MAX_PERCENT ) {
		assert( $min >= 0 && $max <= Images\Color::MAX_PERCENT && $min < $max );

		/**
		 * Return a pseudo-random saturation level between the lower and the upper bound.
		 *
		 * @phpstan-var Saturation
		 */
		return $this->number_generator->get( $min, $max );
	}
}
