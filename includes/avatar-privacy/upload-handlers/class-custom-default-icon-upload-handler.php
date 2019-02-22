<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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
use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;

/**
 * Handles uploaded custom default icons.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Custom_Default_Icon_Upload_Handler extends Upload_Handler {

	/**
	 * The nonce action for updating custom default icons.
	 */
	const ACTION_UPLOAD = 'avatar_privacy_upload_default_icon';

	/**
	 * The nonce used for updating custom default icons.
	 */
	const NONCE_UPLOAD = 'avatar_privacy_upload_default_icon_nonce_';

	const CHECKBOX_ERASE = 'avatar-privacy-custom-default-icon-erase';
	const FILE_UPLOAD    = 'avatar-privacy-custom-default-icon-upload';

	const UPLOAD_DIR = '/avatar-privacy/custom-default';

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new instance.
	 *
	 * @param string           $plugin_file The full path to the base plugin file.
	 * @param Core             $core        The core API.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 * @param Options          $options     The options handler.
	 */
	public function __construct( $plugin_file, Core $core, Filesystem_Cache $file_cache, Options $options ) {
		parent::__construct( $plugin_file, self::UPLOAD_DIR, $core, $file_cache );

		$this->options = $options;
	}

	/**
	 * Stores the uploaded default icon in the proper directory.
	 *
	 * @param  int             $site_id      A site ID.
	 * @param  string|string[] $option_value The option value. Passed by reference.
	 */
	public function save_uploaded_default_icon( $site_id, &$option_value ) {
		if ( ! isset( $_POST[ self::NONCE_UPLOAD . $site_id ] ) || ! \wp_verify_nonce( \sanitize_key( $_POST[ self::NONCE_UPLOAD . $site_id ] ), self::ACTION_UPLOAD ) ) { // Input var okay.
			return;
		}

		$upload_index     = $this->options->get_name( Core::SETTINGS_NAME );
		$normalized_files = ! empty( $_FILES[ $upload_index ]['name'] ) ? $this->normalize_files_array( /* @scrutinizer ignore-type */ \wp_unslash( $_FILES[ $upload_index ] ) ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! empty( $normalized_files[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ]['name'] ) ) {
			// Upload to our custom directory.
			$icon = $this->upload( $normalized_files[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ], false );

			// Handle upload failures.
			if ( empty( $icon['file'] ) ) {
				$this->handle_errors( $icon );
				return; // Abort.
			}

			// Save the new default avatar image.
			$option_value = $this->assign_new_icon( $site_id, $icon );
		} elseif ( ! empty( $_POST[ self::CHECKBOX_ERASE ] ) && 'true' === $_POST[ self::CHECKBOX_ERASE ] ) { // Input var okay.
			// Just delete the current default avatar.
			$this->delete_uploaded_icon( $site_id );
			$option_value = [];
		}
	}

	/**
	 * Assigns a new user avatar to the given user ID.
	 *
	 * @param  int   $site_id A site ID.
	 * @param  array $icon    The result of `wp_handle_upload()`.
	 *
	 * @return array
	 */
	private function assign_new_icon( $site_id, array $icon ) {
		// Delete old images.
		$this->delete_uploaded_icon( $site_id );

		// Save user information (overwriting previous).
		return $icon;
	}

	/**
	 * Handles upload errors and prints appropriate notices.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  array $result The result of \wp_handle_upload().
	 */
	protected function handle_errors( array $result ) {
		$id = $this->options->get_name( Core::SETTINGS_NAME ) . '[' . Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR . ']';
		switch ( $result['error'] ) {
			case 'Sorry, this file type is not permitted for security reasons.':
				\add_settings_error( $id, 'default_avatar_invalid_image_type', \__( 'Please upload a valid PNG, GIF or JPEG image for the avatar.', 'avatar-privacy' ), 'error' );
				break;

			default:
				\add_settings_error( $id, 'default_avatar_other_error', \sprintf( '<strong>%s</strong> %s', \__( 'There was an error uploading the avatar: ', 'avatar-privacy' ), \esc_attr( $result['error'] ) ), 'error' );
		}
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
		$filename = \sanitize_file_name( \htmlspecialchars_decode( /* @scrutinizer ignore-type */ $this->options->get( 'blogname', 'custom-default-icon', true ) ) );

		return parent::get_unique_filename( $directory, $filename, $extension );
	}

	/**
	 * Delete the uploaded avatar (including all cached size variants) for the given site.
	 *
	 * @param  int $site_id The site ID.
	 *
	 * @return bool
	 */
	public function delete_uploaded_icon( $site_id ) {
		$hash = $this->core->get_hash( "custom-default-{$site_id}" );
		$this->file_cache->invalidate( 'custom', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );

		$icon = $this->core->get_settings()[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ];
		if ( ! empty( $icon['file'] ) && \file_exists( $icon['file'] ) && \unlink( $icon['file'] ) ) {
			return true;
		}

		return false;
	}
}
