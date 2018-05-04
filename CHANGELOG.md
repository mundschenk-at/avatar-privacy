# Changelog

## 1.0 (unreleased)
*   _Change_: Refactored according to modern development principles.
*   _Change_: Removed settings in favor of sensible default values and filter hooks:
    -   Gravatar.com usage is opt-in and gravatars are only displayed if the exist.
	-   The default behavior for legacy comments can be customized via the
        `avatar_privacy_gravatar_use_default` filter hook.
*   _Feature_: All default avatars are generated on your server.
*   _Feature_: Gravatar.com avatars are cached locally.
*   _Feature_: Registered users can upload their own avatar images to your server.


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
*   Bugfix: lower-case and trim E-Mail addresses before hashing to produce a
    gravatar URL (otherwise gravatars are not displayed if the address is entered
    with mixed case) -- thanks to "Schokokaese" for finding the problem and solution
*   Bugfix: repaired a bug so that the plugin actually caches the results of a
    gravatar check and uses these cached results if the same E-Mail address appears
    twice on a page
*   Bugfix: corrected image name of the "Media Artist" image (large version)
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
