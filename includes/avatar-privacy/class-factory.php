<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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
use Avatar_Privacy\Core\API;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Component;
use Avatar_Privacy\Components;
use Avatar_Privacy\Controller;
use Avatar_Privacy\CLI;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

use Avatar_Privacy\Avatar_Handlers\Avatar_Handler;
use Avatar_Privacy\Avatar_Handlers\Default_Icons;
use Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler;
use Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler;
use Avatar_Privacy\Avatar_Handlers\Legacy_Icon_Handler;
use Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;

use Avatar_Privacy\Exceptions\Object_Factory_Exception;

use Avatar_Privacy\Integrations;

use Avatar_Privacy\Tools;
use Avatar_Privacy\Tools\HTML\User_Form;


/**
 * A factory for creating Avatar_Privacy instances via dependency injection.
 *
 * @since 1.0.0
 * @since 2.1.0 Class made concrete.
 * @since 2.3.0 Moved to Avatar_Privacy\Factory.
 * @since 2.4.0 Named instances converted to use class constants.
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-import-type ConfigData from User_Form
 */
class Factory extends Dice {
	const SHARED = [ 'shared' => true ];

	// Named instances.
	const USERFORM_PROFILE_INSTANCE                 = '$UserProfileForm';
	const USERFORM_FRONTEND_INSTANCE                = '$FrontendUserForm';
	const USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE = '$ThemeMyLoginProfilesUserForm';
	const USERFORM_BBPRESS_PROFILE_INSTANCE         = '$bbPressProfileForm';
	const JDENTICON_INSTANCE                        = '$JdenticonIdenticon';
	const RETRO_IDENTICON_INSTANCE                  = '$RetroIdenticon';

	/**
	 * The factory instance.
	 *
	 * @var Factory|null
	 */
	private static $factory;

	/**
	 * Creates a new instance.
	 */
	final protected function __construct() {
	}

	/**
	 * Retrieves a factory set up for creating Avatar_Privacy instances.
	 *
	 * @since 2.1.0 Parameter $full_plugin_path replaced with AVATAR_PRIVACY_PLUGIN_FILE constant.
	 * @since 2.5.1 Now throws an Object_Factory_Exception in case of error.
	 *
	 * @return Factory
	 *
	 * @throws Object_Factory_Exception An exception is thrown if the factory cannot
	 *                                  be created.
	 */
	public static function get() {
		if ( ! isset( self::$factory ) ) {

			// Create factory.
			$factory = new static();
			$factory = $factory->addRules( $factory->get_rules() );

			if ( $factory instanceof Factory ) {
				self::$factory = $factory;
			} else {
				throw new Object_Factory_Exception( 'Could not create object factory.' ); // @codeCoverageIgnore
			}
		}

		return self::$factory;
	}

	/**
	 * Retrieves the rules for setting up the plugin.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 *
	 * @phpstan-return array<class-string|string,mixed[]>
	 */
	protected function get_rules() {
		return [
			// Shared helpers.
			Cache::class                                            => self::SHARED,
			Database\Table::class                                   => self::SHARED,
			Transients::class                                       => self::SHARED,
			Site_Transients::class                                  => self::SHARED,
			Options::class                                          => self::SHARED,
			Network_Options::class                                  => self::SHARED,
			Filesystem_Cache::class                                 => self::SHARED,

			// Core API.
			API::class                                              => self::SHARED,
			Core::class                                             => self::SHARED,
			Settings::class                                         => [
				'constructParams' => [ $this->get_plugin_version( \AVATAR_PRIVACY_PLUGIN_FILE ) ],
			],

			// The plugin controller.
			Controller::class                                       => [
				'constructParams' => [ $this->get_components() ],
			],

			// Components.
			Component::class                                        => self::SHARED,
			Components\Block_Editor::class                          => [
				'substitutions' => [
					User_Form::class => [ self::INSTANCE => self::USERFORM_FRONTEND_INSTANCE ],
				],
			],
			Components\Command_Line_Interface::class                => [
				'constructParams' => [ $this->get_cli_commands() ],
			],
			Components\Image_Proxy::class                           => [
				'constructParams' => [ $this->get_avatar_handlers() ],
			],
			Components\Integrations::class                          => [
				'constructParams' => [ $this->get_plugin_integrations() ],
			],
			Components\Setup::class                                 => [
				'constructParams' => [ $this->get_database_tables() ],
			],
			Components\Shortcodes::class                            => [
				'substitutions' => [
					User_Form::class => [ self::INSTANCE => self::USERFORM_FRONTEND_INSTANCE ],
				],
			],
			Components\User_Profile::class                          => [
				'substitutions' => [
					User_Form::class => [ self::INSTANCE => self::USERFORM_PROFILE_INSTANCE ],
				],
			],

			// Default icon providers.
			Static_Icons\Mystery_Icon_Provider::class               => self::SHARED,
			Static_Icons\Speech_Bubble_Icon_Provider::class         => self::SHARED,
			Static_Icons\Bowling_Pin_Icon_Provider::class           => self::SHARED,
			Static_Icons\Silhouette_Icon_Provider::class            => self::SHARED,

			// Avatar handlers.
			Avatar_Handler::class                                   => self::SHARED,
			Default_Icons_Handler::class                            => [
				'constructParams' => [ $this->get_default_icons() ],
			],

			// Default icon generators.
			Default_Icons\Generator::class                          => self::SHARED,
			Default_Icons\Generators\Jdenticon::class               => [
				'substitutions' => [
					\Jdenticon\Identicon::class => [ self::INSTANCE => self::JDENTICON_INSTANCE ],
				],
			],
			Default_Icons\Generators\Retro::class                   => [
				'substitutions' => [
					\Identicon\Identicon::class => [ self::INSTANCE => self::RETRO_IDENTICON_INSTANCE ],
				],
			],
			Default_Icons\Generators\Rings::class                   => [
				'constructParams' => [
					512, // The bounding box dimensions.
					3,   // The number of rings.
				],
				'call'            => [
					[ 'setMono', [ true ] ], // The rings should be monochrome.
				],
			],

			// Icon components.
			self::JDENTICON_INSTANCE                                => [
				'instanceOf'      => \Jdenticon\Identicon::class,
				'constructParams' => [
					// Some extra styling for the Jdenticon instance.
					[ 'style' => [ 'padding' => 0 ] ],
				],
			],
			self::RETRO_IDENTICON_INSTANCE                          => [
				'instanceOf'      => \Identicon\Identicon::class,
				'constructParams' => [
					// The constructor argument is not type-hinted.
					[ self::INSTANCE => \Identicon\Generator\SvgGenerator::class ],
				],
			],

			// Upload handlers.
			Upload_Handler::class                                   => self::SHARED,

			// Form helpers.
			User_Form::class                                        => self::SHARED,
			self::USERFORM_PROFILE_INSTANCE                         => [
				'instanceOf'      => User_Form::class,
				'constructParams' => $this->get_user_form_parameters( self::USERFORM_PROFILE_INSTANCE ),
			],
			self::USERFORM_BBPRESS_PROFILE_INSTANCE                 => [
				'instanceOf'      => User_Form::class,
				'constructParams' => $this->get_user_form_parameters( self::USERFORM_BBPRESS_PROFILE_INSTANCE ),
			],
			self::USERFORM_FRONTEND_INSTANCE                        => [
				'instanceOf'      => User_Form::class,
				'constructParams' => $this->get_user_form_parameters( self::USERFORM_FRONTEND_INSTANCE ),
			],
			self::USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE         => [
				'instanceOf'      => User_Form::class,
				'constructParams' => $this->get_user_form_parameters( self::USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE ),
			],

			// Plugin integrations.
			Integrations\Plugin_Integration::class                  => self::SHARED,
			Integrations\BBPress_Integration::class                 => [
				'substitutions' => [
					User_Form::class => [ self::INSTANCE => self::USERFORM_BBPRESS_PROFILE_INSTANCE ],
				],
			],
			Integrations\Theme_My_Login_Profiles_Integration::class => [
				'substitutions' => [
					User_Form::class => [ self::INSTANCE => self::USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE ],
				],
			],

			// Shared tools.
			Tools\Hasher::class                                     => self::SHARED,
			Tools\Number_Generator::class                           => self::SHARED,
			Tools\Multisite::class                                  => self::SHARED,
			Tools\Images\Color::class                               => self::SHARED,
			Tools\Images\Editor::class                              => self::SHARED,
			Tools\Images\PNG::class                                 => self::SHARED,
			Tools\Network\Gravatar_Service::class                   => self::SHARED,
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
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
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
	 *
	 * @phpstan-return array<int, array<string, class-string<Component>>>
	 */
	protected function get_components() {
		return [
			[ self::INSTANCE => Components\Setup::class ],
			[ self::INSTANCE => Components\Image_Proxy::class ],
			[ self::INSTANCE => Components\Avatar_Handling::class ],
			[ self::INSTANCE => Components\Comments::class ],
			[ self::INSTANCE => Components\User_Profile::class ],
			[ self::INSTANCE => Components\Settings_Page::class ],
			[ self::INSTANCE => Components\Network_Settings_Page::class ],
			[ self::INSTANCE => Components\Privacy_Tools::class ],
			[ self::INSTANCE => Components\Integrations::class ],
			[ self::INSTANCE => Components\Shortcodes::class ],
			[ self::INSTANCE => Components\Block_Editor::class ],
			[ self::INSTANCE => Components\Command_Line_Interface::class ],
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
	 *
	 * @phpstan-return array<array<self::INSTANCE, class-string<Default_Icons\Icon_Provider>>>
	 */
	protected function get_default_icons() {
		return [
			// These are sorted as the should appear for selection in the discussion settings.
			[ self::INSTANCE => Static_Icons\Mystery_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Identicon_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Wavatar_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Monster_ID_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Retro_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Rings_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Bird_Avatar_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Cat_Avatar_Icon_Provider::class ],
			[ self::INSTANCE => Generated_Icons\Robohash_Icon_Provider::class ],
			[ self::INSTANCE => Static_Icons\Speech_Bubble_Icon_Provider::class ],
			[ self::INSTANCE => Static_Icons\Bowling_Pin_Icon_Provider::class ],
			[ self::INSTANCE => Static_Icons\Silhouette_Icon_Provider::class ],
			[ self::INSTANCE => Default_Icons\Custom_Icon_Provider::class ],
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
	 *
	 * @phpstan-return array<array<self::INSTANCE, class-string<Integrations\Plugin_Integration>>>
	 */
	protected function get_plugin_integrations() {
		return [
			[ self::INSTANCE => Integrations\BBPress_Integration::class ],
			[ self::INSTANCE => Integrations\BuddyPress_Integration::class ],
			[ self::INSTANCE => Integrations\Simple_Author_Box_Integration::class ],
			[ self::INSTANCE => Integrations\Simple_Local_Avatars_Integration::class ],
			[ self::INSTANCE => Integrations\Simple_User_Avatar_Integration::class ],
			[ self::INSTANCE => Integrations\Theme_My_Login_Profiles_Integration::class ],
			[ self::INSTANCE => Integrations\Ultimate_Member_Integration::class ],
			[ self::INSTANCE => Integrations\WPDiscuz_Integration::class ],
			[ self::INSTANCE => Integrations\WP_User_Manager_Integration::class ],
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
	 *
	 * @phpstan-return array<array<self::INSTANCE, class-string<CLI\Command>>>
	 */
	protected function get_cli_commands() {
		return [
			[ self::INSTANCE => CLI\Cron_Command::class ],
			[ self::INSTANCE => CLI\Database_Command::class ],
			[ self::INSTANCE => CLI\Default_Command::class ],
			[ self::INSTANCE => CLI\Uninstall_Command::class ],
			[ self::INSTANCE => CLI\User_Command::class ],
		];
	}

	/**
	 * Retrieves a list of database table handlers.
	 *
	 * @since 2.4.0
	 *
	 * @return array {
	 *     An array of `Table` instances in `Dice` syntax.
	 *
	 *     @type array {
	 *         @type string $instance The classname.
	 *     }
	 * }
	 *
	 * @phpstan-return array<array<self::INSTANCE, class-string<Database\Table>>>
	 */
	protected function get_database_tables() {
		$classes = [
			Database\Comment_Author_Table::class,
			Database\Hashes_Table::class,
		];
		$tables  = [];

		foreach ( $classes as $table_class ) {
			$tables[ $table_class::TABLE_BASENAME ] = [ self::INSTANCE => $table_class ];
		}

		return $tables;
	}

	/**
	 * Retrieves a list of avatar handlers.
	 *
	 * @since 2.4.0
	 *
	 * @return array {
	 *     An array of `Avatar_Handler` instances (in `Dice` syntax), indexed by
	 *     their filter hooks.
	 *
	 *     @type array {
	 *         @type array $hook The instance definition.
	 *     }
	 * }
	 *
	 * @phpstan-return array<array<self::INSTANCE, class-string<Avatar_Handler>>>
	 */
	protected function get_avatar_handlers() {
		return [
			'avatar_privacy_user_avatar_icon_url' => [ self::INSTANCE => User_Avatar_Handler::class ],
			'avatar_privacy_gravatar_icon_url'    => [ self::INSTANCE => Gravatar_Cache_Handler::class ],
			'avatar_privacy_default_icon_url'     => [ self::INSTANCE => Default_Icons_Handler::class ],
			'avatar_privacy_legacy_icon_url'      => [ self::INSTANCE => Legacy_Icon_Handler::class ],
		];
	}

	/**
	 * Retrieves the constructor parameters for configuring named user form instances.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $instance The named instance.
	 *
	 * @return array            The constructor parameter array for the named instance.
	 *
	 * @throws \InvalidArgumentException An exception is raised when $instance is
	 *                                   not one of the expected constants.
	 *
	 * @phpstan-return array{ 0: ConfigData, 1: ConfigData, 2: ConfigData }
	 */
	protected function get_user_form_parameters( $instance ) {
		switch ( $instance ) {
			case self::USERFORM_PROFILE_INSTANCE:
				$use_gravatar    = [
					'nonce'   => 'avatar_privacy_use_gravatar_nonce_',
					'action'  => 'avatar_privacy_edit_use_gravatar',
					'field'   => 'avatar-privacy-use-gravatar',
					'partial' => 'admin/partials/profile/use-gravatar.php',
				];
				$allow_anonymous = [
					'nonce'   => 'avatar_privacy_allow_anonymous_nonce_',
					'action'  => 'avatar_privacy_edit_allow_anonymous',
					'field'   => 'avatar-privacy-allow-anonymous',
					'partial' => 'admin/partials/profile/allow-anonymous.php',
				];
				$user_avatar     = [
					'nonce'   => 'avatar_privacy_upload_avatar_nonce_',
					'action'  => 'avatar_privacy_upload_avatar',
					'field'   => 'avatar-privacy-user-avatar-upload',
					'erase'   => 'avatar-privacy-user-avatar-erase',
					'partial' => 'admin/partials/profile/user-avatar-upload.php',
				];
				break;

			case self::USERFORM_BBPRESS_PROFILE_INSTANCE:
				$use_gravatar    = [
					'nonce'   => 'avatar_privacy_bbpress_use_gravatar_nonce_',
					'action'  => 'avatar_privacy_bbpress_edit_use_gravatar',
					'field'   => 'avatar-privacy-bbpress-use-gravatar',
					'partial' => 'public/partials/bbpress/profile/use-gravatar.php',
				];
				$allow_anonymous = [
					'nonce'   => 'avatar_privacy_bbpress_allow_anonymous_nonce_',
					'action'  => 'avatar_privacy_bbpress_edit_allow_anonymous',
					'field'   => 'avatar-privacy-bbpress-allow-anonymous',
					'partial' => 'public/partials/bbpress/profile/allow-anonymous.php',
				];
				$user_avatar     = [
					'nonce'   => 'avatar_privacy_bbpress_upload_avatar_nonce_',
					'action'  => 'avatar_privacy_bbpress_upload_avatar',
					'field'   => 'avatar-privacy-bbpress-user-avatar-upload',
					'erase'   => 'avatar-privacy-bbpress-user-avatar-erase',
					'partial' => 'public/partials/bbpress/profile/user-avatar-upload.php',
				];
				break;

			case self::USERFORM_FRONTEND_INSTANCE:
				$use_gravatar    = [
					'nonce'   => 'avatar_privacy_frontend_use_gravatar_nonce_',
					'action'  => 'avatar_privacy_frontend_edit_use_gravatar',
					'field'   => 'avatar-privacy-frontend-use-gravatar',
					'partial' => 'public/partials/profile/use-gravatar.php',
				];
				$allow_anonymous = [
					'nonce'   => 'avatar_privacy_frontend_allow_anonymous_nonce_',
					'action'  => 'avatar_privacy_frontend_edit_allow_anonymous',
					'field'   => 'avatar_privacy-frontend-allow_anonymous',
					'partial' => 'public/partials/profile/allow-anonymous.php',
				];
				$user_avatar     = [
					'nonce'   => 'avatar_privacy_frontend_upload_avatar_nonce_',
					'action'  => 'avatar_privacy_frontend_upload_avatar',
					'field'   => 'avatar-privacy-frontend-user-avatar-upload',
					'erase'   => 'avatar-privacy-frontend-user-avatar-erase',
					'partial' => 'public/partials/profile/user-avatar-upload.php',
				];
				break;

			case self::USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE:
				$use_gravatar    = [
					'nonce'   => 'avatar_privacy_tml_profiles_use_gravatar_nonce_',
					'action'  => 'avatar_privacy_tml_profiles_edit_use_gravatar',
					'field'   => 'avatar-privacy-tml-profiles-use-gravatar',
					'partial' => 'public/partials/tml-profiles/use-gravatar.php',
				];
				$allow_anonymous = [
					'nonce'   => 'avatar_privacy_tml_profiles_allow_anonymous_nonce_',
					'action'  => 'avatar_privacy_tml_profiles_edit_allow_anonymous',
					'field'   => 'avatar_privacy-tml-profiles-allow_anonymous',
					'partial' => 'public/partials/tml-profiles/allow-anonymous.php',
				];
				$user_avatar     = [
					'nonce'   => 'avatar_privacy_tml_profiles_upload_avatar_nonce_',
					'action'  => 'avatar_privacy_tml_profiles_upload_avatar',
					'field'   => 'avatar-privacy-tml-profiles-user-avatar-upload',
					'erase'   => 'avatar-privacy-tml-profiles-user-avatar-erase',
					'partial' => 'public/partials/tml-profiles/user-avatar-upload.php',
				];
				break;

			default:
				throw new \InvalidArgumentException( "Invalid named instance {$instance}." );
		}

		return [ $use_gravatar, $allow_anonymous, $user_avatar ];
	}
}
