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

namespace Avatar_Privacy;

use Avatar_Privacy\Components\Comments;

/**
 * Returns the 'use gravatar' checkbox for the comment form.
 *
 * This is intended as a template function for older or highly-customized
 * themes.  Modern themes should no need to use it. Output the result with echo
 * or print.
 *
 * @since 2.3.0
 *
 * @return string The HTML code for the checkbox or an empty string.
 */
function get_gravatar_checkbox() {
	// The checkbox is meaningless for logged-in users.
	if ( \is_user_logged_in() ) {
		return '';
	}

	return Comments::get_gravatar_checkbox();
}

/**
 * Prints the 'use gravatar' checkbox for the comment form.
 *
 * This is intended as a template function for older or highly-customized
 * themes. Modern themes should no need to use it.
 *
 * @since 2.3.0
 *
 * @return void
 */
function gravatar_checkbox() {
	echo get_gravatar_checkbox(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
}
