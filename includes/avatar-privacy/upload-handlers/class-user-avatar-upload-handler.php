<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Tools\Images\Image_File;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

/**
 * Handles uploaded user avatars.
 *
 * This implementation has been inspired by Simple Local Avatars (Jake Goldman & 10up).
 *
 * @since 1.0.0
 * @since 2.0.0 Image generation moved to new class Avatar_Handlers\User_Avatar_Handler, the upload handler is now a subclass of Upload_Handler.
 * @since 2.3.0 Obsolete class constants removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-import-type UploadArgs from Upload_Handler
 * @phpstan-import-type FileSlice from Image_File
 * @phpstan-import-type HandleUploadSuccess from Image_File
 * @phpstan-import-type HandleUploadError from Image_File
 *
 * @phpstan-type UploadArgsWithUserID UploadArgs&array{user_id:int}
 */
class User_Avatar_Upload_Handler extends Upload_Handler {

	const UPLOAD_DIR = '/avatar-privacy/user-avatar';

	/**
	 * The user fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private User_Fields $registered_user;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameters $core and $file_cache removed, parameters $image_file
	 *              and $registered_user added.
	 *
	 * @param Image_File  $image_file      The image file handler.
	 * @param User_Fields $registered_user The user fields API.
	 */
	public function __construct( Image_File $image_file, User_Fields $registered_user ) {
		parent::__construct( self::UPLOAD_DIR, $image_file, true );

		$this->registered_user = $registered_user;
	}

	/**
	 * Retrieves the markup for uploading user avatars.
	 *
	 * @deprecated 2.4.0
	 *
	 * @param  \WP_User $user The profile user.
	 *
	 * @return string
	 */
	public function get_avatar_upload_markup( \WP_User $user ) {
		\_deprecated_function( __METHOD__, 'Avatar Privacy 2.4.0' );

		\ob_start();
		require \AVATAR_PRIVACY_PLUGIN_PATH . '/admin/partials/profile/user-avatar-upload.php';
		return (string) \ob_get_clean();
	}

	/**
	 * Stores the uploaded avatar image in the proper directory.
	 *
	 * @global array $_POST  Post request superglobal.
	 * @global array $_FILES Uploaded files superglobal.
	 *
	 * @param  int    $user_id      The user ID.
	 * @param  string $nonce        The nonce root required for saving the field
	 *                              (the user ID will be automatically appended).
	 * @param  string $action       The action required for saving the field.
	 * @param  string $upload_field The HTML name of the "upload" field.
	 * @param  string $erase_field  The HTML name of the "erase" checkbox.
	 *
	 * @return void
	 */
	public function save_uploaded_user_avatar( $user_id, $nonce, $action, $upload_field, $erase_field ) {
		// Prepare arguments.
		$args = [
			'nonce'        => "{$nonce}{$user_id}",
			'action'       => $action,
			'upload_field' => $upload_field,
			'erase_field'  => $erase_field,
			'user_id'      => $user_id,
		];

		$this->maybe_save_data( $args );
	}

	/**
	 * Retrieves the relevant slice of the global $_FILES array.
	 *
	 * @since 2.4.0
	 * @since 2.8.0 Parameter `$files` addded to reduce reliance on $_FILES superglobal.
	 *
	 * @param  mixed[] $files The $_FILES uploaded files superglobal.
	 * @param  array   $args  Arguments passed from ::maybe_save_data().
	 *
	 * @return array          A slice of the $_FILES array.
	 *
	 * @phpstan-param  UploadArgs $args
	 * @phpstan-return FileSlice|array{}
	 */
	protected function get_file_slice( array $files, array $args ): array {
		if ( ! empty( $files[ $args['upload_field'] ] ) ) {
			return (array) $files[ $args['upload_field'] ]; // $_FILES does not need wp_unslash.
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
	 * @param  array   $upload_result The result of ::upload().
	 * @param  mixed[] $args          Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 *
	 * @phpstan-param array{ error?: string } $upload_result
	 */
	protected function handle_upload_errors( array $upload_result, array $args ) {
		if ( empty( $upload_result['error'] ) ) {
			$error_message = \__( 'An unknown error occurred while uploading the avatar', 'avatar-privacy' );
		} elseif ( 'Sorry, this file type is not permitted for security reasons.' === $upload_result['error'] ) {
			$error_message = \__( 'Please upload a valid PNG, GIF or JPEG image for the avatar.', 'avatar-privacy' );
		} else {
			$error_message = \sprintf( '<strong>%s</strong> %s', \__( 'There was an error uploading the avatar:', 'avatar-privacy' ), \esc_attr( $upload_result['error'] ) );
		}

		\add_action( 'user_profile_update_errors', function ( \WP_Error $errors ) use ( $error_message ) {
			$errors->add( 'avatar_error', $error_message ); // @codeCoverageIgnore
		} );
	}

	/**
	 * Stores metadata about the uploaded file.
	 *
	 * @since 2.4.0
	 *
	 * @param  mixed[] $upload_result The result of ::upload().
	 * @param  mixed[] $args          Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 *
	 * @phpstan-param HandleUploadSuccess|HandleUploadError $upload_result
	 * @phpstan-param UploadArgsWithUserID                  $args
	 */
	protected function store_file_data( array $upload_result, array $args ) {
		$this->registered_user->set_uploaded_local_avatar( $args['user_id'], $upload_result );
	}

	/**
	 * Deletes a previously uploaded file and its metadata.
	 *
	 * @since 2.4.0
	 *
	 * @param  mixed[] $args Arguments passed from ::maybe_save_data().
	 *
	 * @return void
	 *
	 * @phpstan-param UploadArgsWithUserID $args
	 */
	protected function delete_file_data( array $args ) {
		$this->registered_user->delete_local_avatar( $args['user_id'] );
	}

	/**
	 * Retrieves the filename to use.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $filename The proposed filename.
	 * @param  array  $args     Arguments passed from ::maybe_save_data().
	 *
	 * @return string
	 *
	 * @phpstan-param UploadArgsWithUserID $args
	 */
	protected function get_filename( $filename, array $args ) {
		return $this->registered_user->get_local_avatar_filename( $args['user_id'], $filename );
	}

	/**
	 * Delete the uploaded avatar (including all cached size variants) for the given user.
	 *
	 * @deprecated 2.4.0 Use \Avatar_Privacy\Core:delete_user_avatar instead.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return void
	 */
	public function delete_uploaded_avatar( $user_id ) {
		\_deprecated_function( __METHOD__, 'Avatar Privacy 2.4.0', 'Avatar_Privacy\Core:delete_user_avatar' );

		$this->registered_user->delete_local_avatar( $user_id );
	}

	/**
	 * Invalidates cached avatar images.
	 *
	 * @since 2.2.0
	 *
	 * @deprecated 2.4.0 Use \Avatar_Privacy\Core::invalidate_user_avatar_cache
	 *                   instead.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return void
	 */
	public function invalidate_user_avatar_cache( $user_id ) {
		\_deprecated_function( __METHOD__, 'Avatar Privacy 2.4.0', 'Avatar_Privacy\Core::invalidate_user_avatar_cache' );

		$this->registered_user->invalidate_local_avatar_cache( $user_id );
	}
}
