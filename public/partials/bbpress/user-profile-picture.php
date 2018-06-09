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

use Avatar_Privacy\Integrations\BBPress_Integration;

?>
<h2 class="entry-title"><?php \esc_html_e( 'Profile Picture', 'avatar-privacy' ); ?></h2>
<fieldset class="bbp-form">
	<legend><?php \esc_html_e( 'Profile Picture', 'avatar-privacy' ); ?></legend>
	<?php require __DIR__ . '/profile/user-avatar-upload.php'; ?>
	<?php require __DIR__ . '/profile/use-gravatar.php'; ?>
</fieldset>
<?php
