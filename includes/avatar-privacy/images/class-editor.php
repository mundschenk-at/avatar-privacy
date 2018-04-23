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

namespace Avatar_Privacy\Images;

/**
 * A collection of utlitiy methods for using the \WP_Image_Editor class.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Editor {

	/**
	 * Retrieves the image data from the given editor object.
	 *
	 * @param  \WP_Image_Editor|\WP_Error $image  The image.
	 * @param  string                     $format Optional. The image mimetype. Default 'image/png'.
	 *
	 * @return string
	 */
	public static function get_image_data( $image, $format = 'image/png' ) {

		// Check for validity.
		if ( \is_wp_error( $image ) ) {
			return '';
		}

		// Convert the image to PNG format and extract data.
		$result = $image->save( 'php://memory', $format );
		if ( \is_wp_error( $result ) || empty( $result['path'] ) ) {
			return '';
		}

		// Read the data from memory stream.
		$fp = \fopen( $result['path'], 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( false === $fp ) {
			// Something went wrong.
			return '';
		}
		$data = \stream_get_contents( $fp );
		\fclose( $fp );                          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		// Return result.
		return $data;
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

		// Check for validity.
		if ( \is_wp_error( $image ) ) {
			return '';
		}

		// Try to resize image.
		$result = $image->resize( $width, $height, $crop );
		if ( \is_wp_error( $result ) ) {
			return '';
		}

		return self::get_image_data( $image, $format );
	}
}
