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
	id="avatar_privacy_default_show_true"
	name="avatar_privacy_settings[default_show]"
	type="radio"
	value="1"
	<?php checked( $options['default_show'], 1 ); ?>
/>
<label for="avatar_privacy_default_show_true"><?php esc_html_e( 'Show gravatars, except if users/commenters opted out', 'avatar-privacy' ); ?></label><br />
<input
	id="avatar_privacy_default_show_false"
	name="avatar_privacy_settings[default_show]"
	type="radio"
	value="0"
	<?php checked( $options['default_show'], 0 ); ?>
/>
<label for="avatar_privacy_default_show_false"><?php esc_html_e( "Don't show gravatars, except if users/commenters opted in", 'avatar-privacy' ); ?></label><br />
<p class="description">
	<?php esc_html_e( "Regulates whether to show or not to show gravatars for commenters and users who haven't saved any preference. This is relevant for old comments from before activating the plugin and users who did not save their profile after the plugin was activated.", 'avatar-privacy' ); ?>
</p>
<?php
