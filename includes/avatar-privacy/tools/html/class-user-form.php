<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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

namespace Avatar_Privacy\Tools\HTML;

use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Exceptions\Form_Field_Not_Found_Exception;
use Avatar_Privacy\Exceptions\Invalid_Nonce_Exception;
use Avatar_Privacy\Tools\Template;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler as Upload;

/**
 * An abstraction layer for working with user profile forms, including the
 * upload handling. The form helper instances are normally not shared.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Form {

	/**
	 * The upload handler.
	 *
	 * @var Upload
	 */
	protected $upload;

	/**
	 * The user fields API.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	protected $registered_user;

	/**
	 * The templating handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * The configuration data for the `use_gravatar` checkbox.
	 *
	 * @var array {
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the actual `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 */
	protected $use_gravatar;

	/**
	 * The configuration data for the `allow_anonymous` checkbox.
	 *
	 * @var array {
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the actual `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 */
	protected $allow_anonymous;

	/**
	 * The configuration data for the user avatar uploader.
	 *
	 * @var array {
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the upload `<input>` element.
	 *     @type string $erase   The ID/name of the erase checkbox `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 */
	protected $user_avatar;

	/**
	 * Creates a new form helper instance.
	 *
	 * @since 2.4.0 Parameters $registered_user, and $template added.
	 *
	 * @param Upload      $upload          The upload handler.
	 * @param User_Fields $registered_user The user fields API handler.
	 * @param Template    $template        The templating handler.
	 * @param array       $use_gravatar {
	 *     The configuration data for the `use_gravatar` checkbox.
	 *
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the actual `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 * @param array       $allow_anonymous {
	 *     The configuration data for the `allow_anonymous` checkbox.
	 *
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the actual `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 * @param array       $user_avatar {
	 *     The configuration data for the user avatar uploader.
	 *
	 *     @type string $nonce   The nonce root (the ID of the user in question
	 *                           will be automatically added).
	 *     @type string $action  The nonce action.
	 *     @type string $field   The ID/name of the upload `<input>` element.
	 *     @type string $erase   The ID/name of the erase checkbox `<input>` element.
	 *     @type string $partial The path to the partial template file (relative
	 *                           to the plugin path).
	 * }
	 */
	public function __construct( Upload $upload, User_Fields $registered_user, Template $template, array $use_gravatar, array $allow_anonymous, array $user_avatar ) {
		$this->upload          = $upload;
		$this->registered_user = $registered_user;
		$this->template        = $template;
		$this->use_gravatar    = $use_gravatar;
		$this->allow_anonymous = $allow_anonymous;
		$this->user_avatar     = $user_avatar;
	}

	/**
	 * Prints the markup for the `use_gravatar` checkbox.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type bool $show_description True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return void
	 */
	public function use_gravatar_checkbox( $user_id, array $args = [] ) {
		$this->checkbox( $this->registered_user->allows_gravatar_use( $user_id ), "{$this->use_gravatar['nonce']}{$user_id}", $this->use_gravatar['action'], $this->use_gravatar['field'], $this->use_gravatar['partial'], $args );
	}

	/**
	 * Retrieves the markup for the `use_gravatar` checkbox.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return string
	 */
	public function get_use_gravatar_checkbox( $user_id, array $args = [] ) {
		\ob_start();
		$this->use_gravatar_checkbox( $user_id, $args );
		return (string) \ob_get_clean();
	}

	/**
	 * Prints the markup for the `allow_anonymous` checkbox.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return void
	 */
	public function allow_anonymous_checkbox( $user_id, array $args = [] ) {
		$this->checkbox( $this->registered_user->allows_anonymous_commenting( $user_id ), "{$this->allow_anonymous['nonce']}{$user_id}", $this->allow_anonymous['action'], $this->allow_anonymous['field'], $this->allow_anonymous['partial'], $args );
	}

	/**
	 * Retrieves the markup for the `allow_anonymous` checkbox.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return string
	 */
	public function get_allow_anonymous_checkbox( $user_id, array $args = [] ) {
		\ob_start();
		$this->allow_anonymous_checkbox( $user_id, $args );
		return (string) \ob_get_clean();
	}

	/**
	 * Prints the markup for uploading user avatars.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type int  $avatar_size       The width/height of the avatar preview image (in pixels). Default 96.
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return void
	 */
	public function avatar_uploader( $user_id, array $args = [] ) {
		// Merge default arguments.
		$args = \wp_parse_args( $args, [
			'avatar_size'       => 96,
			'show_descriptions' => true,
		] );

		/**
		 * Filters whether native profile picture uploading is disabled for some
		 * reasone (e.g. because another plugin already provides for that).
		 *
		 * @since 2.3.0
		 *
		 * @param bool $disabled Default false.
		 */
		$uploads_disabled = \apply_filters( 'avatar_privacy_profile_picture_upload_disabled', false );

		// Set up variables used by the included partial.
		$partial_args = [
			'user_id'          => $user_id,
			'nonce'            => "{$this->user_avatar['nonce']}{$user_id}",
			'action'           => $this->user_avatar['action'],
			'upload_field'     => $this->user_avatar['field'],
			'erase_field'      => $this->user_avatar['erase'],
			'current_avatar'   => $this->registered_user->get_local_avatar( $user_id ),
			'uploads_disabled' => $uploads_disabled,
			'can_upload'       => empty( $uploads_disabled ) && \current_user_can( 'upload_files' ),
			'size'             => $args['avatar_size'],
			'show_description' => $args['show_descriptions'],
		];

		// Include partial.
		$this->template->print_partial( $this->user_avatar['partial'], $partial_args );
	}

	/**
	 * Retrieves the markup for uploading user avatars.
	 *
	 * @param  int   $user_id The ID of the user to edit.
	 * @param  array $args {
	 *     Additional arguments for the template.
	 *
	 *     @type int  $avatar_size       The width/height of the avatar preview image (in pixels). Default 96.
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return string
	 */
	public function get_avatar_uploader( $user_id, array $args = [] ) {
		\ob_start();
		$this->avatar_uploader( $user_id, $args );
		return (string) \ob_get_clean();
	}

	/**
	 * Prints checkbox markup, initialized from the given user meta key.
	 *
	 * @since  2.4.0 Parameters $user_id, and $meta_key removed, parameter $value
	 *               added. The parameter $nonce now has to include the variable
	 *               part (user ID).
	 *
	 * @param  bool   $value      Whether the checkbox should be checked, or not.
	 * @param  string $nonce      The nonce root required for saving the field.
	 * @param  string $action     The action required for saving the field.
	 * @param  string $field_name The HTML name of the checkbox field.
	 * @param  string $partial    The relative path to the partial to load.
	 * @param  array  $args {
	 *     Additional arguments for the template.
	 *
	 *     @type bool $show_descriptions True if the long description should be displayed. Default true.
	 * }
	 *
	 * @return void
	 */
	protected function checkbox( $value, $nonce, $action, $field_name, $partial, array $args = [] ) {
		// Merge default arguments.
		$args = \wp_parse_args( $args, [
			'show_descriptions' => true,
		] );

		// Set up variables used by the included partial.
		$partial_args = [
			'nonce'            => $nonce,
			'action'           => $action,
			'field_name'       => $field_name,
			'value'            => $value,
			'show_description' => $args['show_descriptions'],
		];

		// Include partial.
		$this->template->print_partial( $partial, $partial_args );
	}

	/**
	 * Saves the value of the 'use_gravatar' checkbox from the user profile in
	 * the database.
	 *
	 * @param  int $user_id            The ID of the user that has just been saved.
	 *
	 * @return void
	 *
	 * @throws Invalid_Nonce_Exception An exception is thrown when the nonce cannot
	 *                                 be verified.
	 */
	public function save_use_gravatar_checkbox( $user_id ) {
		try {
			$this->registered_user->update_gravatar_use( $user_id, $this->get_submitted_checkbox_value( "{$this->use_gravatar['nonce']}{$user_id}", $this->use_gravatar['action'], $this->use_gravatar['field'] ) );
		} catch ( Form_Field_Not_Found_Exception $e ) {
			// No attempt to set the field (probably no form submitted).
			return;
		}
	}

	/**
	 * Saves the value of the 'allow_anonymous' checkbox from the user profile in
	 * the database.
	 *
	 * @param  int $user_id            The ID of the user that has just been saved.
	 *
	 * @return void
	 *
	 * @throws Invalid_Nonce_Exception An exception is thrown when the nonce cannot
	 *                                 be verified.
	 */
	public function save_allow_anonymous_checkbox( $user_id ) {
		try {
			$this->registered_user->update_anonymous_commenting( $user_id, $this->get_submitted_checkbox_value( "{$this->allow_anonymous['nonce']}{$user_id}", $this->allow_anonymous['action'], $this->allow_anonymous['field'] ) );
		} catch ( Form_Field_Not_Found_Exception $e ) {
			// No attempt to set the field (probably no form submitted).
			return;
		}
	}

	/**
	 * Retrieves the checkbox value to save.
	 *
	 * @since  2.4.0
	 *
	 * @global array $_POST            Post request superglobal.
	 *
	 * @param  string $nonce           The nonce root required for saving the field
	 *                                 (the user ID will be automatically appended).
	 * @param  string $action          The action required for saving the field.
	 * @param  string $field_name      The HTML name of the field to be saved.
	 *
	 * @return bool
	 *
	 * @throws Form_Field_Not_Found_Exception An exception is thrown when the field
	 *                                        does not exist (i.e. no form was submitted).
	 * @throws Invalid_Nonce_Exception        An exception is thrown when the nonce
	 *                                        cannot be verified.
	 */
	protected function get_submitted_checkbox_value( $nonce, $action, $field_name ) {
		if ( ! isset( $_POST[ $field_name ] ) ) {
			throw new Form_Field_Not_Found_Exception( "Form field '{$field_name}' not found." );
		}

		if ( isset( $_POST[ $nonce ] ) && \wp_verify_nonce( \sanitize_key( $_POST[ $nonce ] ), $action ) ) {
			return 'true' === $_POST[ $field_name ];
		}

		throw new Invalid_Nonce_Exception( 'Could not verify checkbox nonce.' );
	}

	/**
	 * Saves the uploaded avatar image to the proper directory.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return void
	 */
	public function save_uploaded_user_avatar( $user_id ) {
		$this->upload->save_uploaded_user_avatar( $user_id, $this->user_avatar['nonce'], $this->user_avatar['action'], $this->user_avatar['field'], $this->user_avatar['erase'] );
	}

	/**
	 * Saves all the custom fields of the user form into the database/the
	 * the filesystem. (The data is ultimately taken from the $_POST and $_FILES
	 * superglobals.)
	 *
	 * If the current user lacks the capability to edit the profile of the given
	 * user ID, the data is not saved.
	 *
	 * @since  2.4.0 The method now throws an exception when a nonce cannot be
	 *               verified.
	 *
	 * @param  int $user_id The ID of the edited user.
	 *
	 * @return void
	 *
	 * @throws Invalid_Nonce_Exception An exception is thrown when a nonce
	 *                                 cannot be verified.
	 */
	public function save( $user_id ) {
		if ( ! \current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$this->save_use_gravatar_checkbox( $user_id );
		$this->save_allow_anonymous_checkbox( $user_id );
		$this->save_uploaded_user_avatar( $user_id );
	}

	/**
	 * Processes a form submission. Currently, it is limited to "self-editing".
	 *
	 * This method should only be hooked for frontend forms (via the `init`
	 * action hook).
	 *
	 * @since  2.4.0 The method now throws an exception when a nonce cannot be
	 *               verified.
	 *
	 * @return void
	 *
	 * @throws Invalid_Nonce_Exception An exception is thrown when a nonce
	 *                                 cannot be verified.
	 */
	public function process_form_submission() {
		// Check that user is logged in.
		$user_id = \get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}

		// Process upload.
		$this->save( $user_id );
	}

	/**
	 * Registers the `process_form_submission` method with the `init` hook, but
	 * makes sure not to do it twice.
	 *
	 * @return void
	 */
	public function register_form_submission() {
		if ( ! \has_action( 'init', [ $this, 'process_form_submission' ] ) ) {
			\add_action( 'init', [ $this, 'process_form_submission' ] );
		}
	}

	/**
	 * Prints the form for the specified user, using the given PHP template.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  int    $user_id The ID of the edited user.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return void
	 */
	public function print_form( $partial, $user_id, array $args = [] ) {
		$this->template->print_partial( $partial, $this->get_partial_arguments( $user_id, $args ) );
	}

	/**
	 * Retrieves the form markup for the specified user, using the given PHP template.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  int    $user_id The ID of the edited user.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return string
	 */
	public function get_form( $partial, $user_id, array $args = [] ) {
		return $this->template->get_partial( $partial, $this->get_partial_arguments( $user_id, $args ) );
	}

	/**
	 * Prepares the arguments array for passing to Template::get_partial and
	 * Template::print_partial.
	 *
	 * @since  2.4.0
	 *
	 * @param  int   $user_id The ID of the edited user.
	 * @param  array $args    Arguments passed to the partial. Only string keys
	 *                        allowed and the keys must be valid variable names.
	 *
	 * @return array
	 */
	protected function get_partial_arguments( $user_id, array $args ) {
		$args['form']    = $this;
		$args['user_id'] = $user_id;

		return $args;
	}
}
