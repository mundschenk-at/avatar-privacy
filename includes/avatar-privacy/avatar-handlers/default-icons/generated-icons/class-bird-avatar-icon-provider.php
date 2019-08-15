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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Generated_Icons;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generating_Icon_Provider;
use Avatar_Privacy\Avatar_Handlers\Default_Icons\Generators;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * An icon provider for "birds" style icons.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Bird_Avatar_Icon_Provider extends Generating_Icon_Provider {

	/**
	 * Creates a new instance.
	 *
	 * @param Generators\Bird_Avatar $generator   A generator instance.
	 * @param Filesystem_Cache       $file_cache  The file cache handler.
	 */
	public function __construct( Generators\Bird_Avatar $generator, Filesystem_Cache $file_cache ) {
		parent::__construct( $generator, $file_cache, [ 'bird' ] );
	}

	/**
	 * Retrieves the filename (including the sub-directory and file extension).
	 *
	 * @param  string $identity The identity (mail address) hash. Ignored.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	protected function get_filename( $identity, $size ) {
		return "bird/{$this->get_sub_dir( $identity )}/{$identity}-{$size}.png";
	}

	/**
	 * Retrieves the user-visible, translated name.
	 *
	 * @return string
	 */
	public function get_name() {
		return \__( 'Birds (Generated)', 'avatar-privacy' );
	}
}
