<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Data_Storage;

/**
 * A filesystem caching handler.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Filesystem_Cache {

	const CACHE_DIR = 'avatar-privacy/cache/';

	/**
	 * The base directory for the filesystem cache.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * The base URL for accessing cached files.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Information about the uploads directory.
	 *
	 * @var array
	 */
	private $upload_dir;

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		$this->get_base_dir();
	}

	/**
	 * Retrieves the base directory for caching files.
	 *
	 * @throws \RuntimeException A RuntimeException is thrown if the cache directory does not exist and can't be created.
	 *
	 * @return string
	 */
	public function get_base_dir() {
		if ( empty( $this->base_dir ) ) {
			$upload_dir     = $this->get_upload_dir();
			$this->base_dir = "{$upload_dir['basedir']}/" . self::CACHE_DIR;

			if ( ! \wp_mkdir_p( $this->base_dir ) ) {
				throw new \RuntimeException( "The cache directory {$this->base_dir} could not be created." );
			}
		}

		return $this->base_dir;
	}

	/**
	 * Retrieves the base URL for accessing cached files.
	 *
	 * @return string
	 */
	public function get_base_url() {
		if ( empty( $this->base_url ) ) {
			$upload_dir     = $this->get_upload_dir();
			$this->base_url = "{$upload_dir['baseurl']}/" . self::CACHE_DIR;
		}

		return $this->base_url;
	}

	/**
	 * Retrieves information about the upload directory.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @return array
	 */
	protected function get_upload_dir() {
		if ( empty( $this->upload_dir ) ) {
			$multisite = \is_multisite();

			if ( $multisite ) {
				\switch_to_blog( \get_main_site_id() );
			}

			$this->upload_dir = \wp_get_upload_dir();

			if ( $multisite ) {
				\restore_current_blog();
			}
		}

		return $this->upload_dir;
	}

	/**
	 * Stores data in the filesystem cache.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 * @param  mixed  $data     The data. Will not be cached if empty.
	 * @param  bool   $force    Optional. The cached file will only be overwritten if set to true. Default false.
	 *
	 * @return bool             True if the file was successfully stored in the cache, false otherwise.
	 */
	public function set( $filename, $data, $force = false ) {
		$file = $this->get_base_dir() . $filename;

		if ( \file_exists( $file ) && ! $force ) {
			return true;
		}

		if ( 0 === \strlen( $data ) || ! \wp_mkdir_p( \dirname( $file ) ) || false === \file_put_contents( $file, $data, \LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Retrieves the URL for the cached file.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 *
	 * @return string
	 */
	public function get_url( $filename ) {
		return $this->get_base_url() . $filename;
	}

	/**
	 * Removes a file from the cache.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 *
	 * @return bool             True if the file was successfully removed from the cache, false otherwise.
	 */
	public function delete( $filename ) {
		$file = $this->get_base_dir() . $filename;

		return \is_writable( $file ) && \unlink( $file );
	}

	/**
	 * Invalidate all cached elements by recursively deleting all files and directories.
	 *
	 * @param string $subdir Optional. Limit invalidation to the given subdirectory. Default ''.
	 * @param string $regex  Optional. Limit invalidation to files matching the given regular expression. Default ''.
	 */
	public function invalidate( $subdir = '', $regex = '' ) {
		try {
			$iterator = $this->get_recursive_file_iterator( $subdir, $regex );
		} catch ( \UnexpectedValueException $e ) {
			// Ignore non-existing subdirectories.
			return;
		}

		foreach ( $iterator as $path => $file ) {
			if ( $file->isWritable() ) {

				if ( $file->isDir() ) {
					\rmdir( $path );
				} else {
					\unlink( $path );
				}
			}
		}
	}

	/**
	 * Invalidate all cached files older than the given age.
	 *
	 * @param  int    $age    The maximum file age in seconds.
	 * @param  string $subdir Optional. Limit invalidation to the given subdirectory. Default ''.
	 * @param  string $regex  Optional. Limit invalidation to files matching the given regular expression. Default ''.
	 */
	public function invalidate_files_older_than( $age, $subdir = '', $regex = '' ) {
		try {
			$now      = \time();
			$iterator = $this->get_recursive_file_iterator( $subdir, $regex );
		} catch ( \UnexpectedValueException $e ) {
			// Ignore non-existing subdirectories.
			return;
		}

		foreach ( $iterator as $path => $file ) {
			if ( $file->isWritable() && ! $file->isDir() && $now - $file->getMTime() > $age ) {
				\unlink( $path );
			}
		}
	}

	/**
	 * Retrieves a recursive iterator for all files in the cache.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  string $subdir Optional. Limit invalidation to the given subdirectory. Default ''.
	 * @param  string $regex  Optional. Limit invalidation to files matching the given regular expression. Default ''.
	 *
	 * @return \OuterIterator
	 */
	protected function get_recursive_file_iterator( $subdir = '', $regex = '' ) {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( "{$this->get_base_dir()}{$subdir}", \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		if ( ! empty( $regex ) ) {
			$files = new \RegexIterator( $files, $regex, \RecursiveRegexIterator::MATCH );
		}

		return $files;
	}
}
