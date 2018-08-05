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

namespace Avatar_Privacy\Avatar_Handlers;

use Avatar_Privacy\Core;

use Avatar_Privacy\Components\Images;

use Avatar_Privacy\Data_Storage\Filesystem_Cache;
use Avatar_Privacy\Data_Storage\Options;

/**
 * Handles retrieving and caching Gravatar.com images.
 *
 * @since 1.0.0
 * @since 1.2.0 Class was moved to Avatar_Privacy\Avatar_Handlers and now implements the new Avatar_Handler interface.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Gravatar_Cache implements Avatar_Handler {

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
	 * A copy of Gravatar_Cache::TYPE_MAPPING.
	 *
	 * @var string[]
	 */
	private $type_mapping;

	/**
	 * Creates a new instance.
	 *
	 * @param Options          $options     The options handler.
	 * @param Filesystem_Cache $file_cache  The file cache handler.
	 */
	public function __construct( Options $options, Filesystem_Cache $file_cache ) {
		$this->options    = $options;
		$this->file_cache = $file_cache;

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
		$args = \wp_parse_args( $args, [
			'user_id'  => false,
			'email'    => '',
			'rating'   => 'g',
			'mimetype' => Images::PNG_IMAGE,
			'force'    => false,
		] );

		$subdir       = $this->get_sub_dir( $hash, false !== $args['user_id'] );
		$filename     = "gravatar/{$subdir}/{$hash}-{$size}." . Images::FILE_EXTENSION[ $args['mimetype'] ];
		$gravatar_url = "https://secure.gravatar.com/avatar/{$this->get_gravatar_hash( $args['email'] )}.png?s={$size}&r={$args['rating']}&d=404";

		// Only retrieve new Gravatar if necessary.
		if ( ! \file_exists( "{$this->file_cache->get_base_dir()}{$filename}" ) || $args['force'] ) {
			$icon = \wp_remote_retrieve_body( /* @scrutinizer ignore-type */ \wp_remote_get( $gravatar_url ) );

			// Store icon.
			if ( ! empty( $icon ) && $this->file_cache->set( $filename, $icon ) ) {
				$url = $this->file_cache->get_url( $filename );
			}
		} else {
			$url = $this->file_cache->get_url( $filename );
		}

		return $url;
	}

	/**
	 * Creates a hash from the given mail address using the SHA-256 algorithm.
	 *
	 * @param  string $email An email address.
	 *
	 * @return string
	 */
	public function get_gravatar_hash( $email ) {
		return \md5( \strtolower( \trim( $email ) ) );
	}

	/**
	 * Calculates the subdirectory from the given identity hash.
	 *
	 * @param  string $identity The identity (mail address) hash.
	 * @param  bool   $user     If we need to encode the type as "user".
	 *
	 * @return string
	 */
	private function get_sub_dir( $identity, $user = false ) {
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
	 * @since 1.2.0
	 *
	 * @param  string $type      The image (sub-)type.
	 * @param  string $hash      The hashed mail address.
	 * @param  int    $size      The requested size in pixels.
	 * @param  string $subdir    The requested sub-directory.
	 * @param  string $extension The requested file extension.
	 * @param  Core   $core      The plugin instance.
	 *
	 * @return bool              Returns `true` if successful, `false` otherwise.
	 */
	public function cache_image( $type, $hash, $size, $subdir, $extension, $core ) {
		// Determine hash type.
		$type = \explode( '/', $subdir )[0];
		if ( empty( $type ) || ! isset( $this->type_mapping[ $type ] ) ) {
			return false;
		}

		// Lookup user and/or email address.
		$user_id = false;
		if ( self::TYPE_USER === $this->type_mapping[ $type ] ) {
			$user = $core->get_user_by_hash( $hash );
			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
				$email   = ! empty( $user->user_email ) ? $user->user_email : '';
			}
		} else {
			$email = $core->get_comment_author_email( $hash );
		}

		// Could not find user/comment author.
		if ( empty( $email ) ) {
			return false;
		}

		// Try to cache the icon.
		return ! empty( $this->get_url( '', $hash, $size, [
			'user_id'  => $user_id,
			'email'    => $email,
			'rating'   => $this->options->get( 'avatar_rating', 'g', true ),
			'mimetype' => Images::CONTENT_TYPE[ $extension ],
		] ) );
	}
}
