<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019 Peter Putzer.
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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler as Upload;

/**
 * Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Default_Icons\Custom_Icon_Provider
 *
 * @uses ::__construct
 * @uses \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
 */
class Custom_Icon_Provider_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The upload handler.
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $images;

	/**
	 * The system-under-test.
	 *
	 * @var Custom_Icon_Provider
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
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->upload     = m::mock( Upload::class );
		$this->core       = m::mock( Core::class );
		$this->images     = m::mock( Images\Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Custom_Icon_Provider::class,
			[
				$this->file_cache,
				$this->upload,
				$this->core,
				$this->images,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses \Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider::__construct
	 */
	public function test_constructor() {
		// Dependencies.
		$file_cache = m::mock( Filesystem_Cache::class );
		$upload     = m::mock( Upload::class );
		$core       = m::mock( Core::class );
		$images     = m::mock( Images\Editor::class );

		$mock = m::mock( Custom_Icon_Provider::class )->makePartial()->shouldAllowMockingProtectedMethods();

		$this->invokeMethod( $mock, '__construct', [ $file_cache, $upload, $core, $images ] );

		$this->assert_attribute_same( $file_cache, 'file_cache', $mock );
		$this->assert_attribute_same( $upload, 'upload', $mock );
		$this->assert_attribute_same( $core, 'core', $mock );
		$this->assert_attribute_same( $images, 'images', $mock );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 */
	public function test_get_icon_url() {
		// Input parameters.
		$identity = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$size     = 64;

		// Intermediate data.
		$basedir  = 'the/file/cache/base/directory/';
		$data     = 'binary image data';
		$site_id  = 7;
		$hash     = 'some hash';
		$filename = "custom/{$site_id}/{$hash}-{$size}.png";
		$icon     = [
			'file' => '/the/original/image/file.png',
			'type' => 'image/png',
		];
		$settings = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => $icon,
		];

		// Expected result.
		$url         = 'https://some_host/my/beautiful/custom/image.png';
		$default_url = 'https://some_host/images/blank.gif';

		Functions\expect( 'includes_url' )->once()->with( 'images/blank.gif' )->andReturn( $default_url );

		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $settings );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->core->shouldReceive( 'get_hash' )->once()->with( "custom-default-{$site_id}" )->andReturn( $hash );

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->with()->andReturn( $basedir );

		$this->images->shouldReceive( 'get_image_editor' )->once()->with( $icon['file'] )->andReturn( m::mock( \WP_Image_Editor::class ) );
		$this->images->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( \WP_Image_Editor::class ), $size, $size, $icon['type'] )->andReturn( $data );

		$this->file_cache->shouldReceive( 'set' )->once()->with( $filename, $data )->andReturn( true );
		$this->file_cache->shouldReceive( 'get_url' )->once()->with( $filename )->andReturn( $url );

		$this->assertSame( $url, $this->sut->get_icon_url( $identity, $size ) );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 */
	public function test_get_icon_url_no_icon() {
		// Input parameters.
		$identity = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$size     = 64;

		// Intermediate data.
		$basedir  = 'the/file/cache/base/directory/';
		$data     = 'binary image data';
		$site_id  = 7;
		$hash     = 'some hash';
		$filename = "custom/{$site_id}/{$hash}-{$size}.png";
		$settings = [];

		// Expected result.
		$default_url = 'https://some_host/images/blank.gif';

		Functions\expect( 'includes_url' )->once()->with( 'images/blank.gif' )->andReturn( $default_url );

		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $settings );

		Functions\expect( 'get_current_blog_id' )->never();

		$this->core->shouldReceive( 'get_hash' )->never();

		$this->file_cache->shouldReceive( 'get_base_dir' )->never();

		$this->images->shouldReceive( 'get_image_editor' )->never();
		$this->images->shouldReceive( 'get_resized_image_data' )->never();

		$this->file_cache->shouldReceive( 'set' )->never();
		$this->file_cache->shouldReceive( 'get_url' )->never();

		$this->assertSame( $default_url, $this->sut->get_icon_url( $identity, $size ) );
	}

	/**
	 * Tests ::get_icon_url.
	 *
	 * @covers ::get_icon_url
	 */
	public function test_get_icon_url_empty_data() {
		// Input parameters.
		$identity = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$size     = 64;

		// Intermediate data.
		$basedir  = 'the/file/cache/base/directory/';
		$data     = '';
		$site_id  = 7;
		$hash     = 'some hash';
		$filename = "custom/{$site_id}/{$hash}-{$size}.png";
		$icon     = [
			'file' => '/the/original/image/file.png',
			'type' => 'image/png',
		];
		$settings = [
			Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR => $icon,
		];

		// Expected result.
		$default_url = 'https://some_host/images/blank.gif';

		Functions\expect( 'includes_url' )->once()->with( 'images/blank.gif' )->andReturn( $default_url );

		$this->core->shouldReceive( 'get_settings' )->once()->andReturn( $settings );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );

		$this->core->shouldReceive( 'get_hash' )->once()->with( "custom-default-{$site_id}" )->andReturn( $hash );

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->with()->andReturn( $basedir );

		$this->images->shouldReceive( 'get_image_editor' )->once()->with( $icon['file'] )->andReturn( m::mock( \WP_Image_Editor::class ) );
		$this->images->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( \WP_Image_Editor::class ), $size, $size, $icon['type'] )->andReturn( $data );

		$this->file_cache->shouldReceive( 'set' )->never();
		$this->file_cache->shouldReceive( 'get_url' )->never();

		$this->assertSame( $default_url, $this->sut->get_icon_url( $identity, $size ) );
	}

	/**
	 * Tests ::get_name.
	 *
	 * @covers ::get_name
	 */
	public function test_get_name() {
		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( 'icon name' );

		$this->assertSame( 'icon name', $this->sut->get_name() );
	}
}
