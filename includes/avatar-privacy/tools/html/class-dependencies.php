<?php
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
 * @package mundschenk-at/avatar-privacy
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Avatar_Privacy\Tools\HTML;

/**
 * A helper class for script and style registration.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Dependencies {

	/**
	 * The minification suffix.
	 *
	 * @var string
	 */
	private $suffix;

	/**
	 * The base URL for plugin scripts and styles.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The plugin base path for scripts and styles.
	 *
	 * @since 2.5.2
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ) ? '' : '.min';
		$this->url    = \plugins_url( '', \AVATAR_PRIVACY_PLUGIN_FILE );
		$this->path   = \AVATAR_PRIVACY_PLUGIN_PATH;
	}

	/**
	 * Registers a script for the block editor (using the generated asset file).
	 *
	 * @param  string $handle The name of the script. Should be unique.
	 * @param  string $block  The path of the script relative to the plugin's
	 *                        directory, but without the file extension.
	 *
	 * @return bool           Whether the script has been registered. True on
	 *                        success, false on failure.
	 */
	public function register_block_script( $handle, $block ) {
		// Load meta information.
		$asset = include "{$this->path}/{$block}.asset.php";

		// Register script.
		return \wp_register_script( $handle, "{$this->url}/{$block}.js", $asset['dependencies'], $asset['version'], false );
	}

	/**
	 * Registers the script (does not overwrite).
	 *
	 * @param  string      $handle    The name of the script. Should be unique.
	 * @param  string      $src       The path of the script relative to the
	 *                                plugin's directory.
	 * @param  string[]    $deps      Optional. An array of registered script
	 *                                handles this script depends on. Default [].
	 * @param  string|bool $version   Optional. A string specifying the script
	 *                                version number, if it has one, which is added
	 *                                to the URL as a query string for cache busting
	 *                                purposes. If $version is set to false, the
	 *                                script file's modification time is used
	 *                                automatically. If set to null, no version
	 *                                is added.
	 * @param  bool        $in_footer Optional. Whether to enqueue the script before
	 *                                </body> instead of in the <head>. Default `false`.
	 *
	 * @return bool                   Whether the script has been registered. True
	 *                                on success, false on failure.
	 */
	public function register_script( $handle, $src, $deps = [], $version = false, $in_footer = false ) {
		// Use minified versions where appropriate.
		$src = $this->maybe_add_minification_suffix( $src );

		// Use file modification time as version.
		$version = $this->maybe_add_file_modification_version( $version, $src );

		// Register script.
		return \wp_register_script( $handle, "{$this->url}/{$src}", $deps, $version, $in_footer );
	}

	/**
	 * Registers a CSS stylesheet.
	 *
	 * @param  string      $handle  The name of the script. Should be unique.
	 * @param  string      $src     The path of the script relative to the plugin's
	 *                              directory.
	 * @param  string[]    $deps    Optional. An array of registered script
	 *                              handles this script depends on. Default [].
	 * @param  string|bool $version Optional. A string specifying the script version
	 *                              number, if it has one, which is added to the
	 *                              URL as a query string for cache busting purposes.
	 *                              If $version is set to false, the style file's
	 *                              modification time is used automatically. If
	 *                              set to null, no version is added.
	 * @param  string      $media   Optional. The media for which this stylesheet
	 *                              has been defined. Accepts media types like 'all',
	 *                              'print' and 'screen', or media queries like
	 *                              '(orientation: portrait)' and '(max-width: 640px)'.
	 *                              Default 'all'.
	 *
	 * @return bool                 xxx
	 */
	public function register_style( $handle, $src, $deps = [], $version = false, $media = 'all' ) {
		// Use minified versions where appropriate.
		$src = $this->maybe_add_minification_suffix( $src );

		// Use file modification time as version.
		$version = $this->maybe_add_file_modification_version( $version, $src );

		// Register style.
		return \wp_register_style( $handle, "{$this->url}/{$src}", $deps, $version, $media );
	}

	/**
	 * Enqueues the script specified by the handle.
	 *
	 * @param  string $handle A registered script handle.
	 *
	 * @return void
	 */
	public function enqueue_script( $handle ) {
		$key = \preg_replace( [ '/^avatar-privacy-/', '/-/' ], [ '', '_' ], $handle );

		/**
		 * Filters whether to enqueue the script.
		 *
		 * The $key part will be the script handle without the plugin prefix
		 * and with all '-' replaced  with '_'.
		 *
		 * @since 2.4.0
		 *
		 * @param bool $allow Optional. Default true.
		 */
		if ( \apply_filters( "avatar_privacy_enqueue_script_{$key}", true ) ) {
			\wp_enqueue_script( $handle );
		}
	}

	/**
	 * Enqueues the stylesheet specified by the handle.
	 *
	 * @param  string $handle A registered stylesheet handle.
	 *
	 * @return void
	 */
	public function enqueue_style( $handle ) {
		$key = \preg_replace( [ '/^avatar-privacy-/', '/-style$/', '/-/' ], [ '', '', '_' ], $handle );

		/**
		 * Filters whether to enqueue the stylesheet.
		 *
		 * The $key part will be the stylesheet handle without the plugin prefix
		 * or '-style' suffix and with all '-' replaced  with '_'.
		 *
		 * @since 2.4.0
		 *
		 * @param bool $allow Optional. Default true.
		 */
		if ( \apply_filters( "avatar_privacy_enqueue_style_{$key}", true ) ) {
			\wp_enqueue_style( $handle );
		}
	}

	/**
	 * Adds the minification suffix to a file path (if appropriate).
	 *
	 * @param  string $src The path of a file relative to the plugin's directory.
	 *
	 * @return string
	 */
	protected function maybe_add_minification_suffix( $src ) {
		if ( ! empty( $this->suffix ) && ! empty( $src ) ) {
			$i   = \pathinfo( $src );
			$ext = ! empty( $i['extension'] ) ? ".{$i['extension']}" : '';
			$src = "{$i['dirname']}/{$i['filename']}{$this->suffix}{$ext}";
		}

		return $src;
	}

	/**
	 * Adds the file modification time as a version number if necessary.
	 *
	 * @param  string|bool|null $version A string specifying a version number If
	 *                                   version is set to false, the file's
	 *                                   modification time is used automatically.
	 *                                   If set to null, no version is added.
	 * @param  string           $src     The path of a file relative to the plugin's
	 *                                   directory.
	 *
	 * @return string|bool|null
	 */
	protected function maybe_add_file_modification_version( $version, $src ) {
		$full_src_path = "{$this->path}/{$src}";

		if ( false === $version && ! empty( $src ) && \file_exists( $full_src_path ) ) {
			$version = (string) @\filemtime( $full_src_path );
			$version = empty( $version ) ? false : $version;
		}

		return $version;
	}
}
