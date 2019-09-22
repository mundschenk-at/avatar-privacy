<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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
use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Options;

use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Network\Gravatar_Service;

/**
 * Handles the display of avatars in WordPress.
 *
 * @since 1.0.0
 */
class Avatar_Handling implements \Avatar_Privacy\Component {

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The Gravatar network service.
	 *
	 * @var Gravatar_Service
	 */
	private $gravatar;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.0.0 Parameter $gravatar added.
	 * @since 2.1.0 Parameter $plugin_file removed.
	 *
	 * @param Core             $core        The core API.
	 * @param Options          $options     The options handler.
	 * @param Gravatar_Service $gravatar    The Gravatar network service.
	 */
	public function __construct( Core $core, Options $options, Gravatar_Service $gravatar ) {
		$this->core     = $core;
		$this->options  = $options;
		$this->gravatar = $gravatar;
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
	 */
	public function init() {
		// New default image display: filter the gravatar image upon display.
		\add_filter( 'pre_get_avatar_data', [ $this, 'get_avatar_data' ], 10, 2 );

		// Generate presets from saved settings.
		$this->enable_presets();
	}

	/**
	 * Enables default filters from the user settings.
	 */
	public function enable_presets() {
		$settings = $this->core->get_settings();

		if ( ! empty( $settings[ Settings::GRAVATAR_USE_DEFAULT ] ) ) {
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
		$force_default = ! empty( $args['force_default'] );
		$mimetype      = '';

		// Process the user identifier.
		list( $user_id, $email, $age ) = $this->parse_id_or_email( $id_or_email );

		// Generate the hash.
		$hash = $this->core->get_user_hash( (int) $user_id ) ?: $this->core->get_hash( $email );

		if ( ! $force_default && ! empty( $user_id ) ) {
			// Fetch local avatar from meta and make sure it's properly stzed.
			$args['url'] = $this->get_local_avatar_url( $user_id, $hash, $args['size'] );
			if ( ! empty( $args['url'] ) ) {
				// Great, we have got a local avatar.
				return $args;
			}
		}

		// Prepare filter arguments.
		$filter_args = [
			'default' => $args['default'],
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
		$url = \apply_filters( 'avatar_privacy_default_icon_url', \includes_url( 'images/blank.gif' ), $hash, $args['size'], $filter_args );

		// Maybe display a gravatar.
		if ( ! $force_default && $this->should_show_gravatar( $user_id, $email, $id_or_email, $age, $mimetype ) ) {
			if ( empty( $mimetype ) ) {
				$mimetype = Images\Type::PNG_IMAGE;
			}

			// Prepare filter arguments.
			$filter_args = [
				'user_id'  => $user_id,
				'email'    => $email,
				'rating'   => $args['rating'],
				'mimetype' => $mimetype,
			];

			/**
			 * Filters the Gravatar.com URL for the given e-mail.
			 *
			 * @param  string $url   The fallback default icon URL.
			 * @param  string $hash  The hashed mail address.
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
			$url = \apply_filters( 'avatar_privacy_gravatar_icon_url', $url, $hash, $args['size'], $filter_args );
		}

		$args['url'] = $url;

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
			if ( ! empty( $user ) && 'true' === $user->get( Core::ALLOW_ANONYMOUS_META_KEY ) ) {
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
	 */
	protected function parse_comment( \WP_Comment $comment ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$allowed_comment_types = \apply_filters( 'get_avatar_comment_types', [ 'comment' ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- can be replaced with is_avatar_comment_type() once WordPress 5.1 is released.

		if ( ! empty( $comment->comment_type ) && ! \in_array( $comment->comment_type, (array) $allowed_comment_types, true ) ) {
			return [ false, '', 0 ]; // Abort.
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
		$local_avatar = $this->core->get_user_avatar( $user_id );
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
			$meta_value    = \get_user_meta( $user_id, Core::GRAVATAR_USE_META_KEY, true );
			$show_gravatar = 'true' === $meta_value;
			$use_default   = '' === $meta_value;
		} else {
			// For comments get the value from the plugin's table.
			$show_gravatar = $this->core->comment_author_allows_gravatar_use( $email );

			// Don't use the default policy for spam comments.
			if ( ! $show_gravatar && ( ! $id_or_email instanceof \WP_Comment || ( 'spam' !== $id_or_email->comment_approved && 'trash' !== $id_or_email->comment_approved ) ) ) {
				$use_default = ! $this->core->comment_author_has_gravatar_policy( $email );
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
		}

		return $show_gravatar;
	}
}
