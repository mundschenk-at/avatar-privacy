<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Default_Icons\Monster_ID;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A monster generator.
 *
 * @since 1.0.0
 */
class Monster_ID {
	const SAME_COLOR_PARTS     = [
		'arms_S8.png',
		'legs_S5.png',
		'legs_S13.png',
		'mouth_S5.png',
		'mouth_S4.png',
	];
	const SPECIFIC_COLOR_PARTS = [
		'hair_S4.png'  => [ .6, .75 ],
		'arms_S2.png'  => [ -.05, .05 ],
		'hair_S6.png'  => [ -.05, .05 ],
		'mouth_9.png'  => [ -.05, .05 ],
		'mouth_6.png'  => [ -.05, .05 ],
		'mouth_S2.png' => [ -.05, .05 ],
	];
	const RANDOM_COLOR_PARTS   = [
		'arms_3.png',
		'arms_4.png',
		'arms_5.png',
		'arms_S1.png',
		'arms_S3.png',
		'arms_S5.png',
		'arms_S6.png',
		'arms_S7.png',
		'arms_S9.png',
		'hair_S1.png',
		'hair_S2.png',
		'hair_S3.png',
		'hair_S5.png',
		'legs_1.png',
		'legs_2.png',
		'legs_3.png',
		'legs_5.png',
		'legs_S1.png',
		'legs_S2.png',
		'legs_S3.png',
		'legs_S4.png',
		'legs_S6.png',
		'legs_S7.png',
		'legs_S10.png',
		'legs_S12.png',
		'mouth_3.png',
		'mouth_4.png',
		'mouth_7.png',
		'mouth_10.png',
		'mouth_S6.png',
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

	// Units used in HSL colors.
	const PERCENT = 100;
	const DEGREE  = 360;

	/**
	 * The path to the monster parts image files.
	 *
	 * @var string
	 */
	private $parts_dir;

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		$this->parts_dir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/public/images/monster-id';
	}

	/**
	 * Finds all the monster parts images.
	 *
	 * @param  array $parts An array of arrays indexed by body parts.
	 *
	 * @return array
	 *
	 * @throws \RuntimeException The part files could not be found.
	 */
	private function locate_parts( array $parts ) {
		$noparts = true;
		if ( false !== ( $dh = opendir( $this->parts_dir ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found,WordPress.CodeAnalysis.AssignmentInCondition.Found
			while ( false !== ( $file = readdir( $dh ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found,WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				if ( is_file( "{$this->parts_dir}/{$file}" ) ) {
					list( $partname, ) = explode( '_', $file );
					if ( isset( $parts[ $partname ] ) ) {
						$parts[ $partname ][] = $file;
						$noparts              = false;
					}
				}
			}

			closedir( $dh );
		}

		if ( $noparts ) {
			throw new \RuntimeException( "Could not find parts images in {$this->parts_dir}" );
		}

		// Sort for consistency across servers.
		foreach ( $parts as $key => $value ) {
			sort( $parts[ $key ], SORT_NATURAL );
		}

		return $parts;
	}

	/**
	 * Determines exact dimensions for individual parts.
	 *
	 * @param  bool $text A flag that determines whether a human readable result should be returned.
	 *
	 * @return string|array
	 */
	private function get_parts_dimensions( $text = false ) {
		$parts = $this->locate_parts( [
			'legs'  => [],
			'hair'  => [],
			'arms'  => [],
			'body'  => [],
			'eyes'  => [],
			'mouth' => [],
		] );

		$bounds      = [];
		$result_text = '';

		foreach ( $parts as $key => $value ) {
			foreach ( $value as $part ) {
				$file    = "{$this->parts_dir}/{$part}";
				$im      = imagecreatefrompng( $file );
				$imgw    = imagesx( $im );
				$imgh    = imagesy( $im );
				$xbounds = [ 999999, 0 ];
				$ybounds = [ 999999, 0 ];
				for ( $i = 0;$i < $imgw;$i++ ) {
					for ( $j = 0;$j < $imgh;$j++ ) {
						$rgb       = ImageColorAt( $im, $i, $j );
						$r         = ( $rgb >> 16 ) & 0xFF;
						$g         = ( $rgb >> 8 ) & 0xFF;
						$b         = $rgb & 0xFF;
						$alpha     = ( $rgb & 0x7F000000 ) >> 24;
						$lightness = ( $r + $g + $b ) / 3 / 255 * self::PERCENT;
						if ( $lightness > 10 && $lightness < 99 && $alpha < 115 ) {
							$xbounds[0] = min( $xbounds[0],$i );
							$xbounds[1] = max( $xbounds[1],$i );
							$ybounds[0] = min( $ybounds[0],$j );
							$ybounds[1] = max( $ybounds[1],$j );
						}
					}
				}
				$result_text    .= "'$part' => [[${xbounds[0]},${xbounds[1]}],[${ybounds[0]},${ybounds[1]}]], ";
				$bounds[ $part ] = [ $xbounds, $ybounds ];
			}
		}

		if ( $text ) {
			return $result_text;
		} else {
			return $bounds;
		}
	}

	/**
	 * Builds a monster icon and returns the image data.
	 *
	 * @param  string $seed The seed data (hash).
	 * @param  int    $size Options. Size in pixels. Default maximum size (400 px).
	 *
	 * @return string|false
	 */
	public function build_monster( $seed, $size = 0 ) {

		// Init random seed.
		$id = substr( $seed, 0, 8 );

		if ( empty( $size ) ) {
			$size = 400; // px.
		}

		// Get possible parts files.
		$parts_array = $this->locate_parts( [
			'legs'  => [],
			'hair'  => [],
			'arms'  => [],
			'body'  => [],
			'eyes'  => [],
			'mouth' => [],
		] );

		// Set randomness.
		mt_srand( (int) hexdec( $id ) );

		// Throw the dice for body parts.
		foreach ( $parts_array as $part => $files ) {
			$parts_array[ $part ] = $files[ mt_rand( 0, count( $files ) - 1 ) ];
		}

		// Create background.
		$monster = @imagecreatefrompng( "{$this->parts_dir}/back.png" );
		if ( false === $monster ) {
			return false; // Something went wrong but don't want to mess up blog layout.
		}

		$max_rand   = mt_getrandmax();
		$hue        = ( ( mt_rand( 1, $max_rand ) - 1 ) / $max_rand ) * self::DEGREE; // real_halfopen.
		$saturation = mt_rand( 25000, 100000 ) / 100000 * self::PERCENT;

		// Add parts.
		foreach ( $parts_array as $part => $file ) {
			$im = @imagecreatefrompng( "{$this->parts_dir}/{$file}" );
			if ( ! $im ) {
				return false; // Something went wrong but don't want to mess up blog layout.
			}
			imageSaveAlpha( $im, true );

			// Randomly color body parts.
			if ( 'body' === $part ) {
				$this->image_colorize( $im, $hue, $saturation, $file );
			} elseif ( in_array( $file, self::SAME_COLOR_PARTS, true ) ) {
				$this->image_colorize( $im, $hue, $saturation, $file );
			} elseif ( in_array( $file, self::RANDOM_COLOR_PARTS, true ) ) {
				$this->image_colorize( $im, ( mt_rand( 1, $max_rand ) - 1 ) / $max_rand * self::DEGREE, mt_rand( 25000, 100000 ) / 100000 * self::PERCENT, $file );
			} elseif ( array_key_exists( $file, self::SPECIFIC_COLOR_PARTS ) ) {
				$low  = self::SPECIFIC_COLOR_PARTS[ $file ][0] * 10000;
				$high = self::SPECIFIC_COLOR_PARTS[ $file ][1] * 10000;
				$this->image_colorize( $im, mt_rand( $low, $high ) / 10000 * self::DEGREE, mt_rand( 25000, 100000 ) / 100000 * self::PERCENT, $file );
			}
			imagecopy( $monster, $im, 0, 0, 0, 0, 120, 120 );
			imagedestroy( $im );
		}

		// Going to resize always for now.
		$out = @imagecreatetruecolor( $size, $size );
		if ( false === $out ) {
			return false; // Something went wrong but don't want to mess up blog layout.
		}

		// Save transparent background.
		imageSaveAlpha( $out, true );
		imageAlphaBlending( $out, false );

		// Resize final image.
		imagecopyresampled( $out, $monster, 0, 0, 0, 0, $size, $size, 120, 120 );
		imagedestroy( $monster );

		$stream = new \Bcn\Component\StreamWrapper\Stream();
		$wrote  = @imagepng( $out, fopen( $stream, 'w' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $wrote ) {
			return false; // Something went wrong but don't want to mess up blog layout.
		}
		imagedestroy( $out );

		// Reset randomness.
		mt_srand();

		// Return image.
		return $stream->getContent();
	}

	/**
	 * Adds color to the given image.
	 *
	 * @param  resource $im         The image. Passed by reference.
	 * @param  int      $hue        The hue (0-360).
	 * @param  int      $saturation The saturation (0-100).
	 * @param  string   $part       The part name.
	 *
	 * @return resource             The image, for chaining.
	 */
	private function image_colorize( &$im, $hue = 360, $saturation = 100, $part = '' ) {
		$imgw = imagesx( $im );
		$imgh = imagesy( $im );

		// Ensure non-negative hue.
		$hue = $hue < 0 ? self::DEGREE + $hue : $hue;

		imagealphablending( $im, false );
		if ( isset( self::PART_OPTIMIZATION[ $part ] ) ) {
			$optimize = self::PART_OPTIMIZATION[ $part ];
			$xmin     = $optimize[0][0];
			$xmax     = $optimize[0][1];
			$ymin     = $optimize[1][0];
			$ymax     = $optimize[1][1];
		} else {
			$xmin = 0;
			$xmax = $imgw - 1;
			$ymin = 0;
			$ymax = $imgh - 1;
		}

		for ( $i = $xmin; $i <= $xmax; $i++ ) {
			for ( $j = $ymin; $j <= $ymax; $j++ ) {
				$rgb       = ImageColorAt( $im, $i, $j );
				$r         = ( $rgb >> 16 ) & 0xFF;
				$g         = ( $rgb >> 8 ) & 0xFF;
				$b         = $rgb & 0xFF;
				$alpha     = ( $rgb & 0x7F000000 ) >> 24;
				$lightness = ( $r + $g + $b ) / 3 / 255 * self::PERCENT;
				if ( $lightness > 10 && $lightness < 99 && $alpha < 115 ) {
					$newrgb = HSLtoRGB( $hue, $saturation, $lightness );
					// The green and blue were switched in the original hsl_2_rgb function, so we keep
					// the same behavior for backwards compatibility reasons.
					$color = imagecolorallocatealpha( $im, $newrgb[0], $newrgb[2], $newrgb[1], $alpha );
					imagesetpixel( $im, $i, $j, $color );
				}
			}
		}
		imagealphablending( $im, true );

		return $im;
	}
}
