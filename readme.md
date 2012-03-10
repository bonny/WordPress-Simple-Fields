=== Plugin Name ===

Contributors: eskapism, MarsApril, samface, angrycreative

Donate link: http://eskapism.se/sida/donate/

Tags: admin, fields, custom fields, field manager, attachments, text areas, input fields, tinymce, radio button, drop down, files, meta box, edit, post, post_meta, post meta, custom

Requires at least: 3.3.1

Tested up to: 3.3.1

Stable tag: 0.5

Add different kind of input fields to your edit post page. Field can be of type textarea, TinyMCE, checkbox, radio buttons, drop downs or files.

== Description ==

Simple Fields for WordPress let you add groups of fields to your edit post page.

Simple Fields turns WordPress into an even more powerful Content Management System (CMS).

#### Features and highlight

* Add textboxes, text areas, checkboxes, radio buttons, dropdowns, and file browser to the post admin area
* Much easier for the user than regular custom fields
* Group fields together into logical groups. For example combine File + Name + Description into a an Attachments-group, that let's you add multiple files to your posts
* Use "repeatable" field groups to add many any amount of field groups to a single post (great for images or attachments!)
* Use drag and drop to change order of added repeatable groups
* Different post types can use different field groups - actually you can even use different field groups even for same post type
* Can be used on any post type, including custom post types
* Nice GUI that looks like it belongs to the regular WordPress GUI

For more information check out my introductory blog post:
http://eskapism.se/blogg/2010/05/simple-fields-wp-custom-fields-on-steroids/

Also check out this short tutorial:
http://eskapism.se/code-playground/simple-fields/tutorial/

#### Beta? You bet!

Please note that this plugin still is in a very early version. Please try it out but be aware of bugs. 
Also, please remember to backup your database, just to be sure if anything goes wrong.
For bugreports, feature request and so on, please contact me at par.thernstrom@gmail.com or through twitter
http://twitter.com/eskapism/.

#### Help and Support
If you have questions/bug reports/feature requests for Simple Fields please use the WordPress [Support Forum](http://wordpress.org/tags/simple-fields?forum_id=10).
There are also [tutorials available for Simple Fields](http://eskapism.se/code-playground/simple-fields/).

#### Donation and more plugins
* If you like this plugin don't forget to [donate to support further development](http://eskapism.se/sida/donate/).
* Check out some [more plugins](http://wordpress.org/extend/plugins/profile/eskapism) by the same author.


== Installation ==

As always, make a backup of your database first!

1. Upload the folder "simple-fields" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Start poking around with Simple Fields under Settings > Simple Fields
1. Get help at http://eskapism.se/code-playground/simple-fields/ and ask your questions at http://wordpress.org/tags/simple-fields?forum_id=10
1. That's it; you know have a super cool and powerful CMS based on WordPress!


== Screenshots ==

1. A post in edit, showing two field groups: "Article options" and "Article images".
These groups are just example: you can create your own field groups with any combinatin for fields.
See that "Add"-link above "Article images"? That means that it is repeatable, so you can add as many images as you want to the post.

2. One field group being created (or modified).

3. Group field groups together and make them available for different post types.


== Changelog ==

#### 0.5
- Added: New function: simple_fields_query_posts(). Fetch and sort posts by simple fields value.
- Fixed: An incompatibility with Wordpress 3.3 prevented saving of default post connector.
- Fixed: An incompatibility with the new TinyMCE in Wordpress 3.3 caused the HTML-editor to not show.
- Fixed: An incompatibility with the new media uploader in Wordpress 3.3 caused the "insert into post"-button to not show in the media library.

#### 0.4 
- Massive update!
- Backup: Lots of new stuff in this version of the plugin, so pleeeeeeease make sure you backup your stuff before installing this. Things may be borked.
- Added: new field type: taxonomy term! Select taxonomy terms, from any taxonomy in the system. The development of this field type was sponsored by http://plucera.se. Thanks guys!
- Added: new field type: taxonomy! Select a taxonomy from a drop down with all the taxonomies in the system. The development of this field type was sponsored by http://plucera.se. Thanks guys!
- Added: new field type: post! Select a post from one or several post types. The development of this field type was sponsored by http://plucera.se. Thanks guys!
- Added: new field type: user! Select a user from a dropdown of all users in the system.
- Added: now it's possible to add a description to each field. It will be displayed under the field name, in italic and a bit brighter.
- Added: new field type: color! Let's you pick a color using a nice colorpicker from http://jscolor.com/
- Added: new field type: date! Let's you pick a date using a nice date picker from http://www.kelvinluck.com/assets/jquery/datePicker/v2/demo/
- Added: option to hide the built-in wordpress editor (so you can show only fields from simple fields for a post or page)
- Added: Field type file now has a edit-link so it's easy to edit the attachment. Previosly you had to search for the name in the media library = it was very cumbersome. Now = super easy!
- Fixed: last group of a repeatable group could not be deleted under some circumstances
- Fixed: TinyEditor/HTML-editor now remembers line breaks and stuff when switching between visual and HTML.
- Fixed: if "inherit" was used as the simple fields for a post and the post was saved it would get the inherited connector as the saved one. should be kept "inherit" so it's easier to change the connector of a lots of post (by just changing the connector of the parent)
- Added: the name of the inherited connector is now shown when editing a post
- Fixed: jquery ui core and effect highligt are now loaded locally. googlecode.com was awfally slow at times.
- Fixed: field groups, fields, and connector had problems with apostrophes and similar chars.
- Fixed: post did not get inherit as connector, even if it was set to that is post type defaults
- Fixed: full screen disabled for field type tinymce/html since it updated the wrong textarea/tinymce.
- Updated: jquery.effects.core and jquery.effects.color updated to latest version
- Finally: probably lots of other stuff has been fixed too. And if you like this plugin, please consider donating or thanking me in some other way. Looooots of time has been spent on this plugin. And when I mean lots of time, I really mean like hundreds of hours.


#### 0.3.9
- Added: debug page
- Fixed: If a field group was renamed, that was not reflected in the post connector edit screen
- Fixed: some notice-errors (the wp debug bar is wonderful, btw!)
- Fixed: Sometimes a deleted field group would still show up when editing a post

#### 0.3.8
- Better handling of international chars when selecting file
- html_esc on file names
- Repeatable fields did not work in Safari

#### 0.3.7
- Now more localized than ever before. Lots of thanks to Ricardo Tomasi who made the required changes.
- Added Brazilian translation, also by Ricardo Tomasi.
- Added donate-sidebar

#### 0.3.6
- Removed some old code that had security issues. You should update to this version as soon as possible.

#### 0.3.5
- Think I broke the regular media browser witht he last update. Should be fixed now. Sorry everyone!

#### 0.3.4
- effects.core.js and effects.highlight.js actually points to existing files now
- adding repeatable field groups would "hang" due to the fact that effects.highlight was missing. so should be fixed now too.
- media browser would hang on chrome (and safari too i guess). should be fixed now.

#### 0.3.3
- Use jquery-ui version 1.7.3 instead of 1.8.1, since that's the version otherwise used by WordPress.
- If FORCE_SSL_ADMIN is set to true, jquery-ui-stuff is loaded from Google through HTTPS instead of plain HTTP. Please let me know if this solves the problems some of you had.
- removed post_id from media select querystring. should make it not add the selected image to the gallery/attach it to the post
- media browser: filter and search now works
- media browser: finally managed to change the name of the "insert into post"-button. Code gracefully stolen/inspired by the Attachments-plugin (http://wordpress.org/extend/plugins/attachments/)
- uses nonce when saving. should fix a couple of bugs, for example post connector being reseted
- if multiple file fields where in a single group, clearing one file would clear them all
- Hopefully fixed some more stuff that I can't remember. ..and probably broke some stuff too. Make a backup before installing, people! And let me know of any bugs you find!

#### 0.3.2
- Fixed a problem with checkboxes and multiple fields (as reported here: http://eskapism.se/code-playground/simple-fields/comment-page-1/#comment-73892). I hope. Please make sure you make a backup of your database before upgrading. Things may go boom!

#### 0.3.1
- simple_fields_get_post_group_values would return an array with one element with a value of null, if a repeatable field group did not have any added items. kinda confusing.
- fixed a couple of undefined index-errors

#### 0.3
- Field type file now uses wordpress own file browser, so upload and file browsing should work much better now. If you still encounter any problems let me know. Hey, even if it works, please let med know! :)
- Media buttons for tiny now check if current user can use each button before adding it (just like the normal add-buttons work)

#### 0.2.9
- Fixed a JavaScript error when using the gallery function
- Fixed a warning when using simple_fields_get_post_value() on a post with no post

#### 0.2.8
- fixed errors when trying to fetch saved values for a post with no post_connector selected
- tinymce-fields can now be resized (does not save them correctly afterwards though...)
- uses require_once instead of require. should fix some problems with other plugins.
- clicking on "+ Add" when using repeatable fields the link changes text to "Adding.." so the user will know that something is happening.
- removed media buttons from regular (non-tiny) textareas
- tiny-editor: can now switch between visual/html

#### 0.2.7
- file browser had some <? tags instead of <?php
- Could not add dropdown values in admin

#### 0.2.6
- media buttons for tinymce fields
- fixed some js errors
- content of first tinymce-editor in a repeatable field group would lose it's contents during first save
- drag and drop of repeatable groups with tinymce-editors are now more stable
- code cleanup
- filter by mime types works in file browser

#### 0.2.5
- used <? instead of <?php in a couple of places
- now uses menu_page_url() instead of hard-coding plugin url
- inherited fields now work again. thanks for the report (and fix!)
- p and br-tags now work in tiny editors, using wpautop()
- moved some code from one file to another. really cool stuff.

#### 0.2.4
- file browser: search and filter dates should work now
- file browser: pagination was a bit off and could miss files

#### 0.2.3
- some problems with file browser (some problems still exist)
- added a "Show custom field keys"-link to post edit screen. Clicking this link will reveal the internal custom field keys that are being used to save each simple field value. This key can be used together with for example get_post_meta() or query_posts()
- code cleanups. but still a bit messy.
- removed field type "image". use field type "file" instead.

#### 0.2.2
- can now delete a post connector
- does no longer show deleted connectors in post edit

#### 0.2.1
- works on PHP < 5.3.0

#### 0.2
- Still beta! But actually usable.
- added some functions for getting values

#### 0.1
- First beta version.

