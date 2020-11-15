<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Exceptions\Avatar_Comment_Type_Exception;

use Avatar_Privacy\Tools\Images\Image_File;
use Avatar_Privacy\Tools\Network\Gravatar_Service;
use Avatar_Privacy\Tools\Network\Remote_Image_Service;

/**
 * Handles the display of avatars in WordPress.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Avatar_Handling implements Component {

	/**
	 * The settings API.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The user data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var User_Fields
	 */
	private $registered_user;

	/**
	 * The comment author data helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author;

	/**
	 * The Gravatar network service.
	 *
	 * @var Gravatar_Service
	 */
	private $gravatar;

	/**
	 * The remote image network service.
	 *
	 * @var Remote_Image_Service
	 */
	private $remote_images;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.0.0 Parameter $gravatar added.
	 * @since 2.1.0 Parameter $plugin_file removed.
	 * @since 2.3.4 Parameter $remote_images added.
	 * @since 2.4.0 Parameters $settings, $user_fields and $comment_author_fields
	 *              added, unused parameters $core and $options removed.
	 *
	 * @param Settings              $settings              The settings API.
	 * @param User_Fields           $user_fields           User data API.
	 * @param Comment_Author_Fields $comment_author_fields Comment author data API.
	 * @param Gravatar_Service      $gravatar              The Gravatar network service.
	 * @param Remote_Image_Service  $remote_images         The remote images network service.
	 */
	public function __construct( Settings $settings, User_Fields $user_fields, Comment_Author_Fields $comment_author_fields, Gravatar_Service $gravatar, Remote_Image_Service $remote_images ) {
		$this->settings        = $settings;
		$this->registered_user = $user_fields;
		$this->comment_author  = $comment_author_fields;
		$this->gravatar        = $gravatar;
		$this->remote_images   = $remote_images;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Allow remote URLs by default for legacy avatar images. Use priority 9
		// to allow filters with the default priority to override this consistently.
		\add_filter( 'avatar_privacy_allow_remote_avatar_url', '__return_true', 9, 0 );

		// Start handling avatars when all plugins have been loaded and initialized.
		\add_action( 'init', [ $this, 'setup_avatar_filters' ] );

		// Generate presets from saved settings.
		\add_action( 'init', [ $this, 'enable_presets' ] );
	}

	/**
	 * Sets up avatar handling filters.
	 *
	 * @since 2.4.0 Renamed from init().
	 *
	 * @return void
	 */
	public function setup_avatar_filters() {
		/**
		 * Filters the priority used for filtering the `pre_get_avatar_data` hook.
		 *
		 * @since 2.3.4
		 *
		 * @param $priority Default 9999.
		 */
		$priority = \apply_filters( 'avatar_privacy_pre_get_avatar_data_filter_priority', 9999 );

		// New default image display: filter the gravatar image upon display.
		\add_filter( 'pre_get_avatar_data', [ $this, 'get_avatar_data' ], $priority, 2 );
	}

	/**
	 * Enables default filters from the user settings.
	 *
	 * @return void
	 */
	public function enable_presets() {
		if ( ! empty( $this->settings->get( Settings::GRAVATAR_USE_DEFAULT ) ) ) {
			// Use priority 9 to allow filters with the default priority to override this consistently.
			\add_filter( 'avatar_privacy_gravatar_use_default', '__return_true', 9, 0 );
		}
	}

	/**
	 * Before displaying an avatar image, checks that displaying the gravatar
	 * for this e-mail address has been enabled (opted-in). Also, if the option
	 * "Don't publish encrypted e-mail addresses for non-members of Gravatar." is
	 * enabled, the function checks if a gravatar is actually available for the
	 * e-mail address. If not, it displays the default image directly.
	 *
	 * @param  array             $args        Arguments passed to get_avatar_data(), after processing.
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array
	 */
	public function get_avatar_data( $args, $id_or_email ) {
		// Process the user identifier.
		try {
			list( $user_id, $email, $age ) = $this->parse_id_or_email( $id_or_email );
		} catch ( Avatar_Comment_Type_Exception $e ) {
			// The $id_or_email is a comment of a type that should not display an avatar.
			$args['url']          = false;
			$args['found_avatar'] = false;
			return $args;
		}

		// Generate the hash.
		if ( ! empty( $user_id ) ) {
			// Since we are having a non-empty $user_id, we'll always get a hash.
			$hash = (string) $this->registered_user->get_hash( (int) $user_id );
		} else {
			// This might generate hashes for empty email addresses.
			// That's OK in case some plugins want to display avatars for
			// e.g. trackbacks and linkbacks.
			$hash = $this->comment_author->get_hash( $email );
		}

		// We only need to check these if we are not forcing a default icon to be shown.
		if ( empty( $args['force_default'] ) ) {
			if ( ! empty( $user_id ) ) {
				// Uploaded avatars take precedence.
				$url = $this->get_local_avatar_url( $user_id, $hash, $args['size'] );
			}

			if ( empty( $url ) ) {
				// "Sniffed" Gravatar MIME type.
				$mimetype = '';

				// Maybe display a gravatar.
				if ( $this->should_show_gravatar( $user_id, $email, $id_or_email, $age, $mimetype ) ) {
					$url = $this->get_gravatar_url( $user_id, $email, $hash, $args['size'], $args['rating'], $mimetype );
				} elseif ( ! empty( $args['url'] ) && $this->is_valid_image_url( $args['url'] ) ) {
					// Fall back to avatars set by other plugins.
					$url = $this->get_legacy_icon_url( $args['url'], $args['size'] );
				}
			}
		}

		if ( empty( $url ) ) {
			// Nothing so far, use the default icon.
			$url = $this->get_default_icon_url( $hash, $args['default'], $args['size'] );
		}

		// Return found image.
		$args['url']          = $url;
		$args['found_avatar'] = true;

		return $args;
	}

	/**
	 * Determines if we should go for a gravatar.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param int|false         $user_id     A WordPress user ID (or false).
	 * @param string            $email       The email address.
	 * @param int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param int               $age         The seconds since the post or comment was first created, or 0 if $id_or_email was not one of these object types.
	 * @param string            $mimetype    The expected MIME type of the gravatar image (if any). Passed by reference.
	 *
	 * @return bool
	 */
	protected function should_show_gravatar( $user_id, $email, $id_or_email, $age, &$mimetype ) {
		// Find out if the user opted into displaying a gravatar.
		$show_gravatar = $this->determine_gravatar_policy( $user_id, $email, $id_or_email );

		// Check if a gravatar exists for the e-mail address.
		if ( $show_gravatar ) {
			/**
			 * Filters whether we check if opting-in users and commenters actually have a Gravatar.com account.
			 *
			 * @param bool      $enable_check Defaults to true.
			 * @param string    $email        The email address.
			 * @param int|false $user_id      A WordPress user ID (or false).
			 */
			if ( \apply_filters( 'avatar_privacy_enable_gravatar_check', true, $email, $user_id ) ) {
				$mimetype      = $this->gravatar->validate( $email, $age );
				$show_gravatar = ! empty( $mimetype );
			}
		}

		return $show_gravatar;
	}

	/**
	 * Parses e-mail address and/or user ID from $id_or_email.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.3.4 Throws an Avatar_Comment_Type_Exception for invalid comment types.
	 *
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array {
	 *     The tuple `[ $user_id, $email, $age ]`.
	 *
	 *     @type int|false $user_id The WordPress user ID, or `false`.
	 *     @type string    $email   The email address (or the empty string).
	 *     @type int       $age     The seconds since the post or comment was first created,
	 *                              or 0 if `$id_or_email was` not one of these object types.
	 * }
	 *
	 * @throws Avatar_Comment_Type_Exception The function throws an
	 *     `Avatar_Comment_Type_Exception` if `$id_or_email` is an instance of
	 *     `WP_Comment` but its comment type is not one of the allowed avatar
	 *     comment types.
	 */
	protected function parse_id_or_email( $id_or_email ) {
		list( $user_id, $email, $age ) = $this->parse_id_or_email_unfiltered( $id_or_email );

		if ( ! empty( $user_id ) && empty( $email ) ) {
			$user = \get_user_by( 'ID', $user_id );

			// Prevent warnings when a user ID is invalid (e.g. because a user was deleted directly from the database).
			if ( ! empty( $user ) ) {
				$email = $user->user_email;
			} else {
				$user_id = false; // The user ID was invalid.
			}
		} elseif ( empty( $user_id ) && ! empty( $email ) ) {
			// Check if anonymous comments "as user" are allowed.
			$user = \get_user_by( 'email', $email );
			if ( ! empty( $user ) && 'true' === $user->get( User_Fields::ALLOW_ANONYMOUS_META_KEY ) ) {
				$user_id = $user->ID;
			}
		}

		/**
		 * Filters the parsed user ID, email address and "object age".
		 *
		 * @param array $parsed_data {
		 *     The information parsed from $id_or_email.
		 *
		 *     @type int|false $user_id The WordPress user ID, or `false`.
		 *     @type string    $email   The email address (or the empty string).
		 *     @type int       $age     The seconds since the post or comment was first created,
		 *                              or 0 if `$id_or_email was` not one of these object types.
		 * }
		 * @param int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
		 */
		return \apply_filters( 'avatar_privacy_parse_id_or_email', [ $user_id, $email, $age ], $id_or_email );
	}

	/**
	 * Parses e-mail address and/or user ID from $id_or_email without filtering
	 * the result in any way.
	 *
	 * @since 2.3.0
	 * @since 2.3.4 Throws an Avatar_Comment_Type_Exception for invalid comment types.
	 *
	 * @internal
	 *
	 * @param  int|string|object $id_or_email The identity to retrieven an avatar for.
	 *                                        Accepts a user_id, user email, WP_User object,
	 *                                        WP_Post object, or WP_Comment object.
	 *
	 * @return array {
	 *     The tuple `[ $user_id, $email, $age ]`.
	 *
	 *     @type int|false $user_id The WordPress user ID, or `false`.
	 *     @type string    $email   The email address (or the empty string).
	 *     @type int       $age     The seconds since the post or comment was first created,
	 *                              or 0 if $id_or_email was not one of these object types.
	 * }
	 *
	 * @throws Avatar_Comment_Type_Exception The function throws an
	 *     `Avatar_Comment_Type_Exception` if `$id_or_email` is an instance of
	 *     `WP_Comment` but its comment type is not one of the allowed avatar
	 *     comment types.
	 */
	protected function parse_id_or_email_unfiltered( $id_or_email ) {
		$user_id = false;
		$email   = '';
		$age     = 0;

		if ( \is_numeric( $id_or_email ) ) {
			$user_id = \absint( $id_or_email );
		} elseif ( \is_string( $id_or_email ) ) {
			// E-mail address.
			$email = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_User ) {
			// User object.
			$user_id = $id_or_email->ID;
			$email   = $id_or_email->user_email;
		} elseif ( $id_or_email instanceof \WP_Post ) {
			// Post object.
			$user_id = (int) $id_or_email->post_author;
			$age     = $this->get_age( $id_or_email->post_date_gmt );
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			return $this->parse_comment( $id_or_email );
		}

		return [ $user_id, $email, $age ];
	}

	/**
	 * Parse a WP_Comment object.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 * @since 2.3.4 Throws an Avatar_Comment_Type_Exception for invalid comment types.
	 *
	 * @param  \WP_Comment $comment A comment.
	 *
	 * @return array {
	 *     The information parsed from $id_or_email.
	 *
	 *     @type int|false $user_id The WordPress user ID, or false.
	 *     @type string    $email   The email address.
	 *     @type int       $age     The seconds since the post or comment was first created, or 0 if $id_or_email was not one of these object types.
	 * }
	 *
	 * @throws Avatar_Comment_Type_Exception The function throws an
	 *     `Avatar_Comment_Type_Exception` if the comment type of `$comment` is
	 *     not one of the allowed avatar comment types.
	 */
	protected function parse_comment( \WP_Comment $comment ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$allowed_comment_types = \apply_filters( 'get_avatar_comment_types', [ 'comment' ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- can be replaced with is_avatar_comment_type() once WordPress 5.1 is released.

		if ( ! \in_array( \get_comment_type( $comment ), (array) $allowed_comment_types, true ) ) {
			// Abort.
			throw new Avatar_Comment_Type_Exception();
		}

		$user_id = false;
		$email   = '';
		$age     = $this->get_age( $comment->comment_date_gmt );
		if ( ! empty( $comment->user_id ) ) {
			$user_id = (int) $comment->user_id;
		} elseif ( ! empty( $comment->comment_author_email ) ) {
			$email = $comment->comment_author_email;
		}

		return [ $user_id, $email, $age ];
	}

	/**
	 * Calculates the age (seconds before now) from a GMT-based date/time string.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $date_gmt A date/time string in the GMT time zone.
	 *
	 * @return int              The age in seconds.
	 */
	protected function get_age( $date_gmt ) {
		return \time() - \mysql2date( 'U', $date_gmt );
	}

	/**
	 * Retrieves a URL pointing to the local avatar image of the appropriate size.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  int    $user_id The user ID.
	 * @param  string $hash    The hashed mail address.
	 * @param  int    $size    The requested avatar size in pixels.
	 *
	 * @return string          The URL, or '' if no local avatar has been set.
	 */
	protected function get_local_avatar_url( $user_id, $hash, $size ) {
		// Bail if we haven't got a valid user ID.
		if ( empty( $user_id ) ) {
			return '';
		}

		// Fetch local avatar from meta and make sure it's properly stzed.
		$url          = '';
		$local_avatar = $this->registered_user->get_local_avatar( $user_id );
		if ( ! empty( $local_avatar['file'] ) && ! empty( $local_avatar['type'] ) ) {
			// Prepare filter arguments.
			$args = [
				'user_id'  => $user_id,
				'avatar'   => $local_avatar['file'],
				'mimetype' => $local_avatar['type'],
			];

			/**
			 * Filters the uploaded avatar URL for the given user.
			 *
			 * @param  string $url   The URL. Default empty.
			 * @param  string $hash  The hashed mail address.
			 * @param  int    $size  The size of the avatar image in pixels.
			 * @param  array  $args {
			 *     An array of arguments.
			 *
			 *     @type int    $user_id  A WordPress user ID.
			 *     @type string $avatar   The full-size avatar image path.
			 *     @type string $mimetype The expected MIME type of the avatar image.
			 * }
			 */
			$url = \apply_filters( 'avatar_privacy_user_avatar_icon_url', '', $hash, $size, $args );
		}

		return $url;
	}

	/**
	 * Retrieves the default icon URL for the given hash.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $hash    The hashed mail address.
	 * @param  string $default The default icon type.
	 * @param  int    $size    The size of the avatar image in pixels.
	 *
	 * @return string
	 */
	protected function get_default_icon_url( $hash, $default, $size ) {
		// Prepare filter arguments.
		$args = [
			'default' => $default,
		];

		/**
		 * Filters the default icon URL for the given e-mail.
		 *
		 * @param  string $url   The fallback icon URL (a blank GIF).
		 * @param  string $hash  The hashed mail address.
		 * @param  int    $size  The size of the avatar image in pixels.
		 * @param  array  $args {
		 *     An array of arguments.
		 *
		 *     @type string $default The default icon type.
		 * }
		 */
		return \apply_filters( 'avatar_privacy_default_icon_url', \includes_url( 'images/blank.gif' ), $hash, $size, $args );
	}

	/**
	 * Determines the gravatar use policy.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  int|false         $user_id     A WordPress user ID (or false).
	 * @param  string            $email       The email address.
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Can be a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return bool
	 */
	protected function determine_gravatar_policy( $user_id, $email, $id_or_email ) {
		$show_gravatar = false;
		$use_default   = false;

		if ( ! empty( $user_id ) ) {
			// For users get the value from the usermeta table.
			$meta_value    = \get_user_meta( $user_id, User_Fields::GRAVATAR_USE_META_KEY, true );
			$show_gravatar = 'true' === $meta_value;
			$use_default   = '' === $meta_value;
		} else {
			// For comments get the value from the plugin's table.
			$show_gravatar = $this->comment_author->allows_gravatar_use( $email );

			// Don't use the default policy for spam comments.
			if ( ! $show_gravatar && ( ! $id_or_email instanceof \WP_Comment || ( 'spam' !== $id_or_email->comment_approved && 'trash' !== $id_or_email->comment_approved ) ) ) {
				$use_default = ! $this->comment_author->has_gravatar_policy( $email );
			}
		}

		if ( $use_default ) {
			/**
			 * Filters the default policy for showing gravatars.
			 *
			 * The result only applies if a user or comment author has not
			 * explicitely set a value for `use_gravatar` (i.e. for comments
			 * created  before the plugin was installed).
			 *
			 * @param  bool              $show        Default false.
			 * @param  int|string|object $id_or_email The Gravatar to retrieve. Can be a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
			 */
			$show_gravatar = \apply_filters( 'avatar_privacy_gravatar_use_default', false, $id_or_email );

			if ( $show_gravatar && empty( $user_id ) ) {
				// Create only the hash so that the gravatar can be regenerated.
				$this->comment_author->update_hash( $email );
			}
		}

		return $show_gravatar;
	}

	/**
	 * Retrieves the Gravatar.com URL for the given e-mail.
	 *
	 * @since 2.4.0
	 *
	 * @param  int|false $user_id  A WordPress user ID (or false).
	 * @param  string    $email    The mail address used to generate the identity hash.
	 * @param  string    $hash     The hashed e-mail address.
	 * @param  int       $size     The size of the avatar image in pixels.
	 * @param  string    $rating   The audience rating (e.g. 'g', 'pg', 'r', 'x').
	 * @param  string    $mimetype The expected MIME type of the Gravatar image.
	 *
	 * @return string
	 */
	protected function get_gravatar_url( $user_id, $email, $hash, $size, $rating, $mimetype = null ) {
		// Prepare filter arguments.
		$args = [
			'user_id'  => $user_id,
			'email'    => $email,
			'rating'   => $rating,
			'mimetype' => empty( $mimetype ) ? Image_File::PNG_IMAGE : $mimetype,
		];

		/**
		 * Filters the Gravatar.com URL for the given e-mail.
		 *
		 * @param  string $url   The fallback default icon URL (or '').
		 * @param  string $hash  The hashed e-mail address.
		 * @param  int    $size  The size of the avatar image in pixels.
		 * @param  array  $args {
		 *     An array of arguments.
		 *
		 *     @type int|false $user_id  A WordPress user ID (or false).
		 *     @type string    $email    The mail address used to generate the identity hash.
		 *     @type string    $rating   The audience rating (e.g. 'g', 'pg', 'r', 'x').
		 *     @type string    $mimetype The expected MIME type of the Gravatar image.
		 * }
		 */
		return \apply_filters( 'avatar_privacy_gravatar_icon_url', '', $hash, $size, $args );
	}

	/**
	 * Checks if an image URL is valid to use as a fallback avatar icon.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $url The image URL.
	 *
	 * @return bool
	 */
	protected function is_valid_image_url( $url ) {
		return ( ! \strpos( $url, 'gravatar.com' ) && $this->remote_images->validate_image_url( $url, 'avatar' ) );
	}

	/**
	 * Retrieves a URL pointing to the legacy icon scaled to the appropriate size.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $url  A valid image URL.
	 * @param  int    $size The size of the avatar image in pixels.
	 *
	 * @return string
	 */
	protected function get_legacy_icon_url( $url, $size ) {
		// Prepare filter arguments.
		$hash = $this->remote_images->get_hash( $url );
		$args = [];

		/**
		 * Filters the legacy icon URL.
		 *
		 * @since 2.4.0
		 *
		 * @param  string $url   The legacy image URL.
		 * @param  string $hash  The hashed URL.
		 * @param  int    $size  The size of the avatar image in pixels.
		 * @param  array  $args {
		 *     An array of arguments. Currently unused.
		 * }
		 */
		return \apply_filters( 'avatar_privacy_legacy_icon_url', $url, $hash, $size, $args );
	}
}
