<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
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

namespace Avatar_Privacy\Tools\Network;

use Avatar_Privacy\Data_Storage\Transients;
use Avatar_Privacy\Data_Storage\Site_Transients;
use Avatar_Privacy\Tools\Images\Editor;

use Mundschenk\Data_Storage\Transients as Transients_Helper;

/**
 * A class for accessing the Gravatar service.
 *
 * @since      1.2.0
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Gravatar_Service {

	/**
	 * A cache for the results of the validate method.
	 *
	 * @var string[]
	 */
	private $validation_cache = [];

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
	 * The image editor.
	 *
	 * @since 2.3.0
	 *
	 * @var Editor
	 */
	private $editor;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.3.0 Parameter $editor added.
	 *
	 * @param Transients      $transients       The transients handler.
	 * @param Site_Transients $site_transients  The site transients handler.
	 * @param Editor          $editor           The image editor.
	 */
	public function __construct( Transients $transients, Site_Transients $site_transients, Editor $editor ) {
		$this->transients      = $transients;
		$this->site_transients = $site_transients;
		$this->editor          = $editor;
	}

	/**
	 * Retrieves the gravatar image for a given e-mail address.
	 *
	 * @param  string $email  The e-mail address.
	 * @param  int    $size   The size in pixels.
	 * @param  string $rating The audience rating.
	 *
	 * @return string         The image data.
	 */
	public function get_image( $email, $size, $rating ) {
		$image = \wp_remote_retrieve_body(
			/* @scrutinizer ignore-type */
			\wp_remote_get( $this->get_url( $email, $size, $rating ) )
		);

		if ( false === $this->editor->get_mime_type( $image ) ) {
			// Propably a Varnish error, so we ignore the data.
			$image = '';
		}

		return $image;
	}

	/**
	 * Constructs the Gravatar.com service URL from the given parameters.
	 *
	 * @param  string $email  The email address.
	 * @param  int    $size   Optional. The size in pixels. Default 80 (the same as Gravatar.com).
	 * @param  string $rating Optional. Either 'g', 'pg', 'r', or 'x'. Default 'x' (to allow any image).
	 *
	 * @return string
	 */
	public function get_url( $email, $size = 80, $rating = 'x' ) {
		$args = [
			'd' => '404', // We are never interested in gravatar default images.
			's' => empty( $size ) ? '' : $size,
			'r' => $rating,
		];

		return \esc_url_raw( \add_query_arg( $args, "https://secure.gravatar.com/avatar/{$this->get_hash( $email )}" ) );
	}

	/**
	 * Creates a hash from the given mail address using the SHA-256 algorithm.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  string $email An email address.
	 *
	 * @return string
	 */
	protected function get_hash( $email ) {
		return \md5( \strtolower( \trim( $email ) ) );
	}

	/**
	 * Checks if a gravatar exists for the given e-mail address and returns the
	 * MIME type of the image if successful.
	 *
	 * Function originally taken from: http://codex.wordpress.org/Using_Gravatars.
	 *
	 * @param  string $email    The e-mail address to check.
	 * @param  int    $age      Optional. The age of the object associated with the e-mail address. Default 0.
	 *
	 * @return string           Returns the MIME type if a gravatar exists for the given e-mail address,
	 *                          '' otherwise. This includes situations where Gravatar.com could not be
	 *                          reached, or answered with a different error code, or if no e-mail address
	 *                          was given.
	 */
	public function validate( $email = '', $age = 0 ) {
		// Make sure we have a real address to check.
		if ( empty( $email ) ) {
			return '';
		}

		// Calculate the hash of the e-mail address.
		$hash = $this->get_hash( $email );

		// Try to find something in the cache.
		if ( isset( $this->validation_cache[ $hash ] ) ) {
			return $this->validation_cache[ $hash ];
		}

		// Try to find it via transient cache. On multisite, we use site transients.
		return $this->validate_and_cache( \is_multisite() ? $this->site_transients : $this->transients, $email, $hash, $age );
	}

	/**
	 * Checks if a gravatar exists for the given e-mail address and cachces the
	 * result.
	 *
	 * @since  2.4.0 Extracted from ::validate
	 *
	 * @param  Transients_Helper $transients The transients API helper to use.
	 * @param  string            $email      The e-mail address to check.
	 * @param  string            $hash       The hashed e-mail address.
	 * @param  int               $age        The age of the object associated with
	 *                                       the e-mail address.
	 *
	 * @return string                        Returns the image MIME type if successful,
	 *                                       or the empty string otherwise.
	 */
	protected function validate_and_cache( Transients_Helper $transients, $email, $hash, $age ) {
		$transient_key = "check_{$hash}";
		$result        = $transients->get( $transient_key );
		if ( false !== $result ) {
			// Warm 1st level cache.
			$this->validation_cache[ $hash ] = $result;
			return $result;
		}

		// Ask gravatar.com.
		$result = $this->ping_gravatar( $email );
		if ( false !== $result ) {
			// Cache result.
			$transients->set( $transient_key, $result, $this->calculate_caching_duration( $result, $age ) );
			$this->validation_cache[ $hash ] = $result;
		}

		return $result ?: '';
	}

	/**
	 * Pings Gravatar.com to check if there is an image for the given hash.
	 *
	 * @since  2.1.0  Visibility changed to protected.
	 * @since  2.4.0  Returns '' instead of 0 when no Gravatar can be found.
	 *
	 * @param  string $email The e-mail address to check.
	 *
	 * @return string|false  Returns the image MIME type if successful, the empty
	 *                       string there is no Gravatar for the e-mail address,
	 *                       and `false` in case of an error.
	 */
	protected function ping_gravatar( $email ) {
		// Ask gravatar.com.
		$response = \wp_remote_head( $this->get_url( $email ) );
		if ( $response instanceof \WP_Error ) {
			return false; // Don't cache the result.
		}

		switch ( \wp_remote_retrieve_response_code( $response ) ) {
			case 200:
				// Valid image found.
				$result = \wp_remote_retrieve_header( $response, 'content-type' );
				break;

			case 404:
				// No image found.
				$result = '';
				break;

			default:
				$result = false; // Don't cache the result.
		}

		return $result;
	}

	/**
	 * Calculates the proper caching duration.
	 *
	 * @since  2.1.0  Visibility changed to protected.
	 * @since  2.4.0  The type hint of $result has been adjusted according the
	 *                simplified return value of `::ping_gravatar`.
	 *
	 * @param  string|false $result The result of the validation check.
	 * @param  int          $age    The "age" (difference between now and the
	 *                              creation date) of a comment or post (in seconds).
	 *
	 * @return int
	 */
	protected function calculate_caching_duration( $result, $age ) {
		// Cache the result across all blogs (a YES for 1 week, a NO for 10 minutes
		// or longer, depending on the age of the object (comment, post), since a
		// YES basically shouldn't change, but a NO might change when the user
		// signs up with gravatar.com).
		if ( ! empty( $result ) || $age > \WEEK_IN_SECONDS ) {
			$duration = \WEEK_IN_SECONDS;
		} elseif ( $age > \DAY_IN_SECONDS ) {
			$duration = \DAY_IN_SECONDS;
		} elseif ( $age > \HOUR_IN_SECONDS ) {
			$duration = \HOUR_IN_SECONDS;
		} else {
			$duration = 10 * \MINUTE_IN_SECONDS;
		}

		/**
		 * Filters the interval between gravatar validation checks.
		 *
		 * @param int  $duration The validation interval. Default 1 week if the check was successful, less if not.
		 * @param bool $result   The result of the validation check.
		 * @param int  $age      The "age" (difference between now and the creation date) of a comment or post (in sceonds).
		 */
		return \apply_filters( 'avatar_privacy_validate_gravatar_interval', $duration, ! empty( $result ), $age );
	}
}
