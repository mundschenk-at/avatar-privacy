<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2019-2020 Peter Putzer.
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
use Avatar_Privacy\Components\Image_Proxy;

use WP_CLI;

/**
 * CLI commands for working with Avatar Privacy cron jobs.
 *
 * @since 2.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Cron_Command extends Abstract_Command {

	/**
	 * Registers the command (and any optional subcommands).
	 *
	 * The method assumes that `\WP_CLI` is available.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'avatar-privacy cron list', [ $this, 'list_' ] );
		WP_CLI::add_command( 'avatar-privacy cron delete', [ $this, 'delete' ] );
	}

	/**
	 * Displays information on the cron jobs installed by Avatar Privacy.
	 *
	 * ## EXAMPLES
	 *
	 *    # Show when cron job will run next.
	 *    $ wp avatar-privacy cron list
	 *    Success: Cron job will run next at 2019-09-03 20:56:16 on this site.
	 *
	 * @subcommand list
	 *
	 * @return void
	 */
	public function list_() {
		$job  = Image_Proxy::CRON_JOB_ACTION;
		$next = \wp_next_scheduled( $job );

		if ( false === $next ) {
			WP_CLI::success( WP_CLI::colorize( "Cron job %B{$job}%n not scheduled on this site." ) );
		} else {
			$timestamp = \gmdate( 'Y-m-d H:i:s', $next );
			WP_CLI::success( WP_CLI::colorize( "Cron job %B{$job}%n will run next at %B{$timestamp}%n on this site." ) );
		}
	}

	/**
	 * Deletes the cron jobs installed by Avatar Privacy.
	 *
	 * They will be scheduled again on the next request.
	 *
	 * ## EXAMPLES
	 *
	 *    # Delete all cron jobs hooked by Avatar Privacy.
	 *    $ wp avatar-privacy cron delete
	 *    Success: Cron job avatar_privacy_daily was unscheduled on this site (1 event).
	 *
	 * @return void
	 */
	public function delete() {
		$job    = Image_Proxy::CRON_JOB_ACTION;
		$events = \wp_unschedule_hook( $job );

		if ( false === $events ) {
			WP_CLI::error( WP_CLI::colorize( "Cron job %B{$job}%n could not be unscheduled on this site." ) );
		} else {
			$events = ( 1 === $events ) ? "{$events} event" : "{$events} events";
			WP_CLI::success( WP_CLI::colorize( "Cron job %B{$job}%n was unscheduled on this site (%B{$events}%n)." ) );
		}
	}
}
