<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use function Avatar_Privacy\get_gravatar_checkbox;

if ( ! \function_exists( 'avapr_get_avatar_checkbox' ) ) {

	/**
	 * Returns the 'use gravatar' checkbox for the comment form.
	 *
	 * This is intended as a template function for older or highly-customized
	 * themes. Output the result with echo or print.
	 *
	 * @deprecated 2.3.0 Use \Avatar_Privacy\get_gravatar_checkbox instead.
	 *
	 * @return string The HTML code for the checkbox or an empty string.
	 */
	function avapr_get_avatar_checkbox() {

		\_deprecated_function( __FUNCTION__, '2.3.0', 'Avatar_Privacy\get_gravatar_checkbox' );

		return get_gravatar_checkbox();
	}
}

/**
 * PHP 5.2 compatibility layer.
 *
 * Will be removed once the minimum requireemnt is WordPress 5.2.
 */
class_alias( \Avatar_Privacy\Factory::class, \Avatar_Privacy_Factory::class );
