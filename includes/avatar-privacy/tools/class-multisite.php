<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2019 Peter Putzer.
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

namespace Avatar_Privacy\Tools;

/**
 * A collection of utility methods for use in multisite environments.
 *
 * @since 2.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Multisite {

	/**
	 * Performs the given task on all sites in a network.
	 *
	 * Warning: This is potentially expensive.
	 *
	 * @param  callable $task       The task to execute. Should take the site ID as its parameter.
	 * @param  int|null $network_id Optional. The network ID (`null` means the current netwrok). Default null.
	 */
	public function do_for_all_sites_in_network( callable $task, $network_id = null ) {
		foreach ( $this->get_site_ids( $network_id ) as $site_id ) {
			\switch_to_blog( $site_id );

			$task( $site_id );

			\restore_current_blog();
		}
	}

	/**
	 * Retrieves all site IDs for a network.
	 *
	 * @param  int|null $network_id Optional. The network ID (`null` means the current network). Default null.
	 *
	 * @return int[]                An array of site IDs.
	 */
	public function get_site_ids( $network_id = null ) {
		$network_id = $network_id ?: \get_current_network_id();
		$query      = [
			'fields'     => 'ids',
			'network_id' => $network_id,
			'number'     => '',
		];

		$result = \get_sites( $query );
		if ( ! \is_array( $result ) ) {
				$result = [];
		}

		return $result;
	}
}
