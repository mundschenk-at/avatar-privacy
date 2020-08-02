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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;

use Mockery as m;

/**
 * Avatar_Privacy\Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Factory
 * @usesDefaultClass \Avatar_Privacy\Factory
 */
class Factory_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Factory
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
		$this->sut = m::mock( \Avatar_Privacy\Factory::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$rules = [
			'Fake_Class_A' => [
				'shared'          => true,
				'constructParams' => [ 'A', 'B' ],
			],
			'Fake_Class_B' => [
				'constructParams' => [ 'C' ],
			],
		];

		$this->sut->shouldReceive( 'get_rules' )->once()->andReturn( $rules );

		// Manually call constructor.
		$this->sut->__construct();

		$resulting_rules = $this->get_value( $this->sut, 'rules' );
		$this->assert_is_array( $resulting_rules );
		$this->assertCount( \count( $rules ), $resulting_rules );
	}

	/**
	 * Test ::get_rules.
	 *
	 * @covers ::get_rules
	 */
	public function test_get_rules() {
		$version         = '6.6.6';
		$components      = [
			[ 'instance' => \Avatar_Privacy\Components\Setup::class ],
			[ 'instance' => \Avatar_Privacy\Components\Avatar_Handling::class ],
		];
		$integrations    = [
			[ 'instance' => \Avatar_Privacy\Integrations\BBPress_Integration::class ],
		];
		$default_icons   = [
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ],
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ],
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Wavatar_Icon_Provider::class ],
		];
		$cli_commands    = [
			[ 'instance' => \Avatar_Privacy\CLI\Database_Command::class ],
		];
		$tables          = [
			[ 'instance' => \Avatar_Privacy\Data_Storage\Database\Table::class ],
		];
		$avatar_handlers = [
			'some_hook' => [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Avatar_Handler::class ],
		];

		$this->sut->shouldReceive( 'get_plugin_version' )->once()->with( \AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $version );
		$this->sut->shouldReceive( 'get_components' )->once()->andReturn( $components );
		$this->sut->shouldReceive( 'get_plugin_integrations' )->once()->andReturn( $integrations );
		$this->sut->shouldReceive( 'get_default_icons' )->once()->andReturn( $default_icons );
		$this->sut->shouldReceive( 'get_cli_commands' )->once()->andReturn( $cli_commands );
		$this->sut->shouldReceive( 'get_avatar_handlers' )->once()->andReturn( $avatar_handlers );
		$this->sut->shouldReceive( 'get_database_tables' )->once()->andReturn( $tables );

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
	 * Test ::get. Should be run after tet_get_plugin_version.
	 *
	 * @covers ::get
	 *
	 * @uses Avatar_Privacy\Factory::__construct
	 * @uses Avatar_Privacy\Factory::get_default_icons
	 * @uses Avatar_Privacy\Factory::get_cli_commands
	 * @uses Avatar_Privacy\Factory::get_avatar_handlers
	 * @uses Avatar_Privacy\Factory::get_components
	 * @uses Avatar_Privacy\Factory::get_database_tables
	 * @uses Avatar_Privacy\Factory::get_plugin_integrations
	 * @uses Avatar_Privacy\Factory::get_plugin_version
	 * @uses Avatar_Privacy\Factory::get_rules
	 */
	public function test_get() {
		Functions\expect( 'get_plugin_data' )->once()->with( m::type( 'string' ), false, false )->andReturn( [ 'Version' => '42' ] );

		$result1 = \Avatar_Privacy\Factory::get();

		$this->assertInstanceOf( \Avatar_Privacy\Factory::class, $result1 );

		$result2 = \Avatar_Privacy\Factory::get();

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
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Components\Avatar_Handling::class ], $result, 'Component missing.' );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Components\Setup::class ], $result, 'Component missing.' );

		// Uninstallation must not (!) be included.
		$this->assert_not_contains( [ 'instance' => \Avatar_Privacy\Components\Uninstallation::class ], $result, 'Uninstallation component should not be included.' );
	}

	/**
	 * Test ::get_default_icons.
	 *
	 * @covers ::get_default_icons
	 */
	public function test_get_default_icons() {
		$result = $this->sut->get_default_icons();

		$this->assert_is_array( $result );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Speech_Bubble_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Bowling_Pin_Icon_Provider::class ], $result, 'Default icon missing.' );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider::class ], $result, 'Default icon missing.' );
	}

	/**
	 * Test ::get_plugin_integrations.
	 *
	 * @covers ::get_plugin_integrations
	 */
	public function test_get_plugin_integrations() {
		$result = $this->sut->get_plugin_integrations();

		$this->assert_is_array( $result );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Integrations\BBPress_Integration::class ], $result, 'Default icon missing.' );
	}

	/**
	 * Test ::get_cli_commands.
	 *
	 * @covers ::get_cli_commands
	 */
	public function test_get_cli_commands() {
		$result = $this->sut->get_cli_commands();

		$this->assert_is_array( $result );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\CLI\Database_Command::class ], $result, 'Command missing.' );
	}

	/**
	 * Test ::get_database_tables.
	 *
	 * @covers ::get_database_tables
	 */
	public function test_get_database_tables() {
		$result = $this->sut->get_database_tables();

		$this->assert_is_array( $result );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Data_Storage\Database\Comment_Author_Table::class ], $result, 'Table missing.' );
	}

	/**
	 * Test ::get_avatar_handlers.
	 *
	 * @covers ::get_avatar_handlers
	 */
	public function test_get_avatar_handlers() {
		$result = $this->sut->get_avatar_handlers();

		$this->assert_is_array( $result );
		$this->assert_contains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler::class ], $result, 'Avatar Handler missing.' );
		$this->assertArrayHasKey( 'avatar_privacy_user_avatar_icon_url', $result );
	}
}
