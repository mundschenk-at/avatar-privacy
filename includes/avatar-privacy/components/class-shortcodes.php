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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Component;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * The component providing the `[avatar-privacy-avatar-upload]` shortcode.
 *
 * @since 2.3.0
 */
class Shortcodes implements Component {

	/**
	 * The shortcode attributes for `[avatar-privacy-form]`.
	 *
	 * @var array
	 */
	const FRONTEND_FORM_ATTRIBUTES = [
		'avatar_size' => 96,
	];

	/**
	 * The profile form helper.
	 *
	 * @var User_Form
	 */
	private $form;

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
			\add_action( 'init', [ $this->form, 'process_form_submission' ] );
		}
	}

	/**
	 * Adds our shortcode and overrdies the WordPress caption shortcodes to allow nesting.
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
	 * @param  array  $atts {
	 *     An array of shortcode attributes.
	 *
	 *     @type int $avatar_size Optional. The width/height of the avatar preview image (in pixels). Default 96.
	 * }
	 * @param  string $content Optional. Shortcode content. Default null.
	 *
	 * @return string          The HTML markup for the upload form.
	 */
	public function render_frontend_form_shortcode( $atts, $content = null ) {
		$user_id = \get_current_user_id();

		// User not logged in.
		if ( empty( $user_id ) ) {
			return '';
		}

		// Make sure that $atts really is an array, might be an empty string in some edge cases.
		$atts = $this->sanitize_frontend_form_attributes( empty( $atts ) ? [] : $atts );

		// Make form helper available to partial.
		$form = $this->form;

		// Include partials.
		\ob_start();
		require \dirname( AVATAR_PRIVACY_PLUGIN_FILE ) . '/public/partials/shortcode/avatar-upload.php';
		return \ob_get_clean();
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
	 */
	protected function sanitize_frontend_form_attributes( array $atts ) {
		// Merge default shortcode attributes.
		$atts = \shortcode_atts( self::FRONTEND_FORM_ATTRIBUTES, $atts, 'avatar-privacy-form' );

		// Sanitize attribute values.
		$atts['avatar_size'] = \absint( $atts['avatar_size'] );

		return $atts;
	}
}
