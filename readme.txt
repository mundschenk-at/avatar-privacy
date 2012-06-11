=== Avatar Privacy ===
Contributors: Ammaletu
Plugin Name: Avatar Privacy
Plugin URI: http://wordpress.org/extend/plugins/avatar-privacy/
Author URI: http://code.freudendahl.net/
Tags: gravatar, avatar, privacy
Requires at least: 3.2
Tested up to: 3.4
Stable tag: 0.2
License: GPLv2

Adds options to enhance the privacy when using avatars.


== Description ==

Avatars from gravatar.com are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users probably don't. Avatar Privacy can help to improve the privacy situation by making some subtle changes to the way avatars are displayed on your site.

The plugin works without changing your theme files if you use a modern theme, and it does support (simple) multisite installations. It requires at least PHP 5.2.4 and WordPress 3.2. For the plugin to do anything for you, you need to visit the discussion settings page in the WordPress admin area and save the new settings. Please note that the plugin does not provide an options page of its own, it rather adds to the existing discussion settings page.

= Features =

The plugin's features summed up. See following sections for longer explanations:

* Don't publish encrypted E-Mail addresses for non-members of gravatar.com.
* Let users and commenters opt in or out of using Gravatars.
* Use default avatar images hosted on your server rather than gravatar.com.

The plugin is currently available in these languages:

* English (by Ammaletu)
* German (by Ammaletu)

Contact me if you want to provide translations for other languages.

= In what way are avatars a privacy risk? =

To display an avatar image, you publish an encrypted version (MD5) of the E-Mail address in the gravatar's image URL. Gravatar.com then decides if there is an avatar image to deliver, otherwise the default image is delivered. The default image's address is also part of the overall gravatar image URL. Normally, both the avatar image and the default image are requested from gravatar.com servers. This process has the following problems:

1. MD5 is theoretically secure, but research has shown that it is possible to guess the E-Mail address from the MD5 token in the gravatar URL: [Gravatars: why publishing your email's hash is not a good idea](http://www.developer.it/post/gravatars-why-publishing-your-email-s-hash-is-not-a-good-idea). So there is a chance that you make your commenter's E-Mail addresses public.
2. The published avatar URL ties all comments made with the same (privately entered) E-Mail address together (publicly). The user might use different pseudonyms and web addresses with the comment, they even might want to stay anonym. But if the web site admin enables gravatars, even at a later point, all this user's comments can be recognized as being made by the same person. Creating such a comment profile for an E-Mail address is easiest for gravatar.com, they just have to look into their log files from where a particular image was requested (request header). That works for everyone, not only gravatar.com registered users. And of course, anybody else can program a bot to find occurences of a particular avatar URL throughout the web. The commenter most likely does not know what entering an E-Mail address means, usually is not told and has no control over whether a gravatar is displayed for his address or not.
3. Whenever someone visits the page, the avatar images are loaded from the gravatar.com servers into the visitor's browser. By doing so, gravatar.com gets all kind of data, e.g. the visitor's IP address, the browser version, and the URL of the page containing the avatar images. Since gravatars are used on many websites, if the visitor visits a lot of blogs while using the same IP address, the gravatar.com log files show exactly where the person using this IP address went.
4. If somebody wants to create fake comments using someone else's identity, this looks all the better with the matching gravatar image next to it. If you know the E-Mail address used for the comment, great. If not just create a new gravatar acount and upload the same picture.

= How does Avatar Privacy help with these problems? =

The plugin offers some measures to deal with these problems. It's not perfect or a complete solution, but some of the above points can be addressed sufficiently:

1. If you want gravatars, you don't really have a choice but to **publish the MD5 tokens** of the E-Mail adresses. If you want to have dynamic default images like the identicons, you also don't have a choice but to publish the MD5 tokens of all users, not only the users who actually signed up with gravatar.com (because the images are generated out of the E-Mail addresses). For gravatar.com users, you could of course request the images server-side and then cache them, but in my opinion that is a bit overkill. If somebody signs up with gravatar.com, they probably know that this means their E-Mail adresses will be published in encrypted form. The bad part is that this happens for everyone, even users who haven't ever heard of gravatar.com. That is an aspect that this plugin fixes with the 'Don't publish encrypted E-Mail addresses for non-members of gravatar.com' option. Why is this optional? The additional calls to gravatar.com from your server could in theory stress your server or make the page loading too slow. Please check this on a page with many comments. 
2. The problem of **tying comments throughout the web together** is addressed by the plugin in two ways: You can let commenters opt in or out of using gravatars with their E-Mail address. Aditionally, you can use a local default image and display the default image directly instead of as a redirect. This way the page optically looks identical, but comments of users who didn't sign up with gravatar.com are not linked through a unique avatar image URL anymore. For users who did sign up with gravatar.com, you should display a short message to the user somwhere around the comment form.
3. That gravatar.com is able to create **profiles of what websites you visited** is something that the plugin can't fix. Personally, I trust Auttomatic not to misuse this kind of data. I'm not even saying that they do create profiles, but technically they could. The profiles would be anonym unless they are connected with other data, like a provider's data who used a certain IP address at a certain point in time. Unfortunately, there is nothing that the plugin can really do about it, apart from complete caching solutions. This particular problem needs to be addressed by concerned visitors on their side, e.g. by using a TOR server to go online. Also, the whole modern web works this way, it's not a problem specific to gravatar.com. ;-)
4. The plugin does nothing against the **fake identity** problem. It's questionable if any countermeasures would even be possible without changing the way that gravatar.com works. Stealing identities is always possible, you can do it with a comment form without gravatars just as well. So that's not really the focus of this plugin.

= Feedback =

The plugin is still quite new. Please use it with caution and report any problems. You can use the contact form on [my code site](http://code.freudendahl.net/kontakt) or  create a forum topic on forum.wordpress.org with the tag [avatar-privacy]. I'll see these pop up in my feed reader and hopefully will reply shortly. ;-) You can contact me in German or English.


== Installation ==

= Installation =

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip the archive and put the 'avatar-privacy' folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

Be sure to visit the discussion settings page and configure the plugin. It won't do anything if you skip this step!

= Uninstallation =

There is a difference between deactivating the plugin and uninstalling it. The plugin gets deactivated if you do so on the plugins page or if you simply delete the plugin files via FTP. No uninstallation tasks are performed then, so you can activate and deactivate the plugin as you want without losing the plugin's settings.

If you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars. If you changed the default avatar to one of the new local avatar images, the default avatar image on your blog will revert to the gravatar logo.

If you want to completely uninstall the plugin and get rid of any data in the database, you should properly uninstall it: Deactivate the plugin first via the WordPress plugin page and then click 'delete' (same page, next to the plugin). For multisite installations, this has to be done by the network administrator on the network plugins page.

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). The following data are saved and deleted upon uninstallation:

* global table [prefix]_avatar_privacy
* usermeta value per user: use_gravatar
* option per blog: avatar_privacy_settings

The default avatar image is set to the mystery man if you selected one of the new local default avatar images.


== Screenshots ==

1. The new options on bottom of the Discussion Settings page.
2. The new default gravatars added by the plugin.
3. The option added to the comment form.
4. The option added to the user profile.
5. An example of a comment page, with explanations.


== Frequently Asked Questions ==

= I activated the plugin and don't see any change!? =

Did you remember to visit the discussion settings page, tick some or all of the checkboxes in the "Avatar Privacy" section and save the changes? On a multisite installation, this has to be done for every blog that wants to use the plugin. You also have to enable the use of gravatars first.

= I saved the settings and still don't see any changes. How do I know the plugin works? =

Depending on which options you selected, you wouldn't see a change in the way the page looks. The changes are visible in the source code though:

* Don't publish encrypted E-Mail addresses for non-members of gravatar.com: Look at the gravatar image URL of a user without a gravatar. The plugin works if the URL looks like "http://1.gravatar.com/avatar/[long MD5 token]?s=68" instead of "http://1.gravatar.com/avatar/[other long MD5 token]?s=68&d=http%3A%2F%2F1.gravatar.com%2Favatar%2F[long MD5 token]%3Fs%3D68&r=PG". There aren't two URLs in there anymore, only one, and the default URL looks the same for two comments without a gravatar.
* Let users and commenters opt in or out of using Gravatars: You should see the checkbox on the comment form. You need to log out though to see it. If you are logged in, you should see a similar checkbox in your user profile in the WordPress backend.

= I still don't see the checkbox in the comment form!? Everything else works. =

Then you probably don't use a modern theme which makes use of the function comment_form() to create the comment form. Check if you can find this function used in comments.php or a similar file of your theme. If you do and it still doesn't work, tell me. Otherwise chances are that you do have to add the checkbox manually. Use this function:

`<?php if (function_exists('avapr_get_avatar_checkbox')) echo avapr_get_avatar_checkbox(); ?>`

= I'm confused by all the settings. What should I select? =

For a maximum effect, check both "Don't publish encrypted E-Mail addresses for non-members of gravatar.com." and "Let users and commenters opt in or out of using gravatars.". Whether you want to enable the latter or not depends on whether you think this will demand too much from your potential commenters.

For a maximum privacy effect, select "The checkbox is... not checked by default". Then people wanting to use gravatars would actively have to tick this box. If you just want to give concerned visitors the chance not to display gravatars, but want to use gravatars for everyone els as a default, select "The checkbox is... checked by default".

The default value is necessary for older comments and user profiles that haven't been saved since activating the plugin. If you did have gravatars enabled before, choose "Show gravatars" here, otherwise "Don't show gravatars". If you are newly enabling gravatars on your site and have already lots of comments, you can of course select "Show gravatars", so that these comments won't look odd because none of them has a gravatar. It would be a bit unfair to your users though, since they commented when there weren't any gravatars on your site. For regular commenters, the gravatars will start to show up over time anyway, since the per-commenter setting of showing gravatars or not is per commenter, not per comment.

Last, scroll up a bit and select one of the local default avatar icons added to the bottom of the list. Their advantage is that together with the rest of the plugin options they can reduce (public) calls to gravatar.com. You are depending a bit less on an external resource and a bit less data flows to gravatar.com.

= What happens if I disable the plugin? Are any of the data altered? =

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). These data are deleted when you properly uninstall the plugin.

Apart from that, the plugin only filters data, but does not permanently change them. Especially, if you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars.

= Can this plugin be used together with cache plugins? =

Yes, it certainly can. You have to be careful though which plugin options you select and how you cache your content. The first plugin option checks if a gravatar exists for a given E-Mail address and, if not, displays the default image directly. If you cache the output of this check, the gravatar will not be displayed if the user later decides to sign up with gravatar.com. If you're using this option, you should invalidate cached pages with gravatars on them (mostly the single view of entries) regularly.

= Can this plugin be used on a multisite installation? =

Yes, the plugin can be used on a multisite installation. You can either activate it on individual blogs or do a network activation. Options will be set per blog, so the blog admins need to decide which options to use. What will be global is the table for the 'Let users and commenters opt in or out of using gravatars.' option: A global table 'wp_avatar_privacy' will be created that is shared across all blogs. So if a user comments on blog A and chooses to display gravatars, this decision will be followed on blog B and C too.

I develop and use this plugin on a multisite installation with three blogs. Any network with a comparatively small number of blogs should be fine. I haven't really thought about the implications of using the plugin on a network with many 'sites' (as opposed to 'blogs'). Does anybody even do that with WordPress?!

= Why is a minimal WordPress version of 3.2 required? Will it work with older WordPress installations? =

I chose WP 3.2 since that was the release that dropped support for PHP 4 and I didn't want to support that. While I'm writing the initial release of this plugin, WP 3.3 is the current release. I will be testing with WP 3.2, but not with older versions. It's reasonable to assume that it works with versions since WP 3.0 at least if you use PHP 5. There is a check in the main plugin file that checks for PHP and WP versions and doesn't load the plugin on older versions. If you absolutely must use it with older WP versions, comment out the lines after the 'check minimum WP requirements' comment.

= Won't spam comments flood the database table with useless entries for the checkbox in the comment form? =

The plugin doesn't save the value of the "use gravatar" checkbox for comments by registered users (see below), trackbacks/pingbacks (there is no E-Mail address) and comments that are marked as spam when they are saved. If you mark a comment as spam later, the table entry is not removed, since the same E-Mail address might also be used by non-spam comments. If a comment is marked as spam by Akismet or similar plugins and you later manually mark it as non-spam, what the user selected when submitting the comment will already be lost. This only happens with spam comments, not comments who just need to be moderated, e.g. because of the 'needs at least one published comment' rule.

= Can commenters override a registered user's choice whether to display a gravatar by creating fake comments? =

No, for registered users the user profile is checked, not the table for the commenter's choices. Commenters can not override this value, not even the user themselves if they post a comment when not signed-in.

= Which plugins are known to work with Avatar Privacy? =

I used Avatar Privacy together with these plugins:

* [AntiSpam Bee](http://wordpress.org/extend/plugins/antispam-bee/)
* [Twitter Avatar Reloaded](http://wordpress.org/extend/plugins/twitter-avatar-reloaded/)
* [User Photo](http://wordpress.org/extend/plugins/user-photo/) (worked on normal WP installation, haven't tried MultiSite; the plugin is a bit outdated and needs some general fixes)

If you find any problems with particular plugins, please tell me!


== Changelog ==

= 0.2 (2012-06-01) =
* Bugfix: lower-case and trim E-Mail addresses before hashing to produce a gravatar URL (otherwise gravatars are not displayed if the address is entered with mixed case) -- thanks to "Schokokaese" for finding the problem and solution
* Bugfix: repaired a bug so that the plugin actually caches the results of a gravatar check and uses these cached results if the same E-Mail address appears twice on a page
* Bugfix: corrected image name of the "Media Artist" image (large version)
* removed the check for the get_headers PHP function unless the "Don't publish encrypted E-Mail addresses for non-members of gravatar.com." option is enabled to not annaoy other users -- thanks to Scott for finding the problem
* added some simple inline CSS to fix the display of the checkbox in the comment form with TwentyTen theme
* fixed notice for deprecated function get_user_by_email
* added screenshots
* tested with WP 3.4
* tested with plugins User Photo and Twitter Avatar Reloaded

= 0.1 (2012-02-14) =
* initial release
