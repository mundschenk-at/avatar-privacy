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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Core;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Core\Settings;

use Avatar_Privacy\Data_Storage\Options;

/**
 * Avatar_Privacy\Core\Settings unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core\Settings
 * @usesDefaultClass \Avatar_Privacy\Core\Settings
 *
 * @uses ::__construct
 */
class Settings_Test extends \Avatar_Privacy\Tests\TestCase {

	const VERSION = '1.0.0';

	/**
	 * The system-under-test.
	 *
	 * @var Settings&m\MockInterface
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Options&m\MockInterface
	 */
	private $options;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( '__' )->returnArg();

		$this->options = m::mock( Options::class );

		$this->sut = m::mock( Settings::class, [ self::VERSION, $this->options ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses ::get_version
	 */
	public function test_constructor() {
		$version = '6.6.6';
		$options = m::mock( Options::class );

		$core = m::mock( Settings::class )->makePartial();
		$core->__construct( $version, $options );

		$this->assert_attribute_same( $version, 'version', $core );
		$this->assert_attribute_same( $options, 'options', $core );
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
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings() {
		$site_id  = 4711;
		$settings = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_repeated() {
		$site_id  = 4711;
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', [ $site_id => $original ] );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'load_settings' )->never();

		$this->assertSame( $original, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_forced() {
		$site_id  = 4711;
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', [ $site_id => $original ] );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings( true ) );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_no_version() {
		$site_id  = 4711;
		$original = [
			'foo' => 'bar',
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', [ $site_id => $original ] );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_version_mismatch() {
		$site_id  = 4711;
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => '1.2.3',
		];
		$settings = [
			'foo'                      => 'barfoo',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', [ $site_id => $original ] );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings() {
		$setting1 = 'foo';
		$settings = [
			$setting1                  => 'barfoo',
			Options::INSTALLED_VERSION => '1.2.3',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Settings::OPTION_NAME )
			->andReturn( $settings );

		$this->options->shouldReceive( 'set' )
			->once()
			->with( Settings::OPTION_NAME, m::type( 'array' ) );

		$result = $this->sut->load_settings();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( $setting1, $result );
		$this->assertSame( 'barfoo', $result[ $setting1 ] );
		$this->assertArrayHasKey( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR, $result );
		$this->assertSame( [], $result[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );
		$this->assertArrayHasKey( Settings::GRAVATAR_USE_DEFAULT, $result );
		$this->assertSame( false, $result[ Settings::GRAVATAR_USE_DEFAULT ] );
		$this->assertArrayHasKey( Options::INSTALLED_VERSION, $result );
		$this->assertSame( '1.2.3', $result[ Options::INSTALLED_VERSION ] );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings_invalid_result() {
		$this->options->shouldReceive( 'get' )
			->once()
			->with( Settings::OPTION_NAME )
			->andReturn( false );

		$this->options->shouldReceive( 'set' )
			->once()
			->with( Settings::OPTION_NAME, m::type( 'array' ) );

		$result = $this->sut->load_settings();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR, $result );
		$this->assertSame( [], $result[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] );
		$this->assertArrayHasKey( Settings::GRAVATAR_USE_DEFAULT, $result );
		$this->assertSame( false, $result[ Settings::GRAVATAR_USE_DEFAULT ] );
		$this->assertArrayHasKey( Options::INSTALLED_VERSION, $result );
		$this->assertSame( '', $result[ Options::INSTALLED_VERSION ] );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings_everything_in_order() {
		$settings = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => [
				'file' => '/some/avatar-image.png',
				'type' => 'image/png',
			],
			Settings::GRAVATAR_USE_DEFAULT         => true,
			Options::INSTALLED_VERSION             => '9.9.9',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Settings::OPTION_NAME )
			->andReturn( $settings );

		$this->options->shouldReceive( 'set' )->never();

		$this->assertSame( $settings, $this->sut->load_settings() );
	}

	/**
	 * Tests ::get.
	 *
	 * @covers ::get
	 */
	public function test_get() {
		$setting_key   = 'foo';
		$setting_value = 'bar';
		$settings      = [
			$setting_key => $setting_value,
			'something'  => 'else',
		];

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $settings );

		$this->assertSame( $setting_value, $this->sut->get( $setting_key ) );
	}

	/**
	 * Tests ::get.
	 *
	 * @covers ::get
	 */
	public function test_get_invalid_setting() {
		$setting_key   = 'foo';
		$setting_value = 'bar';
		$settings      = [
			$setting_key => $setting_value,
			'something'  => 'else',
		];

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $settings );

		Functions\expect( 'esc_html' )->once()->andReturnFirstArg();
		$this->expect_exception( \UnexpectedValueException::class );

		$this->assertNull( $this->sut->get( 'invalid setting' ) );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set() {
		$setting_key   = 'my_key';
		$setting_value = 'bar';

		$site_id       = 55;
		$orig_settings = [
			'a_setting'  => 666,
			$setting_key => 'some other value',
			'something'  => 'else',
		];

		$new_settings                 = $orig_settings;
		$new_settings[ $setting_key ] = $setting_value;

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $orig_settings );
		$this->options->shouldReceive( 'set' )->once()->with( Settings::OPTION_NAME, $new_settings )->andReturn( true );

		$this->assertTrue( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( [ $site_id => $new_settings ], 'settings', $this->sut );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set_db_error() {
		$setting_key   = 'my_key';
		$setting_value = 'bar';

		$site_id       = 55;
		$orig_settings = [
			'a_setting'  => 666,
			$setting_key => 'some other value',
			'something'  => 'else',
		];

		$new_settings                 = $orig_settings;
		$new_settings[ $setting_key ] = $setting_value;

		$cached_settings = $this->get_value( $this->sut, 'settings' );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $orig_settings );
		$this->options->shouldReceive( 'set' )->once()->with( Settings::OPTION_NAME, $new_settings )->andReturn( false );

		$this->assertFalse( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( $cached_settings, 'settings', $this->sut );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set_invalid_setting() {
		$setting_key   = 'invalid_key';
		$setting_value = 'bar';

		$site_id       = 55;
		$orig_settings = [
			'a_setting' => 666,
			'my_key'    => 'some other value',
			'something' => 'else',
		];

		$cached_settings = $this->get_value( $this->sut, 'settings' );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $orig_settings );

		Functions\expect( 'esc_html' )->once()->andReturnFirstArg();
		$this->expect_exception( \UnexpectedValueException::class );

		$this->options->shouldReceive( 'set' )->never();

		$this->assertNull( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( $cached_settings, 'settings', $this->sut );
	}

	/**
	 * Tests ::get_fields.
	 *
	 * @covers ::get_fields
	 */
	public function test_get_fields() {
		$information_header = 'INFORMATION_HEADER';

		$result = $this->sut->get_fields( $information_header );

		$this->assert_is_array( $result );
		$this->assertContainsOnly( 'string', \array_keys( $result ) );
		$this->assertContainsOnly( 'array', $result );
		$this->assertSame( $result[ Settings::INFORMATION_HEADER ]['elements'], [ $information_header ] );
	}

	/**
	 * Tests ::get_defaults.
	 *
	 * @covers ::get_defaults
	 *
	 * @uses ::get_fields
	 */
	public function test_get_defaults() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), '2.8.0' );

		$result = $this->sut->get_defaults();

		$this->assert_is_array( $result );
		$this->assertNotContainsOnly( 'array', $result );
		$this->assertSame( '', $result[ Options::INSTALLED_VERSION ] );
	}

	/**
	 * Tests ::get_network_fields.
	 *
	 * @covers ::get_network_fields
	 */
	public function test_get_network_fields() {
		$result = $this->sut->get_network_fields();

		$this->assert_is_array( $result );
		$this->assertContainsOnly( 'string', \array_keys( $result ) );
		$this->assertContainsOnly( 'array', $result );
	}
}
