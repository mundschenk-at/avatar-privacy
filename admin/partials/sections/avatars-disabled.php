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

$show_avatars_class = empty( $show_avatars ) ? '' : ' hide-if-js';

?>
<div class="avatar-settings-disabled<?php echo $show_avatars_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<p>
		<?php \esc_html_e( "The Avatar Privacy plugin modifies the display of avatars. You have not enabled avatars, so this plugin can't do anything for you. :-)", 'avatar-privacy' ); ?>
	</p>
	<p>
		<?php \esc_html_e( 'You can enable profile pictures for users and commenters by selecting "Show Avatars" above. Then you will also see the features enabled by the Avatar Privacy plugin here.', 'avatar-privacy' ); ?>
	</p>
</div>
<?php
