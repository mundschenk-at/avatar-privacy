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

namespace Avatar_Privacy\Default_Icons;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * An icon provider for "monsterid" style icons.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Monster_ID_Icon_Provider extends Abstract_Icon_Provider {

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The icon generator.
	 *
	 * @var Generator\Monster_ID
	 */
	private $generator;

	/**
	 * Creates a new instance.
	 *
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( Filesystem_Cache $file_cache ) {
		parent::__construct( [ 'monsterid' ] );

		$this->file_cache = $file_cache;
		$this->generator  = new Generator\Monster_ID();
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash. Ignored.
	 * @param  string $size     The requested size in pixels.
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size ) {
		$filename = "monsterid/$identity-$size.png";

		$icon = $this->generator->build_monster( $identity, $size );

		$this->file_cache->set( $filename, $icon, true );

		return $this->file_cache->get_url( $filename );
	}
}
