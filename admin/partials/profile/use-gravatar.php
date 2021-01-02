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

use Avatar_Privacy\Tools\Template as T;

/**
 * Required template variables:
 *
 * @var T      $template   The templating helper.
 * @var string $nonce      The nonce itself.
 * @var string $action     The nonce action.
 * @var string $field_name The name of the checkbox `<input>` element.
 * @var string $value      The checkbox value.
 */
?>
<tr class="avatar-privacy-use-gravatar">
	<th scope="row"><?php \esc_html_e( 'Gravatars', 'avatar-privacy' ); ?></th>
	<td>
		<?php \wp_nonce_field( $action, $nonce ); ?>
		<input
			id="<?php echo \esc_attr( $field_name ); ?>"
			name="<?php echo \esc_attr( $field_name ); ?>"
			type="checkbox"
			value="true"
			<?php \checked( $value ); ?>
		/>
		<label for="<?php echo \esc_attr( $field_name ); ?>"><?php echo \wp_kses( $template->get_use_gravatar_label( 'user' ), T::ALLOWED_HTML_LABEL ); ?></label><br />
		<p class="description">
			<?php \esc_html_e( "Uncheck this box if you don't want to display the gravatar for your e-mail address (or don't have an account on Gravatar.com).", 'avatar-privacy' ); ?>
			<?php \esc_html_e( 'This setting will only take effect if you have not uploaded a local profile picture.', 'avatar-privacy' ); ?>
		</p>
	</td>
</tr>
<?php
