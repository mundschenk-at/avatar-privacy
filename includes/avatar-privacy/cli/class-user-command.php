<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020-2023 Peter Putzer.
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

namespace Avatar_Privacy\CLI;

use Avatar_Privacy\CLI\Abstract_Command;
use Avatar_Privacy\Core\User_Fields;

use WP_CLI;

use function WP_CLI\Utils\get_flag_value;

/**
 * CLI commands for working with the Avatar Privacy user data API.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class User_Command extends Abstract_Command {

	/**
	 * The user data helper.
	 *
	 * @var User_Fields
	 */
	private User_Fields $user_fields;

	/**
	 * Creates a new command instance.
	 *
	 * @param User_Fields $user_fields The user data API.
	 */
	public function __construct( User_Fields $user_fields ) {
		$this->user_fields = $user_fields;
	}

	/**
	 * Registers the command (and any optional subcommands).
	 *
	 * The method assumes that `\WP_CLI` is available.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'avatar-privacy user set-local-avatar', [ $this, 'set_local_avatar' ] );
		WP_CLI::add_command( 'avatar-privacy user delete-local-avatar', [ $this, 'delete_local_avatar' ] );
	}

	/**
	 * Sets a new local avatar for the given user.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The ID of the user whose local avatar should be set.
	 *
	 * <image_url>
	 * : The URL of the avatar to set.
	 *
	 * [--live]
	 * : Actually change the user avatar (instead of only listing it).
	 *
	 * [--yes]
	 * : Do not ask for confirmation when removing data.
	 *
	 * ## EXAMPLES
	 *
	 *    # Show when cron job will run next.
	 *    $ wp avatar-privacy user set-local-avatar 1 http://example.org/image.jpg --live --yes
	 *    Success: Local avatar http://example.org/image.jpg set for user 'example_user' (ID: 1) has been set.
	 *
	 * @param  string[] $args       The positional arguments.
	 * @param  string[] $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function set_local_avatar( array $args, array $assoc_args ) {
		$live      = get_flag_value( $assoc_args, 'live', false );
		$user_id   = (int) $args[0];
		$image_url = $args[1];

		$user       = \get_user_by( 'id', $user_id );
		$user_login = ! empty( $user ) ? $user->user_login : '';
		if ( empty( $user_login ) ) {
			WP_CLI::error( "Invalid user ID {$user_id}" );
		}

		if ( \esc_url_raw( $image_url ) !== $image_url ) {
			WP_CLI::error( "Invalid image URL {$image_url}" );
		}

		if ( ! $live ) {
			WP_CLI::warning( 'Starting dry run.' );
		}

		$avatar        = $this->user_fields->get_local_avatar( $user_id );
		$current_image = $avatar['file'] ?? 'none';

		WP_CLI::line( WP_CLI::colorize( "Currently set local avatar for user '{$user_login}' (ID: {$user_id}): %g{$current_image}%n" ) );

		// OK, let's do this.
		if ( $live ) {
			// Get confirmation.
			WP_CLI::confirm( "Are you sure you want to set {$image_url} as the new local avatar for user '{$user_login}' (ID: {$user_id})?", $assoc_args );

			try {
				// Actually set the new avatar image.
				$this->user_fields->set_local_avatar( $user_id, $image_url );
			} catch ( \Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			WP_CLI::success( WP_CLI::colorize( "Local avatar {$image_url} for user '{$user_login}' (ID: {$user_id}) has been set." ) );
		} else {
			WP_CLI::success( 'Dry run finished.' );
		}
	}

	/**
	 * Deletes the local avatar for the given user.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The ID of the user whose local avatar should be deleted.
	 *
	 * [--live]
	 * : Actually change the user avatar (instead of only listing it).
	 *
	 * [--yes]
	 * : Do not ask for confirmation when removing data.
	 *
	 * ## EXAMPLES
	 *
	 *    # Show when cron job will run next.
	 *    $ wp avatar-privacy user delete-local-avatar 1 --live --yes
	 *    Success: The local avatar for user 'example_user' (ID: 1) has been deleted.
	 *
	 * @param  string[] $args       The positional arguments.
	 * @param  string[] $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function delete_local_avatar( array $args, array $assoc_args ) {
		$live    = get_flag_value( $assoc_args, 'live', false );
		$user_id = (int) $args[0];

		$user       = \get_user_by( 'id', $user_id );
		$user_login = ! empty( $user ) ? $user->user_login : '';
		if ( empty( $user_login ) ) {
			WP_CLI::error( "Invalid user ID {$user_id}" );
		}

		$avatar = $this->user_fields->get_local_avatar( $user_id );
		if ( empty( $avatar['file'] ) ) {
			WP_CLI::error( "No local avatar set for user '{$user_login}' (ID: {$user_id})." );
		}

		if ( ! $live ) {
			WP_CLI::warning( 'Starting dry run.' );
		}

		WP_CLI::line( WP_CLI::colorize( "Currently set local avatar for user '{$user_login}' (ID: {$user_id}): %g{$avatar['file']}%n" ) );

		// OK, let's do this.
		if ( $live ) {
			// Get confirmation.
			WP_CLI::confirm( "Are you sure you want to delete the current local avatar of user '{$user_login}' (ID: {$user_id})?", $assoc_args );

			try {
				// Actually set the new avatar image.
				$this->user_fields->delete_local_avatar( $user_id );
			} catch ( \Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			WP_CLI::success( WP_CLI::colorize( "The local avatar for user '{$user_login}' (ID: {$user_id}) has been deleted." ) );
		} else {
			WP_CLI::success( 'Dry run finished.' );
		}
	}
}
