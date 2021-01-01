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

use Avatar_Privacy\Components\User_Profile;
use Avatar_Privacy\Tools\Template as T;

/**
 * Template for bbPress use_gravatar checkbox.
 *
 * Required template variables:
 *
 * @var T      $template   The templating helper.
 * @var string $nonce      The nonce itself.
 * @var string $action     The nonce action.
 * @var string $field_name The name of the checkbox `<input>` element.
 * @var string $value      The checkbox value.
 */
?>
<div class="avatar-privacy-use-gravatar">
	<?php \wp_nonce_field( $action, $nonce ); ?>
	<label>
		<input
			id="<?php echo \esc_attr( $field_name ); ?>"
			name="<?php echo \esc_attr( $field_name ); ?>"
			class="checkbox"
			type="checkbox"
			value="true"
			<?php \checked( $value ); ?>
		/>
		<?php echo \wp_kses( $template->get_use_gravatar_label(), T::ALLOWED_HTML_LABEL ); ?>
	</label>
	<span class="description indicator-hint" style="width:100%;margin-left:0;">
		<?php \esc_html_e( 'An uploaded profile picture takes precedence over your gravatar.', 'avatar-privacy' ); ?>
	</span>
</div>
<?php
