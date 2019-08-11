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

use Avatar_Privacy\Core;
use Avatar_Privacy\Component;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * The component providing our Gutenberg blocks.
 *
 * @since 2.3.0
 */
class Block_Editor implements Component {

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The profile form helper.
	 *
	 * @var User_Form
	 */
	private $form;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Core      $core The core API.
	 * @param User_Form $form The profile form helper.
	 */
	public function __construct( Core $core, User_Form $form ) {
		$this->core = $core;
		$this->form = $form;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Initialize shortcodes after WordPress has loaded.
		\add_action( 'init', [ $this, 'register_blocks' ] );

		// Only process forms on the frontend.
		if ( ! \is_admin() ) {
			\add_action( 'init', [ $this->form, 'process_form_submission' ] );
		}
	}

	/**
	 * Registers the Gutenberg blocks.
	 */
	public function register_blocks() {
		$version    = $this->core->get_version();
		$suffix     = \SCRIPT_DEBUG ? '' : '.min';
		$plugin_dir = \plugin_dir_url( \AVATAR_PRIVACY_PLUGIN_FILE );

		// Register the script containing all our block types.
		$block_js = 'admin/blocks/js/blocks.js';
		\wp_register_script( 'avatar-privacy-gutenberg', "{$plugin_dir}{$block_js}", $this->get_dependencies( \plugin_dir_path( \AVATAR_PRIVACY_PLUGIN_FILE ) . $block_js ), $version, false );

		// Register the stylesheet for the blocks.
		\wp_register_style( 'avatar-privacy-gutenberg-style', "{$plugin_dir}admin/css/blocks{$suffix}.css", [], $version );

		// Register each individual block type.
		\register_block_type(
			'avatar-privacy/form',
			[
				'editor_script'   => 'avatar-privacy-gutenberg',
				'editor_style'    => 'avatar-privacy-gutenberg-style',
				'render_callback' => [ $this, 'render_frontend_form' ],
				'attributes'      => [
					'avatar_size'       => [
						'type'    => 'integer',
						'default' => 96,
					],
					'show_descriptions' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'className'         => [
						'type'    => 'string',
						'default' => '',
					],
					'preview'           => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);
	}

	/**
	 * Retrieves the dependencies for the given JavaScript file.
	 *
	 * @param  string $file The full path.
	 *
	 * @return string[]
	 */
	public function get_dependencies( $file ) {
		// Replace `.js` extension with `.deps.json` to find the generated dependencies file.
		$dependencies_file = \substr( $file, 0, -3 ) . '.deps.json';

		return \is_readable( $dependencies_file ) ? \json_decode( \file_get_contents( $dependencies_file ) ) : []; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Renders the frontend form.
	 *
	 * @param  array $attributes {
	 *     The `avatar-privacy/form` block attributes.
	 *
	 *     @type int    $avatar_size The width/height of the avatar preview image (in pixels).
	 *     @type string $className   The additional classname defined in the Block Editor.
	 * }
	 *
	 * @return string
	 */
	public function render_frontend_form( array $attributes ) {
		$user_id = \get_current_user_id();

		// User not logged in.
		if ( empty( $user_id ) ) {
			return '';
		}

		// Make form helper available to partial.
		$form = $this->form;

		// Include partials.
		\ob_start();
		require \dirname( AVATAR_PRIVACY_PLUGIN_FILE ) . '/public/partials/block/frontend-form.php';
		$markup = \ob_get_clean();

		// As an additional precaution, remove some data if we are in preview mode.
		if ( ! empty( $attributes['preview'] ) ) {
			// Remove nonces and other hidden fields.
			$markup = \preg_replace( '/<input[^>]+type=["\']hidden[^>]+>/Si', '', $markup );

			// Also remove links.
			$markup = \preg_replace( '/(<a[^>]+)href=("[^"]*"|\'[^\']*\')([^>]+>)/Si', '$1$3', $markup );
		}

		return $markup;
	}
}
