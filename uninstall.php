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

// drop the avatar_privacy table
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
}

avapr_uninstall();
