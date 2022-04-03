<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2022 Peter Putzer.
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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generator;
use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * An Icon_Provider that generates dynamic icons.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Generating_Icon_Provider extends Abstract_Icon_Provider {

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The icon generator.
	 *
	 * @var Generator
	 */
	private $generator;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.0.0 Parameter $name added.
	 * @since 2.1.0 Parameter $name removed to allow proper translation loading.
	 *
	 * @param Generator        $generator  An image generator.
	 * @param Filesystem_Cache $file_cache The file cache handler.
	 * @param string[]         $types      An array of valid types. The first entry is used as the cache sub-directory.
	 */
	protected function __construct( Generator $generator, Filesystem_Cache $file_cache, array $types ) {
		parent::__construct( $types );

		$this->generator  = $generator;
		$this->file_cache = $file_cache;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash. Ignored.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size ) {
		$filename = $this->get_filename( $identity, $size );

		// Only generate a new icon if necessary.
		if ( ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) ) {
			$this->file_cache->set( $filename, (string) $this->generator->build( $identity, $size ) );
		}

		return $this->file_cache->get_url( $filename );
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 *
	 * @return string
	 */
	protected function get_sub_dir( $identity ) {
		return \implode( '/', \str_split( \substr( $identity, 0, 2 ) ) );
	}

	/**
	 * Retrieves the filename (including the sub-directory and file extension).
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	abstract protected function get_filename( $identity, $size );
}
