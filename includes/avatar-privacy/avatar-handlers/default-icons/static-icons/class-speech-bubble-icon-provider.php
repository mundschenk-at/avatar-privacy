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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons\Static_Icons;

use Avatar_Privacy\Avatar_Handlers\Default_Icons\SVG_Icon_Provider;

/**
 * An icon provider for the "speech bubble" icon.
 *
 * @since 2.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Speech_Bubble_Icon_Provider extends SVG_Icon_Provider {

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		parent::__construct( [ 'bubble', 'comment' ], 'comment-bubble' );
	}

	/**
	 * Retrieves the user-visible, translated name.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_name() {
		return \__( 'Speech Bubble', 'avatar-privacy' );
	}
}
