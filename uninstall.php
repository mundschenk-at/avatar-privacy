<?php

/**
 * Uninstalls the Avatar Privacy plugin. This file is called by WordPress when
 * the user deletes the plugin via the WordPress backend.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */

// check that the user is indeed just uninstalling this plugin
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

/**
 * Uninstalls all the plugin's information from the database.
 */
function avapr_uninstall() {
  global $wpdb;
  
  // drop global table
  $table_name = $wpdb->base_prefix . "avatar_privacy";
  $wpdb->query('DROP TABLE IF EXISTS ' . $table_name . ';');
  
  // delete usermeta for all users
  $wpdb->query('DELETE FROM ' . $wpdb->usermeta . ' WHERE meta_key = "use_gravatar";');
  
  // delete/change options for main blog
  $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name = "avatar_privacy_settings";');
  $wpdb->query('UPDATE ' . $wpdb->options . ' SET option_value = "mystery" WHERE option_name = "avatar_default"'
      . ' AND option_value IN ("comment", "im-user-offline", "view-media-artist");');
  
  // delete/change options for all other blogs (multisite)
  if (is_multisite()) {
    $sql = 'SELECT blog_id FROM ' . $wpdb->blogs . ' WHERE site_id = %d ORDER BY registered DESC';
    $blogs = $wpdb->get_col($wpdb->prepare($sql, $wpdb->siteid));
    foreach ($blogs as $blog_id) {
      $wpdb->query('DELETE FROM ' . $wpdb->get_blog_prefix($blog_id) . 'options WHERE option_name = "avatar_privacy_settings";');
      $wpdb->query('UPDATE ' . $wpdb->get_blog_prefix($blog_id) . 'options SET option_value = "mystery" WHERE option_name = "avatar_default"'
          . ' AND option_value IN ("comment", "im-user-offline", "view-media-artist");');
    }
  }
  
  // delete transients from sitemeta or options table
  if (is_multisite()) {
    // stored in sitemeta
    $wpdb->query('DELETE FROM ' . $wpdb->sitemeta . ' WHERE meta_key LIKE "_site_transient_timeout_avapr_validate_gravatar_%" OR meta_key LIKE "_site_transient_avapr_validate_gravatar_%";');
  } else {
    // stored in wp_options
    $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_timeout_avapr_check_%" OR option_name LIKE "_transient_avapr_check_%";');
  }
}

avapr_uninstall();
