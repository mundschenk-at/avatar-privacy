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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons;

/**
 * An abstract implementation of the Default_Icon_Provider interface.
 *
 * @since 1.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons
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
	 * The primary type (option value).
	 *
	 * @var string
	 */
	private $primary_type;

	/**
	 * The user-visible name. Already translated.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Creates a new instance.
	 *
	 * @since 1.2.0 Parameter $name added.
	 *
	 * @param string[] $types An array of valid types.
	 * @param string   $name  Optional. A translated user-visible name. Maybe left empty if a provider replaces a core default icon. Default ''.
	 */
	protected function __construct( array $types, $name = '' ) {
		$this->valid_types  = \array_flip( $types );
		$this->primary_type = $types[0];
		$this->name         = $name;
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
	 * Retrieves all icon types handled by the class.
	 *
	 * @since 2.0.0
	 *
	 * @return string[]
	 */
	public function get_provided_types() {
		return \array_keys( $this->valid_types );
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
	 * Retrieves the option value (the primary provided type).
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_option_value() {
		return $this->primary_type;
	}

	/**
	 * Retrieves the user-visible, translated name. Replacements for existing
	 * core default icon styles may return the empty string instead.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}
}
