<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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
use Avatar_Privacy\Tools\Template as T;

/**
 * Required template variables:
 *
 * @var T    $template   The templating helper.
 * @var bool $is_checked Whether the checkbox should be pre-checked.
 */

/**
 * Filters whether `style="display:inline;"` should be added to the label of the
 * `use_gravatar` checkbox in the comments form.
 *
 * @param bool $disable Default false.
 */
$disable_style = \apply_filters( 'avatar_privacy_comment_checkbox_disable_inline_style', false );
?>
<p class="comment-form-use-gravatar">
	<input id="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" name="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>" type="checkbox" value="true" <?php \checked( $is_checked, true ); ?>
	<?php if ( ! $disable_style ) : ?>
		style="margin-right:1ex;"
	<?php endif; ?>
	/><label
	<?php if ( ! $disable_style ) : ?>
		style="display:inline;"
	<?php endif; ?>
		for="<?php echo \esc_attr( Comments::CHECKBOX_FIELD_NAME ); ?>"
	><?php echo \wp_kses( $template->get_use_gravatar_label( 'comment' ), T::ALLOWED_HTML_LABEL ); ?></label>
</p>
<?php
