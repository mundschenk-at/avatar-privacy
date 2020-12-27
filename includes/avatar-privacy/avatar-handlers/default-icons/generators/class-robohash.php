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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators\Parts_Generator;

use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Number_Generator;
use Avatar_Privacy\Tools\Template;

/**
 * An icon generator based on the Robohash SVG library by Nimiq.
 *
 * The code is a new implementation based on the general idea (only color
 * pre-selections have been reused).
 *
 * @link https://github.com/nimiq/robohash
 *
 * @since 2.3.0
 * @since 2.4.0 Internal method render_svg removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Robohash extends Parts_Generator {

	const COLORS = [
		'#ff9800', // orange-500.
		'#E53935', // red-600.
		'#FDD835', // yellow-600.
		'#3f51b5', // indigo-500.
		'#03a9f4', // light-blue-500.
		'#9c27b0', // purple-500.
		'#009688', // teal-500.
		'#EC407A', // pink-400.
		'#8bc34a', // light-green-500.
		'#795548', // brown-500.
	];

	const BG_COLORS = [
		/* Red  */
		'#FF8A80', // red-a100.
		'#F48FB1', // pink-200.
		'#ea80fc', // purple-a100.

		/* Blue */
		'#8c9eff', // indigo-a100.
		'#80d8ff', // light-blue-a100.
		'#CFD8DC', // blue-grey-100.

		/* Green */
		'#1DE9B6', // teal-a400.
		'#00C853', // green-a-700.

		/* Orange */
		'#FF9E80', // deep-orange-a100.
		'#FFE57F', // amber-a100.
	];

	/**
	 * The templating handler.
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0 Parameter $template added.
	 *
	 * @param Number_Generator $number_generator A pseudo-random number generator.
	 * @param Site_Transients  $site_transients  The transients handler.
	 * @param Template         $template         The templating handler.
	 */
	public function __construct( Number_Generator $number_generator, Site_Transients $site_transients, Template $template ) {
		parent::__construct(
			\dirname( \AVATAR_PRIVACY_PLUGIN_FILE ) . '/public/images/robohash',
			[ 'body', 'face', 'eyes', 'mouth', 'accessory' ],
			$number_generator,
			$site_transients
		);

		$this->template = $template;
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
	 */
	protected function get_additional_arguments( $seed, $size, array $parts ) {
		$color    = self::COLORS[ $this->number_generator->get( 0, \count( self::COLORS ) - 1 ) ];
		$bg_color = self::BG_COLORS[ $this->number_generator->get( 0, \count( self::BG_COLORS ) - 1 ) ];

		return [
			'color'    => $color,
			'bg_color' => $bg_color,
		];
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

		// Add robot parts to arguments.
		$args['body']      = $parts['body'];
		$args['face']      = $parts['face'];
		$args['eyes']      = $parts['eyes'];
		$args['mouth']     = $parts['mouth'];
		$args['accessory'] = $parts['accessory'];

		return $this->template->get_partial( 'public/partials/robohash/svg.php', $args );
	}

	/**
	 * Retrieves an array of SVG part type definitions.
	 *
	 * @param  array $parts An array of empty arrays indexed by part type.
	 *
	 * @return array        The same array, but now containing the part type definitions.
	 */
	protected function read_parts_from_filesystem( array $parts ) {
		// Get a recursive depth-first iterator over the part type directories.
		$dir = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$this->parts_dir,
				\FilesystemIterator::KEY_AS_FILENAME |
				\FilesystemIterator::CURRENT_AS_FILEINFO |
				\FilesystemIterator::SKIP_DOTS
			),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		// Iterate over the files in the parts directory.
		foreach ( $dir as $file => $info ) {
			if ( ! $info->isFile() ) {
				continue;
			}

			list( $partname, ) = \explode( '-', $file );
			if ( isset( $parts[ $partname ] ) ) {
				$parts[ $partname ][ $file ] = $this->prepare_svg_part(
					(string) \file_get_contents( $info ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				);
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
		foreach ( $parts as $key => $list ) {
			\ksort( $list, \SORT_NATURAL );
			$parts[ $key ] = \array_values( $list );
		}

		return $parts;
	}

	/**
	 * Combines the parts into a single SVG image.
	 *
	 * @param  string $color     The robot body color as CSS color string (e.g. `#ff9800`).
	 * @param  string $bg_color  The background color as a CSS color string (e.g. `#80d8ff`).
	 * @param  string $body      The SVG elements making up the robot's body.
	 * @param  string $face      The SVG elements making up the robot's face.
	 * @param  string $eyes      The SVG elements making up the robot's eyes.
	 * @param  string $mouth     The SVG elements making up the robot's mouth.
	 * @param  string $accessory The SVG elements making up the robot's accessory.
	 *
	 * @return string
	 */
	protected function render_svg( $color, $bg_color, $body, $face, $eyes, $mouth, $accessory ) {
		\ob_start();
		require \AVATAR_PRIVACY_PLUGIN_PATH . '/public/partials/robohash/svg.php';
		return (string) \ob_get_clean();
	}

	/**
	 * Prepares SVG elements for inclusion as robot parts.
	 *
	 * @param  string $svg The part to include.
	 *
	 * @return string
	 */
	protected function prepare_svg_part( $svg ) {
		$svg = \preg_replace(
			[
				'#<svg[^>]+>(.*)</svg>#',
				'/#26a9e0/',
			], [
				'$1',
				'currentColor',
			],
			$svg
		);

		return "<g transform=\"translate(0,20)\">{$svg}</g>";
	}
}
