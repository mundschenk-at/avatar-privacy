<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

/**
 * A collection of utility methods for use in templates.
 *
 * @internal
 *
 * @since 2.0.0
 * @since 2.4.0 Class made concrete and marked as internal.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Template {

	/**
	 * The allowed HTML tags and attributes for checkbox labels.
	 *
	 * @var array
	 */
	const ALLOWED_HTML_LABEL = [
		'a' => [
			'href'   => true,
			'rel'    => true,
			'target' => true,
		],
	];

	/**
	 * Retrieves and translates the URL for linking to Gravatar.com (for explanations).
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public static function get_gravatar_link_url() {
		return \__( 'https://en.gravatar.com/', 'avatar-privacy' );
	}

	/**
	 * Retrieves and filters the `rel` attribute for links to gravatar.com.
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_rel() {
		/**
		 * Filters the `rel` attribute for user-visible links to gravatar.com.
		 *
		 * @param string $rel Default 'noopener nofollow'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_rel', 'noopener nofollow' ) );
	}

	/**
	 * Retrieves and filters the `target` attribute for links to gravatar.com.
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_target() {
		/**
		 * Filters the `target` attribute for user-visible links to gravatar.com.
		 *
		 * @param string $target Default '_self'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_target', '_self' ) );
	}

	/**
	 * Parses and echoes a partial template.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return void
	 */
	public function print_partial( $partial, array $args = [] ) {
		if ( \extract( $args ) !== \count( $args ) ) { // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- needed for "natural" partials.
			\_doing_it_wrong( __METHOD__, \esc_html( "Invalid arguments passed to partial {$partial}." ), 'Avatar Privacy 2.4.0' );
		}

		require \AVATAR_PRIVACY_PLUGIN_PATH . "/{$partial}";
	}

	/**
	 * Parses a partial template and returns the content as a string.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return string
	 */
	public function get_partial( $partial, array $args = [] ) {
		\ob_start();
		$this->print_partial( $partial, $args );
		return (string) \ob_get_clean();
	}
}
