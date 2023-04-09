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
use Avatar_Privacy\Core\Default_Avatars;

use WP_CLI;

use function WP_CLI\Utils\get_flag_value;

/**
 * CLI commands for setting Avatar Privacy defaults.
 *
 * @since 2.4.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Default_Command extends Abstract_Command {

	/**
	 * The default avatars data helper.
	 *
	 * @var Default_Avatars
	 */
	private Default_Avatars $default_avatars;

	/**
	 * Creates a new command instance.
	 *
	 * @param Default_Avatars $default_avatars The default avatars API.
	 */
	public function __construct( Default_Avatars $default_avatars ) {
		$this->default_avatars = $default_avatars;
	}

	/**
	 * Registers the command (and any optional subcommands).
	 *
	 * The method assumes that `\WP_CLI` is available.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'avatar-privacy default get-custom-default-avatar', [ $this, 'get_custom_default_avatar' ] );
		WP_CLI::add_command( 'avatar-privacy default set-custom-default-avatar', [ $this, 'set_custom_default_avatar' ] );
		WP_CLI::add_command( 'avatar-privacy default delete-custom-default-avatar', [ $this, 'delete_custom_default_avatar' ] );
	}

	/**
	 * Retrieves the custom default avatar for the site.
	 *
	 * ## EXAMPLES
	 *
	 *    # Show the current custom default avatar.
	 *    $ wp avatar-privacy default get-custom-default-avatar
	 *    Success: Currently set custom default avatar: /path/image.jpg
	 *
	 * @return void
	 */
	public function get_custom_default_avatar() {
		$avatar = $this->default_avatars->get_custom_default_avatar();
		if ( empty( $avatar['file'] ) ) {
			WP_CLI::success( 'No custom default avatar set for this site.' );
		} else {
			WP_CLI::success( WP_CLI::colorize( "Currently set custom default avatar: %g{$avatar['file']}%n" ) );
		}
	}

	/**
	 * Sets the custom default avatar for the site.
	 *
	 * ## OPTIONS
	 *
	 * <image_url>
	 * : The URL of the avatar to set.
	 *
	 * [--live]
	 * : Actually change the default avatar (instead of only listing it).
	 *
	 * [--yes]
	 * : Do not ask for confirmation when updating data.
	 *
	 * ## EXAMPLES
	 *
	 *    # Set a new custom default avatar.
	 *    $ wp avatar-privacy user set-local-avatar http://example.org/image.jpg --live --yes
	 *    Currently set custom default avatar: /path/old-image.jpg
	 *    Success: Custom default avatar http://example.org/image.jpg has been set.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function set_custom_default_avatar( array $args, array $assoc_args ) {
		$live      = get_flag_value( $assoc_args, 'live', false );
		$image_url = $args[0];

		if ( \esc_url_raw( $image_url ) !== $image_url ) {
			WP_CLI::error( "Invalid image URL {$image_url}" );
		}

		if ( ! $live ) {
			WP_CLI::warning( 'Starting dry run.' );
		}

		$avatar        = $this->default_avatars->get_custom_default_avatar();
		$current_image = $avatar['file'] ?? 'none';

		WP_CLI::line( WP_CLI::colorize( "Currently set custom default avatar: %g{$current_image}%n" ) );

		// OK, let's do this.
		if ( $live ) {
			// Get confirmation.
			WP_CLI::confirm( "Are you sure you want to set {$image_url} as the new custom default avatar for this site?", $assoc_args );

			try {
				// Actually set the new avatar image.
				$this->default_avatars->set_custom_default_avatar( $image_url );
			} catch ( \Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			WP_CLI::success( WP_CLI::colorize( "Custom default avatar {$image_url} has been set." ) );
		} else {
			WP_CLI::success( 'Dry run finished.' );
		}
	}

	/**
	 * Deletes the custom default avatar for the site.
	 *
	 * ## OPTIONS
	 *
	 * [--live]
	 * : Actually delete the custom default avatar (instead of only listing it).
	 *
	 * [--yes]
	 * : Do not ask for confirmation when removing data.
	 *
	 * ## EXAMPLES
	 *
	 *    # Show when cron job will run next.
	 *    $ wp avatar-privacy user delete-local-avatar --live --yes
	 *    Currently set custom default avatar: /path/image.jpg
	 *    Success: The custom default avatar for this site has been deleted.
	 *
	 * @param  array $args       The positional arguments.
	 * @param  array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function delete_custom_default_avatar( array $args, array $assoc_args ) {
		$live = get_flag_value( $assoc_args, 'live', false );

		$avatar = $this->default_avatars->get_custom_default_avatar();
		if ( empty( $avatar['file'] ) ) {
			WP_CLI::error( 'No custom default avatar set for this site.' );
		}

		if ( ! $live ) {
			WP_CLI::warning( 'Starting dry run.' );
		}

		WP_CLI::line( WP_CLI::colorize( "Currently set custom default avatar: %g{$avatar['file']}%n" ) );

		// OK, let's do this.
		if ( $live ) {
			// Get confirmation.
			WP_CLI::confirm( 'Are you sure you want to delete the custom default avatar?', $assoc_args );

			try {
				// Actually set the new avatar image.
				$this->default_avatars->delete_custom_default_avatar();
			} catch ( \Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			WP_CLI::success( WP_CLI::colorize( 'The custom default avatar for this site has been deleted.' ) );
		} else {
			WP_CLI::success( 'Dry run finished.' );
		}
	}
}
