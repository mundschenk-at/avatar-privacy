<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tools\Images;

/**
 * A persistent memory stream for storing temporary images.
 *
 * Use `avprimg://HANDLE/dummy/path` to access. Only the host part of the URL
 * (HANDLE) is used as the actual stream identifier. The path is ignored.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type StreamStat array{ dev: int, ino: int, mode: int, nlink: int, uid: int, gid: int, rdev: int, size: int, atime: int, mtime: int, ctime: int, blksize: int, blocks: int }
 * @phpstan-type StreamHandle array{ data: string, atime: int, mtime: int }
 */
class Image_Stream {
	const PROTOCOL = 'avprimg';

	const READ_MODE  = [
		'r'  => true,
		'r+' => true,
		'w'  => false,
		'w+' => true,
		'a'  => false,
		'a+' => true,
		'c'  => false,
		'c+' => true,
	];
	const WRITE_MODE = [
		'r'  => false,
		'r+' => true,
		'w'  => true,
		'w+' => true,
		'a'  => true,
		'a+' => true,
		'c'  => true,
		'c+' => true,
	];

	// The access keys for StreamHandle components.
	private const DATA              = 'data';
	private const ACCESS_TIME       = 'atime';
	private const MODIFICATION_TIME = 'mtime';

	/**
	 * The persistent handles for existing streams.
	 *
	 * @var array
	 *
	 * @phpstan-var array<string, StreamHandle>
	 */
	private static array $handles = [];

	/**
	 * The contents of the stream.
	 *
	 * @var string
	 */
	private string $data;

	/**
	 * The access time of the stream.
	 *
	 * @since 2.7.0
	 *
	 * @var int
	 */
	private int $atime;

	/**
	 * The modification time of the stream.
	 *
	 * @since 2.7.0
	 *
	 * @var int
	 */
	private int $mtime;

	/**
	 * Whether this stream can be read from.
	 *
	 * @var bool
	 */
	private bool $read;

	/**
	 * Whether this stream can be written to.
	 *
	 * @var bool
	 */
	private bool $write;

	/**
	 * The stream options.
	 *
	 * @var int
	 */
	private int $options;

	/**
	 * The current position within the stream.
	 *
	 * @var int
	 */
	private int $position;

	/**
	 * The stream context resource.
	 *
	 * @var resource
	 */
	public $context;

	/**
	 * Opens a stream. Only the host part is used as the handle.
	 *
	 * @param  string $path        The stream URL.
	 * @param  string $mode        The mode flags.
	 * @param  int    $options     Holds additional flags set by the streams API.
	 * @param  string $opened_path Return value of the actually opened path. Passed by reference.
	 *
	 * @return bool
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$real_path     = static::get_handle_from_url( $path );
		$this->options = $options;

		[
			self::DATA              => &$this->data,
			self::ACCESS_TIME       => &$this->atime,
			self::MODIFICATION_TIME => &$this->mtime,
		] = static::get_data_reference( $real_path );

		// Strip binary/text flags from mode for comparison.
		$mode = \str_replace( [ 'b', 't' ], '', $mode );

		switch ( $mode ) {

			case 'w':
			case 'w+':
				$this->stream_truncate( 0 );
				// fall through.
			case 'r':
			case 'r+':
			case 'c':
			case 'c+':
				$this->read     = self::READ_MODE[ $mode ];
				$this->write    = self::WRITE_MODE[ $mode ];
				$this->position = 0;
				break;

			case 'a':
			case 'a+':
				$this->read     = self::READ_MODE[ $mode ];
				$this->write    = self::WRITE_MODE[ $mode ];
				$this->position = \strlen( $this->data );
				break;

			default:
				// Signal error.
				$this->maybe_trigger_error( ! empty( $this->options & \STREAM_REPORT_ERRORS ), 'Invalid mode specified (mode specified makes no sense for this stream implementation)' );

				return false;
		}

		// Set the opened path if requested.
		if ( $this->options & \STREAM_USE_PATH ) {
			$opened_path = $real_path;
		}

		return true;
	}

	/**
	 * Reads from the stream.
	 *
	 * @param  int $bytes The number of bytes to read.
	 *
	 * @return string|false
	 */
	public function stream_read( $bytes ) {
		if ( $this->read ) {
			$read_bytes      = \substr( $this->data, $this->position, $bytes );
			$this->position += \strlen( $read_bytes );

			// Update access time.
			$this->atime = \time();

			return $read_bytes;
		}

		return false;
	}

	/**
	 * Writes to the stream.
	 *
	 * @param string $data The data to write.
	 *
	 * @return int
	 */
	public function stream_write( $data ) {
		if ( $this->write ) {
			$data_length     = \strlen( $data );
			$left            = \substr( $this->data, 0, $this->position );
			$right           = \substr( $this->data, $this->position + $data_length );
			$this->data      = "{$left}{$data}{$right}";
			$this->position += $data_length;

			// Update modification time.
			$this->mtime = \time();

			return $data_length;
		}

		return 0;
	}

	/**
	 * Retrieves the current position.
	 *
	 * @return int
	 */
	public function stream_tell() {
		return $this->position;
	}

	/**
	 * Determines if the stream has reached its EOF.
	 *
	 * @return bool
	 */
	public function stream_eof() {
		return $this->position >= \strlen( $this->data );
	}

	/**
	 * Seeks to a new position.
	 *
	 * @param int $offset The amount by which to change the position.
	 * @param int $whence A flag indicating the direction of the seek operation.
	 *
	 * @return bool
	 */
	public function stream_seek( $offset, $whence ) {
		switch ( $whence ) {
			case \SEEK_SET:
				$this->position = $offset;
				$truncate       = true;
				break;

			case \SEEK_CUR:
				$this->position += $offset;
				$truncate        = true;
				break;

			case \SEEK_END:
				$this->position = \strlen( $this->data ) + $offset;
				$truncate       = true;
				break;

			default:
				$truncate = false;
		}

		if ( $truncate ) {
			$this->truncate_after_seek();
		}

		return $truncate;
	}

	/**
	 * Truncates the stream after a seek operation beyond its length.
	 *
	 * @return void
	 */
	protected function truncate_after_seek() {
		if ( $this->position > \strlen( $this->data ) ) {
			$this->stream_truncate( $this->position );
		}
	}

	/**
	 * Truncates the stream to a given size.
	 *
	 * @param int $length The new length in bytes.
	 *
	 * @return bool
	 */
	public function stream_truncate( $length ) {
		$current_length = \strlen( $this->data );

		if ( $current_length > $length ) {
			$this->data  = \substr( $this->data, 0, $length );
			$this->mtime = \time();
		} elseif ( $current_length < $length ) {
			$this->data  = \str_pad( $this->data, $length, "\0", \STR_PAD_RIGHT );
			$this->mtime = \time();
		}

		return true;
	}

	/**
	 * Retrieves information about the stream.
	 *
	 * @return array
	 *
	 * @phpstan-return StreamStat
	 */
	public function stream_stat() {
		return [
			'dev'     => 0,
			'ino'     => 0,
			'mode'    => 0100777, // is_file & mode 0777.
			'nlink'   => 0,
			'uid'     => 0,
			'gid'     => 0,
			'rdev'    => 0,
			'size'    => \strlen( $this->data ),
			'atime'   => isset( $this->atime ) ? $this->atime : 0,
			'mtime'   => isset( $this->mtime ) ? $this->mtime : 0,
			'ctime'   => isset( $this->mtime ) ? $this->mtime : 0, // We don't have an inode.
			'blksize' => -1,
			'blocks'  => -1,
		];
	}

	/**
	 * Retrieves information about the stream. Non-existing paths are silently
	 * ignored to simulate folders.
	 *
	 * @param  string $path  The URL.
	 * @param  int    $flags Additional flags set by the streams API.
	 *
	 * @return array|false
	 *
	 * @phpstan-return StreamStat|false
	 */
	public function url_stat( $path, $flags ) {
		$handle = static::get_handle_from_url( $path );

		if ( static::handle_exists( $handle ) ) {
			$h = @\fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

			// If fopen() failed, we are in trouble.
			if ( ! \is_resource( $h ) ) {
				$this->maybe_trigger_error( ! ( $flags & \STREAM_URL_STAT_QUIET ), 'Error opening stream handle for stat call' );

				return false;
			}

			return \fstat( $h );
		}

		// Since we don't really have folders, treat every other call as if
		// STREAM_URL_STAT_QUIET was set.
		return [
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
		];
	}

	/**
	 * Implements touch(), chmod(), chown() and chgrp() for the stream.
	 *
	 * @param  string           $path   The URL to set the metadata for.
	 * @param  int              $option A flag indicating the originating function.
	 * @param  array|string|int $value  The arguments of the originating function.
	 *
	 * @return bool
	 *
	 * @phpstan-param array{ mtime: int, atime: int }|string|int $value
	 */
	public function stream_metadata( $path, $option, $value ) {
		if ( \STREAM_META_TOUCH === $option ) {
			$stream = &static::get_data_reference( static::get_handle_from_url( $path ) );

			$stream[ self::MODIFICATION_TIME ] = isset( $value[0] ) ? (int) $value[0] : \time();
			$stream[ self::ACCESS_TIME ]       = isset( $value[1] ) ? (int) $value[1] : $stream[ self::MODIFICATION_TIME ];
		}

		// Ignore metadata changing functions, but simulate success.
		return true;
	}

	/**
	 * Unlinks the given URL.
	 *
	 * @param  string $path The stream URL.
	 *
	 * @return bool
	 */
	public function unlink( $path ) {
		$handle = static::get_handle_from_url( $path );

		if ( empty( $handle ) ) {
			return false;
		}

		static::delete_handle( $handle );

		// Clean up local references.
		unset( $this->data );
		unset( $this->atime );
		unset( $this->mtime );

		return true;
	}

	/**
	 * Triggers an error if the trigger condition is fulfilled.
	 *
	 * @since  2.4.0
	 * @since  2.8.0 Default $error_level downgraded to `E_USER_WARNING` due to the PHP 8.4 deprecation of `E_USER_ERROR`.
	 *
	 * @param  bool   $condition   Whether the error should be triggered.
	 * @param  string $message     The error message.
	 * @param  int    $error_level Optional. Only the E_USER_* constants are valid. Default `E_USER_WARNING`.
	 *
	 * @return void
	 */
	protected function maybe_trigger_error( $condition, $message, $error_level = \E_USER_WARNING ) {
		if ( $condition ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			\trigger_error( \esc_html( $message ), $error_level );
		}
	}

	/**
	 * Retrieves a reference to the handle and creates it if necessary.
	 *
	 * @since 2.1.0 Visibility changed to protected and renamed to get_data_reference.
	 *
	 * @param  string $handle The stream handle.
	 *
	 * @return array         A reference to the stream data.
	 *
	 * @phpstan-return StreamHandle
	 */
	protected static function &get_data_reference( $handle ) { // phpcs:ignore ImportDetection.Imports.RequireImports.Symbol -- false positive.
		if ( ! static::handle_exists( $handle ) ) {
			$now = \time();

			self::$handles[ $handle ] = [
				self::DATA              => '',
				self::ACCESS_TIME       => $now,
				self::MODIFICATION_TIME => $now,
			];
		}

		return self::$handles[ $handle ];
	}

	/**
	 * Determines if the given handle already exists.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  string $handle The stream handle.
	 *
	 * @return bool
	 */
	protected static function handle_exists( $handle ) {
		return isset( self::$handles[ $handle ] );
	}

	/**
	 * Retrieves the stream data and optionally deletes the handle.
	 *
	 * @param  string $handle The stream handle.
	 * @param  bool   $delete Delete the handle after retrieving the stream data.
	 *
	 * @return string|null    The stream data or null.
	 */
	public static function get_data( $handle, $delete = false ) {
		if ( ! static::handle_exists( $handle ) ) {
			return null;
		}

		// Save data.
		$result = static::get_data_reference( $handle )[ self::DATA ];

		// Clean up, if requested.
		if ( $delete ) {
			static::delete_handle( $handle );
		}

		return $result;
	}

	/**
	 * Deletes the given stream handle and its data.
	 *
	 * @param  string $handle The stream handle.
	 *
	 * @return void
	 */
	public static function delete_handle( $handle ) {
		unset( self::$handles[ $handle ] );
	}

	/**
	 * Retrieves the stream handle from the wrapper URL.
	 *
	 * @since  2.4.0  An exception is thrown when an invalid URL is passed to the method.
	 *
	 * @param  string $url The wrapper URL.
	 *
	 * @return string      The handle.
	 *
	 * @throws \InvalidArgumentException Throws an exception if the URL is not valid.
	 */
	public static function get_handle_from_url( $url ) {
		$parts = \parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		// Validate results.
		if ( empty( $parts ) ) {
			throw new \InvalidArgumentException( "{$url} is not a valid stream URL" );
		}

		$host = $parts['host'] ?? '';
		$path = $parts['path'] ?? '/';

		return $host . $path;
	}

	/**
	 * Registers the stream wrapper.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $protocol Optional. The wrapper-specific URL protocol. Default 'avprimg'.
	 *
	 * @return void
	 */
	public static function register( $protocol = self::PROTOCOL ) {
		if ( ! \in_array( $protocol, \stream_get_wrappers(), true ) ) {
			\stream_wrapper_register( $protocol, static::class );
		}
	}
}
