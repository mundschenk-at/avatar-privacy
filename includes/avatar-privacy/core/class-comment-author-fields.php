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

namespace Avatar_Privacy\Core;

use Avatar_Privacy\Core\API;
use Avatar_Privacy\Core\Hasher;
use Avatar_Privacy\Data_Storage\Cache;

use const MINUTE_IN_SECONDS;

/**
 * The API for handling (anonymous) comment author data as part of the
 * Avatar_Privacy Core API.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 * @author Johannes Freudendahl <wordpress@freudendahl.net>
 */
class Comment_Author_Fields implements API {

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
	 * A copy of COLUMN_FORMAT_STRINGS for PHP 5.6 compatibility.
	 *
	 * @var array
	 */
	private $column_format_strings;

	/**
	 * The cache handler.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * The hashing helper.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Creates a \Avatar_Privacy\Core instance and registers all necessary hooks
	 * and filters for the plugin.
	 *
	 * @param Cache  $cache   Required.
	 * @param Hasher $hasher  Required.
	 */
	public function __construct( Cache $cache, Hasher $hasher ) {
		$this->cache  = $cache;
		$this->hasher = $hasher;

		// PHP 5.6 compatibility.
		$this->column_format_strings = self::COLUMN_FORMAT_STRINGS;
	}

	/**
	 * Retrieves the hash for the given comment author e-mail address.
	 *
	 * @param  string $email The comment author's e-mail address.
	 *
	 * @return string
	 */
	public function get_hash( $email ) {
		return $this->hasher->get_hash( $email );
	}

	/**
	 * Checks whether an anonymous comment author has opted-in to Gravatar usage.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function allows_gravatar_use( $email_or_hash ) {
		$data = $this->load( $email_or_hash );

		return ! empty( $data ) && ! empty( $data->use_gravatar );
	}

	/**
	 * Checks whether an anonymous comment author is in our Gravatar policy database.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return bool
	 */
	public function has_gravatar_policy( $email_or_hash ) {
		$data = $this->load( $email_or_hash );

		return ! empty( $data );
	}

	/**
	 * Retrieves the database primary key for the given email address.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 *
	 * @return int                   The database key for the given email address (or 0).
	 */
	public function get_key( $email_or_hash ) {
		$data = $this->load( $email_or_hash );

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
	public function load( $email_or_hash ) {
		global $wpdb;

		// Won't change valid hashes.
		$email_or_hash = \strtolower( \trim( $email_or_hash ) );
		if ( empty( $email_or_hash ) ) {
			return null;
		}

		// Check cache.
		$type = ( false === \strpos( $email_or_hash, '@' ) ) ? 'hash' : 'email';
		$key  = $this->get_cache_key( $email_or_hash, $type );
		$data = $this->cache->get( $key );

		if ( false === $data ) {
			// We need to query the database.
			$data = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB and column name.
				$wpdb->prepare( 'SELECT * FROM `%1$s` WHERE `%2$s` = "%3$s"', $wpdb->avatar_privacy, $type, $email_or_hash ),
				\OBJECT
			); // WPCS: db call ok, cache ok.

			$this->cache->set( $key, $data, 5 * MINUTE_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Retrieves the email for the given comment author database key.
	 *
	 * @param  string $hash The hashed mail address.
	 *
	 * @return string
	 */
	public function get_email( $hash ) {
		$data = $this->load( $hash );

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
	protected function update( $id, $email, array $columns ) {
		global $wpdb;

		$result = $wpdb->update( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $this->get_format_strings( $columns ), [ '%d' ] ); // WPCS: db call ok, db cache ok.

		if ( false !== $result && $result > 0 ) {
			// Clear any previously cached value.
			$this->clear_cache( $email );
		}

		return $result;
	}

	/**
	 * Updates the Avatar Privacy table.
	 *
	 * @param  string   $email        The mail address.
	 * @param  int|null $use_gravatar A flag indicating if gravatar use is allowed. `null` indicates the default policy (i.e. not set).
	 * @param  string   $last_updated A date/time in MySQL format.
	 * @param  string   $log_message  The log message.
	 *
	 * @return int|false              The number of rows updated, or false on error.
	 */
	protected function insert( $email, $use_gravatar, $last_updated, $log_message ) {
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
		$this->clear_cache( $hash, 'hash' );

		return $wpdb->insert( $wpdb->avatar_privacy, $columns, $this->get_format_strings( $columns ) ); // WPCS: db call ok, db cache ok.
	}

	/**
	 * Ensures that the comment author gravatar policy is updated.
	 *
	 * @param  string $email        The comment author's mail address.
	 * @param  int    $comment_id   The comment ID.
	 * @param  int    $use_gravatar 1 if Gravatar.com is enabled, 0 otherwise.
	 */
	public function update_gravatar_use( $email, $comment_id, $use_gravatar ) {
		$data = $this->load( $email );
		if ( empty( $data ) ) {
			// Nothing found in the database, insert the dataset.
			$this->insert( $email, $use_gravatar, \current_time( 'mysql' ), $this->get_log_message( $comment_id ) );
		} else {
			if ( $data->use_gravatar !== $use_gravatar ) {
				// Dataset found but with different value, update it.
				$new_values = [
					'use_gravatar' => $use_gravatar,
					'last_updated' => \current_time( 'mysql' ),
					'log_message'  => $this->get_log_message( $comment_id ),
					'hash'         => $this->get_hash( $email ),
				];
				$this->update( $data->id, $data->email, $new_values );
			} elseif ( empty( $data->hash ) ) {
				// Just add the hash.
				$this->update_hash( $data->id, $data->email );
			}
		}
	}

	/**
	 * Updates the hash using the ID and email.
	 *
	 * @param  int    $id    The database key.
	 * @param  string $email The email.
	 */
	public function update_hash( $id, $email ) {
		$this->update( $id, $email, [ 'hash' => $this->get_hash( $email ) ] );
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
	 * Returns a formatted log message for comment author data.
	 *
	 * @param  int $comment_id A valid comment ID.
	 *
	 * @return string
	 */
	protected function get_log_message( $comment_id ) {
		$log_message = 'set with comment %d';
		$parameters  = [ $comment_id ];

		if ( \is_multisite() ) {
			global $wpdb;

			$log_message .= ' (site: %d, blog: %d)';
			$parameters[] = $wpdb->siteid;
			$parameters[] = $wpdb->blogid;
		}

		return \vsprintf( $log_message, $parameters );
	}

	/**
	 * Clears the cache for the given comment author e-mail address or hash.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 * @param  string $type          Optional. The identifier type ('email' or 'hash'). Default 'email'.
	 */
	public function clear_cache( $email_or_hash, $type = 'email' ) {
		$this->cache->delete( $this->get_cache_key( $email_or_hash, $type ) );
	}

	/**
	 * Calculates the cache key for the given identifier.
	 *
	 * @param  string $email_or_hash The comment author's e-mail address or the unique hash.
	 * @param  string $type          Optional. The identifier type ('email' or 'hash'). Default 'email'.
	 *
	 * @return string
	 */
	protected function get_cache_key( $email_or_hash, $type = 'email' ) {
		if ( 'email' === $type ) {
			// We only need the hash here.
			$email_or_hash = $this->get_hash( $email_or_hash );
		}

		return self::EMAIL_CACHE_PREFIX . $email_or_hash;
	}
}
