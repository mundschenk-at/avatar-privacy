<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler;

use Avatar_Privacy\Core;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Tools\Network\Gravatar_Service;

/**
 * Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler
 * @usesDefaultClass \Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler
 *
 * @uses ::__construct
 */
class Gravatar_Cache_Handler_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Gravatar_Cache_Handler
	 */
	private $sut;

	/**
	 * The core API mock.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The options handler mock.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The filesystem cache handler mock.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The image editor support class.
	 *
	 * @var Gravatar_Service
	 */
	private $gravatar;

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
		$this->core       = m::mock( Core::class );
		$this->options    = m::mock( Options::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->gravatar   = m::mock( Gravatar_Service::class );

		// Partially mock system under test.
		$this->sut = m::mock(
			Gravatar_Cache_Handler::class,
			[
				$this->core,
				$this->options,
				$this->file_cache,
				$this->gravatar,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock       = m::mock( Gravatar_Cache_Handler::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$core       = m::mock( Core::class );
		$options    = m::mock( Options::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$gravatar   = m::mock( Gravatar_Service::class );

		$mock->__construct( $core, $options, $file_cache, $gravatar );

		$this->assert_attribute_same( $core, 'core', $mock );
		$this->assert_attribute_same( $options, 'options', $mock );
		$this->assert_attribute_same( $file_cache, 'file_cache', $mock );
		$this->assert_attribute_same( $gravatar, 'gravatar', $mock );
	}

	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		$force       = false;
		$rating      = 'pg';
		$email       = 'some@email';
		$basedir     = '/basedir';
		$hash        = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url = 'https://some/default';
		$size        = 42;
		$subdir      = 'a/b';
		$args        = [
			'user_id'  => false, // Not relevant for this test.
			'email'    => $email,
			'rating'   => $rating,
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Expected result.
		$image = 'fake image data';
		$url   = 'https://some_url_for/the/avatar';

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash, m::type( 'bool' ) )->andReturn( $subdir );

		$this->gravatar->shouldReceive( 'get_image' )->once()->with( $email, $size, $rating )->andReturn( $image );

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
		$rating      = 'pg';
		$email       = 'some@email';
		$basedir     = '/basedir';
		$hash        = 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b';
		$default_url = 'https://some/default';
		$size        = 42;
		$subdir      = 'a/b';
		$args        = [
			'user_id'  => false, // Not relevant for this test.
			'email'    => $email,
			'rating'   => $rating,
			'mimetype' => 'image/jpeg',
			'force'    => $force,
		];

		// Expected result.
		$image = '';

		Functions\expect( 'wp_parse_args' )->once()->with( $args, m::type( 'array' ) )->andReturn( $args );

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->sut->shouldReceive( 'get_sub_dir' )->once()->with( $hash, m::type( 'bool' ) )->andReturn( $subdir );

		$this->gravatar->shouldReceive( 'get_image' )->once()->with( $email, $size, $rating )->andReturn( $image );

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
			[ 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b', true, 'a/0' ],
			[ 'f0e4c2f76c58916ec258f246851bea091d14d4247a2fc3e18694461b1816e13b', false, 'f/0' ],
			[ '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496', true, 'a/4' ],
			[ '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496', false, '7/4' ],
			[ '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6', true, '4/9' ],
			[ '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6', false, 'b/9' ],
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
	 * @param  bool   $user    If the identity belongs to a user account.
	 * @param  string $result  The expected result.
	 */
	public function test_get_sub_dir( $hash, $user, $result ) {
		$this->assertSame( $result, $this->sut->get_sub_dir( $hash, $user ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_comment_author() {
		// Input parameters.
		$type      = 'gravatar';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Fake user.
		$email = 'some@email';

		// Intermediate data.
		$rating = 'r';
		$args   = [
			'user_id'   => false,
			'email'     => $email,
			'rating'    => $rating,
			'mimetype'  => 'image/jpeg',
		];

		$this->core->shouldReceive( 'get_user_by_hash' )->never();
		$this->core->shouldReceive( 'get_comment_author_email' )->once()->with( $hash )->andReturn( $email );
		$this->options->shouldReceive( 'get' )->once()->with( 'avatar_rating', 'g', true )->andReturn( $rating );

		$this->sut->shouldReceive( 'get_url' )->once()->with( '', $hash, $size, $args )->andReturn( 'https://foobar.org/cached_avatar_url' );

		$this->assertTrue( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_user() {
		// Input parameters.
		$type      = 'gravatar';
		$hash      = '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6';
		$size      = 99;
		$subdir    = '4/9';
		$extension = 'jpg';

		// Fake user.
		$user             = m::mock( \WP_User::class );
		$user->ID         = '666';
		$user->user_email = 'some@email';

		// Intermediate data.
		$rating = 'r';
		$args   = [
			'user_id'   => $user->ID,
			'email'     => $user->user_email,
			'rating'    => $rating,
			'mimetype'  => 'image/jpeg',
		];

		$this->core->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $user );
		$this->core->shouldReceive( 'get_comment_author_email' )->never();

		$this->options->shouldReceive( 'get' )->once()->with( 'avatar_rating', 'g', true )->andReturn( $rating );

		$this->sut->shouldReceive( 'get_url' )->once()->with( '', $hash, $size, $args )->andReturn( 'https://foobar.org/cached_avatar_url' );

		$this->assertTrue( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_incorrect_subdir() {
		// Input parameters.
		$type      = 'gravatar';
		$hash      = '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6';
		$size      = 99;
		$subdir    = 'h/9';
		$extension = 'jpg';

		$this->core->shouldReceive( 'get_user_by_hash' )->never();
		$this->core->shouldReceive( 'get_comment_author_email' )->never();

		$this->options->shouldReceive( 'get' )->never();

		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_no_user_email() {
		// Input parameters.
		$type      = 'gravatar';
		$hash      = '49ea535b117df03929284fa3c8d3f73e18a88b6a6650e66ea588c077360c30c6';
		$size      = 99;
		$subdir    = '4/9';
		$extension = 'jpg';

		// Fake user.
		$user             = m::mock( \WP_User::class );
		$user->ID         = '666';
		$user->user_email = '';

		$this->core->shouldReceive( 'get_user_by_hash' )->once()->with( $hash )->andReturn( $user );
		$this->core->shouldReceive( 'get_comment_author_email' )->never();

		$this->options->shouldReceive( 'get' )->never();

		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}

	/**
	 * Tests ::cache_image.
	 *
	 * @covers ::cache_image
	 **/
	public function test_cache_image_no_omment_author_email() {
		// Input parameters.
		$type      = 'gravatar';
		$hash      = '7458549de917a04b3d57f76d7c2b7fe42309c07089f3356d87eeb36776b69496';
		$size      = 99;
		$subdir    = '7/4';
		$extension = 'jpg';

		// Fake user.
		$email = '';

		$this->core->shouldReceive( 'get_user_by_hash' )->never();
		$this->core->shouldReceive( 'get_comment_author_email' )->once()->with( $hash )->andReturn( $email );

		$this->options->shouldReceive( 'get' )->never();

		$this->sut->shouldReceive( 'get_url' )->never();

		$this->assertFalse( $this->sut->cache_image( $type, $hash, $size, $subdir, $extension ) );
	}
}
