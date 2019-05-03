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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Avatar_Handlers;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler;
use Avatar_Privacy\Upload_Handlers\User_Avatar_Upload_Handler;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Images;

/**
 * Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler
 *
 * @uses ::__construct
 */
class User_Avatar_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var User_Avatar_Handler
	 */
	private $sut;

	/**
	 * The core API mock.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The filesystem cache handler mock.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $images;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$filesystem = [
			'uploads' => [
				'delete' => [
					'existing_file.txt'  => 'CONTENT',
				],
			],
		];

		// Set up virtual filesystem.
		$root = vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Helper mocks.
		$this->core       = m::mock( Core::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->images     = m::mock( Images\Editor::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			User_Avatar_Handler::class,
			[
				$this->core,
				$this->file_cache,
				$this->images,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock       = m::mock( User_Avatar_Handler::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$core       = m::mock( Core::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$images     = m::mock( Images\Editor::class );

		$mock->__construct( $core, $file_cache, $images );

		$this->assertAttributeSame( $core, 'core', $mock );
		$this->assertAttributeSame( $file_cache, 'file_cache', $mock );
		$this->assertAttributeSame( $images, 'images', $mock );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		$force       = false;
		$basedir     = '/basedir';
		$hash        = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url = 'https://some/default';
		$size        = 42;
		$subdir      = 'a/b';
		$args        = [
			'type'     => 'ignored',
			'avatar'   => '/image/path',
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Expected result.
		$image = 'fake image data';
		$url   = 'https://some_url_for/the/avatar';

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash )->andReturn( 'a/b' );

		$this->images->shouldReceive( 'get_image_editor' )->once()->with( $args['avatar'] )->andReturn( m::mock( \WP_Image_Editor::class ) );
		$this->images->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( \WP_Image_Editor::class ), $size, $size, $args['mimetype'] )->andReturn( $image );

		$this->file_cache->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $image, $force )->andReturn( true );
		$this->file_cache->shouldReceive( 'get_url' )->once()->with( m::type( 'string' ) )->andReturn( $url );

		$this->assertSame( $url, $this->sut->get_url( $default_url, $hash, $size, $args ) );
	}


	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url_no_data() {
		$force       = false;
		$basedir     = '/basedir';
		$hash        = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url = 'https://some/default';
		$size        = 42;
		$subdir      = 'a/b';
		$args        = [
			'type'     => 'ignored',
			'avatar'   => '/image/path',
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Expected result.
		$image = '';

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash )->andReturn( 'a/b' );

		$this->images->shouldReceive( 'get_image_editor' )->once()->with( $args['avatar'] )->andReturn( m::mock( \WP_Image_Editor::class ) );
		$this->images->shouldReceive( 'get_resized_image_data' )->once()->with( m::type( \WP_Image_Editor::class ), $size, $size, $args['mimetype'] )->andReturn( $image );

		$this->file_cache->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $image, $force )->andReturn( false );
		$this->file_cache->shouldReceive( 'get_url' )->never();

		$this->assertSame( $default_url, $this->sut->get_url( $default_url, $hash, $size, $args ) );
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
			[ '39ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6', '3/9' ],
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
	public function test_cache_image() {
		// Input parameters.
		$type      = 'image/jpeg';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpeg';

		// Fake user.
		$user     = m::mock( \WP_User::class );
		$user->ID = '666';

		// User avatar data.
		$local_avatar = [
			'file' => 'foobar.png',
			'type' => 'image/png',
		];

		// Intermediate data.
		$args = [
			'type'      => $type,
			'avatar'    => $local_avatar['file'],
			'mimetype'  => $local_avatar['type'],
			'subdir'    => $subdir,
			'extension' => $extension,
		];

		$this->core->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $user );
		$this->core->shouldReceive( 'get_user_avatar' )->once()->with( $user->ID )->andReturn( $local_avatar );

		$this->sut->shouldReceive( 'get_url' )->once()->with( '', $hash, $size, $args )->andReturn( 'https://foobar.org/cached_avatar_url' );

		$this->assertTrue( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_no_user() {
		// Input parameters.
		$type      = 'image/jpeg';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpeg';

		// Fake user.
		$user = null;

		$this->core->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $user );
		$this->core->shouldReceive( 'get_user_avatar' )->never();

		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_no_avatar() {
		// Input parameters.
		$type      = 'image/jpeg';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpeg';

		// Fake user.
		$user     = m::mock( \WP_User::class );
		$user->ID = '666';

		$this->core->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $user );
		$this->core->shouldReceive( 'get_user_avatar' )->once()->with( $user->ID )->andReturn( false );

		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}
}
