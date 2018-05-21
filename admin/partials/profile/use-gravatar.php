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

// Allowed HTML tags in the checkbox label.
$allowed_html = [
	'a' => [
		'href'   => true,
		'rel'    => true,
		'target' => true,
	],
];

?>
<tr class"avatar-privacy-use-gravatar">
	<th scope="row"><?php esc_html_e( 'Gravatars', 'avatar-privacy' ); ?></th>
	<td>
		<?php \wp_nonce_field( self::ACTION_EDIT_USE_GRAVATAR, self::NONCE_USE_GRAVATAR . $user->ID ); ?>
		<input
			id="<?php echo esc_attr( self::CHECKBOX_FIELD_NAME ); ?>"
			name="<?php echo esc_attr( self::CHECKBOX_FIELD_NAME ); ?>"
			type="checkbox"
			value="true"
			<?php checked( $value ); ?>
		/>
		<label for="<?php echo esc_attr( self::CHECKBOX_FIELD_NAME ); ?>"><?php echo wp_kses( sprintf( /* translators: gravatar.com URL */ __( 'Display a <a href="%s">Gravatar</a> image for my e-mail address.', 'avatar-privacy' ), __( 'https://gravatar.com', 'avatar-privacy' ) ), $allowed_html ); ?></label><br />
		<p class="description">
			<?php esc_html_e( "Uncheck this box if you don't want to display the gravatar for your e-mail address (or don't have an account on Gravatar.com).", 'avatar-privacy' ); ?>
			<?php esc_html_e( 'This setting will only take effect if you have not uploaded a local profile picture.', 'avatar-privacy' ); ?>
		</p>
	</td>
</tr>
<?php
