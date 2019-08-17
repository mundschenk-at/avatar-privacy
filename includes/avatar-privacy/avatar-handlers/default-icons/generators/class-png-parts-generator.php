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

use Avatar_Privacy\Data_Storage\Site_Transients;
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
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * Creates a new generator.
	 *
	 * @param string          $parts_dir       The directory containing our image parts.
	 * @param string[]        $part_types      The valid part types for this generator.
	 * @param int             $size            The width and height of the generated image (in pixels).
	 * @param Images\Editor   $images          The image editing handler.
	 * @param Site_Transients $site_transients The site transients handler.
	 */
	public function __construct( $parts_dir, array $part_types, $size, Images\Editor $images, Site_Transients $site_transients ) {
		$this->parts_dir       = $parts_dir;
		$this->part_types      = $part_types;
		$this->size            = $size;
		$this->site_transients = $site_transients;

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
		return $this->randomize_parts( $this->get_parts(), $randomize );
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
			$image = @\imageCreateFromPNG( "{$this->parts_dir}/{$image}" );
		}

		parent::apply_image( $base, /* @scrutinizer ignore-type */ $image, $width, $height );
	}

	/**
	 * Retrieves the avatar parts image files.
	 *
	 * @return array {
	 *     An array of file lists indexed by the part name.
	 *
	 *     @type string[] $part An array of filenames (without the path).
	 * }
	 *
	 * @throws \RuntimeException The part files could not be found.
	 */
	protected function get_parts() {
		if ( empty( $this->parts ) ) {
			// Calculate transient key.
			$basename = \basename( $this->parts_dir );
			$key      = "avatar_privacy_{$basename}_png_parts";

			// Check existence of transient.
			$this->parts = $this->site_transients->get( $key );
			if ( empty( $this->parts ) ) {
				// Look at the actual filesystem.
				$this->parts = $this->locate_parts();

				// Only store transient if we got a result.
				if ( ! empty( $this->parts ) ) {
					$this->site_transients->set( $key, $this->parts );
				}
			}
		}

		// Raise an exception if there were no files found.
		if ( empty( $this->parts ) ) {
			throw new \RuntimeException( "Could not find parts images in {$this->parts_dir}" );
		}

		return $this->parts;
	}

	/**
	 * Finds all avatar parts images on the filesystem. Normally you should use
	 * the cached function `get_parts()` instead.
	 *
	 * @since 2.3.0 Moved to PNG_Parts_Generator class. Parameter $parts removed.
	 *              Made internal.
	 *
	 * @return array {
	 *     An array of file lists indexed by the part name, or the empty array.
	 *
	 *     @type string[] $part An array of filenames (without the path).
	 * }
	 */
	protected function locate_parts() {
		// Make sure the keys are in the correct order.
		$parts = \array_fill_keys( $this->part_types, [] );

		// Keep copy of original array check if we found anything.
		$empty = $parts;

		// Iterate over the files in the parts directory.
		$dir = new \FilesystemIterator(
			$this->parts_dir,
			\FilesystemIterator::KEY_AS_FILENAME |
			\FilesystemIterator::CURRENT_AS_PATHNAME |
			\FilesystemIterator::SKIP_DOTS
		);
		foreach ( $dir as $file => $path ) {
			list( $partname, ) = \explode( '_', $file );
			if ( isset( $parts[ $partname ] ) ) {
				$parts[ $partname ][] = $file;
			}
		}

		// Sort for consistency across servers.
		foreach ( $parts as $key => $value ) {
			\sort( $parts[ $key ], \SORT_NATURAL );
		}

		// Return an empty array if there were no files found.
		if ( $parts === $empty ) {
			$parts = [];
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
	 * @since 2.3.0 Moved to PNG_Parts_Generator class and paramter $text removed.
	 *              Use new method `get_parts_dimensions_as_text` to retrieve the
	 *              human-readable array definition.
	 *
	 * @return array {
	 *     An array of boundary coordinates indexed by filename.
	 *
	 *     @type array $file {
	 *         The boundary coordinates for a single file.
	 *
	 *         @type int[] $xbounds The low and high boundary on the X axis.
	 *         @type int[] $ybounds The low and high boundary on the Y axis.
	 *     }
	 * }
	 */
	protected function get_parts_dimensions() {
		$parts  = $this->get_parts();
		$bounds = [];

		foreach ( $parts as $part_type => $file_list ) {
			foreach ( $file_list as $file ) {
				$im = @\imageCreateFromPNG( "{$this->parts_dir}/{$file}" );

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
						$rgb       = \imageColorAt( $im, $i, $j );
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

				$bounds[ $file ] = [ $xbounds, $ybounds ];
			}
		}

		return $bounds;
	}

	/**
	 * Prints the exact dimensions for individual parts as human-readable PHP
	 * array definitions.
	 *
	 * Mainly useful for subclasses exchanging the provided images.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	protected function get_parts_dimensions_as_text() {
		$result = '';

		foreach ( $this->get_parts_dimensions() as $part => $bounds ) {
			list( $xbounds, $ybounds ) = $bounds;

			$result .= "'$part' => [ [ {$xbounds[0]}, {$xbounds[1]} ], [ {$ybounds[0]}, {$ybounds[1]} ] ],\n";
		}

		return $result;
	}
}
