<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

use Dice\Dice;

use Avatar_Privacy\Core;
use Avatar_Privacy\Component;
use Avatar_Privacy\Components;
use Avatar_Privacy\Controller;
use Avatar_Privacy\CLI;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

use Avatar_Privacy\Avatar_Handlers\Default_Icons;
use Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler;
use Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler;
use Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler;

use Avatar_Privacy\Components\Integrations;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons;

use Avatar_Privacy\Integrations\BBPress_Integration;
use Avatar_Privacy\Integrations\Ultimate_Member_Integration;
use Avatar_Privacy\Integrations\WPDiscuz_Integration;
use Avatar_Privacy\Integrations\WP_User_Manager_Integration;

use Avatar_Privacy\Tools;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * A factory for creating Avatar_Privacy instances via dependency injection.
 *
 * @since 1.0.0
 * @since 2.1.0 Class made concrete.
 * @since 2.3.0 Moved to Avatar_Privacy\Factory.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Factory extends Dice {
	const SHARED = [ 'shared' => true ];

	/**
	 * The factory instance.
	 *
	 * @var Factory
	 */
	private static $factory;

	/**
	 * Creates a new instance.
	 */
	protected function __construct() {
		// Add rules.
		foreach ( $this->get_rules() as $classname => $rule ) {
			$this->addRule( $classname, $rule );
		}
	}

	/**
	 * Retrieves a factory set up for creating Avatar_Privacy instances.
	 *
	 * @since 2.1.0 Parameter $full_plugin_path replaced with AVATAR_PRIVACY_PLUGIN_FILE constant.
	 *
	 * @return Factory
	 */
	public static function get() {
		if ( ! isset( self::$factory ) ) {

			// Create factory.
			self::$factory = new static();
		}

		return self::$factory;
	}

	/**
	 * Retrieves the rules for setting up the plugin.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	protected function get_rules() {
		return [
			// Shared helpers.
			Cache::class                                    => self::SHARED,
			Database::class                                 => self::SHARED,
			Transients::class                               => self::SHARED,
			Site_Transients::class                          => self::SHARED,
			Options::class                                  => self::SHARED,
			Network_Options::class                          => self::SHARED,
			Filesystem_Cache::class                         => self::SHARED,
			Settings::class                                 => self::SHARED,

			// Core API.
			Core::class                                     => [
				'shared'          => true,
				'constructParams' => [ $this->get_plugin_version( \AVATAR_PRIVACY_PLUGIN_FILE ) ],
			],

			// The plugin controller.
			Controller::class                               => [
				'constructParams' => [ $this->get_components() ],
			],

			// Components.
			Component::class                                => self::SHARED,
			Components\Command_Line_Interface::class        => [
				'constructParams' => [ $this->get_cli_commands() ],
			],
			Components\Integrations::class                  => [
				'constructParams' => [ $this->get_plugin_integrations() ],
			],

			// Default icon providers.
			Static_Icons\Mystery_Icon_Provider::class       => self::SHARED,
			Static_Icons\Speech_Bubble_Icon_Provider::class => self::SHARED,
			Static_Icons\Bowling_Pin_Icon_Provider::class   => self::SHARED,
			Static_Icons\Silhouette_Icon_Provider::class    => self::SHARED,

			// Avatar handlers.
			Default_Icons_Handler::class                    => [
				'shared'          => true,
				'constructParams' => [ $this->get_default_icons() ],
			],
			Gravatar_Cache_Handler::class                   => self::SHARED,
			User_Avatar_Handler::class                      => self::SHARED,

			// Default icon generators.
			Default_Icons\Generator::class                  => self::SHARED,
			Default_Icons\Generators\Jdenticon::class       => [
				'substitutions' => [
					\Jdenticon\Identicon::class => [ 'instance' => '$JdenticonIdenticon' ],
				],
			],
			Default_Icons\Generators\Retro::class           => [
				'substitutions' => [
					\Identicon\Identicon::class => [ 'instance' => '$RetroIdenticon' ],
				],
			],

			// Icon components.
			'$JdenticonIdenticon'                           => [
				'instanceOf'      => \Jdenticon\Identicon::class,
				'constructParams' => [
					// Some extra styling for the Jdenticon instance.
					[ 'style' => [ 'padding' => 0 ] ],
				],
			],
			'$RetroIdenticon'                               => [
				'instanceOf'      => \Identicon\Identicon::class,
				'constructParams' => [
					// The constructor argument is not type-hinted.
					[ 'instance' => \Identicon\Generator\SvgGenerator::class ],
				],
			],
			Default_Icons\Generators\Ring_Icon::class       => [
				'shared'          => true, // Not really necessary, but ...
				'constructParams' => [
					512, // The bounding box dimensions.
					3,   // The number of rings.
				],
				'call'            => [
					[ 'setMono', [ true ] ], // The rings should be monochrome.
				],
			],

			// Upload handlers.
			Upload_Handler::class                           => self::SHARED,

			// Form helpers.
			User_Form::class                                => self::SHARED,
			'$UserProfileForm'                              => [
				'instanceOf'      => User_Form::class,
				'constructParams' => [
					[
						'nonce'   => 'avatar_privacy_use_gravatar_nonce_',
						'action'  => 'avatar_privacy_edit_use_gravatar',
						'field'   => 'avatar-privacy-use-gravatar',
						'partial' => '/admin/partials/profile/use-gravatar.php',
					],
					[
						'nonce'   => 'avatar_privacy_allow_anonymous_nonce_',
						'action'  => 'avatar_privacy_edit_allow_anonymous',
						'field'   => 'avatar-privacy-allow-anonymous',
						'partial' => '/admin/partials/profile/allow-anonymous.php',
					],
					[
						'nonce'   => 'avatar_privacy_upload_avatar_nonce_',
						'action'  => 'avatar_privacy_upload_avatar',
						'field'   => 'avatar-privacy-user-avatar-upload',
						'erase'   => 'avatar-privacy-user-avatar-erase',
						'partial' => '/admin/partials/profile/user-avatar-upload.php',
					],
				],
			],
			'$bbPressProfileForm'                           => [
				'instanceOf'      => User_Form::class,
				'constructParams' => [
					[
						'nonce'   => 'avatar_privacy_bbpress_use_gravatar_nonce_',
						'action'  => 'avatar_privacy_bbpress_edit_use_gravatar',
						'field'   => 'avatar-privacy-bbpress-use-gravatar',
						'partial' => '/public/partials/bbpress/profile/use-gravatar.php',
					],
					[
						'nonce'   => 'avatar_privacy_bbpress_allow_anonymous_nonce_',
						'action'  => 'avatar_privacy_bbpress_edit_allow_anonymous',
						'field'   => 'avatar-privacy-bbpress-allow-anonymous',
						'partial' => '/public/partials/bbpress/profile/allow-anonymous.php',
					],
					[
						'nonce'   => 'avatar_privacy_bbpress_upload_avatar_nonce_',
						'action'  => 'avatar_privacy_bbpress_upload_avatar',
						'field'   => 'avatar-privacy-bbpress-user-avatar-upload',
						'erase'   => 'avatar-privacy-bbpress-user-avatar-erase',
						'partial' => '/public/partials/bbpress/profile/user-avatar-upload.php',
					],
				],
			],
			'$FrontendUserForm'                             => [
				'instanceOf'      => User_Form::class,
				'constructParams' => [
					[
						'nonce'   => 'avatar_privacy_frontend_use_gravatar_nonce_',
						'action'  => 'avatar_privacy_frontend_edit_use_gravatar',
						'field'   => 'avatar-privacy-frontend-use-gravatar',
						'partial' => '/public/partials/profile/use-gravatar.php',
					],
					[
						'nonce'   => 'avatar_privacy_frontend_allow_anonymous_nonce_',
						'action'  => 'avatar_privacy_frontend_edit_allow_anonymous',
						'field'   => 'avatar_privacy_frontend-allow_anonymous',
						'partial' => '/public/partials/profile/allow-anonymous.php',
					],
					[
						'nonce'   => 'avatar_privacy_frontend_upload_avatar_nonce_',
						'action'  => 'avatar_privacy_frontend_upload_avatar',
						'field'   => 'avatar-privacy-frontend-user-avatar-upload',
						'erase'   => 'avatar-privacy-frontend-user-avatar-erase',
						'partial' => '/public/partials/profile/user-avatar-upload.php',
					],
				],
			],

			Components\Block_Editor::class                  => [
				'substitutions' => [
					User_Form::class => [ 'instance' => '$FrontendUserForm' ],
				],
			],
			Components\Shortcodes::class                    => [
				'substitutions' => [
					User_Form::class => [ 'instance' => '$FrontendUserForm' ],
				],
			],
			Components\User_Profile::class                  => [
				'substitutions' => [
					User_Form::class => [ 'instance' => '$UserProfileForm' ],
				],
			],

			// Plugin integrations.
			BBPress_Integration::class                      => [
				'shared'        => true,
				'substitutions' => [
					User_Form::class => [ 'instance' => '$bbPressProfileForm' ],
				],
			],

			// Shared tools.
			Tools\Number_Generator::class                   => self::SHARED,
			Tools\Multisite::class                          => self::SHARED,
			Tools\Images\Editor::class                      => self::SHARED,
			Tools\Network\Gravatar_Service::class           => self::SHARED,
		];
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $plugin_file The full plugin path.
	 *
	 * @return string
	 */
	protected function get_plugin_version( $plugin_file ) {
		// Load version from plugin data.
		if ( ! \function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return \get_plugin_data( $plugin_file, false, false )['Version'];
	}

	/**
	 * Retrieves the list of plugin components run during normal operations
	 * (i.e. not including the Uninstallation component).
	 *
	 * @return array {
	 *     An array of `Component` instances in `Dice` syntax.
	 *
	 *     @type array {
	 *         @type string $instance The classname.
	 *     }
	 * }
	 */
	protected function get_components() {
		return [
			[ 'instance' => Components\Setup::class ],
			[ 'instance' => Components\Image_Proxy::class ],
			[ 'instance' => Components\Avatar_Handling::class ],
			[ 'instance' => Components\Comments::class ],
			[ 'instance' => Components\User_Profile::class ],
			[ 'instance' => Components\Settings_Page::class ],
			[ 'instance' => Components\Network_Settings_Page::class ],
			[ 'instance' => Components\Privacy_Tools::class ],
			[ 'instance' => Components\REST_API::class ],
			[ 'instance' => Components\Integrations::class ],
			[ 'instance' => Components\Shortcodes::class ],
			[ 'instance' => Components\Block_Editor::class ],
			[ 'instance' => Components\Command_Line_Interface::class ],
		];
	}

	/**
	 * Retrieves a list of default icon providers suitable for inclusion in a `Dice` rule.
	 *
	 * @since 2.1.0
	 *
	 * @return array {
	 *     An array of `Icon_Provider` instances in `Dice` syntax.
	 *
	 *     @type array {
	 *         @type string $instance The classname.
	 *     }
	 * }
	 */
	protected function get_default_icons() {
		return [
			// These are sorted as the should appear for selection in the discussion settings.
			[ 'instance' => Static_Icons\Mystery_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Identicon_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Wavatar_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Monster_ID_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Retro_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Rings_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Bird_Avatar_Icon_Provider::class ],
			[ 'instance' => Generated_Icons\Cat_Avatar_Icon_Provider::class ],
			[ 'instance' => Static_Icons\Speech_Bubble_Icon_Provider::class ],
			[ 'instance' => Static_Icons\Bowling_Pin_Icon_Provider::class ],
			[ 'instance' => Static_Icons\Silhouette_Icon_Provider::class ],
			[ 'instance' => Default_Icons\Custom_Icon_Provider::class ],
		];
	}

	/**
	 * Retrieves a list of plugin integrations.
	 *
	 * @since 2.1.0
	 *
	 * @return array {
	 *     An array of `Plugin_Integration` instances in `Dice` syntax.
	 *
	 *     @type array {
	 *         @type string $instance The classname.
	 *     }
	 * }
	 */
	protected function get_plugin_integrations() {
		return [
			[ 'instance' => BBPress_Integration::class ],
			[ 'instance' => Ultimate_Member_Integration::class ],
			[ 'instance' => WPDiscuz_Integration::class ],
			[ 'instance' => WP_User_Manager_Integration::class ],
		];
	}

	/**
	 * Retrieves a list of CLI commands.
	 *
	 * @since 2.3.0
	 *
	 * @return array {
	 *     An array of `Command` instances in `Dice` syntax.
	 *
	 *     @type array {
	 *         @type string $instance The classname.
	 *     }
	 * }
	 */
	protected function get_cli_commands() {
		return [
			[ 'instance' => CLI\Cron_Command::class ],
			[ 'instance' => CLI\Database_Command::class ],
			[ 'instance' => CLI\Uninstall_Command::class ],
		];
	}
}
