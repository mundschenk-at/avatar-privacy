<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2023 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Exceptions\Part_Files_Not_Found_Exception;
use Avatar_Privacy\Tools\Number_Generator;

/**
 * A base class for parts-based icon generators independent of the used image
 * format. Part definitions are chached using WordPress' transient mechanism.
 *
 * The algorithm for building icons is conceptually based on Andreas Gohr's
 * MonsterId library.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type PartType string
 * @phpstan-type PartsTemplate array<PartType, array{}>
 * @phpstan-type AllPossibleParts array<PartType, string[]>
 * @phpstan-type RandomizedParts array<PartType, string>
 * @phpstan-type AdditionalArguments array<string, mixed>
 */
abstract class Parts_Generator implements Generator {

	/**
	 * The path to the monster parts image files.
	 *
	 * @var string
	 */
	protected string $parts_dir;

	/**
	 * An array of part types.
	 *
	 * @var string[]
	 */
	protected array $part_types;

	/**
	 * Lists of files, indexed by part types.
	 *
	 * @since 2.4.0 Property renamed to $all_parts, visibility changed to private.
	 *
	 * @var array {
	 *     @type string[] $type An array of files.
	 * }
	 *
	 * @phpstan-var array<string, string[]>
	 */
	private array $all_parts;

	/**
	 * The random number generator.
	 *
	 * @var Number_Generator
	 */
	protected Number_Generator $number_generator;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private Site_Transients $site_transients;

	/**
	 * Creates a new generator.
	 *
	 * @param string           $parts_dir        The directory containing our image parts.
	 * @param string[]         $part_types       The valid part types for this generator.
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Site_Transients  $site_transients  The site transients handler.
	 */
	public function __construct(
		$parts_dir,
		array $part_types,
		Number_Generator $number_generator,
		Site_Transients $site_transients
	) {
		$this->parts_dir        = $parts_dir;
		$this->part_types       = $part_types;
		$this->number_generator = $number_generator;
		$this->site_transients  = $site_transients;
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
			$this->number_generator->seed( $seed );

			// Throw the dice for avatar parts.
			$parts = $this->get_randomized_parts();

			// Prepare any additional arguments needed.
			$args = $this->get_additional_arguments( $seed, $size, $parts );

			// Build the avatar image in its final size.
			return $this->get_avatar( $size, $parts, $args );
		} catch ( \Exception $e ) {
			// Something went wrong but don't want to mess up blog layout.
			return false;
		} finally {
			// Reset randomness to something unknonwn.
			$this->number_generator->reset();
		}
	}

	/**
	 * Retrieves the "randomized" parts for the avatar being built.
	 *
	 * @return array A simple array of files, indexed by part.
	 *
	 * @throws Part_Files_Not_Found_Exception The part files could not be found.
	 *
	 * @phpstan-return RandomizedParts
	 */
	protected function get_randomized_parts() {
		return $this->randomize_parts( $this->get_parts() );
	}

	/**
	 * Prepares any additional arguments needed for rendering the avatar image.
	 *
	 * The arguments will be passed to `render_avatar()`.
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
		return [];
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
	 *
	 * @phpstan-param RandomizedParts     $parts
	 * @phpstan-param AdditionalArguments $args
	 */
	abstract protected function get_avatar( $size, array $parts, array $args );

	/**
	 * Throws the dice for parts.
	 *
	 * @param  array $parts An array of arrays containing all parts definitions.
	 *
	 * @return array        A simple array of part definitions, indexed by type.
	 *
	 * @phpstan-param  AllPossibleParts $parts
	 * @phpstan-return RandomizedParts
	 */
	protected function randomize_parts( array $parts ) {

		// Throw the dice for every part type.
		foreach ( $parts as $type => $list ) {
			$parts[ $type ] = $list[ $this->get_random_part_index( $type, \count( $list ) ) ];
		}

		return $parts;
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
		return $this->number_generator->get( 0, $count - 1 );
	}

	/**
	 * Retrieves the avatar parts image files.
	 *
	 * @return array {
	 *     An array of file lists indexed by the part name.
	 *
	 *     @type string[] $part An array of part definitions (the exact content
	 *                          is determined by the subclasses).
	 * }
	 *
	 * @throws Part_Files_Not_Found_Exception The part files could not be found.
	 *
	 * @phpstan-return AllPossibleParts
	 */
	protected function get_parts() {
		if ( empty( $this->all_parts ) ) {
			// Calculate transient key.
			$basename = \basename( $this->parts_dir );
			$key      = "avatar_privacy_{$basename}_parts";

			// Check existence of transient first.
			$cached_parts = $this->site_transients->get( $key );
			if ( \is_array( $cached_parts ) && ! empty( $cached_parts ) ) {
				/**
				 * The cached parts look good, let's use those.
				 *
				 * @phpstan-var AllPossibleParts $cached_parts
				 */
				$this->all_parts = $cached_parts;
			} else {
				// Look at the actual filesystem.
				$this->all_parts = $this->build_parts_array();

				// Only store transient if we got a result.
				if ( ! empty( $this->all_parts ) ) {
					$this->site_transients->set( $key, $this->all_parts, \YEAR_IN_SECONDS );
				}
			}
		}

		return $this->all_parts;
	}

	/**
	 * Builds a sorted array of parts.
	 *
	 * @return array {
	 *     An array of part type definitions.
	 *
	 *     @type string $type The part definition list, indexed by type.
	 * }
	 *
	 * @throws Part_Files_Not_Found_Exception The part files could not be found.
	 *
	 * @phpstan-return AllPossibleParts
	 */
	protected function build_parts_array() {
		// Make sure the keys are in the correct order.
		$empty_parts = \array_fill_keys( $this->part_types, [] );

		// Read part definitions.
		$parts = $this->read_parts_from_filesystem( $empty_parts );

		// Raise an exception if there were no files found.
		if ( $parts === $empty_parts ) {
			throw new Part_Files_Not_Found_Exception( "Could not find parts images in {$this->parts_dir}" );
		}

		return $this->sort_parts( $parts );
	}

	/**
	 * Retrieves an array of SVG part type definitions.
	 *
	 * @param  array $parts An array of empty arrays indexed by part type.
	 *
	 * @return array        The same array, but now containing the part type definitions.
	 *
	 * @phpstan-param  array<PartType, array{}> $parts
	 * @phpstan-return AllPossibleParts
	 */
	abstract protected function read_parts_from_filesystem( array $parts );

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
	 *
	 * @phpstan-param  AllPossibleParts $parts
	 * @phpstan-return AllPossibleParts
	 */
	abstract protected function sort_parts( array $parts );
}
