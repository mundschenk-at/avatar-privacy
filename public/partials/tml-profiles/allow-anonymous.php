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

/**
 * Frontend profile form `allow_anonymous` checkbox.
 *
 * Required template variables:
 *
 * @var string $nonce            The nonce itself.
 * @var string $action           The nonce action.
 * @var string $field_name       The name of the checkbox `<input>` element.
 * @var string $value            The checkbox value.
 * @var string $show_description True if the long description should be displayed.
 */
?>
<?php \wp_nonce_field( $action, $nonce ); ?>
<input
	id="<?php echo \esc_attr( $field_name ); ?>"
	name="<?php echo \esc_attr( $field_name ); ?>"
	class="tml-checkbox"
	type="checkbox"
	value="true"
	<?php \checked( $value ); ?>
/>
<label class="tml-label" for="<?php echo \esc_attr( $field_name ); ?>"><?php \esc_html_e( 'Allow logged-out comments with my profile picture.', 'avatar-privacy' ); ?></label><br />
<?php if ( ! empty( $show_description ) ) : ?>
	<p class="tml-description">
			<?php \esc_html_e( 'Check this box if you want to be able to use your profile picture while logged-out.', 'avatar-privacy' ); ?>
	</p>
<?php endif; ?>
<?php
