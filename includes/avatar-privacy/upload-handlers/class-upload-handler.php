<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

namespace Avatar_Privacy\Upload_Handlers;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

use Avatar_Privacy\Tools\Images\Image_File;

/**
 * Handles image uploads.
 *
 * @since 2.0.0
 * @since 2.4.0 Property $core removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Upload_Handler {

	/**
	 * A mapping of file extension patterns to MIME types.
	 *
	 * @deprecated 2.4.0
	 *
	 * @var string[]
	 */
	const ALLOWED_MIME_TYPES = Image_File::ALLOWED_UPLOAD_MIME_TYPES;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	protected $file_cache;

	/**
	 * The subfolder used for our uploaded files. Has to start with /.
	 *
	 * @var string
	 */
	private $upload_dir;

	/**
	 * The image file handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Image_File
	 */
	private $image_file;

	/**
	 * Whether to use the global upload directory.
	 *
	 * @since 2.4.0
	 *
	 * @var bool
	 */
	private $global_upload;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameter $core removed, parameters $image_File and $global_upload added.
	 *
	 * @param string           $upload_dir    The subfolder used for our uploaded
	 *                                        files. Has to start with /.
	 * @param Filesystem_Cache $file_cache    The file cache handler.
	 * @param Image_File       $image_file    The image file handler.
	 * @param bool             $global_upload Optional. A flag indicating whether
	 *                                        there should be a global upload directory
	 *                                        on multisite. Default false.
	 */
	public function __construct( $upload_dir, Filesystem_Cache $file_cache, Image_File $image_file, $global_upload = false ) {
		$this->upload_dir    = $upload_dir;
		$this->file_cache    = $file_cache;
		$this->image_file    = $image_file;
		$this->global_upload = $global_upload;
	}

	/**
	 * Returns a unique filename.
	 *
	 * @deprecated 2.4.0
	 *
	 * @param string $directory The uploads directory.
	 * @param string $filename  The proposed filename.
	 * @param string $extension The file extension (including leading dot).
	 *
	 * @return string
	 */
	public function get_unique_filename( $directory, $filename, $extension ) {
		$number   = 1;
		$basename = \basename( $filename, $extension );
		$filename = $basename;

		while ( \file_exists( "$directory/{$filename}{$extension}" ) ) {
			$filename = "{$basename}_{$number}";
			$number++;
		}

		return "{$filename}{$extension}";
	}

	/**
	 * Processes the submitted form and saves the upload results if possible.
	 *
	 * @since 2.4.0
	 *
	 * @global array $_POST  Post request superglobal.
	 *
	 * @param  array $args {
	 *     An array of arguments passed to the form processing methods. All of
	 *     the listed arguments are required.
	 *
	 *     @type string $nonce        The nonce.
	 *     @type string $action       The form action.
	 *     @type string $upload_field The upload field name.
	 *     @type string $erase_field  The erase checkbox field name.
	 * }
	 *
	 * @return void
	 */
	protected function maybe_save_data( array $args ) {
		// Check arguments.
		if ( empty( $args['nonce'] ) || empty( $args['action'] ) || empty( $args['upload_field'] ) || empty( $args['erase_field'] ) ) {
			\_doing_it_wrong( __METHOD__, 'Required arguments missing', 'Avatar Privacy 2.4.0' );
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST[ $args['nonce'] ] ) || ! \wp_verify_nonce( \sanitize_key( $_POST[ $args['nonce'] ] ), $args['action'] ) ) {
			return;
		}

		// Verify a file was uploaded.
		$file_slice = $this->get_file_slice( $args );
		if ( ! empty( $file_slice['name'] ) ) {

			// Upload to our custom directory.
			$upload_result = $this->upload( $file_slice, $args );

			// Handle upload failures.
			if ( empty( $upload_result['file'] ) ) {
				$this->handle_upload_errors( $upload_result, $args );
				return; // Abort.
			}

			// Save the new avatar image.
			$this->store_file_data( $upload_result, $args );
		} elseif ( ! empty( $_POST[ $args['erase_field'] ] ) && 'true' === $_POST[ $args['erase_field'] ] ) {
			// Just delete the current avatar.
			$this->delete_file_data( $args );
		}
	}

	/**
	 * Retrieves the relevant slice of the global $_FILES array.
	 *
	 * @since 2.4.0
	 *
	 * @global array $_FILES Uploaded files superglobal.
	 *
	 * @param  array $args   Arguments passed from ::maybe_save_data().
	 *
	 * @return array         A slice of the $_FILES array.
	 */
	abstract protected function get_file_slice( array $args );

	/**
	 * Handles upload errors and prints appropriate notices.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $upload_result The result of ::handle_upload().
	 * @param  array $args          Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 */
	abstract protected function handle_upload_errors( array $upload_result, array $args );

	/**
	 * Stores metadata about the uploaded file.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $upload_result The result of ::handle_upload().
	 * @param  array $args          Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 */
	abstract protected function store_file_data( array $upload_result, array $args );

	/**
	 * Deletes a previously uploaded file and its metadata.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $args Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 */
	abstract protected function delete_file_data( array $args );

	/**
	 * Retrieves the filename to use.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $filename The proposed filename.
	 * @param  array  $args     Arguments passed from ::maybe_save_data().
	 *
	 * @return string
	 */
	protected function get_filename( $filename, array $args ) {
		return $filename;
	}

	/**
	 * Handles the file upload by optionally switching to the primary site of the network.
	 *
	 * @since 2.4.0 Parameter $global replaced with property.
	 *
	 * @param  array $file  A slice of the $_FILES superglobal.
	 * @param  array $args  Arguments passed from ::maybe_save_data().
	 *
	 * @return string[]     Information about the uploaded file.
	 */
	protected function upload( array $file, $args ) {
		// Prepare arguments.
		$overrides = [
			'global_upload' => $this->global_upload,
			'upload_dir'    => $this->upload_dir,
		];

		// Allow for subclass-specific filenames.
		$file['name'] = $this->get_filename( $file['name'], $args );

		return $this->image_file->handle_upload( $file, $overrides );
	}

	/**
	 * Returns a custom upload directory.
	 *
	 * @deprecated 2.4.0
	 *
	 * @param  array $uploads The uploads data.
	 *
	 * @return array
	 */
	public function custom_upload_dir( array $uploads ) {
		\_deprecated_function( __METHOD__, 'Avatar Privacy 2.4.0' );

		$uploads['path']   = \str_replace( $uploads['subdir'], $this->upload_dir, $uploads['path'] );
		$uploads['url']    = \str_replace( $uploads['subdir'], $this->upload_dir, $uploads['url'] );
		$uploads['subdir'] = $this->upload_dir;

		return $uploads;
	}

	/**
	 * Normalizes the sliced $_FILES to be an array indexed by file handle/number.
	 *
	 * @param  array $files_slice  A slice of the $_FILES superglobal.
	 * @return array {
	 *     An array containing one sub-array for each uploaded file.
	 *
	 *     @type array $file {
	 *         An array of properties for an uploaded file.
	 *
	 *         @type string $name    The name of the uploaded file.
	 *         @type string $type    The MIME type.
	 *         @type string tmp_name The path of the temporary file created by the upload.
	 *         @type int    $error   The error code for the upload.
	 *         @type int    $size    The file size in bytes.
	 *     }
	 * }
	 */
	protected function normalize_files_array( array $files_slice ) {
		if ( ! \is_array( $files_slice['name'] ) ) {
			return [ $files_slice ];
		}

		$new   = [];
		$props = \array_keys( $files_slice );
		$files = \array_keys( $files_slice['name'] );

		foreach ( $files as $file ) {
			foreach ( $props as $property ) {
				$new[ $file ][ $property ] = $files_slice[ $property ][ $file ];
			}
		}

		return $new;
	}
}
