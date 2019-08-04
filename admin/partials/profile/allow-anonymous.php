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

?>
<tr class"avatar-privacy-allow-anonymous">
	<th scope="row"><?php \esc_html_e( 'Logged-out Commenting', 'avatar-privacy' ); ?></th>
	<td>
		<?php \wp_nonce_field( self::ACTION_EDIT_ALLOW_ANONYMOUS, self::NONCE_ALLOW_ANONYMOUS . $user->ID ); ?>
		<input
			id="<?php echo \esc_attr( self::CHECKBOX_ALLOW_ANONYMOUS ); ?>"
			name="<?php echo \esc_attr( self::CHECKBOX_ALLOW_ANONYMOUS ); ?>"
			type="checkbox"
			value="true"
			<?php \checked( $allow_anonymous ); ?>
		/>
		<label for="<?php echo \esc_attr( self::CHECKBOX_ALLOW_ANONYMOUS ); ?>"><?php \esc_html_e( 'Allow logged-out comments with my profile picture.', 'avatar-privacy' ); ?></label><br />
		<p class="description">
			<?php \esc_html_e( 'Check this box if you want to be able to use your profile picture while logged-out.', 'avatar-privacy' ); ?>
		</p>
	</td>
</tr>
<?php
