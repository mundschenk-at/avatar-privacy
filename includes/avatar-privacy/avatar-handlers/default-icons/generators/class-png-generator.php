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
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A base class for PNG-based icon generators.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators
 * @since 2.3.0 The $parts_dir property has been moved to the new PNG_Parts_Generator class.
 */
abstract class PNG_Generator implements Generator {

	// Units used in HSL colors.
	const PERCENT = 100;
	const DEGREE  = 360;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $images;

	/**
	 * Creates a new generator.
	 *
	 * @since 2.3.0 The $parts_dir parameter has been removed.
	 *
	 * @param Images\Editor $images The image editing handler.
	 */
	public function __construct( Images\Editor $images ) {
		$this->images = $images;
	}

	/**
	 * Copies an image onto an existing base image. The image resource is freed
	 * after copying.
	 *
	 * @since 2.1.0 Returns true on success, false on error.
	 * @since 2.3.0 Throws a RuntimeException instead of returning a boolean.
	 *              The $image parameter now only takes a resource, not a string.
	 *
	 * @param  resource $base   The avatar image resource.
	 * @param  resource $image  The image to be copied onto the base.
	 * @param  int      $width  Image width in pixels.
	 * @param  int      $height Image height in pixels.
	 *
	 * @throws \RuntimeException The image could not be copied.
	 */
	protected function apply_image( $base, $image, $width, $height ) {

		// Abort if $image is not a valid resource.
		if ( ! \is_resource( $base ) || ! \is_resource( $image ) ) {
			throw new \RuntimeException( 'Invalid image resource.' );
		}

		// Copy the image to the base.
		$result = \imagecopy( $base, $image, 0, 0, 0, 0, $width, $height );

		// Clean up.
		\imagedestroy( $image );

		// Return copy success status.
		if ( ! $result ) {
			throw new \RuntimeException( 'Error while copying image.' ); // @codeCoverageIgnore
		}
	}

	/**
	 * Fill the given image with a HSL color.
	 *
	 * @param  resource $image      The image.
	 * @param  int      $hue        The hue (0-360).
	 * @param  int      $saturation The saturation (0-100).
	 * @param  int      $lightness  The lightness/Luminosity (0-100).
	 * @param  int      $x          The horizontal coordinate.
	 * @param  int      $y          The vertical coordinate.
	 *
	 * @return bool
	 */
	protected function fill( $image, $hue, $saturation, $lightness, $x, $y ) {
		$rgb   = HSLtoRGB( $hue, $saturation, $lightness );
		$color = \imagecolorallocate( $image, $rgb[0], $rgb[1], $rgb[2] );

		if ( false !== $color ) {
			return \imagefill( $image, $x, $y, $color );
		}

		return false;
	}

	/**
	 * Resizes the image and returns the raw data.
	 *
	 * @param  resource $image The image resource.
	 * @param  int      $size  The size in pixels.
	 *
	 * @return string          The image data (or the empty string on error).
	 */
	protected function get_resized_image_data( $image, $size ) {
		return $this->images->get_resized_image_data(
			$this->images->create_from_image_resource( $image ), $size, $size, 'image/png'
		);
	}
}
