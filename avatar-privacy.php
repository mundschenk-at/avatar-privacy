<?php
/*
Plugin Name: Avatar Privacy
Plugin URI: http://wordpress.org/extend/plugins/avatar-privacy/
Description: Adds options to enhance the privacy when using avatars.
Version: 0.3
Author: Johannes Freudendahl
Author URI: http://code.freudendahl.net/
License: GPL2 (or later)
  For licenses of bundled default avatar images see icons/license.txt.
*/

/*  Copyright (C) 2011  Johannes Freudendahl  (email: wordpress@freudendahl.net)

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Checks some requirements and then loads the plugin core.
 */
function avapr_init() {
  global $avapr_core;
  $failed = false;
  $settings_page = false;
  
  // load the text domain
  load_plugin_textdomain('avatar-privacy', false, dirname(plugin_basename(__FILE__)) . '/lang');
  
  // check minimum PHP requirements
  if (version_compare(PHP_VERSION, '5.2.4', '<') && current_user_can('manage_plugins')) {
    add_action('admin_notices', 'avapr_add_error_php');
    $failed = true;
  }
  
  // check minimum WP requirements
  if (version_compare($GLOBALS["wp_version"], '3.2', '<') && current_user_can('manage_plugins')) {
    add_action('admin_notices', 'avapr_add_error_wp');
    $failed = true;
  }
  
  // if the admin selected not to display avatars at all, just add a note to the discussions settings page
  if (!get_option('show_avatars')) {
    add_action('admin_init', 'avapr_register_settings');
    $failed = true;
    $settings_page = true;
  }
  
  // load the plugin core
  if (!$failed) {
    // frontend
    require(trailingslashit(dirname(__FILE__)) . "avatar-privacy-core.php");
    $core = new AvatarPrivacyCore();
    $avapr_core = $core; // save in global variable so that the template function avapr_get_avatar_checkbox works
    // backend
    if (is_admin()) {
      require(trailingslashit(dirname(__FILE__)) . "avatar-privacy-options.php");
      new AvatarPrivacyOptions($core);
      $settings_page = true;
    }
  }
  
  // display a settings link on the plugin page
  if ($settings_page) {
    add_filter('plugin_row_meta', 'avapr_display_settings_link', 10, 2);
  }
}

/**
 * Displays a settings link next to the plugin on the plugins page.
 * 
 * @param array $links The array of links.
 * @param string $file The current plugin file.
 * @return array The modified array or links.
 */
function avapr_display_settings_link($links, $file) {
  if ($file == plugin_basename(__FILE__)) {
    $links[] = '<a href="' . admin_url('options-discussion.php#section_avatar_privacy') . '">' . __('Settings', 'avatar-privacy') . '</a>';
  }
  return $links;
}

/**
 * Registers the settings with the settings API. This is only used to display
 * an explanation of the wrong gravatar settings.
 */
function avapr_register_settings() {
  add_settings_section('avatar_privacy_section', __('Avatar Privacy', 'avatar-privacy'), 'avapr_output_settings_header', 'discussion');
}

/**
 * Outputs a short explanation on the discussion settings page.
 */
function avapr_output_settings_header() {
?>
  <p><?php _e("The 'Avatar Privacy' plugin modifies the display of avatars. You have not enabled avatars, so this plugin can't do anything for you. :-)", 'avatar-privacy'); ?></p>
  <p><?php _e("You can enable gravatars above by selecting 'show avatars'. Save the settings and after the page has reloaded you'll see"
    . " the 'Avatar Privacy' plugin options here. There will also be more default avatars, so don't worry about them too much now.", 'avatar-privacy'); ?></p>
<?php
}
  
/**
 * Adds a notice to the admin interface that the PHP version is too old for
 * the plugin.
 */
function avapr_add_error_php() {
  echo '<div class="error fade"><p><strong>' . __('Your PHP version is too old for Avatar Privacy.', 'avatar-privacy') . '</strong><br /> '
    . sprintf(__('This release of the Avatar Privacy plugin requires at least PHP 5.2.4. You are using PHP %2$s. Please ask your web host to update your PHP installation or go to <a href="%1$s">active plugins</a> and deactivate the Avatar Privacy plugin to hide this message.', 'avatar-privacy'), 'plugins.php?plugin_status=active', PHP_VERSION)
    . '</p></div>';
}

/**
 * Adds a notice to the admin interface that the WordPress version is too old
 * for the plugin.
 */
function avapr_add_error_wp() {
  echo '<div class="error fade"><p><strong>' . __('Your WordPress version is too old for Avatar Privacy.', 'avatar-privacy') . '</strong><br /> '
    . sprintf(__('This release of the Avatar Privacy plugin requires at least WordPress 3.2. You are using WordPress %2$s. Please upgrade or go to <a href="%1$s">active plugins</a> and deactivate the Avatar Privacy plugin to hide this message.', 'avatar-privacy'), 'plugins.php?plugin_status=active', $GLOBALS["wp_version"])
    . '</p></div>';
}

// call the init function unless this file was called directly
if (defined('ABSPATH') && defined('WPINC') && !class_exists("AvatarPrivacyCore", false)) {
  avapr_init();
}

/**
 * Template function for older themes: Returns the 'use gravatar' checkbox for
 * the comment form. Output the result with echo or print!
 * 
 * @return string The HTML code for the checkbox or an empty string.
 */
function avapr_get_avatar_checkbox() {
  global $avapr_core;
  
  if (!class_exists("AvatarPrivacyCore", false) || !isset($avapr_core)) {
    return;
  }
  $settings = get_option(AvatarPrivacyCore::SETTINGS_NAME);
  if (!$settings || !is_array($settings) || (sizeof($settings) == 0)) {
    return;
  }
  if (isset($settings['mode_optin']) && ($settings['mode_optin'] === '1')) {
    $result = $avapr_core->comment_form_default_fields(null);
    if (is_array($result) && array_key_exists(AvatarPrivacyCore::CHECKBOX_FIELD_NAME, $result)) {
      return $result[AvatarPrivacyCore::CHECKBOX_FIELD_NAME];
    }
  }
}
