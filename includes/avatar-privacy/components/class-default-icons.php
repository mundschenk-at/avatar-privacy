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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Default_Icons\Icon_Provider;
use Avatar_Privacy\Default_Icons\Retro_Icon_Provider;
use Avatar_Privacy\Default_Icons\Rings_Icon_Provider;
use Avatar_Privacy\Default_Icons\Static_Icon_Provider;
use Avatar_Privacy\Default_Icons\SVG_Icon_Provider;

/**
 * Handles the various default icon providers.
 *
 * @since 1.0.0
 */
class Default_Icons implements \Avatar_Privacy\Component {
	const MYSTERY          = 'mystery';
	const COMMENT_BUBBLE   = 'comment';
	const SHADED_CONE      = 'im-user-offline';
	const BLACK_SILHOUETTE = 'view-media-artist';

	const STATIC_ICONS = [
		self::COMMENT_BUBBLE   => self::COMMENT_BUBBLE,
		self::SHADED_CONE      => self::SHADED_CONE,
		self::BLACK_SILHOUETTE => self::BLACK_SILHOUETTE,
	];

	const SVG_ICONS = [
		self::MYSTERY => [
			'mystery',
			'mystery-man',
			'mm',
		],
	];

	/**
	 * The full path to the main plugin file.
	 *
	 * @var   string
	 */
	private $plugin_file;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * The site transients handler.
	 *
	 * @var Site_Transients
	 */
	private $site_transients;

	/**
	 * The file system caching handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * A list of icon providers.
	 *
	 * @var Icon_Provider[]
	 */
	private $icon_providers = [];

	/**
	 * Creates a new Setup instance.
	 *
	 * @param Transients       $transients      The transients handler.
	 * @param Site_Transients  $site_transients The site transients handler.
	 * @param Options          $options         The options handler.
	 * @param Filesystem_Cache $file_cache      The filesystem cache handler.
	 */
	public function __construct( Transients $transients, Site_Transients $site_transients, Options $options, Filesystem_Cache $file_cache ) {
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->file_cache      = $file_cache;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		foreach ( self::STATIC_ICONS as $file => $types ) {
			$this->icon_providers[] = new Static_Icon_Provider( $types, $file, $core->get_plugin_file() );
		}
		foreach ( self::SVG_ICONS as $file => $types ) {
			$this->icon_providers[] = new SVG_Icon_Provider( $types, $file, $core->get_plugin_file() );
		}

		$this->icon_providers[] = new Retro_Icon_Provider( $this->file_cache );
		$this->icon_providers[] = new Rings_Icon_Provider( $this->file_cache );
		\add_filter( 'avatar_privacy_default_icon_url', [ $this, 'default_icon_url' ], 10, 4 );
	}

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @param  string $url   The fallback default icon URL.
	 * @param  string $email The mail address used to generate the identity hash.
	 * @param  string $type  The default icon type.
	 * @param  string $size  The requested size in pixels.
	 *
	 * @return string
	 */
	public function default_icon_url( $url, $email, $type, $size ) {
		foreach ( $this->icon_providers as $provider ) {
			if ( $provider->provides( $type ) ) {
				return $provider->get_icon_url( $provider->hash( $email ), $size );
			}
		}

		// Return the fallback default icon URL.
		return $url;
	}
}
