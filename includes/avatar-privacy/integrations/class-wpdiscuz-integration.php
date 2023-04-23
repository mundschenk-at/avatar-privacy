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

namespace Avatar_Privacy\Integrations;

use Avatar_Privacy\Components\Comments;
use Avatar_Privacy\Integrations\Plugin_Integration;
use Avatar_Privacy\Tools\HTML\Dependencies;

use wpdFormAttr\Form;
use wpdFormAttr\Field;

/**
 * An integration for wpDiscuz.
 *
 * @since      2.2.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class WPDiscuz_Integration implements Plugin_Integration {

	/**
	 * The script & style registration helper.
	 *
	 * @since 2.4.0
	 *
	 * @var Dependencies
	 */
	private Dependencies $dependencies;

	/**
	 * The comment handling component.
	 *
	 * @var Comments
	 */
	private Comments $comments;

	/**
	 * The field name for the cookies consent checkbox.
	 *
	 * @var string
	 */
	private string $cookie_consent_name;

	/**
	 * Creates a new instance.
	 *
	 * @param Dependencies $dependencies The script & style registration helper.
	 * @param Comments     $comments     The comment handler.
	 */
	public function __construct( Dependencies $dependencies, Comments $comments ) {
		$this->dependencies = $dependencies;
		$this->comments     = $comments;
	}

	/**
	 * Check if the bbPress integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \function_exists( 'wpDiscuz' );
	}

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Init action handler.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! \is_user_logged_in() ) {
			if ( ! \is_admin() ) {
				\add_action( 'wpdiscuz_submit_button_before', [ $this, 'print_gravatar_checkbox' ] );
				\add_action( 'wp_enqueue_scripts',            [ $this, 'enqeue_styles_and_scripts' ] );
			}

			// Needed in AJAX calls.
			\add_action( 'wpdiscuz_form_init',               [ $this, 'store_cookie_consent_checkbox' ] );
			\add_action( 'wpdiscuz_before_save_commentmeta', [ $this, 'set_comment_cookies' ] );
		}
	}

	/**
	 * Prints the wpDiscuz "Use Gravatar" checkbox.
	 *
	 * @return void
	 */
	public function print_gravatar_checkbox() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- echoing a partial.
		echo $this->comments->get_gravatar_checkbox_markup( 'public/partials/wpdiscuz/use-gravatar.php' );
	}

	/**
	 * Enqueue stylesheet comments form.
	 *
	 * @return void
	 */
	public function enqeue_styles_and_scripts() {
		// Register script.
		$this->dependencies->register_script( 'avatar-privacy-wpdiscuz-use-gravatar', 'public/js/wpdiscuz/use-gravatar.js', [ 'jquery' ], false, true );

		// Set up the localized script data.
		$data = [
			'cookie'   => Comments::COOKIE_PREFIX . \COOKIEHASH,
			'checkbox' => Comments::CHECKBOX_FIELD_NAME,
		];
		\wp_localize_script( 'avatar-privacy-wpdiscuz-use-gravatar', 'avatarPrivacy', $data );

		// Enqueue script.
		$this->dependencies->enqueue_script( 'avatar-privacy-wpdiscuz-use-gravatar' );
	}

	/**
	 * Sets the "Use Gravatar" cookie.
	 *
	 * @param \WP_Comment $comment Comment object.
	 *
	 * @return void
	 */
	public function set_comment_cookies( \WP_Comment $comment ) {
		$user           = \wp_get_current_user();
		$cookie_consent = $this->filter_bool( \INPUT_POST, $this->cookie_consent_name );

		$this->comments->set_comment_cookies( $comment, $user, $cookie_consent );
	}

	/**
	 * Stores the wpDiscuz form fields for later use.
	 *
	 * @param  Form $form The form object.
	 *
	 * @return void
	 */
	public function store_cookie_consent_checkbox( Form $form ) {
		$form->initFormFields();

		foreach ( $form->getFormCustomFields() as $field_name => $field ) {
			if ( Field\CookiesConsent::class === $field['type'] ) {
				$this->cookie_consent_name = $field_name;
			}
		}
	}

	/**
	 * Filters boolean values in one of the input super globals to allow for
	 * unit testing.
	 *
	 * @since  2.6.0 Renamed to `filter_bool` and made more specific.
	 *
	 * @param  int    $type          One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
	 * @param  string $variable_name Name of a variable to get.
	 *
	 * @return bool
	 *
	 * @phpstan-param \INPUT_GET|\INPUT_POST|\INPUT_COOKIE|\INPUT_SERVER|\INPUT_ENV $type
	 */
	protected function filter_bool( $type, $variable_name ) {
		return true === \filter_input( $type, $variable_name, \FILTER_VALIDATE_BOOLEAN ); // @codeCoverageIgnore
	}
}
