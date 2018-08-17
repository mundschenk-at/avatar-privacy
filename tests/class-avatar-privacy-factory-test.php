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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
		parent::setUp();

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, [
			'wordpress' => [
				'path' => [
					'wp-admin' => [
						'includes' => [
							'plugin.php' => "<?php Brain\\Monkey\\Functions\\expect( 'get_plugin_data' )->once()->andReturn( [ 'Version' => '6.6.6' ] ); ?>",
						],
					],
				],
			],
		] );
		set_include_path( 'vfs://root/' ); // @codingStandardsIgnoreLine
	}

	/**
	 * Test ::get.
	 *
	 * @covers ::get
	 *
	 * The Integrations list should be handled differently to prevent premature object creation.
	 *
	 * @uses \Avatar_Privacy\Components\User_Profile::__construct
	 * @uses \Avatar_Privacy\Core::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Cache::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Filesystem_Cache::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Filesystem_Cache::get_base_dir
	 * @uses \Avatar_Privacy\Data_Storage\Filesystem_Cache::get_upload_dir
	 * @uses \Avatar_Privacy\Data_Storage\Network_Options::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Options::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Site_Transients::__construct
	 * @uses \Avatar_Privacy\Data_Storage\Transients::__construct
	 * @uses \Avatar_Privacy\Integrations\BBPress_Integration::__construct
	 * @uses \Avatar_Privacy\Upload_Handlers\Upload_Handler::__construct
	 * @uses \Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler::__construct
	 */
	public function test_get() {
		Functions\expect( 'get_transient' )->once()->andReturn( 1 );
		Functions\expect( 'get_site_transient' )->once()->andReturn( 1 );
		Functions\expect( 'wp_cache_get' )->once()->andReturn( false );
		Functions\expect( 'wp_cache_set' )->once()->andReturn( true );
		Functions\expect( 'get_current_network_id' )->once()->andReturn( false );
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'wp_get_upload_dir' )->once()->andReturn( false );
		Functions\expect( 'wp_mkdir_p' )->once()->andReturn( true );

		define( 'ABSPATH', 'wordpress/path/' );

		$this->assertInstanceOf( Dice::class, \Avatar_Privacy_Factory::get( '/dummy/path' ) );
	}
}
