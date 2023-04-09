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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\PNG_Parts_Generator;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Number_Generator;

use GdImage; // phpcs:ignore ImportDetection.Imports -- PHP 8.0 compatibility.

/**
 * A cat avatar generator for the images created by David Revoy.
 *
 * See https://www.davidrevoy.com/article591/cat-avatar-generator
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type PartType value-of<self::PARTS>
 * @phpstan-type PartsTemplate array<PartType, array{}>
 * @phpstan-type AllPossibleParts array<PartType, string[]>
 * @phpstan-type RandomizedParts array<PartType, string>
 * @phpstan-type AdditionalArguments array{}
 */
class Cat_Avatar extends PNG_Parts_Generator {

	/**
	 * All Cat parts in their natural order.
	 *
	 * @since 2.7.0
	 */
	private const PARTS = [ 'body', 'fur', 'eyes', 'mouth', 'accessoire' ];

	/**
	 * Creates a new instance.
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
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/cats',
			self::PARTS,
			512,
			$editor,
			$png,
			$number_generator,
			$site_transients
		);
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
		$cat = $this->create_image( 'transparent' );

		// Add parts.
		foreach ( $parts as $file ) {
			$this->combine_images( $cat, $file );
		}

		return $cat;
	}
}
