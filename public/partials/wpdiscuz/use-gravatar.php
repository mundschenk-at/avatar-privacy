<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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
use Avatar_Privacy\Tools\Template;

// Determine if the checkbox should be checked.
$cookie_name = Comments::COOKIE_PREFIX . COOKIEHASH;
$is_checked  = false;
if ( isset( $_POST[ Comments::CHECKBOX_FIELD_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontend form.
	// Re-displaying the comment form with validation errors.
	$is_checked = ! empty( $_POST[ Comments::CHECKBOX_FIELD_NAME ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- frontend form.
} elseif ( isset( $_COOKIE[ $cookie_name ] ) ) {
	// Read the value from the cookie, saved with previous comment.
	$is_checked = \filter_input( INPUT_COOKIE, $cookie_name, FILTER_VALIDATE_BOOLEAN );
}
?>
<!-- div class="comment-form-use-gravatar wpdiscuz-item wpd-field-group wpd-field-checkbox wpd-field-single wpd-has-desc" -->
<div class="comment-form-use-gravatar wpdiscuz-item wpd-field-group wpd-field-checkbox wpd-field-single wpd-has-desc">
	<div class="wpd-field-group-title">
		<div class="wpd-item">
			<input id="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" name="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" class="wpd-field" type="checkbox" value="true" <?php \checked( $is_checked, true ); ?> />
			<label
				class="wpd-field-label wpd-cursor-pointer"
				for="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>"
			><?php echo \wp_kses( \sprintf( /* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */ \__( 'Display a <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a> image next to my comments.', 'avatar-privacy' ), \__( 'https://en.gravatar.com/', 'avatar-privacy' ), Template::get_gravatar_link_rel(), Template::get_gravatar_link_target() ), Template::ALLOWED_HTML_LABEL ); ?></label>
		</div>
	</div>
	<div class="wpd-field-desc">
		<i class="far fa-question-circle" aria-hidden="true"></i><span><?php \esc_attr_e( 'If checked, an MD5 hash of your email address will be shared with Gravatar.com. However, that hash will not be made public.', 'avatar-privacy' ); ?></span>
	</div>
</div>
<?php
