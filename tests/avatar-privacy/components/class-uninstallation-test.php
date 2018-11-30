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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Uninstallation;

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

/**
 * Avatar_Privacy\Components\Uninstallation unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Uninstallation
 * @usesDefaultClass \Avatar_Privacy\Components\Uninstallation
 *
 * @uses ::__construct
 */
class Uninstallation_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Uninstallation
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$filesystem = [
			'uploads'    => [
				'avatar-privacy' => [
					'cache'       => [],
					'user-avatar' => [
						'foo.png' => 'FAKE_PNG',
					],
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->sut = m::mock( Uninstallation::class, [ 'plugin/file' ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Uninstallation::class )->makePartial();

		$mock->__construct( 'path/file' );

		$this->assertAttributeSame( 'path/file', 'plugin_file', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		$this->sut->shouldReceive( 'uninstall' )->once();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::uninstall.
	 *
	 * @covers ::uninstall
	 *
	 * @uses Avatar_Privacy\Data_Storage\Options::__construct
	 * @uses Avatar_Privacy\Data_Storage\Network_Options::__construct
	 * @uses Avatar_Privacy\Data_Storage\Transients::__construct
	 * @uses Avatar_Privacy\Data_Storage\Site_Transients::__construct
	 * @uses Avatar_Privacy\Data_Storage\Database::__construct
	 * @uses Avatar_Privacy\Data_Storage\Filesystem_Cache::__construct
	 * @uses Avatar_Privacy\Data_Storage\Filesystem_Cache::get_base_dir
	 * @uses Avatar_Privacy\Data_Storage\Filesystem_Cache::get_upload_dir
	 */
	public function test_uninstall() {
		Functions\expect( 'get_current_network_id' )->once()->andReturn( 0 );
		Functions\expect( 'get_transient' )->once()->andReturn( false );
		Functions\expect( 'get_site_transient' )->once()->andReturn( false );
		Functions\expect( 'wp_using_ext_object_cache' )->twice()->andReturn( true );
		Functions\expect( 'set_transient' )->once();
		Functions\expect( 'set_site_transient' )->once();
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'wp_get_upload_dir' )->once()->andReturn( [ 'basedir' => vfsStream::url( 'root/uploads' ) ] );
		Functions\expect( 'wp_mkdir_p' )->once()->andReturn( true );

		$this->sut->shouldReceive( 'delete_cached_files' )->once();
		$this->sut->shouldReceive( 'delete_uploaded_avatars' )->once();
		$this->sut->shouldReceive( 'delete_user_meta' )->once();
		$this->sut->shouldReceive( 'delete_options' )->once()->with( m::type( Options::class ), m::type( Network_Options::class ) );
		$this->sut->shouldReceive( 'delete_transients' )->once()->with( m::type( Transients::class ), m::type( Site_Transients::class ) );
		$this->sut->shouldReceive( 'drop_all_tables' )->once()->with( m::type( Database::class ) );

		$this->assertNull( $this->sut->uninstall() );
	}

	/**
	 * Tests ::delete_uploaded_avatars.
	 *
	 * @covers ::delete_uploaded_avatars
	 */
	public function test_delete_uploaded_avatars() {
		$user_avatar        = User_Avatar_Upload_Handler::USER_META_KEY;
		$query              = [
			'meta_key'     => $user_avatar,  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		];
		$user               = m::mock( 'WP_User' );
		$user->$user_avatar = [
			'file' => vfsStream::url( 'root/uploads/avatar-privacy/user-avatars/foo.png' ),
		];

		Functions\expect( 'get_users' )->once()->with( $query )->andReturn( [ $user ] );

		$this->assertNull( $this->sut->delete_uploaded_avatars() );
	}

	/**
	 * Tests ::delete_cached_files.
	 *
	 * @covers ::delete_cached_files
	 */
	public function test_delete_cached_files() {
		$file_cache = m::mock( Filesystem_Cache::class );
		$file_cache->shouldReceive( 'invalidate' )->once();

		$this->assertNull( $this->sut->delete_cached_files( $file_cache ) );
	}

	/**
	 * Tests ::drop_all_tables.
	 *
	 * @covers ::drop_all_tables
	 */
	public function test_drop_all_tables() {
		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$db = m::mock( Database::class );
		$db->shouldReceive( 'drop_table' )->once()->withNoArgs();

		$this->assertNull( $this->sut->drop_all_tables( $db ) );
	}

	/**
	 * Tests ::drop_all_tables.
	 *
	 * @covers ::drop_all_tables
	 */
	public function test_drop_all_tables_multisite() {
		$db         = m::mock( Database::class );
		$site_ids   = [ 1, 2, 10 ];
		$site_count = \count( $site_ids );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'get_sites' )->once()->with( [ 'fields' => 'ids' ] )->andReturn( $site_ids );
		$db->shouldReceive( 'drop_table' )->times( $site_count )->with( m::type( 'int' ) );

		$this->assertNull( $this->sut->drop_all_tables( $db ) );
	}

	/**
	 * Tests ::delete_user_meta.
	 *
	 * @covers ::delete_user_meta
	 */
	public function test_delete_user_meta() {
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, Core::GRAVATAR_USE_META_KEY, null, true );
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, Core::ALLOW_ANONYMOUS_META_KEY, null, true );
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, User_Avatar_Upload_Handler::USER_META_KEY, null, true );

		$this->assertNull( $this->sut->delete_user_meta() );
	}

	/**
	 * Tests ::delete_options.
	 *
	 * @covers ::delete_options
	 */
	public function test_delete_options() {
		$options         = m::mock( Options::class );
		$network_options = m::mock( Network_Options::class );

		$options->shouldReceive( 'delete' )->once()->with( Core::SETTINGS_NAME );
		$options->shouldReceive( 'reset_avatar_default' )->once();

		Functions\expect( 'is_multisite' )->once()->andReturn( false );

		$network_options->shouldReceive( 'delete' )->once()->with( Network_Options::USE_GLOBAL_TABLE );

		$this->assertNull( $this->sut->delete_options( $options, $network_options ) );
	}

	/**
	 * Tests ::delete_options.
	 *
	 * @covers ::delete_options
	 */
	public function test_delete_options_multisite() {
		$options         = m::mock( Options::class );
		$network_options = m::mock( Network_Options::class );
		$site_ids        = [ 1, 2, 3 ];
		$site_count      = \count( $site_ids );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'get_sites' )->once()->with( [ 'fields' => 'ids' ] )->andReturn( $site_ids );
		Functions\expect( 'switch_to_blog' )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( 'restore_current_blog' )->times( $site_count );

		// FIXME: The main site is included!
		$options->shouldReceive( 'delete' )->times( $site_count + 1 )->with( Core::SETTINGS_NAME );
		$options->shouldReceive( 'reset_avatar_default' )->times( $site_count + 1 );
		$network_options->shouldReceive( 'delete' )->once()->with( Network_Options::USE_GLOBAL_TABLE );

		$this->assertNull( $this->sut->delete_options( $options, $network_options ) );
	}

	/**
	 * Tests ::delete_transients.
	 *
	 * @covers ::delete_transients
	 */
	public function test_delete_transients() {
		$transients      = m::mock( Transients::class );
		$site_transients = m::mock( Site_Transients::class );

		$key1      = 'foo';
		$key2      = 'bar';
		$key3      = 'acme';
		$site_key1 = 'foobar';
		$site_key2 = 'barfoo';

		$transients->shouldReceive( 'get_keys_from_database' )->once()->andReturn( [ $key1, $key2, $key3 ] );
		$transients->shouldReceive( 'delete' )->once()->with( $key1, true );
		$transients->shouldReceive( 'delete' )->once()->with( $key2, true );
		$transients->shouldReceive( 'delete' )->once()->with( $key3, true );

		$site_transients->shouldReceive( 'get_keys_from_database' )->once()->andReturn( [ $site_key1, $site_key2 ] );
		$site_transients->shouldReceive( 'delete' )->once()->with( $site_key1, true );
		$site_transients->shouldReceive( 'delete' )->once()->with( $site_key2, true );

		$this->assertNull( $this->sut->delete_transients( $transients, $site_transients ) );
	}
}
