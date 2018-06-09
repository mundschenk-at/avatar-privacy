<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

// Allowed HTML tags in the checkbox label.
$allowed_html = [
	'a' => [
		'href'   => true,
		'rel'    => true,
		'target' => true,
	],
];
?>
<div class="avatar-privacy-use-gravatar">
	<?php \wp_nonce_field( User_Profile::ACTION_EDIT_USE_GRAVATAR, User_Profile::NONCE_USE_GRAVATAR . $user_id ); ?>
	<label>
		<input
			id="<?php echo esc_attr( User_Profile::CHECKBOX_FIELD_NAME ); ?>"
			name="<?php echo esc_attr( User_Profile::CHECKBOX_FIELD_NAME ); ?>"
			class="checkbox"
			type="checkbox"
			value="true"
			<?php checked( $use_gravatar ); ?>
		/>
		<?php echo wp_kses( sprintf( /* translators: gravatar.com URL */ __( 'Display a <a href="%s" rel="noopener nofollow">Gravatar</a> image for my e-mail address.', 'avatar-privacy' ), __( 'https://en.gravatar.com/', 'avatar-privacy' ) ), $allowed_html ); ?>
	</label>
	<span class="description indicator-hint" style="width:100%;margin-left:0;">
		<?php \esc_html_e( 'An uploaded profile picture takes precedence over your gravatar.', 'avatar-privacy' ); ?>
	</span>
<div>
<?php
