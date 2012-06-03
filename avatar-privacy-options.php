<?php

/**
 * Options class of the Avatar Privacy plugin. Contains all code for the
 * options page. The plugin's options are displayed on the discussion settings
 * page.
 *
 * @author Johannes Freudendahl, wordpress@freudendahl.net
 */
class AvatarPrivacyOptions {
  
  private $core = null;
  
  /**
   * Creates a AvatarPrivacyOptions instance and registers all necessary
   * hooks and filters for the settings.
   * 
   * @param object $core_instance An AvatarPrivacyCore instance.
   */
  public function __construct(&$core_instance) {
    $this->core = $core_instance;
    // register the settings to be displayed
    add_action('admin_init', array(&$this, 'register_settings'));
  }
  
  /**
   * Registers the settings with the settings API.
   */
  public function register_settings() {
    // add a section for the 'check for gravatar' mode to the avatar options
    add_settings_section('avatar_privacy_section', __('Avatar Privacy', 'avatar-privacy') . '<span id="section_avatar_privacy"></span>', array(&$this, 'output_settings_header'), 'discussion');
    add_settings_field('avatar_privacy_checkforgravatar', __('Check for gravatars', 'avatar-privacy'), array(&$this, 'output_checkforgravatar_setting'), 'discussion', 'avatar_privacy_section');
    add_settings_field('avatar_privacy_optin', __('Opt in or out of gravatars', 'avatar-privacy'), array(&$this, 'output_optin_setting'), 'discussion', 'avatar_privacy_section');
    add_settings_field('avatar_privacy_checkbox_default', __('The checkbox is...', 'avatar-privacy'), array(&$this, 'output_checkbox_default_setting'), 'discussion', 'avatar_privacy_section');
    add_settings_field('avatar_privacy_default_show', __('Default value', 'avatar-privacy'), array(&$this, 'output_default_show_setting'), 'discussion', 'avatar_privacy_section');
    // we save all settings in one variable in the database table; also adds a validation method
    register_setting('discussion', AvatarPrivacyCore::SETTINGS_NAME, array(&$this, 'validate_settings'));
  }
  
  /**
   * Validates the plugin's settings, rejects any invalid data.
   * 
   * @param array $input The array of settings values to save.
   * @return array The cleaned-up array of user input.
   */
  public function validate_settings($input) {
    // validate the settings
    $newinput['mode_checkforgravatar'] = (isset($input['mode_checkforgravatar']) && ($input['mode_checkforgravatar'] === '1')) ? '1' : '0';
    $newinput['mode_optin'] = (isset($input['mode_optin']) && ($input['mode_optin'] === '1')) ? '1' : '0';
    $newinput['checkbox_default'] = (isset($input['checkbox_default']) && ($input['checkbox_default'] === '1')) ? '1' : '0';
    $newinput['default_show'] = (isset($input['default_show']) && ($input['default_show'] === '1')) ? '1' : '0';
    // check if the headers function works on the server (use MD5 of mystery default image)
    if ($newinput['mode_checkforgravatar'] == '1') {
      $uri = 'http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=32&d=404';
      $headers = @get_headers($uri);
      if (!is_array($headers)) {
        add_settings_error(AvatarPrivacyCore::SETTINGS_NAME, 'get-headers-failed',
          __("The get_headers() function seems to be disabled on your system! To check if a gravatar exists for an E-Mail address,"
            . " this PHP function is needed. It seems this function is either disabled on your system or the gravatar.com"
            . " servers can not be reached for another reason. Check with your server admin if you don't see gravatars for your own"
            . " gravatar account and this message keeps popping up after saving the plugin settings.", 'avatar-privacy'),
          'error');
      }
    }
    return $newinput;
  }
  
  /**
   * Outputs the header of the Avatar Privacy settings section.
   */
  public function output_settings_header() {
?>
    <p><?php _e("Avatars are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users"
      . " probably don't. With the following options, this plugin can help to improve the privacy situation. You can select one or more options as you like."
      . " If you don't select anything, then the plugin won't do anything!", 'avatar-privacy'); ?></p>
<?php
  }
  
  /**
   * Outputs the elements for the 'check for gravatar' setting.
   */
  public function output_checkforgravatar_setting() {
    $options = get_option(AvatarPrivacyCore::SETTINGS_NAME);
    if (($options === false) || !isset($options['mode_checkforgravatar'])) {
      $options['mode_checkforgravatar'] = false;
    }
?>
    <input id="avatar_privacy_checkforgravatar" name="avatar_privacy_settings[mode_checkforgravatar]" type="checkbox" value="1"<?php if ($options['mode_checkforgravatar'] === '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_checkforgravatar"><strong><?php _e("Don't publish encrypted E-Mail addresses for non-members of gravatar.com.", 'avatar-privacy'); ?></strong></label><br />
    <?php if ($this->core->is_default_avatar_dynamic()) { ?>
    <p>
      <strong style="font-color: #FF0000;"><?php _e('Warning:', 'avatar-privacy'); ?></strong>
      <?php _e("This option does not work with dynamic default avatars since these images are generated out of the encrypted E-Mail address."
        . " Please change to a static default image, otherwise only blank images will be displayed as default avatar image.", 'avatar-privacy'); ?>
    </p>
    <?php } ?>
    <p class="description">
      <?php _e("The plugin will check if a gravatar exists for a given E-Mail address. If a gravatar exists, it is displayed as usual."
        . " If no gravatar exists, the default image is displayed directly instead of as a redirect from the non-existing gravatar image.", 'avatar-privacy'); ?>
      <?php _e("The check is done on your server, not in the visitor's browser. If your site has many visitors, you should keep an eye on whether"
        . " your server is ok with the calls to gravatar.com.", 'avatar-privacy'); ?>
    </p>
    <p class="description">
      <span style="font-weight: bold;"><?php _e('Advantage:', 'avatar-privacy'); ?></span>
      <?php _e("You are not publicly publishing encrypted E-Mail addresses of people who have not actually signed up with gravatar.com. Gravatar.com will still"
        . " get the encrypted address though. This reduces the possibility to track all these users' comments all over the web.", 'avatar-privacy'); ?>
      <?php _e("It also removes the possibility that the E-Mail address is reverse engineered (e.g. guessed) out of the MD5 token. That's good,"
        . " since you probably promised not to publish the E-Mail address somewhere around your comment form.", 'avatar-privacy'); ?>
    </p>
<?php
  }
  
  /**
   * Outputs the elements for the 'optin' setting.
   */
  public function output_optin_setting() {
    $options = get_option(AvatarPrivacyCore::SETTINGS_NAME);
    if (($options === false) || !isset($options['mode_optin'])) {
      $options['mode_optin'] = false;
    }
?>
    <input id="avatar_privacy_optin" name="avatar_privacy_settings[mode_optin]" type="checkbox" value="1"<?php if ($options['mode_optin'] === '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_optin"><strong><?php _e('Let users and commenters opt in or out of using gravatars.', 'avatar-privacy'); ?></strong></label><br />
    <?php if ($this->core->is_default_avatar_dynamic()) { ?>
    <p>
      <strong style="font-color: #FF0000;"><?php _e('Warning:', 'avatar-privacy'); ?></strong>
      <?php _e("This mode does not work well with dynamic default images because for users without a gravatar image, a difference will be visible between"
        . " users who opted out (no image) and other users (dynamic default). If you want all users without a gravatar to look the same, please change the"
        . " settings to a static default image.", 'avatar-privacy'); ?>
    </p>
    <?php } ?>
    <p class="description">
      <?php _e("Commenters will see a checkbox to enable or disable the use of gravatars for their comments. Users will have the same option in their user profile.", 'avatar-privacy'); ?>
      <?php _e("For both users and commenters, the selection is saved globally for all comments (+on all blogs for multisite installations). Gravatars"
        . " can't be enabled/disabled on a per-comment basis. For commenters, the decision is saved in a cookie.", 'avatar-privacy'); ?>
    </p>
    <p class="description">
      <span style="font-weight: bold;"><?php _e('Advantage:', 'avatar-privacy'); ?></span>
      <?php _e("Users and commenters can decide if they want the MD5 of their E-Mail address to be published. Commenters can change their mind"
        . " by leaving another comment, users can change the setting in their user profile.", 'avatar-privacy'); ?>
    </p>
<?php
  }
  
  /**
   * Outputs the elements for the 'checkbox default' setting.
   */
  public function output_checkbox_default_setting() {
    $options = get_option(AvatarPrivacyCore::SETTINGS_NAME);
    if (($options === false) || !isset($options['checkbox_default'])) {
      $options['checkbox_default'] = false;
    }
?>
    <input id="avatar_privacy_checkbox_default_true" name="avatar_privacy_settings[checkbox_default]" type="radio" value="1"<?php if ($options['checkbox_default'] === '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_checkbox_default_true"><?php _e('checked by default', 'avatar-privacy'); ?></label> <span class="description">(<?php _e('commenters and users can opt out by unchecking it', 'avatar-privacy'); ?>)</span><br />
    <input id="avatar_privacy_checkbox_default_false" name="avatar_privacy_settings[checkbox_default]" type="radio" value="0"<?php if ($options['checkbox_default'] !== '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_checkbox_default_false"><?php _e('not checked by default', 'avatar-privacy'); ?></label> <span class="description">(<?php _e('commenters and users can opt in by checking it', 'avatar-privacy'); ?>)</span><br />
 <?php
  }
  
  /**
   * Outputs the elements for the 'default show' setting.
   */
  public function output_default_show_setting() {
    $options = get_option(AvatarPrivacyCore::SETTINGS_NAME);
    if (($options === false) || !isset($options['default_show'])) {
      $options['default_show'] = false;
    }
?>
    <input id="avatar_privacy_default_show_true" name="avatar_privacy_settings[default_show]" type="radio" value="1"<?php if ($options['default_show'] === '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_default_show_true"><?php _e("Show gravatars, except if users / commenters opted out", 'avatar-privacy'); ?></label><br />
    <input id="avatar_privacy_default_show_false" name="avatar_privacy_settings[default_show]" type="radio" value="0"<?php if ($options['default_show'] !== '1') echo ' checked="checked"' ?> />
    <label for="avatar_privacy_default_show_false"><?php _e("Don't show gravatars, except if users / commenters opted in", 'avatar-privacy'); ?></label><br />
    <p class="description"><?php _e("Regulates whether to show or not to show gravatars for commenters and users who haven't saved any preference."
      . " This is relevant for old comments from before activating the plugin and users who did not save their profile after the plugin was activated.", 'avatar-privacy'); ?></p>
<?php
  }
  
}
