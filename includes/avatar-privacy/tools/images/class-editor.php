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

namespace Avatar_Privacy\Tools\Images;

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
		$result = self::get_image_editor( $stream );

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
	 * @since 2.0.5 Parameter $crop has been deprecated.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image      The image.
	 * @param  int                        $width      The width in pixels.
	 * @param  int                        $height     The height in pixels.
	 * @param  bool                       $deprecated Optional. This deprecated argument is ignored. Default false.
	 * @param  string                     $format     Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public static function get_resized_image_data( $image, $width, $height, $deprecated = false, $format = 'image/png' ) {

		// Try to resize only if we haven't been handed an error object.
		if ( $image instanceof \WP_Error ) {
			return '';
		}

		// Retrieve the size of the original image.
		$current = $image->get_size();

		// We need to use the `crop` method because `resize` includes a block against enlarging images.
		if ( $image->crop( 0, 0, $current['width'], $current['height'], $width, $height, false ) instanceof \WP_Error ) {
			$result = '';
		} else {
			$result = self::get_image_data( $image, $format );
		}

		return $result;
	}

	/**
	 * Returns a `\WP_Image_Editor` instance and loads file into it. Preference is
	 * given to stream-capable editor classes (i.e. GD-based ones).
	 *
	 * @param  string $path Path to the file to load.
	 * @param  array  $args Optional. Additional arguments for retrieving the image editor. Default [].
	 *
	 * @return \WP_Image_Editor|\WP_Error
	 */
	public static function get_image_editor( $path, array $args = [] ) {
		// Create image editor instance from path, but strongly prefer GD implementations.
		\add_filter( 'wp_image_editors', [ __CLASS__, 'prefer_gd_image_editor' ], 9999 );
		$result = \wp_get_image_editor( $path, $args );
		\remove_filter( 'wp_image_editors', [ __CLASS__, 'prefer_gd_image_editor' ], 9999 );

		return $result;
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
