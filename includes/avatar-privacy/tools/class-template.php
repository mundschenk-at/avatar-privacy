<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2023 Peter Putzer.
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
 *
 * @phpstan-type PartialArguments array<string,mixed>
 */
class Template {

	/**
	 * The allowed HTML tags and attributes for checkbox labels.
	 *
	 * @var array<string, array<string, bool>>
	 */
	const ALLOWED_HTML_LABEL = [
		'a' => [
			'href'   => true,
			'rel'    => true,
			'target' => true,
		],
	];

	/**
	 * Retrieves and filters the `rel` attribute for links to gravatar.com.
	 *
	 * @deprecated 2.4.0
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_rel() {
		\_deprecated_function( __METHOD__, '2.4.0', 'Please use ' . __CLASS__ . '::get_uploader_description() or ::get_use_gravatar_label() instead.' );

		/**
		 * Filters the `rel` attribute for user-visible links to gravatar.com.
		 *
		 * @since 2.0.0
		 *
		 * @param string $rel Default 'noopener nofollow'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_rel', 'noopener nofollow' ) );
	}

	/**
	 * Retrieves and filters the `target` attribute for links to gravatar.com.
	 *
	 * @deprecated 2.4.0
	 *
	 * @return string The result is safe for output.
	 */
	public static function get_gravatar_link_target() {
		\_deprecated_function( __METHOD__, '2.4.0', 'Please use ' . __CLASS__ . '::get_uploader_description() or ::get_use_gravatar_label() instead.' );

		/**
		 * Filters the `target` attribute for user-visible links to gravatar.com.
		 *
		 * @since 2.0.0
		 *
		 * @param string $target Default '_self'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_target', '_self' ) );
	}

	/**
	 * Retrieves the uploader description depending on whether the user is allowed
	 * upload images and whether a local avatar has already been set.
	 *
	 * @param  bool $can_upload       Optional. Whether the current user can upload images. Default false.
	 * @param  bool $has_local_avatar Optional. Whether the user currently has a local avatar set. Default false.
	 *
	 * @return string
	 */
	public function get_uploader_description( $can_upload = false, $has_local_avatar = false ) {
		$link_needed = false;

		if ( $can_upload ) {
			if ( ! $has_local_avatar ) {
				/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
				$description = \__( 'No local profile picture is set. Use the upload field to add a local profile picture or change your profile picture on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>.', 'avatar-privacy' );
				$link_needed = true;
			} else {
				/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
				$description = \__( 'Replace the local profile picture by uploading a new avatar, or erase it (falling back on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>) by checking the delete option.', 'avatar-privacy' );
				$link_needed = true;
			}
		} else {
			if ( ! $has_local_avatar ) {
				/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
				$description = \__( 'No local profile picture is set. Change your profile picture on <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a>.', 'avatar-privacy' );
				$link_needed = true;
			} else {
				$description = \__( 'You do not have media management permissions. To change your local profile picture, contact the site administrator.', 'avatar-privacy' );
			}
		}

		if ( $link_needed ) {
			$description = $this->fill_in_gravatar_url( $description );
		}

		return $description;
	}

	/**
	 * Retrieves the label for the "Use gravatar" checkbox.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $context Optional. The context for the label (valid: 'user',
	 *                         'comment'). Default 'user'.
	 *
	 * @return string
	 */
	public function get_use_gravatar_label( $context = 'user' ) {
		if ( 'user' === $context ) {
			/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
			$label = \__( 'Display a <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a> image for my e-mail address.', 'avatar-privacy' );
		} elseif ( 'comment' === $context ) {
			/* translators: 1: gravatar.com URL, 2: rel attribute, 3: target attribute */
			$label = \__( 'Display a <a href="%1$s" rel="%2$s" target="%3$s">Gravatar</a> image next to my comments.', 'avatar-privacy' );
		} else {
			\_doing_it_wrong( __METHOD__, \esc_html( "Invalid context $context" ), '2.4.0' );
			return '';
		}

		return $this->fill_in_gravatar_url( $label );
	}

	/**
	 * Fills in the (translated) Gravatar.com URL and the link `rel` and `target`
	 * attributes.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $message The message to fill in. It needs to contain placeholders
	 *                         in this order:
	 *                         1. URL (default 'https://en.gravatar.com'),
	 *                         2. `rel` attribute (default 'noopener nofollow'), and
	 *                         3. `target` attribute (default '_self').
	 * @return string
	 */
	protected function fill_in_gravatar_url( $message ) {
		return \sprintf(
			$message,
			\__( 'https://en.gravatar.com/', 'avatar-privacy' ),
			$this->get_gravatar_link_rel_attribute(),
			$this->get_gravatar_link_target_attribute()
		);
	}
	/**
	 * Retrieves and filters the `rel` attribute for links to gravatar.com.
	 *
	 * @since  2.4.0
	 *
	 * @return string The result is safe for output.
	 */
	protected function get_gravatar_link_rel_attribute() {
		/**
		 * Filters the `rel` attribute for user-visible links to gravatar.com.
		 *
		 * @since 2.0.0
		 *
		 * @param string $rel Default 'noopener nofollow'.
		 */
		return \esc_attr( \apply_filters( 'avatar_privacy_gravatar_link_rel', 'noopener nofollow' ) );
	}

	/**
	 * Retrieves and filters the `target` attribute for links to gravatar.com.
	 *
	 * @since  2.4.0
	 *
	 * @return string The result is safe for output.
	 */
	protected function get_gravatar_link_target_attribute() {
		/**
		 * Filters the `target` attribute for user-visible links to gravatar.com.
		 *
		 * @since 2.0.0
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
	 *
	 * @phpstan-param PartialArguments $args
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
	 *
	 * @phpstan-param PartialArguments $args
	 */
	public function get_partial( $partial, array $args = [] ) {
		\ob_start();
		$this->print_partial( $partial, $args );
		return (string) \ob_get_clean();
	}
}
