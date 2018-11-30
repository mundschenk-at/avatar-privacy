<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
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

namespace Avatar_Privacy\Avatar_Handlers\Default_Icons;

use Avatar_Privacy\Core;
use Avatar_Privacy\Settings;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

use Avatar_Privacy\Tools\Images;

use Avatar_Privacy\Upload_Handlers\Custom_Default_Icon_Upload_Handler as Upload;

/**
 * An icon provider for uploaded custom default icons.
 *
 * @since 2.0.0
 * @since 2.0.0 Moved to Avatar_Privacy\Avatar_Handlers\Default_Icons
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Custom_Icon_Provider extends Abstract_Icon_Provider {

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * The upload handler.
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Creates a new instance.
	 *
	 * @param Filesystem_Cache $file_cache The file cache handler.
	 * @param Upload           $upload     The upload handler.
	 * @param Core             $core       The plugin instance.
	 */
	public function __construct( Filesystem_Cache $file_cache, Upload $upload, Core $core ) {
		parent::__construct( [ 'custom' ], __( 'Custom', 'avatar-privacy' ) );

		$this->file_cache = $file_cache;
		$this->upload     = $upload;
		$this->core       = $core;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $identity The identity (mail address) hash. Ignored.
	 * @param  int    $size     The requested size in pixels.
	 *
	 * @return string
	 */
	public function get_icon_url( $identity, $size ) {
		// Abort if no custom image has been set.
		$default  = \includes_url( 'images/blank.gif' );
		$settings = $this->core->get_settings();
		$icon     = ! empty( $settings[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] ) ? $settings[ Settings::UPLOAD_CUSTOM_DEFAULT_AVATAR ] : [];
		if ( empty( $icon['file'] ) ) {
			return $default;
		}

		// We need the current site ID.
		$site_id   = \get_current_blog_id();
		$extension = Images\Type::FILE_EXTENSION[ $icon['type'] ];
		$identity  = $this->core->get_hash( "custom-default-{$site_id}" );
		$filename  = "custom/{$site_id}/{$identity}-{$size}.{$extension}";

		// Only generate a new icon if necessary.
		if ( ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) ) {

			$data = Images\Editor::get_resized_image_data( Images\Editor::get_image_editor( $icon['file'] ), $size, $size, true, $icon['type'] );
			if ( empty( $data ) ) {
				// Something went wrong..
				return $default;
			}

			// Save the generated image file.
			$this->file_cache->set( $filename, $data );
		}

		return $this->file_cache->get_url( $filename );
	}
}
