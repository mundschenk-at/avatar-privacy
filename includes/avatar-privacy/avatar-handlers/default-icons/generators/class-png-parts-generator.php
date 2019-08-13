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
	 * Creates a new Wavatars generator.
	 *
	 * @param string        $parts_dir  The directory containing our image parts.
	 * @param string[]      $part_types The valid part types for this generator.
	 * @param Images\Editor $images     The image editing handler.
	 */
	public function __construct( $parts_dir, array $part_types, Images\Editor $images ) {
		$this->parts_dir  = $parts_dir;
		$this->part_types = $part_types;

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
	 * Copies an image onto an existing base image. This implementation adds the
	 * ability to dynamically load image parts from the parts directory. The
	 * image resource is freed after copying.
	 *
	 * @param  resource        $base   The avatar image resource.
	 * @param  string|resource $image  The image to be copied onto the base. Can
	 *                                 be either the name of the image file
	 *                                 relative to the parts directory, or an
	 *                                 existing image resource.
	 * @param  int             $width  Image width in pixels.
	 * @param  int             $height Image height in pixels.
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function apply_image( $base, $image, $width, $height ) {

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
}
