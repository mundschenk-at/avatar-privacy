<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Icon_Provider;

use Avatar_Privacy\Tools\Network\Remote_Image_Service;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler
 *
 * @uses ::__construct
 */
class Default_Icons_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Default_Icons_Handler&m\MockInterface
	 */
	private $sut;

	/**
	 * The image editor support class.
	 *
	 * @var Remote_Image_Service&m\MockInterface
	 */
	private $remote_images;

	/**
	 * An array of icon provider mocks.
	 *
	 * @var Icon_Provider&m\MockInterface[]
	 */
	private $icon_providers;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'uploads' => [
				'delete' => [
					'existing_file.txt'  => 'CONTENT',
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Helper mocks.
		$this->icon_providers = [
			m::mock( Icon_Provider::class ),
			m::mock( Icon_Provider::class ),
			m::mock( Icon_Provider::class ),
		];
		$this->remote_images  = m::mock( Remote_Image_Service::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Default_Icons_Handler::class,
			[
				$this->icon_providers,
				$this->remote_images,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock           = m::mock( Default_Icons_Handler::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$icon_providers = [
			m::mock( Icon_Provider::class ),
			m::mock( Icon_Provider::class ),
		];
		$remote_images  = m::mock( Remote_Image_Service::class );

		$mock->__construct( $icon_providers, $remote_images );

		$this->assert_attribute_same( $icon_providers, 'icon_providers', $mock );
		$this->assert_attribute_same( $remote_images, 'remote_images', $mock );
	}

	/**
	 * Tests ::get_provider_mapping.
	 *
	 * @covers ::get_provider_mapping
	 */
	public function test_get_provider_mapping() {

		// Aliases for the mocked icon provider.
		$provider1 = $this->icon_providers[0];
		$provider2 = $this->icon_providers[1];
		$provider3 = $this->icon_providers[2];

		// Expected result.
		$expected = [
			'provider1a' => $this->icon_providers[0],
			'provider1b' => $provider1,
			'provider2'  => $provider2,
			'provider3'  => $provider3,
		];

		$provider1->shouldReceive( 'get_provided_types' )->once()->andReturn( [ 'provider1a', 'provider1b' ] );
		$provider2->shouldReceive( 'get_provided_types' )->once()->andReturn( [ 'provider2' ] );
		$provider3->shouldReceive( 'get_provided_types' )->once()->andReturn( [ 'provider3' ] );

		// The result should not change between calls.
		$this->assertSame( $expected, $this->sut->get_provider_mapping() );
		$this->assertSame( $expected, $this->sut->get_provider_mapping() );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		// Input data.
		$default_icon_type = 'foobar';
		$force             = false;
		$hash              = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url       = 'https://some/default';
		$size              = 42;
		$args              = [
			'type'     => $default_icon_type,
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Interim data.
		$provider1      = m::mock( Icon_Provider::class );
		$provider2      = m::mock( Icon_Provider::class );
		$icon_providers = [
			'foo'    => $provider1,
			'bar'    => $provider1,
			'foobar' => $provider2,
		];

		// Expected result.
		$url = 'https://some_url_for/the/avatar';

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_provider_mapping' )->once()->andReturn( $icon_providers );

		$provider1->shouldReceive( 'get_icon_url' )->never();
		$provider2->shouldReceive( 'get_icon_url' )->once()->with( $hash, $size, $force )->andReturn( $url );

		$this->assertSame( $url, $this->sut->get_url( $default_url, $hash, $size, $args ) );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url_no_provider() {
		// Input data.
		$default_icon_type = 'foobar';
		$force             = false;
		$hash              = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url       = 'https://some/default';
		$size              = 42;
		$args              = [
			'type'     => $default_icon_type,
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Interim data.
		$provider1      = m::mock( Icon_Provider::class );
		$provider2      = m::mock( Icon_Provider::class );
		$icon_providers = [
			'foo'    => $provider1,
			'bar'    => $provider1,
			'fugazi' => $provider2,
		];

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_provider_mapping' )->once()->andReturn( $icon_providers );

		$provider1->shouldReceive( 'get_icon_url' )->never();
		$provider2->shouldReceive( 'get_icon_url' )->never();

		$this->remote_images->shouldReceive( 'validate_image_url' )->once()->with( $default_icon_type, 'default_icon' )->andReturn( false );

		$this->assertSame( $default_url, $this->sut->get_url( $default_url, $hash, $size, $args ) );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url_no_provider_but_image_url() {
		// Input data.
		$default_icon_type = 'https://some/image';
		$force             = false;
		$hash              = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url       = 'https://some/default';
		$size              = 42;
		$args              = [
			'type'     => $default_icon_type,
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Interim data.
		$provider1      = m::mock( Icon_Provider::class );
		$provider2      = m::mock( Icon_Provider::class );
		$icon_providers = [
			'foo'    => $provider1,
			'bar'    => $provider1,
			'fugazi' => $provider2,
		];

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_provider_mapping' )->once()->andReturn( $icon_providers );

		$provider1->shouldReceive( 'get_icon_url' )->never();
		$provider2->shouldReceive( 'get_icon_url' )->never();

		$this->remote_images->shouldReceive( 'validate_image_url' )->once()->with( $default_icon_type, 'default_icon' )->andReturn( true );
		$this->remote_images->shouldReceive( 'get_hash' )->once()->with( $default_icon_type )->andReturn( $hash );

		Filters\expectApplied( 'avatar_privacy_legacy_icon_url' )->once()->with( $default_icon_type, $hash, $size, m::type( 'array' ) )->andReturn( $default_icon_type );

		$this->assertSame( $default_icon_type, $this->sut->get_url( $default_url, $hash, $size, $args ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_success() {
		// Input parameters.
		$type      = 'monsterid';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Intermediate data.
		$args = [
			'type' => $type,
		];

		$this->sut->shouldReceive( 'get_url' )->once()->with( '', $hash, $size, $args )->andReturn( 'https://foobar.org/cached_avatar_url' );

		$this->assertTrue( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_failure() {
		// Input parameters.
		$type      = 'monsterid';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Intermediate data.
		$args = [
			'type' => $type,
		];

		$this->sut->shouldReceive( 'get_url' )->once()->with( '', $hash, $size, $args )->andReturn( '' );

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::get_type.
	 *
	 * @covers ::get_type
	 */
	public function test_get_type() {
		$this->assertSame( '', $this->sut->get_type() );
	}

	/**
	 * Tests ::avatar_defaults.
	 *
	 * @covers ::avatar_defaults
	 **/
	public function test_avatar_defaults() {
		// Input parameters.
		$avatar_defaults = [
			'mm'                => 'Mystery Person',
			'foo'               => 'Foo',
			'bar'               => 'Bar',
			'gravatar_default'  => 'Gravatar logo',
		];

		// Aliases for the mocked icon provider.
		$provider1 = $this->icon_providers[0];
		$provider2 = $this->icon_providers[1];
		$provider3 = $this->icon_providers[2];

		$provider1->shouldReceive( 'get_option_value' )->once()->andReturn( 'new_default_icon' );
		$provider2->shouldReceive( 'get_option_value' )->once()->andReturn( 'another_new_default_icon' );
		$provider3->shouldReceive( 'get_option_value' )->once()->andReturn( 'and_another_one' );
		$provider1->shouldReceive( 'get_name' )->once()->andReturn( 'A new default icon' );
		$provider2->shouldReceive( 'get_name' )->once()->andReturn( 'Another new default icon' );
		$provider3->shouldReceive( 'get_name' )->once()->andReturn( 'And another one' );

		// Call the method.
		$result = $this->sut->avatar_defaults( $avatar_defaults );

		// Check that the result is an array of strings.
		$this->assertContainsOnly( 'string', $result );

		// Standard default icons are still here.
		$this->assertArrayHasKey( 'mm', $result );
		$this->assertArrayHasKey( 'foo', $result );
		$this->assertArrayHasKey( 'bar', $result );

		// Except the Gravatar logo.
		$this->assertArrayNotHasKey( 'gravatar_default', $result );

		// And here are our new default icons.
		$this->assertArrayHasKey( 'new_default_icon', $result );
		$this->assertArrayHasKey( 'another_new_default_icon', $result );
		$this->assertArrayHasKey( 'and_another_one', $result );
	}
}
