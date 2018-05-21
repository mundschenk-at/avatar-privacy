<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

use Avatar_Privacy\Components\Comments;

// Allowed HTML tags in the checkbox label.
$allowed_html = [
	'a' => [
		'href'   => true,
		'rel'    => true,
		'target' => true,
	],
];

/**
 * Filters whether `style="display:inline;"` should be added to the label of the
 * `use_gravatar` checkbox in the comments form.
 *
 * @param bool $disable Default false.
 */
$disable_style = \apply_filters( 'avatar_privacy_comment_checkbox_disable_inline_style', false );

// Determine if the checkbox should be checked.
$is_checked = false;
if ( isset( $_POST[ Comments::CHECKBOX_FIELD_NAME ] ) ) { // WPCS: CSRF ok, Input var okay.
	// Re-displaying the comment form with validation errors.
	$is_checked = ! empty( $_POST[ Comments::CHECKBOX_FIELD_NAME ] ); // WPCS: CSRF ok, Input var okay.
} elseif ( isset( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ) ) { // Input var okay.
	// Read the value from the cookie, saved with previous comment.
	$is_checked = ! empty( $_COOKIE[ 'comment_use_gravatar_' . COOKIEHASH ] ); // Input var okay.
}
?>
<p class="comment-form-use-gravatar">
	<input id="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" name="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" type="checkbox" value="true" <?php \checked( $is_checked, true, false ); ?> /><label
	<?php if ( ! $disable_style ) : ?>
		style="display:inline;"
	<?php endif; ?>
		for="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>"
	><?php \printf( /* translators: gravatar.com URL */ \wp_kses( \__( 'Display a <a href="%s" rel="noopener nofollow">Gravatar</a> image next to my comments.', 'avatar-privacy' ), $allowed_html ), 'https://gravatar.com' ); ?></label>
</p>
<?php
