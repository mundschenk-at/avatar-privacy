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

namespace Avatar_Privacy\Tests\Avatar_Privacy\Tools\Images;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

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
	 * @var Image_Stream&m\MockInterface
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
		vfsStream::setup( 'root', null, $filesystem );

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

		$stream_ref = [
			'data'  => $data,
			'atime' => 0,
			'mtime' => 0,
		];

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $stream_ref );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'stream_truncate' )->once()->with( 0 );
		}

		if ( $result ) {
			$this->sut->shouldReceive( 'maybe_trigger_error' )->never();
		} else {
			$this->sut->shouldReceive( 'maybe_trigger_error' )->once()->with( false, m::type( 'string' ) );
		}

		$this->assertSame( $result, $this->sut->stream_open( $path, $mode, $options, $opened_path ) );

		if ( $result ) {
			$this->assert_attribute_same( $read, 'read', $this->sut, "Incorrect read mode (should be $read)" );
			$this->assert_attribute_same( $write, 'write', $this->sut, "Incorrect write mode (should be $write)" );
			$this->assert_attribute_same( $position, 'position', $this->sut, "Incorrect position (should be $position)" );
		}
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
	public function test_stream_open_use_path( $mode, $result, $read, $write, $position, $truncate, $data ) {
		$path        = 'scheme://some/path/or/other';
		$opened_path = '';
		$options     = \STREAM_USE_PATH;

		$handle = 'some/path/or/other';

		$stream_ref = [
			'data'  => $data,
			'atime' => 0,
			'mtime' => 0,
		];

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $stream_ref );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'stream_truncate' )->once()->with( 0 );
		}

		if ( $result ) {
			$this->sut->shouldReceive( 'maybe_trigger_error' )->never();
		} else {
			$this->sut->shouldReceive( 'maybe_trigger_error' )->once()->with( false, m::type( 'string' ) );
		}
		$this->assertSame( $result, $this->sut->stream_open( $path, $mode, $options, $opened_path ) );
		if ( $result ) {
			$this->assert_attribute_same( $read, 'read', $this->sut, "Incorrect read mode (should be $read)" );
			$this->assert_attribute_same( $write, 'write', $this->sut, "Incorrect write mode (should be $write)" );
			$this->assert_attribute_same( $position, 'position', $this->sut, "Incorrect position (should be $position)" );

			$this->assertSame( 'some/path/or/other', $opened_path );
		} else {
			$this->assertSame( '', $opened_path );
		}
	}

	/**
	 * Tests ::stream_open.
	 *
	 * @covers ::stream_open
	 */
	public function test_stream_open_with_error() {
		$path        = 'scheme://some/path/or/other';
		$mode        = 'x+';
		$data        = 'something';
		$opened_path = '';
		$options     = STREAM_REPORT_ERRORS;

		$handle = 'some/path/or/other';

		$stream_ref = [
			'data'  => $data,
			'atime' => 0,
			'mtime' => 0,
		];

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( $handle );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $stream_ref );

		$this->sut->shouldReceive( 'stream_truncate' )->never();

		$this->sut->shouldReceive( 'maybe_trigger_error' )->once()->with( true, m::type( 'string' ) );

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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

		// Check.
		$this->assertSame( 'and', $this->sut->stream_read( $bytes_to_read ) );
		$this->assert_attribute_same( $position + $bytes_to_read, 'position', $this->sut );
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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

		// Check.
		$this->assertFalse( $this->sut->stream_read( $bytes_to_read ) );
		$this->assert_attribute_same( $position, 'position', $this->sut );
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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

		// Results.
		$length = \strlen( $new_data );

		// Check.
		$this->assertSame( $length, $this->sut->stream_write( $new_data ) );
		$this->assert_attribute_same( $position + $length, 'position', $this->sut );
		$this->assert_attribute_same( 'a long xxx tedious string that is our stream', 'data', $this->sut );
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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

		// Check.
		$this->assertSame( 0, $this->sut->stream_write( $new_data ) );
		$this->assert_attribute_same( $position, 'position', $this->sut );
		$this->assert_attribute_same( $data, 'data', $this->sut );
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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

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
		$this->set_value( $this->sut, 'read', $readable );
		$this->set_value( $this->sut, 'write', $writable );
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

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
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

		if ( $truncate ) {
			$this->sut->shouldReceive( 'truncate_after_seek' )->once();
		}

		$this->assertSame( $result, $this->sut->stream_seek( $offset, $whence ) );
		$this->assert_attribute_same( $new_position, 'position', $this->sut );
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
		$this->set_value( $this->sut, 'data', $data );
		$this->set_value( $this->sut, 'position', $position );

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
		$this->set_value( $this->sut, 'data', $data );

		$this->assertTrue( $this->sut->stream_truncate( $length ) );
		$this->assert_attribute_same( $result, 'data', $this->sut );
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
		$this->set_value( $this->sut, 'data', $data );

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

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( 'size', $result );
		$this->assertSame( 4, $result['size'] );
	}

	/**
	 * Tests ::url_stat.
	 *
	 * @covers ::url_stat
	 */
	public function test_url_stat_no_handle() {
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
	 * Tests ::url_stat.
	 *
	 * @covers ::url_stat
	 */
	public function test_url_stat_error() {
		$path  = vfsStream::url( 'root/invalid/folder/filename.txt' );
		$flags = 0;

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( 'handle' );
		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'handle' )->andReturn( true );

		$this->sut->shouldReceive( 'maybe_trigger_error' )->once()->with( true, m::type( 'string' ) );

		$this->assertFalse( $this->sut->url_stat( $path, $flags ) );
	}

	/**
	 * Tests ::url_stat.
	 *
	 * @covers ::url_stat
	 */
	public function test_url_stat_error_quiet() {
		$path  = vfsStream::url( 'root/invalid/folder/filename.txt' );
		$flags = \STREAM_URL_STAT_QUIET;

		$this->sut->shouldReceive( 'get_handle_from_url' )->once()->with( $path )->andReturn( 'handle' );
		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'handle' )->andReturn( true );

		$this->sut->shouldReceive( 'maybe_trigger_error' )->once()->with( false, m::type( 'string' ) );

		$this->assertFalse( $this->sut->url_stat( $path, $flags ) );
	}

	/**
	 * Tests ::stream_metadata.
	 *
	 * @covers ::stream_metadata
	 */
	public function test_stream_metadata_invalid_option() {
		$path   = 'scheme://root/folder/filename.txt';
		$option = 666;
		$args   = [ 'foobar' ];

		$this->sut->shouldReceive( 'get_handle_from_url' )->never();
		$this->sut->shouldReceive( 'handle_exists' )->never();
		$this->sut->shouldReceive( 'get_data_reference' )->never();

		$this->assertTrue( $this->sut->stream_metadata( $path, $option, $args ) );
	}

	/**
	 * Tests ::stream_metadata.
	 *
	 * Since Mockery can't handle return by reference, we have to use the real class here.
	 *
	 * @covers ::stream_metadata
	 *
	 * @uses ::register
	 * @uses ::get_data_reference
	 * @uses ::get_handle_from_url
	 * @uses ::handle_exists
	 * @uses ::stream_open
	 * @uses ::stream_stat
	 * @uses ::url_stat
	 *
	 * @return void
	 */
	public function test_stream_metadata_touch(): void {
		// Make sure the real Image_Stream class is registered.
		Image_Stream::register( 'imageStream' );

		// Set up test.
		$path      = 'imageStream://my/never/uses/test/stream';
		$new_mtime = 123;

		// Check timestamps - the "file" does not exist yet, so the stream wrapper reports 0.
		$this->assertSame( 0, \fileatime( $path ) );
		$this->assertSame( 0, \filemtime( $path ) );

		// Run the test.
		$this->assertTrue( \touch( $path, $new_mtime ) );

		// Check timestamps again.
		\clearstatcache( false, $path );
		$this->assertSame( $new_mtime, \fileatime( $path ) );
		$this->assertSame( $new_mtime, \filemtime( $path ) );
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
	 * Provides data for testing maybe_trigger_error.
	 *
	 * @return mixed[]
	 */
	public function provide_maybe_trigger_error_data() {
		return [
			[
				true,
				'My user notice',
				\E_USER_NOTICE,
			],
			[
				true,
				'My user warning',
				\E_USER_WARNING,
			],
			[
				true,
				'My user deprecation',
				\E_USER_DEPRECATED,
			],
			[
				false,
				'My user warning is never triggered',
				\E_USER_WARNING,
			],
		];
	}

	/**
	 * Tests ::maybe_trigger_error.
	 *
	 * @covers ::maybe_trigger_error
	 *
	 * @dataProvider provide_maybe_trigger_error_data
	 *
	 * @param bool   $condition   If an error should be triggered.
	 * @param string $message     The error message.
	 * @param int    $error_level The error severity (PHP constant).
	 */
	public function test_maybe_trigger_error( bool $condition, string $message, int $error_level ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		\set_error_handler(
			function ( $errno, $errstr ) {
				\restore_error_handler();
				throw new \RuntimeException( $errstr, $errno );
			},
			\E_ALL
		);

		if ( $condition ) {
			Functions\expect( 'esc_html' )->once()->with( $message )->andReturnFirstArg();
			$this->expectException( \RuntimeException::class );
			$this->expectExceptionMessage( $message );
			$this->expectExceptionCode( $error_level );
		} else {
			Functions\expect( 'esc_html' )->never();
		}

		$this->assertNull( $this->sut->maybe_trigger_error( $condition, $message, $error_level ) );
	}

	/**
	 * Tests ::get_data_reference.
	 *
	 * @covers ::get_data_reference
	 */
	public function test_get_data_reference() {
		$classname = \get_class( $this->sut );

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( 'foo' );

		$data = $this->invoke_static_method( $classname, 'get_data_reference', [ 'foo' ] );

		$this->assertArrayHasKey( 'data', $data );
		$this->assertSame( '', $data['data'] );
		$this->assertArrayHasKey( 'atime', $data );
		$this->assertIsInt( $data['atime'] );
		$this->assertArrayHasKey( 'mtime', $data );
		$this->assertIsInt( $data['mtime'] );
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

		$this->assertFalse( $this->invoke_static_method( $classname, 'handle_exists', [ 'foobar' ] ) );

		$this->invoke_static_method( $classname, 'get_data_reference', [ 'foobar' ] );

		$this->assertTrue( $this->invoke_static_method( $classname, 'handle_exists', [ 'foobar' ] ) );
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

		$stream_ref = [
			'data'  => $result,
			'atime' => 0,
			'mtime' => 0,
		];

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( $handle )->andReturn( true );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $stream_ref );
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

		$stream_ref = [
			'data'  => $result,
			'atime' => 0,
			'mtime' => 0,
		];

		$this->sut->shouldReceive( 'handle_exists' )->once()->with( $handle )->andReturn( true );
		$this->sut->shouldReceive( 'get_data_reference' )->once()->with( $handle )->andReturn( $stream_ref );
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

		$this->invoke_static_method( $classname, 'get_data_reference', [ $handle ] );
		$this->assertTrue( $this->invoke_static_method( $classname, 'handle_exists', [ $handle ] ) );

		$this->assertNull( $classname::delete_handle( $handle ) );

		$this->assertFalse( $this->invoke_static_method( $classname, 'handle_exists', [ $handle ] ) );
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
			[ 'foobar://only-host/', 'only-host/' ],
			[ 'foobar://only-host', 'only-host/' ],
			[ '//somehost/path', 'somehost/path' ],
			[ 'foobar:///only/path', null ],
			[ 'foobar://', null ],
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

		if ( null === $result ) {
			Functions\expect( 'esc_html' )->once()->andReturnFirstArg();
			$this->expect_exception( \InvalidArgumentException::class );
		}

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

	/**
	 * Integration test for Image_Stream.
	 *
	 * @coversNothing
	 *
	 * @uses ::stream_open
	 *
	 * @return void
	 */
	public function test_integration(): void {
		// Make sure the real Image_Stream class is registered.
		\stream_wrapper_unregister( 'foobar' );
		Image_Stream::register( 'foobar' );

		// Set up test.
		$initial_time = \time();
		$stream_url   = 'foobar://my/test/stream';
		$initial_data = 'Some random stream.';

		// Wait a bit.
		\sleep( 1 );

		// Copy data to stream implementation.
		\file_put_contents( $stream_url, $initial_data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Check length of stream.
		$this->assertSame( \strlen( $initial_data ), \filesize( $stream_url ) );

		// Check last access time.
		$first_access_time = \fileatime( $stream_url );
		$this->assertGreaterThan( $initial_time, $first_access_time );

		// Check last modification time.
		$first_mod_time = \filemtime( $stream_url );
		$this->assertGreaterThan( $initial_time, $first_mod_time );

		// Now let's check the content.
		$this->assertSame( $initial_data, \file_get_contents( $stream_url ) );

		// Wait a bit.
		\sleep( 1 );

		// Modify the file.
		$additional_data = ' With another sentence tacked on.';
		\file_put_contents( $stream_url, $additional_data, \FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Check timestamps again.
		\clearstatcache( false, $stream_url );
		$second_access_time = \fileatime( $stream_url );
		$second_mod_time    = \filemtime( $stream_url );

		$this->assertSame( $first_access_time, $second_access_time ); // We didn't read the stream.
		$this->assertGreaterThan( $first_mod_time, $second_mod_time );

		// Now let's check the content again.
		$this->assertSame( "{$initial_data}{$additional_data}", \file_get_contents( $stream_url ) );

		// Wait a bit.
		\sleep( 1 );

		// Touch the file and check the times again.
		\touch( $stream_url );
		\clearstatcache( false, $stream_url );
		$this->assertGreaterThan( $second_access_time, \fileatime( $stream_url ) );
		$this->assertGreaterThan( $second_mod_time, \filemtime( $stream_url ) );

		// Touch the file and check the times yet another time.
		\touch( $stream_url, 111, 222 );
		\clearstatcache( false, $stream_url );
		$this->assertSame( 111, \filemtime( $stream_url ) );
		$this->assertSame( 222, \fileatime( $stream_url ) );
	}
}
