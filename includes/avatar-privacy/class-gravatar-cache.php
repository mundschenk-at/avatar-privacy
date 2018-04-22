<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * An icon provider for caching Gravatar.com images.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Gravatar_Cache {

	const TYPE_USER    = 'user';
	const TYPE_COMMENT = 'comment';

	const TYPE_MAPPING = [
		'0' => self::TYPE_USER,
		'1' => self::TYPE_COMMENT,
		'2' => self::TYPE_USER,
		'3' => self::TYPE_COMMENT,
		'4' => self::TYPE_USER,
		'5' => self::TYPE_COMMENT,
		'6' => self::TYPE_USER,
		'7' => self::TYPE_COMMENT,
		'8' => self::TYPE_USER,
		'9' => self::TYPE_COMMENT,
		'a' => self::TYPE_USER,
		'b' => self::TYPE_COMMENT,
		'c' => self::TYPE_USER,
		'd' => self::TYPE_COMMENT,
		'e' => self::TYPE_USER,
		'f' => self::TYPE_COMMENT,
	];

	const REVERSE_TYPE_MAPPING = [
		true  => 'a', // TYPE_USER.
		false => 'b', // TYPE_COMMENT.
	];

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Creates a new instance.
	 *
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( Filesystem_Cache $file_cache ) {
		$this->file_cache = $file_cache;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string               $url     The fallback default icon URL.
	 * @param  string               $email   The mail address used to generate the identity hash.
	 * @param  int                  $size    The requested size in pixels.
	 * @param  int|false            $user_id A WordPress user ID, or false.
	 * @param  string               $rating  The audience rating (e.g. 'g', 'pg', 'r', 'x').
	 * @param  \Avatar_Privacy_Core $core    The core API.
	 * @param  bool                 $force   Optional. Whether to force the regeneration of the icon. Default false.
	 *
	 * @return string
	 */
	public function get_icon_url( $url, $email, $size, $user_id, $rating, \Avatar_Privacy_Core $core, $force = false ) {
		$hash         = $core->get_hash( $email );
		$subdir       = $this->get_sub_dir( $hash, false !== $user_id );
		$filename     = "gravatar/{$subdir}/{$hash}-{$size}.png";
		$gravatar_url = "https://secure.gravatar.com/avatar/{$this->get_gravatar_hash( $email )}.png?s={$size}&r={$rating}&d=404";

		// Only retrieve new Gravatar if necessary.
		if ( ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) || $force ) {
			$icon = \wp_remote_retrieve_body( /* @scrutinizer ignore-type */ \wp_remote_get( $gravatar_url ) );

			// Store icon.
			if ( ! empty( $icon ) && $this->file_cache->set( $filename, $icon ) ) {
				$url = $this->file_cache->get_url( $filename );
			}
		} else {
			$url = $this->file_cache->get_url( $filename );
		}

		return $url;
	}

	/**
	 * Creates a hash from the given mail address using the SHA-256 algorithm.
	 *
	 * @param  string $email An email address.
	 *
	 * @return string
	 */
	public function get_gravatar_hash( $email ) {
		return \md5( \strtolower( \trim( $email ) ) );
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  bool   $user     If we need to encode the type as "user".
	 *
	 * @return string
	 */
	private function get_sub_dir( $identity, $user = false ) {
		$first  = \substr( $identity, 0, 1 );
		$second = \substr( $identity, 1, 1 );

		if ( ( $user && self::TYPE_USER === self::TYPE_MAPPING[ $first ] ) || ( ! $user && self::TYPE_COMMENT === self::TYPE_MAPPING[ $first ] ) ) {
			$levels = [ $first, $second ];
		} else {
			$levels = [ self::REVERSE_TYPE_MAPPING[ $user ], $second ];
		}

		return \implode( '/', $levels );
	}
}
