<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2020 Peter Putzer.
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

use Avatar_Privacy\Avatar_Handlers\Default_Icons\Abstract_Icon_Provider;

use Avatar_Privacy\Core\Default_Avatars;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Images\Image_File;

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
	 * The settings API.
	 *
	 * @since 2.4.0
	 *
	 * @var Default_Avatars
	 */
	private $default_avatars;

	/**
	 * The image editor support class.
	 *
	 * @var Images\Editor
	 */
	private $images;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.4.0 Parameter $default_avatars added, parameter $core removed.
	 *
	 * @param Filesystem_Cache $file_cache      The file cache handler.
	 * @param Default_Avatars  $default_avatars The custom default avatars API.
	 * @param Images\Editor    $images          The image editing handler.
	 */
	public function __construct( Filesystem_Cache $file_cache, Default_Avatars $default_avatars, Images\Editor $images ) {
		parent::__construct( [ 'custom' ] );

		$this->file_cache      = $file_cache;
		$this->default_avatars = $default_avatars;
		$this->images          = $images;
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
		$default = \includes_url( 'images/blank.gif' );
		$icon    = $this->default_avatars->get_custom_default_avatar();
		if ( empty( $icon['file'] ) ) {
			return $default;
		}

		// We need the current site ID.
		$site_id   = \get_current_blog_id();
		$extension = Image_File::FILE_EXTENSION[ $icon['type'] ];
		$identity  = $this->default_avatars->get_hash( $site_id );
		$filename  = "custom/{$site_id}/{$identity}-{$size}.{$extension}";

		// Only generate a new icon if necessary.
		if ( ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) ) {

			$data = $this->images->get_resized_image_data( $this->images->get_image_editor( $icon['file'] ), $size, $size, $icon['type'] );
			if ( empty( $data ) ) {
				// Something went wrong..
				return $default;
			}

			// Save the generated image file.
			$this->file_cache->set( $filename, $data );
		}

		return $this->file_cache->get_url( $filename );
	}

	/**
	 * Retrieves the user-visible, translated name.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_name() {
		return \__( 'Custom', 'avatar-privacy' );
	}
}
