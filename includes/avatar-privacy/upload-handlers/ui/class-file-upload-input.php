<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2024 Peter Putzer.
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

namespace Avatar_Privacy\Upload_Handlers\UI;

use Mundschenk\UI\Controls;

use Mundschenk\Data_Storage\Options;

/**
 * Special <input> element for file uploads.
 *
 * @since 2.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-type Input_Arguments array{
 *     tab_id: string,
 *     section: string,
 *     default: string|int,
 *     short?: ?string,
 *     label?: ?string,
 *     help_text?: ?string,
 *     inline_help?: bool,
 *     attributes?: mixed[],
 *     outer_attributes?: mixed[],
 *     settings_args?: mixed[],
 *     erase_checkbox?: string
 * }
 * @phpstan-type Prepared_Input_Arguments array{
 *     tab_id: string,
 *     section: string,
 *     default: string|int,
 *     short?: ?string,
 *     label?: ?string,
 *     help_text?: ?string,
 *     inline_help?: bool,
 *     attributes?: mixed[],
 *     outer_attributes?: mixed[],
 *     settings_args?: mixed[],
 *     erase_checkbox: string,
 *     action: string,
 *     nonce: string,
 *     help_no_file: string,
 *     help_no_upload: string
 * }
 */
class File_Upload_Input extends Controls\Input {

	/**
	 * The HTML ID of the erase image checkbox.
	 *
	 * @var string
	 */
	private string $erase_checkbox_id;

	/**
	 * The nonce prefix for the upload.
	 *
	 * @var string
	 */
	private string $nonce;

	/**
	 * The action ID for the upload.
	 *
	 * @var string
	 */
	private string $action;

	/**
	 * Create a new input control object.
	 *
	 * @param Options $options      Options API handler.
	 * @param string  $options_key  Database key for the options array.
	 * @param string  $id           Control ID (equivalent to option name). Required.
	 * @param array   $args {
	 *    Optional and required arguments.
	 *
	 *    @type string      $tab_id           Tab ID. Required.
	 *    @type string      $section          Section ID. Required.
	 *    @type string|int  $default          The default value. Required, but may be an empty string.
	 *    @type string|null $short            Optional. Short label. Default null.
	 *    @type string|null $label            Optional. Label content with the position of the control marked as %1$s. Default null.
	 *    @type string|null $help_text        Optional. Help text. Default null.
	 *    @type bool        $inline_help      Optional. Display help inline. Default false.
	 *    @type array       $attributes       Optional. Default [],
	 *    @type array       $outer_attributes Optional. Default [],
	 *    @type array       $settings_args    Optional. Default [],
	 *    @type string      $erase_checkbox   Erase image checkbox ID.
	 * }
	 *
	 * @throws \InvalidArgumentException Missing argument.
	 *
	 * @phpstan-param Input_Arguments $args
	 */
	public function __construct( Options $options, $options_key, $id, array $args ) {
		/**
		 * Check passed arguments.
		 *
		 * @phpstan-var Prepared_Input_Arguments $args
		 */
		$args               = $this->prepare_args( $args, [ 'erase_checkbox', 'action', 'nonce', 'help_no_file', 'help_no_upload' ] );
		$args['input_type'] = 'file';

		parent::__construct( $options, $options_key, $id, $args );

		if ( \current_user_can( 'upload_files' ) ) {
			$value = $this->get_value();
			if ( empty( $value ) ) {
				$this->help_text = $args['help_no_file'];
			}
		} else {
			$this->help_text = $args['help_no_upload'];
		}

		$this->erase_checkbox_id = \esc_attr( $args['erase_checkbox'] );
		$this->action            = \esc_attr( $args['action'] );
		$this->nonce             = \esc_attr( $args['nonce'] );
	}

	/**
	 * Render the value markup for this input.
	 *
	 * @param mixed $value The input value.
	 *
	 * @return string
	 */
	protected function get_value_markup( $value ): string {
		// Don't display file names.
		return 'value="" ';
	}

	/**
	 * Retrieves the control-specific HTML markup.
	 *
	 * @return string
	 */
	protected function get_element_markup(): string {
		$value           = $this->get_value();
		$checkbox_markup = '';
		$nonce_markup    = \wp_nonce_field( $this->action, $this->nonce . \get_current_blog_id(), true, false );

		if ( ! empty( $value ) ) {
			$checkbox_markup =
				"<input id=\"{$this->erase_checkbox_id}\" name=\"{$this->erase_checkbox_id}\" value=\"true\" type=\"checkbox\">
				 <label for=\"{$this->erase_checkbox_id}\">" . \__( 'Delete custom default avatar.', 'avatar-privacy' ) . '</label>';
		}

		return $nonce_markup . parent::get_element_markup() . $checkbox_markup;
	}
}
