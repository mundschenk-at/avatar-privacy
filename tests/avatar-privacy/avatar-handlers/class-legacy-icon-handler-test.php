<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2024 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Legacy_Icon_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Network\Remote_Image_Service;

/**
 * Avatar_Privacy\Avatar_Handlers\Legacy_Icon_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Legacy_Icon_Handler
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Legacy_Icon_Handler
 *
 * @uses ::__construct
 */
class Legacy_Icon_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Legacy_Icon_Handler&m\MockInterface
	 */
	private $sut;

	/**
	 * The filesystem cache handler mock.
	 *
	 * @var Filesystem_Cache&m\MockInterface
	 */
	private $file_cache;

	/**
	 * The image editor support class.
	 *
	 * @var Remote_Image_Service&m\MockInterface
	 */
	private $remote_images;

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
		$this->file_cache    = m::mock( Filesystem_Cache::class );
		$this->remote_images = m::mock( Remote_Image_Service::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Legacy_Icon_Handler::class,
			[
				$this->file_cache,
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
		$mock          = m::mock( Legacy_Icon_Handler::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$file_cache    = m::mock( Filesystem_Cache::class );
		$remote_images = m::mock( Remote_Image_Service::class );

		$mock->__construct( $file_cache, $remote_images );

		$this->assert_attribute_same( $file_cache, 'file_cache', $mock );
		$this->assert_attribute_same( $remote_images, 'remote_images', $mock );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		// Input data.
		$force     = false;
		$hash      = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$image_url = 'https://some/remote/image';
		$size      = 42;
		$basedir   = '/basedir';
		$subdir    = 'a/b';
		$icon      = 'fake image data';
		$mimetype  = 'image/jpeg';
		$args              = [
			'mimetype' => $mimetype,
			'force'    => $force,
		];

		// Expected result.
		$url = 'https://some_url_for/the/avatar';

		$this->sut->shouldReceive( 'get_target_mime_type' )->once()->with( $image_url )->andReturn( $mimetype );

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash )->andReturn( $subdir );
		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->remote_images->shouldReceive( 'get_image' )->once()->with( $image_url, $size, $mimetype )->andReturn( $icon );

		$this->file_cache->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $icon, $force )->andReturn( true );
		$this->file_cache->shouldReceive( 'get_url' )->once()->with( m::type( 'string' ) )->andReturn( $url );

		$this->assertSame( $url, $this->sut->get_url( $image_url, $hash, $size, $args ) );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url_caching_error() {
		// Input data.
		$force     = false;
		$hash      = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$image_url = 'https://some/remote/image';
		$size      = 42;
		$basedir   = '/basedir';
		$subdir    = 'a/b';
		$icon      = 'fake image data';
		$mimetype  = 'image/jpeg';
		$args      = [
			'mimetype' => $mimetype,
			'force'    => $force,
		];

		$this->sut->shouldReceive( 'get_target_mime_type' )->once()->with( $image_url )->andReturn( $mimetype );

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash )->andReturn( $subdir );
		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->remote_images->shouldReceive( 'get_image' )->once()->with( $image_url, $size, $mimetype )->andReturn( $icon );

		$this->file_cache->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $icon, $force )->andReturn( false );
		$this->file_cache->shouldReceive( 'get_url' )->never();

		$this->assertSame( $image_url, $this->sut->get_url( $image_url, $hash, $size, $args ) );
	}

	/**
	 * Provides data for testing get_sub_dir.
	 *
	 * @return array
	 */
	public function provide_get_target_mime_type_data() {
		return [
			[ 'https://example.org/gravatar/foobar.png', 'image/png' ],
			[ 'https://example.org/gravatar/foobar.gif', 'image/png' ],
			[ 'https://example.org/gravatar/foobar.jpg', 'image/jpeg' ],
			[ 'https://example.org/gravatar/foobar.jpeg', 'image/jpeg' ],
			[ 'https://example.org/gravatar/foobar.svg', 'image/svg+xml' ],
		];
	}

	/**
	 * Tests ::get_target_mime_type.
	 *
	 * @covers ::get_target_mime_type
	 *
	 * @dataProvider provide_get_target_mime_type_data
	 *
	 * @param  string $url      The image URL.
	 * @param  string $mimetype The expected MIME type.
	 */
	public function test_get_target_mime_type( $url, $mimetype ) {
		Functions\expect( 'wp_parse_url' )->once()->with( $url, \PHP_URL_PATH )->andReturnUsing( 'parse_url' );

		$this->assertSame( $mimetype, $this->sut->get_target_mime_type( $url ) );
	}

	/**
	 * Provides data for testing get_sub_dir.
	 *
	 * @return array
	 */
	public function provide_get_sub_dir_data() {
		return [
			[ 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b', 'f/0' ],
			[ '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496', '7/4' ],
			[ '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6', '4/9' ],
		];
	}

	/**
	 * Tests ::get_sub_dir.
	 *
	 * @covers ::get_sub_dir
	 *
	 * @dataProvider provide_get_sub_dir_data
	 *
	 * @param  string $hash    The hashed identity.
	 * @param  string $result  The expected result.
	 */
	public function test_get_sub_dir( $hash, $result ) {
		$this->assertSame( $result, $this->sut->get_sub_dir( $hash ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_success() {
		// Input parameters.
		$type      = 'legacy';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Intermediate data.
		$remote_url = 'https://example.org/original/image.jpg';
		$args       = [
			'mimetype' => 'image/jpeg',
		];

		$this->remote_images->shouldReceive( 'get_image_url' )->once()->with( $hash )->andReturn( $remote_url );
		$this->sut->shouldReceive( 'get_url' )->once()->with( $remote_url, $hash, $size, $args )->andReturn( 'https://example.net/cached/image.jpg' );

		$this->assertTrue( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_failure() {
		// Input parameters.
		$type      = 'legacy';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Intermediate data.
		$remote_url = 'https://example.org/original/image.jpg';
		$args       = [
			'mimetype' => 'image/jpeg',
		];

		$this->remote_images->shouldReceive( 'get_image_url' )->once()->with( $hash )->andReturn( $remote_url );
		$this->sut->shouldReceive( 'get_url' )->once()->with( $remote_url, $hash, $size, $args )->andReturn( '' );

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_no_remote_url() {
		// Input parameters.
		$type      = 'legacy';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		$this->remote_images->shouldReceive( 'get_image_url' )->once()->with( $hash )->andReturn( '' );
		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::get_type.
	 *
	 * @covers ::get_type
	 */
	public function test_get_type() {
		$this->assertSame( 'legacy', $this->sut->get_type() );
	}
}
