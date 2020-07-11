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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Uninstallation;

use Avatar_Privacy\Core;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table as Database;
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
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The database handler.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'uploads'    => [
				'avatar-privacy' => [
					'cache'        => [],
					'user-avatars' => [
						'foo.png' => 'FAKE_PNG',
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Helper mocks.
		$this->options         = m::mock( Options::class );
		$this->network_options = m::mock( Network_Options::class );
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );
		$this->database        = m::mock( Database::class );
		$this->file_cache      = m::mock( Filesystem_Cache::class );

		$this->sut = m::mock( Uninstallation::class, [ $this->options, $this->network_options, $this->transients, $this->site_transients, $this->database, $this->file_cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Uninstallation::class )->makePartial();

		$mock->__construct( $this->options, $this->network_options, $this->transients, $this->site_transients, $this->database, $this->file_cache );

		$this->assert_attribute_same( $this->options, 'options', $mock );
		$this->assert_attribute_same( $this->network_options, 'network_options', $mock );
		$this->assert_attribute_same( $this->transients, 'transients', $mock );
		$this->assert_attribute_same( $this->site_transients, 'site_transients', $mock );
		$this->assert_attribute_same( $this->database, 'database', $mock );
		$this->assert_attribute_same( $this->file_cache, 'file_cache', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		$this->sut->shouldReceive( 'enqueue_cleanup_tasks' )->once();
		$this->sut->shouldReceive( 'do_site_cleanups' )->once();

		Actions\expectDone( 'avatar_privacy_uninstallation_global' )->once();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::enqueue_cleanup_tasks.
	 *
	 * @covers ::enqueue_cleanup_tasks
	 */
	public function test_enqeueu_cleanup_tasks() {
		Actions\expectAdded( 'avatar_privacy_uninstallation_global' )->once()->with( [ $this->file_cache, 'invalidate' ], m::type( 'int' ), 0 );
		Actions\expectAdded( 'avatar_privacy_uninstallation_global' )->once()->with( [ $this->sut, 'delete_uploaded_avatars' ], m::type( 'int' ), 0 );

		// Delete usermeta for all users.
		Actions\expectAdded( 'avatar_privacy_uninstallation_global' )->once()->with( [ $this->sut, 'delete_user_meta' ], m::type( 'int' ), 0 );

		// Delete/change options (from all sites in case of a multisite network).
		Actions\expectAdded( 'avatar_privacy_uninstallation_site' )->once()->with( [ $this->sut, 'delete_options' ], m::type( 'int' ), 0 );
		Actions\expectAdded( 'avatar_privacy_uninstallation_global' )->once()->with( [ $this->sut, 'delete_network_options' ], m::type( 'int' ), 0 );

		// Delete transients from sitemeta or options table.
		Actions\expectAdded( 'avatar_privacy_uninstallation_site' )->once()->with( [ $this->sut, 'delete_transients' ], m::type( 'int' ), 0 );
		Actions\expectAdded( 'avatar_privacy_uninstallation_global' )->once()->with( [ $this->sut, 'delete_network_transients' ], m::type( 'int' ), 0 );

		// Drop all our tables.
		Actions\expectAdded( 'avatar_privacy_uninstallation_site' )->once()->with( [ $this->database, 'drop_table' ], m::type( 'int' ), 1 );

		$this->assertNull( $this->sut->enqueue_cleanup_tasks() );
	}

	/**
	 * Tests ::delete_uploaded_avatars.
	 *
	 * @covers ::delete_uploaded_avatars
	 */
	public function test_delete_uploaded_avatars() {
		$user_avatar        = User_Fields::USER_AVATAR_META_KEY;
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
	 * Tests ::do_site_cleanups.
	 *
	 * @covers ::do_site_cleanups
	 */
	public function test_do_site_cleanups() {
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'get_sites' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'restore_current_blog' )->never();

		Actions\expectDone( 'avatar_privacy_uninstallation_site' )->once()->with( null );

		$this->assertNull( $this->sut->do_site_cleanups() );
	}

	/**
	 * Tests ::do_site_cleanups.
	 *
	 * @covers ::do_site_cleanups
	 */
	public function test_do_site_cleanups_multisite() {
		$site_ids   = [ 1, 2, 10 ];
		$site_count = \count( $site_ids );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'get_sites' )->once()->with( m::subset( [ 'fields' => 'ids' ] ) )->andReturn( $site_ids );
		Functions\expect( 'switch_to_blog' )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( 'restore_current_blog' )->times( $site_count );

		Actions\expectDone( 'avatar_privacy_uninstallation_site' )->times( $site_count )->with( m::type( 'int' ) );

		$this->assertNull( $this->sut->do_site_cleanups() );
	}

	/**
	 * Tests ::delete_user_meta.
	 *
	 * @covers ::delete_user_meta
	 */
	public function test_delete_user_meta() {
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, User_Fields::GRAVATAR_USE_META_KEY, null, true );
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, User_Fields::ALLOW_ANONYMOUS_META_KEY, null, true );
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, User_Fields::USER_AVATAR_META_KEY, null, true );
		Functions\expect( 'delete_metadata' )->once()->with( 'user', 0, User_Fields::EMAIL_HASH_META_KEY, null, true );

		$this->assertNull( $this->sut->delete_user_meta() );
	}

	/**
	 * Tests ::delete_options.
	 *
	 * @covers ::delete_options
	 */
	public function test_delete_options() {
		$this->options->shouldReceive( 'delete' )->once()->with( Settings::OPTION_NAME );
		$this->options->shouldReceive( 'reset_avatar_default' )->once();

		$this->assertNull( $this->sut->delete_options() );
	}

	/**
	 * Tests ::delete_network_options.
	 *
	 * @covers ::delete_network_options
	 */
	public function test_delete_network_options() {
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::USE_GLOBAL_TABLE );
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::GLOBAL_TABLE_MIGRATION );
		$this->network_options->shouldReceive( 'delete' )->once()->with( Network_Options::START_GLOBAL_TABLE_MIGRATION );

		$this->assertNull( $this->sut->delete_network_options() );
	}

	/**
	 * Tests ::delete_transients.
	 *
	 * @covers ::delete_transients
	 */
	public function test_delete_transients() {
		$key1 = 'foo';
		$key2 = 'bar';
		$key3 = 'acme';

		$this->transients->shouldReceive( 'get_keys_from_database' )->once()->andReturn( [ $key1, $key2, $key3 ] );
		$this->transients->shouldReceive( 'delete' )->once()->with( $key1, true );
		$this->transients->shouldReceive( 'delete' )->once()->with( $key2, true );
		$this->transients->shouldReceive( 'delete' )->once()->with( $key3, true );

		$this->assertNull( $this->sut->delete_transients() );
	}

	/**
	 * Tests ::delete_network_transients.
	 *
	 * @covers ::delete_network_transients
	 */
	public function test_delete_network_transients() {
		$site_key1 = 'foobar';
		$site_key2 = 'barfoo';

		$this->site_transients->shouldReceive( 'get_keys_from_database' )->once()->andReturn( [ $site_key1, $site_key2 ] );
		$this->site_transients->shouldReceive( 'delete' )->once()->with( $site_key1, true );
		$this->site_transients->shouldReceive( 'delete' )->once()->with( $site_key2, true );

		$this->assertNull( $this->sut->delete_network_transients() );
	}
}
