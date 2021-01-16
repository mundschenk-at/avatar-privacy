<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2021 Peter Putzer.
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

use Avatar_Privacy\Integrations\Plugin_Integration;
use Avatar_Privacy\Tools\HTML\User_Form;

use Theme_My_Login_Form;
use Theme_My_Login_Form_Field;
use Theme_My_Login_Profiles;

/**
 * An integration for the Theme My Login Profiles plugin.
 *
 * @since      2.3.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Theme_My_Login_Profiles_Integration implements Plugin_Integration {
	/**
	 * The form helper.
	 *
	 * @var User_Form
	 */
	private $form;

	/**
	 * Creates a new instance.
	 *
	 * @param User_Form $form The form handling helper.
	 */
	public function __construct( User_Form $form ) {
		$this->form = $form;
	}

	/**
	 * Check if the bbPress integration should be activated.
	 *
	 * @return bool
	 */
	public function check() {
		return \class_exists( Theme_My_Login_Profiles::class )
			&& \function_exists( 'tml_get_form' )
			&& \function_exists( 'tml_add_form_field' );
	}

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'init', [ $this, 'integrate_with_theme_my_login' ] );
	}

	/**
	 * Integrates with Theme My Login after all plugins have been loaded.
	 *
	 * @return void
	 */
	public function integrate_with_theme_my_login() {
		$tml_form = \tml_get_form( 'profile' );
		if ( ! $tml_form instanceof Theme_My_Login_Form ) {
			// Profiles extension not set up.
			return;
		}

		$avatar_field = $tml_form->get_field( 'avatar' );
		if ( ! $avatar_field instanceof Theme_My_Login_Form_Field ) {
			// Avatars seem to be disabled.
			return;
		}

		// Add proper encoding for uploading.
		$tml_form->add_attribute( 'enctype', 'multipart/form-data' );

		// Change render callback for avatar field.
		$avatar_field->set_content( [ $this, 'render_avatar_field' ] );

		// Add additional fields.
		\tml_add_form_field( $tml_form, 'avatar_privacy_use_gravatar', [
			'type'       => 'custom',
			'content'    => [ $this, 'render_use_gravatar_checkbox' ],
			'priority'   => 86,
		] );
		\tml_add_form_field( $tml_form, 'avatar_privacy_allow_anonymous', [
			'type'       => 'custom',
			'content'    => [ $this, 'render_allow_anonymous_checkbox' ],
			'priority'   => 86,
		] );

		// Save the added fields.
		\add_action( 'personal_options_update',  [ $this->form, 'save' ] );
	}

	/**
	 * Renders the avatar uploader for use with Theme_My_Login_Profiles.
	 *
	 * @return string
	 */
	public function render_avatar_field() {
		return $this->form->get_avatar_uploader( \get_current_user_id() );
	}

	/**
	 * Renders the "use gravatar" checkbox for use with Theme_My_Login_Profiles.
	 *
	 * @return string
	 */
	public function render_use_gravatar_checkbox() {
		return $this->form->get_use_gravatar_checkbox( \get_current_user_id() );
	}

	/**
	 * Renders the "allow anonymous" checkbox for use with Theme_My_Login_Profiles.
	 *
	 * @return string
	 */
	public function render_allow_anonymous_checkbox() {
		return $this->form->get_allow_anonymous_checkbox( \get_current_user_id() );
	}
}
