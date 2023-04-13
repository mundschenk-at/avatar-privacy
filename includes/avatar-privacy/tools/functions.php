<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2023 Peter Putzer.
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
 *
 * @internal All functions in this file are to be considered for internal use only.
 */

namespace Avatar_Privacy\Tools;

/**
 * Deletes a file.
 *
 * This functions is a replacement for `\wp_delete_file` that _does_ return
 * the result of the wrapped `unlink` call.
 *
 * @since 2.7.0
 *
 * @param  string $file The path to the file to delete.
 *
 * @return bool
 */
function delete_file( string $file ): bool {
	/** This filter is documented in wp-includes/functions.php */
	$delete = \apply_filters( 'wp_delete_file', $file ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress Core hook usage is intentional.
	if ( ! empty( $delete ) ) {
		return @unlink( $delete ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	return false;
}
