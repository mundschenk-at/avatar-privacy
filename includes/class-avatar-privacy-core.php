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
	 * The full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

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
	 * @param string     $plugin_file The full path to the base plugin file.
	 * @param string     $version     The full plugin version string (e.g. "3.0.0-beta.2").
	 * @param Transients $transients  Required.
	 * @param Cache      $cache       Required.
	 * @param Options    $options     Required.
	 */
	public function __construct( $plugin_file, $version, Transients $transients, Cache $cache, Options $options ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
		$this->transients  = $transients;
		$this->cache       = $cache;
		$this->options     = $options;

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Enable various hooks.
	 */
	public function plugins_loaded() {
		// Read the plugin settings.
		$this->settings = $this->options->get( self::SETTINGS_NAME, [] );

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

	/**
	 * Validates if a gravatar exists for the given e-mail address. Function originally
	 * taken from: http://codex.wordpress.org/Using_Gravatars
	 *
	 * @param string $email The e-mail address to check.
	 * @return bool         True if a gravatar exists for the given e-mail address,
	 *                      false otherwise, including if gravatar.com could not be
	 *                      reached or answered with a different error code or if
	 *                      no e-mail address was given.
	 */
	public function validate_gravatar( $email = '' ) {
		// Make sure we have a real address to check.
		if ( empty( $email ) ) {
			return false;
		}

		// Build the hash of the e-mail address.
		$hash = md5( strtolower( trim( $email ) ) );

		// Try to find something in the cache.
		if ( isset( $this->validate_gravatar_cache[ $hash ] ) ) {
			return $this->validate_gravatar_cache[ $hash ];
		}

		// Try to find it via transient cache. On multisite, we use site transients.
		$transient_key = "check_{$hash}";
		$transients    = is_multisite() ? $this->site_transients : $this->transients;
		$result        = $transients->get( $transient_key );
		if ( false !== $result ) {
			$result                                 = ! empty( $result );
			$this->validate_gravatar_cache[ $hash ] = $result;
			return $result;
		}

		// Ask gravatar.com.
		$result = 200 === wp_remote_retrieve_response_code( wp_remote_head( "https://gravatar.com/avatar/{$hash}?d=404" ) );

		// Cache the result across all blogs (a YES for 1 day, a NO for 10 minutes
		// -- since a YES basically shouldn't change, but a NO might change when the user signs up with gravatar.com).
		$transients->set( $transient_key, $result ? 1 : 0, $result ? DAY_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
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

	/**
	 * Retrieves the full path to the main plugin file.
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = $this->options->get( self::SETTINGS_NAME, [] );
		}

		return $this->settings;
	}

	/**
	 * Checks whether an anonymous comment author has opted-in to Gravatar usage.
	 *
	 * @param  string $email The comment author's email address.
	 *
	 * @return bool
	 */
	public function comment_author_allows_gravatar_use( $email ) {
		$data = $this->load_data( $email );

		return ! empty( $data ) && ! empty( $data->use_gravatar );
	}

	/**
	 * Checks whether an anonymous comment author is in our Gravatar policy database.
	 *
	 * @param  string $email The comment author's email address.
	 *
	 * @return bool
	 */
	public function comment_author_has_gravatar_policy( $email ) {
		$data = $this->load_data( $email );

		return ! empty( $data );
	}


	/**
	 * Returns the dataset from the 'use gravatar' table for the given E-Mail
	 * address.
	 *
	 * @param  string $email The e-mail address to check.
	 *
	 * @return object        The dataset as an object or null.
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
}
