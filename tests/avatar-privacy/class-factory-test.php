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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;

use Mockery as m;

use Avatar_Privacy\Factory;

/**
 * Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Factory
 * @usesDefaultClass \Avatar_Privacy\Factory
 */
class Factory_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Factory&m\MockInterface
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		$filesystem = [
			'wordpress' => [
				'path' => [
					'wp-admin' => [
						'includes' => [
							'plugin.php' => "<?php \\Brain\\Monkey\\Functions\\expect( 'get_plugin_data' )->andReturn( [ 'Version' => '6.6.6' ] ); ?>",
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		\set_include_path( 'vfs://root/' ); // @codingStandardsIgnoreLine

		// Set up the mock.
		$this->sut = m::mock( Factory::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$this->sut->shouldReceive( 'get_rules' )->never();

		// Manually call constructor.
		$this->sut->__construct();

		$resulting_rules = $this->get_value( $this->sut, 'rules' );
		$this->assert_is_array( $resulting_rules );
		$this->assertCount( 0, $resulting_rules );
	}

	/**
	 * Test ::get_rules.
	 *
	 * @covers ::get_rules
	 */
	public function test_get_rules() {
		$version         = '6.6.6';
		$components      = [
			[ Factory::INSTANCE => \Avatar_Privacy\Components\Setup::class ],
			[ Factory::INSTANCE => \Avatar_Privacy\Components\Avatar_Handling::class ],
		];
		$integrations    = [
			[ Factory::INSTANCE => \Avatar_Privacy\Integrations\BBPress_Integration::class ],
		];
		$default_icons   = [
			[ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ],
			[ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ],
			[ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Wavatar_Icon_Provider::class ],
		];
		$cli_commands    = [
			[ Factory::INSTANCE => \Avatar_Privacy\CLI\Database_Command::class ],
		];
		$tables          = [
			[ Factory::INSTANCE => \Avatar_Privacy\Data_Storage\Database\Table::class ],
		];
		$avatar_handlers = [
			'some_hook' => [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Avatar_Handler::class ],
		];
		$user_form_args  = [
			[
				// use_gravatar.
				'nonce'   => 'avatar_privacy_tml_profiles_use_gravatar_nonce_',
				'action'  => 'avatar_privacy_tml_profiles_edit_use_gravatar',
				'field'   => 'avatar-privacy-tml-profiles-use-gravatar',
				'partial' => 'public/partials/tml-profiles/use-gravatar.php',
			],
			[
				// allow_anonymous.
				'nonce'   => 'avatar_privacy_tml_profiles_allow_anonymous_nonce_',
				'action'  => 'avatar_privacy_tml_profiles_edit_allow_anonymous',
				'field'   => 'avatar_privacy-tml-profiles-allow_anonymous',
				'partial' => 'public/partials/tml-profiles/allow-anonymous.php',
			],
			[
				// user_avatar.
				'nonce'   => 'avatar_privacy_tml_profiles_upload_avatar_nonce_',
				'action'  => 'avatar_privacy_tml_profiles_upload_avatar',
				'field'   => 'avatar-privacy-tml-profiles-user-avatar-upload',
				'erase'   => 'avatar-privacy-tml-profiles-user-avatar-erase',
				'partial' => 'public/partials/tml-profiles/user-avatar-upload.php',
			],
		];

		$this->sut->shouldReceive( 'get_plugin_version' )->once()->with( \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $version );
		$this->sut->shouldReceive( 'get_components' )->once()->andReturn( $components );
		$this->sut->shouldReceive( 'get_plugin_integrations' )->once()->andReturn( $integrations );
		$this->sut->shouldReceive( 'get_default_icons' )->once()->andReturn( $default_icons );
		$this->sut->shouldReceive( 'get_cli_commands' )->once()->andReturn( $cli_commands );
		$this->sut->shouldReceive( 'get_avatar_handlers' )->once()->andReturn( $avatar_handlers );
		$this->sut->shouldReceive( 'get_database_tables' )->once()->andReturn( $tables );
		$this->sut->shouldReceive( 'get_user_form_parameters' )->times( 4 )->with( m::type( 'string' ) )->andReturn( $user_form_args );

		$result = $this->sut->get_rules();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( \Avatar_Privacy\Core::class, $result );
		$this->assertArrayHasKey( \Avatar_Privacy\Avatar_Handlers\Avatar_Handler::class, $result );
	}

	/**
	 * Test ::get_plugin_version.
	 *
	 * @covers ::get_plugin_version
	 */
	public function test_get_plugin_version() {
		$version     = '6.6.6';
		$plugin_file = '/the/main/plugin/file.php';

		$this->assertSame( $version, $this->sut->get_plugin_version( $plugin_file ) );
	}

	/**
	 * Test ::get. Should be run after test_get_plugin_version.
	 *
	 * @covers ::get
	 *
	 * @uses Avatar_Privacy\Factory::__construct
	 * @uses Avatar_Privacy\Factory::get_avatar_handlers
	 * @uses Avatar_Privacy\Factory::get_cli_commands
	 * @uses Avatar_Privacy\Factory::get_components
	 * @uses Avatar_Privacy\Factory::get_database_tables
	 * @uses Avatar_Privacy\Factory::get_database_tables
	 * @uses Avatar_Privacy\Factory::get_default_icons
	 * @uses Avatar_Privacy\Factory::get_plugin_integrations
	 * @uses Avatar_Privacy\Factory::get_plugin_version
	 * @uses Avatar_Privacy\Factory::get_rules
	 * @uses Avatar_Privacy\Factory::get_user_form_parameters
	 */
	public function test_get() {
		Functions\expect( 'get_plugin_data' )->once()->with( m::type( 'string' ), false, false )->andReturn( [ 'Version' => '42' ] );

		$result1 = Factory::get();

		$this->assertInstanceOf( Factory::class, $result1 );

		$result2 = Factory::get();

		$this->assertSame( $result1, $result2 );
	}

	/**
	 * Test ::get_components.
	 *
	 * @covers ::get_components
	 */
	public function test_get_components() {
		$result = $this->sut->get_components();

		$this->assert_is_array( $result );

		// Check some exemplary components.
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Components\Avatar_Handling::class ], $result, 'Component missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Components\Setup::class ], $result, 'Component missing.' );

		// Uninstallation must not (!) be included.
		$this->assert_not_contains( [ Factory::INSTANCE => \Avatar_Privacy\Components\Uninstallation::class ], $result, 'Uninstallation component should not be included.' );
	}

	/**
	 * Test ::get_default_icons.
	 *
	 * @covers ::get_default_icons
	 */
	public function test_get_default_icons() {
		$result = $this->sut->get_default_icons();

		$this->assert_is_array( $result );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Speech_Bubble_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Bowling_Pin_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider::class ], $result, 'Default icon missing.' );
	}

	/**
	 * Test ::get_plugin_integrations.
	 *
	 * @covers ::get_plugin_integrations
	 */
	public function test_get_plugin_integrations() {
		$result = $this->sut->get_plugin_integrations();

		$this->assert_is_array( $result );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Integrations\BBPress_Integration::class ], $result, 'Default icon missing.' );
	}

	/**
	 * Test ::get_cli_commands.
	 *
	 * @covers ::get_cli_commands
	 */
	public function test_get_cli_commands() {
		$result = $this->sut->get_cli_commands();

		$this->assert_is_array( $result );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\CLI\Database_Command::class ], $result, 'Command missing.' );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\CLI\Default_Command::class ], $result, 'Command missing.' );
	}

	/**
	 * Test ::get_database_tables.
	 *
	 * @covers ::get_database_tables
	 */
	public function test_get_database_tables() {
		$result = $this->sut->get_database_tables();

		$this->assert_is_array( $result );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Data_Storage\Database\Comment_Author_Table::class ], $result, 'Table missing.' );
	}

	/**
	 * Test ::get_avatar_handlers.
	 *
	 * @covers ::get_avatar_handlers
	 */
	public function test_get_avatar_handlers() {
		$result = $this->sut->get_avatar_handlers();

		$this->assert_is_array( $result );
		$this->assert_contains( [ Factory::INSTANCE => \Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler::class ], $result, 'Avatar Handler missing.' );
		$this->assertArrayHasKey( 'avatar_privacy_user_avatar_icon_url', $result );
	}

	/**
	 * Provides data for testing ::get_user_form_parameters.
	 *
	 * @return array
	 */
	public function provide_get_user_form_parameters_data() {
		return [
			[ Factory::USERFORM_PROFILE_INSTANCE, true ],
			[ Factory::USERFORM_FRONTEND_INSTANCE, true ],
			[ Factory::USERFORM_THEME_MY_LOGIN_PROFILES_INSTANCE, true ],
			[ Factory::USERFORM_BBPRESS_PROFILE_INSTANCE, true ],
			[ 'foobar', false ],
		];
	}

	/**
	 * Test ::get_user_form_parameters.
	 *
	 * @covers ::get_user_form_parameters
	 *
	 * @dataProvider provide_get_user_form_parameters_data
	 *
	 * @param  string $instance A named instance in Dice syntax.
	 * @param  bool   $valid    Whether the named instance is expected to be valid.
	 */
	public function test_get_user_form_parameters( $instance, $valid ) {
		if ( ! $valid ) {
			Functions\expect( 'esc_html' )->once()->andReturnFirstArg();
			$this->expect_exception( \InvalidArgumentException::class );
		}

		$result = $this->sut->get_user_form_parameters( $instance );

		if ( $valid ) {
			$this->assert_is_array( $result );
			$this->assertCount( 3, $result );
			$this->assertArrayHasKey( 0, $result );
			$this->assertArrayHasKey( 1, $result );
			$this->assertArrayHasKey( 2, $result );
			$this->assertArrayHasKey( 'nonce', $result[0] );
		} else {
			$this->assertNull( $result );
		}
	}
}
