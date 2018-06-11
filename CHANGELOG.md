# Changelog

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
