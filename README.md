# Avatar Privacy

Avatars from Gravatar.com are great, but they come with certain privacy implications. You as site admin may already know this, but your visitors and users probably don't. Avatar Privacy can help to improve the privacy situation by making some subtle changes to the way avatars are displayed on your site.

The plugin works without changing your theme files if you use a modern theme, and it does support (simple) multisite installations. It requires at least PHP 5.6 and WordPress 4.6. For the plugin to do anything for you, you need to visit the discussion settings page in the WordPress admin area and enable `Show Avatars`. Please note that the plugin does not provide an options page of its own, it rather adds to the existing discussion settings page.


### Features

The plugin's features summed up. See following sections for longer explanations:

*   Add local avatar caching to ensure the privacy of your website visitors.
*   Let users and commenters explicitly opt-in before using gravatars.
*   Don't publish encrypted e-mail addresses for people who are not members of Gravatar.com.
*   Use default avatar images hosted on your server rather than Gravatar.com.


### In what way are avatars a privacy risk?

To display an avatar image, you publish an encrypted version (MD5) of the e-mail address in the gravatar's image URL. Gravatar.com then decides if there is an avatar image to deliver, otherwise the default image is delivered. The default image's address is also part of the overall gravatar image URL. Normally, both the avatar image and the default image are requested from Gravatar.com servers. This process has the following problems:

1.  MD5 is theoretically secure, but research has shown that it is possible to guess the e-mail address from the MD5 token in the gravatar URL: [gravatars: why publishing your email's hash is not a good idea](http://www.developer.it/post/gravatars-why-publishing-your-email-s-hash-is-not-a-good-idea). So there is a chance that you make your commenter's e-mail addresses public.
2.  The published avatar URL ties all comments made with the same (privately entered) e-mail address together (publicly). The user might use different pseudonyms and web addresses with the comment, they even might want to stay anonymous. But if the web site admin enables gravatars, even at a later point, all this user's comments can be recognized as being made by the same person. Creating such a comment profile for an e-mail address is easiest for Gravatar.com, they just have to look into their log files from where a particular image was requested (request header). That works for everyone, not only Gravatar.com registered users. And of course, anybody else can program a bot to find occurrences of a particular avatar URL throughout the web. The commenter most likely does not know what entering an e-mail address means, usually is not told and has no control over whether a gravatar is displayed for his address or not.
3.  Whenever someone visits the page, the avatar images are loaded from the Gravatar.com servers into the visitor's browser. By doing so, Gravatar.com gets all kind of data, e.g. the visitor's IP address, the browser version, and the URL of the page containing the avatar images. Since gravatars are used on many websites, if the visitor visits a lot of blogs while using the same IP address, the Gravatar.com log files show exactly where the person using this IP address went.
4.  If somebody wants to create fake comments using someone else's identity, this looks all the better with the matching gravatar image next to it. If you know the e-mail address used for the comment, great. If not just create a new gravatar account and upload the same picture.


### How does Avatar Privacy help with these problems?

The plugin offers some measures to deal with these problems. It's not perfect or a complete solution, but some of the above points can be addressed sufficiently:

1.  All default images are hosted or generated **on your server** instead of at Gravatar.com.
2.  Only for users and commenters who **explicitly give their consent** will Gravatar.com be contacted to get their avatar image. This shares the MD5 hash of their e-mail address with Gravatar.com.
3.  To **prevent Gravatar.com from tracking your site's visitors**, these gravatars will be cached locally and the only IP address sent to Gravatar.com will be that of your server.
4.  Instead of MD5, Avatar Privacy uses a salted SHA256 hash for identifying avatars. This means that the published hashes **cannot be used to track people across the web**. (It also means that generated avatars will be different between websites.)
5.  The plugin does nothing against the **fake identity** problem. It's questionable if any countermeasures would even be possible without changing the way that Gravatar.com works. Stealing identities is always possible, you can do it with a comment form without gravatars just as well. So that's not really the focus of this plugin.


### Uninstallation

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


### I activated the plugin and don't see any change!?

Did you remember to visit the discussion settings page, activate 'Show Avatars'? You have to enable avatars for this plugin to be able to do anything.


### I saved the settings and still don't see any changes. How do I know the plugin works?

Depending on which options you selected, you might not see a change in the way the page looks. The changes are visible in the source code though:

*   Look at the gravatar image URL of a user without a gravatar. The plugin works if the URL looks like `[your site]/wp-content/uploads/avatar-privacy/cache/gravatars/[x]/[y]/[long SHA256 token]-68.png` instead of `http://1.gravatar.com/avatar/[other long MD5 token]?s=68&d=http%3A%2F%2F1.gravatar.com%2Favatar%2F[long MD5 token]%3Fs%3D68&r=PG`. There aren't two URLs in there anymore, only one, and the default URL looks the same for two comments without a gravatar.
*   You should see the checkbox on the comment form. You need to log out though to see it. If you are logged in, you should see a similar checkbox in your user profile in the WordPress backend.


### I still don't see the checkbox in the comment form!? Everything else works.

Then you probably don't use a modern theme which makes use of the function `comment_form()` to create the comment form. Check if you can find this function used in `comments.php` or a similar file of your theme. If you do and it still doesn't work, tell me. Otherwise chances are that you do have to add the checkbox manually. Use this function:

```php
<?php if (function_exists('avapr_get_avatar_checkbox')) echo avapr_get_avatar_checkbox(); ?>
```


### What happens if I disable the plugin? Are any of the data altered?

The plugin saves additional data about whether commenters and users want to display a gravatar or not (if you select that mode in the settings). These data are deleted when you properly uninstall the plugin.

Apart from that, the plugin only filters data, but does not permanently change them. Especially, if you deactivate the plugin und have gravatars turned on, they will again show up for everybody, even those commenters and users who opted out of displaying gravatars. You do have to change the default gravatar back manually, though.


### Can this plugin be used together with cache plugins?

Yes, it certainly can. You have to be careful though which plugin options you select and how you cache your content. The first plugin option checks if a gravatar exists for a given e-mail address and, if not, displays the default image directly. If you cache the output of this check, the gravatar will not be displayed if the user later decides to sign up with Gravatar.com. If you're using this option, you should invalidate cached pages with gravatars on them (mostly the single view of entries) regularly.


### Can this plugin be used on a multisite installation?

Yes, the plugin can be used on a multisite installation. You can either activate it on individual blogs or do a network activation. As users are global to a multisite installation, their choice regarding Gravatar.com use will affect all sites in the network. So if a user comments on blog A and chooses to display gravatars, this decision will be followed on blog B and C too. On new installations, comment author (i.e. non-user) opt-in is recorded per site, not per network. If you first installed Avatar Privacy 0.4 or earlier, the global table `wp_avatar_privacy` continues to be used for all sites in the multisite network. This behavior can be overriden by the network admin via the filter hook `avatar_privacy_enable_global_table`.


### Won't spam comments flood the database table with useless entries for the checkbox in the comment form?

The plugin doesn't save the value of the "use gravatar" checkbox for comments by registered users (see below), trackbacks/pingbacks (there is no e-mail address) and comments that are marked as spam when they are saved. If you mark a comment as spam later, the table entry is not removed, since the same e-mail address might also be used by non-spam comments. If a comment is marked as spam by Akismet or similar plugins and you later manually mark it as non-spam, what the user selected when submitting the comment will already be lost. This only happens with spam comments, not comments who just need to be moderated, e.g. because of the 'needs at least one published comment' rule.


### Can commenters override a registered user's choice whether to display a gravatar by creating fake comments?

No, for registered users the user profile is checked, not the table for the commenter's choices. Commenters can not override this value, not even the user themselves if they post a comment when not signed-in.


### Which plugins are known to work with Avatar Privacy?

I used Avatar Privacy together with these plugins:

*   [AntiSpam Bee](http://wordpress.org/extend/plugins/antispam-bee/)

If you find any problems with particular plugins, please tell me!
