<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

namespace Avatar_Privacy\Core;

use Avatar_Privacy\Core\API;
use Avatar_Privacy\Components\Network_Settings_Page;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler;

use Mundschenk\UI\Controls;

/**
 * Default configuration for Avatar Privacy.
 *
 * @internal
 *
 * @since 2.0.0
 * @since 2.1.0 Class made concrete and marked internal.
 * @since 2.4.0 Moved to Avatar_Privacy\Core.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Settings implements API {

	/**
	 * The name of the combined settings in the database.
	 *
	 * @since 2.4.0 Moved from Avatar_Privacy\Core and renamed from SETTINGS_NAME.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'settings';

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
	 * The fields definition array for the network settings.
	 *
	 * @var array
	 */
	private $network_fields;

	/**
	 * The cached information header markup.
	 *
	 * @var string
	 */
	private $information_header;

	/**
	 * The plugin version.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The user's settings.
	 *
	 * @since 2.4.0
	 *
	 * @var array
	 */
	private $settings = [];

	/**
	 * The options handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0
	 *
	 * @param string  $version The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Options $options The options handler.
	 */
	public function __construct( $version, Options $options ) {
		$this->version = $version;
		$this->options = $options;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieves the complete plugin settings array.
	 *
	 * @since 2.0.0 Parameter $force added.
	 * @since 2.4.0 Moved to Avatar_Privacy\Core\Settings::get_all_settings.
	 *
	 * @param bool $force Optional. Forces retrieval of settings from database. Default false.
	 *
	 * @return array
	 */
	public function get_all_settings( $force = false ) {
		// Force a re-read if the cached settings do not appear to be from the current version.
		if ( empty( $this->settings ) || empty( $this->settings[ Options::INSTALLED_VERSION ] )
			|| $this->version !== $this->settings[ Options::INSTALLED_VERSION ] || $force ) {
			$this->settings = (array) $this->options->get( self::OPTION_NAME, $this->get_defaults() );
		}

		return $this->settings;
	}

	/**
	 * Retrieves the settings field definitions.
	 *
	 * @param string $information_header Optional. The HTML markup for the informational header in the settings. Default ''.
	 *
	 * @return array
	 */
	public function get_fields( $information_header = '' ) {
		if ( empty( $this->fields ) ) {
			$this->fields = [ // @codeCoverageIgnoreStart
				self::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
					'ui'             => \Avatar_Privacy\Upload_Handlers\UI\File_Upload_Input::class,
					'tab_id'         => '', // Will be added to the 'discussions' page.
					'section'        => 'avatars',
					'help_no_file'   => \__( 'No custom default avatar is set. Use the upload field to add a custom default avatar image.', 'avatar-privacy' ),
					'help_no_upload' => \__( 'You do not have media management permissions. To change your custom default avatar, contact the site administrator.', 'avatar-privacy' ),
					'help_text'      => \__( 'Replace the custom default avatar by uploading a new image, or erase it by checking the delete option.', 'avatar-privacy' ),
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
				], // @codeCoverageIgnoreEnd
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

			// Allow detection of new installations.
			$defaults[ Options::INSTALLED_VERSION ] = '';

			$this->defaults = $defaults;
		}

		return $this->defaults;
	}

	/**
	 * Retrieves the network settings field definitions.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	public function get_network_fields() {
		if ( empty( $this->network_fields ) ) {
			$this->network_fields = [ // @codeCoverageIgnoreStart
				Network_Options::USE_GLOBAL_TABLE          => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => Network_Settings_Page::SECTION,
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Use global table.', 'avatar-privacy' ),
					'short'            => \__( 'Global Table', 'avatar-privacy' ),
					'help_text'        => \__( 'Checking will make Avatar Privacy use a single table for each network (instead of for each site) for storing anonymous comment author consent. (Do not enable this setting unless you are sure about the privacy implications.)', 'avatar-privacy' ),
					'default'          => 0,
				], // @codeCoverageIgnoreEnd
			];
		}

		return $this->network_fields;
	}
}
