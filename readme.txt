=== Avatar Privacy ===
Contributors: Ammaletu, pputzer
Plugin Name: Avatar Privacy
Plugin URI: https://code.mundschenk.at/avatar-privacy/
Author URI: https://code.mundschenk.at/
Tags: gravatar, avatar, privacy
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 4.9
Stable tag: 1.0.5
License: GPLv2 or later

Adds options to enhance the privacy when using avatars.

== Description ==

Avatars from Gravatar.com are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users probably don't. Avatar Privacy can help to improve the privacy situation by making some subtle changes to the way avatars are displayed on your site.

The plugin works without changing your theme files if you use a modern theme, and it does support (simple) multisite installations. It requires at least PHP 5.6 and WordPress 4.9. For the plugin to do anything for you, you need to visit the discussion settings page in the WordPress admin area and save the new settings. Please note that the plugin does not provide an options page of its own, it rather adds to the existing discussion settings page.

= Features =

The plugin's features summed up:

* Add local avatar caching to ensure the privacy of your website visitors.
* Let users and commenters explicitly opt-in before using gravatars.
* Don't publish encrypted e-mail addresses for people who are not members of Gravatar.com.
* Use default avatar images hosted on your server rather than Gravatar.com.

A more detailed examination of the [reasons for using Avatar Privacy](https://code.mundschenk.at/avatar-privacy/reasons/) can be found on the plugin homepage.

= Feedback =

The plugin is still quite new. Please use it with caution and report any problems. You can use the contact form on [my code site](https://code.mundschenk.at/avatar-privacy/) or [create a topic in the support forum](https://wordpress.org/support/plugin/avatar-privacy). I'll see these pop up in my feed reader and hopefully will reply shortly. ;-) You can contact me in German or English.

= Credits =

Avatar Privacy is based on the original plugin by [Johannes Freudendahl](http://code.freudendahl.net/projekte/avatar-privacy/). The new release also includes work by several other people:

* Daniel Mester Pirttijärvi ([Jdenticon](https://jdenticon.com)),
* Shamus Young ([Wavatars](https://shamusyoung.com/twentysidedtale/?p=1462")),
* Andreas Gohr (the original [MonsterID](https://www.splitbrain.org/blog/2007-01/20_monsterid_as_gravatar_fallback) and [RingIcon](https://github.com/splitbrain/php-ringicon)),
* Scott Sherrill-Mix & Katherine Garner (the [hand-drawn monster update](http://scott.sherrillmix.com/blog/blogger/wp_monsterid-update-hand-drawn-monsters/)), and
* Benjamin Laugueux ([Identicon](https://github.com/yzalis/Identicon)).


== Installation ==

= Requirements =
Avatar Privacy has the following additional requirements beyond those of WordPress itself:
* Your server must run PHP 5.6.0 or later, and
* the PHP installation must include the GD extension (most do).


== Screenshots ==

== Frequently Asked Questions ==

= I activated the plugin and don't see any change!? =

Did you remember to visit the discussion settings page, activate 'Show Avatars'? You have to enable avatars for this plugin to be able to do anything.

= I saved the settings and still don't see any changes. How do I know the plugin works? =

Depending on which options you selected, you might not see a change in the way the page looks. The changes are visible in the source code though:

* Look at the gravatar image URL of a user without a gravatar. The plugin works if the URL looks like `[your site]/wp-content/uploads/avatar-privacy/cache/gravatars/[x]/[y]/[long SHA256 token]-68.png` instead of `http://1.gravatar.com/avatar/[other long MD5 token]?s=68&d=http%3A%2F%2F1.gravatar.com%2Favatar%2F[long MD5 token]%3Fs%3D68&r=PG`. There aren't two URLs in there anymore, only one, and the default URL looks the same for two comments without a gravatar.
* You should see the checkbox on the comment form. You need to log out though to see it. If you are logged in, you should see a similar checkbox in your user profile in the WordPress backend.

= I still don't see the checkbox in the comment form!? Everything else works. =

Then you probably don't use a modern theme which makes use of the function `comment_form()` to create the comment form. Check if you can find this function used in `comments.php` or a similar file of your theme. If you do and it still doesn't work, tell me. Otherwise chances are that you do have to add the checkbox manually. Use this function:

`<?php if (function_exists('avapr_get_avatar_checkbox')) echo avapr_get_avatar_checkbox(); ?>`

= What happens if I disable the plugin? Are any of the data altered? =

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). These data are deleted when you properly uninstall the plugin.

Apart from that, the plugin only filters data, but does not permanently change them. Especially, if you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars. You do have to change the default gravatar back manually, though.

= Can this plugin be used together with cache plugins? =

Yes, it certainly can. You have to be careful though which plugin options you select and how you cache your content. The first plugin option checks if a gravatar exists for a given e-mail address and, if not, displays the default image directly. If you cache the output of this check, the gravatar will not be displayed if the user later decides to sign up with Gravatar.com. If you're using this option, you should invalidate cached pages with gravatars on them (mostly the single view of entries) regularly.

= Can this plugin be used on a multisite installation? =

Yes, the plugin can be used on a multisite installation. You can either activate it on individual blogs or do a network activation. As users are global to a multisite installation, their choice regarding Gravatar.com use will affect all sites in the network. So if a user comments on blog A and chooses to display gravatars, this decision will be followed on blog B and C too. On new installations, comment author (i.e. non-user) opt-in is recorded per site, not per network. If you first installed Avatar Privacy 0.4 or earlier, the global table `wp_avatar_privacy` continues to be used for all sites in the multisite network. This behavior can be overriden by the network admin via the filter hook `avatar_privacy_enable_global_table`.

= Won't spam comments flood the database table with useless entries for the checkbox in the comment form? =

The plugin doesn't save the value of the "use gravatar" checkbox for comments by registered users (see below), trackbacks/pingbacks (there is no e-mail address) and comments that are marked as spam when they are saved. If you mark a comment as spam later, the table entry is not removed, since the same e-mail address might also be used by non-spam comments. If a comment is marked as spam by Akismet or similar plugins and you later manually mark it as non-spam, what the user selected when submitting the comment will already be lost. This only happens with spam comments, not comments who just need to be moderated, e.g. because of the 'needs at least one published comment' rule.

= Will the avatar caching make my disk space run out? =

While storing the cached avatar images on your own server will take some extra disk space, the plugin makes sure that it does not grow out of bounds by deleting cached gravatars every other day and all other images once a week. When the cached file is accessed again, it is automatically regenerated.

If you don't have to worry about the amount of disk space consumed, you can extend the maximum age of cached files via the filter hooks `avatar_privacy_gravatars_max_age` and `avatar_privacy_all_images_max_age`. The cron job intervals can also be adjusted via hooks (`avatar_privacy_gravatars_cleanup_interval` and `avatar_privacy_all_images_cleanup_interval`, respectively).

= Can commenters override a registered user's choice whether to display a gravatar by creating fake comments? =

No, for registered users the user profile is checked, not the table for the commenter's choices. Commenters can not override this value, not even the user themselves if they post a comment when not signed-in.

= Which plugins are known to work with Avatar Privacy? =

I used Avatar Privacy together with these plugins:

* [AntiSpam Bee](https://wordpress.org/plugins/antispam-bee/)
* [EWWW Image Optimizer](https://wordpress.org/plugins/ewww-image-optimizer/)

If you find any problems with particular plugins, please tell me!

= What happens if I remove the plugin? =

There is a difference between deactivating the plugin and uninstalling it. The plugin gets deactivated if you do so on the plugins page or if you simply delete the plugin files via FTP. No uninstallation tasks are performed then, so you can activate and deactivate the plugin as you want without losing the plugin's settings.

If you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars. If you changed the default avatar to one of the new local avatar images, the gravatars will not be displayed until you change the default avatar image back.

= OK, but I really want to get rid of everything. How do I that? =

If you want to completely uninstall the plugin and get rid of any data in the database, you should properly uninstall it: Deactivate the plugin first via the WordPress plugins page and then click 'delete' (same page, next to the plugin). For multisite installations, this has to be done by the network administrator on the network plugins page.

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). The following data are stored by the plugin and deleted upon uninstallation:

* custom table(s) `[prefix]_avatar_privacy` (global or per blog on new multisite installations)
* `usermeta` values per user: `use_gravatar`, `avatar_privacy_hash`, `avatar_privacy_user_avatar`
* `option` per blog: `avatar_privacy_settings`
* option per network (`sitemeta`) on multisite installations: `avatar_privacy_salt`
* `transient` per commenter: `avapr_check_[mail hash]`

The default avatar image is set to the mystery man if you selected one of the new local default avatar images.


== Changelog ==

= 1.0.6 (2018-05-29) =
* _Bugfix_: Only valid response codes from Gravatar.com are cached (200 and 404).
* _Bugfix_: Plugin transients are cleared on plugin upgrades.
* _Bugfix_: The workaround for [trac ticket #42663](https://core.trac.wordpress.org/ticket/42663) introduced in 1.0.5 is expanded to all uses of `wp_get_image_editor()`.

= 1.0.5 (2018-05-22) =
* _Bugfix_: Prefer GD-based implementations of `WP_Image_Editor` to work around [trac ticket #42663](https://core.trac.wordpress.org/ticket/42663).
* _Bugfix_: The `rel` and `target` attributes are allowed in `use_gravatar` checkbox labels and by the default, the `noopener` and `nofollow` values for the `rel` attribute are added to the Gravatar.com link.
* _Bugfix_: Invalid 0-byte image files are not saved anymore.

= 1.0.4 (2018-05-20) =
* _Bugfix_: When the plugin is uninstalled, the default avatar image is really reset to `mystery` if necessary.
* _Bugfix_: The `use_gravatar` checkbox is compatible with more themes now.

= 1.0.3 (2018-05-17) =
* _Bugfix_: The plugin no longer fails with a fatal error on PHP 5.6.

= 1.0.2 (2018-05-16) =
* _Bugfix_: PNG avatars were not created correctly when EWWW Image Optimizer was enabled.

= 1.0.1 (2018-05-14) =
* _Bugfix_: Non-multisite installations triggered an SQL error in some situations.

= 1.0 (2018-05-13) =
* _Feature_: All default avatars are generated on your server.
* _Feature_: Gravatar.com avatars are cached locally. (The cache is cleaned regularly via a cron job to prevent unlimited growth.)
* _Feature_: Registered users can upload their own avatar images to your server.
* _Change_: Refactored according to modern development principles.
* _Change_: Removed settings in favor of sensible default values and filter hooks:
  - Gravatar.com usage is opt-in and gravatars are only displayed if the exist.
  - The default behavior for legacy comments can be customized via the `avatar_privacy_gravatar_use_default` filter hook.
* _Change_: All static default icons are now SVG images.

= 0.4 (2018-04-17) =
* adapted the plugin to some subtle changes in how WordPress handles the avatar filter (mainly, default icons arent't passed as URLs anymore)
* added support for the srcset attribute
* raised minimum PHP version to 5.6.0
* raised minimum WordPress version to 4.2
* checked compatibility with WP 4.9.5

= 0.3 (2013-02-24) =
* used transients API to cache results of requests to Gravatar.com for a small amount of time
* added two previously untranslated strings to the translation files
* added a link to the label of the checkbox in the comment and user profile forms
* checked compatibility with WP 3.5.1

= 0.2 (2012-06-11) =
* Bugfix: lower-case and trim e-mail addresses before hashing to produce a gravatar URL (otherwise gravatars are not displayed if the address is entered with mixed case) -- thanks to "Schokokaese" for finding the problem and solution
* Bugfix: repaired a bug so that the plugin actually caches the results of a gravatar check and uses these cached results if the same e-mail address appears twice on a page
* Bugfix: corrected image name of the "Media Artist" image (large version)
* removed the check for the get_headers PHP function unless the "Don't publish encrypted e-mail addresses for non-members of Gravatar.com." option is enabled to not annoy other users -- thanks to Scott for finding the problem
* added some simple inline CSS to fix the display of the checkbox in the comment form with TwentyTen theme
* fixed notice for deprecated function get_user_by_email
* added screenshots
* tested with WP 3.4
* tested with plugins User Photo and Twitter Avatar Reloaded

= 0.1 (2012-02-14) =
* initial release
