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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Avatar_Privacy\Tools\Images\Image_Stream;

/**
 * Avatar_Privacy\Tools\Images\Image_Stream unit test.
 *
 * @coversDefaultClass \Avatar_Privacy\Tools\Images\Image_Stream
 * @usesDefaultClass \Avatar_Privacy\Tools\Images\Image_Stream
 */
class Image_Stream_Test extends \Avatar_Privacy\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Image_Stream
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
			'folder' => [
				'filename.txt' => 'DATA',
			],
		];

		// Set up virtual filesystem used to simulate stream access.
		$root = vfsStream::setup( 'root', null, $filesystem );

		$this->sut = m::mock( Image_Stream::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Provides data for testing stream_open.
	 *
	 * @return array
	 */
	public function provide_stream_open_data() {
		return [
			[ 'r', true, true, false, 0, false, 'almost empty' ],
			[ 'rt', true, true, false, 0, false, 'almost empty' ],
			[ 'rb', true, true, false, 0, false, 'almost empty' ],
			[ 'r+', true, true, true, 0, false, 'almost empty' ],
			[ 'r+t', true, true, true, 0, false, 'almost empty' ],
			[ 'r+b', true, true, true, 0, false, 'almost empty' ],
			[ 'c+', true, true, true, 0, false, 'almost empty' ],
			[ 'c+t', true, true, true, 0, false, 'almost empty' ],
			[ 'c+b', true, true, true, 0, false, 'almost empty' ],
			[ 'w', true, false, true, 0, true, 'almost empty' ],
			[ 'wt', true, false, true, 0, true, 'almost empty' ],
			[ 'wb', true, false, true, 0, true, 'almost empty' ],
			[ 'w+', true, true, true, 0, true, 'almost empty' ],
			[ 'w+t', true, true, true, 0, true, 'almost empty' ],
			[ 'w+b', true, true, true, 0, true, 'almost empty' ],
			[ 'a', true, false, true, 12, false, 'almost empty' ],
			[ 'at', true, false, true, 12, false, 'almost empty' ],
			[ 'ab', true, false, true, 12, false, 'almost empty' ],
			[ 'a+', true, true, true, 12, false, 'almost empty' ],
			[ 'a+t', true, true, true, 12, false, 'almost empty' ],
			[ 'a+b', true, true, true, 12, false, 'almost empty' ],
			[ 'c', true, false, true, 0, false, 'almost empty' ],
			[ 'ct', true, false, true, 0, false, 'almost empty' ],
			[ 'cb', true, false, true, 0, false, 'almost empty' ],
			[ 'x', false, false, false, 0, false, 'almost empty' ],
		];
	}

	/**
	 * Tests ::stream_open.
	 *
	 * @covers ::stream_open
	 *
	 * @dataProvider provide_stream_open_data
	 *
	 * @param  string $mode     The open mode (input).
	 * @param  bool   $result   The expected result.
	 * @param  bool   $read     Flag indicating that the stream should be readable.
	 * @param  bool   $write    Flag indicating that the stream should be writable.
	 * @param  int    $position The new stream position.
	 * @param  bool   $truncate Flag indicating the the stream should be truncated.
	 * @param  string $data     Existing data in the stream.
	 */
	public function test_stream_open( $mode, $result, $read, $write, $position, $truncate, $data ) {
		$path        = 'scheme://some/path/or/other';
		$opened_path = '';
		$options     = 0;

		$handle = 'some/path/or/other';

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $data );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'stream_truncate' )->once()->with( 0 );
		}

		$this->assertSame( $result, $this->sut->stream_open( $path, $mode, $options, $opened_path ) );
	}

	/**
	 * Tests ::stream_open.
	 *
	 * @covers ::stream_open
	 *
	 * @expectedExceptionMessage Invalid mode specified (mode specified makes no sense for this stream implementation)
	 */
	public function test_stream_open_with_error() {
		$path        = 'scheme://some/path/or/other';
		$mode        = 'x+';
		$data        = 'something';
		$opened_path = '';
		$options     = STREAM_REPORT_ERRORS;

		$handle = 'some/path/or/other';

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $data );

		$this->sut->shouldReceive( 'stream_truncate' )->never();

		// PHP < 7.0 raises an error instead of throwing an "exception".
		if ( version_compare( phpversion(), '7.0.0', '<' ) ) {
			$this->expectException( \PHPUnit_Framework_Error::class );
		} else {
			$this->expectException( \PHPUnit\Framework\Error\Error::class );
		}

		$this->assertSame( false, $this->sut->stream_open( $path, $mode, $options, $opened_path ) );
	}

	/**
	 * Tests ::stream_read.
	 *
	 * @covers ::stream_read
	 */
	public function test_stream_read() {
		// Initial state.
		$readable = true;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = 7;

		// Input.
		$bytes_to_read = 3;

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Check.
		$this->assertSame( 'and', $this->sut->stream_read( $bytes_to_read ) );
		$this->assertAttributeSame( $position + $bytes_to_read, 'position', $this->sut );
	}

	/**
	 * Tests ::stream_read.
	 *
	 * @covers ::stream_read
	 */
	public function test_stream_read_not_readable() {
		// Initial state.
		$readable = false;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = 7;

		// Input.
		$bytes_to_read = 3;

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Check.
		$this->assertFalse( $this->sut->stream_read( $bytes_to_read ) );
		$this->assertAttributeSame( $position, 'position', $this->sut );
	}

	/**
	 * Tests ::stream_write.
	 *
	 * @covers ::stream_write
	 */
	public function test_stream_write() {
		// Initial state.
		$readable = false;
		$writable = true;
		$data     = 'a long and tedious string that is our stream';
		$position = 7;

		// Input.
		$new_data = 'xxx';

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Results.
		$length = \strlen( $new_data );

		// Check.
		$this->assertSame( $length, $this->sut->stream_write( $new_data ) );
		$this->assertAttributeSame( $position + $length, 'position', $this->sut );
		$this->assertAttributeSame( 'a long xxx tedious string that is our stream', 'data', $this->sut );
	}

	/**
	 * Tests ::stream_write.
	 *
	 * @covers ::stream_write
	 */
	public function test_stream_write_not_writable() {
		// Initial state.
		$readable = false;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = 7;

		// Input.
		$new_data = 'xxx';

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Results.
		$length = \strlen( $new_data );

		// Check.
		$this->assertSame( 0, $this->sut->stream_write( $new_data ) );
		$this->assertAttributeSame( $position, 'position', $this->sut );
		$this->assertAttributeSame( $data, 'data', $this->sut );
	}

	/**
	 * Tests ::stream_tell.
	 *
	 * @covers ::stream_tell
	 */
	public function test_stream_tell() {
		// Initial state.
		$readable = false;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = 7;

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Check.
		$this->assertSame( $position, $this->sut->stream_tell() );
	}

	/**
	 * Tests ::stream_eof.
	 *
	 * @covers ::stream_eof
	 */
	public function test_stream_eof() {
		// Initial state.
		$readable = false;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = \strlen( $data );

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Check.
		$this->assertTrue( $this->sut->stream_eof() );
	}

	/**
	 * Tests ::stream_eof.
	 *
	 * @covers ::stream_eof
	 */
	public function test_stream_eof_not_eof() {
		// Initial state.
		$readable = false;
		$writable = false;
		$data     = 'a long and tedious string that is our stream';
		$position = \strlen( $data ) - 1;

		// Set up stream object.
		$this->setValue( $this->sut, 'read', $readable, Image_Stream::class );
		$this->setValue( $this->sut, 'write', $writable, Image_Stream::class );
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		// Check.
		$this->assertFalse( $this->sut->stream_eof() );
	}

	/**
	 * Provides data for testing stream_seek.
	 *
	 * @return array
	 */
	public function provide_stream_seek_data() {
		return [
			[ 2, SEEK_SET, true, true ],
			[ 2, SEEK_CUR, true, true ],
			[ 2, SEEK_END, true, true ],
			[ 2, 73, false, false ],
		];
	}

	/**
	 * Tests ::stream_seek.
	 *
	 * @covers ::stream_seek
	 *
	 * @dataProvider provide_stream_seek_data
	 *
	 * @param  int  $offset   The amount by which to change the position.
	 * @param  int  $whence   A flag indicating the direction of the seek operation.
	 * @param  bool $result   The expected result.
	 * @param  bool $truncate A flag indicating the stream should be truncated.
	 */
	public function test_stream_seek( $offset, $whence, $result, $truncate ) {
		// Initial state.
		$data     = 'a long and tedious string that is our stream';
		$position = 12;

		// Expected results.
		$new_position = $offset;
		if ( SEEK_CUR === $whence ) {
			$new_position += $position;
		} elseif ( SEEK_END === $whence ) {
			$new_position += \strlen( $data );
		} elseif ( SEEK_SET !== $whence ) {
			$new_position = $position;
		}

		// Set up stream object.
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'truncate_after_seek' )->once();
		}

		$this->assertSame( $result, $this->sut->stream_seek( $offset, $whence ) );
		$this->assertAttributeSame( $new_position, 'position', $this->sut );
	}

	/**
	 * Provides data for testing truncate_after_seek.
	 *
	 * @return array
	 */
	public function provide_truncate_after_seek_data() {
		return [
			[ 2, 'some string', false ],
			[ 12, 'some string', true ],
		];
	}

	/**
	 * Tests ::truncate_after_seek.
	 *
	 * @covers ::truncate_after_seek
	 *
	 * @dataProvider provide_truncate_after_seek_data
	 *
	 * @param  int    $position The initial position.
	 * @param  string $data     The stream data.
	 * @param  bool   $truncate A flag indicating the stream should be truncated.
	 */
	public function test_truncate_after_seek( $position, $data, $truncate ) {
		// Set up stream object.
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );
		$this->setValue( $this->sut, 'position', $position, Image_Stream::class );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'stream_truncate' )->once()->with( $position );
		}

		$this->assertNull( $this->sut->truncate_after_seek() );
	}

	/**
	 * Provides data for testing stream_truncate.
	 *
	 * @return array
	 */
	public function provide_stream_truncate_data() {
		return [
			[ 'some string', 11, 'some string' ],
			[ 'some string', 4, 'some' ],
			[ 'some string', 13, "some string\0\0" ],
		];
	}

	/**
	 * Tests ::stream_truncate.
	 *
	 * @covers ::stream_truncate
	 *
	 * @dataProvider provide_stream_truncate_data
	 *
	 * @param  string $data   The stream data.
	 * @param  int    $length The new stream length.
	 * @param  string $result The stream data after truncating.
	 */
	public function test_stream_truncate( $data, $length, $result ) {
		// Set up stream object.
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );

		$this->assertTrue( $this->sut->stream_truncate( $length ) );
		$this->assertAttributeSame( $result, 'data', $this->sut );
	}

	/**
	 * Tests ::stream_stat.
	 *
	 * @covers ::stream_stat
	 */
	public function test_stream_stat() {
		// Initial state.
		$data = 'a long and tedious string that is our stream';

		// Set up stream object.
		$this->setValue( $this->sut, 'data', $data, Image_Stream::class );

		// Expected result.
		$result = [
			'dev'     => 0,
			'ino'     => 0,
			'mode'    => 0100777, // is_file & mode 0777.
			'nlink'   => 0,
			'uid'     => 0,
			'gid'     => 0,
			'rdev'    => 0,
			'size'    => \strlen( $data ),
			'atime'   => 0,
			'mtime'   => 0,
			'ctime'   => 0,
			'blksize' => -1,
			'blocks'  => -1,
		];

		// Check.
		$this->assertSame( $result, $this->sut->stream_stat() );
	}

	/**
	 * Tests ::url_stat.
	 *
	 * @covers ::url_stat
	 */
	public function test_url_stat() {
		$path  = vfsStream::url( 'root/folder/filename.txt' );
		$flags = 0;

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( 'handle' );
		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'handle' )->andReturn( true );

		$result = $this->sut->url_stat( $path, $flags );

		$this->assertInternalType( 'array', $result );
		$this->assertArrayHasKey( 'size', $result );
		$this->assertSame( 4, $result['size'] );
	}

	/**
	 * Tests ::url_stat.
	 *
	 * @covers ::url_stat
	 */
	public function test_url_stat_error() {
		$path  = vfsStream::url( 'root/folder/filename.txt' );
		$flags = 0; // STREAM_URL_STAT_QUIET does not have to be explicitely set.

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( 'handle' );
		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'handle' )->andReturn( false );

		$this->assertSame(
			[
				'dev'     => 0,
				'ino'     => 0,
				'mode'    => 0,
				'nlink'   => 0,
				'uid'     => 0,
				'gid'     => 0,
				'rdev'    => 0,
				'size'    => 0,
				'atime'   => 0,
				'mtime'   => 0,
				'ctime'   => 0,
				'blksize' => -1,
				'blocks'  => -1,
			],
			$this->sut->url_stat( $path, $flags )
		);
	}

	/**
	 * Tests ::stream_metadata.
	 *
	 * @covers ::stream_metadata
	 */
	public function test_stream_metadata() {
		$path   = vfsStream::url( 'root/folder/filename.txt' );
		$option = 666;
		$args   = [ 'foobar' ];

		$this->assertTrue( $this->sut->stream_metadata( $path, $option, $args ) );

	}

	/**
	 * Tests ::unlink.
	 *
	 * @covers ::unlink
	 */
	public function test_unlink() {
		$path   = 'scheme://some/path/or/other';
		$handle = 'some/path';

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'delete_handle' )->once()->with( $handle );

		$this->assertTrue( $this->sut->unlink( $path ) );

	}

	/**
	 * Tests ::unlink.
	 *
	 * @covers ::unlink
	 */
	public function test_unlink_no_handle() {
		$path   = 'scheme://some/path/or/other';
		$handle = '';

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'delete_handle' )->never();

		$this->assertFalse( $this->sut->unlink( $path ) );

	}

	/**
	 * Tests ::get_data_reference.
	 *
	 * @covers ::get_data_reference
	 */
	public function test_get_data_reference() {
		$classname = \get_class( $this->sut );

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'foo' );

		$data = $this->invokeStaticMethod( $classname, 'get_data_reference', [ 'foo' ] );
		$this->assertSame( '', $data );
	}

	/**
	 * Tests ::handle_exists.
	 *
	 * @covers ::handle_exists
	 *
	 * @uses ::get_data_reference
	 */
	public function test_handle_exists() {
		$classname = \get_class( $this->sut );

		$this->assertFalse( $this->invokeStaticMethod( $classname, 'handle_exists', [ 'foobar' ] ) );

		$this->invokeStaticMethod( $classname, 'get_data_reference', [ 'foobar' ] );

		$this->assertTrue( $this->invokeStaticMethod( $classname, 'handle_exists', [ 'foobar' ] ) );
	}

	/**
	 * Tests ::get_data.
	 *
	 * @covers ::get_data
	 */
	public function test_get_data_with_cleanup() {
		$classname = \get_class( $this->sut );

		$handle = 'something';
		$result = 'fake data';

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( $handle )->andReturn( true );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $result );
		$this->sut->shouldReceive( 'delete_handle' )->once()->with( $handle )->andReturn( true );

		$this->assertSame( $result, $classname::get_data( $handle, true ) );
	}

	/**
	 * Tests ::get_data.
	 *
	 * @covers ::get_data
	 */
	public function test_get_data_no_cleanup() {
		$classname = \get_class( $this->sut );

		$handle = 'something';
		$result = 'fake data';

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( $handle )->andReturn( true );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $result );
		$this->sut->shouldReceive( 'delete_handle' )->never();

		$this->assertSame( $result, $classname::get_data( $handle, false ) );
	}

	/**
	 * Tests ::get_data.
	 *
	 * @covers ::get_data
	 */
	public function test_get_data_handle_does_not_exist() {
		$classname = \get_class( $this->sut );

		$handle = 'something';

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( $handle )->andReturn( false );
		$this->sut->shouldReceive( 'get_data_reference' )->never();
		$this->sut->shouldReceive( 'delete_handle' )->never();

		$this->assertNull( $classname::get_data( $handle, true ) );
	}

	/**
	 * Tests ::delete_handle.
	 *
	 * @covers ::delete_handle
	 *
	 * @uses ::get_data_reference
	 * @uses ::handle_exists
	 */
	public function test_delete_handle() {
		$classname = \get_class( $this->sut );

		$handle = 'a new handle';

		$this->invokeStaticMethod( $classname, 'get_data_reference', [ $handle ] );
		$this->assertTrue( $this->invokeStaticMethod( $classname, 'handle_exists', [ $handle ] ) );

		$this->assertNull( $classname::delete_handle( $handle ) );

		$this->assertFalse( $this->invokeStaticMethod( $classname, 'handle_exists', [ $handle ] ) );
	}

	/**
	 * Provides data for testing get_handle_from_url.
	 *
	 * @return array
	 */
	public function provide_get_handle_from_url_data() {
		return [
			[ 'avprimg://somehost/path', 'somehost/path' ],
			[ 'foobar://somehost/path?query', 'somehost/path' ],
		];
	}

	/**
	 * Tests ::get_handle_from_url.
	 *
	 * @covers ::get_handle_from_url
	 *
	 * @dataProvider provide_get_handle_from_url_data
	 *
	 * @param  string $url    Input.
	 * @param  string $result Expected result.
	 */
	public function test_get_handle_from_url( $url, $result ) {
		$classname = \get_class( $this->sut );

		$this->assertSame( $result, $classname::get_handle_from_url( $url ) );
	}

	/**
	 * Tests ::register.
	 *
	 * @covers ::register
	 */
	public function test_register() {
		$classname = \get_class( $this->sut );

		$this->assertNotContains( 'foobar', \stream_get_wrappers() );

		$classname::register( 'foobar' );

		$this->assertContains( 'foobar', \stream_get_wrappers() );
	}
}
