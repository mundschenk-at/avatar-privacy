<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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
