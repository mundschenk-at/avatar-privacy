# Avatar Privacy

Avatars from Gravatar.com are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users probably don't. Avatar Privacy can help to improve the privacy situation by making some subtle changes to the way avatars are displayed on your site.

The plugin works without changing your theme files if you use a modern theme, and it does support (simple) multisite installations. It requires at least PHP 5.6 and WordPress 4.6. For the plugin to do anything for you, you need to visit the discussion settings page in the WordPress admin area and enable `Show Avatars`. Please note that the plugin does not provide an options page of its own, it rather adds to the existing discussion settings page.


## Features

The plugin's features summed up:

*   Add local avatar caching to ensure the privacy of your website visitors.
*   Let users and commenters explicitly opt-in before using gravatars.
*   Don't publish encrypted e-mail addresses for people who are not members of Gravatar.com.
*   Use default avatar images hosted on your server rather than Gravatar.com.

A more detailed examination of the [reasons for using Avatar Privacy](https://code.mundschenk.at/avatar-privacy/reasons/) can be found on the plugin homepage.

## Uninstallation

There is a difference between deactivating the plugin and uninstalling it. The plugin gets deactivated if you do so on the plugins page or if you simply delete the plugin files via FTP. No uninstallation tasks are performed then, so you can activate and deactivate the plugin as you want without losing the plugin's settings.

If you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars. If you changed the default avatar to one of the new local avatar images, the gravatars will not be displayed until you change the default avatar image back.

If you want to completely uninstall the plugin and get rid of any data in the database, you should properly uninstall it: Deactivate the plugin first via the WordPress plugins page and then click 'delete' (same page, next to the plugin). For multisite installations, this has to be done by the network administrator on the network plugins page.

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). The following data are saved and deleted upon uninstallation:

*   custom table(s) `[prefix]_avatar_privacy` (global or per blog on new multisite installations)
*   `usermeta` values per user: `use_gravatar`, `avatar_privacy_hash`, `avatar_privacy_user_avatar`
*   `option` per blog: `avatar_privacy_settings`
*   option per network (`sitemeta`) on multisite installations: `avatar_privacy_salt`
*   `transient` per commenter: `avapr_check_[mail hash]`

The default avatar image is set to the mystery man if you selected one of the new local default avatar images.


## Frequently Asked Questions

The [Avatar Privacy FAQ](https://code.mundschenk.at/avatar-privacy/frequently-asked-questions/) can be found on the plugin homepage.
