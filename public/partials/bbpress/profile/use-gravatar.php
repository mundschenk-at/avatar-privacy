<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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
use Avatar_Privacy\Tools\Template;

?>
<div class="avatar-privacy-use-gravatar">
	<?php \wp_nonce_field( User_Profile::ACTION_EDIT_USE_GRAVATAR, User_Profile::NONCE_USE_GRAVATAR . $user_id ); ?>
	<label>
		<input
			id="<?php echo \esc_attr( User_Profile::CHECKBOX_FIELD_NAME ); ?>"
			name="<?php echo \esc_attr( User_Profile::CHECKBOX_FIELD_NAME ); ?>"
			class="checkbox"
			type="checkbox"
			value="true"
			<?php \checked( $use_gravatar ); ?>
		/>
		<?php echo \wp_kses( \sprintf( /* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */ \__( 'Display a <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a> image for my e-mail address.', 'avatar-privacy' ), \__( 'https://en.gravatar.com/', 'avatar-privacy' ), Template::get_gravatar_link_rel(), Template::get_gravatar_link_target() ), Template::ALLOWED_HTML_LABEL ); ?>
	</label>
	<span class="description indicator-hint" style="width:100%;margin-left:0;">
		<?php \esc_html_e( 'An uploaded profile picture takes precedence over your gravatar.', 'avatar-privacy' ); ?>
	</span>
<div>
<?php
