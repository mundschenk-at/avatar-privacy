<?php
/**
 * Procedural part of Avatar Privacy.
 *
 * @package mundschenk-at/avatar-privacy
 * @author Peter Putzer <github@mundschenk.at>
 * @copyright 2018-2020 Peter Putzer
 * @license GPL-2.0-or-later https://github.com/mundschenk-at/avatar-privacy/blob/master/LICENSE.md
 * @link https://code.mundschenk.at/avatar-privacy/
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
