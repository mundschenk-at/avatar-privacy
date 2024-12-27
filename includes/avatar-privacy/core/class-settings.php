<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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
 *
 * @phpstan-import-type AvatarDefinition from User_Fields
 *
 * @phpstan-type SettingsFieldMeta array{
 *     ui: class-string<\Mundschenk\UI\Control>,
 *     tab_id: string,
 *     section: string,
 *     help_no_file?: string,
 *     help_no_upload?: string,
 *     help_text?: string,
 *     short?: string,
 *     label?: string,
 *     erase_checkbox?: string,
 *     action?: string,
 *     nonce?: string,
 *     default?: mixed,
 *     attributes?: mixed[],
 *     settings_args?: mixed[],
 *     elements?: mixed[],
 *     grouped_with?: string,
 *     outer_attributes?: mixed[],
 * }
 * @phpstan-type SettingsFieldDefinitions array{
 *     custom_default_avatar: SettingsFieldMeta,
 *     display: SettingsFieldMeta,
 *     gravatar_use_default: SettingsFieldMeta,
 * }
 * @phpstan-type SettingsFields array{
 *     custom_default_avatar: AvatarDefinition|array{},
 *     gravatar_use_default: bool,
 *     installed_version: string
 * }
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
	 * The defaults for the options array.
	 *
	 * @since 2.8.0
	 *
	 * @var array<string,mixed>
	 * @phpstan-var SettingsFields
	 */
	private const DEFAULTS = [
		self::UPLOAD_CUSTOM_DEFAULT_AVATAR => [],
		self::GRAVATAR_USE_DEFAULT         => false,
		Options::INSTALLED_VERSION         => '',
	];

	/**
	 * The fields definition array.
	 *
	 * @var array
	 *
	 * @phpstan-var SettingsFieldDefinitions
	 */
	private array $fields;

	/**
	 * The fields definition array for the network settings.
	 *
	 * @var array
	 *
	 * @phpstan-var array<SettingsFieldMeta>
	 */
	private array $network_fields;

	/**
	 * The cached information header markup.
	 *
	 * @var string
	 */
	private string $information_header;

	/**
	 * The plugin version.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * The user's settings (indexed by site ID to be multisite-safe).
	 *
	 * @since 2.4.0
	 *
	 * @var array {
	 *     @type array $site_settings The plugin settings for the site.
	 * }
	 *
	 * @phpstan-var array<int, SettingsFields>
	 */
	private array $settings = [];

	/**
	 * The options handler.
	 *
	 * @since 2.4.0
	 *
	 * @var Options
	 */
	private Options $options;

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
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieves the complete plugin settings array.
	 *
	 * @since  2.0.0 Parameter $force added.
	 * @since  2.4.0 Moved to Avatar_Privacy\Core\Settings::get_all_settings.
	 *
	 * @param  bool $force Optional. Forces retrieval of settings from database. Default false.
	 *
	 * @return array
	 *
	 * @phpstan-return SettingsFields
	 */
	public function get_all_settings( $force = false ) {
		$site_id = \get_current_blog_id();

		// Force a re-read if the cached settings do not appear to be from the current version.
		if ( empty( $this->settings[ $site_id ] ) ||
			empty( $this->settings[ $site_id ][ Options::INSTALLED_VERSION ] ) ||
			$this->version !== $this->settings[ $site_id ][ Options::INSTALLED_VERSION ] ||
			$force
		) {
			$this->settings[ $site_id ] = $this->load_settings();
		}

		return $this->settings[ $site_id ];
	}

	/**
	 * Load settings from the database and set defaults if necessary.
	 *
	 * @since 2.4.1
	 *
	 * @return array
	 *
	 * @phpstan-return SettingsFields
	 */
	protected function load_settings() {
		$_settings = $this->options->get( self::OPTION_NAME );
		$modified  = false;

		if ( \is_array( $_settings ) ) {
			foreach ( self::DEFAULTS as $name => $default_value ) {
				if ( ! isset( $_settings[ $name ] ) ) {
					$_settings[ $name ] = $default_value;
					$modified           = true;
				}
			}

			/**
			 * PHPStan type.
			 *
			 * @phpstan-var SettingsFields $_settings
			 */
		} else {
			$_settings = self::DEFAULTS;
			$modified  = true;
		}

		if ( $modified ) {
			$this->options->set( self::OPTION_NAME, $_settings );
		}

		return $_settings;
	}

	/**
	 * Retrieves a single setting.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $setting       The setting name (index).
	 * @param  bool   $force         Optional. Forces retrieval of settings from
	 *                               database. Default false.
	 *
	 * @return string|int|bool|array The requested setting value.
	 *
	 * @throws \UnexpectedValueException Thrown when the setting name is invalid.
	 *
	 * @phpstan-param key-of<SettingsFields> $setting
	 * @phpstan-return value-of<SettingsFields>
	 */
	public function get( $setting, $force = false ) {
		$all_settings = $this->get_all_settings( $force );

		if ( ! isset( $all_settings[ $setting ] ) ) {
			throw new \UnexpectedValueException( \esc_html( "Invalid setting name '{$setting}'." ) );
		}

		return $all_settings[ $setting ];
	}

	/**
	 * Sets a single setting.
	 *
	 * @since  2.4.0
	 *
	 * @internal
	 *
	 * @param  string                $setting The setting name (index).
	 * @param  string|int|bool|array $value   The setting value.
	 *
	 * @return bool
	 *
	 * @throws \UnexpectedValueException Thrown when the setting name is invalid.
	 *
	 * @phpstan-param key-of<SettingsFields> $setting
	 * @phpstan-param value-of<SettingsFields> $value
	 */
	public function set( $setting, $value ) {
		$site_id      = \get_current_blog_id();
		$all_settings = $this->get_all_settings();

		if ( ! isset( $all_settings[ $setting ] ) ) {
			throw new \UnexpectedValueException( \esc_html( "Invalid setting name '{$setting}'." ) );
		}

		// Update DB.
		$all_settings[ $setting ] = $value;
		$result                   = $this->options->set( self::OPTION_NAME, $all_settings );

		// Update cached settings only if DB the DB write was successful.
		if ( $result ) {
			/**
			 * PHPStan type.
			 *
			 * @phpstan-var SettingsFields $all_settings
			 */
			$this->settings[ $site_id ] = $all_settings;
		}

		return $result;
	}

	/**
	 * Retrieves the settings field definitions.
	 *
	 * @param string $information_header Optional. The HTML markup for the informational header in the settings. Default ''.
	 *
	 * @return array
	 *
	 * @phpstan-return SettingsFieldDefinitions
	 */
	public function get_fields( $information_header = '' ) {
		if ( ! isset( $this->fields ) ) {
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
					'default'        => self::DEFAULTS[ self::UPLOAD_CUSTOM_DEFAULT_AVATAR ],
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
					'default'          => self::DEFAULTS[ self::GRAVATAR_USE_DEFAULT ],
					'grouped_with'     => self::INFORMATION_HEADER,
					'outer_attributes' => [ 'class' => 'avatar-settings-enabled' ],
				], // @codeCoverageIgnoreEnd
			];
		}

		// Allow calls where the information header is not relevant by caching it separately.
		if ( ! empty( $information_header ) &&
			( ! isset( $this->information_header ) || $information_header !== $this->information_header ) ) {
			$this->fields[ self::INFORMATION_HEADER ]['elements'] = [ $information_header ];
			$this->information_header                             = $information_header;
		}

		return $this->fields;
	}

	/**
	 * Retrieves the default settings.
	 *
	 * @deprecated 2.8.0
	 *
	 * @return array
	 *
	 * @phpstan-return SettingsFields
	 */
	public function get_defaults() {
		\_deprecated_function( __METHOD__, '2.8.0' );

		return self::DEFAULTS;
	}

	/**
	 * Retrieves the network settings field definitions.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 *
	 * @phpstan-return array<SettingsFieldMeta>
	 */
	public function get_network_fields() {
		if ( ! isset( $this->network_fields ) ) {
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
