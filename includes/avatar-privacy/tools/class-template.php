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

namespace Avatar_Privacy\Tools;

/**
 * A collection of utility methods for use in templates.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Template {

	/**
	 * Retrieves and filters the `rel` attribute for links to gravatar.com.
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_rel() {
		/**
		 * Filters the `rel` attribute for user-visible links to gravatar.com.
		 *
		 * @param string $rel Default 'noopener nofollow'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_rel', 'noopener nofollow' ) );
	}

	/**
	 * Retrieves and filters the `target` attribute for links to gravatar.com.
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_target() {
		/**
		 * Filters the `target` attribute for user-visible links to gravatar.com.
		 *
		 * @param string $target Default '_self'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_target', '_self' ) );
	}
}
