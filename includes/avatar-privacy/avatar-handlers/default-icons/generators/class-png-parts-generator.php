<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Number_Generator;

/**
 * A base class for parts-based PNG icon generators.
 *
 * The class includes some functions created for Scott Sherrill-Mix' WordPress
 * plugin of MonsterID.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Scott Sherrill-Mix
 */
abstract class PNG_Parts_Generator extends Parts_Generator {

	// Units used in HSL colors.
	const PERCENT = 100;
	const DEGREE  = 360;

	/**
	 * The base size of the generated avatar.
	 *
	 * @var int
	 */
	protected $size;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $editor;

	/**
	 * The PNG image helper.
	 *
	 * @var Images\PNG
	 */
	protected $png;

	/**
	 * Creates a new generator.
	 *
	 * @param string           $parts_dir        The directory containing our image parts.
	 * @param string[]         $part_types       The valid part types for this generator.
	 * @param int              $size             The width and height of the generated image (in pixels).
	 * @param Images\Editor    $editor           The image editing handler.
	 * @param Images\PNG       $png              The PNG image helper.
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Site_Transients  $site_transients  The site transients handler.
	 */
	public function __construct(
		$parts_dir,
		array $part_types,
		$size,
		Images\Editor $editor,
		Images\PNG $png,
		Number_Generator $number_generator,
		Site_Transients $site_transients
	) {
		$this->size   = $size;
		$this->editor = $editor;
		$this->png    = $png;

		parent::__construct( $parts_dir, $part_types, $number_generator, $site_transients );
	}

	/**
	 * Renders the avatar from its parts in the given size, using any of the
	 * optional additional arguments.
	 *
	 * @param  int   $size  The target image size in pixels.
	 * @param  array $parts The (randomized) avatar parts.
	 * @param  array $args  Any additional arguments defined by the subclass.
	 *
	 * @return string       The image data (bytes).
	 */
	protected function get_avatar( $size, array $parts, array $args ) {
		// Build the avatar image in its native size.
		$avatar = $this->render_avatar( $parts, $args );

		// Resize if necessary.
		return $this->get_resized_image_data( $avatar, $size );
	}

	/**
	 * Renders the avatar from its parts, using any of the given additional arguments.
	 *
	 * @param  array $parts The (randomized) avatar parts.
	 * @param  array $args  Any additional arguments defined by the subclass.
	 *
	 * @return resource
	 */
	abstract protected function render_avatar( array $parts, array $args );

	/**
	 * Resizes the image and returns the raw data.
	 *
	 * @param  resource $image The image resource.
	 * @param  int      $size  The size in pixels.
	 *
	 * @return string          The image data (or the empty string on error).
	 */
	protected function get_resized_image_data( $image, $size ) {
		return $this->editor->get_resized_image_data(
			$this->editor->create_from_image_resource( $image ), $size, $size, 'image/png'
		);
	}

	/**
	 * Retrieves an array of SVG part type definitions.
	 *
	 * @param  array $parts An array of empty arrays indexed by part type.
	 *
	 * @return array        The same array, but now containing the part type definitions.
	 */
	protected function read_parts_from_filesystem( array $parts ) {
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

		return $parts;
	}

	/**
	 * Sorts the parts array to be independent of filesystem sort order.
	 *
	 * @param array $parts {
	 *     An array of part type definitions.
	 *
	 *     @type string $type The part definition list, indexed by type.
	 * }
	 *
	 * @return array
	 */
	protected function sort_parts( array $parts ) {
		foreach ( $parts as $key => $value ) {
			\sort( $parts[ $key ], \SORT_NATURAL );
		}

		return $parts;
	}

	/**
	 * Creates an image resource of the chosen type with the set avatar size for
	 * width and height.
	 *
	 * @since 2.3.0
	 *
	 * @param  string $type The type of background to create. Valid: 'white', 'black', 'transparent'.
	 *
	 * @return resource
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function create_image( $type ) {
		return $this->png->create( $type, $this->size, $this->size );
	}

	/**
	 * Copies an image onto an existing base image. Image parts are loaded from
	 * the parts directory if a filename is given, assuming the avatar size for
	 * width and height.
	 *
	 * The image resource is freed after copying.
	 *
	 * @param  resource        $base   The avatar image resource.
	 * @param  string|resource $image  The image to be copied onto the base. Can
	 *                                 be either the name of the image file
	 *                                 relative to the parts directory, or an
	 *                                 existing image resource.
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function combine_images( $base, $image ) {
		// Load image if we are given a filename.
		if ( \is_string( $image ) ) {
			$image = $this->png->create_from_file( "{$this->parts_dir}/{$image}" );
		}

		$this->png->combine( $base, $image, $this->size, $this->size );
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
	 * @author Peter Putzer
	 * @author Scott Sherrill-Mix
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
		$bounds = [];

		foreach ( $this->get_parts() as $file_list ) {
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
