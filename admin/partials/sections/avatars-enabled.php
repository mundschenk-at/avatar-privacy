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

$show_avatars_class = ! empty( $show_avatars ) ? '' : ' hide-if-js';
$allowed_html       = [ 'strong' => [] ];

?>
<div class="avatar-settings-enabled<?php echo $show_avatars_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<p>
		<?php \esc_html_e( 'To protect the privacy of your users, visitors and commenters, the Avatar Privacy plugin has enabled the following features:', 'avatar-privacy' ); ?>
	</p>
	<ul class="ul-disc">
		<li><strong><?php \esc_html_e( 'Consent:', 'avatar-privacy' ); ?></strong> <?php \esc_html_e( 'We only try to load an avatar from Gravatar.com when users and commenters have explicitly consented.', 'avatar-privacy' ); ?></li>
		<li><strong><?php \esc_html_e( 'Check:', 'avatar-privacy' ); ?></strong> <?php \esc_html_e( 'Even when people opt-in, we check if they really have a gravatar associated with their e-mail address.', 'avatar-privacy' ); ?></li>
		<li><strong><?php \esc_html_e( 'Cache:', 'avatar-privacy' ); ?></strong> <?php \esc_html_e( 'To prevent tracking of your visitors, gravatars are cached locally and all default images are hosted on your server.', 'avatar-privacy' ); ?></li>
	</ul>
</div>
<?php
