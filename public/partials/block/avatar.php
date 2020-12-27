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
 * @var \WP_User $user       The user object whose avatar should be displayed.
 * @var int      $size       The width/height of the avatar preview image (in pixels).
 * @var string   $class_name The additional classname defined in the Block Editor.
 * @var string   $align      An additional alignment class.
 */

// Combine classes.
$classes = \trim( "{$class_name} {$align}" ); // @phpstan-ignore-line -- https://github.com/phpstan/phpstan/issues/3515

// Provide a proper alt text, as this is a content image.
$alt = \sprintf(
	/* translators: The display name of the user */
	\__( 'Avatar of %s', 'avatar-privacy' ),
	$user->display_name
);
?>
<figure class="<?php echo \esc_attr( $classes ); ?>">
	<?php echo /* @scrutinizer ignore-type */ \get_avatar( $user, $size, '', $alt ); ?>
</figure>
<?php
