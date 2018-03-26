<?php
/**
 * This file is part of Avatar Privacy.
 *
 * Copyright 2018 Peter Putzer.
 * Copyright 2012-2013 Johannes Freudendahl.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
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

/**
 * Uninstalls the Avatar Privacy plugin. This file is called by WordPress when
 * the user deletes the plugin via the WordPress backend.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */

// Check that the user is indeed just uninstalling this plugin.
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * Uninstalls all the plugin's information from the database.
 */
function avapr_uninstall() {
	global $wpdb;

	// Drop global table.
	$table_name = $wpdb->base_prefix . 'avatar_privacy';
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name . ';' );

	// Delete usermeta for all users.
	$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . ' WHERE meta_key = "use_gravatar";' );

	// Delete/change options for main blog.
	$wpdb->query( 'DELETE FROM ' . $wpdb->options . ' WHERE option_name = "avatar_privacy_settings";' );
	$wpdb->query(
		'UPDATE ' . $wpdb->options . ' SET option_value = "mystery" WHERE option_name = "avatar_default"' .
		' AND option_value IN ("comment", "im-user-offline", "view-media-artist");'
	);

	// Delete/change options for all other blogs (multisite).
	if ( is_multisite() ) {
		foreach ( get_sites( [ 'fields' => 'ids' ] ) as $blog_id ) {
			$wpdb->query( 'DELETE FROM ' . $wpdb->get_blog_prefix( $blog_id ) . 'options WHERE option_name = "avatar_privacy_settings";' );
			$wpdb->query(
				'UPDATE ' . $wpdb->get_blog_prefix( $blog_id ) . 'options SET option_value = "mystery" WHERE option_name = "avatar_default"' .
				' AND option_value IN ("comment", "im-user-offline", "view-media-artist");'
			);
		}
	}

	// Delete transients from sitemeta or options table.
	if ( is_multisite() ) {
		// Stored in sitemeta.
		$wpdb->query( 'DELETE FROM ' . $wpdb->sitemeta . ' WHERE meta_key LIKE "_site_transient_timeout_avapr_validate_gravatar_%" OR meta_key LIKE "_site_transient_avapr_validate_gravatar_%";' );
	} else {
		// Stored in wp_options.
		$wpdb->query( 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_timeout_avapr_check_%" OR option_name LIKE "_transient_avapr_check_%";' );
	}
}

avapr_uninstall();
