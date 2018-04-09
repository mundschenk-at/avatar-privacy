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
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;

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

	const COLUMN_FORMAT_STRINGS = [
		'email'        => '%s',
		'last_updated' => '%s',
		'log_message'  => '%s',
		'hash'         => '%s',
		'use_gravatar' => '%d',
	];

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
	 * The network options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The salt used for the get_hash() method.
	 *
	 * @var string
	 */
	private $salt;


	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The singleton instance.
	 *
	 * @var Avatar_Privacy_Core
	 */
	private static $_instance;

	/**
	 * Creates a Avatar_Privacy_Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @param string          $plugin_file      The full path to the base plugin file.
	 * @param string          $version          The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Transients      $transients       Required.
	 * @param Site_Transients $site_transients  Required.
	 * @param Cache           $cache            Required.
	 * @param Options         $options          Required.
	 * @param Network_Options $network_options  Required.
	 */
	public function __construct( $plugin_file, $version, Transients $transients, Site_Transients $site_transients, Cache $cache, Options $options, Network_Options $network_options ) {
		$this->plugin_file     = $plugin_file;
		$this->version         = $version;
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->cache           = $cache;
		$this->options         = $options;
		$this->network_options = $network_options;

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

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
			$this->insert_comment_author_data( $comment->comment_author_email, $use_gravatar, current_time( 'mysql' ),
				'set with comment ' . $comment_id . ( is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' )
			);
		} else {
			if ( $current_value->use_gravatar !== $use_gravatar ) {
				// Dataset found but with different value, update it.
				$this->update_comment_author_data( $current_value->id, $current_value->email, [
					'use_gravatar' => $use_gravatar,
					'last_updated' => current_time( 'mysql' ),
					'log_message'  => 'set with comment ' . $comment_id . ( is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' ),
					'hash'         => $this->get_hash( $comment->comment_author_email ),
				] );
			} elseif ( empty( $current_value->hash ) ) {
				// Just add the hash.
				$this->update_comment_author_data( $current_value->id, $current_value->email, [ 'hash' => $this->get_hash( $comment->comment_author_email ) ] );
			}
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
		$result = 200 === wp_remote_retrieve_response_code( /* @scrutinizer ignore-type */ wp_remote_head( "https://gravatar.com/avatar/{$hash}?d=404" ) );

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
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function comment_author_allows_gravatar_use( $email_or_hash ) {
		$data = $this->load_data( $email_or_hash );

		return ! empty( $data ) && ! empty( $data->use_gravatar );
	}

	/**
	 * Checks whether an anonymous comment author is in our Gravatar policy database.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function comment_author_has_gravatar_policy( $email_or_hash ) {
		$data = $this->load_data( $email_or_hash );

		return ! empty( $data );
	}

	/**
	 * Retrieves the database primary key for the given email address.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return int                   The database key for the given email address (or 0).
	 */
	public function get_comment_author_key( $email_or_hash ) {
		$data = $this->load_data( $email_or_hash );

		if ( isset( $data->id ) ) {
			return $data->id;
		}

		return 0;
	}

	/**
	 * Returns the dataset from the 'use gravatar' table for the given E-Mail
	 * address.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return object                The dataset as an object or null.
	 */
	private function load_data( $email_or_hash ) {
		if ( false === \strpos( $email_or_hash, '@' ) ) {
			return $this->load_data_by_hash( $email_or_hash );
		} else {
			return $this->load_data_by_email( $email_or_hash );
		}
	}

	/**
	 * Returns the dataset from the 'use gravatar' table for the given database key.
	 *
	 * @param  string $email The mail address.
	 *
	 * @return object        The dataset as an object or null.
	 */
	private function load_data_by_email( $email ) {
		global $wpdb;

		$email = \strtolower( \trim( $email ) );
		if ( empty( $email ) ) {
			return null;
		}

		$key  = self::EMAIL_CACHE_PREFIX . $this->get_hash( $email );
		$data = $this->cache->get( $key );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->avatar_privacy} WHERE email = %s", $email ),
				OBJECT
			); // WPCS: db call ok, cache ok.

			$this->cache->set( $key, $data, 5 * MINUTE_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Returns the dataset from the 'use gravatar' table for the given database key.
	 *
	 * @param  string $hash The hashed mail address.
	 *
	 * @return object       The dataset as an object or null.
	 */
	private function load_data_by_hash( $hash ) {
		global $wpdb;

		if ( empty( $hash ) ) {
			return null;
		}

		$key  = self::EMAIL_CACHE_PREFIX . $hash;
		$data = $this->cache->get( $key );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->avatar_privacy} WHERE hash = %s", $hash ),
				OBJECT
			); // WPCS: db call ok, cache ok.

			$this->cache->set( $key, $data, 5 * MINUTE_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Generates a random, 3 byte long salt.
	 *
	 * @return int
	 */
	private function generate_salt() {
		return \mt_rand( 1, 16777215 );
	}

	/**
	 * Retrieves the hash for the given user ID. If there currently is no hash,
	 * a new one is generated.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string
	 */
	public function get_user_hash( $user_id ) {
		$hash = \get_user_meta( $user_id, 'avatar_privacy_hash', true );

		if ( empty( $hash ) ) {
			$user = \get_user_by( 'ID', $user_id );
			$hash = $this->get_hash( $user->user_email );
			\update_user_meta( $user_id, 'avatar_privacy_hash', $hash );
		}

		return $hash;
	}

	/**
	 * Retrieves the email for the given comment author database key.
	 *
	 * @param  string $hash The hashed mail address.
	 *
	 * @return string
	 */
	public function get_comment_author_email( $hash ) {
		$data = $this->load_data_by_hash( $hash );

		return ! empty( $data->email ) ? $data->email : '';
	}

	/**
	 * Updates the Avatar Privacy table.
	 *
	 * @param  int    $id      The row ID.
	 * @param  string $email   The mail address.
	 * @param  array  $columns An array of values index by column name.
	 *
	 * @return int|false      The number of rows updated, or false on error.
	 *
	 * @throws \RuntimeException A \RuntimeException is raised when invalid column names are used.
	 */
	private function update_comment_author_data( $id, $email, array $columns ) {
		global $wpdb;

		$result = $wpdb->update( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $this->get_format_strings( $columns ), [ '%d' ] ); // WPCS: db call ok, db cache ok.

		if ( false !== $result && $result > 0 ) {
			// Clear any previously cached value.
			$this->cache->delete( self::EMAIL_CACHE_PREFIX . $this->get_hash( $email ) );
		}

		return $result;
	}

	/**
	 * Updates the Avatar Privacy table.
	 *
	 * @param  string $email        The mail address.
	 * @param  int    $use_gravatar A flag indicating if gravatar use is allowed.
	 * @param  string $last_updated A date/time in MySQL format.
	 * @param  string $log_message  The log message.
	 *
	 * @return int|false      The number of rows updated, or false on error.
	 */
	private function insert_comment_author_data( $email, $use_gravatar, $last_updated, $log_message ) {
		global $wpdb;

		$hash    = $this->get_hash( $email );
		$columns = [
			'email'        => $email,
			'hash'         => $hash,
			'use_gravatar' => $use_gravatar,
			'last_updated' => $last_updated,
			'log_message'  => $log_message,
		];

		// Clear any previously cached value, just in case.
		$this->cache->delete( self::EMAIL_CACHE_PREFIX . $hash );

		return $wpdb->insert( $wpdb->avatar_privacy, $columns, $this->get_format_strings( $columns ) ); // WPCS: db call ok, db cache ok.
	}

	/**
	 * Retrieves the salt for current the site/network.
	 *
	 * @return string
	 */
	public function get_salt() {
		if ( empty( $this->salt ) ) {
			// FIXME: Add filter hook.
			$this->salt = $this->network_options->get( 'salt' );

			if ( empty( $this->salt ) ) {
				$this->salt = \mt_rand();

				$this->network_options->set( 'salt', $this->salt );
			}
		}

		return $this->salt;
	}

	/**
	 * Generates a salted SHA-256 hash for the given e-mail address.
	 *
	 * @param  string $email The mail address.
	 *
	 * @return string
	 */
	public function get_hash( $email ) {
		$email = \strtolower( \trim( $email ) );

		return \hash( 'sha256', "{$this->get_salt()}{$email}" );
	}

	/**
	 * Retrieves the correct format strings for the given columns.
	 *
	 * @param  array $columns An array of values index by column name.
	 *
	 * @return string[]
	 *
	 * @throws \RuntimeException A \RuntimeException is raised when invalid column names are used.
	 */
	private function get_format_strings( array $columns ) {
		$format_strings = [];

		foreach ( $columns as $key => $value ) {
			if ( ! empty( self::COLUMN_FORMAT_STRINGS[ $key ] ) ) {
				$format_strings[] = self::COLUMN_FORMAT_STRINGS[ $key ];
			}
		}

		if ( count( $columns ) !== count( $format_strings ) ) {
			throw new \RuntimeException( 'Invalid database update string.' );
		}

		return $format_strings;
	}
}
