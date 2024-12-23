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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Components\Setup;

use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Data_Storage\Database\Comment_Author_Table;
use Avatar_Privacy\Data_Storage\Database\Hashes_Table;
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
	 * @var Options&m\MockInterface
	 */
	private $options;

	/**
	 * The options handler.
	 *
	 * @var Network_Options&m\MockInterface
	 */
	private $network_options;

	/**
	 * The transients handler.
	 *
	 * @var Transients&m\MockInterface
	 */
	private $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients&m\MockInterface
	 */
	private $site_transients;

	/**
	 * A database handler.
	 *
	 * @var Table&m\MockInterface
	 */
	private $table_one;

	/**
	 * A database handler.
	 *
	 * @var Table&m\MockInterface
	 */
	private $table_two;

	/**
	 * The multisite tools.
	 *
	 * @var Multisite&m\MockInterface
	 */
	private $multisite;

	/**
	 * The settings API.
	 *
	 * @var Settings&m\MockInterface
	 */
	private $settings;

	/**
	 * The user fields API.
	 *
	 * @var User_Fields&m\MockInterface
	 */
	private $registered_user;

	/**
	 * The user fields API.
	 *
	 * @var Comment_Author_Fields&m\MockInterface
	 */
	private $comment_author;

	/**
	 * The system-under-test.
	 *
	 * @var Setup&m\MockInterface
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
		$this->comment_author  = m::mock( Comment_Author_Fields::class );
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
				$this->comment_author,
				$this->transients,
				$this->site_transients,
				$this->options,
				$this->network_options,
				[
					Comment_Author_Table::TABLE_BASENAME => $this->table_one,
					Hashes_Table::TABLE_BASENAME         => $this->table_two,
				],
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
			$this->comment_author,
			$this->transients,
			$this->site_transients,
			$this->options,
			$this->network_options,
			$tables,
			$this->multisite
		);

		$this->assert_attribute_same( $this->settings, 'settings', $mock );
		$this->assert_attribute_same( $this->registered_user, 'registered_user', $mock );
		$this->assert_attribute_same( $this->comment_author, 'comment_author', $mock );
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
		Actions\expectAdded( 'deleted_user_meta' )->once()->with( [ $this->registered_user, 'remove_orphaned_local_avatar' ], 10, 4 );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Provides data for testing update_check.
	 *
	 * @return array
	 */
	public function provide_update_check_data() {
		return [
			[ '1.0', null, true, false ], // 0.4 or earlier.
			[ '1.0', null, true, false, true, true ], // 0.4 or earlier, multisite.
			[ '1.0', '0.5', true, false, true, false ], // multisite.
			[ '1.0', '1.0', false, false ],
			[ '1.0', null, true, true ],  // new installation.
		];
	}

	/**
	 * Tests ::update_check.
	 *
	 * @covers ::update_check
	 *
	 * @dataProvider provide_update_check_data
	 *
	 * @param  string $version       The simulated plugin version.
	 * @param  string $installed     The previously installed plugin version (may be empty).
	 * @param  bool   $update_needed Whether the data needs to be updated.
	 * @param  bool   $new_install   Whether this is a new installation.
	 * @param  bool   $multisite     Optional. Whether this is a multisite installation. Default false.
	 * @param  bool   $use_global    Optional. Whether this is a legacy installation which should default to the global table. Default false.
	 */
	public function test_update_check( $version, $installed, $update_needed, $new_install, $multisite = false, $use_global = false ) {
		$settings = $new_install ? [] : [ 'foo' => 'bar' ];
		if ( null !== $installed ) {
			$settings[ Options::INSTALLED_VERSION ] = $installed;
		}

		// Installed version matching.
		$match_installed = m::anyOf( $installed, '0.4-or-earlier', '' );

		$this->settings->shouldReceive( 'get_all_settings' )->once()->with( true )->andReturn( $settings );
		$this->settings->shouldReceive( 'get_version' )->once()->andReturn( $version );

		$this->table_one->shouldReceive( 'setup' )->once()->with( $match_installed );
		$this->table_two->shouldReceive( 'setup' )->once()->with( $match_installed );

		if ( $update_needed ) {
			if ( ! $new_install ) {
				$this->sut->shouldReceive( 'update_settings' )->once()->with( $match_installed, $settings )->andReturn( $settings );
				$this->sut->shouldReceive( 'update_plugin_data' )->once()->with( $match_installed );

				Functions\expect( 'is_multisite' )->once()->andReturn( $multisite );
				if ( $multisite && $use_global ) {
					$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::USE_GLOBAL_TABLE, true );
				}
			}

			$this->transients->shouldReceive( 'invalidate' )->once();
			$this->site_transients->shouldReceive( 'invalidate' )->once();
			$this->sut->shouldReceive( 'flush_rewrite_rules_soon' )->once();
		}

		$this->options->shouldReceive( 'set' )->once()->with(
			Settings::OPTION_NAME,
			m::on(
				function ( &$s ) use ( $version ) {
					$this->assertSame( $version, $s[ Options::INSTALLED_VERSION ] );

					return \is_array( $s );
				}
			)
		);

		$this->assertNull( $this->sut->update_check() );
	}

	/**
	 * Tests ::update_settings.
	 *
	 * @covers ::update_settings
	 */
	public function test_update_settings() {
		$previous = '0.4-or-earlier';
		$settings = [
			'foo'                   => 'bar',
			'mode_optin'            => 'foo',
			'use_gravatar'          => true,
			'mode_checkforgravatar' => 'bar',
			'default_show'          => false,
			'checkbox_default'      => true,
		];

		$result = $this->sut->update_settings( $previous, $settings );

		$this->assertArrayHasKey( 'foo', $result );
		$this->assertSame( 'bar', $result['foo'] );
		$this->assertArrayNotHasKey( 'mode_optin', $result );
		$this->assertArrayNotHasKey( 'use_gravatar', $result );
		$this->assertArrayNotHasKey( 'mode_checkforgravatar', $result );
		$this->assertArrayNotHasKey( 'default_show', $result );
		$this->assertArrayNotHasKey( 'checkbox_default', $result );
	}

	/**
	 * Tests ::update_settings.
	 *
	 * @covers ::update_settings
	 */
	public function test_update_settings_pre_1_0() {
		$previous = '0.9';
		$settings = [
			'foo' => 'bar',
		];

		$result = $this->sut->update_settings( $previous, $settings );

		$this->assertArrayHasKey( 'foo', $result );
		$this->assertSame( 'bar', $result['foo'] );
		$this->assertArrayNotHasKey( 'mode_optin', $result );
		$this->assertArrayNotHasKey( 'use_gravatar', $result );
		$this->assertArrayNotHasKey( 'mode_checkforgravatar', $result );
		$this->assertArrayNotHasKey( 'default_show', $result );
		$this->assertArrayNotHasKey( 'checkbox_default', $result );
	}

	/**
	 * Provides data for testing ::update_plugin_data.
	 *
	 * @return array
	 */
	public function provide_update_plugin_data_data() {
		return [
			[ '0.4-or-earlier', [ 'maybe_update_user_hashes', 'upgrade_old_avatar_defaults', 'prefix_usermeta_keys', 'maybe_add_email_hashes' ] ],
			[ '1.0-alpha.1', [ 'upgrade_old_avatar_defaults', 'prefix_usermeta_keys', 'maybe_add_email_hashes' ] ],
			[ '2.1.0-alpha.2', [ 'prefix_usermeta_keys', 'maybe_add_email_hashes' ] ],
			[ '2.4.0-beta.1', [ 'maybe_add_email_hashes' ] ],
			[ '2.4.0', [] ],
		];
	}

	/**
	 * Tests ::update_plugin_data.
	 *
	 * @covers ::update_plugin_data
	 *
	 * @dataProvider provide_update_plugin_data_data
	 *
	 * @param  string $previous Previously installed version.
	 * @param  array  $called   Called upgrade methods.
	 */
	public function test_update_plugin_data( $previous, array $called ) {
		$upgraders  = [
			'maybe_update_user_hashes',
			'upgrade_old_avatar_defaults',
			'prefix_usermeta_keys',
			'maybe_add_email_hashes',
		];
		$not_called = \array_diff( $upgraders, $called );

		foreach ( $called as $method ) {
			$this->sut->shouldReceive( $method )->once();
		}

		foreach ( $not_called as $method ) {
			$this->sut->shouldReceive( $method )->never();
		}

		$this->assertNull( $this->sut->update_plugin_data( $previous ) );
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
				function ( $task ) {
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

			$this->registered_user->shouldReceive( 'get_hash' )->once()->with( $u->ID )->andReturn( $hash );
		}

		$this->assertNull( $this->sut->maybe_update_user_hashes() );
	}

	/**
	 * Tests ::maybe_add_email_hashes.
	 *
	 * @covers ::maybe_add_email_hashes
	 */
	public function test_maybe_add_email_hashes() {
		global $wpdb;
		$wpdb                        = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy        = 'avatar_privacy';
		$wpdb->avatar_privacy_hashes = 'avatar_privacy_hashes';

		$emails      = [
			'foo@bar.com',
			'bar@foo.com',
			'hugo@example.org',
		];
		$rows        = [
			[
				'identifier' => $emails[0],
				'hash'       => 'hash1',
				'type'       => 'comment',
			],
			[
				'identifier' => $emails[1],
				'hash'       => 'hash2',
				'type'       => 'comment',
			],
			[
				'identifier' => $emails[2],
				'hash'       => 'hash3',
				'type'       => 'comment',
			],
		];
		$email_count = \count( $emails );

		$wpdb->shouldReceive( 'prepare' )->once()->with(
			'SELECT c.email FROM `%1$s` c LEFT OUTER JOIN `%2$s` h ON c.email = h.identifier AND h.type = "comment" AND h.hash IS NULL',
			$wpdb->avatar_privacy,
			$wpdb->avatar_privacy_hashes
		)->andReturn( 'EMAILS_QUERY' );
		$wpdb->shouldReceive( 'get_col' )->once()->with( 'EMAILS_QUERY' )->andReturn( $emails );

		$this->comment_author->shouldReceive( 'get_hash' )
			->times( $email_count )
			->with( m::type( 'string' ) )
			->andReturn( 'hash1', 'hash2', 'hash3' );

		$this->table_two->shouldReceive( 'insert_or_update' )->once()->with( [ 'identifier', 'hash', 'type' ], $rows )->andReturn( $email_count );

		$this->assertSame( $email_count, $this->sut->maybe_add_email_hashes() );
	}

	/**
	 * Tests ::maybe_add_email_hashes.
	 *
	 * @covers ::maybe_add_email_hashes
	 */
	public function test_maybe_add_email_hashes_not_needed() {
		global $wpdb;
		$wpdb                        = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy        = 'avatar_privacy';
		$wpdb->avatar_privacy_hashes = 'avatar_privacy_hashes';

		$emails = [];

		$wpdb->shouldReceive( 'prepare' )->once()->with(
			'SELECT c.email FROM `%1$s` c LEFT OUTER JOIN `%2$s` h ON c.email = h.identifier AND h.type = "comment" AND h.hash IS NULL',
			$wpdb->avatar_privacy,
			$wpdb->avatar_privacy_hashes
		)->andReturn( 'EMAILS_QUERY' );
		$wpdb->shouldReceive( 'get_col' )->once()->with( 'EMAILS_QUERY' )->andReturn( $emails );

		$this->comment_author->shouldReceive( 'get_hash' )->never();
		$this->table_two->shouldReceive( 'insert_or_update' )->never();

		$this->assertSame( 0, $this->sut->maybe_add_email_hashes() );
	}
}
