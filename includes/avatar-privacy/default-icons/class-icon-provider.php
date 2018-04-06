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

/**
 * Specifies an interface for default icon providers.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
interface Icon_Provider {

	/**
	 * Checks if this Default_Icon_Provider handles the given icon type.
	 *
	 * @param  string $type The default icon type.
	 *
	 * @return bool
	 */
	public function provides( $type );

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size );

	/**
	 * Creates a hash from the given mail address.
	 *
	 * @param  string $email An email address.
	 * @param  string $salt  Optional. A salt value for the hash function. Default ''.
	 *
	 * @return string
	 */
	public function hash( $email, $salt = '' );
}
