<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2023 Peter Putzer.
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

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Core\Default_Avatars;
use Avatar_Privacy\Core\Settings;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Exceptions\File_Deletion_Exception;
use Avatar_Privacy\Exceptions\Upload_Handling_Exception;
use Avatar_Privacy\Tools\Hasher;
use Avatar_Privacy\Tools\Images\Image_File;

/**
 * Default_Avatars unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Core\Default_Avatars
 * @usesDefaultClass \Avatar_Privacy\Core\Default_Avatars
 *
 * @uses ::__construct
 */
class Default_Avatars_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Default_Avatars
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Required helper object.
	 *
	 * @var Hasher
	 */
	private $hasher;

	/**
	 * Required helper object.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * Required helper object.
	 *
	 * @var Image_File
	 */
	private $image_file;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		$filesystem = [
			'uploads'   => [
				'some.png'            => '',
				'Jane-Doe_avatar.gif' => '',
				'Foobar_avatar.png'   => '',
				'Foobar_avatar_1.png' => '',
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );

		$this->settings   = m::mock( Settings::class );
		$this->options    = m::mock( Options::class );
		$this->hasher     = m::mock( Hasher::class );
		$this->file_cache = m::mock( Filesystem_Cache::class );
		$this->image_file = m::mock( Image_File::class );

		// Partially mock system under test.
		$this->sut = m::mock( Default_Avatars::class, [ $this->settings, $this->options, $this->hasher, $this->file_cache, $this->image_file ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		// Mock required helpers.
		$settings   = m::mock( Settings::class );
		$options    = m::mock( Options::class );
		$hasher     = m::mock( Hasher::class );
		$file_cache = m::mock( Filesystem_Cache::class );
		$image_file = m::mock( Image_File::class );

		$default_avatars = m::mock( Default_Avatars::class )->makePartial();
		$default_avatars->__construct( $settings, $options, $hasher, $file_cache, $image_file );

		$this->assert_attribute_same( $settings, 'settings', $default_avatars );
		$this->assert_attribute_same( $options, 'options', $default_avatars );
		$this->assert_attribute_same( $hasher, 'hasher', $default_avatars );
		$this->assert_attribute_same( $file_cache, 'file_cache', $default_avatars );
		$this->assert_attribute_same( $image_file, 'image_file', $default_avatars );
	}

	/**
	 * Tests ::get_hash.
	 *
	 * @covers ::get_hash
	 */
	public function test_get_hash() {
		$site_id = 47;
		$hash    = 'fake hash';

		$this->hasher->shouldReceive( 'get_hash' )->once()->with( "custom-default-{$site_id}" )->andReturn( $hash );

		$this->assertSame( $hash, $this->sut->get_hash( $site_id ) );
	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar() {
		$avatar = [
			'type' => 'image/png',
			'file' => '/some/fake/file.png',
		];

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR )->andReturn( $avatar );

		$this->assertSame( $avatar, $this->sut->get_custom_default_avatar() );
	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar_invalid() {
		$avatar = [
			'type' => 'image/png',
			'foo'  => 'bar',
		];

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR )->andReturn( $avatar );

		$this->assertSame( [], $this->sut->get_custom_default_avatar() );

	}

	/**
	 * Tests ::get_custom_default_avatar.
	 *
	 * @covers ::get_custom_default_avatar
	 */
	public function test_get_custom_default_avatar_empty_result() {
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR )->andReturn( false );

		$this->assertSame( [], $this->sut->get_custom_default_avatar() );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar() {
		$filename  = '/path/image.png';
		$image_url = "https://example.org{$filename}";

		$upload_filename = 'fancy-image.png';
		$avatar          = [
			'type' => 'image/png',
			'file' => '/some/path/file.png',
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $upload_filename );
		$this->image_file->shouldReceive( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $avatar );
		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->once()->with( $avatar )->andReturn( $upload_filename );

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_invalid_url() {
		$filename  = '/path/image.png';
		$image_url = "https://?malformed.example.org{$filename}";

		$this->expect_exception( \InvalidArgumentException::class );

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->never();
		$this->image_file->shouldReceive( 'handle_sideload' )->never();
		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->never();

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_missing_upload_file() {
		$filename  = '/path/image.png';
		$image_url = "https://example.org{$filename}";

		$upload_filename = 'fancy-image.png';
		$avatar          = [
			'type' => 'image/png',
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $upload_filename );
		$this->image_file->shouldReceive( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $avatar );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->never();

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_missing_mimetype() {
		$filename  = '/path/image.png';
		$image_url = "https://example.org{$filename}";

		$upload_filename = 'fancy-image.png';
		$avatar          = [
			'file' => '/some/fake/file.tif',
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $upload_filename );
		$this->image_file->shouldReceive( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $avatar );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->never();

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_invalid_mimetype() {
		$filename  = '/path/image.tiff';
		$image_url = "https://example.org{$filename}";

		$upload_filename = 'fancy-image.tif';
		$avatar          = [
			'type' => 'image/tiff',
			'file' => '/some/fake/file.tif',
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $upload_filename );
		$this->image_file->shouldReceive( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andReturn( $avatar );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->never();

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::set_custom_default_avatar.
	 *
	 * @covers ::set_custom_default_avatar
	 */
	public function test_set_custom_default_avatar_sideload_error() {
		$filename  = '/path/image.png';
		$image_url = "https://example.org{$filename}";

		$upload_filename = 'fancy-image.png';

		$this->sut->shouldReceive( 'get_custom_default_avatar_filename' )->once()->with( $filename )->andReturn( $upload_filename );
		$this->image_file->shouldReceive( 'handle_sideload' )->once()->with( $image_url, m::type( 'array' ) )->andThrow( Upload_Handling_Exception::class );

		$this->expect_exception( Upload_Handling_Exception::class );

		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->never();

		$this->assertNull( $this->sut->set_custom_default_avatar( $image_url ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar.
	 *
	 * @covers ::delete_custom_default_avatar
	 */
	public function test_delete_custom_default_avatar() {
		$this->sut->shouldReceive( 'store_custom_default_avatar_data' )->once();

		$this->assertNull( $this->sut->delete_custom_default_avatar() );
	}

	/**
	 * Tests ::store_custom_default_avatar_data.
	 *
	 * @covers ::store_custom_default_avatar_data
	 */
	public function test_store_custom_default_avatar_data() {
		$avatar  = [
			'type' => 'image/png',
			'file' => '/some/fake/image.png',
		];
		$site_id = 65;

		$this->sut->shouldReceive( 'delete_custom_default_avatar_image_file' )->once()->andReturn( true );

		Functions\expect( 'get_current_blog_id' )->once()->andReturn( $site_id );
		$this->sut->shouldReceive( 'invalidate_custom_default_avatar_cache' )->once()->with( $site_id );

		$this->settings->shouldReceive( 'set' )->once()->with( Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR, $avatar );

		$this->assertNull( $this->sut->store_custom_default_avatar_data( $avatar ) );
	}

	/**
	 * Tests ::store_custom_default_avatar_data.
	 *
	 * @covers ::store_custom_default_avatar_data
	 */
	public function test_store_custom_default_avatar_data_delete_error() {
		$avatar = [
			'type' => 'image/png',
			'file' => '/some/fake/image.png',
		];

		$this->sut->shouldReceive( 'delete_custom_default_avatar_image_file' )->once()->andReturn( false );

		$this->expect_exception( File_Deletion_Exception::class );

		Functions\expect( 'get_current_blog_id' )->never();
		$this->sut->shouldReceive( 'invalidate_custom_default_avatar_cache' )->never();

		$this->settings->shouldReceive( 'set' )->never();

		$this->assertNull( $this->sut->store_custom_default_avatar_data( $avatar ) );
	}

	/**
	 * Tests ::delete_custom_default_avatar_image_file.
	 *
	 * @covers ::delete_custom_default_avatar_image_file
	 *
	 * @uses Avatar_Privacy\Tools\delete_file
	 */
	public function test_delete_custom_default_avatar_image_file() {
		$avatar = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/some.png' ),
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );

		$this->assertTrue( $this->sut->delete_custom_default_avatar_image_file() );
	}

	/**
	 * Tests ::delete_custom_default_avatar_image_file.
	 *
	 * @covers ::delete_custom_default_avatar_image_file
	 */
	public function test_delete_local_avatar_invalid_file() {
		$avatar = [
			'type' => 'image/png',
			'file' => vfsStream::url( 'root/uploads/does-not-exist.png' ),
		];

		$this->sut->shouldReceive( 'get_custom_default_avatar' )->once()->andReturn( $avatar );

		$this->assertFalse( $this->sut->delete_custom_default_avatar_image_file() );
	}

	/**
	 * Tests ::invalidate_custom_default_avatar_cache.
	 *
	 * @covers ::invalidate_custom_default_avatar_cache
	 */
	public function test_invalidate_custom_default_avatar_cache() {
		$site_id = 42;
		$hash    = 'fake hash';

		$this->sut->shouldReceive( 'get_hash' )->once()->with( $site_id )->andReturn( $hash );
		$this->file_cache->shouldReceive( 'invalidate' )->once()->with( 'custom', m::pattern( "/#\/{$hash}-/" ) );

		$this->assertNull( $this->sut->invalidate_custom_default_avatar_cache( $site_id ) );
	}

	/**
	 * Provides the data for testing get_local_avatar_filename.
	 *
	 * @return array
	 */
	public function provide_get_local_avatar_filename_data() {
		return [
			[ '/some/dir/some.png', 'Foo--Bar.png', 'Foo & Bar' ],
			[ 'some/dir/some.gif', 'My-Nice-Blog.gif', 'My Nice Blog' ],
			[ 'other.png', 'Foobar.png', 'Foobar' ],
		];
	}

	/**
	 * Tests ::get_custom_default_avatar_filename.
	 *
	 * @covers ::get_custom_default_avatar_filename
	 *
	 * @dataProvider provide_get_local_avatar_filename_data
	 *
	 * @param string $filename The proposed filename.
	 * @param string $result   The resulting filename.
	 * @param string $blogname The name of the current site.
	 */
	public function test_get_custom_default_avatar_filename( $filename, $result, $blogname ) {
		Functions\expect( 'sanitize_file_name' )->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $arg ) {
				return \strtr(
					$arg,
					[
						' '  => '-',
						'&'  => '',
						'--' => '-',
					]
				);
			}
		);

		$this->options->shouldReceive( 'get' )->once()->with( 'blogname', '', true )->andReturn( $blogname );

		$this->assertSame( $result, $this->sut->get_custom_default_avatar_filename( $filename ) );
	}
}
