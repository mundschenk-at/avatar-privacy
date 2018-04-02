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
	id="avatar_privacy_checkforgravatar"
	name="avatar_privacy_settings[mode_checkforgravatar]"
	type="checkbox"
	value="1"
	<?php checked( $options['mode_checkforgravatar'] ); ?>
/>
<label for="avatar_privacy_checkforgravatar"><strong><?php esc_html_e( "Don't publish encrypted e-mail addresses for non-members of gravatar.com.", 'avatar-privacy' ); ?></strong></label><br />
<p class="description">
	<?php esc_html_e( 'The plugin will check if a gravatar exists for a given e-mail address. If a gravatar exists, it is displayed as usual. If no gravatar exists, the default image is displayed directly instead of as a redirect from the non-existing gravatar image.', 'avatar-privacy' ); ?>
	<?php esc_html_e( "The check is done on your server, not in the visitor's browser. If your site has many visitors, you should keep an eye on whether your server is ok with the calls to gravatar.com.", 'avatar-privacy' ); ?>
</p>
<p class="description">
	<span style="font-weight: bold;"><?php esc_html_e( 'Advantage:', 'avatar-privacy' ); ?></span>
	<?php esc_html_e( "You are not publicly publishing encrypted e-mail addresses of people who have not actually signed up with gravatar.com. Gravatar.com will still get the encrypted address though. This reduces the possibility to track all these users' comments all over the web.", 'avatar-privacy' ); ?>
	<?php esc_html_e( "It also removes the possibility that the e-mail address is reverse engineered (e.g. guessed) out of the MD5 token. That's good, since you probably promised not to publish the e-mail address somewhere around your comment form.", 'avatar-privacy' ); ?>
</p>
<?php
