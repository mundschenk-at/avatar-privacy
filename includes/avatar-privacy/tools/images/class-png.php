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

namespace Avatar_Privacy\Tools\Images;

use Avatar_Privacy\Exceptions\PNG_Image_Exception;
use Avatar_Privacy\Tools\Images\Color;

use GdImage; // phpcs:ignore ImportDetection.Imports -- PHP 8.0 compatibility.

/**
 * A utility class providing some methods for dealing with PNG images.
 *
 * @since  2.3.0
 * @since  2.4.0 The class now uses `PNG_Image_Exception` instead of plain `RuntimeException`.
 * @since  2.7.0 Class marked as internal.
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @internal
 *
 * @phpstan-import-type RGBValue from Color
 * @phpstan-import-type PercentValue from Color
 * @phpstan-import-type NormalizedHue from Color
 */
class PNG {

	/**
	 * The color helper.
	 *
	 * @since 2.7.0
	 *
	 * @var Color
	 */
	private Color $color;

	/**
	 * Creates a new PNG helper.
	 *
	 * @since 2.7.0
	 *
	 * @param Color $color The color helper.
	 */
	public function __construct( Color $color ) {
		$this->color = $color;
	}

	/**
	 * Creates an image resource of the chosen type.
	 *
	 * @since  2.5.0 Returns a resource or GdImage instance, depending on the PHP version.
	 *
	 * @param  string $type   The type of background to create. Valid: 'white', 'black', 'transparent'.
	 * @param  int    $width  Image width in pixels.
	 * @param  int    $height Image height in pixels.
	 *
	 * @return resource|GdImage
	 *
	 * @throws \InvalidArgumentException Called with an incorrect type.
	 * @throws PNG_Image_Exception       The image could not be created.
	 */
	public function create( $type, $width, $height ) {
		/**
		 * A bitmap image (class or resource).
		 *
		 * @var resource|GdImage $image The created GD image.
		 */
		$image = \imageCreateTrueColor( $width, $height );

		// Something went wrong, badly.
		if ( ! \is_gd_image( $image ) ) {
			throw new PNG_Image_Exception( "The image of type {$type} ($width x $height) could not be created." );  // @codeCoverageIgnore
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
				throw new PNG_Image_Exception( "Error filling image of type $type." ); // @codeCoverageIgnore
			}
		} catch ( PNG_Image_Exception $e ) {
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
	 * @since  2.5.0 Returns a resource or GdImage instance, depending on the PHP version.
	 *
	 * @param  string $file The absolute path to a PNG image file.
	 *
	 * @return resource|GdImage
	 *
	 * @throws PNG_Image_Exception The image could not be read.
	 */
	public function create_from_file( $file ) {
		/**
		 * A bitmap image (class or resource).
		 *
		 * @var resource|GdImage $image The created GD image.
		 */
		$image = @\imageCreateFromPNG( $file );

		// Something went wrong, badly.
		if ( ! \is_gd_image( $image ) ) {
			throw new PNG_Image_Exception( "The PNG image {$file} could not be read." );
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
	 * @since  2.5.0 Parameters $base and $image can now also be instances of GdImage.
	 *
	 * @param  resource|GdImage $base   The avatar image resource.
	 * @param  resource|GdImage $image  The image to be copied onto the base.
	 * @param  int              $width  Image width in pixels.
	 * @param  int              $height Image height in pixels.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException One of the first two parameters was not a valid image resource.
	 * @throws PNG_Image_Exception       The image could not be copied.
	 */
	public function combine( $base, $image, $width, $height ) {

		// Abort if $image is not a valid resource.
		if ( ! \is_gd_image( $base ) || ! \is_gd_image( $image ) ) {
			throw new \InvalidArgumentException( 'Invalid image resource.' );
		}

		// Copy the image to the base.
		$result = \imageCopy( $base, $image, 0, 0, 0, 0, $width, $height );

		// Clean up.
		\imageDestroy( $image );

		// Return copy success status.
		if ( ! $result ) {
			throw new PNG_Image_Exception( 'Error while copying image.' ); // @codeCoverageIgnore
		}
	}

	/**
	 * Fills the given image with a HSL color.
	 *
	 * @since  2.5.0 Parameter $image can now also be a GdImage.
	 *
	 * @param  resource|GdImage $image      The image.
	 * @param  int              $hue        The hue (0-359).
	 * @param  int              $saturation The saturation (0-100).
	 * @param  int              $lightness  The lightness/Luminosity (0-100).
	 * @param  int              $x          The horizontal coordinate.
	 * @param  int              $y          The vertical coordinate.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException Not a valid image resource.
	 * @throws PNG_Image_Exception       The image could not be filled.
	 *
	 * @phpstan-param NormalizedHue $hue
	 * @phpstan-param PercentValue  $saturation
	 * @phpstan-param PercentValue  $lightness
	 */
	public function fill_hsl( $image, $hue, $saturation, $lightness, $x, $y ) {
		// Abort if $image is not a valid resource.
		if ( ! \is_gd_image( $image ) ) {
			throw new \InvalidArgumentException( 'Invalid image resource.' );
		}

		list( $red, $green, $blue ) = $this->color->hsl_to_rgb( $hue, $saturation, $lightness );
		$color                      = \imageColorAllocate( $image, $red, $green, $blue );

		if ( false === $color || ! \imageFill( $image, $x, $y, $color ) ) {
			throw new PNG_Image_Exception( "Error filling image with HSL ({$hue}, {$saturation}, {$lightness})." ); // @codeCoverageIgnore
		}
	}

	/**
	 * Converts a color specified using HSL to its RGB representation.
	 *
	 * @since 2.5.0
	 *
	 * @deprecated 2.7.0 Use Color::hsl_to_rgb instead.
	 *
	 * @param  int $hue        The hue (in degrees, i.e. 0-359).
	 * @param  int $saturation The saturation (in percent, i.e. 0-100).
	 * @param  int $lightness  The lightness (in percent, i.e. 0-100).
	 *
	 * @return int[] {
	 *     The RGB color as a tuple.
	 *
	 *     @type int $red   The red component (0-255).
	 *     @type int $green The green component (0-255).
	 *     @type int $blue  The blue component (0-255).
	 * }
	 *
	 * @phpstan-param  NormalizedHue $hue
	 * @phpstan-param  PercentValue  $saturation
	 * @phpstan-param  PercentValue  $lightness
	 * @phpstan-return array{ 0: RGBValue, 1: RGBValue, 2: RGBValue }
	 */
	public function hsl_to_rgb( $hue, $saturation, $lightness ) {
		\_deprecated_function( __METHOD__, 'Avatar Privacy 2.7.0', 'Avatar_Privacy\Tools\Images\Color::hsl_to_rgb' );

		return $this->color->hsl_to_rgb( $hue, $saturation, $lightness );
	}
}
