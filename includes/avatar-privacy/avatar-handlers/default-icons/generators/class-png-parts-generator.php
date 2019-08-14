<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
 * Copyright 2007-2014 Scott Sherrill-Mix.
 * Copyright 2007-2008 Shamus Young.
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

/**
 * A base class for parts-based PNG icon generators.
 *
 * @since 2.3.0
 */
abstract class PNG_Parts_Generator extends PNG_Generator {

	/**
	 * The path to the monster parts image files.
	 *
	 * @var string
	 */
	protected $parts_dir;

	/**
	 * An array of part types.
	 *
	 * @var string[]
	 */
	protected $part_types;

	/**
	 * Lists of files, indexed by part types.
	 *
	 * @var array {
	 *     @type string[] $type An array of files.
	 * }
	 */
	protected $parts;

	/**
	 * The base size of the generated avatar.
	 *
	 * @var int
	 */
	protected $size;

	/**
	 * Creates a new generator.
	 *
	 * @param string        $parts_dir  The directory containing our image parts.
	 * @param string[]      $part_types The valid part types for this generator.
	 * @param int           $size       The width and height of the generated image (in pixels).
	 * @param Images\Editor $images     The image editing handler.
	 */
	public function __construct( $parts_dir, array $part_types, $size, Images\Editor $images ) {
		$this->parts_dir  = $parts_dir;
		$this->part_types = $part_types;
		$this->size       = $size;

		parent::__construct( $images );
	}

	/**
	 * Retrieves the "randomized" parts for the avatar being built.
	 *
	 * @param  callable $randomize A randomization function taking a minimum and
	 *                             maximum value and the parts type as its argument;
	 *                             returning an integer.
	 *
	 * @return array               A simple array of files, indexe by part.
	 *
	 * @throws \RuntimeException The part files could not be found.
	 */
	protected function get_randomized_parts( callable $randomize ) {
		if ( empty( $this->parts ) ) {
			$this->parts = $this->locate_parts( \array_fill_keys( $this->part_types, [] ) );
		}

		return $this->randomize_parts( $this->parts, $randomize );
	}

	/**
	 * Creates an image resource of the chosen type.
	 *
	 * @since 2.3.0
	 *
	 * @param  string $type   The type of background to create. Valid: 'white', 'black', 'transparent'.
	 * @param  int    $width  Optional. Image width in pixels. Default is the base size.
	 * @param  int    $height Optional. Image height in pixels. Default is the base size.
	 *
	 * @return resource
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function create_image( $type, $width = null, $height = null ) {

		// Apply image width and height defaults.
		if ( empty( $width ) ) {
			$width = $this->size;
		}
		if ( empty( $height ) ) {
			$height = $this->size;
		}

		return parent::create_image( $type, $width, $height );
	}

	/**
	 * Copies an image onto an existing base image. This implementation adds the
	 * ability to dynamically load image parts from the parts directory, and the
	 * the width and height arguments are optional.
	 *
	 * The image resource is freed after copying.
	 *
	 * @param  resource        $base   The avatar image resource.
	 * @param  string|resource $image  The image to be copied onto the base. Can
	 *                                 be either the name of the image file
	 *                                 relative to the parts directory, or an
	 *                                 existing image resource.
	 * @param  int             $width  Optional. Image width in pixels. Default is the base size.
	 * @param  int             $height Optional. Image height in pixels. Default is the base size.
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function apply_image( $base, $image, $width = null, $height = null ) {

		// Apply image width and height defaults.
		if ( empty( $width ) ) {
			$width = $this->size;
		}
		if ( empty( $height ) ) {
			$height = $this->size;
		}

		// Load image if we are given a filename.
		if ( \is_string( $image ) ) {
			$image = @\imagecreatefrompng( "{$this->parts_dir}/{$image}" );
		}

		parent::apply_image( $base, /* @scrutinizer ignore-type */ $image, $width, $height );
	}

	/**
	 * Finds all avatar parts images.
	 *
	 * @since 2.3.0 Moved to PNG_Parts_Generator class.
	 *
	 * @param  array $parts An array of arrays indexed by body parts.
	 *
	 * @return array
	 *
	 * @throws \RuntimeException The part files could not be found.
	 */
	protected function locate_parts( array $parts ) {
		$noparts = true;
		if ( false !== ( $dh = \opendir( $this->parts_dir ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found,WordPress.CodeAnalysis.AssignmentInCondition.Found
			while ( false !== ( $file = \readdir( $dh ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found,WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				if ( \is_file( "{$this->parts_dir}/{$file}" ) ) {
					list( $partname, ) = \explode( '_', $file );
					if ( isset( $parts[ $partname ] ) ) {
						$parts[ $partname ][] = $file;
						$noparts              = false;
					}
				}
			}

			\closedir( $dh );
		}

		if ( $noparts ) {
			throw new \RuntimeException( "Could not find parts images in {$this->parts_dir}" );
		}

		// Sort for consistency across servers.
		foreach ( $parts as $key => $value ) {
			\sort( $parts[ $key ], \SORT_NATURAL );
		}

		return $parts;
	}

	/**
	 * Throws the dice for parts.
	 *
	 * @param  array    $parts     An array of arrays containing all parts files.
	 * @param  callable $randomize A randomization function taking a minimum and
	 *                             maximum value and the parts type as its argument;
	 *                             returning an integer.
	 *
	 * @return array               A simple array of files, indexe by part.
	 */
	protected function randomize_parts( array $parts, callable $randomize ) {

		// Throw the dice for every part type.
		foreach ( $parts as $type => $files ) {
			$parts[ $type ] = $files[ $randomize( 0, \count( $files ) - 1, $type ) ];
		}

		return $parts;
	}

	/**
	 * Determines exact dimensions for individual parts. Mainly useful for subclasses
	 * exchanging the provided images.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.3.0 Moved to PNG_Parts_Generator class.
	 *
	 * @param  bool $text A flag that determines whether a human readable result should be returned.
	 *
	 * @return string|array
	 */
	protected function get_parts_dimensions( $text = false ) {
		$parts = $this->locate_parts( \array_fill_keys( $this->part_types, [] ) );

		$bounds      = [];
		$result_text = '';

		foreach ( $parts as $key => $value ) {
			foreach ( $value as $part ) {
				$im = @\imageCreateFromPNG( "{$this->parts_dir}/{$part}" );

				if ( false === $im ) {
					// Not a valid image file.
					continue;
				}

				$imgw    = \imageSX( $im );
				$imgh    = \imageSY( $im );
				$xbounds = [ 999999, 0 ];
				$ybounds = [ 999999, 0 ];
				for ( $i = 0;$i < $imgw;$i++ ) {
					for ( $j = 0;$j < $imgh;$j++ ) {
						$rgb       = \ImageColorAt( $im, $i, $j );
						$r         = ( $rgb >> 16 ) & 0xFF;
						$g         = ( $rgb >> 8 ) & 0xFF;
						$b         = $rgb & 0xFF;
						$alpha     = ( $rgb & 0x7F000000 ) >> 24;
						$lightness = ( $r + $g + $b ) / 3 / 255 * self::PERCENT;
						if ( $lightness > 10 && $lightness < 99 && $alpha < 115 ) {
							$xbounds[0] = \min( $xbounds[0],$i );
							$xbounds[1] = \max( $xbounds[1],$i );
							$ybounds[0] = \min( $ybounds[0],$j );
							$ybounds[1] = \max( $ybounds[1],$j );
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
}
