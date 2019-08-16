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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Cache;
use Avatar_Privacy\Data_Storage\Network_Options;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

/**
 * Avatar_Privacy_Factory unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core
 * @usesDefaultClass \Avatar_Privacy\Core
 *
 * @uses ::__construct
 */
class Core_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Core
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * Required helper object.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * Required helper object.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Required helper object.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Required helper object.
	 *
	 * @var Settings
	 */
	private $settings;

	// Mock version.
	const VERSION = '1.0.0';

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
		parent::setUp();

		// Mock required helpers.
		$this->transients      = m::mock( Transients::class );
		$this->site_transients = m::mock( Site_Transients::class );
		$this->cache           = m::mock( Cache::class );
		$this->options         = m::mock( Options::class );
		$this->network_options = m::mock( Network_Options::class );
		$this->settings        = m::mock( Settings::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			\Avatar_Privacy\Core::class,
			[
				self::VERSION,
				$this->transients,
				$this->site_transients,
				$this->cache,
				$this->options,
				$this->network_options,
				$this->settings,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Necesssary clean-up work.
	 */
	protected function tearDown() { // @codingStandardsIgnoreLine
		// Reset singleton.
		$this->setStaticValue( \Avatar_Privacy\Core::class, 'instance', null );

		parent::tearDown();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses ::get_version
	 */
	public function test_constructor() {
		// Mock required helpers.
		$transients      = m::mock( Transients::class )->makePartial();
		$site_transients = m::mock( Site_Transients::class )->makePartial();
		$cache           = m::mock( Cache::class )->makePartial();
		$options         = m::mock( Options::class )->makePartial();
		$network_options = m::mock( Network_Options::class )->makePartial();
		$settings        = m::mock( Settings::class );

		$core = m::mock( \Avatar_Privacy\Core::class )->makePartial();
		$core->__construct( '6.6.6', $transients, $site_transients, $cache, $options, $network_options, $settings );

		$this->assertSame( '6.6.6', $core->get_version() );
	}

	/**
	 * Tests singleton methods.
	 *
	 * @covers ::get_instance
	 * @covers ::set_instance
	 */
	public function test_singleton() {

		$core = m::mock( \Avatar_Privacy\Core::class );
		\Avatar_Privacy\Core::set_instance( $core );

		$core2 = \Avatar_Privacy\Core::get_instance();
		$this->assertSame( $core, $core2 );

		// Check ::get_instance (no underscore).
		$core3 = \Avatar_Privacy\Core::get_instance();
		$this->assertSame( $core, $core3 );
	}

	/**
	 * Tests ::get_instance without a previous call to ::_get_instance (i.e. _doing_it_wrong).
	 *
	 * @covers ::get_instance
	 *
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage Avatar_Privacy\Core::get_instance called without prior plugin intialization.
	 */
	public function test_get_instance_failing() {
		$core = \Avatar_Privacy\Core::get_instance();
		$this->assertInstanceOf( \Avatar_Privacy\Core::class, $core );
	}

	/**
	 * Tests ::get_instance without a previous call to ::_get_instance (i.e. _doing_it_wrong).
	 *
	 * @covers ::set_instance
	 *
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage Avatar_Privacy\Core::set_instance called more than once.
	 */
	public function test_set_instance_failing() {
		$core = m::mock( \Avatar_Privacy\Core::class );
		\Avatar_Privacy\Core::set_instance( $core );
		\Avatar_Privacy\Core::set_instance( $core );
	}

	/**
	 * Tests ::get_version.
	 *
	 * @covers ::get_version
	 */
	public function test_get_version() {
		$this->assertSame( self::VERSION, $this->sut->get_version() );
	}

	/**
	 * Tests ::get_plugin_file.
	 *
	 * @covers ::get_plugin_file
	 */
	public function test_get_plugin_file() {
		$this->assertSame( \AVATAR_PRIVACY_PLUGIN_FILE, $this->sut->get_plugin_file() );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings() {
		$settings = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$defaults = [
			'foo' => 'bar',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( \Avatar_Privacy\Core::SETTINGS_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->settings->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_settings() );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings_repeated() {
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->setValue( $this->sut, 'settings', $original, \Avatar_Privacy\Core::class );

		$this->options->shouldReceive( 'get' )
			->never();
		$this->settings->shouldReceive( 'get_defaults' )
			->never();

		$this->assertSame( $original, $this->sut->get_settings() );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings_forced() {
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$defaults = [
			'foo' => 'bar',
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->setValue( $this->sut, 'settings', $original, \Avatar_Privacy\Core::class );

		$this->options->shouldReceive( 'get' )
			->once()
			->with( \Avatar_Privacy\Core::SETTINGS_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->settings->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_settings( true ) );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings_no_version() {
		$original = [
			'foo' => 'bar',
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$defaults = [
			'foo' => 'bar',
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->setValue( $this->sut, 'settings', $original, \Avatar_Privacy\Core::class );

		$this->options->shouldReceive( 'get' )
			->once()
			->with( \Avatar_Privacy\Core::SETTINGS_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->settings->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_settings() );
	}

	/**
	 * Tests ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings_version_mismatch() {
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => '1.2.3',
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$defaults = [
			'foo' => 'bar',
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->setValue( $this->sut, 'settings', $original, \Avatar_Privacy\Core::class );

		$this->options->shouldReceive( 'get' )
			->once()
			->with( \Avatar_Privacy\Core::SETTINGS_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->settings->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_settings() );
	}

	/**
	 * Data: id_or_email, returned object, use_gravatar result, has_gravatar_policy result.
	 *
	 * @return array
	 */
	public function provide_comment_author_data() {
		return [
			[ 'some_id_or_email', (object) [ 'use_gravatar' => true ], true, true ],
			[ 'some_id_or_email', (object) [ 'use_gravatar' => false ], false, true ],
			[ 'some_id_or_email', null, false, false ],
		];
	}

	/**
	 * Tests ::comment_author_allows_gravatar_use.
	 *
	 * @covers ::comment_author_allows_gravatar_use
	 *
	 * @dataProvider provide_comment_author_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param bool   $use_gravatar        An expected result.
	 * @param bool   $has_gravatar_policy An expected result.
	 */
	public function test_comment_author_allows_gravatar_use( $id_or_email, $object, $use_gravatar, $has_gravatar_policy ) {
		$this->sut->shouldReceive( 'load_data' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $use_gravatar, $this->sut->comment_author_allows_gravatar_use( $id_or_email ) );
	}

	/**
	 * Tests ::comment_author_has_gravatar_policy.
	 *
	 * @covers ::comment_author_has_gravatar_policy
	 *
	 * @dataProvider provide_comment_author_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param bool   $use_gravatar        An expected result.
	 * @param bool   $has_gravatar_policy An expected result.
	 */
	public function test_comment_author_has_gravatar_policy( $id_or_email, $object, $use_gravatar, $has_gravatar_policy ) {
		$this->sut->shouldReceive( 'load_data' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $has_gravatar_policy, $this->sut->comment_author_has_gravatar_policy( $id_or_email ) );
	}

	/**
	 * Data: id_or_email, returned object, database ID result.
	 *
	 * @return array
	 */
	public function provide_comment_author_key_data() {
		return [
			[ 'some_id_or_email', (object) [ 'id' => 5 ], 5 ],
			[ 'some_id_or_email', null, 0 ],
		];
	}

	/**
	 * Tests ::get_comment_author_key.
	 *
	 * @covers ::get_comment_author_key
	 *
	 * @dataProvider provide_comment_author_key_data
	 *
	 * @param mixed  $id_or_email         An ID or email (not really important here).
	 * @param object $object              The result object.
	 * @param int    $id                  The result.
	 */
	public function test_get_comment_author_key( $id_or_email, $object, $id ) {
		$this->sut->shouldReceive( 'load_data' )->once()->with( $id_or_email )->andReturn( $object );

		$this->assertSame( $id, $this->sut->get_comment_author_key( $id_or_email ) );
	}

	/**
	 * Data: id_or_email, returned object, email.
	 *
	 * @return array
	 */
	public function provide_get_comment_author_email_data() {
		return [
			[ 'should_be_a_hash', (object) [ 'email' => 5 ], 5 ],
			[ 'should_be_another_hash', null, '' ],
		];
	}

	/**
	 * Tests ::get_comment_author_email.
	 *
	 * @covers ::get_comment_author_email
	 *
	 * @dataProvider provide_get_comment_author_email_data
	 *
	 * @param mixed  $hash   A hashed email.
	 * @param object $object The result object.
	 * @param int    $email  The retrieved email.
	 */
	public function test_get_comment_author_email( $hash, $object, $email ) {
		$this->sut->shouldReceive( 'load_data_by_hash' )->once()->with( $hash )->andReturn( $object );

		$this->assertSame( $email, $this->sut->get_comment_author_email( $hash ) );
	}

	/**
	 * Provides data for testing load_data.
	 *
	 * @return array
	 */
	public function provide_load_data_data() {
		return [
			[ 'something other than an email address', 'load_data_by_hash' ],
			[ 'foo@bar.com', 'load_data_by_email' ],
		];
	}

	/**
	 * Tests ::load_data.
	 *
	 * @covers ::load_data
	 *
	 * @dataProvider provide_load_data_data
	 *
	 * @param  string $email_or_hash   Required.
	 * @param  string $expected_method Required.
	 */
	public function test_load_data( $email_or_hash, $expected_method ) {
		$this->sut->shouldReceive( $expected_method )->once()->andReturn( 'foo' );

		$this->assertSame( 'foo', $this->sut->load_data( $email_or_hash ) );
	}

	/**
	 * Tests ::load_data_by_email.
	 *
	 * @covers ::load_data_by_email
	 */
	public function test_load_data_by_email() {
		$email  = 'foo@bar.org';
		$hash   = 'foo_hashed';
		$result = (object) [ 'foo' => 'bar' ];
		$key    = \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_hash' )->with( $email )->andReturn( $hash );
		$this->cache->shouldReceive( 'get' )->with( $key )->andReturn( false );
		$wpdb->shouldReceive( 'prepare' )->with( m::type( 'string' ), $email )->andReturn( 'sql_string' );
		$wpdb->shouldReceive( 'get_row' )->with( 'sql_string', OBJECT )->andReturn( $result );
		$this->cache->shouldReceive( 'set' )->with( $key, $result, m::type( 'int' ) )->andReturn( false );

		$this->assertSame( $result, $this->sut->load_data_by_email( $email ) );
	}

	/**
	 * Tests ::load_data_by_email.
	 *
	 * @covers ::load_data_by_email
	 */
	public function test_load_data_by_email_cached() {
		$email  = 'foo@bar.org';
		$hash   = 'foo_hashed';
		$result = (object) [ 'foo' => 'bar' ];
		$key    = \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_hash' )->with( $email )->andReturn( $hash );
		$this->cache->shouldReceive( 'get' )->with( $key )->andReturn( $result );
		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'get_row' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $result, $this->sut->load_data_by_email( $email ) );
	}

	/**
	 * Tests ::load_data_by_email.
	 *
	 * @covers ::load_data_by_email
	 */
	public function test_load_data_by_email_invalid() {
		$email = '  ';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_hash' )->never();
		$this->cache->shouldReceive( 'get' )->never();
		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'get_row' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertNull( $this->sut->load_data_by_email( $email ) );
	}

	/**
	 * Tests ::load_data_by_hash.
	 *
	 * @covers ::load_data_by_hash
	 */
	public function test_load_data_by_hash() {
		$hash   = 'foo_hashed';
		$result = (object) [ 'foo' => 'bar' ];
		$key    = \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->cache->shouldReceive( 'get' )->with( $key )->andReturn( false );
		$wpdb->shouldReceive( 'prepare' )->with( m::type( 'string' ), $hash )->andReturn( 'sql_string' );
		$wpdb->shouldReceive( 'get_row' )->with( 'sql_string', OBJECT )->andReturn( $result );
		$this->cache->shouldReceive( 'set' )->with( $key, $result, m::type( 'int' ) )->andReturn( false );

		$this->assertSame( $result, $this->sut->load_data_by_hash( $hash ) );
	}

	/**
	 * Tests ::load_data_by_hash.
	 *
	 * @covers ::load_data_by_hash
	 */
	public function test_load_data_by_hash_cached() {
		$hash   = 'foo_hashed';
		$result = (object) [ 'foo' => 'bar' ];
		$key    = \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->cache->shouldReceive( 'get' )->with( $key )->andReturn( $result );
		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'get_row' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $result, $this->sut->load_data_by_hash( $hash ) );
	}

	/**
	 * Tests ::load_data_by_hash.
	 *
	 * @covers ::load_data_by_hash
	 */
	public function test_load_data_by_hash_invalid() {
		$hash = '';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->cache->shouldReceive( 'get' )->never();
		$wpdb->shouldReceive( 'prepare' )->never();
		$wpdb->shouldReceive( 'get_row' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertNull( $this->sut->load_data_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_hash.
	 *
	 * @covers ::get_user_hash
	 */
	public function test_get_user_hash() {
		$user_id = '666';
		$email   = 'foobar@email.org';
		$user    = (object) [ 'user_email' => $email ];
		$hash    = 'hashed_email';

		Functions\expect( 'get_user_meta' )->with( $user_id, \Avatar_Privacy\Core::EMAIL_HASH_META_KEY, true )->once()->andReturn( $hash );
		Functions\expect( 'get_user_by' )->never();
		$this->sut->shouldReceive( 'get_hash' )->never();
		Functions\expect( 'update_user_meta' )->never();

		$this->assertSame( $hash, $this->sut->get_user_hash( $user_id ) );
	}

	/**
	 * Tests ::get_user_hash.
	 *
	 * @covers ::get_user_hash
	 */
	public function test_get_user_hash_new() {
		$user_id = '666';
		$email   = 'foobar@email.org';
		$user    = (object) [ 'user_email' => $email ];
		$hash    = 'hashed_email';

		Functions\expect( 'get_user_meta' )->with( $user_id, \Avatar_Privacy\Core::EMAIL_HASH_META_KEY, true )->once()->andReturn( false );
		Functions\expect( 'get_user_by' )->with( 'ID', $user_id )->once()->andReturn( $user );
		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		Functions\expect( 'update_user_meta' )->with( $user_id, \Avatar_Privacy\Core::EMAIL_HASH_META_KEY, $hash )->once();

		$this->assertSame( $hash, $this->sut->get_user_hash( $user_id ) );
	}

	/**
	 * Tests ::get_user_hash.
	 *
	 * @covers ::get_user_hash
	 */
	public function test_get_user_hash_invalid_user_id() {
		$user_id = 55;

		Functions\expect( 'get_user_meta' )->with( $user_id, \Avatar_Privacy\Core::EMAIL_HASH_META_KEY, true )->once()->andReturn( false );
		Functions\expect( 'get_user_by' )->with( 'ID', $user_id )->once()->andReturn( false );

		$this->assertFalse( $this->sut->get_user_hash( $user_id ) );
	}

	/**
	 * Tests ::update_comment_author_data.
	 *
	 * @covers ::update_comment_author_data
	 */
	public function test_update_comment_author_data() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 5;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( $rows_updated );

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		$this->cache->shouldReceive( 'delete' )->once()->with( \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash );

		$this->assertSame( $rows_updated, $this->sut->update_comment_author_data( $id, $email, $columns ) );
	}

	/**
	 * Tests ::update_comment_author_data.
	 *
	 * @covers ::update_comment_author_data
	 */
	public function test_update_comment_author_data_error() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( false );

		$this->sut->shouldReceive( 'get_hash' )->never();
		$this->cache->shouldReceive( 'delete' )->never();

		$this->assertFalse( $this->sut->update_comment_author_data( $id, $email, $columns ) );
	}

	/**
	 * Tests ::update_comment_author_data.
	 *
	 * @covers ::update_comment_author_data
	 */
	public function test_update_comment_author_data_no_rows_updated() {
		$columns        = [ 'foo' => 'bar' ];
		$id             = 13;
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 0;

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'update' )->once()->with( $wpdb->avatar_privacy, $columns, [ 'id' => $id ], $format_strings, [ '%d' ] )->andReturn( $rows_updated );

		$this->sut->shouldReceive( 'get_hash' )->never();
		$this->cache->shouldReceive( 'delete' )->never();

		$this->assertSame( 0, $this->sut->update_comment_author_data( $id, $email, $columns ) );
	}

	/**
	 * Tests ::insert_comment_author_data.
	 *
	 * @covers ::insert_comment_author_data
	 */
	public function test_insert_comment_author_data() {
		$email          = 'foo@bar.org';
		$hash           = 'hashed $email';
		$use_gravatar   = true;
		$last_updated   = 'a timestamp';
		$log_message    = 'a log message';
		$format_strings = 'format strings for columns array';
		$rows_updated   = 1;

		$expected_columns = [
			'email'        => $email,
			'hash'         => $hash,
			'use_gravatar' => $use_gravatar,
			'last_updated' => $last_updated,
			'log_message'  => $log_message,
		];

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );

		$this->cache->shouldReceive( 'delete' )->once()->with( \Avatar_Privacy\Core::EMAIL_CACHE_PREFIX . $hash );

		$this->sut->shouldReceive( 'get_format_strings' )->once()->with( $expected_columns )->andReturn( $format_strings );
		$wpdb->shouldReceive( 'insert' )->once()->with( $wpdb->avatar_privacy, $expected_columns, $format_strings )->andReturn( $rows_updated );

		$this->assertSame( $rows_updated, $this->sut->insert_comment_author_data( $email, $use_gravatar, $last_updated, $log_message ) );
	}

	/**
	 * Provides data for testing update_comment_author_gravatar_use
	 *
	 * @return array
	 */
	public function provide_update_comment_author_gravatar_use_data() {
		return [
			[
				'foo@bar.org',
				5,
				true,
				(object) [
					'id'           => 77,
					'email'        => 'foo@bar.org',
					'use_gravatar' => true,
				],
			],
			[
				'foo@bar.org',
				5,
				true,
				(object) [
					'id'           => 77,
					'email'        => 'foo@bar.org',
					'use_gravatar' => false,
				],
			],
			[ 'foo@bar.org', 5, false, false ],
		];
	}

	/**
	 * Tests ::update_comment_author_gravatar_use.
	 *
	 * @covers ::update_comment_author_gravatar_use
	 *
	 * @dataProvider provide_update_comment_author_gravatar_use_data
	 *
	 * @param  string $email        An email address.
	 * @param  int    $comment_id   A comment ID.
	 * @param  bool   $use_gravatar The gravatar use flag.
	 * @param  object $data         The retrieved data.
	 */
	public function test_update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar, $data ) {
		$hash = 'hashed $email';

		global $wpdb;
		$wpdb                 = m::mock( 'wpdb' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb->avatar_privacy = 'avatar_privacy_table';
		$wpdb->site_id        = 1;
		$wpdb->blog_id        = 2;

		$this->sut->shouldReceive( 'load_data' )->once()->with( $email )->andReturn( $data );

		if ( empty( $data ) ) {
			Functions\expect( 'is_multisite' )->once()->andReturn( false );
			Functions\expect( 'current_time' )->once()->with( 'mysql' )->andReturn( 'a timestamp' );
			$this->sut->shouldReceive( 'insert_comment_author_data' )->once()->with( $email, $use_gravatar, m::type( 'string' ), m::type( 'string' ) );
		} else {
			if ( $data->use_gravatar !== $use_gravatar ) {
				Functions\expect( 'is_multisite' )->once()->andReturn( false );
				Functions\expect( 'current_time' )->once()->with( 'mysql' )->andReturn( 'a timestamp' );
				$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
				$this->sut->shouldReceive( 'update_comment_author_data' )->once()->with( $data->id, $data->email, m::type( 'array' ) );
			} elseif ( empty( $data->hash ) ) {
				$this->sut->shouldReceive( 'update_comment_author_hash' )->once()->with( $data->id, $data->email );
			}
		}

		$this->assertNull( $this->sut->update_comment_author_gravatar_use( $email, $comment_id, $use_gravatar ) );
	}

	/**
	 * Tests ::update_comment_author_hash.
	 *
	 * @covers ::update_comment_author_hash
	 */
	public function test_update_comment_author_hash() {
		$id    = 666;
		$email = 'foo@bar.com';
		$hash  = 'hashedemail123';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );
		$this->sut->shouldReceive( 'update_comment_author_data' )->once()->with( $id, $email, [ 'hash' => $hash ] );

		$this->assertNull( $this->sut->update_comment_author_hash( $id, $email ) );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' );

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::SALT )->andReturn( '' );

		Functions\expect( 'wp_rand' )->once()->andReturn( $expected_salt );

		$this->network_options->shouldReceive( 'set' )->once()->with( Network_Options::SALT, $expected_salt );

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt_filtered() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' )->andReturn( $expected_salt );

		$this->network_options->shouldReceive( 'get' )->never();
		Functions\expect( 'wp_rand' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt_from_options() {
		$expected_salt = 'random salt';

		Filters\expectApplied( 'avatar_privacy_salt' )->once()->with( '' );

		$this->network_options->shouldReceive( 'get' )->once()->with( Network_Options::SALT )->andReturn( $expected_salt );

		Functions\expect( 'wp_rand' )->never();
		$this->network_options->shouldReceive( 'set' )->never();

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$salt  = 'foobar57';
		$email = 'example@example.org';

		$this->sut->shouldReceive( 'get_salt' )->once()->andReturn( $salt );

		$this->assertSame( '874ccc6634195fdf4a1e5391a623fddb8a347d26cad4d9bbda683923afca3132', $this->sut->get_hash( $email ) );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash_with_whitespace() {
		$salt  = 'foobar57';
		$email = '   example@example.org ';

		$this->sut->shouldReceive( 'get_salt' )->once()->andReturn( $salt );

		$this->assertSame( '874ccc6634195fdf4a1e5391a623fddb8a347d26cad4d9bbda683923afca3132', $this->sut->get_hash( $email ) );
	}

	/**
	 * Tests ::get_format_strings.
	 *
	 * @covers ::get_format_strings
	 */
	public function test_get_format_strings() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->assertSame( $expected, $this->sut->get_format_strings( $columns ) );
	}

	/**
	 * Tests ::get_format_strings.
	 *
	 * @covers ::get_format_strings
	 */
	public function test_get_format_strings_invalid_column() {
		$columns  = [
			'log_message'  => 'foo',
			'use_gravatar' => 1,
			'hash'         => 'bar',
			'foo'          => 'bar',
		];
		$expected = [ '%s', '%d', '%s' ];

		$this->expectException( \RuntimeException::class );

		$this->assertSame( $expected, $this->sut->get_format_strings( $columns ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash() {
		$hash     = 'some hashed email';
		$expected = m::mock( 'WP_User' );
		$users    = [ $expected ];

		Functions\expect( 'get_users' )->once()->with( m::type( 'array' ) )->andReturn( $users );

		$this->assertSame( $expected, $this->sut->get_user_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash_not_found() {
		$hash  = 'some hashed email';
		$users = [];

		Functions\expect( 'get_users' )->once()->with( m::type( 'array' ) )->andReturn( $users );

		$this->assertNull( $this->sut->get_user_by_hash( $hash ) );
	}

	/**
	 * Tests ::get_user_avatar.
	 *
	 * @covers ::get_user_avatar
	 */
	public function test_get_user_avatar() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( null );
		Functions\expect( 'get_user_meta' )->once()->with( $user_id, \Avatar_Privacy\Core::USER_AVATAR_META_KEY, true )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_user_avatar.
	 *
	 * @covers ::get_user_avatar
	 */
	public function test_get_user_avatar_invalid_filter_result() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( [ 'file' => '/some/other/file' ] );
		Functions\expect( 'get_user_meta' )->once()->with( $user_id, \Avatar_Privacy\Core::USER_AVATAR_META_KEY, true )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_user_avatar( $user_id ) );
	}

	/**
	 * Tests ::get_user_avatar.
	 *
	 * @covers ::get_user_avatar
	 */
	public function test_get_user_avatar_invalid_filtered() {
		$user_id = 42;
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		Filters\expectApplied( 'avatar_privacy_pre_get_user_avatar' )->once()->with( null, $user_id )->andReturn( $avatar );
		Functions\expect( 'get_user_meta' )->never();

		$this->assertSame( $avatar, $this->sut->get_user_avatar( $user_id ) );
	}
}
