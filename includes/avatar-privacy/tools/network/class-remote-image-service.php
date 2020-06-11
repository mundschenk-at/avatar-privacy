<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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

/**
 * A class for accessing the generic remote images.
 *
 * @since      2.3.4
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Remote_Image_Service {

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
}
