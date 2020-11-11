<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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
 * A utility class for handling image files.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Image_File {
	const JPEG_IMAGE = 'image/jpeg';
	const PNG_IMAGE  = 'image/png';
	const SVG_IMAGE  = 'image/svg+xml';

	const JPEG_EXTENSION     = 'jpg';
	const JPEG_ALT_EXTENSION = 'jpeg';
	const PNG_EXTENSION      = 'png';
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
		self::SVG_IMAGE  => self::SVG_EXTENSION,
	];

	/**
	 * Handles the file upload by optionally switching to the primary site of the network.
	 *
	 * @param  array    $file      A slice of the $_FILES superglobal.
	 * @param  string[] $overrides An associative array of names => values to override
	 *                             default variables. See `wp_handle_uploads` documentation
	 *                             for the full list of available overrides.
	 *
	 * @return string[]            Information about the uploaded file.
	 */
	public function handle_upload( array $file, array $overrides = [] ) {
		// Enable front end support.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/file.php'; // @codeCoverageIgnore
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
		$result = \wp_handle_upload( $file, $overrides );

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
	 * Determines if the upload should use the global upload directory.
	 *
	 * @param  string[] $overrides {
	 *     An associative array of names => values to override default variables.
	 *     See `wp_handle_uploads` documentation for the full list of available
	 *     overrides.
	 *
	 *     @type bool $global_upload Whether to use the global uploads directory on multisite.
	 * }
	 *
	 * @return bool
	 */
	protected function is_global_upload( $overrides ) {
		return ( ! empty( $overrides['global_upload'] ) && \is_multisite() );
	}
}
