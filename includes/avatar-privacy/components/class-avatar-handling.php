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

use Avatar_Privacy\User_Avatar_Upload;

use Avatar_Privacy\Components\Images;

use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;

/**
 * Handles the display of avatars in WordPress.
 *
 * @since 1.0.0
 */
class Avatar_Handling implements \Avatar_Privacy\Component {

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
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The core API.
	 *
	 * @var \Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * A cache for the results of the validate_gravatar function.
	 *
	 * @var array
	 */
	private $validate_gravatar_cache = [];

	/**
	 * Creates a new instance.
	 *
	 * @param string          $plugin_file      The full path to the base plugin file.
	 * @param Options         $options          The options handler.
	 * @param Transients      $transients       The transients handler.
	 * @param Site_Transients $site_transients  The site transients handler.
	 */
	public function __construct( $plugin_file, Options $options, Transients $transients, Site_Transients $site_transients ) {
		$this->plugin_file     = $plugin_file;
		$this->options         = $options;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
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
		// Add new default avatars.
		\add_filter( 'avatar_defaults', [ $this, 'avatar_defaults' ] );

		// New default image display: filter the gravatar image upon display.
		\add_filter( 'pre_get_avatar_data', [ $this, 'get_avatar_data' ], 10, 2 );
	}

	/**
	 * Adds new images to the list of default avatar images.
	 *
	 * @param  string[] $avatar_defaults The list of default avatar images.
	 *
	 * @return string[] The modified default avatar array.
	 */
	public function avatar_defaults( $avatar_defaults ) {
		// Remove Gravatar logo.
		unset( $avatar_defaults['gravatar_default'] );

		$avatar_defaults = array_merge( $avatar_defaults, [
			'rings'             => __( 'Rings (Generated)', 'avatar-privacy' ),
			/* translators: Icon set URL */
			'comment'           => sprintf( __( 'Comment (loaded from your server, part of <a href="%s">NDD Icon Set</a>, under LGPL)', 'avatar-privacy' ), 'http://www.nddesign.de/news/2007/10/15/NDD_Icon_Set_1_0_-_Free_Icon_Set' ),
			/* translators: Icon set URL */
			'im-user-offline'   => sprintf( __( 'User Offline (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy' ), 'http://www.oxygen-icons.org/' ),
			/* translators: Icon set URL */
			'view-media-artist' => sprintf( __( 'Media Artist (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy' ), 'http://www.oxygen-icons.org/' ),
		] );
		return $avatar_defaults;
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
		$show_gravatar = false;
		$force_default = ! empty( $args['force_default'] );
		$mimetype      = '';

		// Process the user identifier.
		list( $user_id, $email ) = $this->parse_id_or_email( $id_or_email );

		// Find out if the user opted out of displaying a gravatar.
		if ( ! $force_default && ( $user_id || $email ) ) {
			$use_default = false;
			if ( $user_id ) {
				// Fetch local avatar from meta and make sure it's properly stzed.
				$local_avatar = \get_user_meta( $user_id, User_Avatar_Upload::USER_META_KEY, true );
				if ( ! empty( $local_avatar['file'] ) && ! empty( $local_avatar['type'] ) ) {
					/**
					 * Filters the uploaed avatar URL for the given user.
					 *
					 * @param  string[]  $avatar  The full-size avatar image information.
					 * @param  string    $email   The mail address used to generate the identity hash.
					 * @param  int       $size    The size of the avatar image in pixels.
					 */
					$url = \apply_filters( 'avatar_privacy_user_avatar_icon_url', $local_avatar, $email, $args['size'] );

					if ( ! empty( $url ) ) {
						// Great, we have got a local avatar.
						$args['url'] = $url;

						// Return early.
						return $args;
					}
				}

				// For users get the value from the usermeta table.
				$show_gravatar = \get_user_meta( $user_id, \Avatar_Privacy_Core::GRAVATAR_USE_META_KEY, true ) === 'true';
				$use_default   = '' === $show_gravatar;
			} else {
				// For comments get the value from the plugin's table.
				$show_gravatar = $this->core->comment_author_allows_gravatar_use( $email );
				$use_default   = ! $this->core->comment_author_has_gravatar_policy( $email );
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
		}

		// Check if a gravatar exists for the e-mail address.
		if ( empty( $email ) ) {
			$show_gravatar = false;
		} elseif ( $show_gravatar ) {
			/**
			 * Filters whether we check if opting-in users and commenters actually have a Gravatar.com account.
			 *
			 * @param bool      $enable_check Defaults to true.
			 * @param string    $email        The email address.
			 * @param int]false $user_id      A WordPress user ID (or false).
			 */
			if ( \apply_filters( 'avatar_privacy_enable_gravatar_check', true, $email, $user_id ) ) {
				$show_gravatar = $this->validate_gravatar( $email, $mimetype );
			}
		}

		/**
		 * Filters the default icon URL for the given e-mail.
		 *
		 * @param  string $url     The fallback icon (a blank GIF).
		 * @param  string $hash    The hashed mail address.
		 * @param  string $default The default avatar image identifier.
		 * @param  int    $size    The size of the avatar image in pixels.
		 */
		$url = \apply_filters( 'avatar_privacy_default_icon_url', \includes_url( 'images/blank.gif' ), $this->core->get_hash( $email ), $args['default'], $args['size'] );

		// Maybe display a Gravatar.
		if ( $show_gravatar ) {
			if ( empty( $mimetype ) ) {
				$mimetype = Images::PNG_IMAGE;
			}

			/**
			 * Filters the Gravatar.com URL for the given e-mail.
			 *
			 * @param  string    $url      The fallback default icon URL.
			 * @param  string    $email    The mail address used to generate the identity hash.
			 * @param  int       $size     The size of the avatar image in pixels.
			 * @param  int]false $user_id  A WordPress user ID (or false).
			 * @param  string    $rating   The audience rating (e.g. 'g', 'pg', 'r', 'x').
			 * @param  string    $mimetype The expected MIME type of the Gravatar image.
			 */
			$url = \apply_filters( 'avatar_privacy_gravatar_icon_url', $url, $email, $args['size'], $user_id, $args['rating'], $mimetype );
		}

		$args['url'] = $url;

		return $args;
	}

	/**
	 * Parses e-mail address and/or user ID from $id_or_email.
	 *
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array                          The tuple [ $user_id, $email ],
	 */
	private function parse_id_or_email( $id_or_email ) {
		$user_id = false;
		$email   = '';

		if ( is_numeric( $id_or_email ) ) {
			$user_id = absint( $id_or_email );
		} elseif ( is_string( $id_or_email ) ) {
			// E-mail address.
			$email = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_User ) {
			// User object.
			$user_id = $id_or_email->ID;
			$email   = $id_or_email->user_email;
		} elseif ( $id_or_email instanceof \WP_Post ) {
			// Post object.
			$user_id = (int) $id_or_email->post_author;
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			/** This filter is documented in wp-includes/pluggable.php */
			$allowed_comment_types = \apply_filters( 'get_avatar_comment_types', [ 'comment' ] );

			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types, true ) ) {
				return [ false, '' ]; // Abort.
			}

			if ( ! empty( $id_or_email->user_id ) ) {
				$user_id = (int) $id_or_email->user_id;
			}
			if ( empty( $user_id ) && ! empty( $id_or_email->comment_author_email ) ) {
				$email = $id_or_email->comment_author_email;
			}
		}

		if ( ! empty( $user_id ) && empty( $email ) ) {
			$user  = \get_user_by( 'ID', $user_id );
			$email = $user->user_email;
		}

		return [ $user_id, $email ];
	}

	/**
	 * Validates if a gravatar exists for the given e-mail address. Function originally
	 * taken from: http://codex.wordpress.org/Using_Gravatars
	 *
	 * @param string $email    The e-mail address to check.
	 * @param string $mimetype Optional. Set to the mimetype of the gravatar if present. Passed by reference. Default null.
	 * @return bool            True if a gravatar exists for the given e-mail address,
	 *                         false otherwise, including if gravatar.com could not be
	 *                         reached or answered with a different error code or if
	 *                         no e-mail address was given.
	 */
	public function validate_gravatar( $email = '', &$mimetype = null ) {
		// Make sure we have a real address to check.
		if ( empty( $email ) ) {
			return false;
		}

		// Build the hash of the e-mail address.
		$hash = \md5( \strtolower( \trim( $email ) ) );

		// Try to find something in the cache.
		if ( isset( $this->validate_gravatar_cache[ $hash ] ) ) {
			$result = $this->validate_gravatar_cache[ $hash ];
			if ( null !== $mimetype && ! empty( $result ) ) {
				$mimetype = $result;
			}

			return ! empty( $result );
		}

		// Try to find it via transient cache. On multisite, we use site transients.
		$transient_key = "check_{$hash}";
		$transients    = \is_multisite() ? $this->site_transients : $this->transients;
		$result        = $transients->get( $transient_key );
		if ( false !== $result ) {
			$this->validate_gravatar_cache[ $hash ] = $result;
			if ( null !== $mimetype && ! empty( $result ) ) {
				$mimetype = $result;
			}

			return ! empty( $result );
		}

		// Ask gravatar.com.
		$response = \wp_remote_head( "https://gravatar.com/avatar/{$hash}?d=404" );
		if ( $response instanceof \WP_Error ) {
			return false; // Don't cache the result.
		}

		if ( 200 === \wp_remote_retrieve_response_code( $response ) ) {
			$result = wp_remote_retrieve_header( $response, 'content-type' );

			if ( null !== $mimetype && ! empty( $result ) ) {
				$mimetype = $result;
			}
		}

		// Cache the result across all blogs (a YES for 1 day, a NO for 10 minutes
		// -- since a YES basically shouldn't change, but a NO might change when the user signs up with gravatar.com).
		$transients->set( $transient_key, ! empty( $result ) ? $result : 0, ! empty( $result ) ? DAY_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		$this->validate_gravatar_cache[ $hash ] = $result;

		return ! empty( $result );
	}
}
