<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

namespace Avatar_Privacy;

/**
 * A custom requirements class to check for additional PHP packages and other
 * prerequisites.
 *
 * @since 1.0.0
 * @since 2.3.0 Moved to \Avatar_Privacy\Requirements.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Requirements extends \Mundschenk\WP_Requirements {

	/**
	 * Creates a new requirements instance.
	 *
	 * @since 2.1.0 Parameter $plugin_file replaced with AVATAR_PRIVACY_PLUGIN_FILE constant.
	 */
	public function __construct() {
		$requirements = array(
			'php'              => '5.6.0',
			'multibyte'        => false,
			'utf-8'            => false,
			'gd'               => true,
			'uploads_writable' => true,
		);

		parent::__construct( 'Avatar Privacy', AVATAR_PRIVACY_PLUGIN_FILE, 'avatar-privacy', $requirements );
	}

	/**
	 * Retrieves an array of requirement specifications.
	 *
	 * @return array {
	 *         An array of requirements checks.
	 *
	 *   @type string   $enable_key An index in the $install_requirements array to switch the check on and off.
	 *   @type callable $check      A function returning true if the check was successful, false otherwise.
	 *   @type callable $notice     A function displaying an appropriate error notice.
	 * }
	 */
	protected function get_requirements() {
		$requirements   = parent::get_requirements();
		$requirements[] = array(
			'enable_key' => 'gd',
			'check'      => array( $this, 'check_gd_support' ),
			'notice'     => array( $this, 'admin_notices_gd_incompatible' ),
		);
		$requirements[] = array(
			'enable_key' => 'uploads_writable',
			'check'      => array( $this, 'check_uploads_writable' ),
			'notice'     => array( $this, 'admin_notices_uploads_not_writable' ),
		);

		return $requirements;
	}

	/**
	 * Checks for availability of the GD extension.
	 *
	 * @return bool
	 */
	protected function check_gd_support() {
		return function_exists( 'imagecreatefrompng' )
			&& function_exists( 'imagecopy' )
			&& function_exists( 'imagedestroy' )
			&& function_exists( 'imagepng' )
			&& function_exists( 'imagecreatetruecolor' );
	}

	/**
	 * Prints 'GD extension missing' admin notice
	 */
	public function admin_notices_gd_incompatible() {
		$this->display_error_notice(
			/* translators: 1: plugin name 2: GD documentation URL */
			__( 'The activated plugin %1$s requires the GD PHP extension to be enabled on your server. Please deactivate this plugin, or <a href="%2$s">enable the extension</a>.', 'avatar-privacy' ),
			'<strong>Avatar Privacy</strong>',
			/* translators: URL with GD PHP extension installation instructions */
			__( 'http://php.net/manual/en/image.setup.php', 'avatar-privacy' )
		);
	}

	/**
	 * Checks for availability of the GD extension.
	 *
	 * @return bool
	 */
	protected function check_uploads_writable() {
		$uploads = wp_get_upload_dir();

		return is_writable( $uploads['basedir'] );
	}

	/**
	 * Prints 'GD extension missing' admin notice
	 */
	public function admin_notices_uploads_not_writable() {
		$this->display_error_notice(
			/* translators: 1: plugin name */
			__( 'The activated plugin %1$s requires write access to the WordPress uploads folder on your server. Please check the folder\'s permissions, or deactivate this plugin.', 'avatar-privacy' ),
			'<strong>Avatar Privacy</strong>'
		);
	}
}
