<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2021 Peter Putzer.
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

namespace Avatar_Privacy\Tools\Network;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Database\Hashes_Table;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Editor;

/**
 * A class for accessing the generic remote images.
 *
 * @since      2.3.4
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Remote_Image_Service {

	/**
	 * The cache handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * The hashing helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * The images editor.
	 *
	 * @since 2.4.0
	 *
	 * @var Editor
	 */
	private $editor;

	/**
	 * The hashes database table.
	 *
	 * @since 2.4.0
	 *
	 * @var Hashes_Table
	 */
	private $table;

	const IDENTIFIER_TYPE    = 'image-url';
	const URL_CACHE_DURATION = 24 * \HOUR_IN_SECONDS;
	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0
	 *
	 * @param Cache        $cache  The cache helper.
	 * @param Hasher       $hasher The hashing helper.
	 * @param Editor       $editor The image editor.
	 * @param Hashes_Table $table  The database table for storing hash <=> URL mappings.
	 */
	public function __construct( Cache $cache, Hasher $hasher, Editor $editor, Hashes_Table $table ) {
		$this->cache  = $cache;
		$this->hasher = $hasher;
		$this->editor = $editor;
		$this->table  = $table;
	}

	/**
	 * Retrieves the remote image.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $url      The image URL.
	 * @param  int    $size     The image size in pixels.
	 * @param  string $mimetype The expected MIME type of the image data.
	 *
	 * @return string      The image data (or an empty string on error).
	 */
	public function get_image( $url, $size, $mimetype ) {
		// Retrieve remote image.
		$image = \wp_remote_retrieve_body(
			\wp_remote_get( $url )
		);

		// Check if the image data is valid.
		if ( false === $this->editor->get_mime_type( $image ) ) {
			// Something went wrong, so we ignore the data.
			return '';
		}

		// Resize and convert image.
		return $this->editor->get_resized_image_data(
			$this->editor->create_from_string( $image ), $size, $size, $mimetype
		);
	}

	/**
	 * Checks that the given string is a valid image URL.
	 *
	 * @since 2.3.4
	 *
	 * @param  string $maybe_url Possibly an image URL.
	 * @param  string $context   The URL context (e.g. `'default_icon'` or `'avatar'`).
	 *
	 * @return bool
	 */
	public function validate_image_url( $maybe_url, $context ) {
		/**
		 * Filters whether remote default icon URLs (i.e. having a different domain) are allowed.
		 *
		 * @since 2.3.4
		 *
		 * @param bool $allow Default false.
		 */
		$allow_remote = \apply_filters( "avatar_privacy_allow_remote_{$context}_url", false );

		// Get current site domain part (without schema).
		$domain = \wp_parse_url( \get_site_url(), \PHP_URL_HOST );

		// Make sure URL is valid and local (unless $allow_remote is set to true).
		$result =
			\filter_var( $maybe_url, \FILTER_VALIDATE_URL, \FILTER_FLAG_PATH_REQUIRED ) &&
			( $allow_remote || \wp_parse_url( $maybe_url, \PHP_URL_HOST ) === $domain );

		/**
		 * Filters the result of checking whether the candidate URL is a valid image URL.
		 *
		 * @since 2.3.4
		 *
		 * @param bool   $result       The validation result.
		 * @param string $maybe_url    The candidate URL.
		 * @param bool   $allow_remote Whether URLs from other domains should be allowed.
		 */
		return \apply_filters( "avatar_privacy_validate_{$context}_url", $result, $maybe_url, $allow_remote );
	}

	/**
	 * Retrieves the hash for the given image URL. This method ensures that a reverse
	 * lookup using is possible by storing the URL and the hash in a database table.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $url The remote image URL.
	 *
	 * @return string
	 */
	public function get_hash( $url ) {
		// Generate hash.
		$hash = $this->hasher->get_hash( $url );

		// Check cache.
		$key = "image-url_{$hash}";
		if ( $url !== $this->cache->get( $key ) ) {
			// OK, we need to update the database.
			$data = [
				'identifier' => $url,
				'hash'       => $hash,
				'type'       => self::IDENTIFIER_TYPE,
			];
			$this->table->replace( $data );

			// Also prime the URL cache, just in case.
			$this->cache->set( $key, $url, self::URL_CACHE_DURATION );
		}

		return $hash;
	}

	/**
	 * Retrieves the image URL for the given hash value.
	 *
	 * @since 2.4.0
	 *
	 * @global \wpdb $wpdb  The WordPress Database Access Abstraction.
	 *
	 * @param  string $hash The hashed URL.
	 *
	 * @return string|false
	 */
	public function get_image_url( $hash ) {
		global $wpdb;

		// Check cache.
		$key = "image-url_{$hash}";
		$url = $this->cache->get( $key );

		if ( false === $url ) {
			// Lookup image URL.
			$url = $wpdb->get_var( $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT identifier FROM `{$this->table->get_table_name()}` WHERE hash = %s AND type = %s",
				$hash,
				self::IDENTIFIER_TYPE
			) ); // WPCS: db call ok, cache ok.

			// Store only positive results.
			if ( ! empty( $url ) ) {
				$this->cache->set( $key, $url, self::URL_CACHE_DURATION );
			}
		}

		return $url;
	}
}
