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

use Avatar_Privacy\Components\Setup;

use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database\Table;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Tools\Multisite;

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
	 * A database handler.
	 *
	 * @var Table
	 */
	private $table_one;

	/**
	 * A database handler.
	 *
	 * @var Table
	 */
	private $table_two;

	/**
	 * The multisite tools.
	 *
	 * @var Multisite
	 */
	private $multisite;

	/**
	 * The settings API.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The user fields API.
	 *
	 * @var User_Fields
	 */
	private $registered_user;
	/**
	 * The system-under-test.
	 *
	 * @var Setup
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		// Helper mocks.
		$this->settings        = m::mock( Settings::class );
		$this->registered_user = m::mock( User_Fields::class );
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );
		$this->options         = m::mock( Options::class );
		$this->network_options = m::mock( Network_Options::class );
		$this->table_one       = m::mock( Table::class );
		$this->table_two       = m::mock( Table::class );
		$this->multisite       = m::mock( Multisite::class );

		$this->sut = m::mock(
			Setup::class,
			[
				$this->settings,
				$this->registered_user,
				$this->transients,
				$this->site_transients,
				$this->options,
				$this->network_options,
				[ $this->table_one, $this->table_two ],
				$this->multisite,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$tables = [ $this->table_one, $this->table_two ];
		$mock   = m::mock( Setup::class )->makePartial();

		$mock->__construct(
			$this->settings,
			$this->registered_user,
			$this->transients,
			$this->site_transients,
			$this->options,
			$this->network_options,
			$tables,
			$this->multisite
		);

		$this->assert_attribute_same( $this->settings, 'settings', $mock );
		$this->assert_attribute_same( $this->registered_user, 'registered_user', $mock );
		$this->assert_attribute_same( $this->transients, 'transients', $mock );
		$this->assert_attribute_same( $this->site_transients, 'site_transients', $mock );
		$this->assert_attribute_same( $this->options, 'options', $mock );
		$this->assert_attribute_same( $this->network_options, 'network_options', $mock );
		$this->assert_attribute_same( $tables, 'tables', $mock );
		$this->assert_attribute_same( $this->multisite, 'multisite', $mock );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'register_deactivation_hook' )->once()->with( \AVATAR_PRIVACY_PLUGIN_FILE, [ $this->sut, 'deactivate' ] );

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
			[ '1.0', '', true ],
			[ '1.0', '1.0', true ],
			[ '1.0', null, false ],
		];
	}

	/**
	 * Tests ::update_check.
	 *
	 * @covers ::update_check
	 *
	 * @dataProvider provide_update_check_data
	 *
	 * @param  string $version        The simulated plugin version.
	 * @param  string $installed      The previously installed plugin version (may be empty).
	 * @param  bool   $settings_empty Whether the intial settings array is empty.
	 */
	public function test_update_check( $version, $installed, $settings_empty ) {
		$settings = $settings_empty ? [] : [ 'foo' => 'bar' ];
		if ( null !== $installed ) {
			$settings[ Options::INSTALLED_VERSION ] = $installed;
		}

		// Installed version matching.
		$match_installed = m::anyOf( $installed, '0.4-or-earlier', '' );

		$this->settings->shouldReceive( 'get_all_settings' )->once()->with( true )->andReturn( $settings );
		$this->settings->shouldReceive( 'get_version' )->once()->andReturn( $version );

		$this->table_one->shouldReceive( 'setup' )->once()->with( $match_installed );
		$this->table_two->shouldReceive( 'setup' )->once()->with( $match_installed );

		if ( $version !== $installed ) {
			$this->sut->shouldReceive( 'plugin_updated' )->once()->with( $match_installed, m::type( 'array' ) );
			$this->transients->shouldReceive( 'invalidate' )->once();
			$this->site_transients->shouldReceive( 'invalidate' )->once();
		}

		$this->options->shouldReceive( 'set' )->once()->with(
			Settings::OPTION_NAME,
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
		$this->sut->shouldReceive( 'prefix_usermeta_keys' )->once();
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();

		// Preserve pass-by-reference.
		$this->assertNull( $this->invoke_method( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

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
		$this->sut->shouldReceive( 'prefix_usermeta_keys' )->once();
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();

		// Preserve pass-by-reference.
		$this->assertNull( $this->invoke_method( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

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
		$this->sut->shouldReceive( 'prefix_usermeta_keys' )->once();
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();

		// Preserve pass-by-reference.
		$this->assertNull( $this->invoke_method( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

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
	public function test_plugin_updated_2_1() {
		$previous = '2.1.0';
		$settings = [
			'foo'          => 'bar',
		];

		// Global table use preserved for 0.4 or earlier.
		Functions\expect( 'is_multisite' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->sut->shouldReceive( 'maybe_update_user_hashes' )->never();
		$this->sut->shouldReceive( 'upgrade_old_avatar_defaults' )->never();
		$this->sut->shouldReceive( 'prefix_usermeta_keys' )->never();
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();

		// Preserve pass-by-reference.
		$this->assertNull( $this->invoke_method( $this->sut, 'plugin_updated', [ $previous, &$settings ] ) );

		$this->assertFalse( isset( $settings['mode_optin'] ) );
		$this->assertFalse( isset( $settings['use_gravatar'] ) );
		$this->assertFalse( isset( $settings['mode_checkforgravatar'] ) );
		$this->assertFalse( isset( $settings['default_show'] ) );
		$this->assertFalse( isset( $settings['checkbox_default'] ) );
	}

	/**
	 * Tests ::deactivate.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate() {
		$this->sut->shouldReceive( 'deactivate_plugin' )->once();

		$this->assertNull( $this->sut->deactivate( false ) );
	}

	/**
	 * Tests ::deactivate on a small network.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate_small_network() {
		Functions\expect( 'wp_is_large_network' )->once()->andReturn( false );

		$this->multisite->shouldReceive( 'do_for_all_sites_in_network' )->once()->with( [ $this->sut, 'deactivate_plugin' ] );
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->never();

		$this->assertNull( $this->sut->deactivate( true ) );
	}

	/**
	 * Tests ::deactivate on a large network.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate_large_network() {
		Functions\expect( 'wp_is_large_network' )->once()->andReturn( true );

		$this->multisite->shouldReceive( 'do_for_all_sites_in_network' )->once()->with(
			m::on(
				function( $task ) {
					Functions\expect( 'wp_unschedule_hook' )->once()->with( Image_Proxy::CRON_JOB_ACTION );
					$task( null );

					return true;
				}
			)
		);
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->never();

		$this->assertNull( $this->sut->deactivate( true ) );
	}

	/**
	 * Tests ::flush_rewrite_rules_soon.
	 *
	 * @covers ::flush_rewrite_rules_soon
	 */
	public function test_flush_rewrite_rules_soon() {
		$this->options->shouldReceive( 'delete' )->once()->with( 'rewrite_rules', true );

		$this->assertNull( $this->sut->flush_rewrite_rules_soon() );
	}

	/**
	 * Tests ::prefix_usermeta_keys.
	 *
	 * @covers ::prefix_usermeta_keys
	 */
	public function test_prefix_usermeta_keys() {
		global $wpdb;
		$wpdb           = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->usermeta = 'wp_usermeta';
		$user_ids       = [ 1, 2, 4 ];
		$rows           = \count( $user_ids );

		// Update meta keys.
		$wpdb->shouldReceive( 'prepare' )->once()->with( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", 'use_gravatar' )->andReturn( 'select_query' );
		$wpdb->shouldReceive( 'get_col' )->once()->with( 'select_query' )->andReturn( $user_ids );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->usermeta, [ 'meta_key' => User_Fields::GRAVATAR_USE_META_KEY ], [ 'meta_key' => 'use_gravatar' ] )->andReturn( $rows ); // phpcs:ignore WordPress.DB.SlowDBQuery

		// Clear cache.
		Functions\expect( 'wp_cache_delete' )->times( $rows )->with( m::type( 'int' ), 'user_meta' );

		$this->assertNull( $this->sut->prefix_usermeta_keys() );
	}

	/**
	 * Tests ::deactivate_plugin.
	 *
	 * @covers ::deactivate_plugin
	 */
	public function test_deactivate_plugin() {
		Functions\expect( 'wp_unschedule_hook' )->once()->with( Image_Proxy::CRON_JOB_ACTION );
		$this->options->shouldReceive( 'reset_avatar_default' )->once();
		$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();

		$this->assertNull( $this->sut->deactivate_plugin() );
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

			$this->registered_user->shouldReceive( 'get_hash' )->once()->with( $u->user_email )->andReturn( $hash );
			Functions\expect( 'update_user_meta' )->once()->with( $u->ID, User_Fields::EMAIL_HASH_META_KEY, $hash );
		}

		$this->assertNull( $this->sut->maybe_update_user_hashes() );
	}
}
