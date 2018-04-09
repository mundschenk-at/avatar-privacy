<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Data_Storage\Options;

/**
 * Handles comment posting in WordPress.
 *
 * @since 1.0.0
 */
class Comments implements \Avatar_Privacy\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The core API.
	 *
	 * @var \Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * Creates a new instance.
	 *
	 * @param string         $plugin_file The full path to the base plugin file.
	 * @param Options        $options     The options handler.
	 */
	public function __construct( $plugin_file, Options $options ) {
		$this->plugin_file = $plugin_file;
		$this->options     = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		$this->core = $core;

		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize additional plugin hooks.
	 */
	public function init() {
		// Add the checkbox to the comment form.
		\add_filter( 'comment_form_default_fields', [ $this, 'comment_form_default_fields' ] );

		// Handle the checkbox data upon saving the comment.
		\add_action( 'comment_post', [ $this, 'comment_post' ], 10, 2 );
	}

	/**
	 * Adds the 'use gravatar' checkbox to the comment form. The checkbox value
	 * is read from a cookie if available.
	 *
	 * @param array $fields The array of default comment fields.
	 *
	 * @return array The modified array of comment fields.
	 */
	public function comment_form_default_fields( $fields ) {
		// Don't change the form if a user is logged-in.
		if ( \is_user_logged_in() ) {
			return $fields;
		}

		// Define the new checkbox field.
		$is_checked = false;
		if ( isset( $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ) ) { // WPCS: CSRF ok, Input var okay.
			// Re-displaying the comment form with validation errors.
			$is_checked = ! empty( $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ); // WPCS: CSRF ok, Input var okay.
		} elseif ( isset( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ) ) { // Input var okay.
			// Read the value from the cookie, saved with previous comment.
			$is_checked = ! empty( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ); // Input var okay.
		}
		$new_field = '<p class="comment-form-use-gravatar">'
		. '<input id="' . \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME . '" name="' . \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME . '" type="checkbox" value="true"' . checked( $is_checked, true, false ) . ' " />'
		. '<label for="' . \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME . '">' . sprintf( /* translators: gravatar.com URL */ __( 'Display a <a href="%s">Gravatar</a> image next to my comments.', 'avatar-privacy' ), 'https://gravatar.com' ) . '</label> '
		. '</p>';

		// Either add the new field after the E-Mail field or at the end of the array.
		if ( isset( $fields['email'] ) ) {
			$result = [];
			foreach ( $fields as $key => $value ) {
				$result[ $key ] = $value;
				if ( 'email' === $key ) {
					$result['use_gravatar'] = $new_field;
				}
			}
			$fields = $result;
		} else {
			$fields['use_gravatar'] = $new_field;
		}

		return $fields;
	}

	/**
	 * Saves the value of the 'use gravatar' checkbox from the comment form in
	 * the database, but only for non-spam comments.
	 *
	 * @param string $comment_id       The ID of the comment that has just been saved.
	 * @param string $comment_approved Whether the comment has been approved (1)
	 *                                 or not (0) or is marked as spam (spam).
	 */
	public function comment_post( $comment_id, $comment_approved ) {
		// Don't save anything for spam comments, trackbacks/pingbacks, and registered user's comments.
		if ( 'spam' === $comment_approved ) {
			return;
		}
		$comment = \get_comment( $comment_id );
		if ( ! $comment || ( '' !== $comment->comment_type ) || ( '' === $comment->comment_author_email ) ) {
			return;
		}

		// Make sure that the e-mail address does not belong to a registered user.
		if ( \get_user_by( 'email', $comment->comment_author_email ) ) {
			// This is either a comment with a fake identity or a user who didn't sign in
			// and rather entered their details manually. Either way, don't save anything.
			return;
		}

		// Save the 'use gravatar' value.
		$use_gravatar = ( isset( $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ] ) ) ? '1' : '0'; // WPCS: CSRF ok, Input var okay.
		$this->core->update_comment_author_gravatar_use( $comment->comment_author_email, $comment_id, $use_gravatar );

		// Set a cookie for the 'use gravatar' value.
		$comment_cookie_lifetime = \apply_filters( 'comment_cookie_lifetime', 30000000 );
		$secure                  = ( 'https' === \wp_parse_url( \home_url(), PHP_URL_SCHEME ) );
		\setcookie( 'comment_use_gravatar_' . COOKIEHASH, $use_gravatar, \time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	}
}
