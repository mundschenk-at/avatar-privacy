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

namespace Avatar_Privacy;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Handles uploaded user avatars.
 *
 * This implementation has been inspired by Simple Local Avatars (Jake Goldman & 10up).
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Avatar_Upload {

	/**
	 * The nonce action for updating the 'use_gravatar' meta field.
	 */
	const ACTION_UPLOAD = 'avatar_privacy_upload_avatar';

	/**
	 * The nonce used for updating the 'use_gravatar' meta field.
	 */
	const NONCE_UPLOAD = 'avatar_privacy_upload_avatar_nonce_';

	const CHECKBOX_ERASE = 'avatar-privacy-user-avatar-erase';
	const FILE_UPLOAD    = 'avatar-privacy-user-avatar-upload';

	const ALLOWED_MIME_TYPES = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
	];

	const UPLOAD_DIR = '/avatar-privacy/user-avatar';

	const USER_META_KEY = 'avatar_privacy_user_avatar';


	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The ID of the user whose profile is being edited.
	 *
	 * @var int
	 */
	private $user_id_being_edited;

	/**
	 * The uploads base directory.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * The uploads base URL.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Creates a new instance.
	 *
	 * @param string           $plugin_file The full path to the base plugin file.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( $plugin_file, Filesystem_Cache $file_cache ) {
		$this->plugin_file = $plugin_file;
		$this->file_cache  = $file_cache;
	}

	/**
	 * Retrieves the markup for uploading user avatars.
	 *
	 * @param  \WP_User $user The profile user.
	 *
	 * @return string
	 */
	public function get_avatar_upload_markup( \WP_User $user ) {
		\ob_start();
		require \dirname( $this->plugin_file ) . '/admin/partials/profile/user-avatar-upload.php';
		return \ob_get_clean();
	}

	/**
	 * Stores the uploaded avatar image in the proper directory.
	 *
	 * @param  int $user_id The user ID.
	 */
	public function save_uploaded_user_avatar( $user_id ) {
		if ( ! isset( $_POST[ self::NONCE_UPLOAD . $user_id ] ) || ! \wp_verify_nonce( \sanitize_key( $_POST[ self::NONCE_UPLOAD . $user_id ] ), self::ACTION_UPLOAD ) ) { // Input var okay.
			return;
		}

		if ( ! empty( $_FILES[ self::FILE_UPLOAD ]['name'] ) ) { // Input var okay.

			// Enable front end support.
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			// Make user_id known to unique_filename_callback function.
			$this->user_id_being_edited = $user_id;

			// Upload to our custom directory.
			\add_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );
			$avatar = \wp_handle_upload( $_FILES[ self::FILE_UPLOAD ], [ // WPCS: Input var okay. Sanitization ok.
				'mimes'                    => self::ALLOWED_MIME_TYPES,
				'test_form'                => false,
				'unique_filename_callback' => [ $this, 'get_unique_filename' ],
			] );
			\remove_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );

			// Handle upload failures.
			if ( empty( $avatar['file'] ) ) {
				$this->handle_errors( $avatar );
				return; // Abort.
			}

			// Save the new avatar image.
			$this->assign_new_user_avatar( $user_id, $avatar );
		} elseif ( ! empty( $_POST[ self::CHECKBOX_ERASE ] ) && 'true' === $_POST[ self::CHECKBOX_ERASE ] ) { // Input var okay.
			// Just delete the current avatar.
			$this->delete_uploaded_avatar( $user_id );
		}
	}

	/**
	 * Returns a custom upload direcetory for user avatars.
	 *
	 * @param  array $uploads The uplaods data.
	 * @return array
	 */
	public function custom_upload_dir( array $uploads ) {
		$uploads['path']   = \str_replace( $uploads['subdir'], self::UPLOAD_DIR, $uploads['path'] );
		$uploads['url']    = \str_replace( $uploads['subdir'], self::UPLOAD_DIR, $uploads['url'] );
		$uploads['subdir'] = self::UPLOAD_DIR;

		return $uploads;
	}

	/**
	 * Assigns a new user avatar to the given user ID.
	 *
	 * @param  int   $user_id A user ID.
	 * @param  array $avatar  The result of `wp_handle_upload()`.
	 */
	private function assign_new_user_avatar( $user_id, array $avatar ) {
		// Delete old images.
		$this->delete_uploaded_avatar( $user_id );

		// Save user information (overwriting previous).
		\update_user_meta( $user_id, self::USER_META_KEY, $avatar['file'] );
	}

	/**
	 * Handles upload errors and prints appropriate notices.
	 *
	 * @param  array $result The result of \wp_handle_upload().
	 */
	private function handle_errors( array $result ) {
		switch ( $result['error'] ) {
			case 'Sorry, this file type is not permitted for security reasons.':
				\add_action( 'user_profile_update_errors', function( \WP_Error $errors ) {
					$errors->add( 'avatar_error', \__( 'Please upload a valid PNG, GIF or JPEG image for the avatar.', 'avatar-privacy' ) );
				} );
				break;

			default:
				\add_action( 'user_profile_update_errors', function( \WP_Error $errors ) {
					$errors->add( 'avatar_error', \sprintf( '<strong>%s</strong> %s', \__( 'There was an error uploading the avatar: ', 'avatar-privacy' ), \esc_attr( $result['error'] ) ) );
				} );
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
		$user      = \get_user_by( 'id', $this->user_id_being_edited );
		$base_name = \sanitize_file_name( $user->display_name . '_avatar' );
		$filename  = $base_name;
		$number    = 1;

		while ( \file_exists( "$directory/{$filename}{$extension}" ) ) {
			$filename = "{$base_name}_{$number}";
			$number++;
		}

		return "{$filename}{$extension}";
	}

	/**
	 * Delete the uploaded avatar (including all cached size variants) for the given user.
	 *
	 * @param  int $user_id The user ID.
	 */
	public function delete_uploaded_avatar( $user_id ) {
		$hash = \get_user_meta( $user_id, \Avatar_Privacy_Core::EMAIL_HASH_META_KEY, true );

		$this->file_cache->invalidate( 'user', "#/{$hash}-[1-9][0-9]*\.[a-z]{3}\$#" );

		$full = \get_user_meta( $user_id, self::USER_META_KEY, true );

		if ( \unlink( $full ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow
			\delete_user_meta( $user_id, self::USER_META_KEY );
		}
	}

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @param  string               $file    The path to the full-size avatar image.
	 * @param  string               $email   The mail address used to generate the identity hash.
	 * @param  int                  $size    The requested size in pixels.
	 * @param  \Avatar_Privacy_Core $core    The core API.
	 * @param  bool                 $force   Optional. Whether to force the regeneration of the icon. Default false.
	 *
	 * @return string
	 */
	public function get_icon_url( $file, $email, $size, \Avatar_Privacy_Core $core, $force = false ) {
		if ( empty( $this->base_dir ) || empty( $this->base_url ) ) {
			$uploads        = \wp_get_upload_dir();
			$this->base_url = $uploads['baseurl'];
			$this->base_dir = $uploads['basedir'];
		}

		$target = "{$this->file_cache->get_base_dir()}user/{$core->get_hash($email)}-{$size}.png";

		if ( $force || ! \file_exists( $target ) ) {
			$image = \wp_get_image_editor( \str_replace( $this->base_url, $this->base_dir, $file ) );

			if ( ! \is_wp_error( $image ) ) {
				$image->resize( $size, $size, true );
				$resized = $image->save( $target, 'image/png' );

				if ( \is_wp_error( $resized ) ) {
					return '';
				}
			}
		}

		return \str_replace( $this->base_dir, $this->base_url, $target );
	}
}
