<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2020 Peter Putzer.
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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

// phpcs:disable WordPress.NamingConventions, Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.FunctionComment.InvalidNoReturn

// bbPress stubs.

/**
 * Stub for bbPress
 *
 * @return bool
 */
function is_bbpress() {
	return true;
}
/**
 * Stub for bbPress
 *
 * @param  int  $user_id                 Optional.
 * @param  bool $displayed_user_fallback Optional.
 * @param  bool $current_user_fallback   Optional.
 *
 * @return int
 */
function bbp_get_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback = false ) {}

// BuddyPress stubs.

/**
 * Stub for BuddyPress
 *
 * @param  array $args Optional.
 *
 * @return string
 */
function bp_core_fetch_avatar( array $args ) {}
/**
 * Stub for BuddyPress
 *
 * @param  string $retval      Optional.
 * @param  mixed  $id_or_email Required.
 * @param  array  $args        Required.
 *
 * @return string
 */
function bp_core_get_avatar_data_url_filter( $retval, $id_or_email, array $args ) {}

// WP User Manager stubs.

/**
 * Stub for WP User Manager.
 *
 * @param  string $key     Required.
 * @param  mixed  $default Optional.
 *
 * @return mixed
 */
function wpum_get_option( $key = '', $default = false ) {
	return $default; // Return something, for Scrutinizer-CI.
}
/**
 * Stub for WP User Manager (Carbon Fields).
 *
 * @param  int    $id           Required.
 * @param  string $name         Required.
 * @param  string $container_id Optional.
 *
 * @return mixed
 */
function carbon_get_user_meta( $id, $name, $container_id = '' ) {
	return ''; // Return something, for Scrutinizer-CI.
}
