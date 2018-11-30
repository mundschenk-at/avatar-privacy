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

use Avatar_Privacy\Components\Setup;

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Avatar_Privacy\Components\Setup unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Setup
 * @usesDefaultClass \Avatar_Privacy\Components\Setup
 *
 * @uses ::__construct
 */
class Setup_Test extends \Avatar_Privacy\Tests\TestCase {

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
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The system-under-test.
	 *
	 * @var Setup
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		// Helper mocks.
		$this->core            = m::mock( Core::class );
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );
		$this->options         = m::mock( Options::class );
		$this->network_options = m::mock( Network_Options::class );
		$this->database        = m::mock( Database::class );

		$this->sut = m::mock( Setup::class, [ 'plugin/file', $this->core, $this->transients, $this->site_transients, $this->options, $this->network_options, $this->database ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Setup::class )->makePartial();

		$mock->__construct( 'path/file', $this->core, $this->transients, $this->site_transients, $this->options, $this->network_options, $this->database );

		$this->assertAttributeSame( 'path/file', 'plugin_file', $mock );
		$this->assertAttributeSame( $this->core, 'core', $mock );
		$this->assertAttributeSame( $this->transients, 'transients', $mock );
		$this->assertAttributeSame( $this->site_transients, 'site_transients', $mock );
		$this->assertAttributeSame( $this->options, 'options', $mock );
		$this->assertAttributeSame( $this->network_options, 'network_options', $mock );
		$this->assertAttributeSame( $this->database, 'database', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'register_activation_hook' )->once()->with( 'plugin/file', [ $this->sut, 'activate' ] );
		Functions\expect( 'register_deactivation_hook' )->once()->with( 'plugin/file', [ $this->sut, 'deactivate' ] );

		Actions\expectAdded( 'plugins_loaded' )->once()->with( [ $this->sut, 'update_check' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Provides data for testing update_check.
	 *
	 * @return array
	 */
	public function provide_update_check_data() {
		return [
			[ '1.0', '' ],
			[ '1.0', '1.0' ],
			[ '1.0', null ],
		];
	}

	/**
	 * Tests ::update_check.
	 *
	 * @covers ::update_check
	 *
	 * @dataProvider provide_update_check_data
	 *
	 * @param  string $version   The simulated plugin version.
	 * @param  string $installed The previously installed plugin version (may be empty).
	 */
	public function test_update_check( $version, $installed ) {
		$settings = [];
		if ( null !== $installed ) {
			$settings[ Options::INSTALLED_VERSION ] = $installed;
		}

		// Installed version matching.
		$match_installed = m::anyOf( $installed, '0.4-or-earlier', '' );

		$this->core->shouldReceive( 'get_settings' )->once()->with( true )->andReturn( $settings );
		$this->core->shouldReceive( 'get_version' )->once()->andReturn( $version );
		$this->database->shouldReceive( 'maybe_create_table' )->once()->with( $match_installed )->andReturn( true );
		$this->sut->shouldReceive( 'maybe_update_table_data' )->once()->with( $match_installed );

		if ( $version !== $installed ) {
			$this->sut->shouldReceive( 'plugin_updated' )->once()->with( $match_installed, m::type( 'array' ) );
			$this->transients->shouldReceive( 'invalidate' )->once();
			$this->site_transients->shouldReceive( 'invalidate' )->once();
		}

		$this->options->shouldReceive( 'set' )->once()->with(
			Core::SETTINGS_NAME,
			m::on(
				function( &$s ) use ( $version ) {
					$this->assertSame( $version, $s[ Options::INSTALLED_VERSION ] );

					return \is_array( $s );
				}
			)
		);

		$this->assertNull( $this->sut->update_check() );
	}

	/**
	 * Tests ::plugin_updated.
	 *
	 * @covers ::plugin_updated
	 */
	public function test_plugin_updated() {
		$previous = '0.4-or-earlier';
		$settings = [
			'foo'          => 'bar',
			'mode_optin'   => true,
			'use_gravatar' => true,
		];

		// Global table use preserved for 0.4 or earlier.
		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::USE_GLOBAL_TABLE, true );

		$this->sut->shouldReceive( 'maybe_update_user_hashes' )->once();
		$this->sut->shouldReceive( 'upgrade_old_avatar_defaults' )->once();

		Actions\expectAdded( 'init' )->once()->with( 'flush_rewrite_rules' );

		// Preserve pass-by-reference.
		$this->assertNull( $this->invokeMethod( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

		$this->assertFalse( isset( $settings['mode_optin'] ) );
		$this->assertFalse( isset( $settings['use_gravatar'] ) );
		$this->assertFalse( isset( $settings['mode_checkforgravatar'] ) );
		$this->assertFalse( isset( $settings['default_show'] ) );
		$this->assertFalse( isset( $settings['checkbox_default'] ) );
	}

	/**
	 * Tests ::plugin_updated.
	 *
	 * @covers ::plugin_updated
	 */
	public function test_plugin_updated_pre_1_0() {
		$previous = '0.9';
		$settings = [
			'foo'          => 'bar',
		];

		// Global table use preserved for 0.4 or earlier.
		Functions\expect( 'is_multisite' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->sut->shouldReceive( 'maybe_update_user_hashes' )->never();
		$this->sut->shouldReceive( 'upgrade_old_avatar_defaults' )->once();

		Actions\expectAdded( 'init' )->once()->with( 'flush_rewrite_rules' );

		// Preserve pass-by-reference.
		$this->assertNull( $this->invokeMethod( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

		$this->assertFalse( isset( $settings['mode_optin'] ) );
		$this->assertFalse( isset( $settings['use_gravatar'] ) );
		$this->assertFalse( isset( $settings['mode_checkforgravatar'] ) );
		$this->assertFalse( isset( $settings['default_show'] ) );
		$this->assertFalse( isset( $settings['checkbox_default'] ) );
	}

	/**
	 * Tests ::plugin_updated.
	 *
	 * @covers ::plugin_updated
	 */
	public function test_plugin_updated_1_0() {
		$previous = '1.0';
		$settings = [
			'foo'          => 'bar',
		];

		// Global table use preserved for 0.4 or earlier.
		Functions\expect( 'is_multisite' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->sut->shouldReceive( 'maybe_update_user_hashes' )->never();
		$this->sut->shouldReceive( 'upgrade_old_avatar_defaults' )->never();

		Actions\expectAdded( 'init' )->once()->with( 'flush_rewrite_rules' );

		// Preserve pass-by-reference.
		$this->assertNull( $this->invokeMethod( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

		$this->assertFalse( isset( $settings['mode_optin'] ) );
		$this->assertFalse( isset( $settings['use_gravatar'] ) );
		$this->assertFalse( isset( $settings['mode_checkforgravatar'] ) );
		$this->assertFalse( isset( $settings['default_show'] ) );
		$this->assertFalse( isset( $settings['checkbox_default'] ) );
	}

	/**
	 * Tests ::activate.
	 *
	 * @covers ::activate
	 */
	public function test_activate() {
		Functions\expect( 'flush_rewrite_rules' )->once();

		$this->assertNull( $this->sut->activate() );
	}

	/**
	 * Tests ::deactivate.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate() {
		$this->sut->shouldReceive( 'disable_cron_jobs' )->once();
		Functions\expect( 'flush_rewrite_rules' )->once();
		$this->options->shouldReceive( 'reset_avatar_default' )->once();

		$this->assertNull( $this->sut->deactivate() );
	}

	/**
	 * Provides data for testing upgrade_old_defaults.
	 *
	 * @return array
	 */
	public function provide_upgrade_old_avatar_defaults_data() {
		return [
			[ 'comment', true ],
			[ 'im-user-offline', true ],
			[ 'view-media-artist', true ],
			[ 'mystery', false ],
			[ 'foobar', false ],
		];
	}

	/**
	 * Tests ::upgrade_old_avatar_defaults.
	 *
	 * @covers ::upgrade_old_avatar_defaults
	 *
	 * @dataProvider provide_upgrade_old_avatar_defaults_data
	 *
	 * @param  string $old_default The old value of `avatar_default`.
	 * @param  bool   $upgrade     Whether the value should be upgraded.
	 */
	public function test_upgrade_old_avatar_defaults( $old_default, $upgrade ) {
		$this->options->shouldReceive( 'get' )->once()->with( 'avatar_default', 'mystery', true )->andReturn( $old_default );

		if ( $upgrade ) {
			$this->options->shouldReceive( 'set' )->once()->with( 'avatar_default', m::type( 'string' ), true, true );
		} else {
			$this->options->shouldReceive( 'set' )->never();
		}

		$this->assertNull( $this->sut->upgrade_old_avatar_defaults() );
	}


	/**
	 * Tests ::maybe_update_table_data.
	 *
	 * @covers ::maybe_update_table_data
	 */
	public function test_maybe_update_table_data() {
		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
		$wpdb->avatar_privacy = 'avatar_privacy';
		$previous             = '0.4';
		$rows                 = [
			(object) [
				'id'    => 1,
				'email' => 'mail@foobar.org',
			],
			(object) [
				'id'    => 3,
				'email' => 'foo@example.org',
			],
		];

		$wpdb->shouldReceive( 'get_results' )->once()->with( "SELECT id, email FROM {$wpdb->avatar_privacy} WHERE hash is null" )->andReturn( $rows );

		foreach ( $rows as $r ) {
			$this->core->shouldReceive( 'update_comment_author_hash' )->once()->with( $r->id, $r->email );
		}

		$this->assertNull( $this->sut->maybe_update_table_data( $previous ) );
	}

	/**
	 * Tests ::maybe_update_table_data.
	 *
	 * @covers ::maybe_update_table_data
	 */
	public function test_maybe_update_table_data_no_need() {
		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
		$wpdb->avatar_privacy = 'avatar_privacy';
		$previous             = '0.5';

		$wpdb->shouldReceive( 'get_results' )->never();
		$this->core->shouldReceive( 'update_comment_author_hash' )->never();

		$this->assertNull( $this->sut->maybe_update_table_data( $previous ) );
	}

	/**
	 * Tests ::maybe_update_user_hashes.
	 *
	 * @covers ::maybe_update_user_hashes
	 */
	public function test_maybe_update_user_hashes() {
		$user1 = (object) [
			'ID'         => 5,
			'user_email' => 'foo@bar.com',
		];
		$user2 = (object) [
			'ID'         => 33,
			'user_email' => 'foobar@example.org',
		];
		$users = [ $user1, $user2 ];

		Functions\expect( 'get_users' )->once()->with( m::type( 'array' ) )->andReturn( $users );

		foreach ( $users as $u ) {
			$hash = \md5( $u->user_email );

			$this->core->shouldReceive( 'get_hash' )->once()->with( $u->user_email )->andReturn( $hash );
			Functions\expect( 'update_user_meta' )->once()->with( $u->ID, Core::EMAIL_HASH_META_KEY, $hash );
		}

		$this->assertNull( $this->sut->maybe_update_user_hashes() );
	}

	/**
	 * Tests ::disable_cron_jobs.
	 *
	 * @covers ::disable_cron_jobs
	 */
	public function test_disable_cron_jobs() {
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'wp_unschedule_hook' )->once()->with( \Avatar_Privacy\Components\Image_Proxy::CRON_JOB_ACTION );

		$this->assertNull( $this->sut->disable_cron_jobs() );
	}

	/**
	 * Tests ::disable_cron_jobs.
	 *
	 * @covers ::disable_cron_jobs
	 */
	public function test_disable_cron_jobs_multisite() {
		$site_ids   = [ 1, 2, 10 ];
		$site_count = \count( $site_ids );

		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'get_sites' )->once()->with( [ 'fields' => 'ids' ] )->andReturn( $site_ids );
		Functions\expect( 'switch_to_blog' )->times( $site_count )->with( m::type( 'int' ) );
		Functions\expect( 'wp_unschedule_hook' )->times( $site_count )->with( \Avatar_Privacy\Components\Image_Proxy::CRON_JOB_ACTION );
		Functions\expect( 'restore_current_blog' )->times( $site_count );

		$this->assertNull( $this->sut->disable_cron_jobs() );
	}
}
