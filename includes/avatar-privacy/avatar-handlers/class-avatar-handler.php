<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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

namespace Avatar_Privacy\Avatar_Handlers;

/**
 * Specifies an interface for handling avatar retrieval and caching.
 *
 * @since 2.0.0
 * @since 2.4.0 Internal constants removed.
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type AvatarArguments array{ force?: bool, ... }
 */
interface Avatar_Handler {

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @since  2.7.0 Removed argument index 'type' as it is not required for all implemntations.
	 *
	 * @param  string $url  The fallback image URL.
	 * @param  string $hash The hashed mail address.
	 * @param  int    $size The size of the avatar image in pixels.
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type bool $force Optional. Whether to force the regeneration of the image file. Default false.
	 * }
	 *
	 * @return string
	 *
	 * @phpstan-param AvatarArguments $args
	 */
	public function get_url( $url, $hash, $size, array $args );

	/**
	 * Caches the image specified by the parameters.
	 *
	 * @param  string $type      The image (sub-)type.
	 * @param  string $hash      The hashed mail address.
	 * @param  int    $size      The requested size in pixels.
	 * @param  string $subdir    The requested sub-directory (may contain implementation-specific data).
	 * @param  string $extension The requested file extension.
	 *
	 * @return bool              Returns `true` if successful, `false` otherwise.
	 */
	public function cache_image( $type, $hash, $size, $subdir, $extension );

	/**
	 * Retrieves the name of the cache directory for avatars provided by this handler
	 * (e.g. 'gravatar'). Implementations may return an empty string if the actual
	 * type can vary.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_type();
}
