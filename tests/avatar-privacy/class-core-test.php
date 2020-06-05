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

namespace Avatar_Privacy\Tests\Avatar_Privacy;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Core\Comment_Author_Fields;
use Avatar_Privacy\Core\User_Fields;
use Avatar_Privacy\Core\Hasher;

use Avatar_Privacy\Data_Storage\Options;

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
	 * @var Options
	 */
	private $options;

	/**
	 * Required helper object.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Required helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Required helper object.
	 *
	 * @var Comment_Author_Fields
	 */
	private $comment_author_fields;

	/**
	 * Required helper object.
	 *
	 * @var User_Fields
	 */
	private $user_fields;

	// Mock version.
	const VERSION = '1.0.0';

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		// Mock required helpers.
		$this->options               = m::mock( Options::class );
		$this->settings              = m::mock( Settings::class );
		$this->hasher                = m::mock( Hasher::class );
		$this->user_fields           = m::mock( User_Fields::class );
		$this->comment_author_fields = m::mock( Comment_Author_Fields::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Core::class,
			[
				self::VERSION,
				$this->options,
				$this->settings,
				$this->hasher,
				$this->user_fields,
				$this->comment_author_fields,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Necesssary clean-up work.
	 *
	 * @since 2.3.3 Renamed to `tear_down`.
	 */
	protected function tear_down() { // @codingStandardsIgnoreLine
		// Reset singleton.
		$this->set_static_value( Core::class, 'instance', null );

		parent::tear_down();
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
		$options               = m::mock( Options::class )->makePartial();
		$settings              = m::mock( Settings::class );
		$hasher                = m::mock( Hasher::class );
		$user_fields           = m::mock( User_Fields::class );
		$comment_author_fields = m::mock( Comment_Author_Fields::class );

		$core = m::mock( Core::class )->makePartial();
		$core->__construct( '6.6.6', $options, $settings, $hasher, $user_fields, $comment_author_fields );

		$this->assertSame( '6.6.6', $core->get_version() );
	}

	/**
	 * Tests singleton methods.
	 *
	 * @covers ::get_instance
	 * @covers ::set_instance
	 */
	public function test_singleton() {

		$core = m::mock( Core::class );
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
	 */
	public function test_get_instance_failing() {
		$this->expectException( \BadMethodCallException::class );
		$this->expectExceptionMessage( 'Avatar_Privacy\Core::get_instance called without prior plugin intialization.' );

		$core = \Avatar_Privacy\Core::get_instance();
	}

	/**
	 * Tests ::get_instance without a previous call to ::_get_instance (i.e. _doing_it_wrong).
	 *
	 * @covers ::set_instance
	 */
	public function test_set_instance_failing() {
		$core = m::mock( Core::class );

		// The first call is OK.
		\Avatar_Privacy\Core::set_instance( $core );

		// The second call fails with an exception.
		$this->expectException( \BadMethodCallException::class );
		$this->expectExceptionMessage( 'Avatar_Privacy\Core::set_instance called more than once.' );
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
		Functions\expect( '_deprecated_function' )->once()->with( Core::class . '::get_plugin_file', '2.3.0' );
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
		$this->set_value( $this->sut, 'settings', $original );

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
		$this->set_value( $this->sut, 'settings', $original );

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
		$this->set_value( $this->sut, 'settings', $original );

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
		$this->set_value( $this->sut, 'settings', $original );

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
	 * Tests ::comment_author_allows_gravatar_use.
	 *
	 * @covers ::comment_author_allows_gravatar_use
	 */
	public function test_comment_author_allows_gravatar_use() {
		$result      = true;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'allows_gravatar_use' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->comment_author_allows_gravatar_use( $id_or_email ) );
	}

	/**
	 * Tests ::comment_author_has_gravatar_policy.
	 *
	 * @covers ::comment_author_has_gravatar_policy
	 */
	public function test_comment_author_has_gravatar_policy() {
		$result      = true;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'has_gravatar_policy' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->comment_author_has_gravatar_policy( $id_or_email ) );
	}

	/**
	 * Tests ::get_comment_author_key.
	 *
	 * @covers ::get_comment_author_key
	 */
	public function test_get_comment_author_key() {
		$result      = 5;
		$id_or_email = 'some@email';

		$this->comment_author_fields->shouldReceive( 'get_key' )->once()->with( $id_or_email )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_comment_author_key( $id_or_email ) );
	}

	/**
	 * Tests ::get_comment_author_email.
	 *
	 * @covers ::get_comment_author_email
	 */
	public function test_get_comment_author_email() {
		$result = 'some@email';
		$hash   = 'some hash';

		$this->comment_author_fields->shouldReceive( 'get_email' )->once()->with( $hash )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_comment_author_email( $hash ) );
	}

	/**
	 * Tests ::get_user_hash.
	 *
	 * @covers ::get_user_hash
	 */
	public function test_get_user_hash() {
		$user_id = '666';
		$hash    = 'hashed_email';

		$this->user_fields->shouldReceive( 'get_hash' )->once()->with( $user_id )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_user_hash( $user_id ) );
	}

	/**
	 * Tests ::update_comment_author_gravatar_use.
	 *
	 * @covers ::update_comment_author_gravatar_use
	 */
	public function test_update_comment_author_gravatar_use() {
		$email        = 'foo@bar.org';
		$comment_id   = 66;
		$use_gravatar = true;

		$this->comment_author_fields->shouldReceive( 'update_gravatar_use' )->once()->with( $email, $comment_id, $use_gravatar );

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

		$this->comment_author_fields->shouldReceive( 'update_hash' )->once()->with( $id, $email );

		$this->assertNull( $this->sut->update_comment_author_hash( $id, $email ) );
	}

	/**
	 * Tests ::get_salt.
	 *
	 * @covers ::get_salt
	 */
	public function test_get_salt() {
		$expected_salt = 'random salt';

		Functions\expect( '_deprecated_function' )->once()->with( Core::class . '::get_salt', '2.4.0' );

		$this->hasher->shouldReceive( 'get_salt' )->once()->andReturn( $expected_salt );

		$this->assertSame( $expected_salt, $this->sut->get_salt() );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$email = 'example@example.org';
		$hash  = 'fake hash';

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( $email )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $email ) );
	}

	/**
	 * Tests ::get_user_by_hash.
	 *
	 * @covers ::get_user_by_hash
	 */
	public function test_get_user_by_hash() {
		$hash     = 'some hashed email';
		$expected = m::mock( 'WP_User' );

		$this->user_fields->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $expected );

		$this->assertSame( $expected, $this->sut->get_user_by_hash( $hash ) );
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

		$this->user_fields->shouldReceive( 'get_local_avatar' )->once()->with( $user_id )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_user_avatar( $user_id ) );
	}
}
