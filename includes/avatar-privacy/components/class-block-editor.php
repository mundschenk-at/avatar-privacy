<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2024 Peter Putzer.
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
use Avatar_Privacy\Tools\Template;
use Avatar_Privacy\Tools\HTML\Dependencies;
use Avatar_Privacy\Tools\HTML\User_Form;

/**
 * The component providing our Gutenberg blocks.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type FormBlockAttributes array{ avatar_size: int, className: string }
 * @phpstan-type AvatarBlockAttributes array{ user_id: int, avatar_size: int, className: string, align?: string }
 */
class Block_Editor implements Component {

	/**
	 * The script & style registration helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Dependencies
	 */
	private Dependencies $dependencies;

	/**
	 * The template helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * The profile form helper.
	 *
	 * @var User_Form
	 */
	private User_Form $form;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Dependencies $dependencies The script & style registration helper.
	 * @param Template     $template     The template helper.
	 * @param User_Form    $form         The profile form helper.
	 */
	public function __construct( Dependencies $dependencies, Template $template, User_Form $form ) {
		$this->dependencies = $dependencies;
		$this->template     = $template;
		$this->form         = $form;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! \function_exists( 'register_block_type' ) ) {
			// Block editor not installed.
			return;
		}

		// Initialize shortcodes after WordPress has loaded.
		\add_action( 'init', [ $this, 'register_blocks' ] );

		// Only process forms on the frontend.
		if ( ! \is_admin() ) {
			$this->form->register_form_submission();
		}
	}

	/**
	 * Registers the Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Register the script containing all our block types.
		$this->dependencies->register_block_script( 'avatar-privacy-gutenberg', 'admin/blocks/js/blocks' );

		// Register the stylesheet for the blocks.
		$this->dependencies->register_style( 'avatar-privacy-gutenberg-style', 'admin/css/blocks.css' );

		// Register each individual block type:
		// The frontend form block.
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

		// The avatar block.
		\register_block_type(
			'avatar-privacy/avatar',
			[
				'editor_script'   => 'avatar-privacy-gutenberg',
				'editor_style'    => 'avatar-privacy-gutenberg-style',
				'render_callback' => [ $this, 'render_avatar' ],
				'attributes'      => [
					'avatar_size' => [
						'type'    => 'integer',
						'default' => 96,
					],
					'user_id'     => [
						'type'    => 'integer',
						'default' => 0,
					],
					'align'       => [
						'type'    => 'string',
						'default' => '',
					],
					'className'   => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);

		// Enable i18n.
		\wp_set_script_translations( 'avatar-privacy-gutenberg', 'avatar-privacy' );
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
	 *
	 * @phpstan-param FormBlockAttributes $attributes
	 */
	public function render_frontend_form( array $attributes ) {
		$user_id = \get_current_user_id();

		// User not logged in.
		if ( empty( $user_id ) ) {
			return '';
		}

		// Include partial.
		$markup = $this->form->get_form( 'public/partials/block/frontend-form.php', $user_id, [ 'attributes' => $attributes ] );

		// As an additional precaution, remove some data if we are in preview mode.
		if ( ! empty( $attributes['preview'] ) ) {
			// Remove nonces and other hidden fields.
			$markup = (string) \preg_replace( '/<input[^>]+type=["\']hidden[^>]+>/Si', '', $markup );

			// Also remove links.
			$markup = (string) \preg_replace( '/(<a[^>]+)href=("[^"]*"|\'[^\']*\')([^>]+>)/Si', '$1$3', $markup );
		}

		return $markup;
	}

	/**
	 * Renders the avatar block.
	 *
	 * @param  array $attributes {
	 *     The `avatar-privacy/avatar` block attributes.
	 *
	 *     @type int     $user_id     The ID of the user whose avatar should be displayed.
	 *     @type int     $avatar_size The width/height of the avatar preview image (in pixels).
	 *     @type string  $className   The additional classname defined in the Block Editor.
	 *     @type ?string $align       Optional. The alignment for the avatar image.
	 * }
	 *
	 * @return string
	 *
	 * @phpstan-param AvatarBlockAttributes $attributes
	 */
	public function render_avatar( array $attributes ) {
		// Attempt to retrieve user.
		$user = \get_user_by( 'ID', $attributes['user_id'] );

		// No valid user given.
		if ( empty( $user ) ) {
			return '';
		}

		// Set up variables used by the included partial.
		$args = [
			'user'       => $user,
			'size'       => $attributes['avatar_size'],
			'class_name' => $attributes['className'],
			'align'      => ! empty( $attributes['align'] ) ? "align{$attributes['align']}" : '',
		];

		// Include partial.
		return $this->template->get_partial( 'public/partials/block/avatar.php', $args );
	}
}
