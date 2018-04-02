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
	 * The core API.
	 *
	 * @var \Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * Creates a new instance.
	 *
	 * @param string  $plugin_file The full path to the base plugin file.
	 * @param Options $options     The options handler.
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
		// Add new default avatars.
		\add_filter( 'avatar_defaults', [ $this, 'avatar_defaults' ] );

		// New default image display: filter the gravatar image upon display.
		\add_filter( 'get_avatar_url', [ $this, 'get_avatar_url' ], 10, 3 );
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
			'rings'             => __( 'Rings (automatically generated)', 'avatar-privacy' ),
			'aleavatar'         => __( 'Aleavatar (automatically generated)', 'avatar-privacy' ),
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
	 * "Don't publish encrypted E-Mail addresses for non-members of Gravatar." is
	 * enabled, the function checks if a gravatar is actually available for the
	 * e-mail address. If not, it displays the default image directly.
	 *
	 * @param  string            $url         The URL of the avatar.
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash, user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param  array             $args        Arguments passed to get_avatar_data(), after processing.
	 *
	 * @return string
	 */
	public function get_avatar_url( $url, $id_or_email, $args ) {
		global $pagenow;

		// Don't change anything on the discussion settings page, except for our own new gravatars.
		$on_settings_page = 'options-discussion.php' === $pagenow;
		$show_avatar      = false;
		$settings         = $this->core->get_settings();

		// Process the user identifier.
		list( $user_id, $email ) = $this->parse_id_or_email( $id_or_email );

		// Find out if the user opted out of displaying a gravatar.
		if ( $user_id || $email ) {
			$use_default = false;
			if ( $user_id ) {
				// For users get the value from the usermeta table.
				$show_avatar = \get_user_meta( $user_id, 'use_gravatar', true ) === 'true';
				$use_default = '' === $show_avatar;
			} else {
				// For comments get the value from the plugin's table.
				$show_avatar = $this->core->comment_author_allows_gravatar_use( $email );
				$use_default = ! $this->core->comment_author_has_gravatar_policy( $email );
			}
			if ( $use_default ) {
				$show_avatar = ! empty( $settings['default_show'] ); // Default settings are legacy-only.
			}
		}

		// Check if a gravatar exists for the e-mail address.
		if ( empty( $email ) ) {
			$show_avatar = false;
		} elseif ( $show_avatar && ! empty( $settings['mode_checkforgravatar'] ) ) {
			$show_avatar = $this->core->validate_gravatar( $email );
		}

		/**
		 * Filters the default icon URL for the given e-mail.
		 *
		 * @param  string $url     The fallback icon (a blank GIF).
		 * @param  string $email   The mail address used to generate the identity hash.
		 * @param  string $default The default avatar image identifier.
		 * @param  int    $size    The size of the avatar image in pixels.
		 */
		$new_url = \apply_filters( 'avatar_privacy_default_icon_url', \includes_url( 'images/blank.gif' ), $email, $args['default'], $args['size'] );

		// Modify the avatar URL.
		if ( $on_settings_page || ! $show_avatar ) {
			// Display the default avatar instead of the avatar for the e-mail address.
			$url = $new_url;
		} else {
			// Change the default avatar in the given URL (for users who opted in to gravatars but don't have one).
			$url = \str_replace( "d={$args['default']}", "d={$new_url}", $url );
		}

		return $url;
	}

	/**
	 * Parses e-mail address and/or user ID from $id_or_email.
	 *
	 * @param  int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array                          The tuple [ $user_id, $email ],
	 */
	private function parse_id_or_email( $id_or_email ) {
		$user_id = false;
		$email   = '';

		if ( is_numeric( $id_or_email ) ) {
			$user_id = absint( $id_or_email );
		} elseif ( is_string( $id_or_email ) ) {
			if ( strpos( $id_or_email, '@md5.gravatar.com' ) ) {
				// MD5 hash.
				// FIXME: unused.
				list( $email_hash ) = explode( '@', $id_or_email );
			} else {
				// E-mail address.
				$email = $id_or_email;
			}
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
			if ( ! $user_id && ! empty( $id_or_email->comment_author_email ) ) {
				$email = $id_or_email->comment_author_email;
			}
		}

		if ( ! empty( $user_id ) && empty( $email ) ) {
			$user  = \get_user_by( 'ID', $user_id );
			$email = $user->user_email;
		}

		return [ $user_id, $email ];
	}
}
