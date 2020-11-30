<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

namespace Avatar_Privacy\Tools\Images;

use function Scriptura\Color\Helpers\HSLtoRGB;

/**
 * A utility class providing some methods for dealing with PNG images.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class PNG {
	/**
	 * Creates an image resource of the chosen type.
	 *
	 * @param  string $type   The type of background to create. Valid: 'white', 'black', 'transparent'.
	 * @param  int    $width  Image width in pixels.
	 * @param  int    $height Image height in pixels.
	 *
	 * @return resource
	 *
	 * @throws \InvalidArgumentException Called with an incorrect type.
	 * @throws \RuntimeException         The image could not be created.
	 */
	public function create( $type, $width, $height ) {
		$image = \imageCreateTrueColor( $width, $height );

		// Something went wrong, badly.
		if ( ! \is_resource( $image ) ) {
			throw new \RuntimeException( "The image of type {$type} ($width x $height) could not be created." );  // @codeCoverageIgnore
		}

		// Don't do alpha blending for the initial fill operation.
		\imageAlphaBlending( $image, false );
		\imageSaveAlpha( $image, true );

		try {
			// Fill image with appropriate color.
			switch ( $type ) {
				case 'transparent':
					$color = \imageColorAllocateAlpha( $image, 0, 0, 0, 127 );
					break;

				case 'white':
					$color = \imageColorAllocateAlpha( $image, 255, 255, 255, 0 );
					break;

				case 'black':
					// No need to do anything else.
					return $image;

				default:
					throw new \InvalidArgumentException( "Invalid image type $type." );
			}

			if ( false === $color || ! \imageFilledRectangle( $image, 0, 0, $width, $height, $color ) ) {
				throw new \RuntimeException( "Error filling image of type $type." ); // @codeCoverageIgnore
			}
		} catch ( \RuntimeException $e ) {
			// Clean up and re-throw exception.
			\imageDestroy( $image ); // @codeCoverageIgnoreStart
			throw $e;                // @codeCoverageIgnoreEnd
		}

		// Fix transparent background.
		\imageAlphaBlending( $image, true );

		return $image;
	}

	/**
	 * Creates an image resource from the given file.
	 *
	 * @param  string $file The absolute path to a PNG image file.
	 *
	 * @return resource
	 *
	 * @throws \RuntimeException The image could not be read.
	 */
	public function create_from_file( $file ) {
		$image = @\imageCreateFromPNG( $file );

		// Something went wrong, badly.
		if ( ! \is_resource( $image ) ) {
			throw new \RuntimeException( "The PNG image {$file} could not be read." );
		}

		// Fix transparent background.
		\imageAlphaBlending( $image, true );
		\imageSaveAlpha( $image, true );

		return $image;
	}

	/**
	 * Copies an image onto an existing base image. The image resource is freed
	 * after copying.
	 *
	 * @param  resource $base   The avatar image resource.
	 * @param  resource $image  The image to be copied onto the base.
	 * @param  int      $width  Image width in pixels.
	 * @param  int      $height Image height in pixels.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException One of the first two parameters was not a valid image resource.
	 * @throws \RuntimeException         The image could not be copied.
	 */
	public function combine( $base, $image, $width, $height ) {

		// Abort if $image is not a valid resource.
		if ( ! \is_resource( $base ) || ! \is_resource( $image ) ) {
			throw new \InvalidArgumentException( 'Invalid image resource.' );
		}

		// Copy the image to the base.
		$result = \imageCopy( $base, $image, 0, 0, 0, 0, $width, $height );

		// Clean up.
		\imageDestroy( $image );

		// Return copy success status.
		if ( ! $result ) {
			throw new \RuntimeException( 'Error while copying image.' ); // @codeCoverageIgnore
		}
	}

	/**
	 * Fills the given image with a HSL color.
	 *
	 * @param  resource $image      The image.
	 * @param  int      $hue        The hue (0-360).
	 * @param  int      $saturation The saturation (0-100).
	 * @param  int      $lightness  The lightness/Luminosity (0-100).
	 * @param  int      $x          The horizontal coordinate.
	 * @param  int      $y          The vertical coordinate.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException Not a valid image resource.
	 * @throws \RuntimeException         The image could not be filled.
	 */
	public function fill_hsl( $image, $hue, $saturation, $lightness, $x, $y ) {
		// Abort if $image is not a valid resource.
		if ( ! \is_resource( $image ) ) {
			throw new \InvalidArgumentException( 'Invalid image resource.' );
		}

		list( $red, $green, $blue ) = HSLtoRGB( $hue, $saturation, $lightness );
		$color                      = \imageColorAllocate( $image, $red, $green, $blue );

		if ( false === $color || ! \imageFill( $image, $x, $y, $color ) ) {
			throw new \RuntimeException( "Error filling image with HSL ({$hue}, {$saturation}, {$lightness})." ); // @codeCoverageIgnore
		}
	}
}
