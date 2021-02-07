<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

use Avatar_Privacy\Component;
use Avatar_Privacy\Factory;
use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Tools\Template;

/**
 * Handles comment posting in WordPress.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Comments implements Component {

	/**
	 * The name of the checkbox field in the comment form.
	 */
	const CHECKBOX_FIELD_NAME = 'avatar-privacy-use-gravatar';

	/**
	 * The prefix of the comment cookie (COOKIEHASH is added at the end).
	 *
	 * @var string
	 */
	const COOKIE_PREFIX = 'comment_use_gravatar_';

	/**
	 * The core API.
	 *
	 * @since 2.4.0
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author;

	/**
	 * The templating handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.4.0 Parameter $core removed, $comment_author added.
	 *
	 * @param Comment_Author_Fields $comment_author The comment author fields API.
	 * @param Template              $template       The templating handler.
	 */
	public function __construct( Comment_Author_Fields $comment_author, Template $template ) {
		$this->comment_author = $comment_author;
		$this->template       = $template;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize additional plugin hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Add the checkbox to the comment form.
		\add_filter( 'comment_form_fields', [ $this, 'comment_form_fields' ] );

		// Handle the checkbox data upon saving the comment.
		\add_action( 'comment_post', [ $this, 'comment_post' ], 10, 2 );

		// Store gravatar use choice in cookie, if those are enabled in Core.
		if ( \has_action( 'set_comment_cookies', 'wp_set_comment_cookies' ) ) {
			\add_action( 'set_comment_cookies', [ $this, 'set_comment_cookies' ], 10, 3 );
		}
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
		$new_field = $this->get_gravatar_checkbox_markup();

		/**
		 * Filters the insert position for the `use_gravatar` checkbox.
		 *
		 * @since 1.1.0
		 *
		 * @param string[] $position {
		 *     Where to insert the checkbox.
		 *
		 *     @type string $before_or_after Either 'before' or 'after'.
		 *     @type string $insertion_point The index ('url', 'email', etc.) of the field where the checkbox should be inserted.
		 * }
		 */
		list( $before_or_after, $insertion_point ) = \apply_filters( 'avatar_privacy_use_gravatar_position', $this->get_position( $fields ) );

		if ( isset( $fields[ $insertion_point ] ) ) {
			$result = [];
			foreach ( $fields as $key => $value ) {
				if ( $key === $insertion_point ) {
					if ( 'before' === $before_or_after ) {
						$result['use_gravatar'] = $new_field;
						$result[ $key ]         = $value;
					} else {
						$result[ $key ]         = $value;
						$result['use_gravatar'] = $new_field;
					}
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
	 * Determines position for inserting the `use_gravatar` field.
	 *
	 * @since 2.1.0
	 *
	 * @param  string[] $fields The array of comment fields.
	 *
	 * @return string[] $position {
	 *     Where to insert the checkbox.
	 *
	 *     @type string $before_or_after Either 'before' or 'after'.
	 *     @type string $field           The index ('url', 'email', etc.) of the field where the checkbox should be inserted.
	 * }
	 */
	protected function get_position( array $fields ) {
		if ( isset( $fields['cookies'] ) ) {
			// If the `cookies` field exists, add the checkbox just before.
			$before_or_after = 'before';
			$field           = 'cookies';
		} elseif ( isset( $fields['url'] ) ) {
			// Otherwise, if the `url` field exists, add our checkbox after it.
			$before_or_after = 'after';
			$field           = 'url';
		} elseif ( isset( $fields['email'] ) ) {
			// Otherwise, look for the `email` field and add the checkbox after that.
			$before_or_after = 'after';
			$field           = 'email';
		} else {
			// As a last ressort, add the checkbox after all the other fields.
			\end( $fields );
			$before_or_after = 'after';
			$field           = (string) \key( $fields );
		}

		return [ $before_or_after, $field ];
	}

	/**
	 * Retrieves the markup for the use_gravatar checkbox for the comment form.
	 *
	 * @since 2.1.0 Parameter $path removed.
	 *
	 * @return string
	 */
	public static function get_gravatar_checkbox() {
		/**
		 * The share Comments component.
		 *
		 * @var Comments
		 */
		$comments = Factory::get()->create( self::class );

		return $comments->get_gravatar_checkbox_markup();
	}

	/**
	 * Retrieves the markup for the use_gravatar checkbox for the comment form.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $partial Optional. The partial to use. Default 'public/partials/comments/use-gravatar.php'.
	 *
	 * @return string
	 */
	public function get_gravatar_checkbox_markup( $partial = 'public/partials/comments/use-gravatar.php' ) {
		// Determine if the checkbox should be checked.
		$args = [
			'template'   => $this->template,
			'is_checked' => $this->is_gravatar_prechecked(),
		];

		return $this->template->get_partial( $partial, $args );
	}

	/**
	 * Verifies whether the Use Gravatar checkbox should be pre-checked.
	 *
	 * @since  2.4.0
	 *
	 * @global array $_POST    Post request superglobal.
	 * @global array $_COOKIE Cookie superglobal.
	 *
	 * @return bool
	 */
	protected function is_gravatar_prechecked() {
		// Default is unchecked.
		$is_checked = false;

		if ( isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontend form.
			// Re-displaying the comment form with validation errors.
			$is_checked = ! empty( $_POST[ self::CHECKBOX_FIELD_NAME ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontend form.
		} elseif ( isset( $_COOKIE[ self::COOKIE_PREFIX . \COOKIEHASH ] ) ) {
			// Read the value from the cookie, saved with previous comment.
			$is_checked = ! empty( $_COOKIE[ self::COOKIE_PREFIX . \COOKIEHASH ] );
		}

		return $is_checked;
	}

	/**
	 * Saves the value of the 'use gravatar' checkbox from the comment form in
	 * the database, but only for non-spam comments.
	 *
	 * @param int    $comment_id       The ID of the comment that has just been saved.
	 * @param string $comment_approved Whether the comment has been approved (1)
	 *                                 or not (0) or is marked as spam (spam).
	 *
	 * @return void
	 */
	public function comment_post( $comment_id, $comment_approved ) {
		// Don't save anything for spam comments, trackbacks/pingbacks, and registered user's comments.
		if ( 'spam' === $comment_approved ) {
			return;
		}
		$comment = \get_comment( $comment_id );
		if (
			! $comment instanceof \WP_Comment ||
			( '' !== $comment->comment_type && 'comment' !== $comment->comment_type ) ||
			( '' === $comment->comment_author_email )
		) {
			return;
		}

		// Make sure that the e-mail address does not belong to a registered user.
		if ( \get_user_by( 'email', $comment->comment_author_email ) ) {
			// This is either a comment with a fake identity or a user who didn't sign in
			// and rather entered their details manually. Either way, don't save anything.
			return;
		}

		// Save the 'use gravatar' value.
		$use_gravatar = ( isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ self::CHECKBOX_FIELD_NAME ] ) ) ? 1 : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->comment_author->update_gravatar_use( $comment->comment_author_email, $comment_id, $use_gravatar );
	}

	/**
	 * Sets the comment_use_gravatar_ cookie. Based on `wp_set_comment_cookies`.
	 *
	 * @param  \WP_Comment $comment         Comment object.
	 * @param  \WP_User    $user            Comment author's user object. The user may not exist.
	 * @param  bool        $cookies_consent Optional. Comment author's consent to store cookies. Default true.
	 *
	 * @return void
	 */
	public function set_comment_cookies( \WP_Comment $comment, \WP_User $user, $cookies_consent = true ) {
		// If the user already exists, or the user opted out of cookies, don't set cookies.
		if ( $user->exists() ) {
			return;
		}

		if ( false === $cookies_consent ) {
			// Remove any existing cookie.
			\setcookie( self::COOKIE_PREFIX . \COOKIEHASH, '', time() - \YEAR_IN_SECONDS, \COOKIEPATH, \COOKIE_DOMAIN );
			return;
		}

		// Does the author want to use gravatar?
		$use_gravatar = $this->comment_author->allows_gravatar_use( $comment->comment_author_email );

		// Set a cookie for the 'use gravatar' value.
		/** This filter is documented in wp-includes/comment.php */
		$comment_cookie_lifetime = \apply_filters( 'comment_cookie_lifetime', 30000000 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$secure                  = ( 'https' === \wp_parse_url( \home_url(), \PHP_URL_SCHEME ) );
		\setcookie( self::COOKIE_PREFIX . \COOKIEHASH, (string) $use_gravatar, \time() + $comment_cookie_lifetime, \COOKIEPATH, \COOKIE_DOMAIN, $secure );
	}
}
