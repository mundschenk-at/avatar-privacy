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

use Avatar_Privacy\Core\Default_Avatars;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Data_Storage\Options;

use Avatar_Privacy\Tools\Images\Image_File;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

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

	const ERROR_FILE          = 'default_avatar_file_error';
	const ERROR_INVALID_IMAGE = 'default_avatar_invalid_image_type';
	const ERROR_OTHER         = 'default_avatar_other_error';
	/**
	 * The default avatars API.
	 *
	 * @since 2.4.0
	 *
	 * @var Default_Avatars
	 */
	private $default_avatars;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameters $core and $file_cache removed, parameters $image_file,
	 *              and $default_avatars added.
	 *
	 * @param Image_File      $image_file      The image file handler.
	 * @param Default_Avatars $default_avatars The default avatars API.
	 * @param Options         $options         The options handler.
	 */
	public function __construct( Image_File $image_file, Default_Avatars $default_avatars, Options $options ) {
		parent::__construct( self::UPLOAD_DIR, $image_file );

		$this->default_avatars = $default_avatars;
		$this->options         = $options;
	}

	/**
	 * Stores the uploaded default icon in the proper directory.
	 *
	 * @global array $_POST  Post request superglobal.
	 * @global array $_FILES Uploaded files superglobal.
	 *
	 * @param  int             $site_id      A site ID.
	 * @param  string|string[] $option_value The option value. Passed by reference.
	 *
	 * @return void
	 */
	public function save_uploaded_default_icon( $site_id, &$option_value ) {
		// Prepare arguments.
		$args = [
			'nonce'        => self::NONCE_UPLOAD . $site_id,
			'action'       => self::ACTION_UPLOAD,
			'upload_field' => Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR,
			'erase_field'  => self::CHECKBOX_ERASE,
			'site_id'      => $site_id,
			'option_value' => &$option_value,
		];

		$this->maybe_save_data( $args );
	}

	/**
	 * Retrieves the relevant slice of the global $_FILES array.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $args Arguments passed from ::maybe_save_data().
	 *
	 * @return array       A slice of the $_FILES array.
	 */
	protected function get_file_slice( array $args ) {
		$upload_index = $this->options->get_name( Settings::OPTION_NAME );

		if ( ! empty( $_FILES[ $upload_index ]['name'] ) ) {
			$normalized_files = $this->normalize_files_array( $_FILES[ $upload_index ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- $_FILES does not need wp_unslash.

			if ( ! empty( $normalized_files[ $args['upload_field'] ] ) ) {
				return $normalized_files[ $args['upload_field'] ];
			}
		}

		return [];
	}

	/**
	 * Handles upload errors and prints appropriate notices.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.4.0 Renamed to handle_upload_errors, parameter $result renamed
	 *              to $upload_result. Parameter $args added.
	 *
	 * @param  array $upload_result The result of ::handle_upload().
	 * @param  array $args          Arguments passed from ::maybe_save_data().
	 */
	protected function handle_upload_errors( array $upload_result, array $args ) {
		switch ( $upload_result['error'] ) {
			case 'Sorry, this file type is not permitted for security reasons.':
				$id      = self::ERROR_INVALID_IMAGE;
				$message = \__( 'Please upload a valid PNG, GIF or JPEG image for the avatar.', 'avatar-privacy' );
				break;

			default:
				$id      = self::ERROR_OTHER;
				$message = \sprintf( '<strong>%s</strong> %s', \__( 'There was an error uploading the avatar: ', 'avatar-privacy' ), \esc_attr( $upload_result['error'] ) );
		}

		$this->raise_settings_error( $id, $message );
	}

	/**
	 * Stores metadata about the uploaded file.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $upload_result The result of ::handle_upload().
	 * @param  array $args          Arguments passed from ::maybe_save_data().
	 */
	protected function store_file_data( array $upload_result, array $args ) {
		// Delete previous image and thumbnails.
		if ( $this->delete_uploaded_icon( $args['site_id'] ) ) {
			// Store new option value.
			$args['option_value'] = $upload_result;
		} else {
			// There was an error deleting the previous image file.
			$this->handle_file_delete_error();
		}
	}

	/**
	 * Deletes a previously uploaded file and its metadata.
	 *
	 * @since  2.4.0
	 *
	 * @param  array $args Arguments passed from ::maybe_save_data().
	 */
	protected function delete_file_data( array $args ) {
		// Delete previous image and thumbnails.
		if ( $this->delete_uploaded_icon( $args['site_id'] ) ) {
			// Store new option value.
			$args['option_value'] = [];
		} else {
			// There was an error deleting the previous image file.
			$this->handle_file_delete_error();
		}
	}

	/**
	 * Retrieves the filename to use.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $filename The proposed filename.
	 * @param  array  $args     Arguments passed from ::maybe_save_data().
	 *
	 * @return string
	 */
	protected function get_filename( $filename, array $args ) {
		return $this->default_avatars->get_custom_default_avatar_filename( $filename );
	}

	/**
	 * Delete the uploaded avatar (including all cached size variants) for the given site.
	 *
	 * @param  int $site_id The site ID.
	 *
	 * @return bool
	 */
	public function delete_uploaded_icon( $site_id ) {
		if ( $this->default_avatars->delete_custom_default_avatar_image_file() ) {
			$this->default_avatars->invalidate_custom_default_avatar_cache( $site_id );

			return true;
		}

		return false;
	}

	/**
	 * Raises an error on the settings page.
	 *
	 * @since  2.4.0
	 *
	 * @internal
	 *
	 * @param  string $id      The error ID.
	 * @param  string $message The error message.
	 * @param  string $type    Optional. The error type. Default 'error'.
	 *
	 * @return void
	 */
	protected function raise_settings_error( $id, $message, $type = 'error' ) {
		\add_settings_error(
			$this->options->get_name( Settings::OPTION_NAME ) . '[' . Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR . ']',
			$id,
			$message,
			$type
		);
	}

	/**
	 * Handles errors during file deletion.
	 *
	 * @since  2.4.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	protected function handle_file_delete_error() {
		$icon = $this->default_avatars->get_custom_default_avatar();
		$this->raise_settings_error( self::ERROR_FILE, \sprintf( '<strong>%s</strong> %s', \__( 'Could not delete avatar image file:', 'avatar-privacy' ), \esc_attr( $icon['file'] ) ) );
	}
}
