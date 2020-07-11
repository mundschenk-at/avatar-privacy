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
use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table;
use Avatar_Privacy\Data_Storage\Database\Hashes_Table;
use Avatar_Privacy\Tools\Hasher;

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
	 * The comment author table handler.
	 *
	 * @var Comment_Author_Table
	 */
	private $comment_author_table;

	/**
	 * The comment author table handler.
	 *
	 * @var Hashes_Table
	 */
	private $hashes_table;

	/**
	 * Creates a new instance.
	 *
	 * @param Cache                $cache                Required.
	 * @param Hasher               $hasher               Required.
	 * @param Comment_Author_Table $comment_author_table The comment author database table.
	 * @param Hashes_Table         $hashes_table         The hashes database table.
	 */
	public function __construct( Cache $cache, Hasher $hasher, Comment_Author_Table $comment_author_table, Hashes_Table $hashes_table ) {
		$this->cache                = $cache;
		$this->hasher               = $hasher;
		$this->comment_author_table = $comment_author_table;
		$this->hashes_table         = $hashes_table;
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
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB and column name.
					'SELECT c.*, h.hash FROM `%1$s` c LEFT OUTER JOIN `%2$s` h ON c.email = h.identifier AND h.type = "comment" WHERE `%3$s` = "%4$s"',
					$wpdb->avatar_privacy,
					$wpdb->avatar_privacy_hashes,
					$type,
					$email_or_hash
				),
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
	 * Updates the comment author information. Also clears the cache.
	 *
	 * @param  int    $id      The row ID.
	 * @param  string $email   The mail address.
	 * @param  array  $columns An array of values index by column name.
	 *
	 * @return int|false       The number of rows updated, or false on error.
	 *
	 * @throws \RuntimeException A \RuntimeException is raised when invalid
	 *                           column names are used.
	 */
	protected function update( $id, $email, array $columns ) {
		$result = $this->comment_author_table->update( $columns, [ 'id' => $id ] );

		if ( false !== $result && $result > 0 ) {
			// Clear any previously cached value.
			$this->clear_cache( $email );
		}

		return $result;
	}

	/**
	 * Inserts data into the comment author table (and the supplementary hashes
	 * table). Also clears the cache if successful.
	 *
	 * @param  string   $email        The mail address.
	 * @param  int|null $use_gravatar A flag indicating if gravatar use is allowed. `null` indicates the default policy (i.e. not set).
	 * @param  string   $log_message  The log message.
	 *
	 * @return int|false              The number of rows updated, or false on error.
	 */
	protected function insert( $email, $use_gravatar, $log_message ) {
		$columns = [
			'email'        => $email,
			'use_gravatar' => $use_gravatar,
			'log_message'  => $log_message,
		];

		// Update database.
		$result = $this->comment_author_table->insert( $columns );
		if ( ! empty( $result ) ) {
			// Clear any previously cached value, just in case.
			$this->update_hash( $email, true );
		}

		return $result;
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
			$this->insert( $email, $use_gravatar, $this->get_log_message( $comment_id ) );
		} else {
			if ( $data->use_gravatar !== $use_gravatar ) {
				// Dataset found but with different value, update it.
				$new_values = [
					'use_gravatar' => $use_gravatar,
					'log_message'  => $this->get_log_message( $comment_id ),
				];
				$this->update( $data->id, $data->email, $new_values );
			}

			// We might also need to update the hash.
			if ( empty( $data->hash ) ) {
				$this->update_hash( $data->email );
			}
		}
	}

	/**
	 * Updates the hash for the comment author email. Also clears the cache if
	 * necessary.
	 *
	 * @param  string $email       The email.
	 * @param  bool   $clear_cache Optional. Force the cache to be cleared. Default false.
	 *
	 * @return int|false     The number of rows updated, or false on error.
	 */
	public function update_hash( $email, $clear_cache = false ) {
		$hash = $this->get_hash( $email );
		$data = [
			'identifier' => $email,
			'hash'       => $hash,
			'type'       => 'comment',
		];

		// Update database.
		$result = $this->hashes_table->replace( $data );

		// Check whether we need to clear the cache.
		if ( $clear_cache || ! empty( $result ) ) {
			$this->clear_cache( $hash, 'hash' );
		}

		return $result;
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

	/**
	 * Deletes the data for the comment author identified by an email address.
	 *
	 * @param  string $email The comment author's e-mail address or the unique hash.
	 *
	 * @return int|false     The number of rows deleted, or false on error.
	 */
	public function delete( $email ) {
		$comment_author_rows = $this->comment_author_table->delete( [ 'email' => $email ] );
		$hashes_rows         = $this->hashes_table->delete(
			[
				'identifier' => $email,
				'type'       => 'comment',
			]
		);

		if ( ! empty( $comment_author_rows ) || ! empty( $hashes_rows ) ) {
			$this->clear_cache( $email );

			return \max( (int) $comment_author_rows, (int) $hashes_rows );
		}

		return false;
	}
}
