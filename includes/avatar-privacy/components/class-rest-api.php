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

namespace Avatar_Privacy\Components;

use Avatar_Privacy\Component;

/**
 * Combines the WordPress REST API with Avatar Privacy.
 *
 * @since      1.2.0
 *
 * @author     Peter Putzer <github@mundschenk.at>
 */
class REST_API implements Component {

	/**
	 * Start up enabled integrations.
	 */
	public function run() {
		\add_filter( 'rest_prepare_user',    [ $this, 'fix_rest_user_avatars' ],           10, 2 );
		\add_filter( 'rest_prepare_comment', [ $this, 'fix_rest_comment_author_avatars' ], 10, 2 );
	}

	/**
	 * Fixes the avatar URLs in the response to distinguish between users and anonymous commenters.
	 *
	 * @param  \WP_REST_Response $response The response object.
	 * @param  \WP_User          $user     User object used to create response.
	 *
	 * @return \WP_REST_Response
	 */
	public function fix_rest_user_avatars( \WP_REST_Response $response, \WP_User $user ) {

		if ( ! empty( $response->data['avatar_urls'] ) ) {
			$response->data['avatar_urls'] = $this->rest_get_avatar_urls( $user );
		}

		return $response;
	}

	/**
	 * Fixes the avatar URLs in the response to distinguish between users and anonymous commenters.
	 *
	 * @param  \WP_REST_Response $response The response object.
	 * @param  \WP_Comment       $comment  The original comment object.
	 *
	 * @return \WP_REST_Response
	 */
	public function fix_rest_comment_author_avatars( \WP_REST_Response $response, \WP_Comment $comment ) {

		if ( ! empty( $response->data['author_avatar_urls'] ) ) {
			$response->data['author_avatar_urls'] = $this->rest_get_avatar_urls( $comment );
		}

		return $response;
	}

	/**
	 * A proper re-implementation of \rest_get_avatar_urls.
	 *
	 * @param int|string|object $id_or_email The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array
	 */
	private function rest_get_avatar_urls( $id_or_email ) {
		$urls = [];

		foreach ( \rest_get_avatar_sizes() as $size ) {
			$urls[ $size ] = \get_avatar_url( $id_or_email, [ 'size' => $size ] );
		}

		return $urls;
	}
}
