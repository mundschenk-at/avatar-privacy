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

use Avatar_Privacy\Gravatar_Cache;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Data_Storage\Transients;

use Avatar_Privacy\Default_Icons\Icon_Provider;
use Avatar_Privacy\Default_Icons\Retro_Icon_Provider;
use Avatar_Privacy\Default_Icons\Rings_Icon_Provider;
use Avatar_Privacy\Default_Icons\Static_Icon_Provider;
use Avatar_Privacy\Default_Icons\SVG_Icon_Provider;
use Avatar_Privacy\Default_Icons\Monster_ID_Icon_Provider;
use Avatar_Privacy\Default_Icons\Wavatar_Icon_Provider;
use Avatar_Privacy\Default_Icons\Identicon_Icon_Provider;

/**
 * Handles the creation and caching of avatar images. Default icons are created by
 * Icon_Provider instances, remote Gravatar.com avatars by a Gravatar_Cache.
 *
 * @since 1.0.0
 */
class Images implements \Avatar_Privacy\Component {
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

	const CONTENT_TYPE = [
		'png' => 'image/png',
		'svg' => 'image/svg+xml',
	];

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
	 * The Gravatar.com icon provider.
	 *
	 * @var Gravatar_Cache
	 */
	private $gravatar_cache;

	/**
	 * The core API.
	 *
	 * @var \Avatar_Privacy_Core
	 */
	private $core;

	/**
	 * Creates a new instance.
	 *
	 * @param Transients       $transients      The transients handler.
	 * @param Site_Transients  $site_transients The site transients handler.
	 * @param Options          $options         The options handler.
	 * @param Filesystem_Cache $file_cache      The filesystem cache handler.
	 * @param Gravatar_Cache   $gravatar        The Gravatar.com icon provider.
	 */
	public function __construct( Transients $transients, Site_Transients $site_transients, Options $options, Filesystem_Cache $file_cache, Gravatar_Cache $gravatar ) {
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->options         = $options;
		$this->file_cache      = $file_cache;
		$this->gravatar_cache  = $gravatar;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @param \Avatar_Privacy_Core $core The plugin instance.
	 *
	 * @return void
	 */
	public function run( \Avatar_Privacy_Core $core ) {
		$this->core = $core;

		foreach ( self::STATIC_ICONS as $file => $types ) {
			$this->icon_providers[] = new Static_Icon_Provider( $types, $file, $core->get_plugin_file() );
		}
		foreach ( self::SVG_ICONS as $file => $types ) {
			$this->icon_providers[] = new SVG_Icon_Provider( $types, $file, $core->get_plugin_file() );
		}

		$this->icon_providers[] = new Retro_Icon_Provider( $this->file_cache );
		$this->icon_providers[] = new Rings_Icon_Provider( $this->file_cache );
		$this->icon_providers[] = new Monster_ID_Icon_Provider( $this->file_cache );
		$this->icon_providers[] = new Wavatar_Icon_Provider( $this->file_cache );
		$this->icon_providers[] = new Identicon_Icon_Provider( $this->file_cache );

		// Generate the correct avatar images.
		\add_filter( 'avatar_privacy_default_icon_url', [ $this, 'default_icon_url' ], 10, 4 );
		\add_filter( 'avatar_privacy_gravatar_icon_url', [ $this, 'gravatar_icon_url' ], 10, 5 );

		// Automatically regenerate missing image files.
		\add_action( 'init',          [ $this, 'add_cache_rewrite_rules' ] );
		\add_action( 'parse_request', [ $this, 'load_cached_avatar' ] );

		// Clean up cache once per day.
		\add_action( 'init', [ $this, 'enable_image_cache_cleanup' ] );
	}

	/**
	 * Add rewrite rules for nice avatar caching.
	 */
	public function add_cache_rewrite_rules() {
		/**
		 * The global WordPress instance.
		 *
		 * @var \WP
		 */
		global $wp;
		$wp->add_query_var( 'avatar-privacy-file' );

		$basedir = str_replace( ABSPATH, '', $this->file_cache->get_base_dir() );
		\add_rewrite_rule( "^{$basedir}(.*)", [ 'avatar-privacy-file' => '$matches[1]' ], 'top' );
	}

	/**
	 * Short-circuits WordPress initialization and load displays the cached avatar image.
	 *
	 * @param \WP $wp The WordPress global object.
	 */
	public function load_cached_avatar( \WP $wp ) {
		if ( ! empty( $wp->query_vars['avatar-privacy-file'] ) && preg_match( '#^([a-z]+)/(?:([a-z]+)/)?([a-f0-9]{64})(?:-([0-9]+))?\.(png|svg)$#i', $wp->query_vars['avatar-privacy-file'], $matches ) ) {
			list( , $type, $subdir, $hash, $size, $extension ) = $matches;

			$file = "{$this->file_cache->get_base_dir()}{$type}/" . ( empty( $subdir ) ? '' : "{$subdir}/" ) . $hash . ( empty( $size ) ? '' : "-{$size}" ) . ".{$extension}";

			if ( ! \file_exists( $file ) ) {
				// Default size (for SVGs mainly, which ignore it).
				$size = $size ?: 100;

				if ( 'gravatar' === $type ) {
					$success = $this->retrieve_gravatar_icon( $hash, $subdir, $size );
				} else {
					$success = $this->generate_default_icon( $hash, $type, $size );
				}

				if ( ! $success ) {
					/* translators: $file path */
					wp_die( esc_html( sprintf( __( 'Error generating avatar file %s.', 'avatar-privacy' ), $file ) ) );
				}
			}

			$this->send_image( $file, DAY_IN_SECONDS, self::CONTENT_TYPE[ $extension ] );

			// We're done.
			exit( 0 );
		}
	}

	/**
	 * Sends an image file to the browser.
	 *
	 * @param  string $file         The full path to the image.
	 * @param  int    $cache_time   The time the image should be cached by the brwoser (in seconds).
	 * @param  string $content_type The content MIME type.
	 */
	private function send_image( $file, $cache_time, $content_type ) {
		$image = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, Generic.PHP.NoSilencedErrors.Discouraged

		if ( ! empty( $image ) ) {
			// Let's set some HTTP headers.
			\header( "Content-Type: {$content_type}" );
			\header( 'Content-Length: ' . \strlen( $image ) );
			\header( 'Expires: ' . \gmdate( 'D, d M Y H:i:s \G\M\T', \time() + $cache_time ) );

			// Here comes the content.
			echo $image; // WPCS: XSS ok.
		} else {
			/* translators: $file path */
			\wp_die( \esc_html( \sprintf( \__( 'Error generating avatar file %s.', 'avatar-privacy' ), $file ) ) );
		}
	}

	/**
	 * Generates the default icon for the given hash.
	 *
	 * @param  string $hash The hashed mail address.
	 * @param  string $type The default icon type.
	 * @param  int    $size The requested size in pixels.
	 *
	 * @return bool
	 */
	private function generate_default_icon( $hash, $type, $size ) {
		foreach ( $this->icon_providers as $provider ) {
			if ( $provider->provides( $type ) ) {
				return ! empty( $provider->get_icon_url( $hash, $size ) );
			}
		}

		return false;
	}

	/**
	 * Retrieves the Gravatar.com icon for the given hash.
	 *
	 * @param  string $hash The hashed mail address (including some special information).
	 * @param  string $type The address type (`a` if linked to user, `b` if comment author).
	 * @param  int    $size The requested size in pixels.
	 *
	 * @return bool
	 */
	private function retrieve_gravatar_icon( $hash, $type, $size ) {
		if ( ! 'a' !== $type && 'b' !== $type ) {
			return false;
		}

		if ( 'a' === $type ) {
			list( $user ) = \get_users( [
				'number'       => 1,
				'meta_key'     => 'avatar_privacy_hash', // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => $hash,                 // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => '=',
			] );

			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
				$email   = ! empty( $user->user_email ) ? $user->user_email : '';
			} else {
				return false;
			}
		} else {
			$user_id = false;
			$email   = $this->core->get_comment_author_email( $hash );
		}

		// Could not find user/comment author.
		if ( empty( $email ) ) {
			return false;
		}

		// Try to cache the icon.
		return ! empty( $this->gravatar_icon_url( '', $email, $size, $user_id, /* @scrutinizer ignore-type */ $this->options->get( 'avatar_rating', 'g', true ) ) );
	}

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @param  string $url  The fallback default icon URL.
	 * @param  string $hash The hashed mail address.
	 * @param  string $type The default icon type.
	 * @param  int    $size The requested size in pixels.
	 *
	 * @return string
	 */
	public function default_icon_url( $url, $hash, $type, $size ) {
		foreach ( $this->icon_providers as $provider ) {
			if ( $provider->provides( $type ) ) {
				return $provider->get_icon_url( $hash, $size );
			}
		}

		// Return the fallback default icon URL.
		return $url;
	}

	/**
	 * Retrieves the URL for the given default icon type.
	 *
	 * @param  string    $url     The fallback default icon URL.
	 * @param  string    $email   The mail address used to generate the identity hash.
	 * @param  int       $size    The requested size in pixels.
	 * @param  int|false $user_id Optional. A WordPress user ID, or false. Default false.
	 * @param  string    $rating  Optional. The audience rating (e.g. 'g', 'pg', 'r', 'x'). Default 'g'.
	 * @param  bool      $force   Optional. Whether to force the regeneration of the icon. Default false.
	 *
	 * @return string
	 */
	public function gravatar_icon_url( $url, $email, $size, $user_id = false, $rating = 'g', $force = false ) {
		return $this->gravatar_cache->get_icon_url( $url, $email, $size, $user_id, $rating, $this->core, $force );
	}

	/**
	 * Schedules a cron job to clean up the image cache once per day. Otherwise the
	 * cache would grow unchecked and new avatar images uploaded to Gravatar.com
	 * would not be picked up.
	 */
	public function enable_image_cache_cleanup() {
		\add_action( 'avatar_privacy_daily', [ $this, 'trim_image_cache' ] );
		\wp_schedule_event( \time(), 'daily', 'avatar_privacy_daily' );
	}

	/**
	 * Deletes cached image files that are too old. Uses a site transient to ensure
	 * that the clean-up happens only once per day on multisite installations.
	 */
	public function trim_image_cache() {
		if ( ! $this->site_transients->get( 'cron_job_lock' ) ) {
			$this->file_cache->invalidate_files_older_than( 2 * DAY_IN_SECONDS );

			$this->site_transients->set( 'cron_job_lock', 'wewantprivacy', DAY_IN_SECONDS );
		}
	}
}
