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
	 * @var Settings
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Options
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

		$this->sut = m::mock( Settings::class, [ self::VERSION, $this->options ] )->makePartial();
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
		$settings = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];
		$defaults = [
			'foo' => 'bar',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Settings::OPTION_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_repeated() {
		$original = [
			'foo'                      => 'bar',
			Options::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', $original );

		$this->options->shouldReceive( 'get' )
			->never();
		$this->sut->shouldReceive( 'get_defaults' )
			->never();

		$this->assertSame( $original, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_forced() {
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
			->with( Settings::OPTION_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_all_settings( true ) );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_no_version() {
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
			->with( Settings::OPTION_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_version_mismatch() {
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
			->with( Settings::OPTION_NAME, m::type( 'array' ) )
			->andReturn( $settings );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
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
