<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2024 Peter Putzer.
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

use Avatar_Privacy\Exceptions\Upload_Handling_Exception;

use function Avatar_Privacy\Tools\delete_file;

/**
 * A utility class for handling image files.
 *
 * @internal
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type HandleUploadOverrides array{ upload_dir: string, upload_error_handler?: callable, unique_filename_callback?: callable, upload_error_strings?: string[], test_form?: bool, test_size?: bool, test_type?: bool, mimes?: string[]}
 * @phpstan-type HandleUploadSuccess array{ file: string, url: string, type: string }
 * @phpstan-type HandleUploadError array{ error: string }
 * @phpstan-type FileSlice array{ name: string, type: string, tmp_name: string, error: int|string, size: int }
 */
class Image_File {
	const JPEG_IMAGE = 'image/jpeg';
	const PNG_IMAGE  = 'image/png';
	const GIF_IMAGE  = 'image/gif';
	const SVG_IMAGE  = 'image/svg+xml';

	const JPEG_EXTENSION     = 'jpg';
	const JPEG_ALT_EXTENSION = 'jpeg';
	const PNG_EXTENSION      = 'png';
	const GIF_EXTENSION      = 'gif';
	const SVG_EXTENSION      = 'svg';

	const CONTENT_TYPE = [
		self::JPEG_EXTENSION     => self::JPEG_IMAGE,
		self::JPEG_ALT_EXTENSION => self::JPEG_IMAGE,
		self::PNG_EXTENSION      => self::PNG_IMAGE,
		self::SVG_EXTENSION      => self::SVG_IMAGE,
	];

	const FILE_EXTENSION = [
		self::JPEG_IMAGE => self::JPEG_EXTENSION,
		self::PNG_IMAGE  => self::PNG_EXTENSION,
		self::GIF_IMAGE  => self::GIF_EXTENSION,
		self::SVG_IMAGE  => self::SVG_EXTENSION,
	];

	const ALLOWED_UPLOAD_MIME_TYPES = [
		'jpg|jpeg|jpe' => self::JPEG_IMAGE,
		'gif'          => self::GIF_IMAGE,
		'png'          => self::PNG_IMAGE,
	];

	/**
	 * Handles the file upload by optionally switching to the primary site of the network.
	 *
	 * @since  4.4.0 Default value for parameter `$overrides` removed (as it would be invalid).
	 *
	 * @param  array $file      A slice of the $_FILES superglobal.
	 * @param  array $overrides An associative array of names => values to override
	 *                          default variables. See `wp_handle_uploads` documentation
	 *                          for the full list of available overrides.
	 *
	 * @return string[]         Information about the uploaded file.
	 *
	 * @phpstan-param  FileSlice      $file
	 * @phpstan-param  HandleUploadOverrides $overrides
	 * @phpstan-return HandleUploadSuccess|HandleUploadError
	 */
	public function handle_upload( array $file, array $overrides ) {
		// Enable front end support.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			// @phpstan-ignore requireOnce.fileNotFound
			require_once \ABSPATH . 'wp-admin/includes/file.php'; // @codeCoverageIgnore
		}

		}

		// Switch to primary site if this should be a global upload.
		$use_global_upload_dir = $this->is_global_upload( $overrides );
		if ( $use_global_upload_dir ) {
			\switch_to_blog( \get_main_site_id() );
		}

		// Ensure custom upload directory.
		$upload_dir        = $overrides['upload_dir'];
		$upload_dir_filter = function( array $uploads ) use ( $upload_dir ) {
			// @codeCoverageIgnoreStart
			$uploads['path']   = \str_replace( $uploads['subdir'], $upload_dir, $uploads['path'] );
			$uploads['url']    = \str_replace( $uploads['subdir'], $upload_dir, $uploads['url'] );
			$uploads['subdir'] = $upload_dir;

			return $uploads;
			// @codeCoverageIgnoreEnd
		};

		\add_filter( 'upload_dir', $upload_dir_filter );

		// Move uploaded file.

		/**
		 * Check if the image size falls within the allowed minimum and maximum dimensions.
		 *
		 * @phpstan-var FileSlice
		 */
		$file = $this->validate_image_size( $file );

		/**
		 * Let WordPress handle the upload natively.
		 *
		 * @phpstan-var HandleUploadSuccess|HandleUploadError
		 */
		$result = \wp_handle_upload( $file, $this->prepare_overrides( $overrides ) );

		// Restore standard upload directory.
		\remove_filter( 'upload_dir', $upload_dir_filter );

		// Ensure normalized path on Windows.
		if ( ! empty( $result['file'] ) ) {
			$result['file'] = \wp_normalize_path( $result['file'] );
		}

		// Switch back to current site.
		if ( $use_global_upload_dir ) {
			\restore_current_blog();
		}

		return $result;
	}

	/**
	 * Handles the file upload by optionally switching to the primary site of the network.
	 *
	 * @since  4.4.0 Default value for parameter `$overrides` removed (as it would be invalid).
	 *
	 * @param  string $image_url The image file to sideload.
	 * @param  array  $overrides An associative array of names => values to override
	 *                           default variables. See `wp_handle_uploads` documentation
	 *                           for the full list of available overrides.
	 *
	 * @return string[]          Information about the sideloaded file.
	 *
	 * @throws Upload_Handling_Exception The method throws a `RuntimeException`
	 *                                   when an error is returned by `::handle_upload()`
	 *                                   or the image file could not be copied.
	 *
	 * @phpstan-param  HandleUploadOverrides $overrides
	 * @phpstan-return HandleUploadSuccess|HandleUploadError
	 */
	public function handle_sideload( $image_url, array $overrides ) {
		// Enable front end support.
		if ( ! function_exists( 'wp_tempnam' ) ) {
			// @phpstan-ignore requireOnce.fileNotFound
			require_once \ABSPATH . 'wp-admin/includes/file.php'; // @codeCoverageIgnore
		}

		// Save the file.
		$temp_file = \wp_tempnam( $image_url );
		if ( ! @\copy( $image_url, $temp_file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- We throw our own exception.
			throw new Upload_Handling_Exception( "Error copying $image_url to $temp_file." );
		}

		// Prepare file data.
		$file_data = [
			'name'     => $image_url,
			'type'     => '', // No need to determine the MIME type here as it is untrusted anyway.
			'tmp_name' => $temp_file,
			'error'    => \UPLOAD_ERR_OK,
			'size'     => (int) @\filesize( $temp_file ),
		];

		// Optionally override target filename.
		if ( ! empty( $overrides['filename'] ) ) {
			$file_data['name'] = $overrides['filename'];
		}

		// Use a custom action if none is set.
		if ( empty( $overrides['action'] ) ) {
			$overrides['action'] = 'avatar_privacy_sideload';
		}

		// Now, sideload it in.
		$sideloaded = $this->handle_upload( $file_data, $overrides );

		if ( ! empty( $sideloaded['error'] ) ) {
			// Delete temporary file.
			delete_file( $temp_file );

			// Signal error.
			throw new Upload_Handling_Exception( $sideloaded['error'] );
		}

		return $sideloaded;
	}

	/**
	 * Determines if the upload should use the global upload directory.
	 *
	 * @param  array $overrides {
	 *     An associative array of names => values to override default variables.
	 *     See `wp_handle_uploads` documentation for the full list of available
	 *     overrides.
	 *
	 *     @type bool $global_upload Whether to use the global uploads directory on multisite.
	 * }
	 *
	 * @return bool
	 *
	 * @phpstan-param array{ global_upload?: bool } $overrides
	 */
	protected function is_global_upload( $overrides ) {
		return ( ! empty( $overrides['global_upload'] ) && \is_multisite() );
	}

	/**
	 * Prepares the overrides array for `wp_handle_upload()`.
	 *
	 * @param  mixed[] $overrides An associative array of names => values to override
	 *                            default variables. See `wp_handle_uploads` documentation
	 *                            for the full list of available overrides.
	 *
	 * @return array{ mimes: array<string,string>, action: string, test_form: bool }
	 */
	protected function prepare_overrides( array $overrides ) {
		$defaults = [
			'mimes'     => self::ALLOWED_UPLOAD_MIME_TYPES,
			'action'    => 'avatar_privacy_upload',
			'test_form' => false,
		];

		/**
		 * Ensure that all necessary overrides have a default value.
		 *
		 * @phpstan-var array{ mimes: array<string,string>, action: string, test_form: bool }
		 */
		return \wp_parse_args( $overrides, $defaults );
	}

	/**
	 * Validates the image dimensions before uploading the file.
	 *
	 * @since 2.6.0
	 *
	 * @param  array $file {
	 *     Reference to a single element from $_FILES.
	 *
	 *     @type string $name     The original name of the file on the client machine.
	 *     @type string $type     The MIME type of the file, if the browser provided this information.
	 *     @type string $tmp_name The temporary filename of the file in which the uploaded file was stored on the server.
	 *     @type int    $size     The size, in bytes, of the uploaded file.
	 *     @type int    $error    The error code associated with this file upload.
	 * }
	 *
	 * @return array The filtered $file array. The `error` key will be set to a
	 *               string containing the error message if the image dimensions
	 *               don't match the set limits.
	 *
	 * @phpstan-param  FileSlice $file
	 * @phpstan-return FileSlice
	 */
	public function validate_image_size( array $file ) {
		$image_size = @\getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- prevent additional errors if the file cannot be read.
		if ( ! $image_size || empty( $image_size[0] ) || empty( $image_size[1] ) ) {
			/* translators: uploaded image file name */
			$file['error'] = \sprintf( \__( 'Error reading dimensions of image file %s.', 'avatar-privacy' ), $file['tmp_name'] );
		} else {
			$image_width  = $image_size[0];
			$image_height = $image_size[1];

			/**
			 * Filters the minimum width for uploaded images.
			 *
			 * @since 2.6.0
			 *
			 * @param int $min_width The minimum width in pixels. Default 0.
			 */
			$min_width = \apply_filters( 'avatar_privacy_upload_min_width', 0 );

			/**
			 * Filters the minimum height for uploaded images.
			 *
			 * @since 2.6.0
			 *
			 * @param int $min_height The minimum width in pixels. Default 0.
			 */
			$min_height = \apply_filters( 'avatar_privacy_upload_min_height', 0 );

			/**
			 * Filters the maximum width for uploaded images.
			 *
			 * @since 2.6.0
			 *
			 * @param int $max_width The maximum width in pixels. Default 2000.
			 */
			$max_width = \apply_filters( 'avatar_privacy_upload_max_width', 2000 );

			/**
			 * Filters the maximum height for uploaded images.
			 *
			 * @since 2.6.0
			 *
			 * @param int $max_height The maximum height in pixels. Default 2000.
			 */
			$max_height = \apply_filters( 'avatar_privacy_upload_max_height', 2000 );

			if ( $image_width < $min_width || $image_height < $min_height ) {
				$file['error'] = \sprintf(
					/* translators: 1: minimum upload width, 2: minimum upload height, 3: actual image width, 4: actual image height */
					\__( 'Image dimensions are too small. Minimum size is %1$d×%2$d pixels. Uploaded image is %3$d×%4$d pixels.', 'avatar-privacy' ),
					$min_width,
					$min_height,
					$image_width,
					$image_height
				);
			} elseif ( $image_width > $max_width || $image_height > $max_height ) {
				$file['error'] = \sprintf(
					/* translators: 1: maximum upload width, 2: maximum upload height, 3: actual image width, 4: actual image height */
					\__( 'Image dimensions are too large. Maximum size is %1$d×%2$d pixels. Uploaded image is %3$d×%4$d pixels.', 'avatar-privacy' ),
					$max_width,
					$max_height,
					$image_width,
					$image_height
				);
			}
		}

		return $file;
	}
}
