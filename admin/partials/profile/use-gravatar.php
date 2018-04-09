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

?>
<h3><?php esc_html_e( 'Use Gravatar', 'avatar-privacy' ); ?></h3>
<table class="form-table">
	<tr>
		<th scope="row"><?php esc_html_e( 'Gravatars', 'avatar-privacy' ); ?></th>
		<td>
			<input
				id="<?php echo esc_attr( \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ); ?>"
				name="<?php echo esc_attr( \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ); ?>"
				type="checkbox"
				value="true"
				<?php checked( $val ); ?>
			/>
			<label for="<?php echo esc_attr( \Avatar_Privacy_Core::CHECKBOX_FIELD_NAME ); ?>"><?php echo wp_kses( __( 'Display a <a href="https://gravatar.com">gravatar</a> image for my e-mail address', 'avatar-privacy' ), [ 'a' => [ 'href' => true ] ] ); ?></label><br />
			<span class="description"><?php esc_html_e( "Uncheck this box if you don't want to display a gravatar for your E-Mail address.", 'avatar-privacy' ); ?></span>
		</td>
	</tr>
</table>
<?php
