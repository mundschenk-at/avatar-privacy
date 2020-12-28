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

use Avatar_Privacy\Components\Network_Settings_Page;

?><div class='wrap'>
	<h1><?php \esc_html_e( 'Avatar Privacy Network Settings', 'avatar-privacy' ); ?></h1>

	<form method="post" action="<?php echo \esc_url( 'edit.php?action=' . Network_Settings_Page::ACTION ); ?>">
		<?php \settings_fields( Network_Settings_Page::OPTION_GROUP ); ?>
		<?php \do_settings_sections( Network_Settings_Page::OPTION_GROUP ); ?>

		<p class="submit">
			<?php \submit_button( \__( 'Save Changes', 'avatar-privacy' ), 'primary', 'save_changes', false, [ 'tabindex' => 1 ] ); ?>
		</p><!-- .submit -->
	</form>

</div><!-- .wrap -->
<?php
