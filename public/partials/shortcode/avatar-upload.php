<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * Required template variables:
 *
 * @var User_Form $form    The form helper.
 * @var int       $user_id The ID of the user whose profile we are editing.
 * @var array     $atts {
 *     The shortcode attributes.
 *
 *     @type int $avatar_size The width/height of the avatar preview image (in pixels).
 * }
 */
?>
<form class="avatar-privacy-frontend avatar-privacy-shortcode" method="post" enctype="multipart/form-data">
	<?php $form->avatar_uploader( $user_id, $atts['avatar_size'] ); ?>
	<?php $form->use_gravatar_checkbox( $user_id ); ?>
	<?php $form->allow_anonymous_checkbox( $user_id ); ?>
	<input type="submit" value="<?php \esc_attr_e( 'Save', 'avatar-privacy' ); ?>" />
</form>
<?php
