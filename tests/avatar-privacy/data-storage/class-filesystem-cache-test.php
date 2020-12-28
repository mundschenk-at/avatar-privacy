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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Data_Storage;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Exceptions\Filesystem_Exception;

/**
 * Avatar_Privacy\Data_Storage\Filesystem_Cache unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Data_Storage\Filesystem_Cache
 * @usesDefaultClass \Avatar_Privacy\Data_Storage\Filesystem_Cache
 */
class Filesystem_Cache_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Avatar_Privacy\Data_Storage\Filesystem_Cache
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

		$filesystem = [
			'uploads' => [
				'delete' => [
					'existing_file.txt'  => 'CONTENT',
					'existing_file2.txt' => 'MORE CONTENT',
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Partially mock system under test.
		$this->sut = m::mock( Filesystem_Cache::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$this->sut->shouldReceive( 'get_base_dir' )->once();

		$this->assertNull( $this->sut->__construct() );
	}

	/**
	 * Tests ::get_base_dir.
	 *
	 * @covers ::get_base_dir
	 */
	public function test_get_base_dir() {
		$basedir  = 'the/uploads/directory';
		$cachedir = "$basedir/" . Filesystem_Cache::CACHE_DIR;

		// First invocation.
		$this->sut->shouldReceive( 'get_upload_dir' )->once()->andReturn( [ 'basedir' => $basedir ] );
		Functions\expect( 'wp_mkdir_p' )->once()->with( m::type( 'string' ) )->andReturn( true );

		$this->assertSame( $cachedir, $this->sut->get_base_dir() );

		// Second invocation.
		$this->sut->shouldReceive( 'get_upload_dir' )->never();
		Functions\expect( 'wp_mkdir_p' )->never();

		$this->assertSame( $cachedir, $this->sut->get_base_dir() );
	}

	/**
	 * Tests ::get_base_dir.
	 *
	 * @covers ::get_base_dir
	 */
	public function test_get_base_dir_cannot_create_dir() {
		$basedir  = 'the/uploads/directory';
		$cachedir = "$basedir/" . Filesystem_Cache::CACHE_DIR;

		$this->sut->shouldReceive( 'get_upload_dir' )->once()->andReturn( [ 'basedir' => $basedir ] );
		Functions\expect( 'wp_mkdir_p' )->once()->with( m::type( 'string' ) )->andReturn( false );

		$this->expectException( Filesystem_Exception::class );

		$this->assertSame( $cachedir, $this->sut->get_base_dir() );
	}


	/**
	 * Tests ::get_base_url.
	 *
	 * @covers ::get_base_url
	 */
	public function test_get_base_url() {
		$baseurl  = 'http://the/uploads/directory';
		$cacheurl = "$baseurl/" . Filesystem_Cache::CACHE_DIR;

		// First invocation.
		$this->sut->shouldReceive( 'get_upload_dir' )->once()->andReturn( [ 'baseurl' => $baseurl ] );

		$this->assertSame( $cacheurl, $this->sut->get_base_url() );

		// Second invocation.
		$this->sut->shouldReceive( 'get_upload_dir' )->never();

		$this->assertSame( $cacheurl, $this->sut->get_base_url() );
	}

	/**
	 * Tests ::get_upload_dir.
	 *
	 * @covers ::get_upload_dir
	 */
	public function test_get_upload_dir() {
		$result = [
			'basedir' => 'foo',
			'baseurl' => 'bar',
		];

		// First invocation.
		Functions\expect( 'is_multisite' )->once()->andReturn( false );
		Functions\expect( 'wp_get_upload_dir' )->once()->andReturn( $result );
		$this->assertSame( $result, $this->sut->get_upload_dir() );

		// Second invocation.
		Functions\expect( 'is_multisite' )->never();
		Functions\expect( 'wp_get_upload_dir' )->never();
		$this->assertSame( $result, $this->sut->get_upload_dir() );
	}

	/**
	 * Tests ::get_upload_dir.
	 *
	 * @covers ::get_upload_dir
	 */
	public function test_get_upload_dir_multsite() {
		$result = [
			'basedir' => 'foo',
			'baseurl' => 'bar',
		];

		// First invocation.
		Functions\expect( 'is_multisite' )->once()->andReturn( true );
		Functions\expect( 'switch_to_blog' )->once()->with( m::type( 'int' ) );
		Functions\expect( 'get_main_site_id' )->once()->andReturn( 5 );
		Functions\expect( 'wp_get_upload_dir' )->once()->andReturn( $result );
		Functions\expect( 'restore_current_blog' )->once();
		$this->assertSame( $result, $this->sut->get_upload_dir() );

		// Second invocation.
		Functions\expect( 'is_multisite' )->never();
		Functions\expect( 'switch_to_blog' )->never();
		Functions\expect( 'get_main_site_id' )->never();
		Functions\expect( 'wp_get_upload_dir' )->never();
		Functions\expect( 'restore_current_blog' )->never();
		$this->assertSame( $result, $this->sut->get_upload_dir() );
	}

	/**
	 * Helper method that simulates the wp_mkdir_p function.
	 *
	 * @param  string $dir The directory to create.
	 *
	 * @return bool
	 */
	public static function wp_mkdir_p( $dir ) {
		if ( \file_exists( $dir ) ) {
			return \is_dir( $dir );
		}

		return \mkdir( $dir );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set() {
		// We can't use vfsStream because of the PHP LOCK_EX bug in file_put_contents.
		$data      = 'anything';
		$basedir   = \sys_get_temp_dir();
		$temp_file = \tempnam( $basedir, 'avprTest' );
		$filename  = \basename( $temp_file );

		// The temporary file should not exist initially.
		\unlink( $temp_file );

		$this->sut->shouldReceive( 'get_base_dir' )->times( 3 )->andReturn( "$basedir/" );

		Functions\expect( 'wp_mkdir_p' )->once()->with( m::type( 'string' ) )->andReturnUsing( [ __CLASS__, 'wp_mkdir_p' ] );
		$this->assertTrue( $this->sut->set( $filename, $data ) );

		Functions\expect( 'wp_mkdir_p' )->never();
		$this->assertTrue( $this->sut->set( $filename, $data ) );

		Functions\expect( 'wp_mkdir_p' )->once()->with( m::type( 'string' ) )->andReturnUsing( [ __CLASS__, 'wp_mkdir_p' ] );
		$this->assertTrue( $this->sut->set( $filename, $data, true ) );

		// Clean up.
		\unlink( $temp_file );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set_invalid() {
		$basedir  = vfsStream::url( 'root/put' );
		$filename = 'a/file';
		$data     = null;

		$this->sut->shouldReceive( 'get_base_dir' )->times( 3 )->andReturn( "$basedir/" );

		$this->assertFalse( $this->sut->set( $filename, $data ) );
		$this->assertFalse( $this->sut->set( $filename, $data ) );
		$this->assertFalse( $this->sut->set( $filename, $data, true ) );
	}


	/**
	 * Tests ::get_url.
	 *
	 * @covers ::get_url
	 */
	public function test_get_url() {
		$baseurl  = 'https://foo/bar/';
		$filename = 'a/file';

		$this->sut->shouldReceive( 'get_base_url' )->once()->andReturn( $baseurl );

		$this->assertSame( $baseurl . $filename, $this->sut->get_url( $filename ) );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete_existing_file() {
		$basedir  = 'root/uploads/';
		$filename = 'delete/existing_file.txt';

		$this->sut->shouldReceive( 'get_base_dir' )->once()->andReturn( vfsStream::url( $basedir ) );

		$this->assertTrue( $this->sut->delete( $filename ) );
	}

	/**
	 * Tests ::delete.
	 *
	 * @covers ::delete
	 */
	public function test_delete_non_existing_file() {
		$basedir  = 'root/uploads/';
		$filename = 'delete/non_existing_file.txt';

		$this->sut->shouldReceive( 'get_base_dir' )->once()->andReturn( vfsStream::url( $basedir ) );

		$this->assertFalse( $this->sut->delete( $filename ) );
	}

	/**
	 * Tests ::invalidate.
	 *
	 * @covers ::invalidate
	 */
	public function test_invalidate() {
		$subdir = 'delete';
		$regex  = '/\.txt$';

		$fileinfo = m::mock( \SplFileInfo::class );
		$iterator = m::mock( \OuterIterator::class )->makePartial();
		$iterator->shouldReceive( 'rewind' )->once();
		$iterator->shouldReceive( 'valid' )->times( 3 )->andReturn( true, true, false );
		$iterator->shouldReceive( 'current' )->twice()->andReturn( $fileinfo );
		$iterator->shouldReceive( 'key' )->twice()->andReturn( vfsStream::url( 'root/uploads/delete/existing_file.txt' ), vfsStream::url( 'root/uploads/delete' ) );
		$fileinfo->shouldReceive( 'isWritable' )->twice()->andReturn( true, true );
		$fileinfo->shouldReceive( 'isDir' )->twice()->andReturn( false, true );
		$iterator->shouldReceive( 'next' )->twice();

		$this->sut->shouldReceive( 'get_recursive_file_iterator' )->once()->with( $subdir, $regex )->andReturn( $iterator );

		$this->assertNull( $this->sut->invalidate( $subdir, $regex ) );
	}

	/**
	 * Tests ::invalidate.
	 *
	 * @covers ::invalidate
	 */
	public function test_invalidate_but_cannot_get_iterator() {
		$subdir = 'delete';
		$regex  = '/\.txt$';

		$this->sut->shouldReceive( 'get_recursive_file_iterator' )->once()->with( $subdir, $regex )->andThrow( \UnexpectedValueException::class );

		$this->assertNull( $this->sut->invalidate( $subdir, $regex ) );
	}

	/**
	 * Tests ::invalidate_files_older_than.
	 *
	 * @covers ::invalidate_files_older_than
	 */
	public function test_invalidate_files_older_than() {
		$subdir = 'delete';
		$regex  = '/\.txt$';
		$age    = 2;
		$now    = time();

		$fileinfo = m::mock( \SplFileInfo::class );
		$iterator = m::mock( \OuterIterator::class )->makePartial();
		$iterator->shouldReceive( 'rewind' )->once();
		$iterator->shouldReceive( 'valid' )->times( 4 )->andReturn( true, true, true, false );
		$iterator->shouldReceive( 'current' )->times( 3 )->andReturn( $fileinfo );
		$iterator->shouldReceive( 'key' )->times( 3 )->andReturn( vfsStream::url( 'root/uploads/delete/existing_file.txt' ), vfsStream::url( 'root/uploads/delete/existing_file2.txt' ), vfsStream::url( 'root/uploads/delete' ) );
		$fileinfo->shouldReceive( 'isWritable' )->times( 3 )->andReturn( true, true, true );
		$fileinfo->shouldReceive( 'isDir' )->times( 3 )->andReturn( false, false, true );
		$fileinfo->shouldReceive( 'getMTime' )->twice()->andReturn( $now - $age + 1, $now - $age - 1 );
		$iterator->shouldReceive( 'next' )->times( 3 );

		$this->sut->shouldReceive( 'get_recursive_file_iterator' )->once()->with( $subdir, $regex )->andReturn( $iterator );

		$this->assertNull( $this->sut->invalidate_files_older_than( $age, $subdir, $regex ) );
	}

	/**
	 * Tests ::invalidate_files_older_than.
	 *
	 * @covers ::invalidate_files_older_than
	 */
	public function test_invalidate_files_older_than_but_cannot_get_iterator() {
		$subdir = 'delete';
		$regex  = '/\.txt$';
		$age    = 2;

		$this->sut->shouldReceive( 'get_recursive_file_iterator' )->once()->with( $subdir, $regex )->andThrow( \UnexpectedValueException::class );

		$this->assertNull( $this->sut->invalidate_files_older_than( $age, $subdir, $regex ) );
	}

	/**
	 * Tests ::get_recursive_file_iterator.
	 *
	 * @covers ::get_recursive_file_iterator
	 */
	public function test_get_recursive_file_iterator() {
		$this->sut->shouldReceive( 'get_base_dir' )->twice()->andReturn( vfsStream::url( 'root' ) );

		$iterator1 = $this->sut->get_recursive_file_iterator();
		$iterator2 = $this->sut->get_recursive_file_iterator( '', '/.*/' );

		$this->assertNotSame( $iterator1, $iterator2 );
		$this->assertInstanceOf( \RecursiveIteratorIterator::class, $iterator1 );
		$this->assertInstanceOf( \RegexIterator::class, $iterator2 );
	}
}
