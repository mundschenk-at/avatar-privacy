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

namespace Avatar_Privacy\Avatar_Handlers;

use Avatar_Privacy\Core;

use Avatar_Privacy\Avatar_Handlers\Avatar_Handler;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;

use Avatar_Privacy\Tools\Images;
use Avatar_Privacy\Tools\Network\Gravatar_Service;

/**
 * Handles retrieving and caching Gravatar.com images.
 *
 * @since 1.0.0
 * @since 2.0.0 Class was moved to Avatar_Privacy\Avatar_Handlers and now implements the new Avatar_Handler interface.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Gravatar_Cache_Handler implements Avatar_Handler {

	const TYPE_USER    = 'user';
	const TYPE_COMMENT = 'comment';

	const TYPE_MAPPING = [
		'0' => self::TYPE_USER,
		'1' => self::TYPE_COMMENT,
		'2' => self::TYPE_USER,
		'3' => self::TYPE_COMMENT,
		'4' => self::TYPE_USER,
		'5' => self::TYPE_COMMENT,
		'6' => self::TYPE_USER,
		'7' => self::TYPE_COMMENT,
		'8' => self::TYPE_USER,
		'9' => self::TYPE_COMMENT,
		'a' => self::TYPE_USER,
		'b' => self::TYPE_COMMENT,
		'c' => self::TYPE_USER,
		'd' => self::TYPE_COMMENT,
		'e' => self::TYPE_USER,
		'f' => self::TYPE_COMMENT,
	];

	const REVERSE_TYPE_MAPPING = [
		true  => 'a', // TYPE_USER.
		false => 'b', // TYPE_COMMENT.
	];

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The filesystem cache handler.
	 *
	 * @var Filesystem_Cache
	 */
	private $file_cache;

	/**
	 * A copy of Gravatar_Cache_Handler::TYPE_MAPPING.
	 *
	 * @var string[]
	 */
	private $type_mapping;

	/**
	 * The Gravatar network service.
	 *
	 * @var Gravatar_Service
	 */
	private $gravatar;

	/**
	 * Creates a new instance.
	 *
	 * @since 2.0.0 Parameter $gravatar added.
	 *
	 * @param Core             $core        The core API.
	 * @param Options          $options     The options handler.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 * @param Gravatar_Service $gravatar    The Gravatar network service.
	 */
	public function __construct( Core $core, Options $options, Filesystem_Cache $file_cache, Gravatar_Service $gravatar ) {
		$this->core       = $core;
		$this->options    = $options;
		$this->file_cache = $file_cache;
		$this->gravatar   = $gravatar;

		// Needed for PHP 5.6 compatiblity.
		$this->type_mapping = self::TYPE_MAPPING;
	}

	/**
	 * Retrieves the default icon.
	 *
	 * @param  string $url   The fallback default icon URL.
	 * @param  string $hash  The hashed mail address.
	 * @param  int    $size  The size of the avatar image in pixels.
	 * @param  array  $args {
	 *     An array of arguments.
	 *
	 *     @type int|false $user_id  A WordPress user ID (or false).
	 *     @type string    $email    The mail address used to generate the identity hash.
	 *     @type string    $rating   The audience rating (e.g. 'g', 'pg', 'r', 'x').
	 *     @type string    $mimetype The expected MIME type of the Gravatar image.
	 * }
	 *
	 * @return string
	 */
	public function get_url( $url, $hash, $size, array $args ) {
		$defaults = [
			'user_id'  => false,
			'email'    => '',
			'rating'   => 'g',
			'mimetype' => Images\Type::PNG_IMAGE,
			'force'    => false,
		];

		$args     = \wp_parse_args( $args, $defaults );
		$subdir   = $this->get_sub_dir( $hash, false !== $args['user_id'] );
		$filename = "gravatar/{$subdir}/{$hash}-{$size}." . Images\Type::FILE_EXTENSION[ $args['mimetype'] ];

		// Only retrieve new Gravatar if necessary.
		if ( $args['force'] || ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) ) {
			// Retrieve the gravatar icon.
			$icon = $this->gravatar->get_image( $args['email'], $size, $args['rating'] );

			// Store it (empty files will fail this check).
			if ( ! $this->file_cache->set( $filename, $icon, $args['force'] ) ) {
				// Something went wrong..
				return $url;
			}
		}

		return $this->file_cache->get_url( $filename );
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @since 2.1.0 Visibility changed to protected.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  bool   $user     If we need to encode the type as "user".
	 *
	 * @return string
	 */
	protected function get_sub_dir( $identity, $user = false ) {
		$first  = \substr( $identity, 0, 1 );
		$second = \substr( $identity, 1, 1 );

		if ( ( $user && self::TYPE_USER === self::TYPE_MAPPING[ $first ] ) || ( ! $user && self::TYPE_COMMENT === self::TYPE_MAPPING[ $first ] ) ) {
			$levels = [ $first, $second ];
		} else {
			$levels = [ self::REVERSE_TYPE_MAPPING[ $user ], $second ];
		}

		return \implode( '/', $levels );
	}

	/**
	 * Caches the image specified by the parameters.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $type      The image (sub-)type. Ignored.
	 * @param  string $hash      The hashed mail address.
	 * @param  int    $size      The requested size in pixels.
	 * @param  string $subdir    The requested sub-directory.
	 * @param  string $extension The requested file extension.
	 *
	 * @return bool              Returns `true` if successful, `false` otherwise.
	 */
	public function cache_image( $type, $hash, $size, $subdir, $extension ) {
		// Determine hash type.
		$type = \explode( '/', $subdir )[0];
		if ( empty( $type ) || ! isset( $this->type_mapping[ $type ] ) ) {
			return false;
		}

		// Lookup user and/or email address.
		$user_id = false;
		if ( self::TYPE_USER === $this->type_mapping[ $type ] ) {
			$user = $this->core->get_user_by_hash( $hash );
			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
				$email   = ! empty( $user->user_email ) ? $user->user_email : '';
			}
		} else {
			$email = $this->core->get_comment_author_email( $hash );
		}

		// Could not find user/comment author.
		if ( empty( $email ) ) {
			return false;
		}

		// Prepare arguments.
		$args = [
			'user_id'  => $user_id,
			'email'    => $email,
			'rating'   => $this->options->get( 'avatar_rating', 'g', true ),
			'mimetype' => Images\Type::CONTENT_TYPE[ $extension ],
		];

		// Try to cache the icon.
		return ! empty( $this->get_url( '', $hash, $size, $args ) );
	}

	/**
	 * Retrieves the name of the cache subdirectory for avatars provided by this
	 * handler (e.g. 'gravatar'). Implementations may return an empty string if
	 * the actual type can vary.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_type() {
		return 'gravatar';
	}
}
