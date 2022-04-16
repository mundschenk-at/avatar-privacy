<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2021 Peter Putzer.
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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

// phpcs:disable WordPress.NamingConventions, Squiz.Commenting.ClassComment.Missing, Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting.FunctionComment.InvalidNoReturn

// PHP 8.0 internal classes.
namespace {
	if ( \version_compare( \PHP_VERSION, '8.0.0', '<' ) ) {
		class GdImage {}
	}
}

// WP User Manager stubs.
namespace Carbon_Fields\Field {
	class Field {
		/**
		 * Stub for WP User Manager.
		 *
		 * @return string
		 */
		public function get_base_name() {}
	}
}

// Theme My login stubs.
namespace {
	class Theme_My_Login_Form {
		/**
		 * Theme My Login stub.
		 *
		 * @return Theme_My_Login_Form_Field
		 */
		public function getField() {}

		/**
		 * Theme My Login stub.
		 *
		 * @param  string $field The field name.
		 *
		 * @return Theme_My_Login_Form_Field|bool
		 */
		public function get_field( $field ) {}

		/**
		 * Theme My Login stub.
		 *
		 * @param string $key   The attribute key.
		 * @param string $value The attribute value.
		 *
		 * @return void
		 */
		public function add_attribute( $key, $value = null ) {}
	}

	class Theme_My_Login_Form_Field {
		/**
		 * Theme My Login stub.
		 *
		 * @param string|callable $content The field content or a callable function to generate it.
		 *
		 * @return void
		 */
		public function set_content( $content = '' ) {}
	}
}

// wpDiscuz.
namespace wpdFormAttr {
	class Form {
		/**
		 * Stub for wpDiscuz
		 *
		 * @return void
		 */
		public function initFormFields() {}

		/**
		 * Stub for wpDiscuz
		 *
		 * @return array
		 */
		public function getFormCustomFields() {
			return [];
		}
	}
}

namespace wpdFormAttr\Field {
	class CookiesConsent {}
}

// Simple Author Box.
namespace {

	class Simple_Author_Box {
		/**
		 * Stub for Simple Author Box.
		 *
		 * @return Simple_Author_Box
		 */
		public static function get_instance() {}

		/**
		 * Stub for Simple Author Box.
		 *
		 * @param string $avatar      HTML for the user's avatar.
		 * @param mixed  $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
		 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
		 * @param int    $size        Square avatar width and height in pixels to retrieve.
		 * @param string $default     URL for the default image or a default type. Accepts '404', 'retro', 'monsterid',
		 *                            'wavatar', 'indenticon', 'mystery', 'mm', 'mysteryman', 'blank', or 'gravatar_default'.
		 *                            Default is the value of the 'avatar_default' option, with a fallback of 'mystery'.
		 * @param string $alt         Alternative text to use in the avatar image tag. Default empty.
		 * @param array  $args        Arguments passed to get_avatar_data(), after processing.
		 *
		 * @return string|false
		 */
		public function replace_gravatar_image( $avatar, $id_or_email, $size, $default, $alt, $args = [] ) {}
	}
}

// Simple Local Avatars.
namespace {

	class Simple_Local_Avatars {
		/**
		 * Stub for Simple Local Avatars.
		 *
		 * @param array $args        Arguments passed to get_avatar_data(), after processing.
		 * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
		 *                           user email, WP_User object, WP_Post object, or WP_Comment object.
		 */
		public static function get_avatar_data( $args, $id_or_email ) {}
	}
}

// Simple User Avatar.
namespace {

	class SimpleUserAvatar_Public {
		/**
		 * Stub for Simple Author Box.
		 */
		public static function init() {}
	}
}
