<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
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

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Core class of the Avatar Privacy plugin. Contains all the actual code
 * except the options page.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */
class Avatar_Privacy_Core {

	// --------------------------------------------------------------------------
	// constants
	// --------------------------------------------------------------------------
	// If anything changes here, modify uninstall.php too!
	/**
	 * The name of the checkbox field in the comment form.
	 */
	const CHECKBOX_FIELD_NAME = 'use_gravatar';

	/**
	 * The name of the combined settings in the database.
	 */
	const SETTINGS_NAME = 'settings';

	/**
	 * Prefix for caching avatar privacy for non-logged-in users.
	 */
	const EMAIL_CACHE_PREFIX = 'email_';

	// --------------------------------------------------------------------------
	// variables
	// --------------------------------------------------------------------------
	/**
	 * The user's settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * A cache for the results of the validate_gravatar function.
	 *
	 * @var array
	 */
	private $validate_gravatar_cache = array();

	/**
	 * A cache for the default avatars.
	 *
	 * @var array
	 */
	private $default_avatars = array();

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The cache handler.
	 *
	 * @var Cache
	 */
	private $cache;

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
	 * The singleton instance.
	 *
	 * @var Avatar_Privacy_Core
	 */
	private static $_instance;

	/**
	 * Retrieves (and if necessary creates) the API instance. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param Avatar_Privacy_Core $instance Only used for plugin initialization. Don't ever pass a value in user code.
	 *
	 * @throws BadMethodCallException Thrown when WP_Typography::set_instance after plugin initialization.
	 */
	public static function set_instance( Avatar_Privacy_Core $instance ) {
		if ( null === self::$_instance ) {
			self::$_instance = $instance;
		} else {
			throw new BadMethodCallException( 'WP_Typography::set_instance called more than once.' );
		}
	}

	/**
	 * Retrieves the plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @throws BadMethodCallException Thrown when WP_Typography::get_instance is called before plugin initialization.
	 *
	 * @return Avatar_Privacy_Core
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			throw new BadMethodCallException( 'Avatar_Privacy_Core::get_instance called without prior plugin intialization.' );
		}

		return self::$_instance;
	}

	// --------------------------------------------------------------------------
	// constructor
	// --------------------------------------------------------------------------
	/**
	 * Creates a Avatar_Privacy_Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @param string     $version     The full plugin version string (e.g. "3.0.0-beta.2").
	 * @param Transients $transients  Required.
	 * @param Cache      $cache       Required.
	 * @param Options    $options     Required.
	 */
	public function __construct( $version, Transients $transients, Cache $cache, Options $options ) {
		$this->version    = $version;
		$this->transients = $transients;
		$this->cache      = $cache;
		$this->options    = $options;

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Enable various hooks.
	 */
	public function plugins_loaded() {
		// Add new default avatars.
		add_filter( 'avatar_defaults', [ $this, 'avatar_defaults' ] );

		// Read the plugin settings.
		$this->settings = $this->options->get( self::SETTINGS_NAME, [] );

		// New default image display: filter the gravatar image upon display.
		add_filter( 'get_avatar', [ $this, 'get_avatar' ], 10, 5 );

		// Add the checkbox to the comment form.
		add_filter( 'comment_form_default_fields', [ $this, 'comment_form_default_fields' ] );

		// Handle the checkbox data upon saving the comment.
		add_action( 'comment_post', [ $this, 'comment_post' ], 10, 2 );
		if ( is_admin() ) {
			// Add the checkbox to the user profile form if we're in the WP backend.
			add_action( 'show_user_profile', [ $this, 'add_user_profile_fields' ] );
			add_action( 'edit_user_profile', [ $this, 'add_user_profile_fields' ] );
			add_action( 'personal_options_update', [ $this, 'save_user_profile_fields' ] );
			add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_fields' ] );
		}
	}

	// --------------------------------------------------------------------------
	// public functions
	// --------------------------------------------------------------------------
	// If anything gets changed here, modify uninstall.php too.
	/**
	 * Returns the array of new default avatars defined by this plugin.
	 *
	 * @return array The array of default avatars.
	 */
	public function default_avatars() {
		if ( empty( $this->default_avatars ) ) {
			$this->default_avatars = [
				/* translators: Icon set URL */
				'comment'           => sprintf( __( 'Comment (loaded from your server, part of <a href="%s">NDD Icon Set</a>, under LGPL)', 'avatar-privacy' ), 'http://www.nddesign.de/news/2007/10/15/NDD_Icon_Set_1_0_-_Free_Icon_Set' ),
				/* translators: Icon set URL */
				'im-user-offline'   => sprintf( __( 'User Offline (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy' ), 'http://www.oxygen-icons.org/' ),
				/* translators: Icon set URL */
				'view-media-artist' => sprintf( __( 'Media Artist (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy' ), 'http://www.oxygen-icons.org/' ),
			];
		}
		return $this->default_avatars;
	}

	/**
	 * Adds new images to the list of default avatar images.
	 *
	 * @param array $avatar_defaults The list of default avatar images.
	 * @return array The modified default avatar array.
	 */
	public function avatar_defaults( $avatar_defaults ) {
		$avatar_defaults = array_merge( $avatar_defaults, $this->default_avatars() );
		return $avatar_defaults;
	}

	/**
	 * Before displaying an avatar image, checks that displaying the gravatar
	 * for this E-Mail address has not been disabled (opted out, option 2).
	 * Also, if option 1 is selected ("Don't publish encrypted E-Mail addresses
	 * for non-members of Gravatar."), the function checks if a gravatar is
	 * available for the E-Mail address and if not, it displays the default image
	 * directly.
	 *
	 * @param string            $avatar The avatar image HTML fragment as built by the
	 *                          WordPress function.
	 * @param int|string|object $id_or_email Either a user ID, a user object, a
	 *                          comment object, or an E-Mail address.
	 * @param int               $size The size of the avatar image in pixels.
	 * @param string            $default The URL of the default image.
	 * @param string            $alt The alternate text to use in the image tag.
	 *
	 * @return string The avatar image HTML code for the user's avatar.
	 */
	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		global $pagenow;
		$show_avatar = true; // Since this filter function has been called, WP option 'show_avatars' must be set to true.

		// Don't change anything on the discussion settings page, except for our own new gravatars.
		$on_settings_page = 'options-discussion.php' === $pagenow;
		$default_avatars  = $this->default_avatars();
		if ( $on_settings_page && ! isset( $default_avatars[ $default ] ) ) {
			return $avatar;
		}

		// Get the E-Mail address and the user ID to display the gravatar for.
		$email   = '';
		$user_id = false;
		if ( is_numeric( $id_or_email ) ) {
			// Load from user via ID.
			$user_id = (int) $id_or_email;
			$user    = get_userdata( $user_id );
			if ( $user ) {
				$email = $user->user_email;
			}
		} elseif ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				// Load from either a user or an author comment object.
				$user_id = (int) $id_or_email->user_id;
				$user    = get_userdata( $user_id );
				if ( $user ) {
					$email = $user->user_email;
				}
			} elseif ( ! empty( $id_or_email->comment_author_email ) ) {
				// Load from comment.
				$email = $id_or_email->comment_author_email;
			}
		} else {
			// Load string directly.
			$email = $id_or_email;
		}

		// Find out if the user opted out of displaying a gravatar.
		if ( $user_id || $email ) {
			$use_default = false;
			if ( $user_id ) {
				// For users get the value from the usermeta table.
				$show_avatar = get_user_meta( $user_id, 'use_gravatar', true ) === 'true';
				$use_default = '' === $show_avatar;
			} else {
				// For comments get the value from the plugin's table.
				$current_value = $this->load_data( $email );
				$show_avatar   = $current_value && ( '1' === $current_value->use_gravatar );
				$use_default   = empty( $current_value );
			}
			if ( $use_default ) {
				$show_avatar = ! empty( $this->settings['default_show'] ); // Default settings are legacy-only.
			}
		}

		// Check if a gravatar exists for the E-Mail address.
		if ( $show_avatar && ! empty( $this->settings['mode_checkforgravatar'] ) && $email && ! $this->validate_gravatar( $email ) ) {
			$show_avatar = false;
		} elseif ( ! $email ) {
			$show_avatar = false;
		}

		// Change the default image if dynamic defaults are configured.
		if ( ! $show_avatar && $this->is_default_avatar_dynamic() ) {
			// Use blank image here, dynamic default images would leak the MD5.
			$default = includes_url( 'images/blank.gif' );
		}

		// New default avatars: replace avatar name with image URL.
		$default_name    = preg_match( '#http://\d+.gravatar.com/avatar/\?d=([^&]+)&#', $default, $matches ) ? $matches[1] : $default;
		$default_changed = isset( $default_avatars[ $default_name ] );
		if ( $default_changed ) {
			$old_default = $default_name;
			$default     = $this->get_default_avatar_url( $default_name, $size );
		}

		// Modify the avatar URL.
		$settings_page_first_load = $on_settings_page && empty( $this->settings );
		if ( ! $show_avatar || $settings_page_first_load ) {
			// Display the default avatar instead of the avatar for the E-Mail address.
			$avatar = $this->replace_avatar_url( $avatar, $default, $size, $email );
		} elseif ( $default_changed ) {
			// Change the default avatar in the given URL (for users who opted in to gravatars but don't have one).
			$avatar = str_replace( 'd=' . $old_default, 'd=' . $default, $avatar );
		}

		return $avatar;
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
		if ( is_user_logged_in() ) {
			return $fields;
		}

		// Define the new checkbox field.
		$is_checked = false;
		if ( isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) ) { // WPCS: CSRF ok, Input var okay.
			// Re-displaying the comment form with validation errors.
			$is_checked = ! empty( $_POST[ self::CHECKBOX_FIELD_NAME ] ); // WPCS: CSRF ok, Input var okay.
		} elseif ( isset( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ) ) { // Input var okay.
			// Read the value from the cookie, saved with previous comment.
			$is_checked = ! empty( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ); // Input var okay.
		}
		$new_field = '<p class="comment-form-use-gravatar">'
		. '<input id="' . self::CHECKBOX_FIELD_NAME . '" name="' . self::CHECKBOX_FIELD_NAME . '" type="checkbox" value="true"' . checked( $is_checked, true, false ) . ' style="width: auto; margin-right: 5px;" />'
		. '<label for="' . self::CHECKBOX_FIELD_NAME . '">' . sprintf( /* translators: gravatar.com URL */ __( 'Display a <a href="%s">Gravatar</a> image next to my comments.', 'avatar-privacy' ), 'https://gravatar.com' ) . '</label> '
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
		global $wpdb;

		// Don't save anything for spam comments, trackbacks/pingbacks, and registered user's comments.
		if ( 'spam' === $comment_approved ) {
			return;
		}
		$comment = get_comment( $comment_id );
		if ( ! $comment || ( '' !== $comment->comment_type ) || ( '' === $comment->comment_author_email ) ) {
			return;
		}

		// Make sure that the e-mail address does not belong to a registered user.
		if ( get_user_by( 'email', $comment->comment_author_email ) ) {
			// This is either a comment with a fake identity or a user who didn't sign in
			// and rather entered their details manually. Either way, don't save anything.
			return;
		}

		// Save the 'use gravatar' value.
		$use_gravatar  = ( isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ self::CHECKBOX_FIELD_NAME ] ) ) ? '1' : '0'; // WPCS: CSRF ok, Input var okay.
		$current_value = $this->load_data( $comment->comment_author_email );
		if ( ! $current_value ) {
			// Nothing found in the database, insert the dataset.
			$wpdb->insert(
				$wpdb->avatar_privacy, array(
					'email'        => $comment->comment_author_email,
					'use_gravatar' => $use_gravatar,
					'last_updated' => current_time( 'mysql' ),
					'log_message'  => 'set with comment ' . $comment_id . ( is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' ),
				), array( '%s', '%d', '%s', '%s' )
			); // WPCS: db call ok.
		} elseif ( $current_value->use_gravatar !== $use_gravatar ) {
			// Dataset found but with different value, update it.
			$wpdb->update(
				$wpdb->avatar_privacy, array(
					'use_gravatar' => $use_gravatar,
					'last_updated' => current_time( 'mysql' ),
					'log_message'  => 'set with comment ' . $comment_id . ( is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' ),
				),
				array( 'id' => $current_value->id ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			); // WPCS: db call ok, db cache ok.

			// Clear any previously cached value.
			$this->cache->delete( self::EMAIL_CACHE_PREFIX . md5( $comment->comment_author_email ) );
		}

		// Set a cookie for the 'use gravatar' value.
		$comment_cookie_lifetime = apply_filters( 'comment_cookie_lifetime', 30000000 );
		$secure                  = ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) );
		setcookie( 'comment_use_gravatar_' . COOKIEHASH, $use_gravatar, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
	}

	/**
	 * Adds the 'use gravatar' checkbox to the user profile form.
	 *
	 * @param object $user The current user whose profile to modify.
	 */
	public function add_user_profile_fields( $user ) {
		$val = (bool) get_the_author_meta( self::CHECKBOX_FIELD_NAME, $user->ID );

		require dirname( __DIR__ ) . '/admin/partials/profile/use-gravatar.php';
	}

	/**
	 * Saves the value of the 'use gravatar' checkbox from the user profile in
	 * the database.
	 *
	 * @param string $user_id The ID of the user that has just been saved.
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		// Use true/false instead of 1/0 since a '0' value is removed from the database and then
		// we can't differentiate between opted-out and never saved a value.
		$value = isset( $_POST[ self::CHECKBOX_FIELD_NAME ] ) && ( 'true' === $_POST[ self::CHECKBOX_FIELD_NAME ] ) ? 'true' : 'false'; // WPCS: CSRF ok, Input var okay.
		update_user_meta( $user_id, self::CHECKBOX_FIELD_NAME, $value );
	}


	// --------------------------------------------------------------------------
	// public helper functions
	// --------------------------------------------------------------------------
	/**
	 * Checks if the currently selected default avatar is dynamically generated
	 * out of an E-Mail address or not.
	 *
	 * @return bool True if the current default avatar is dynamic, false if it
	 *              is a static image.
	 */
	public function is_default_avatar_dynamic() {
		switch ( get_option( 'avatar_default' ) ) {
			case 'identicon':
			case 'wavatar':
			case 'monsterid':
			case 'retro':
				return true;

			default:
				return false;
		}
	}

	/**
	 * Validates if a gravatar exists for the given E-Mail address. Function
	 * taken from: http://codex.wordpress.org/Using_Gravatars
	 *
	 * @param string $email The E-Mail address to check.
	 * @return bool True if a gravatar exists for the given E-Mail address,
	 * false otherwise, including if gravatar.com could not be reached or
	 * answered with a different errror code or if no E-Mail address was given.
	 */
	public function validate_gravatar( $email = '' ) {
		// Make sure we have a real address to check.
		if ( 0 === strlen( $email ) ) {
			return false;
		}

		// Build the hash of the E-Mail address.
		$email = strtolower( trim( $email ) );
		$hash  = md5( $email );

		// Try to find something in the cache.
		if ( isset( $this->validate_gravatar_cache[ $hash ] ) ) {
			return $this->validate_gravatar_cache[ $hash ];
		}

		// Try to find it via transient cache
		// (maximum length of the key = 45 characters because of wp_options limitation on non-multisite pages, the MD5 hash needs space too).
		$transient_key      = "avapr_check_{$hash}";
		$is_multisite       = is_multisite();
		$transient_function = $is_multisite ? 'get_site_transient' : 'get_transient';
		$result             = $transient_function( $transient_key );
		if ( false !== $result ) {
			$result                                 = ! empty( $result );
			$this->validate_gravatar_cache[ $hash ] = $result;
			return $result;
		}

		// Ask gravatar.com.
		$uri    = 'https://gravatar.com/avatar/' . $hash . '?d=404';
		$result = 200 === wp_remote_retrieve_response_code( wp_remote_head( $uri ) );

		// Cache the result across all blogs (a YES for 1 day, a NO for 10 minutes
		// -- since a YES basically shouldn't change, but a NO might change when the user signs up with gravatar.com).
		$transient_function = $is_multisite ? 'set_site_transient' : 'set_transient';
		$transient_function( $transient_key, $result ? 1 : 0, $result ? DAY_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		$this->validate_gravatar_cache[ $hash ] = $result;

		return $result;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @var string
	 */
	public function get_version() {
		return $this->version;
	}

	// --------------------------------------------------------------------------
	// private functions
	// --------------------------------------------------------------------------
	/**
	 * Returns the dataset from the 'use gravatar' table for the given E-Mail
	 * address.
	 *
	 * @param string $email The E-Mail address to check.
	 *
	 * @return object The dataset as an object or null.
	 */
	private function load_data( $email ) {
		global $wpdb;

		if ( empty( $email ) ) {
			return null;
		}

		$key = self::EMAIL_CACHE_PREFIX . md5( $email );
		$res = $this->cache->get( $key );

		if ( empty( $res ) ) {
			$res = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->avatar_privacy} WHERE email LIKE %s", $email ),
				OBJECT
			); // WPCS: db call ok, cache ok.

			$this->cache->set( $key, $res, 5 * MINUTE_IN_SECONDS );
		}

		return $res;
	}

	/**
	 * Returns an image URL for the given default avatar identifier. The images
	 * are taken from the "images" sub-folder in the plugin folder.
	 *
	 * @param string $default The default avatar image identifier.
	 * @param int    $size    The size of the avatar image in pixels.
	 *
	 * @return string The full default avatar image URL.
	 */
	private function get_default_avatar_url( $default, $size ) {
		$use_size = ( $size > 64 ) ? '128' : '64';
		return plugins_url( '../public/images/' . $default . '-' . $use_size . '.png', __FILE__ ) . '?s=' . $size;
	}

	/**
	 * Replaces the avatar URL in the given HTML fragment.
	 *
	 * @param string $avatar  The avatar image HTML fragment.
	 * @param string $new_url The new URL to insert.
	 * @param int    $size    The size of the avatar image in pixels.
	 * @param string $email   Required.
	 *
	 * @return string The modified avatar HTML fragment.
	 */
	private function replace_avatar_url( $avatar, $new_url, $size, $email ) {
		if ( '' === $new_url ) {
			if ( is_ssl() ) {
				$host = 'https://secure.gravatar.com';
			} else {
				$host = empty( $email ) ? 'http://0.gravatar.com' : sprintf( 'http://%d.gravatar.com', ( hexdec( $email_hash[0] ) % 2 ) );
			}
			$new_url = "$host/avatar/?s={$size}";
		}
		return preg_replace( '/(src=["\'])[^"\']+(["\'])/i', '$1' . $new_url . '$2', $avatar );
	}
}
