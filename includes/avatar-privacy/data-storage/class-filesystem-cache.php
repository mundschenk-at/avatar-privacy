<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

use Avatar_Privacy\Exceptions\Filesystem_Exception;

use function Avatar_Privacy\Tools\delete_file;

/**
 * A filesystem caching handler.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type UploadDir array{
 *     path: string,
 *     url: string,
 *     subdir: string,
 *     basedir: string,
 *     baseurl: string,
 *     error: string|false
 * }
 */
class Filesystem_Cache {

	const CACHE_DIR = 'avatar-privacy/cache/';

	/**
	 * The base directory for the filesystem cache.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * The base URL for accessing cached files.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Information about the uploads directory.
	 *
	 * @var array
	 *
	 * @phpstan-var UploadDir
	 */
	private array $upload_dir;

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		$this->get_base_dir();
	}

	/**
	 * Retrieves the base directory for caching files.
	 *
	 * @since  2.4.0 A Filesystem_Exception is thrown instead of a generic \RuntimeException.
	 *
	 * @throws Filesystem_Exception An exception is thrown if the cache directory
	 *                              does not exist and can't be created.
	 *
	 * @return string
	 */
	public function get_base_dir(): string {
		if ( empty( $this->base_dir ) ) {
			$this->base_dir = "{$this->get_upload_dir()['basedir']}/" . self::CACHE_DIR;

			if ( ! \wp_mkdir_p( $this->base_dir ) ) {
				throw new Filesystem_Exception( \esc_html( "The cache directory {$this->base_dir} could not be created." ) );
			}
		}

		return $this->base_dir;
	}

	/**
	 * Retrieves the base URL for accessing cached files.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		if ( empty( $this->base_url ) ) {
			$this->base_url = "{$this->get_upload_dir()['baseurl']}/" . self::CACHE_DIR;
		}

		return $this->base_url;
	}

	/**
	 * Retrieves information about the upload directory.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @return array
	 *
	 * @phpstan-return UploadDir
	 */
	protected function get_upload_dir(): array {
		if ( ! isset( $this->upload_dir ) ) {
			$multisite = \is_multisite();

			if ( $multisite ) {
				\switch_to_blog( \get_main_site_id() );
			}

			// We only need the basedir, so don't create the monthly sub-directory.
			$this->upload_dir = \wp_upload_dir( null, false );

			if ( $multisite ) {
				\restore_current_blog();
			}
		}

		return $this->upload_dir;
	}

	/**
	 * Stores data in the filesystem cache.
	 *
	 * @since  2.6.0 The type of the `$data` parameter has been corrected to `string`.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 * @param  string $data     The (possibly binary) data. Will not be cached if empty.
	 * @param  bool   $force    Optional. The cached file will only be overwritten if set to true. Default false.
	 *
	 * @return bool             True if the file was successfully stored in the cache, false otherwise.
	 */
	public function set( string $filename, string $data, bool $force = false ): bool {
		$file = $this->get_base_dir() . $filename;

		if ( \file_exists( $file ) && ! $force ) {
			return true;
		}

		return ! (
			// Don't create empty files.
			0 === \strlen( $data ) ||
			// Make sure that the file path is valid.
			! \wp_mkdir_p( \dirname( $file ) ) ||
			// Check if the file has been stored successfully.
			false === \file_put_contents( $file, $data, \LOCK_EX )  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		);
	}

	/**
	 * Retrieves the URL for the cached file.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 *
	 * @return string
	 */
	public function get_url( string $filename ): string {
		return $this->get_base_url() . $filename;
	}

	/**
	 * Removes a file from the cache.
	 *
	 * @param  string $filename The filename (including any sub directory).
	 *
	 * @return bool             True if the file was successfully removed from the cache, false otherwise.
	 */
	public function delete( string $filename ): bool {
		$file = $this->get_base_dir() . $filename;

		return \wp_is_writable( $file ) && delete_file( $file );
	}

	/**
	 * Invalidate all cached elements by recursively deleting all files and directories.
	 *
	 * @param string $subdir Optional. Limit invalidation to the given subdirectory. Default ''.
	 * @param string $regex  Optional. Limit invalidation to files matching the given regular expression. Default ''.
	 *
	 * @return void
	 */
	public function invalidate( string $subdir = '', string $regex = '' ): void {
		try {
			$iterator = $this->get_recursive_file_iterator( $subdir, $regex );
		} catch ( \UnexpectedValueException $e ) {
			// Ignore non-existing subdirectories.
			return;
		}

		foreach ( $iterator as $path => $file ) {
			if ( $file->isWritable() ) {

				if ( $file->isDir() ) {
					\rmdir( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Using the WP_Filesystem API is not an option on the frontend.
				} else {
					delete_file( $path );
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
	 *
	 * @return void
	 */
	public function invalidate_files_older_than( int $age, string $subdir = '', string $regex = '' ): void {
		try {
			$now      = \time();
			$iterator = $this->get_recursive_file_iterator( $subdir, $regex );
		} catch ( \UnexpectedValueException $e ) {
			// Ignore non-existing subdirectories.
			return;
		}

		foreach ( $iterator as $path => $file ) {
			if ( $file->isWritable() && ! $file->isDir() && $now - $file->getMTime() > $age ) {
				delete_file( $path );
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
	 * @return \OuterIterator<string,\SplFileInfo>
	 */
	protected function get_recursive_file_iterator( string $subdir = '', string $regex = '' ): \OuterIterator {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( "{$this->get_base_dir()}{$subdir}", \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		if ( ! empty( $regex ) ) {
			/**
			 * Further filter the collected files using the given regular expression.
			 *
			 * @phpstan-var \RegexIterator<string,\SplFileInfo,\RecursiveIteratorIterator<\RecursiveDirectoryIterator<string,\SplFileInfo>>> $files
			 */
			$files = new \RegexIterator( $files, $regex, \RecursiveRegexIterator::MATCH );
		}

		return $files;
	}
}
