<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

use Avatar_Privacy\Tools\Template as T;

/**
 * Required template variables:
 *
 * @var int    $user_id          The ID of the edited user.
 * @var T      $template         The templating helper.
 * @var string $nonce            The nonce itself.
 * @var string $action           The nonce action.
 * @var string $upload_field     The name of the uploader `<input>` element.
 * @var string $erase_field      The name of the erase checkbox `<input>` element.
 * @var bool   $uploads_disabled Whether the uploads system has been disabled completely..
 * @var bool   $can_upload       Whether the currently active user can upload files.
 * @var bool   $has_local_avatar Whether a local avatar has been uploaded.
 * @var int    $size             The width/height of the avatar preview image (in pixels).
 * @var bool   $show_description Whether the field description should be shown.
 */

?>
<div class="avatar-pricacy-profile-picture-upload">
	<?php echo \get_avatar( $user_id ); ?>

	<?php if ( $can_upload ) : ?>
		<?php \wp_nonce_field( $action, $nonce ); ?>
		<input type="file" id="<?php echo \esc_attr( $upload_field ); ?>" name="<?php echo \esc_attr( $upload_field ); ?>" accept="image/*" />
		<?php if ( $has_local_avatar ) : ?>
			<label>
				<input type="checkbox" class="checkbox" id="<?php echo \esc_attr( $erase_field ); ?>" name="<?php echo \esc_attr( $erase_field ); ?>" value="true" />
				<?php \esc_html_e( 'Delete local avatar picture.', 'avatar-privacy' ); ?>
			</label>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( ! $uploads_disabled && $show_description ) : ?>
		<span class="description indicator-hint" style="width:100%;margin-left:0;">
			<?php echo \wp_kses( $template->get_uploader_description( $can_upload, $has_local_avatar ), T::ALLOWED_HTML_LABEL ); ?>
		</span>
	<?php endif; ?>
</div>
<?php
