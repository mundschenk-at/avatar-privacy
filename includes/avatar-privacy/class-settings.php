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

namespace Avatar_Privacy;

use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler;

use Mundschenk\UI\Controls;

/**
 * Default configuration for Avatar Privacy.
 *
 * @internal
 *
 * @since 2.0.0
 * @since 2.1.0 Class made concrete and marked internal.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Settings {

	/**
	 * The options array index of the custom default avatar image.
	 *
	 * @var string
	 */
	const UPLOAD_CUSTOM_DEFAULT_AVATAR = 'custom_default_avatar';

	/**
	 * The defaults array index of the information headers.
	 *
	 * @var string
	 */
	const INFORMATION_HEADER = 'display';

	/**
	 * The options array index of the default gravatar policy.
	 *
	 * @var string
	 */
	const GRAVATAR_USE_DEFAULT = 'gravatar_use_default';

	/**
	 * The defaults array.
	 *
	 * @var array
	 */
	private $defaults;

	/**
	 * The fields definition array.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * The cached information header markup.
	 *
	 * @var string
	 */
	private $information_header;

	/**
	 * Retrieves the settings field definitions.
	 *
	 * @param string $information_header Optional. The HTML markup for the informational header in the settings. Default ''.
	 *
	 * @return array
	 */
	public function get_fields( $information_header = '' ) {
		if ( empty( $this->fields ) ) {
			$this->fields = [ // @codeCoverageIgnore
				self::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
					'ui'             => \Avatar_Privacy\Upload_Handlers\UI\File_Upload_Input::class,
					'tab_id'         => '', // Will be added to the 'discussions' page.
					'section'        => 'avatars',
					'help_no_file'   => __( 'No custom default avatar is set. Use the upload field to add a custom default avatar image.', 'avatar-privacy' ),
					'help_no_upload' => __( 'You do not have media management permissions. To change your custom default avatar, contact the site administrator.', 'avatar-privacy' ),
					'help_text'      => __( 'Replace the custom default avatar by uploading a new image, or erase it by checking the delete option.', 'avatar-privacy' ),
					'erase_checkbox' => Custom_Default_Icon_Upload_Handler::CHECKBOX_ERASE,
					'action'         => Custom_Default_Icon_Upload_Handler::ACTION_UPLOAD,
					'nonce'          => Custom_Default_Icon_Upload_Handler::NONCE_UPLOAD,
					'default'        => 0,
					'attributes'     => [ 'accept' => 'image/*' ],
					'settings_args'  => [ 'class' => 'avatar-settings' ],
				],
				self::INFORMATION_HEADER           => [
					'ui'            => Controls\Display_Text::class,
					'tab_id'        => '', // Will be added to the 'discussions' page.
					'section'       => 'avatars',
					'elements'      => [], // Will be updated below.
					'short'         => \__( 'Avatar Privacy', 'avatar-privacy' ),
				],
				self::GRAVATAR_USE_DEFAULT         => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => 'avatars',
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Display Gravatar images by default.', 'avatar-privacy' ),
					'help_text'        => \__( 'Checking will ensure that gravatars are displayed when there is no explicit setting for the user or mail address (e.g. for comments made before installing Avatar Privacy). Please only enable this setting after careful consideration of the privacy implications.', 'avatar-privacy' ),
					'default'          => 0,
					'grouped_with'     => self::INFORMATION_HEADER,
					'outer_attributes' => [ 'class' => 'avatar-settings-enabled' ],
				],
			];
		}

		// Allow calls where the information header is not relevant by caching it separately.
		if ( ! empty( $information_header ) && $information_header !== $this->information_header ) {
			$this->fields[ self::INFORMATION_HEADER ]['elements'] = [ $information_header ];
			$this->information_header                             = $information_header;
		}

		return $this->fields;
	}

	/**
	 * Retrieves the default settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		if ( empty( $this->defaults ) ) {
			$defaults = [];
			foreach ( $this->get_fields() as $index => $field ) {
				if ( isset( $field['default'] ) ) {
					$defaults[ $index ] = $field['default'];
				}
			}

			$this->defaults = $defaults;
		}

		return $this->defaults;
	}
}
