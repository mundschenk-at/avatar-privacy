<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Image_Tools;

/**
 * A collection of utlitiy methods for using the \WP_Image_Editor class.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Editor {

	const MEMORY_HANDLE = 'image_editor/dummy/path';
	const STREAM        = Image_Stream::PROTOCOL . '://' . self::MEMORY_HANDLE;

	/**
	 * Creates a \WP_Image_Editor from a given stream wrapper.
	 *
	 * @param  string $stream The image stream wrapper URL.
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public static function create_from_stream( $stream ) {
		// Create image editor instance from stream, but strongly prefer GD implementations.
		\add_filter( 'wp_image_editors', [ __CLASS__, 'prefer_gd_image_editor' ], 9999 );
		$result = \wp_get_image_editor( $stream );
		\remove_filter( 'wp_image_editors', [ __CLASS__, 'prefer_gd_image_editor' ], 9999 );

		// Clean up stream data.
		Image_Stream::delete_handle( Image_Stream::get_handle_from_url( $stream ) );

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
	public static function create_from_string( $data ) {
		// Copy data to stream implementation.
		\file_put_contents( self::STREAM, $data, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents

		return self::create_from_stream( self::STREAM );
	}

	/**
	 * Creates a \WP_Image_Editor from a PHP image resource. The resource is
	 * destroyed on success.
	 *
	 * @param  resource $image Image data.
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public static function create_from_image_resource( $image ) {
		if ( \is_resource( $image ) && \imagepng( $image, self::STREAM ) ) {
			// Clean up resource.
			\imagedestroy( $image );

			// Create editor.
			return self::create_from_stream( self::STREAM );
		}

		return new \WP_Error( 'invalid_image', __( 'Resource is not an image.', 'avatar-privacy' ) );
	}

	/**
	 * Retrieves the image data from the given editor object.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image  The image.
	 * @param  string                     $format Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public static function get_image_data( $image, $format = 'image/png' ) {
		// Needed for PHP 5.6 compatibility.
		$file_extensions = [
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
		];

		// Check for validity.
		if ( $image instanceof \WP_Error || ! isset( $file_extensions[ $format ] ) ) {
			return '';
		}

		// Convert the image the given format and extract data.
		$extension = ".{$file_extensions[ $format ]}";
		if ( $image->save( self::STREAM . $extension, $format ) instanceof \WP_Error ) {
			return '';
		}

		// Read the data from memory stream and clean up.
		return Image_Stream::get_data( self::MEMORY_HANDLE . $extension, true );
	}

	/**
	 * Resizes the given image and returns the image data.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image  The image.
	 * @param  int                        $width  The width in pixels.
	 * @param  int                        $height The height in pixels.
	 * @param  bool                       $crop   Optional. Default false.
	 * @param  string                     $format Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public static function get_resized_image_data( $image, $width, $height, $crop = false, $format = 'image/png' ) {

		// Try to resize only if we haven't been handed an error object.
		\add_filter( 'image_resize_dimensions', [ __CLASS__, 'image_resize_dimensions' ], 10, 6 );
		if ( $image instanceof \WP_Error || $image->resize( $width, $height, $crop ) instanceof \WP_Error ) {
			$result = '';
		} else {
			$result = self::get_image_data( $image, $format );
		}
		\remove_filter( 'image_resize_dimensions', [ __CLASS__, 'image_resize_dimensions' ], 10 );

		return $result;
	}

	/**
	 * Filters and recalculates dimensions and coordinates for a resized image that
	 * fits within a specified width and height. This filter overrides the default
	 * behavior to allow enlarging images.
	 *
	 * Adapted from `image_resize_dimensions()`.
	 *
	 * @param  null|mixed    $null   Whether to preempt output of the resize dimensions.
	 * @param  int           $orig_w Original width in pixels.
	 * @param  int           $orig_h Original height in pixels.
	 * @param  int           $dest_w New width in pixels.
	 * @param  int           $dest_h New height in pixels.
	 * @param  bool|string[] $crop   Optional. Whether to crop image to specified width and height or resize. An array can specify positioning of the crop area. Default false.
	 *
	 * @return false|array        False on failure. Returned array matches parameters for imagecopyresampled().
	 */
	public static function image_resize_dimensions( $null, $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {
		if ( $crop ) {
			// Crop the largest possible portion of the original image that we can size to $dest_w x $dest_h.
			$aspect_ratio = $orig_w / $orig_h;
			$new_w        = \min( $dest_w, $orig_w );
			$new_h        = \min( $dest_h, $orig_h );

			if ( ! $new_w ) {
				$new_w = (int) \round( $new_h * $aspect_ratio );
			}

			if ( ! $new_h ) {
				$new_h = (int) \round( $new_w / $aspect_ratio );
			}

			$size_ratio = \max( $new_w / $orig_w, $new_h / $orig_h );

			$crop_w = \round( $new_w / $size_ratio );
			$crop_h = \round( $new_h / $size_ratio );

			if ( ! \is_array( $crop ) || \count( $crop ) !== 2 ) {
				$crop = [ 'center', 'center' ];
			}

			list( $x, $y ) = $crop;

			if ( 'left' === $x ) {
				$s_x = 0;
			} elseif ( 'right' === $x ) {
				$s_x = $orig_w - $crop_w;
			} else {
				$s_x = \floor( ( $orig_w - $crop_w ) / 2 );
			}

			if ( 'top' === $y ) {
				$s_y = 0;
			} elseif ( 'bottom' === $y ) {
				$s_y = $orig_h - $crop_h;
			} else {
				$s_y = \floor( ( $orig_h - $crop_h ) / 2 );
			}
		} else {
			// Don't crop, just resize using $dest_w x $dest_h as a maximum bounding box.
			$crop_w = $orig_w;
			$crop_h = $orig_h;

			$s_x = 0;
			$s_y = 0;

			list( $new_w, $new_h ) = self::constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}

		// The return array matches the parameters to imagecopyresampled():
		// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h.
		return [ 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h ];
	}

	/**
	 * Calculates the new dimensions for a resampled image. This modified implementation
	 * allows for enlarging images.
	 *
	 * Adapted from `wp_constrain_dimensions()`.
	 *
	 * @param  int $current_width  Current width of the image.
	 * @param  int $current_height Current height of the image.
	 * @param  int $max_width      Optional. Max width in pixels to constrain to. Default 0.
	 * @param  int $max_height     Optional. Max height in pixels to constrain to. Default 0.
	 *
	 * @return int[]               First item is the width, the second item is the height.
	 */
	private static function constrain_dimensions( $current_width, $current_height, $max_width = 0, $max_height = 0 ) {
		if ( ! $max_width && ! $max_height ) {
			return [ $current_width, $current_height ];
		}

		$width_ratio  = 1.0;
		$height_ratio = 1.0;
		$did_width    = false;
		$did_height   = false;

		if ( $max_width > 0 && $current_width > 0 ) {
			$width_ratio = $max_width / $current_width;
			$did_width   = true;
		}

		if ( $max_height > 0 && $current_height > 0 ) {
			$height_ratio = $max_height / $current_height;
			$did_height   = true;
		}

		// Calculate the larger/smaller ratios.
		$smaller_ratio = \min( $width_ratio, $height_ratio );
		$larger_ratio  = \max( $width_ratio, $height_ratio );

		if ( (int) \round( $current_width * $larger_ratio ) > $max_width || (int) \round( $current_height * $larger_ratio ) > $max_height ) {
			// The larger ratio is too big. It would result in an overflow.
			$ratio = $smaller_ratio;
		} else {
			// The larger ratio fits, and is likely to be a more "snug" fit.
			$ratio = $larger_ratio;
		}

		// Very small dimensions may result in 0, 1 should be the minimum.
		$w = \max( 1, (int) \round( $current_width * $ratio ) );
		$h = \max( 1, (int) \round( $current_height * $ratio ) );

		// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
		// We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
		// Thus we look for dimensions that are one pixel shy of the max value and bump them up.
		// Note: $did_width means it is possible $smaller_ratio == $width_ratio.
		if ( $did_width && $w === $max_width - 1 ) {
			$w = $max_width; // Round it up.
		}

		// Note: $did_height means it is possible $smaller_ratio == $height_ratio.
		if ( $did_height && $h === $max_height - 1 ) {
			$h = $max_height; // Round it up.
		}

		return [ $w, $h ];
	}

	/**
	 * Moves Imagick-based image editors to the end of the queue.
	 *
	 * @param  string[] $editors A list of image editor implementation class names.
	 *
	 * @return string[]
	 */
	public static function prefer_gd_image_editor( array $editors ) {
		$preferred_editors = [];
		$imagick_editors   = [];
		foreach ( $editors as $key => $editor ) {
			if ( \preg_match( '/imagick/i', $editor ) ) {
				$imagick_editors[] = $editor;
			} else {
				$preferred_editors[] = $editor;
			}
		}

		return \array_merge( $preferred_editors, $imagick_editors );
	}
}
