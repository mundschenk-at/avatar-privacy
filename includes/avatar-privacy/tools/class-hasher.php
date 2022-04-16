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

namespace Avatar_Privacy\Tools;

use Avatar_Privacy\Data_Storage\Network_Options;

/**
 * A helper class to handle identifier hashing.
 *
 * Extracted from \Avatar_Privacy\Core
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Hasher {

	/**
	 * The salt used for the get_hash() method.
	 *
	 * @var string
	 */
	private $salt;

	/**
	 * The network options handler.
	 *
	 * @var Network_Options
	 */
	private $network_options;

	/**
	 * Creates a new instance.
	 *
	 * @param Network_Options $network_options The network options handler.
	 */
	public function __construct( Network_Options $network_options ) {
		$this->network_options = $network_options;
	}

	/**
	 * Retrieves the salt for current the site/network.
	 *
	 * @return string
	 */
	public function get_salt() {
		if ( empty( $this->salt ) ) {
			/**
			 * Filters the salt used for generating this sites email hashes.
			 *
			 * If a non-empty string is returned, this value is used instead of
			 * the one stored in the network options. On first activation, a random
			 * value is generated and stored in the option.
			 *
			 * @param string $salt Default ''.
			 */
			$_salt = \apply_filters( 'avatar_privacy_salt', '' );

			if ( empty( $_salt ) ) {
				// Let's try the network option next.
				$_salt = $this->network_options->get( Network_Options::SALT );

				if ( ! \is_string( $_salt ) || empty( $_salt ) ) {
					// Still nothing? Generate a random value.
					$_salt = $this->generate_salt();

					// Save the generated salt.
					$this->network_options->set( Network_Options::SALT, $_salt );
				}
			}

			$this->salt = $_salt;
		}

		return $this->salt;
	}

	/**
	 * Generates a new salt.
	 *
	 * @since  2.5.0
	 *
	 * @return string
	 */
	protected function generate_salt() {
		return \wp_generate_password( 64, true, true );
	}

	/**
	 * Generates a salted SHA-256 hash for the given identifier (an e-mail address
	 * in most cases).
	 *
	 * @param  string $identifer      The identifier. Whitespace on either side
	 *                                is ignored.
	 * @param  bool   $case_sensitive Optional. Whether the identifier is case-sensitive
	 *                                (e.g. an URL). Default false.
	 *
	 * @return string
	 */
	public function get_hash( $identifer, $case_sensitive = false ) {
		$identifier = \trim( $identifer );

		if ( ! $case_sensitive ) {
			$identifer = \strtolower( $identifier );
		}

		return \hash( 'sha256', "{$this->get_salt()}{$identifer}" );
	}
}
