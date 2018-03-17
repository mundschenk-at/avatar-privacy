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
<input
	id="avatar_privacy_optin"
	name="avatar_privacy_settings[mode_optin]"
	type="checkbox"
	value="1"
	<?php checked( $options['mode_optin'] ); ?>
/>
<label for="avatar_privacy_optin"><strong><?php esc_html_e( 'Let users and commenters opt in or out of using gravatars.', 'avatar-privacy' ); ?></strong></label><br />
<?php if ( $this->core->is_default_avatar_dynamic() ) : ?>
<p>
	<strong style="font-color: #FF0000;"><?php esc_html_e( 'Warning:', 'avatar-privacy' ); ?></strong>
	<?php esc_html_e( 'This mode does not work well with dynamic default images because for users without a gravatar image, a difference will be visible between users who opted out (no image) and other users (dynamic default). If you want all users without a gravatar to look the same, please change the settings to a static default image.', 'avatar-privacy' ); ?>
</p>
<?php endif; ?>
<p class="description">
	<?php esc_html_e( 'Commenters will see a checkbox to enable or disable the use of gravatars for their comments. Users will have the same option in their user profile.', 'avatar-privacy' ); ?>
	<?php esc_html_e( "For both users and commenters, the selection is saved globally for all comments (+on all blogs for multisite installations). Gravatars can't be enabled/disabled on a per-comment basis. For commenters, the decision is saved in a cookie.", 'avatar-privacy' ); ?>
</p>
<p class="description">
	<span style="font-weight: bold;"><?php esc_html_e( 'Advantage:', 'avatar-privacy' ); ?></span>
	<?php esc_html_e( 'Users and commenters can decide if they want the MD5 of their e-mail address to be published. Commenters can change their mind by leaving another comment, users can change the setting in their user profile.', 'avatar-privacy' ); ?>
</p>
<?php
