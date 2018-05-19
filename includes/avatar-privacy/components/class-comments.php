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

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Options;

/**
 * Handles comment posting in WordPress.
 *
 * @since 1.0.0
 */
class Comments implements \Avatar_Privacy\Component {

	/**
	 * The name of the checkbox field in the comment form.
	 */
	const CHECKBOX_FIELD_NAME = 'use_gravatar';

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Creates a new instance.
	 *
	 * @param string $plugin_file The full path to the base plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( Core $core ) {
		$this->core = $core;

		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize additional plugin hooks.
	 */
	public function init() {
		// Add the checkbox to the comment form.
		\add_filter( 'comment_form_fields', [ $this, 'comment_form_fields' ] );

		// Handle the checkbox data upon saving the comment.
		\add_action( 'comment_post', [ $this, 'comment_post' ], 10, 2 );
	}

	/**
	 * Adds the 'use gravatar' checkbox to the comment form. The checkbox value
	 * is read from a cookie if available.
	 *
	 * @param string[] $fields The array of comment fields.
	 *
	 * @return string[] The modified array of comment fields.
	 */
	public function comment_form_fields( $fields ) {
		// Don't change the form if a user is logged-in or the field already exists.
		if ( \is_user_logged_in() || isset( $fields['use_gravatar'] ) ) {
			return $fields;
		}

		// Define the new checkbox field.
		$new_field = self::get_gravatar_checkbox( $this->plugin_file );

		// Either add the new field before the new cookies checkbox, after the
		// email field or at the end of the array.
		if ( isset( $fields['cookies'] ) || isset( $fields['email'] ) ) {
			$result = [];
			foreach ( $fields as $key => $value ) {
				if ( 'cookies' === $key ) {
					$result['use_gravatar'] = $new_field;
					$result[ $key ]         = $value;
				} elseif ( 'email' === $key && ! isset( $fields['cookies'] ) ) {
					$result[ $key ]         = $value;
					$result['use_gravatar'] = $new_field;
				} else {
					$result[ $key ] = $value;
				}
			}
			$fields = $result;
		} else {
			$fields['use_gravatar'] = $new_field;
		}

		return $fields;
	}

	/**
	 * Retrieves the markup for the use_gravatar checkbox for the comment form.
	 *
	 * @param  string $path The path to the main plugin file.
	 *
	 * @return string
	 */
	public static function get_gravatar_checkbox( $path ) {
		// Start output buffering.
		\ob_start();

		// Include the partial.
		require \dirname( $path ) . '/public/partials/comments/use-gravatar.php';

		// Return included markup.
		return \ob_get_clean();
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
		$use_gravatar = ( isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ self::CHECKBOX_FIELD_NAME ] ) ) ? 1 : 0; // WPCS: CSRF ok, Input var okay.
		$this->core->update_comment_author_gravatar_use( $comment->comment_author_email, $comment_id, $use_gravatar );

		// Set a cookie for the 'use gravatar' value.
		$comment_cookie_lifetime = \apply_filters( 'comment_cookie_lifetime', 30000000 );
		$secure                  = ( 'https' === \wp_parse_url( \home_url(), PHP_URL_SCHEME ) );
		\setcookie( 'comment_use_gravatar_' . COOKIEHASH, $use_gravatar, \time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	}
}
