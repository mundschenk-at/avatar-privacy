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
 * Frontend profile form user avatar uploader.
 *
 * Required template variables:
 *
 * @var string $nonce            The nonce itself.
 * @var string $action           The nonce action.
 * @var string $upload_field     The name of the uploader `<input>` element.
 * @var string $erase_field      The name of the erase checkbox `<input>` element.
 * @var int    $user_id          The ID of the edited user.
 * @var string $current_avatar   The previously set user avatar.
 * @var bool   $can_upload       Whether the currently active user can upload files.
 * @var bool   $uploads_disabled Whether the uploads system has been disabled completely..
 * @var int    $size             The width/height of the avatar preview image (in pixels).
 * @var string $show_description True if the long description should be displayed.
 */

if ( $uploads_disabled ) {
	// We integrate with some other plugin, so skip the description.
	$description = '';
} elseif ( $can_upload ) {
	if ( empty( $current_avatar ) ) {
		$description = \sprintf(
			/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
			\__( 'No local profile picture is set. Use the upload field to add a local profile picture or change your profile picture on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' ),
			Template::get_gravatar_link_rel(),
			Template::get_gravatar_link_target()
		);
	} else {
		$description = \sprintf(
			/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
			\__( 'Replace the local profile picture by uploading a new avatar, or erase it (falling back on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>) by checking the delete option.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' ),
			Template::get_gravatar_link_rel(),
			Template::get_gravatar_link_target()
		);
	}
} else {
	if ( empty( $current_avatar ) ) {
		$description = \sprintf(
			/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
			\__( 'No local profile picture is set. Change your profile picture on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' ),
			Template::get_gravatar_link_rel(),
			Template::get_gravatar_link_target()
		);
	} else {
		$description = \__( 'You do not have media management permissions. To change your local profile picture, contact the site administrator.', 'avatar-privacy' );
	}
}

?>
<div class="avatar-privacy-user-avatar-upload">
	<?php echo /* @scrutinizer ignore-type */ \get_avatar( $user_id, $size ); ?>

	<?php if ( $can_upload ) : ?>
		<p class="avatar-privacy-upload-fields">
			<?php \wp_nonce_field( $action, $nonce ); ?>
			<input type="file" id="<?php echo \esc_attr( $upload_field ); ?>" name="<?php echo \esc_attr( $upload_field ); ?>" accept="image/*" />
			<?php if ( ! empty( $current_avatar ) ) : ?>
				<input type="checkbox" id="<?php echo \esc_attr( $erase_field ); ?>" name="<?php echo \esc_attr( $erase_field ); ?>" value="true" />
				<label for="<?php echo \esc_attr( $erase_field ); ?>"><?php \esc_html_e( 'Delete local avatar picture.', 'avatar-privacy' ); ?></label><br />
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<?php if ( ! empty( $show_description ) ) : ?>
		<p class="description">
			<?php echo \wp_kses( $description, Template::ALLOWED_HTML_LABEL ); ?>
		</p>
	<?php endif; ?>
</div>
<?php
