=== Avatar Privacy ===
Contributors: Ammaletu, pputzer
Plugin Name: Avatar Privacy
Plugin URI: https://code.mundschenk.at/avatar-privacy/
Author URI: https://code.mundschenk.at/
Tags: gravatar, avatar, privacy, caching, bbpress, buddypress
Requires at least: 4.9
Requires PHP: 5.6
Tested up to: 5.3
Stable tag: 2.3.0
License: GPLv2 or later

Enhances the privacy of your users and visitors with gravatar opt-in and local avatars.

== Description ==

Avatars from Gravatar.com are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users probably don't. Avatar Privacy can help to improve the privacy situation by making some subtle changes to the way avatars are displayed on your site.

The plugin works without changing your theme files (for reasonably modern themes), and it does support multisite installations. Please note that the plugin does not provide an options page of its own, it rather adds to the existing discussion settings page.

= Features =

The plugin's features summed up:

* Self-uploaded avatars for users (and custom default images), hosted on _your_ server.
* Users and commenters explicitly opt-in before using gravatars.
* Gravatar caching to ensure the privacy of your website visitors.
* Don't publish weakly encrypted e-mail addresses of comment authors.

A more detailed examination of the [reasons for using Avatar Privacy](https://code.mundschenk.at/avatar-privacy/reasons/) can be found on the plugin homepage.

= WP-CLI Commands =

Avatar Privacy includes the following [WP-CLI](https://wp-cli.org) commands:
* `wp avatar-privacy db show`: Show information about the custom database table(s).
* `wp avatar-privacy db list`: List entries in the custom database table(s).
* `wp avatar-privacy db create`: Create the custom database table.
* `wp avatar-privacy db upgrade`: Upgrade the structure of the custom database table.
* `wp avatar-privacy uninstall`: Remove data added by Avatar Privacy.
* `wp avatar-privacy cron list`: List active cron jobs created by the plugin.
* `wp avatar-privacy cron delete`: Delete cron jobs created by the plugin.


= Feedback =

Please report any problems with the plugin, I'll do my best to sort things out. You can use the contact form on [my code site](https://code.mundschenk.at/avatar-privacy/) or [create a topic in the support forum](https://wordpress.org/support/plugin/avatar-privacy). You can contact me in German or English.

= Credits =

Avatar Privacy is based on the original plugin by [Johannes Freudendahl](http://code.freudendahl.net/projekte/avatar-privacy/). The new release also includes work by several other people:

* Daniel Mester Pirttijärvi ([Jdenticon](https://jdenticon.com)),
* Shamus Young ([Wavatars](https://shamusyoung.com/twentysidedtale/?p=1462")),
* Andreas Gohr (the original [MonsterID](https://www.splitbrain.org/blog/2007-01/20_monsterid_as_gravatar_fallback) and [RingIcon](https://github.com/splitbrain/php-ringicon)),
* Scott Sherrill-Mix & Katherine Garner (the [hand-drawn monster update](http://scott.sherrillmix.com/blog/blogger/wp_monsterid-update-hand-drawn-monsters/))
* Benjamin Laugueux ([Identicon](https://github.com/yzalis/Identicon)),
* David Revoy ([Bird](https://www.davidrevoy.com/article720/bird-avatar-generator) and [Cat Avatars](https://www.davidrevoy.com/article591/cat-avatar-generator)), and
* Zikri Kader, Colin Davis & Nimiq ([RoboHash](https://robohash.org)).

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

`<?php if ( \function_exists( 'Avatar_Privacy\gravatar_checkbox' ) ) { \Avatar_Privacy\gravatar_checkbox(); } ?>`

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

These plugins have been tested in conjunction with Avatar Privacy:

* [AntiSpam Bee](https://wordpress.org/plugins/antispam-bee/)
* [bbPress](https://wordpress.org/plugins/bbpress/)
* [BuddyPress](https://wordpress.org/plugins/buddypress/)
* [Comments – wpDiscuz](https://wordpress.org/plugins/wpdiscuz/)
* [EWWW Image Optimizer](https://wordpress.org/plugins/ewww-image-optimizer/)
* [Theme My Login](https://wordpress.org/plugins/theme-my-login/)
* [Ultimate Member](https://wordpress.org/plugins/ultimate-member/)
* [WP User Manager – User Profile Builder & Membership](https://wordpress.org/plugins/wp-user-manager/)

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

= 2.3.0 (2019-09-28) =
* _Feature_: New shortcode `[avatar-privacy-form]` (optional parameter: `avatar-size`) to allow changing Avatar Privacy's user settings on the frontend of the site.
* _Feature_: Two blocks have been added to the Block Editor to allow displaying a user's avatar and to change the settings related Avatar Privacy on the frontend.
* _Feature_: Three new generated default avatars:
  - Bird Avatars,
  - Cat Avatars (both designed by David Revoy), and
  - RoboHash (designed by Zikri Kader).
* _Feature_: Integration for the following plugins had been added:
  - [BuddyPress](https://wordpress.org/plugins/buddypress/)
  - [Theme My Login](https://wordpress.org/plugins/theme-my-login/)
  - [Ultimate Member](https://wordpress.org/plugins/ultimate-member/)
* _Feature_: New template function `\Avatar_Privacy\gravatar_checkbox()` for legacy themes added.
* _Feature_: There is a CLI interface to some parts of Avatar Privacy:
  - `wp avatar-privacy db show`: Show information about the custom database table(s).
  - `wp avatar-privacy db list`: List entries in the custom database table(s).
  - `wp avatar-privacy db create`: Create the custom database table.
  - `wp avatar-privacy db upgrade`: Upgrade the structure of the custom database table.
  - `wp avatar-privacy uninstall`: Remove data added by Avatar Privacy.
  - `wp avatar-privacy cron list`: List active cron jobs created by the plugin.
  - `wp avatar-privacy cron delete`: Delete cron jobs created by the plugin.
* _Change_: `avapr_get_avatar_checkbox()` has been deprecated in favor of `\Avatar_Privacy\get_gravatar_checkbox()`.
* _Change_: The ID and name of the `use_gravatar` comment form checkbox has been changed to `avatar-privacy-use-gravatar`. Please update custom CSS rules accordingly.
* _Change_: Additional inline styling is added to the `avatar-privacy-use-gravatar` comment form checkbox to work around common theme limitations. Styling can be disabled using the `avatar_privacy_comment_checkbox_disable_inline_style` filter hook.
* _Change_: All external PHP dependencies have been moved to the namespace `Avatar_Privacy\Vendor` to reduce the chance of conflicts with other plugins.
* _Bugfix_: Gravatars are only cached if they are actually images to prevent issues with temporary Varnish errors on Gravatar.com.

= 2.2.2 (2019-06-23) =
* _Bugfix_: Re-allow installation on 4.9.x for increased compatibility with possible future WordPress Core changes. The 2.2.x branch of Avatar Privacy is the last that will support WordPress < 5.2.

= 2.2.1 (2019-06-08) =
* _Bugfix_: Compatibility with Windows servers.

= 2.2.0 (2019-05-12) =
* _Feature_: Integration for the following plugins had been added:
  - [wpDiscuz](https://wordpress.org/plugins/wpdiscuz/)
  - [WP User Manager](https://wordpress.org/plugins/wp-user-manager/)

= 2.1.0 (2019-04-14) =
* _Feature_: Improved compatibility with multisite installations. Plugin data will be properly deleted on uninstallation or when a site is removed. ("Large Networks" will still have to take manual action to prevent timeouts.)
* _Feature_: Network settings for enabling/disabling global table use on multisite. Existing global data will be migrated to the site-specific tables when global table use is disabled (but not in the other direction).
* _Change_: `usermeta` keys are now prefixed (`avatar_privacy_use_gravatar` instead of `use_gravatar`).
* _Change_: Generally improved code quality through unit tests.
* _Bugfix_: New multisite installations were incorrectly detected as "legacy", making them use the global table (instead of per-site tables). Affected installations can be switched via the new network settings page.

= 2.0.5 (2019-02-23) =
* _Bugfix_: Fixed a previously undiscovered compatibility issue with recent versions of EWWW Image Optimizer.

= 2.0.4 (2019-02-22) =
* _Bugfix_: Updated included libraries for improved compatibility with other plugins.

= 2.0.3 (2018-11-30) =
* _Bugfix_: Prevent warnings when trying to retrieve the avatar for an invalid user ID.

= 2.0.2 (2018-09-09) =
* _Bugfix_: Updated included libraries for improved compatibility with other plugins.

= 2.0.1 (2018-08-16) =
* _Bugfix_: The plugin no longer fails with a fatal error on PHP 5.6 (accidentally introduced in 2.0.0).

= 2.0.0 (2018-08-11) =
* _Feature_: Administrators can now upload site-specific default avatar images.
* _Feature_: The default policy previously only accessible via the `avatar_privacy_gravatar_use_default` hook can now be set from the `Discussion` settings page.
* _Feature_: New filter hooks `avatar_privacy_gravatar_link_rel` and `avatar_privacy_gravatar_link_target` to filter the `rel` and `target` attributes of all links to Gravatar.com.
* _Bugfix_: The REST API returned incorrect avatar URLs for registered users (workaround for [trac ticket #40030](https://core.trac.wordpress.org/ticket/40030)).
* _Bugfix_: The gravatar use cookie is only set when the comment author has given consent.
* _Change_: Internal restructuring to make maintenance easier.

= 1.1.1 (2018-06-11) =
* _Bugfix_: Changing the default gravatar policy via `avatar_privacy_gravatar_use_default` works again for registered users.

= 1.1.0 (2018-06-10) =
* _Feature_: Supports the new privacy tools on WordPress >= 4.9.6 (export and deletion of personal data, suggested privacy notice text).
* _Feature_: Registered users can opt into allowing logged-out comments with the same mail address to user their profile pictures.
* _Feature_: The plugin is now compatible with bbPress.
* _Feature_: The position of the `use_gravatar` checkbox can be adjusted via the new filter hook `avatar_privacy_use_gravatar_position`.
* _Change_: Trashed comments and comments marked as spam do not trigger a validation request to Gravatar.com if the admin has set the default gravatar use policy to "enabled" via the filter hook `avatar_privacy_gravatar_use_default`.

= 1.0.7 (2018-06-06) =
* _Bugfix_: The `use_gravatar` is actually checked when the cookie has been set.
* _Bugfix_: A (harmless) PHP warning has been fixed.

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
