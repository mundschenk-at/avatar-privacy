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

use Dice\Dice;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Upload_Handlers\Upload_Handler;

use Avatar_Privacy\Avatar_Handlers\Default_Icons;
use Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler;
use Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler;
use Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler;

use Avatar_Privacy\Components\Avatar_Handling;
use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Components\Integrations;
use Avatar_Privacy\Components\Network_Settings_Page;
use Avatar_Privacy\Components\Privacy_Tools;
use Avatar_Privacy\Components\Settings_Page;
use Avatar_Privacy\Components\Setup;
use Avatar_Privacy\Components\Uninstallation;
use Avatar_Privacy\Components\User_Profile;

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

use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Multisite as Multisite_Tools;
use Avatar_Privacy\Tools\Network\Gravatar_Service;

/**
 * A factory for creating Avatar_Privacy instances via dependency injection.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Avatar_Privacy_Factory {
	const SHARED = [ 'shared' => true ];

	/**
	 * The factory instance.
	 *
	 * @var Dice
	 */
	private static $factory;

	/**
	 * Retrieves a factory set up for creating Avatar_Privacy instances.
	 *
	 * @param string $full_plugin_path The full path to the main plugin file (i.e. __FILE__).
	 *
	 * @return Dice
	 */
	public static function get( $full_plugin_path ) {
		if ( ! isset( self::$factory ) ) {
			// Load version from plugin data.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Dynamic rules' helpers.
			$full_path_rule        = [
				'constructParams' => [ $full_plugin_path ],
			];
			$full_path_shared_rule = [
				'constructParams' => [ $full_plugin_path ],
				'shared'          => true,
			];

			// Define rules.
			$rules = [
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
					'constructParams' => [
						$full_plugin_path,
						\get_plugin_data( $full_plugin_path, false, false )['Version'],
					],
				],

				// Components.
				Avatar_Handling::class                          => $full_path_rule,
				Comments::class                                 => $full_path_rule,
				Network_Settings_Page::class                    => $full_path_rule,
				Privacy_Tools::class                            => $full_path_rule,
				Settings_Page::class                            => $full_path_rule,
				Setup::class                                    => $full_path_rule,
				Uninstallation::class                           => $full_path_rule,
				User_Profile::class                             => $full_path_shared_rule,

				// Default icon providers.
				Static_Icons\Mystery_Icon_Provider::class       => $full_path_shared_rule,
				Static_Icons\Speech_Bubble_Icon_Provider::class => $full_path_shared_rule,
				Static_Icons\Bowling_Pin_Icon_Provider::class   => $full_path_shared_rule,
				Static_Icons\Silhouette_Icon_Provider::class    => $full_path_shared_rule,

				// Avatar handlers.
				Default_Icons_Handler::class                    => [
					'shared'          => true,
					'constructParams' => [
						$full_plugin_path,
						[
							// These are sorted as the should appear for selection in the discussion settings.
							[ 'instance' => Static_Icons\Mystery_Icon_Provider::class ],
							[ 'instance' => Generated_Icons\Identicon_Icon_Provider::class ],
							[ 'instance' => Generated_Icons\Wavatar_Icon_Provider::class ],
							[ 'instance' => Generated_Icons\Monster_ID_Icon_Provider::class ],
							[ 'instance' => Generated_Icons\Retro_Icon_Provider::class ],
							[ 'instance' => Generated_Icons\Rings_Icon_Provider::class ],
							[ 'instance' => Static_Icons\Speech_Bubble_Icon_Provider::class ],
							[ 'instance' => Static_Icons\Bowling_Pin_Icon_Provider::class ],
							[ 'instance' => Static_Icons\Silhouette_Icon_Provider::class ],
							[ 'instance' => Default_Icons\Custom_Icon_Provider::class ],
						],
					],
				],
				Gravatar_Cache_Handler::class                   => self::SHARED,
				User_Avatar_Handler::class                      => self::SHARED,

				// Default icon generators.
				Generators\Monster_ID::class                    => $full_path_shared_rule,
				Generators\Wavatar::class                       => $full_path_shared_rule,

				// Upload handlers.
				Upload_Handler::class                           => $full_path_shared_rule,

				// Plugin integrations.
				BBPress_Integration::class                      => $full_path_shared_rule,

				// Tools.
				Images\Editor::class                            => self::SHARED,
				Multisite_Tools::class                          => self::SHARED,
				Gravatar_Service::class                         => self::SHARED,
			];

			// Create factory.
			self::$factory = new Dice();

			// Add rules.
			foreach ( $rules as $classname => $rule ) {
				self::$factory->addRule( $classname, $rule );
			}

			// Plugin integrations list.
			$integrations = [
				self::$factory->create( BBPress_Integration::class ),
			];
			self::$factory->addRule( Integrations::class, [ 'constructParams' => [ $integrations ] ] );
		}

		return self::$factory;
	}
}
