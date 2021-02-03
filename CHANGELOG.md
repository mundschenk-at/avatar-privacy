# Changelog

## 2.4.4 (2021-02-03)
*   _Bugfix_: Gracefully handle changes to WordPress' default DB collation (no
    more `Illegal mix of collations` errors).

## 2.4.3 (2021-01-15)
*   _Bugfix_: Don't break stuff (another build process fix, for real this time).

## 2.4.2 (2021-01-15)
*   _Bugfix_: An unfortunate oversight in the build process led to crashes instead
              of the intended graceful failure when the installation requirements
              were not met.

## 2.4.1 (2021-01-10)
*   _Bugfix_: Don't break the site when the options value in the DB has become corrupted.
*   _Bugfix_: Workaround for maximum database key length when using MySQL < 5.7.7 or
              MariaDB < 10.2.2.

## 2.4.0 (2021-01-10)
*   _Feature_: Legacy (default) avatars are now properly cached and resized.
*   _Feature_: There are now API methods to get and set a user's (local) avatar
               (and their Gravatar and anonymous commenting policies).
*   _Feature_: New WP-CLI commands relating to local avatars added.
*   _Feature_: Integration for the [Simple Author Box](https://wordpress.org/plugins/simple-author-box/) plugin has been added.
*   _Change_: Requires at least WordPress 5.2 and PHP 7.0.
*   _Change_: The `yzalis/identicon` package has been updated to version 2.0.
*   _Change_: Some unused files have been removed from the `vendor-scoped` directory.
*   _Change_: A new per-site database table for fast hash lookup has been introduced
              (base name `avatar_privacy_hashes`).
*   _Change_: General code clean-up and removal of PHP 5.6 workarounds.
*   _Bugfix_: Gravatars will be properly regenerated for comment authors that have
              not set a policy (when the site-admin has switched the default to
              "opt-out").
*   _Bugfix_: When a user requests deletion of their personal data, this now includes
              the uploaded avatar image files.
*   _Bugfix_: A timestamp is added to uploaded avatar images for better browser
              caching in the Profile screen.

## 2.3.4 (2020-03-22)
*   _Bugfix_: Allow plain URLs as default avatars. Use the filter hook
    `avatar_privacy_allow_remote_default_icon_url` to allow third-party domains
    and `avatar_privacy_validate_default_icon_url` if you want to implement your
    own image URL validation.
*   _Bugfix_: Properly handle trackback/linkback avatars. This includes a workaround
    for avatars provided by the [Webmention](https://wordpress.org/plugins/webmention/)
    plugin. You can use `avatar_privacy_allow_remote_avatar_url` to prohibit third-party
    domains (the default is to allow them for webmentions) and `avatar_privacy_validate_avatar_url`
    if you want to implement your own image URL validation.
*   _Change_: Due to the trackback/linkback bug fix, the priority for `pre_get_avatar_data`
    filter can now be adjusted using the `avatar_privacy_pre_get_avatar_data_filter_priority`
    hook instead of being hardcoded.

## 2.3.3 (2019-12-27)
*   _Bugfix_: Timestamps in WP-CLI commands now always use GMT.

## 2.3.2 (2019-11-09)
*   _Bugfix_: Some error messages were not getting translated because of a [WP.org infrastructure change](https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/).

## 2.3.1 (2019-09-29)
*   _Bugfix_: Will not crash on WordPress 4.9 anymore when the Gutenberg plugin is not activated.
*   _Bugfix_: The label for the block settings panel of the Avatar block was incorrect.

## 2.3.0 (2019-09-28)
*   _Feature_: New shortcode `[avatar-privacy-form]` (optional parameter: `avatar-size`)
    to allow changing Avatar Privacy's user settings on the frontend of the site.
*   _Feature_: Two blocks have been added to the Block Editor to allow displaying a
    user's avatar and to change the settings related Avatar Privacy on the frontend.
*   _Feature_: Three new generated default avatars:
    -   Bird Avatars,
    -   Cat Avatars (both designed by David Revoy), and
    -   RoboHash (designed by Zikri Kader).
*   _Feature_: Integration for the following plugins had been added:
    -   [BuddyPress](https://wordpress.org/plugins/buddypress/)
    -   [Theme My Login](https://wordpress.org/plugins/theme-my-login/)
    -   [Ultimate Member](https://wordpress.org/plugins/ultimate-member/)
*   _Feature_: New template function `\Avatar_Privacy\gravatar_checkbox()` for
    legacy themes added.
*   _Feature_: There is a CLI interface to some parts of Avatar Privacy:
    -   `wp avatar-privacy db show`: Show information about the custom database table(s).
    -   `wp avatar-privacy db list`: List entries in the custom database table(s).
    -   `wp avatar-privacy db create`: Create the custom database table.
    -   `wp avatar-privacy db upgrade`: Upgrade the structure of the custom database table.
    -   `wp avatar-privacy uninstall`: Remove data added by Avatar Privacy.
    -   `wp avatar-privacy cron list`: List active cron jobs created by the plugin.
    -   `wp avatar-privacy cron delete`: Delete cron jobs created by the plugin.
*   _Change_: `avapr_get_avatar_checkbox()` has been deprecated in favor of
    `\Avatar_Privacy\get_gravatar_checkbox()`.
*   _Change_: The ID and name of the `use_gravatar` comment form checkbox has been
    changed to `avatar-privacy-use-gravatar`. Please update custom CSS rules accordingly.
*   _Change_: Additional inline styling is added to the `avatar-privacy-use-gravatar`
    comment form checkbox to work around common theme limitations. Styling can be
    disabled using the `avatar_privacy_comment_checkbox_disable_inline_style`
    filter hook.
*   _Change_: All external PHP dependencies have been moved to the namespace
    `Avatar_Privacy\Vendor` to reduce the chance of conflicts with other plugins.
*   _Bugfix_: Gravatars are only cached if they are actually images to prevent issues
    with temporary Varnish errors on Gravatar.com.

## 2.2.2 (2019-06-23)
*   _Bugfix_: Re-allow installation on 4.9.x for increased compatibility with
    possible future WordPress Core changes. The 2.2.x branch of Avatar Privacy is
    the last that will support WordPress < 5.2.

## 2.2.1 (2019-06-08)
*   _Bugfix_: Compatibility with Windows servers.

## 2.2.0 (2019-05-12)
*   _Feature_: Integration for the following plugins had been added:
    -   [wpDiscuz](https://wordpress.org/plugins/wpdiscuz/)
    -   [WP User Manager](https://wordpress.org/plugins/wp-user-manager/)

## 2.1.0 (2019-04-14)
*   _Feature_: Improved compatibility with multisite installations. Plugin data will
    be properly deleted on uninstallation or when a site is removed. ("Large Networks"
    will still have to take manual action to prevent timeouts.)
*   _Feature_: Network settings for enabling/disabling global table use on multisite.
    Existing global data will be migrated to the site-specific tables when global
    table use is disabled (but not in the other direction).
*   _Change_: `usermeta` keys are now prefixed (`avatar_privacy_use_gravatar`
    instead of `use_gravatar`).
*   _Change_: Generally improved code quality through unit tests.
*   _Bugfix_: New multisite installations were incorrectly detected as "legacy",
    making them use the global table (instead of per-site tables). Affected installations
    can be switched via the new network settings page.

## 2.0.5 (2019-02-23)
*   _Bugfix_: Fixed a previously undiscovered compatibility issue with recent versions of EWWW Image Optimizer.

## 2.0.4 (2019-02-22)
*   _Bugfix_: Updated included libraries for improved compatibility with other plugins.

## 2.0.3 (2018-11-30)
*   _Bugfix_: Prevent warnings when trying to retrieve the avatar for an invalid user ID.

## 2.0.2 (2018-09-09)
*   _Bugfix_: Updated included libraries for improved compatibility with other plugins.

## 2.0.1 (2018-08-16)
*   _Bugfix_: The plugin no longer fails with a fatal error on PHP 5.6 (accidentally introduced in 2.0.0).

## 2.0.0 (2018-08-11)
*   _Feature_: Administrators can now upload site-specific default avatar images.
*   _Feature_: The default policy previously only accessible via the `avatar_privacy_gravatar_use_default`
    hook can now be set from the `Discussion` settings page.
*   _Feature_: New filter hooks `avatar_privacy_gravatar_link_rel` and `avatar_privacy_gravatar_link_target`
    to filter the `rel` and `target` attributes of all links to Gravatar.com.
*   _Bugfix_: The REST API returned incorrect avatar URLs for registered users (workaround for
    [trac ticket #40030](https://core.trac.wordpress.org/ticket/40030)).
*   _Bugfix_: The gravatar use cookie is only set when the comment author has given consent.
*   _Change_: Internal restructuring to make maintenance easier.

## 1.1.1 (2018-06-11)
*   _Bugfix_: Changing the default gravatar policy via `avatar_privacy_gravatar_use_default`
    works again for registered users.

## 1.1.0 (2018-06-10)
*   _Feature_: Supports the new privacy tools on WordPress >= 4.9.6 (export and
    deletion of personal data, suggested privacy notice text).
*   _Feature_: Registered users can opt into allowing logged-out comments with the
    same mail address to user their profile pictures.
*   _Feature_: The plugin is now compatible with bbPress.
*   _Feature_: The position of the `use_gravatar` checkbox can be adjusted via the
    new filter hook `avatar_privacy_use_gravatar_position`.
*   _Change_: Trashed comments and comments marked as spam do not trigger a validation
    request to Gravatar.com if the admin has set the default gravatar use policy
    to "enabled" via the filter hook `avatar_privacy_gravatar_use_default`.

## 1.0.7 (2018-06-06)
*   _Bugfix_: The `use_gravatar` is actually checked when the cookie has been set.
*   _Bugfix_: A (harmless) PHP warning has been fixed.

## 1.0.6 (2018-05-29)
*   _Bugfix_: Only valid response codes from Gravatar.com are cached (200 and 404).
*   _Bugfix_: Plugin transients are cleared on plugin upgrades.
*   _Bugfix_: The workaround for [trac ticket #42663](https://core.trac.wordpress.org/ticket/42663)
    introduced in 1.0.5 is expanded to all uses of `wp_get_image_editor()`.

## 1.0.5 (2018-05-22)
*   _Bugfix_: Prefer GD-based implementations of `WP_Image_Editor` to work around
    [trac ticket #42663](https://core.trac.wordpress.org/ticket/42663).
*   _Bugfix_: The `rel` and `target` attributes are allowed in `use_gravatar`
    checkbox labels and by the default, the `noopener` and `nofollow` values for
    the `rel` attribute are added to the Gravatar.com link.
*   _Bugfix_: Invalid 0-byte image files are not saved anymore.

## 1.0.4 (2018-05-20)
*   _Bugfix_: When the plugin is uninstalled, the default avatar image is really
    reset to `mystery` if necessary.
*   _Bugfix_: The `use_gravatar` checkbox is compatible with more themes now.

## 1.0.3 (2018-05-17)
*   _Bugfix_: The plugin no longer fails with a fatal error on PHP 5.6.

## 1.0.2 (2018-05-16)
*   _Bugfix_: PNG avatars were not created correctly when EWWW Image Optimizer was enabled.

## 1.0.1 (2018-05-14)
*   _Bugfix_: Non-multisite installations triggered an SQL error in some situations.

## 1.0 (2018-05-13)
*   _Feature_: All default avatars are generated on your server.
*   _Feature_: Gravatar.com avatars are cached locally. (The cache is cleaned regularly
    via a cron job to prevent unlimited growth.)
*   _Feature_: Registered users can upload their own avatar images to your server.
*   _Change_: Refactored according to modern development principles.
*   _Change_: Removed settings in favor of sensible default values and filter hooks:
    -   Gravatar.com usage is opt-in and gravatars are only displayed if the exist.
    -   The default behavior for legacy comments can be customized via the
        `avatar_privacy_gravatar_use_default` filter hook.
*   _Change_: All static default icons are now SVG images.

## 0.4 (2018-04-17)
*   adapted the plugin to some subtle changes in how WordPress handles the avatar
    filter (mainly, default icons arent't passed as URLs anymore)
*   added support for the srcset attribute
*   raised minimum PHP version to 5.6.0
*   raised minimum WordPress version to 4.2
*   checked compatibility with WP 4.9.5

## 0.3 (2013-02-24)
*   used transients API to cache results of requests to gravatar.com for a small
    amount of time
*   added two previously untranslated strings to the translation files
*   added a link to the label of the checkbox in the comment and user profile forms
*   checked compatibility with WP 3.5.1

## 0.2 (2012-06-11)
*   _Bugfix_: lower-case and trim E-Mail addresses before hashing to produce a
    gravatar URL (otherwise gravatars are not displayed if the address is entered
    with mixed case) -- thanks to "Schokokaese" for finding the problem and solution
*   _Bugfix_: repaired a bug so that the plugin actually caches the results of a
    gravatar check and uses these cached results if the same E-Mail address appears
    twice on a page
*   _Bugfix_: corrected image name of the "Media Artist" image (large version)
*   removed the check for the get_headers PHP function unless the "Don't publish
    encrypted E-Mail addresses for non-members of gravatar.com." option is enabled
    to not annoy other users -- thanks to Scott for finding the problem
*   added some simple inline CSS to fix the display of the checkbox in the comment
    form with TwentyTen theme
*   fixed notice for deprecated function get_user_by_email
*   added screenshots
*   tested with WP 3.4
*   tested with plugins User Photo and Twitter Avatar Reloaded

## 0.1 (2012-02-14)
*   initial release
