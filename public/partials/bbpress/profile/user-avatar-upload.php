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

use Avatar_Privacy\Tools\Template;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

$current_avatar                = \get_user_meta( $user_id, User_Avatar_Upload_Handler::USER_META_KEY, true );
$current_user_can_upload_files = \current_user_can( 'upload_files' );

if ( $current_user_can_upload_files ) {
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
<div class="avatar-pricacy-profile-picture-upload">
	<?php echo /* @scrutinizer ignore-type */ \get_avatar( $user_id ); ?>

	<?php if ( $current_user_can_upload_files ) : ?>
		<?php \wp_nonce_field( User_Avatar_Upload_Handler::ACTION_UPLOAD, User_Avatar_Upload_Handler::NONCE_UPLOAD . $user_id ); ?>
		<input type="file" id="<?php echo \esc_attr( User_Avatar_Upload_Handler::FILE_UPLOAD ); ?>" name="<?php echo \esc_attr( User_Avatar_Upload_Handler::FILE_UPLOAD ); ?>" accept="image/*" />
		<?php if ( ! empty( $current_avatar ) ) : ?>
			<label>
				<input type="checkbox" class="checkbox" id="<?php echo \esc_attr( User_Avatar_Upload_Handler::CHECKBOX_ERASE ); ?>" name="<?php echo \esc_attr( User_Avatar_Upload_Handler::CHECKBOX_ERASE ); ?>" value="true" />
				<?php \esc_html_e( 'Delete local avatar picture.', 'avatar-privacy' ); ?>
			</label>
		<?php endif; ?>
	<?php endif; ?>
	<span class="description indicator-hint" style="width:100%;margin-left:0;">
		<?php echo \wp_kses( $description, [ 'a' => [ 'href' => true ] ] ); ?>
	</span>
</div>
<?php
