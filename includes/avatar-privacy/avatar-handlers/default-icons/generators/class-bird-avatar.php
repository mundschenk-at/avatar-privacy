<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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

/**
 * A bird avatar generator for the images created by David Revoy.
 *
 * See https://www.peppercarrot.com/extras/html/2019_bird-generator/
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Bird_Avatar extends PNG_Parts_Generator {

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
			\AVATAR_PRIVACY_PLUGIN_PATH . '/public/images/birds',
			[ 'tail', 'hoop', 'body', 'wing', 'eyes', 'beak', 'accessoire' ],
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
	 * @param  array $parts The (randomized) avatar parts.
	 * @param  array $args  Any additional arguments defined by the subclass.
	 *
	 * @return resource
	 */
	protected function render_avatar( array $parts, array $args ) {
		// Create background.
		$bird = $this->create_image( 'transparent' );

		// Add parts.
		foreach ( $parts as $part_type => $file ) {
			$this->combine_images( $bird, $file );
		}

		return $bird;
	}
}
