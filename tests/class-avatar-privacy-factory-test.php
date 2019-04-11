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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tests;

use Dice\Dice;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;

use Mockery as m;

/**
 * Avatar_Privacy_Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy_Factory
 * @usesDefaultClass \Avatar_Privacy_Factory
 */
class Avatar_Privacy_Factory_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy_Factory
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
		parent::setUp();

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
		set_include_path( 'vfs://root/' ); // @codingStandardsIgnoreLine

		// Set up the mock.
		$this->sut = m::mock( \Avatar_Privacy_Factory::class )->makePartial()->shouldAllowMockingProtectedMethods();
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

		$this->assertAttributeCount( \count( $rules ), 'rules', $this->sut );
		$this->assertAttributeInternalType( 'array', 'rules', $this->sut );
	}

	/**
	 * Test ::get_rules.
	 *
	 * @covers ::get_rules
	 */
	public function test_get_rules() {
		$version       = '6.6.6';
		$integrations  = [
			[ 'instance' => \Avatar_Privacy\Integrations\BBPress_Integration::class ],
		];
		$default_icons = [
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ],
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ],
			[ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Wavatar_Icon_Provider::class ],
		];

		$this->sut->shouldReceive( 'get_plugin_version' )->once()->with( AVATAR_PRIVACY_PLUGIN_FILE )->andReturn( $version );
		$this->sut->shouldReceive( 'get_plugin_integrations' )->once()->andReturn( $integrations );
		$this->sut->shouldReceive( 'get_default_icons' )->once()->andReturn( $default_icons );

		$result = $this->sut->get_rules();

		$this->assertInternalType( 'array', $result );
		$this->assertArrayHasKey( \Avatar_Privacy\Core::class, $result );
		$this->assertArrayHasKey( \Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler::class, $result );
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
	 * @uses Avatar_Privacy_Factory::__construct
	 * @uses Avatar_Privacy_Factory::get_default_icons
	 * @uses Avatar_Privacy_Factory::get_plugin_integrations
	 * @uses Avatar_Privacy_Factory::get_plugin_version
	 * @uses Avatar_Privacy_Factory::get_rules
	 */
	public function test_get() {
		Functions\expect( 'get_plugin_data' )->once()->with( m::type( 'string' ), false, false )->andReturn( [ 'Version' => '42' ] );

		$result1 = \Avatar_Privacy_Factory::get();

		$this->assertInstanceOf( \Avatar_Privacy_Factory::class, $result1 );

		$result2 = \Avatar_Privacy_Factory::get();

		$this->assertSame( $result1, $result2 );
	}

	/**
	 * Test ::get_default_icons.
	 *
	 * @covers ::get_default_icons
	 */
	public function test_get_default_icons() {
		$result = $this->sut->get_default_icons();

		$this->assertInternalType( 'array', $result );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Mystery_Icon_Provider::class ], $result, 'Default icon missing.', false, true, true );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons\Identicon_Icon_Provider::class ], $result, 'Default icon missing.', false, true, true );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Speech_Bubble_Icon_Provider::class ], $result, 'Default icon missing.', false, true, true );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons\Bowling_Pin_Icon_Provider::class ], $result, 'Default icon missing.', false, true, true );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider::class ], $result, 'Default icon missing.', false, true, true );
	}

	/**
	 * Test ::get_plugin_integrations.
	 *
	 * @covers ::get_plugin_integrations
	 */
	public function test_get_plugin_integrations() {
		$result = $this->sut->get_plugin_integrations();

		$this->assertInternalType( 'array', $result );
		$this->assertContains( [ 'instance' => \Avatar_Privacy\Integrations\BBPress_Integration::class ], $result, 'Default icon missing.', false, true, true );
	}
}
