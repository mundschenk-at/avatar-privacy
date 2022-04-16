<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018-2021 Peter Putzer.
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

/**
 * Autoload everything using Composer.
 */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

// WordPress time constants.
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 60 * 60 * 24 * 365 );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 7 * 24 * 60 * 60 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * 60 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// WordPress cookie constants.
if ( ! defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', 'somehash' );
}
if ( ! defined( 'COOKIEPATH' ) ) {
	define( 'COOKIEPATH', 'some/path' );
}
if ( ! defined( 'COOKIE_DOMAIN' ) ) {
	define( 'COOKIE_DOMAIN', 'some.blog' );
}

// Other WordPress constants.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', 'wordpress/path/' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'OBJECT_K' ) ) {
	define( 'OBJECT_K', 'OBJECT_K' );
}

// Avatar Privacy constants.
if ( ! defined( 'AVATAR_PRIVACY_PLUGIN_FILE' ) ) {
	define( 'AVATAR_PRIVACY_PLUGIN_FILE', 'plugin/file' );
}
if ( ! defined( 'AVATAR_PRIVACY_PLUGIN_PATH' ) ) {
	define( 'AVATAR_PRIVACY_PLUGIN_PATH', 'plugin' );
}

// Other plugin constants.
if ( ! defined( 'SUA_USER_META_KEY' ) ) {
	define( 'SUA_USER_META_KEY', 'mm_sua_attachment_id' );
}
