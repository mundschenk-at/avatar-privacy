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

namespace Avatar_Privacy\Upload_Handlers;

use Avatar_Privacy\Core;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

use Avatar_Privacy\Tools\Images as Image_Tools;

/**
 * Handles image uploads.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Upload_Handler {

	const ALLOWED_MIME_TYPES = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
	];

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	protected $plugin_file;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	protected $file_cache;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	protected $core;

	/**
	 * The subfolder used for our uploaded files. Has to start with /.
	 *
	 * @var string
	 */
	private $upload_dir;

	/**
	 * Creates a new instance.
	 *
	 * @param string           $plugin_file The full path to the base plugin file.
	 * @param string           $upload_dir  The subfolder used for our uploaded files. Has to start with /.
	 * @param Core             $core        The core API.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( $plugin_file, $upload_dir, Core $core, Filesystem_Cache $file_cache ) {
		$this->plugin_file = $plugin_file;
		$this->upload_dir  = $upload_dir;
		$this->core        = $core;
		$this->file_cache  = $file_cache;
	}

	/**
	 * Returns a unique filename.
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
	 * Handles the file upload by optionally switching to the primary site of the network.
	 *
	 * @param  array $file   A slice of the $_FILES superglobal.
	 * @param  bool  $global Optional A flag indicating if the upload should be global on a multisite installation. Default false.
	 *
	 * @return string[]     Information about the uploaded file.
	 */
	protected function upload( array $file, $global = false ) {
		// Enable front end support.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php'; // @codeCoverageIgnore
		}

		// Switch to primary site if this should be a global upload.
		if ( $global && \is_multisite() ) {
			\switch_to_blog( \get_network()->site_id );
		}

		// Ensure custom upload directory.
		\add_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );

		// Prepare arguments.
		$args = [
			'mimes'                    => self::ALLOWED_MIME_TYPES,
			'test_form'                => false,
			'unique_filename_callback' => [ $this, 'get_unique_filename' ],
		];

		// Move uploaded file.
		$result = \wp_handle_upload( $file, $args );

		// Restore standard upload directory.
		\remove_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );

		// Switch back to current site.
		if ( $global && \is_multisite() ) {
			\restore_current_blog();
		}

		return $result;
	}

	/**
	 * Returns a custom upload directory.
	 *
	 * @param  array $uploads The uploads data.
	 *
	 * @return array
	 */
	public function custom_upload_dir( array $uploads ) {
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
