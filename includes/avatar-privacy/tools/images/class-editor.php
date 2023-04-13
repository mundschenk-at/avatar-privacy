<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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

use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Tools\Images\Image_Stream;

use GdImage; // phpcs:ignore ImportDetection.Imports -- PHP 8.0 compatibility.

/**
 * A utility class providing in-memory \WP_Image_Editor support.
 *
 * @since 1.0.0
 * @since 2.1.0 Made concrete.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Editor {

	const DEFAULT_STREAM = 'avprimg://image_editor/dummy/path';

	/**
	 * Allowed image formats for exporting.
	 *
	 * @internal
	 *
	 * @since 2.4.0
	 *
	 * @var array<string, bool>
	 */
	const ALLOWED_IMAGE_FORMATS = [
		Image_File::JPEG_IMAGE => true,
		Image_File::PNG_IMAGE  => true,
	];

	/**
	 * A stream wrapper URL.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	private string $stream_url;

	/**
	 * The stream implmentation to use.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	private string $stream_class;

	/**
	 * The handle (hostname/path) parsed from the stream URL.
	 *
	 * @var string
	 */
	private string $handle;

	/**
	 * Creates a new image editor helper.
	 *
	 * @since  2.1.0
	 * @since  2.4.0 An exception is thrown when an invalid URL is passed to the method.
	 *
	 * @param string $url          Optional. The stream URL to be used for in-memory-images. Default self::DEFAULT_STREAM.
	 * @param string $stream_class Optional. The stream wrapper class that should be used. Default Image_Stream.
	 *
	 * @throws \InvalidArgumentException Throws an exception if the default stream URL is not valid.
	 */
	public function __construct( $url = self::DEFAULT_STREAM, $stream_class = Image_Stream::class ) {
		$this->stream_url   = $url;
		$this->stream_class = $stream_class;

		// Determine stream URL scheme.
		$scheme = \parse_url( $url, \PHP_URL_SCHEME ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		if ( empty( $scheme ) ) {
			throw new \InvalidArgumentException( "{$url} is not a valid stream URL" );
		}

		// Also save the memory handle.
		$this->handle = $stream_class::get_handle_from_url( $url );

		$stream_class::register( $scheme );
	}

	/**
	 * Creates a \WP_Image_Editor from a given stream wrapper.
	 *
	 * @param  string $stream The image stream wrapper URL.
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public function create_from_stream( $stream ) {
		// Create image editor instance from stream, but strongly prefer GD implementations.
		$result = $this->get_image_editor( $stream );

		// Clean up stream data.
		$this->delete_stream( $stream );

		// Return image editor.
		return $result;
	}

	/**
	 * Creates a \WP_Image_Editor from in-memory image data.
	 *
	 * @param  string $data Image data.
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public function create_from_string( $data ) {
		// Copy data to stream implementation.
		\file_put_contents( $this->stream_url, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return $this->create_from_stream( $this->stream_url );
	}

	/**
	 * Creates a \WP_Image_Editor from a GD image. The image is destroyed on success.
	 *
	 * @since  2.5.0 Parameter $image can now also be a GdImage.
	 *
	 * @param  resource|GdImage $image Image data.
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public function create_from_image_resource( $image ) {
		if ( \is_gd_image( $image ) && \imagePNG( $image, $this->stream_url ) ) {
			// Clean up resource.
			\imageDestroy( $image );

			// Create editor.
			return $this->create_from_stream( $this->stream_url );
		}

		return new \WP_Error( 'invalid_image', \__( 'Resource is not an image.', 'avatar-privacy' ) );
	}

	/**
	 * Retrieves the image data from the given editor object.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image  The image.
	 * @param  string                     $format Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public function get_image_data( $image, $format = Image_File::PNG_IMAGE ) {
		// Check for validity.
		if (
			$image instanceof \WP_Error ||
			! isset( self::ALLOWED_IMAGE_FORMATS[ $format ] ) ||
			! isset( Image_File::FILE_EXTENSION[ $format ] )
		) {
			return '';
		}

		// Convert the image the given format and extract data.
		$extension = Image_File::FILE_EXTENSION[ $format ];
		if ( $image->save( "{$this->stream_url}.{$extension}", $format ) instanceof \WP_Error ) {
			return '';
		}

		// Read the data from memory stream and clean up.
		return $this->stream_class::get_data( "{$this->handle}.{$extension}", true );
	}

	/**
	 * Resizes the given image and returns the image data. If the aspect ratios
	 * differ, the image is center-cropped.
	 *
	 * @since 2.0.5 Parameter $crop has been deprecated.
	 * @since 2.1.0 Parameter $crop has been removed.
	 * @since 2.4.0 Image is cropped if the target aspect ratio differs from the
	 *              original one.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image  The image.
	 * @param  int                        $width  The width in pixels.
	 * @param  int                        $height The height in pixels.
	 * @param  string                     $format Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public function get_resized_image_data( $image, $width, $height, $format = Image_File::PNG_IMAGE ) {

		// Try to resize only if we haven't been handed an error object.
		if ( $image instanceof \WP_Error ) {
			return '';
		}

		// Caculate the crop dimensions.
		$current = $image->get_size();
		$crop    = $this->get_crop_dimensions( $current['width'], $current['height'], $width, $height );

		// We need to use the `crop` method because `resize` includes a block against enlarging images.
		if ( $image->crop( $crop['x'], $crop['y'], $crop['width'], $crop['height'], $width, $height, false ) instanceof \WP_Error ) {
			$result = '';
		} else {
			$result = $this->get_image_data( $image, $format );
		}

		return $result;
	}

	/**
	 * Determines the necessary crop dimensions for moving from the original image
	 * to the destination image.
	 *
	 * @since 2.4.0
	 *
	 * @param  int $orig_w Original image width.
	 * @param  int $orig_h Original image height.
	 * @param  int $dest_w Destination image width.
	 * @param  int $dest_h Destination image height.
	 *
	 * @return array {
	 *     The crop dimensions and coordinates.
	 *
	 *     @type int $x      The X coordinate for the crop.
	 *     @type int $y      The Y coordinate for the crop.
	 *     @type int $width  The width of the crop.
	 *     @type int $height The height of the crop.
	 * }
	 *
	 * @phpstan-return array{ x: int, y: int, width: int, height: int }
	 */
	protected function get_crop_dimensions( $orig_w, $orig_h, $dest_w, $dest_h ) {
		// We crop to the largest rectangle fitting inside the original image
		// with the same aspect ratio as the destination image.
		$factor = \min( $orig_w / $dest_w, $orig_h / $dest_h );

		// Caclulate the crop dimensions.
		$crop_w = \round( $dest_w * $factor );
		$crop_h = \round( $dest_h * $factor );

		// Center the crop.
		$x = \floor( ( $orig_w - $crop_w ) / 2 );
		$y = \floor( ( $orig_h - $crop_h ) / 2 );

		return [
			'x'      => (int) $x,
			'y'      => (int) $y,
			'width'  => (int) $crop_w,
			'height' => (int) $crop_h,
		];
	}

	/**
	 * Returns a `\WP_Image_Editor` instance and loads file into it. Preference is
	 * given to stream-capable editor classes (i.e. GD-based ones).
	 *
	 * @param  string  $path Path to the file to load.
	 * @param  mixed[] $args Optional. Additional arguments for retrieving the image editor. Default [].
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public function get_image_editor( $path, array $args = [] ) {
		// Create image editor instance from path, but strongly prefer GD implementations.
		\add_filter( 'wp_image_editors', [ $this, 'prefer_gd_image_editor' ], 9999 );
		$result = \wp_get_image_editor( $path, $args );
		\remove_filter( 'wp_image_editors', [ $this, 'prefer_gd_image_editor' ], 9999 );

		return $result;
	}

	/**
	 * Moves Imagick-based image editors to the end of the queue.
	 *
	 * @param  string[] $editors A list of image editor implementation class names.
	 *
	 * @return string[]
	 */
	public function prefer_gd_image_editor( array $editors ) {
		$preferred_editors = [];
		$imagick_editors   = [];
		foreach ( $editors as $editor ) {
			if ( \preg_match( '/imagick/i', $editor ) ) {
				$imagick_editors[] = $editor;
			} else {
				$preferred_editors[] = $editor;
			}
		}

		return \array_merge( $preferred_editors, $imagick_editors );
	}

	/**
	 * Retrieves the real MIME type of an image.
	 *
	 * @since 2.3.0
	 *
	 * @param  string $data Image data.
	 *
	 * @return string|false The actual MIME type or false if the type cannot be determined.
	 */
	public function get_mime_type( $data ) {
		// Use custom handle.
		$stream = $this->stream_url . '/mime/type/check';

		// Copy data to stream implementation.
		\file_put_contents( $stream, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Retrieve MIME type.
		$mime = \wp_get_image_mime( $stream );

		// Clean up.
		$this->delete_stream( $stream );

		// Return the MIME type.
		return $mime;
	}

	/**
	 * Deletes the handle/data for the given stream URL.
	 *
	 * @since 2.3.0
	 *
	 * @param  string $stream The image stream wrapper URL.
	 *
	 * @return void
	 */
	protected function delete_stream( $stream ) {
		$this->stream_class::delete_handle( $this->stream_class::get_handle_from_url( $stream ) );
	}
}
