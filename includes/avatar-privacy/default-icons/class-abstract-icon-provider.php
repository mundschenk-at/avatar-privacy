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
 * An abstract implementation of the Default_Icon_Provider interface.
 *
 * @since 1.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Abstract_Icon_Provider implements Icon_Provider {

	/**
	 * An array containing valid types as indexes.
	 *
	 * @var array
	 */
	private $valid_types;

	/**
	 * Creates a new instance.
	 *
	 * @param string[] $types An array of valid types.
	 */
	protected function __construct( array $types ) {
		$this->valid_types = \array_flip( $types );
	}

	/**
	 * Checks if this Default_Icon_Provider handles the given icon type.
	 *
	 * @param  string $type The default icon type.
	 *
	 * @return bool
	 */
	public function provides( $type ) {
		return isset( $this->valid_types[ $type ] );
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	abstract public function get_icon_url( $identity, $size );

	/**
	 * Creates a hash from the given mail address using the SHA-256 algorithm.
	 *
	 * @param  string $email An email address.
	 * @param  string $salt  Optional. A salt value for the hash function. Default ''.
	 *
	 * @return string
	 */
	public function hash( $email, $salt = '' ) {
		return \hash( 'sha256', "{$salt}{$email}" );
	}
}
