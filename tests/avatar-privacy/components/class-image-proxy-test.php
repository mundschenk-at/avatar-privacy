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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Components\Image_Proxy;

use Avatar_Privacy\Core;

use Avatar_Privacy\Avatar_Handlers\Avatar_Handler;
use Avatar_Privacy\Avatar_Handlers\Default_Icons_Handler;
use Avatar_Privacy\Avatar_Handlers\Gravatar_Cache_Handler;
use Avatar_Privacy\Avatar_Handlers\User_Avatar_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Tools\Images\Image_File;

/**
 * Avatar_Privacy\Components\Image_Proxy unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Components\Image_Proxy
 * @usesDefaultClass \Avatar_Privacy\Components\Image_Proxy
 *
 * @uses ::__construct
 */
class Image_Proxy_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * Data for simulating an image.
	 *
	 * @var string
	 */
	const FAKE_IMAGE_DATA = 'NOTREALLYANIMAGE';

	/**
	 * The system-under-test.
	 *
	 * @var Image_Proxy
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Required helper object.
	 *
	 * @var Gravatar_Cache_Handler
	 */
	private $gravatar;

	/**
	 * Required helper object.
	 *
	 * @var User_Avatar_Handler
	 */
	private $user_avatar;

	/**
	 * Required helper object.
	 *
	 * @var Default_Icons_Handler
	 */
	private $default_icons;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @since 2.3.3 Renamed to `set_up`.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'uploads'   => [
				'cache' => [
					'fake_image.png' => self::FAKE_IMAGE_DATA,
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Mock required helpers.
		$this->site_transients = m::mock( Site_Transients::class );
		$this->options         = m::mock( Options::class );
		$this->file_cache      = m::mock( Filesystem_Cache::class );
		$this->gravatar        = m::mock( Gravatar_Cache_Handler::class );
		$this->user_avatar     = m::mock( User_Avatar_Handler::class );
		$this->default_icons   = m::mock( Default_Icons_Handler::class );

		$handlers = [
			'gravatar_hook' => $this->gravatar,
			'user_hook'     => $this->user_avatar,
			'default_hook'  => $this->default_icons,
		];

		$this->gravatar->allows()->get_type()->andReturns( 'gravatar' );
		$this->user_avatar->allows()->get_type()->andReturns( 'user' );
		$this->default_icons->allows()->get_type()->andReturns( '' );

		// Constructor arguments.
		$args = [
			$this->site_transients,
			$this->options,
			$this->file_cache,
			$handlers,
			$this->default_icons,
		];

		$this->sut = m::mock( Image_Proxy::class, $args )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$mock = m::mock( Image_Proxy::class )->makePartial();

		$handlers = [
			'gravatar_hook' => $this->gravatar,
			'user_hook'     => $this->user_avatar,
			'default_hook'  => $this->default_icons,
		];

		$mock->__construct(
			$this->site_transients,
			$this->options,
			$this->file_cache,
			$handlers,
			$this->default_icons
		);

		$this->assert_attribute_same( $this->site_transients, 'site_transients', $mock );
		$this->assert_attribute_same( $this->options, 'options', $mock );
		$this->assert_attribute_same( $this->file_cache, 'file_cache', $mock );
		$this->assert_attribute_same( $handlers, 'handler_hooks', $mock );
		$this->assert_attribute_same(
			[
				'gravatar' => $this->gravatar,
				'user'     => $this->user_avatar,
			],
			'handlers',
			$mock
		);
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Filters\expectAdded( 'avatar_defaults' )->once()->with( [ $this->default_icons, 'avatar_defaults' ] );

		Filters\expectAdded( 'gravatar_hook' )->once()->with( [ $this->gravatar, 'get_url' ], 10, 4 );
		Filters\expectAdded( 'user_hook' )->once()->with( [ $this->user_avatar, 'get_url' ], 10, 4 );
		Filters\expectAdded( 'default_hook' )->once()->with( [ $this->default_icons, 'get_url' ], 10, 4 );

		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'add_cache_rewrite_rules' ] );
		Actions\expectAdded( 'parse_request' )->once()->with( [ $this->sut, 'load_cached_avatar' ] );
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'enable_image_cache_cleanup' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::add_cache_rewrite_rules.
	 *
	 * @covers ::add_cache_rewrite_rules
	 */
	public function test_add_cache_rewrite_rules() {
		global $wp;
		$wp = m::mock( 'WP' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp->shouldReceive( 'add_query_var' )->once()->with( 'avatar-privacy-file' );
		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( '/wordpress/path/base/dir' );

		Functions\expect( 'add_rewrite_rule' )->once()->with( '^/base/dir(.*)',  [ 'avatar-privacy-file' => '$matches[1]' ], 'top' );

		$this->assertNull( $this->sut->add_cache_rewrite_rules() );
	}

	/**
	 * Tests ::load_cached_avatar.
	 *
	 * @covers ::load_cached_avatar
	 */
	public function test_load_cached_avatar_default_icon() {
		// Input parameters.
		$basedir   = '/base/dir/';
		$type      = 'ring';
		$hash      = '19b4a035996a6f641a10a02fac6d3c6be1dd2713dcc42914b3acc4128bbe9399';
		$subdir    = '1/9/';
		$extension = 'svg';
		$size      = 100;
		$file      = "{$type}/{$subdir}{$hash}.{$extension}";

		// Mock WP global.
		$wp             = m::mock( 'WP' );
		$wp->query_vars = [
			'avatar-privacy-file' => $file,
		];

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->default_icons->shouldReceive( 'cache_image' )->once()->with( $type, $hash, $size, $subdir, $extension )->andReturn( true );
		$this->sut->shouldReceive( 'send_image' )->once()->with( "{$basedir}{$file}", \DAY_IN_SECONDS, Image_File::SVG_IMAGE );
		$this->sut->shouldReceive( 'exit_request' )->once();

		$this->assertNull( $this->sut->load_cached_avatar( $wp ) );
	}

	/**
	 * Tests ::load_cached_avatar.
	 *
	 * @covers ::load_cached_avatar
	 */
	public function test_load_cached_avatar_gravatar() {
		// Input parameters.
		$basedir   = '/base/dir/';
		$type      = 'gravatar';
		$hash      = '19b4a035996a6f641a10a02fac6d3c6be1dd2713dcc42914b3acc4128bbe9399';
		$subdir    = '1/9/';
		$extension = 'png';
		$size      = 100;
		$file      = "{$type}/{$subdir}{$hash}-{$size}.{$extension}";

		// Mock WP global.
		$wp             = m::mock( 'WP' );
		$wp->query_vars = [
			'avatar-privacy-file' => $file,
		];

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->gravatar->shouldReceive( 'cache_image' )->once()->with( $type, $hash, $size, $subdir, $extension )->andReturn( true );
		$this->sut->shouldReceive( 'send_image' )->once()->with( "{$basedir}{$file}", \DAY_IN_SECONDS, Image_File::PNG_IMAGE );
		$this->sut->shouldReceive( 'exit_request' )->once();

		$this->assertNull( $this->sut->load_cached_avatar( $wp ) );
	}

	/**
	 * Tests ::load_cached_avatar.
	 *
	 * @covers ::load_cached_avatar
	 */
	public function test_load_cached_avatar_user_avatar_unsuccessful() {
		// Input parameters.
		$basedir   = '/base/dir/';
		$type      = 'user';
		$hash      = '19b4a035996a6f641a10a02fac6d3c6be1dd2713dcc42914b3acc4128bbe9399';
		$subdir    = '1/9/';
		$extension = 'png';
		$size      = 100;
		$file      = "{$type}/{$subdir}{$hash}-{$size}.{$extension}";

		// Mock WP global.
		$wp             = m::mock( 'WP' );
		$wp->query_vars = [
			'avatar-privacy-file' => $file,
		];

		$this->file_cache->shouldReceive( 'get_base_dir' )->once()->andReturn( $basedir );

		$this->user_avatar->shouldReceive( 'cache_image' )->once()->with( $type, $hash, $size, $subdir, $extension )->andReturn( false );
		$this->sut->shouldReceive( 'send_image' )->never();
		$this->sut->shouldReceive( 'exit_request' )->never();

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( 'translated message' );
		Functions\expect( 'esc_html' )->once()->with( 'translated message' )->andReturn( 'escaped message' );
		Functions\expect( 'wp_die' )->once()->with( 'escaped message' )->andThrow( \RuntimeException::class, 'Request died.' );
		$this->expectExceptionMessage( 'Request died.' );

		$this->assertNull( $this->sut->load_cached_avatar( $wp ) );
	}

	/**
	 * Tests ::load_cached_avatar.
	 *
	 * @covers ::load_cached_avatar
	 */
	public function test_load_cached_avatar_no_query_var() {
		// Mock WP global.
		$wp             = m::mock( 'WP' );
		$wp->query_vars = [];

		$this->file_cache->shouldReceive( 'get_base_dir' )->never();
		$this->user_avatar->shouldReceive( 'cache_image' )->never();
		$this->sut->shouldReceive( 'send_image' )->never();
		$this->sut->shouldReceive( 'exit_request' )->never();

		Functions\expect( 'wp_die' )->never();

		$this->assertNull( $this->sut->load_cached_avatar( $wp ) );
	}

	/**
	 * Tests ::load_cached_avatar.
	 *
	 * @covers ::load_cached_avatar
	 */
	public function test_load_cached_avatar_invalid_query_var() {
		// Input parameters.
		$type      = 'user';
		$hash      = '19b4a035996a6f641a10a02faZ6d3c6be1dd2713dcc42914b3acc4128bbe9399'; // Invalid!
		$subdir    = '1/9/';
		$extension = 'png';
		$size      = 100;
		$file      = "{$type}/{$subdir}{$hash}-{$size}.{$extension}";

		// Mock WP global.
		$wp             = m::mock( 'WP' );
		$wp->query_vars = [
			'avatar-privacy-file' => $file,
		];

		$this->file_cache->shouldReceive( 'get_base_dir' )->never();
		$this->user_avatar->shouldReceive( 'cache_image' )->never();
		$this->sut->shouldReceive( 'send_image' )->never();
		$this->sut->shouldReceive( 'exit_request' )->never();

		Functions\expect( 'wp_die' )->never();

		$this->assertNull( $this->sut->load_cached_avatar( $wp ) );
	}

	/**
	 * Tests ::send_image.
	 *
	 * @covers ::send_image
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires extension xdebug
	 */
	public function test_send_image() {
		$file         = vfsStream::url( 'root/uploads/cache/fake_image.png' );
		$cache_time   = 99; // In seconds.
		$content_type = 'image/png';
		$length       = \strlen( self::FAKE_IMAGE_DATA );

		Functions\expect( 'wp_die' )->never();

		$this->expectOutputString( self::FAKE_IMAGE_DATA );

		$this->assertNull( $this->sut->send_image( $file, $cache_time, $content_type ) );

		$headers = \xdebug_get_headers();
		$this->assert_matches_regular_expression( "|Content-Type: {$content_type}|", $headers[0] );
		$this->assert_matches_regular_expression( "|Content-Length: {$length}|", $headers[1] );
		$this->assert_matches_regular_expression( '|Last-Modified: |', $headers[2] );
		$this->assert_matches_regular_expression( '|Expires: |', $headers[3] );
		$this->assert_matches_regular_expression( '|ETag: |', $headers[4] );
	}

	/**
	 * Tests ::send_image.
	 *
	 * @covers ::send_image
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires extension xdebug
	 */
	public function test_send_image_invalid_file() {
		$file         = vfsStream::url( 'root/uploads/cache/invalid_image.png' );
		$cache_time   = 99; // In seconds.
		$content_type = 'image/png';

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'avatar-privacy' )->andReturn( 'translated message' );
		Functions\expect( 'esc_html' )->once()->with( 'translated message' )->andReturn( 'escaped message' );
		Functions\expect( 'wp_die' )->once()->with( 'escaped message' )->andThrow( \RuntimeException::class, 'Request died.' );
		$this->expectExceptionMessage( 'Request died.' );

		$this->assertNull( $this->sut->send_image( $file, $cache_time, $content_type ) );

		$this->assertEmpty( \xdebug_get_headers() );
	}

	/**
	 * Tests ::enable_image_cache_cleanup.
	 *
	 * @covers ::enable_image_cache_cleanup
	 */
	public function test_enable_image_cache_cleanup() {
		Functions\expect( 'wp_next_scheduled' )->once()->with( Image_Proxy::CRON_JOB_ACTION )->andReturnFalse();
		Functions\expect( 'wp_schedule_event' )->once()->with( m::type( 'int' ), 'daily', Image_Proxy::CRON_JOB_ACTION );

		Actions\expectAdded( Image_Proxy::CRON_JOB_ACTION )->once()->with( [ $this->sut, 'trim_gravatar_cache' ] );
		Actions\expectAdded( Image_Proxy::CRON_JOB_ACTION )->once()->with( [ $this->sut, 'trim_image_cache' ] );

		$this->assertNull( $this->sut->enable_image_cache_cleanup() );
	}

	/**
	 * Tests ::trim_gravatar_cache.
	 *
	 * @covers ::trim_gravatar_cache
	 */
	public function test_trim_gravatar_cache() {
		$interval = 10000; // In seconds.
		$max_age  = 200; // In seconds.

		$this->site_transients->shouldReceive( 'get' )->once()->with( Image_Proxy::CRON_JOB_LOCK_GRAVATARS )->andReturnFalse();

		Filters\expectApplied( 'avatar_privacy_gravatars_max_age' )->once()->with( 2 * DAY_IN_SECONDS )->andReturn( $max_age );
		Filters\expectApplied( 'avatar_privacy_gravatars_cleanup_interval' )->once()->with( DAY_IN_SECONDS )->andReturn( $interval );

		$this->sut->shouldReceive( 'invalidate_cached_images' )->once()->with( Image_Proxy::CRON_JOB_LOCK_GRAVATARS, 'gravatar', $interval, $max_age );

		$this->assertNull( $this->sut->trim_gravatar_cache() );
	}

	/**
	 * Tests ::trim_gravatar_cache.
	 *
	 * @covers ::trim_gravatar_cache
	 */
	public function test_trim_gravatar_cache_locked() {
		$this->site_transients->shouldReceive( 'get' )->once()->with( Image_Proxy::CRON_JOB_LOCK_GRAVATARS )->andReturnTrue();

		Filters\expectApplied( 'avatar_privacy_gravatars_max_age' )->never();
		Filters\expectApplied( 'avatar_privacy_gravatars_cleanup_interval' )->never();

		$this->sut->shouldReceive( 'invalidate_cached_images' )->never();

		$this->assertNull( $this->sut->trim_gravatar_cache() );
	}

	/**
	 * Tests ::trim_image_cache.
	 *
	 * @covers ::trim_image_cache
	 */
	public function test_trim_image_cache() {
		$interval = 10000; // In seconds.
		$max_age  = 200; // In seconds.

		$this->site_transients->shouldReceive( 'get' )->once()->with( Image_Proxy::CRON_JOB_LOCK_ALL_IMAGES )->andReturnFalse();

		Filters\expectApplied( 'avatar_privacy_all_images_max_age' )->once()->with( 7 * DAY_IN_SECONDS )->andReturn( $max_age );
		Filters\expectApplied( 'avatar_privacy_all_images_cleanup_interval' )->once()->with( 7 * DAY_IN_SECONDS )->andReturn( $interval );

		$this->sut->shouldReceive( 'invalidate_cached_images' )->once()->with( Image_Proxy::CRON_JOB_LOCK_ALL_IMAGES, '', $interval, $max_age );

		$this->assertNull( $this->sut->trim_image_cache() );
	}

	/**
	 * Tests ::trim_image_cache.
	 *
	 * @covers ::trim_image_cache
	 */
	public function test_trim_image_cache_locked() {
		$this->site_transients->shouldReceive( 'get' )->once()->with( Image_Proxy::CRON_JOB_LOCK_ALL_IMAGES )->andReturnTrue();

		Filters\expectApplied( 'avatar_privacy_all_images_max_age' )->never();
		Filters\expectApplied( 'avatar_privacy_all_images_cleanup_interval' )->never();

		$this->sut->shouldReceive( 'invalidate_cached_images' )->never();

		$this->assertNull( $this->sut->trim_image_cache() );
	}

	/**
	 * Tests ::invalidate_cached_images.
	 *
	 * @covers ::invalidate_cached_images
	 */
	public function test_invalidate_cached_images() {
		$lock     = 'LOCKING_TRANSIENT_NAME';
		$subdir   = 'sub/dir/to/clean';
		$interval = 10000; // In seconds.
		$max_age  = 200; // In seconds.

		$this->file_cache->shouldReceive( 'invalidate_files_older_than' )->once()->with( $max_age, $subdir );
		$this->site_transients->shouldReceive( 'set' )->once()->with( $lock, true, $interval );

		$this->assertNull( $this->sut->invalidate_cached_images( $lock, $subdir, $interval, $max_age ) );
	}
}
