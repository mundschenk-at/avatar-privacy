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

namespace Avatar_Privacy;

use Mundschenk\UI\Controls;

/**
 * Default configuration for Avatar Privacy.
 *
 * @since 1.2.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Settings {

	/**
	 * The defaults array index of the information headers.
	 *
	 * @var string
	 */
	const INFORMATION_HEADER = 'display';

	/**
	 * The options array index of the default gravatar policy.
	 *
	 * @var string
	 */
	const GRAVATAR_USE_DEFAULT = 'gravatar_use_default';

	/**
	 * The defaults array.
	 *
	 * @var array
	 */
	private static $defaults;

	/**
	 * The fields definition array.
	 *
	 * @var array
	 */
	private static $fields;

	/**
	 * The cached information header markup.
	 *
	 * @var string
	 */
	private static $information_header;

	/**
	 * Retrieves the settings field definitions.
	 *
	 * @param string $information_header Optional. The HTML markup for the informational header in the settings. Default ''.
	 *
	 * @return array
	 */
	public static function get_fields( $information_header = '' ) {
		if ( empty( self::$fields ) ) {
			self::$fields = [ // @codeCoverageIgnore
				self::INFORMATION_HEADER   => [
					'ui'            => Controls\Display_Text::class,
					'tab_id'        => '', // Will be added to the 'discussions' page.
					'section'       => 'avatars',
					'elements'      => [], // Will be updated below.
					'short'         => \__( 'Avatar Privacy', 'avatar-privacy' ),
				],
				self::GRAVATAR_USE_DEFAULT => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => 'avatars',
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Display Gravatar images by default.', 'avatar-privacy' ),
					'help_text'        => \__( 'Checking will ensure that gravatars are displayed when there is no explicit setting for the user or mail address (e.g. for comments made before installing Avatar Privacy). Please only enable this setting after careful consideration of the privacy implications.', 'avatar-privacy' ),
					'default'          => 0,
					'grouped_with'     => 'display',
					'outer_attributes' => [ 'class' => 'avatar-settings-enabled' ],
				],
			];
		}

		// Allow calls where the information header is not relevant by caching it separately.
		if ( ! empty( $information_header ) && $information_header !== self::$information_header ) {
			self::$fields[ self::INFORMATION_HEADER ]['elements'] = [ $information_header ];
			self::$information_header                             = $information_header;
		}

		return self::$fields;
	}

	/**
	 * Retrieves the default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		if ( empty( self::$defaults ) ) {
			$defaults = [];
			foreach ( self::get_fields() as $index => $field ) {
				if ( isset( $field['default'] ) ) {
					$defaults[ $index ] = $field['default'];
				}
			}

			self::$defaults = $defaults;
		}

		return self::$defaults;
	}
}
