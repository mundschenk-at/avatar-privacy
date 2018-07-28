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

namespace Avatar_Privacy\Default_Icons;

use Avatar_Privacy\Core;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;

/**
 * Manages the list of icon providers.
 *
 * @since 1.2.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Icon_Provider_List {
	const MYSTERY          = 'mystery';
	const COMMENT_BUBBLE   = 'comment-bubble';
	const SHADED_CONE      = 'shaded-cone';
	const BLACK_SILHOUETTE = 'silhouette';

	const SVG_ICONS = [
		self::MYSTERY          => [
			'mystery',
			'mystery-man',
			'mm',
		],
		self::COMMENT_BUBBLE   => [
			'bubble',
			'comment',
		],
		self::SHADED_CONE      => [
			'bowling-pin',
			'im-user-offline',
		],
		self::BLACK_SILHOUETTE => [
			'silhouette',
			'view-media-artist',
		],
	];

	/**
	 * A list of icon provider instances.
	 *
	 * @var Icon_Provider[]
	 */
	private static $providers = [];

	/**
	 * Retrieves a list of icon provider instances.
	 *
	 * @param  Core             $core       The plugin instance.
	 * @param  Filesystem_Cache $file_cache The filesystem cache.
	 *
	 * @return Icon_Provider[]
	 */
	public static function get( Core $core, Filesystem_Cache $file_cache ) {

		if ( empty( self::$providers ) ) {
			$plugin_file = $core->get_plugin_file();

			self::$providers = [
				new SVG_Icon_Provider( [ 'mystery', 'mystery-man', 'mm' ], 'mystery', $plugin_file ),
				new Identicon_Icon_Provider( $file_cache ),
				new Wavatar_Icon_Provider( $file_cache ),
				new Monster_ID_Icon_Provider( $file_cache ),
				new Retro_Icon_Provider( $file_cache ),
				new Rings_Icon_Provider( $file_cache ),
				new SVG_Icon_Provider( [ 'bubble', 'comment' ],               'comment-bubble', $plugin_file, __( 'Speech Bubble', 'avatar-privacy' ) ),
				new SVG_Icon_Provider( [ 'bowling-pin', 'im-user-offline' ],  'shaded-cone',    $plugin_file, __( 'Bowling Pin', 'avatar-privacy' ) ),
				new SVG_Icon_Provider( [ 'silhouette', 'view-media-artist' ], 'silhouette',     $plugin_file, __( 'Silhouette', 'avatar-privacy' ) ),
			];
		}

		return self::$providers;
	}
}
