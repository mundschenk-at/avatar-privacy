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

$current_avatar                = \get_user_meta( $user->ID, self::USER_META_KEY, true );
$current_user_can_upload_files = \current_user_can( 'upload_files' );

if ( $current_user_can_upload_files ) {
	if ( empty( $current_avatar ) ) {
		$description = \sprintf(
			/* translators: %s: Gravatar URL */
			\__( 'No local profile picture is set. Use the upload field to add a local profile picture or change your profile picture on <a href="%s">Gravatar</a>.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' )
		);
	} else {
		$description = \sprintf(
			/* translators: %s: Gravatar URL */
			\__( 'Replace the local profile picture by uploading a new avatar, or erase it (falling back on <a href="%s">Gravatar</a>) by checking the delete option.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' )
		);
	}
} else {
	if ( empty( $current_avatar ) ) {
		$description = \sprintf(
			/* translators: %s: Gravatar URL */
			\__( 'No local profile picture is set. Change your profile picture on <a href="%s">Gravatar</a>.', 'avatar-privacy' ),
			\__( 'https://en.gravatar.com/', 'avatar-privacy' )
		);
	} else {
		$description = \__( 'You do not have media management permissions. To change your local profile picture, contact the site administrator.', 'avatar-privacy' );
	}
}

?>
<tr class"avatar-privacy-user-avatar-upload">
	<th scope="row"><?php \esc_html_e( 'Profile Picture', 'avatar-privacy' ); ?><0/th>
	<td>
		<?php echo \get_avatar( $user->ID ); ?>

		<?php if ( $current_user_can_upload_files ) : ?>
			<p class="avatar-privacy-upload-fields">
				<?php \wp_nonce_field( self::ACTION_UPLOAD, self::NONCE_UPLOAD . $user->ID ); ?>
				<input type="file" id="<?php echo \esc_attr( self::FILE_UPLOAD ); ?>" name="<?php echo \esc_attr( self::FILE_UPLOAD ); ?>" accept="image/*" />
				<?php if ( ! empty( $current_avatar ) ) : ?>
					<input type="checkbox" id="<?php echo \esc_attr( self::CHECKBOX_ERASE ); ?>" name="<?php echo \esc_attr( self::CHECKBOX_ERASE ); ?>" value="true" />
					<label for="<?php echo \esc_attr( self::CHECKBOX_ERASE ); ?>"><?php \esc_html_e( 'Delete local avatar picture.', 'avatar-privacy' ); ?></label><br />
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<p class="description">
			<?php echo \wp_kses( $description, [ 'a' => true ] ); ?>
		</p>
	</td>
</tr>
<?php
