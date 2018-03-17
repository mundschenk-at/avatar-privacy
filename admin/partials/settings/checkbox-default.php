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
	id="avatar_privacy_checkbox_default_true"
	name="avatar_privacy_settings[checkbox_default]"
	type="radio"
	value="1"
	<?php checked( $options['checkbox_default'], 1 ); ?>
/>
<label for="avatar_privacy_checkbox_default_true"><?php esc_html_e( 'checked by default', 'avatar-privacy' ); ?></label> <span class="description">(<?php esc_html_e( 'commenters and users can opt out by unchecking it', 'avatar-privacy' ); ?>)</span><br />
<input
	id="avatar_privacy_checkbox_default_false"
	name="avatar_privacy_settings[checkbox_default]"
	type="radio"
	value="0"
	<?php checked( $options['checkbox_default'], 0 ); ?>
/>
<label for="avatar_privacy_checkbox_default_false"><?php esc_html_e( 'not checked by default', 'avatar-privacy' ); ?></label> <span class="description">(<?php esc_html_e( 'commenters and users can opt in by checking it', 'avatar-privacy' ); ?>)</span><br />
<?php
