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
	 * @param  \Avatar_Privacy_Core $core    The core API.
	 *
	 * @return string
	 */
	public function get_icon_url( $url, $email, $size, $user_id, \Avatar_Privacy_Core $core ) {
		$type         = false !== $user_id ? 'a' : 'b';
		$filename     = "gravatar/{$type}/{$core->get_hash( $email )}-{$size}.png";
		$gravatar_url = "https://secure.gravatar.com/avatar/{$this->get_gravatar_hash( $email )}.png?s={$size}&d=404";
		$icon         = \wp_remote_retrieve_body( \wp_remote_get( $gravatar_url ) );

		if ( ! empty( $icon ) && $this->file_cache->set( $filename, $icon ) ) {
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
}
