<?php

/**
 * Core class of the Avatar Privacy plugin. Contains all the actual code
 * except the options page.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */
class AvatarPrivacyCore {
  
  //--------------------------------------------------------------------------
  // constants
  //--------------------------------------------------------------------------

  // If anything changes here, modify uninstall.php too!
  
  /**
   * The name of the checkbox field in the comment form.
   */
  const CHECKBOX_FIELD_NAME = 'use_gravatar';
  
  /**
   * The name of the combined settings in the database.
   */
  const SETTINGS_NAME = 'avatar_privacy_settings';
  
  
  //--------------------------------------------------------------------------
  // variables
  //--------------------------------------------------------------------------
  
  /**
   * The user's settings.
   */
  var $settings = array();
  
  /**
   * A cache for the results of the validate_gravatar function.
   */
  var $validate_gravatar_cache = array();
  
  /**
   * A cache for the default avatars.
   */
  var $default_avatars = array();
  
  
  //--------------------------------------------------------------------------
  // constructor
  //--------------------------------------------------------------------------
  
  /**
   * Creates a AvatarPrivacyCore instance and registers all necessary hooks
   * and filters for the plugin.
   */
  public function __construct() {
    // add new default avatars
    add_filter('avatar_defaults', array(&$this, 'avatar_defaults'));
    
    // read the plugin settings
    $this->settings = get_option(self::SETTINGS_NAME);
    if (!$this->settings || !is_array($this->settings) || (sizeof($this->settings) == 0)) {
      $this->settings = array();
    }
    
    // mode 1 + mode 2 + new default image display: filter the gravatar image upon display
    add_filter('get_avatar', array(&$this, 'get_avatar'), 10, 5);
    
    // mode 2
    if (isset($this->settings['mode_optin']) && ($this->settings['mode_optin'] === '1')) {
      // add the checkbox to the comment form
      add_filter('comment_form_default_fields', array(&$this, 'comment_form_default_fields'));
      // handle the checkbox data upon saving the comment
      add_action('comment_post', array(&$this, 'comment_post'), 10, 2);
      if (is_admin()) {
        // add the checkbox to the user profile form
        add_action('show_user_profile', array(&$this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array(&$this, 'add_user_profile_fields'));
        add_action('personal_options_update', array(&$this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array(&$this, 'save_user_profile_fields'));
      }
    }
  }
  
  
  //--------------------------------------------------------------------------
  // public functions
  //--------------------------------------------------------------------------
  
  // If anything gets changed here, modify uninstall.php too.
  /**
   * Returns the array of new default avatars defined by this plugin.
   * 
   * @return array The array of default avatars.
   */
  public function default_avatars() {
    if (empty($this->default_avatars)) {
      $this->default_avatars = array(
        'comment' => sprintf(__('Comment (loaded from your server, part of <a href="%s">NDD Icon Set</a>, under LGPL)', 'avatar-privacy'), 'http://www.nddesign.de/news/2007/10/15/NDD_Icon_Set_1_0_-_Free_Icon_Set'),
        'im-user-offline' => sprintf(__('User Offline (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy'), 'http://www.oxygen-icons.org/'),
        'view-media-artist' => sprintf(__('Media Artist (loaded from your server, part of <a href="%s">Oxygen Icons</a>, under LGPL)', 'avatar-privacy'), 'http://www.oxygen-icons.org/'),
      );
    }
    return $this->default_avatars;
  }  
  
  /**
   * Adds new images to the list of default avatar images.
   * 
   * @param array $avatar_defaults The list of default avatar images.
   * @return array The modified default avatar array.
   */
  public function avatar_defaults($avatar_defaults) {
    $avatar_defaults = array_merge($avatar_defaults, $this->default_avatars());
    return $avatar_defaults;
  }
  
  /**
   * Before displaying an avatar image, checks that displaying the gravatar
   * for this E-Mail address has not been disabled (opted out, option 2).
   * Also, if option 1 is selected ("Don't publish encrypted E-Mail addresses
   * for non-members of Gravatar."), the function checks for the E-Mail address
   * if a gravatar is available and if not displays the default image
   * directly.
   * 
   * @param string $avatar The avatar image HTML fragment as built by the
   * WordPress function.
   * @param int|string|object Either a user ID, a user object, a comment object,
   * or an E-Mail address.
   * @param int $size The size of the avatar image in pixels.
   * @param string $default The URL of the default image.
   * @param string $alt The alternate text to use in the image tag.
   * @return string The avatar image HTML code for the user's avatar.
   */
  public function get_avatar($avatar, $id_or_email, $size, $default, $alt) {
    global $pagenow;
    $show_avatar = true; // since this filter function has been called, WP option 'show_avatars' must be set to true
    
    // don't change anything on the discussion settings page, except for our own new gravatars
    if (($pagenow == 'options-discussion.php') && !array_key_exists($default, $this->default_avatars())) {
      return $avatar;
    }
    
    // get the E-Mail address and the user ID to display the gravatar for
    $email = '';
    $user_id = false;
    if (is_numeric($id_or_email)) {
      // load from user via ID
      $user_id = (int) $id_or_email;
      $user = get_userdata($user_id);
      if ($user) {
        $email = $user->user_email;
      }
    } elseif (is_object($id_or_email)) {
      if (!empty($id_or_email->user_id)) {
        // load from either a user or an author comment object
        $user_id = (int) $id_or_email->user_id;
        $user = get_userdata($user_id);
        if ($user) {
          $email = $user->user_email;
        }
      } elseif (!empty($id_or_email->comment_author_email)) {
        // load from comment
        $email = $id_or_email->comment_author_email;
      }
    } else {
      // load string directly
      $email = $id_or_email;
    }
    
    // mode 2: find out if the user opted out of displaying a gravatar
    if (isset($this->settings['mode_optin']) && ($this->settings['mode_optin'] === '1') && ($user_id || $email)) {
      $use_default = false;
      if ($user_id) {
        // for users get the value from the usermeta table
        $show_avatar = get_user_meta($user_id, 'use_gravatar', true) === 'true';
        $use_default = ($show_avatar === '');
      } else {
        // for comments get the value from the plugin's table
        $this->maybe_create_table(); // make sure our database table exists
        $current_value = $this->load_data($email);
        $show_avatar = $current_value && ($current_value->use_gravatar === '1');
        $use_default = ($current_value == NULL);
      }
      if ($use_default) {
        $options = get_option(AvatarPrivacyCore::SETTINGS_NAME);
        $show_avatar = (isset($options['default_show']) && ($options['default_show'] === '1')); // false as fallback if the default option is not set
      }
    }
    
    // mode 1: check if a gravatar exists for the E-Mail address
    if ($show_avatar && isset($this->settings['mode_checkforgravatar']) && ($this->settings['mode_checkforgravatar'] === '1')
        && $email && !$this->validate_gravatar($email)) {
      $show_avatar = false;
    } else if (!$email) {
      $show_avatar = false;
    }
    
    // mode 1 + 2: change the default image if dynamic defaults are configured
    if (!$show_avatar && $this->is_default_avatar_dynamic()
        && ((isset($this->settings['mode_checkforgravatar']) && ($this->settings['mode_checkforgravatar'] === '1'))
            || (isset($this->settings['mode_optin']) && ($this->settings['mode_optin'] === '1')))) {
      // use blank image here, dynamic default images would leak the MD5
      $default = includes_url('images/blank.gif');
    }
    
    // new default avatars: replace avatar name with image URL
    $default_name = preg_match('#http://\d+.gravatar.com/avatar/\?d=([^&]+)&#', $default, $matches) ? $matches[1] : $default;
    $default_changed = array_key_exists($default_name, $this->default_avatars());
    if ($default_changed) {
      $old_default = $default_name;
      $default = $this->get_default_avatar_url($default_name, $size);
    }
    
    // modify the avatar URL
    if (!$show_avatar) {
      // display the default avatar instead of the avatar for the E-Mail address
      $avatar = $this->replace_avatar_url($avatar, $default, $size, $email);
    } else if ($default_changed) {
      // change the default avatar in the given URL (for users who opted in to gravatars but don't have one)
      $avatar = str_replace('d=' . $old_default, 'd=' . $default, $avatar);
    }
    return $avatar;
  } 
  
  /**
   * Adds the 'use gravatar' checkbox to the comment form. The checkbox value
   * is read from a cookie if available.
   * 
   * @param array $fields The array of default comment fields.
   * @return array The modified array of comment fields.
   */
  public function comment_form_default_fields($fields) {
    // don't change the form if a user is logged-in
    if (is_user_logged_in()) {
      return $fields;
    }
    // define the new checkbox field
    if (isset($_POST[self::CHECKBOX_FIELD_NAME])) {
      // re-displaying the comment form with validation errors
      $is_checked = ($_POST[self::CHECKBOX_FIELD_NAME] == '1');
    } else if (isset($_COOKIE['comment_use_gravatar_' . COOKIEHASH])) {
      // read the value from the cookie, saved with previous comment
      $is_checked = ($_COOKIE['comment_use_gravatar_' . COOKIEHASH] == '1');
    } else {
      // read the value from the options
      $is_checked = (isset($this->settings['checkbox_default']) && ($this->settings['checkbox_default'] === '1'));
    }
    $checked = $is_checked ? ' checked="checked"' : '';
    $new_field = '<p class="comment-form-use-gravatar">'
        . '<input id="' . self::CHECKBOX_FIELD_NAME . '" name="' . self::CHECKBOX_FIELD_NAME . '" type="checkbox" value="true"' . $checked . ' style="width: auto; margin-right: 5px;" />'
        . '<label for="' . self::CHECKBOX_FIELD_NAME . '">' . __('Display a gravatar for my comment', 'avatar-privacy') . '</label> '
        . '</p>';
    // either add the new field after the E-Mail field or at the end of the array
    if (is_array($fields) && array_key_exists('email', $fields)) {
      $result = array();
      foreach ($fields as $key => $value) {
        $result[$key] = $value;
        if ($key == 'email') {
          $result['use_gravatar'] = $new_field;
        }
      }
      $fields = $result;
    } else {
      $fields['use_gravatar'] = $new_field;
    }
    return $fields;
  }
  
  /**
   * Saves the value of the 'use gravatar' checkbox from the comment form in
   * the database, but only for non-spam comments.
   * 
   * @param string $comment_id The ID of the comment that has just been saved.
   * @param string $comment_approved Whether the comment has been approved (1)
   * or not (0) or is marked as spam (spam).
   */
  public function comment_post($comment_id, $comment_approved) {
    global $wpdb;
    
    // don't save anything for spam comments, trackbacks/pingbacks, and registered user's comments
    if ('spam' === $comment_approved) {
      return;
    }
    $comment = get_comment($comment_id);
    if (!$comment || ($comment->comment_type != '') || ($comment->comment_author_email == '')) {
      return;
    }
    
    // make sure that the E-Mail address does not belong to a registered user
    if (get_user_by_email($comment->comment_author_email)) {
      // This is either a comment with a fake identity or a user who didn't sign in
      // and rather entered their details manually. Either way, don't save anything.
      return;
    }
    
    // make sure the database table exists
    $this->maybe_create_table();
    
    // save the 'use gravatar' value
    $use_gravatar = (isset($_POST[self::CHECKBOX_FIELD_NAME]) && ($_POST[self::CHECKBOX_FIELD_NAME] == 'true')) ? '1' : '0';
    $current_value = $this->load_data($comment->comment_author_email);
    if (!$current_value) {
      // nothing found in the database, insert the dataset
      $wpdb->insert($wpdb->avatar_privacy, array(
          'email' => $comment->comment_author_email,
          'use_gravatar' => $use_gravatar,
          'last_updated' => current_time('mysql'),
          'log_message' => 'set with comment ' . $comment_id . (is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : ''),
      ), array('%s', '%d', '%s', '%s'));
    } else if ($current_value->use_gravatar != $use_gravatar) {
      // dataset found but with different value, update it
      $wpdb->update($wpdb->avatar_privacy, array(
          'use_gravatar' => $use_gravatar,
          'last_updated' => current_time('mysql'),
          'log_message' => 'set with comment ' . $comment_id . (is_multisite() ? ' (site: ' . $wpdb->siteid . ', blog: ' . $wpdb->blogid . ')' : ''),
        ),
        array('id' => $current_value->id),
        array('%d', '%s', '%s'),
        array('%d')
      );
    }
    
    // set a cookie for the 'use gravatar' value
    $comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
    setcookie('comment_use_gravatar_' . COOKIEHASH, $use_gravatar, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
  }
  
  /**
   * Adds the 'use gravatar' checkbox to the user profile form.
   * 
   * @param object $user The current user whose profile to modify.
   */
  function add_user_profile_fields($user) {
    $val = get_the_author_meta(self::CHECKBOX_FIELD_NAME, $user->ID);
    if ($val == 'true') {
      $checked = ' checked="checked"';
    } else if ($val == 'false') {
      $checked = '';
    } else {
      $options = get_option(self::SETTINGS_NAME);
      $checked = ($options['checkbox_default'] == '1') ? ' checked="checked"' : '';
    }
?>
    <h3>Use Gravatar</h3>
    <table class="form-table">
      <tr>
        <th scope="row">Gravatars</th>
        <td>
          <input id="<?php echo self::CHECKBOX_FIELD_NAME; ?>" name="<?php echo self::CHECKBOX_FIELD_NAME; ?>" type="checkbox" value="true"<?php echo $checked; ?> />
          <label for="<?php echo self::CHECKBOX_FIELD_NAME; ?>"><?php _e('Display a gravatar for my E-Mail address', 'avatar-privacy'); ?></label><br />
          <span class="description"><?php _e("Uncheck this box if you don't want to display a gravatar for your E-Mail address.", 'avatar-privacy'); ?></span>
        </td>
      </tr>
    </table>
<?php
  }
  
  /**
   * Saves the value of the 'use gravatar' checkbox from the user profile in
   * the database.
   * 
   * @param string $user_id The ID of the user that has just been saved.
   */
  function save_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
      return false;
    }
    // use true/false instead of 1/0 since a '0' value is removed from the database and then
    // we can't differentiate between opted-out and never saved a value
    update_user_meta($user_id, self::CHECKBOX_FIELD_NAME, ($_POST[self::CHECKBOX_FIELD_NAME] == 'true') ? 'true' : 'false');
  }
  
  
  //--------------------------------------------------------------------------
  // public helper functions
  //--------------------------------------------------------------------------
  
  /**
   * Checks if the currently selected default avatar is dynamically generated
   * out of an E-Mail address or not.
   * 
   * @return bool True if the current default avatar is dynamic, false if it
   * is a static image.
   */
  public function is_default_avatar_dynamic() {
    $default_avatar = get_option('avatar_default');
    return ($default_avatar == 'identicon') || ($default_avatar == 'wavatar') || ($default_avatar == 'monsterid') || ($default_avatar == 'retro');
  }
  
  /**
   * Validates if a gravatar exists for the given E-Mail address. Function
   * taken from: http://codex.wordpress.org/Using_Gravatars
   * 
   * @param string $email The E-Mail address to check.
   * @return bool True if a gravatar exists for the given E-Mail address,
   * false otherwise, including if gravatar.com could not be reached or
   * answered with a different errror code or if no E-Mail address was given.
   */
  public function validate_gravatar($email = '') {
    if (strlen($email) == 0) {
      return false;
    }
    if (array_key_exists($email, $this->validate_gravatar_cache)) {
      return $this->validate_gravatar_cache['$email'];
    }
    $hash = md5($email);
    $uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
    $headers = @get_headers($uri);
    $result = is_array($headers) && preg_match("|200|", $headers[0]);
    $this->validate_gravatar_cache['$email'] = $result;
    return $result;
  }
  
  
  //--------------------------------------------------------------------------
  // private functions
  //--------------------------------------------------------------------------
  
  /**
   * Creates the plugin's database table if it doesn't already exist. The
   * table is created as a global table for multisite installations. Makes the
   * name of the table available through $wpdb->avatar_privacy.
   */
  private function maybe_create_table() {
    global $wpdb, $charset_collate;
    
    // check if the table exists
    if (property_exists($wpdb, 'avatar_privacy')) {
      return;
    }
    $table_name = $wpdb->base_prefix . 'avatar_privacy';
    if ($wpdb->get_var('SHOW tables LIKE "' . $table_name . '"') == $table_name) {
      $wpdb->avatar_privacy = $table_name;
      return;
    }
    
    // load upgrade.php for the dbDelta function
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    
    // create the plugin's table
    $sql = 'CREATE TABLE ' . $table_name . ' ('
        . 'id mediumint(9) NOT NULL AUTO_INCREMENT,'
        . 'email VARCHAR(100) NOT NULL UNIQUE,'
        . 'use_gravatar tinyint(2) NOT NULL,'
        . 'last_updated datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,'
        . 'log_message VARCHAR(255),'
        . 'PRIMARY KEY  (id)) ' . $charset_collate . ';'; // Why does wpdb need two spaces here???
    $result = dbDelta($sql);
    if (is_array($result) && array_key_exists($table_name, $result)) {
      $wpdb->avatar_privacy = $table_name;
    }
  }
  
  /**
   * Returns the dataset from the 'use gravatar' table for the given E-Mail
   * address.
   * 
   * @param string $email The E-Mail address to check.
   * @return object The dataset as an object or null.
   */
  private function load_data($email) {
    global $wpdb;
    
    if ($email === '') {
      return null;
    }
    $sql = $wpdb->prepare('SELECT * FROM ' . $wpdb->avatar_privacy . ' WHERE email LIKE "%s"', $email);
    $res = $wpdb->get_row($sql, OBJECT);
    return $res;
  }
  
  /**
   * Returns an image URL for the given default avatar identifier. The images
   * are taken from the "icons" sub-folder in the plugin folder.
   * 
   * @param string The default avatar image identifier.
   * @param int $size The size of the avatar image in pixels.
   * @return string The full default avatar image URL.
   */
  private function get_default_avatar_url($default, $size) {
    $use_size = ($size > 64) ? '128' : '64';
    return plugins_url('/icons/' . $default. '-' . $use_size . '.png', __FILE__) . '?s=' . $size;
  }
  
  /**
   * Replaces the avatar URL in the given HTML fragment.
   * 
   * @param string $avatar The avatar image HTML fragment.
   * @param string $new_url The new URL to insert.
   * @param int $size The size of the avatar image in pixels.
   * @return string The modified avatar HTML fragment.
   */
  private function replace_avatar_url($avatar, $new_url, $size, $email) {
    if ($new_url == '') {
      if (is_ssl()) {
        $host = 'https://secure.gravatar.com';
      } else {
        $host = empty($email) ? 'http://0.gravatar.com' : sprintf("http://%d.gravatar.com", (hexdec($email_hash[0]) % 2));
      }
      $new_url = "$host/avatar/?s={$size}";
    }
    return preg_replace('/(src=["\'])[^"\']+(["\'])/i', '$1' . $new_url . '$2', $avatar);
  }
  
}
