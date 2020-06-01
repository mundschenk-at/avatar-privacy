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

namespace Avatar_Privacy;

use Avatar_Privacy\Settings;

use Avatar_Privacy\Core\Hasher;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;

/**
 * The core database API of the Avatar Privacy plugin.
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Johannes Freudendahl <wordpress@freudendahl.net>
 */
class Core {

	/**
	 * The name of the combined settings in the database.
	 */
	const SETTINGS_NAME = 'settings';

	/**
	 * The user meta key for the hashed email.
	 *
	 * @var string
	 */
	const EMAIL_HASH_META_KEY = 'avatar_privacy_hash';

	/**
	 * The user meta key for the gravatar use flag.
	 *
	 * @var string
	 */
	const GRAVATAR_USE_META_KEY = 'avatar_privacy_use_gravatar';

	/**
	 * The user meta key for the gravatar use flag.
	 *
	 * @var string
	 */
	const ALLOW_ANONYMOUS_META_KEY = 'avatar_privacy_allow_anonymous';

	/**
	 * The user meta key for the local avatar.
	 *
	 * @var string
	 */
	const USER_AVATAR_META_KEY = 'avatar_privacy_user_avatar';


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

	/**
	 * The user's settings.
	 *
	 * @var array
	 */
	private $settings = [];

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
	 * A copy of COLUMN_FORMAT_STRINGS for PHP 5.6 compatibility.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	private $column_format_strings;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings_template;

	/**
	 * The hashing helper.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * The singleton instance.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * Creates a \Avatar_Privacy\Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @since 2.1.0 Parameter $plugin_file removed.
	 *
	 * @param string          $version           The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Transients      $transients        Required.
	 * @param Site_Transients $site_transients   Required.
	 * @param Cache           $cache             Required.
	 * @param Options         $options           Required.
	 * @param Settings        $settings_template Required.
	 * @param Hasher          $hasher            Required.
	 */
	public function __construct( $version, Transients $transients, Site_Transients $site_transients, Cache $cache, Options $options, Settings $settings_template, Hasher $hasher ) {
		$this->version           = $version;
		$this->transients        = $transients;
		$this->site_transients   = $site_transients;
		$this->cache             = $cache;
		$this->options           = $options;
		$this->settings_template = $settings_template;
		$this->hasher            = $hasher;

		// PHP 5.6 compatibility.
		$this->column_format_strings = self::COLUMN_FORMAT_STRINGS;
	}

	/**
	 * Retrieves (and if necessary creates) the API instance. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param Core $instance Only used for plugin initialization. Don't ever pass a value in user code.
	 *
	 * @throws \BadMethodCallException Thrown when Avatar_Privacy_Core::set_instance after plugin initialization.
	 */
	public static function set_instance( Core $instance ) {
		if ( null === self::$instance ) {
			self::$instance = $instance;
		} else {
			throw new \BadMethodCallException( __METHOD__ . ' called more than once.' );
		}
	}

	/**
	 * Retrieves the plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @throws \BadMethodCallException Thrown when Avatar_Privacy_Core::get_instance is called before plugin initialization.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called without prior plugin intialization.' );
		}

		return self::$instance;
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
	 * @deprecated 2.3.0 Use AVATAR_PRIVACY_PLUGIN_FILE instead.
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		return \AVATAR_PRIVACY_PLUGIN_FILE;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @since 2.0.0 Parameter $force added.
	 *
	 * @param bool $force Optional. Forces retrieval of settings from database. Default false.
	 *
	 * @return array
	 */
	public function get_settings( $force = false ) {
		// Force a re-read if the cached settings do not appear to be from the current version.
		if ( empty( $this->settings ) || empty( $this->settings[ Options::INSTALLED_VERSION ] )
			|| $this->version !== $this->settings[ Options::INSTALLED_VERSION ] || $force ) {
			$this->settings = (array) $this->options->get( self::SETTINGS_NAME, $this->settings_template->get_defaults() );
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
	 * Returns the dataset from the 'use gravatar' table for the given e-mail
	 * address.
	 *
	 * @internal
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return object|null           The dataset as an object or null.
	 */
	public function load_data( $email_or_hash ) {
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
	 * @return object|null   The dataset as an object or null.
	 */
	protected function load_data_by_email( $email ) {
		global $wpdb;

		$email = \strtolower( \trim( $email ) );
		if ( empty( $email ) ) {
			return null;
		}

		$key  = self::EMAIL_CACHE_PREFIX . $this->hasher->get_hash( $email );
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
	 * @return object|null  The dataset as an object or null.
	 */
	protected function load_data_by_hash( $hash ) {
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
	 * Retrieves the hash for the given user ID. If there currently is no hash,
	 * a new one is generated.
	 *
	 * @since 2.1.0 False is returned on error.
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return string|false The hashed email, or `false` on failure.
	 */
	public function get_user_hash( $user_id ) {
		$hash = \get_user_meta( $user_id, self::EMAIL_HASH_META_KEY, true );

		if ( empty( $hash ) ) {
			$user = \get_user_by( 'ID', $user_id );

			if ( ! empty( $user->user_email ) ) {
				$hash = $this->hasher->get_hash( $user->user_email );
				\update_user_meta( $user_id, self::EMAIL_HASH_META_KEY, $hash );
			}
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
	protected function update_comment_author_data( $id, $email, array $columns ) {
		global $wpdb;

		$result = $wpdb->update( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $this->get_format_strings( $columns ), [ '%d' ] ); // WPCS: db call ok, db cache ok.

		if ( false !== $result && $result > 0 ) {
			// Clear any previously cached value.
			$this->cache->delete( self::EMAIL_CACHE_PREFIX . $this->hasher->get_hash( $email ) );
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
	protected function insert_comment_author_data( $email, $use_gravatar, $last_updated, $log_message ) {
		global $wpdb;

		$hash    = $this->hasher->get_hash( $email );
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
	 * Ensures that the comment author gravatar policy is updated.
	 *
	 * @param  string $email        The comment author's mail address.
	 * @param  int    $comment_id   The comment ID.
	 * @param  int    $use_gravatar 1 if Gravatar.com is enabled, 0 otherwise.
	 */
	public function update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar ) {
		global $wpdb;

		$data = $this->load_data( $email );
		if ( empty( $data ) ) {
			// Nothing found in the database, insert the dataset.
			$log_message = 'set with comment ' . $comment_id . ( \is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' );
			$this->insert_comment_author_data( $email, $use_gravatar, \current_time( 'mysql' ), $log_message );
		} else {
			if ( $data->use_gravatar !== $use_gravatar ) {
				// Dataset found but with different value, update it.
				$new_values = [
					'use_gravatar' => $use_gravatar,
					'last_updated' => \current_time( 'mysql' ),
					'log_message'  => 'set with comment ' . $comment_id . ( \is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : '' ),
					'hash'         => $this->hasher->get_hash( $email ),
				];
				$this->update_comment_author_data( $data->id, $data->email, $new_values );
			} elseif ( empty( $data->hash ) ) {
				// Just add the hash.
				$this->update_comment_author_hash( $data->id, $data->email );
			}
		}
	}

	/**
	 * Updates the hash using the ID and email.
	 *
	 * @param  int    $id    The database key.
	 * @param  string $email The email.
	 */
	public function update_comment_author_hash( $id, $email ) {
		$this->update_comment_author_data( $id, $email, [ 'hash' => $this->hasher->get_hash( $email ) ] );
	}

	/**
	 * Retrieves the salt for current the site/network.
	 *
	 * @deprecated 2.4.0
	 *
	 * @return string
	 */
	public function get_salt() {
		\_deprecated_function( __METHOD__, '2.4.0' );

		return $this->hasher->get_salt();
	}

	/**
	 * Generates a salted SHA-256 hash for the given e-mail address.
	 *
	 * @since 2.4.0 Implementation extracted to \Avatar_Privacy\Core\Hasher
	 *
	 * @param  string $email The mail address.
	 *
	 * @return string
	 */
	public function get_hash( $email ) {
		return $this->hasher->get_hash( $email );
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
	protected function get_format_strings( array $columns ) {
		$format_strings = [];

		foreach ( $columns as $key => $value ) {
			if ( ! empty( $this->column_format_strings[ $key ] ) ) {
				$format_strings[] = $this->column_format_strings[ $key ];
			}
		}

		if ( \count( $columns ) !== \count( $format_strings ) ) {
			throw new \RuntimeException( 'Invalid database update string.' );
		}

		return $format_strings;
	}

	/**
	 * Retrieves a user by email hash.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hash The user's email hash.
	 *
	 * @return \WP_User|null
	 */
	public function get_user_by_hash( $hash ) {
		// No extra caching necessary, WP Core already does that for us.
		$args  = [
			'number'       => 1,
			'meta_key'     => self::EMAIL_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => '=',
		];
		$users = \get_users( $args );

		if ( empty( $users ) ) {
			return null;
		}

		return $users[0];
	}

	/**
	 * Retrieves the full-size local avatar for a user (if one exists).
	 *
	 * @since 2.2.0
	 *
	 * @param  int $user_id The user ID.
	 *
	 * @return array {
	 *     An avatar definition, or the empty array.
	 *
	 *     @type string $file The local filename.
	 *     @type string $type The MIME type.
	 * }
	 */
	public function get_user_avatar( $user_id ) {
		/**
		 * Filters whether to retrieve the user avatar early. If the filtered result
		 * contains both a filename and a MIME type, those will be returned immediately.
		 *
		 * @since 2.2.0
		 *
		 * @param array|null {
		 *     Optional. The user avatar information. Default null.
		 *
		 *     @type string $file The local filename.
		 *     @type string $type The MIME type.
		 * }
		 * @param int $user_id The user ID.
		 */
		$avatar = \apply_filters( 'avatar_privacy_pre_get_user_avatar', null, $user_id );
		if ( ! empty( $avatar ) && ! empty( $avatar['file'] ) && ! empty( $avatar['type'] ) ) {
			return $avatar;
		}

		$avatar = \get_user_meta( $user_id, self::USER_AVATAR_META_KEY, true );
		if ( empty( $avatar ) ) {
			$avatar = [];
		}

		return $avatar;
	}
}
