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

use Avatar_Privacy\Tools\Template;

/**
 * Frontend profile form `use_gravatar` checkbox.
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
<div class="avatar-privacy-use-gravatar">
	<?php \wp_nonce_field( $action, $nonce ); ?>
	<input
		id="<?php echo \esc_attr( $field_name ); ?>"
		name="<?php echo \esc_attr( $field_name ); ?>"
		type="checkbox"
		value="true"
		<?php \checked( $value ); ?>
	/>
	<label for="<?php echo \esc_attr( $field_name ); ?>"><?php echo \wp_kses( sprintf( /* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */ \__( 'Display a <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a> image for my e-mail address.', 'avatar-privacy' ), \__( 'https://en.gravatar.com/', 'avatar-privacy' ), Template::get_gravatar_link_rel(), Template::get_gravatar_link_target() ), Template::ALLOWED_HTML_LABEL ); ?></label><br />
	<?php if ( ! empty( $show_description ) ) : ?>
		<p class="description">
			<?php \esc_html_e( "Uncheck this box if you don't want to display the gravatar for your e-mail address (or don't have an account on Gravatar.com).", 'avatar-privacy' ); ?>
			<?php \esc_html_e( 'This setting will only take effect if you have not uploaded a local profile picture.', 'avatar-privacy' ); ?>
		</p>
	<?php endif; ?>
</div>
<?php
