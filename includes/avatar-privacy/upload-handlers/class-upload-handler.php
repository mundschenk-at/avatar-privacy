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

namespace Avatar_Privacy\Upload_Handlers;

use Avatar_Privacy\Core;
use Avatar_Privacy\Image_Tools;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Handles image uploads.
 *
 * @since 1.2.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Upload_Handler {

	const ALLOWED_MIME_TYPES = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
	];

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	protected $plugin_file;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	protected $file_cache;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	protected $core;

	/**
	 * Creates a new instance.
	 *
	 * @param string           $plugin_file The full path to the base plugin file.
	 * @param Core             $core        The core API.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( $plugin_file, Core $core, Filesystem_Cache $file_cache ) {
		$this->plugin_file = $plugin_file;
		$this->core        = $core;
		$this->file_cache  = $file_cache;
	}

	/**
	 * Returns a unique filename.
	 *
	 * @param string $directory The uploads directory.
	 * @param string $filename  The proposed filename.
	 * @param string $extension The file extension (including leading dot).
	 *
	 * @return string
	 */
	public function get_unique_filename( $directory, $filename, $extension ) {
		$number    = 1;
		$base_name = $filename;

		while ( \file_exists( "$directory/{$filename}{$extension}" ) ) {
			$filename = "{$base_name}_{$number}";
			$number++;
		}

		return "{$filename}{$extension}";
	}
}
