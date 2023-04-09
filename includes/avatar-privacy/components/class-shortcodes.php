<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2023 Peter Putzer.
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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Component;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * The component providing the `[avatar-privacy-avatar-upload]` shortcode.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type FrontendFormAttributes array{ avatar_size?: int }
 */
class Shortcodes implements Component {

	/**
	 * The shortcode attributes for `[avatar-privacy-form]`.
	 *
	 * @var array<string, int>
	 */
	const FRONTEND_FORM_ATTRIBUTES = [
		'avatar_size' => 96,
	];

	/**
	 * The profile form helper.
	 *
	 * @var User_Form
	 */
	private User_Form $form;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param User_Form $form The profile form helper.
	 */
	public function __construct( User_Form $form ) {
		$this->form = $form;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Initialize shortcodes after WordPress has loaded.
		\add_action( 'init', [ $this, 'add_shortcodes' ] );

		// Only process forms on the frontend.
		if ( ! \is_admin() ) {
			$this->form->register_form_submission();
		}
	}

	/**
	 * Adds our shortcode and overrdies the WordPress caption shortcodes to allow nesting.
	 *
	 * @return void
	 */
	public function add_shortcodes() {
		// Add new media credit shortcode.
		\add_shortcode( 'avatar-privacy-form', [ $this, 'render_frontend_form_shortcode' ] );
	}

	/**
	 * Renders the frontend form shortcode to allow avatar uploads et al.
	 *
	 * Usage: `[avatar-privacy-form]`
	 *
	 * @since  2.4.0 Unused parameter $content removed.
	 *
	 * @param  array $atts {
	 *     An array of shortcode attributes.
	 *
	 *     @type int $avatar_size Optional. The width/height of the avatar preview image (in pixels). Default 96.
	 * }
	 *
	 * @return string          The HTML markup for the upload form.
	 *
	 * @phpstan-param FrontendFormAttributes $atts
	 */
	public function render_frontend_form_shortcode( $atts ) {
		$user_id = \get_current_user_id();

		// User not logged in.
		if ( empty( $user_id ) ) {
			return '';
		}

		// Set up variables used by the included partial.
		$args = [
			// Make sure that $atts really is an array, might be an empty string in some edge cases.
			'atts'    => $this->sanitize_frontend_form_attributes( empty( $atts ) ? [] : $atts ),
		];

		// Include partials.
		return $this->form->get_form( 'public/partials/shortcode/avatar-upload.php', $user_id, $args );
	}

	/**
	 * Ensures all required attributes are present and sanitized.
	 *
	 * @param  array $atts {
	 *     The `[avatar-privacy-form]` shortcode attributes.
	 *
	 *     @type int $avatar_size Optional. The width/height of the avatar preview image (in pixels). Default 96.
	 * }
	 *
	 * @return array
	 *
	 * @phpstan-param FrontendFormAttributes $atts
	 * @phpstan-return FrontendFormAttributes
	 */
	protected function sanitize_frontend_form_attributes( array $atts ) {
		// Merge default shortcode attributes.
		$atts = \shortcode_atts( self::FRONTEND_FORM_ATTRIBUTES, $atts, 'avatar-privacy-form' );

		// Sanitize attribute values.
		$atts['avatar_size'] = \absint( $atts['avatar_size'] );

		return $atts;
	}
}
