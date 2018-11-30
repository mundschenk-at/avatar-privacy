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
 * @package mundschenk-at/avatar-privacy/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Autoload everything using Composer.
 */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Necessary constants.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', 'wordpress/path/' );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 10000 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 1000 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 100 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 10 );
}
