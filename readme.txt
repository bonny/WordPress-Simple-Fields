=== Simple Fields ===
Contributors: eskapism, MarsApril, samface, angrycreative, Earth People
Donate link: http://simple-fields.com/about/donate/
Tags: admin, fields, custom fields, advanced custom fields, extended custom fields, more fields, repeatable fields, field manager, attachments, text areas, input fields, tinymce, radio button, drop down, files, meta box, edit, post, post_meta, post meta, custom, simple fields, cms, date picker, time picker, repeatable fields, multiple fields
Requires at least: 3.5.1
Tested up to: 3.5.1
Stable tag: 1.4.2

With Simple Fields you can add any kind of custom fields to your pages, posts and attachments.

== Description ==
The built in custom fields in WordPress are great, but they do come with a big limitation: they only support plain text. To overcome this limitation I created the Simple Fields WordPress plugin.

With Simple Fields you can add for example textboxes, text areas, checkboxes, radio buttons, dropdowns, and file browser to the post admin area. These fields are all much easier to use for the user than regular custom fields.

All fields are grouped into logical groups called Field Groups. You can for example combine File + Name + Description into a an Attachments-group, that lets you add multiple files to your posts.

Use "repeatable" field groups to add many any amount of field groups to a single post (great for images or attachments!)

Simple Fields can be used on any post type, including custom post types.

The saved values are easy get in your theme or functions.php-file. Like this:

`
<?php
// Get the saved value for a field called "video"
$video_url = simple_fields_value('video');
?>
`

= Field Types =

Simple Fields comes bundled with a useful variation of fields. Note that any field can be added any number of times to a post using repeatable fields.

Fields types available in Simple Fields:

* **Text**<br>
A simple text input to enter any kind of information.

* **Textarea**<br>
A bigger area for inputing text. Even support WYSIWYG/TinyMCE-mode that makes it work like the regular content editor, so you can insert images, headlines, list, paragraphs and so on.

* **Checkbox**<br>
A simple checkbox to be able to select something on/off.

* **Radio Buttons**<br>
Add multiple radiobuttons where a user can select one of the buttons. Useful for letting a user choose between multiple options.
 
* **Dropdown**<br>
Add multiple values to a dropdown box. User can select one or several items in the list. Useful for letting your users select one or severall things.

* **File**<br>
Select any file using the built in file/media browser in WordPress. Since it's using the built in media * browser you can also upload new images/attachments to your posts. Using this field together with repeatable field groups = very powerful! :)

* **Post**<br>
Select any post that exists in WordPress. Can be pages or any custom post type.

* **Taxonomy**<br>
Select a taxonomy from a list of taxonomies.

* **Taxonomy Term**<br>
Select a term from a taxonomy.

* **Color**<br>
Show a color picker where the user can choose any color. The color can also be entered manually, if the user knows the hex value of the color.

* **Date and Time**<br>
Chose a date and optionally time from a JQuery UI date and time picker.
 
* **User**<br>
Choose a user from the system.
 
See the [field documentation](http://simple-fields.com/documentation/field-types/) for more details about the different fields.
 
= Use Field Type Extensions to add your own field types =

If you miss a field type in Simple Fields you can use [Field Extensions](http://simple-fields.com/extensions/) to add more field types. These are fields that are created by other developers and shared with the Simple Fields community.

if you want to create your own field you can do that using the [Extension API](http://simple-fields.com/documentation/api/extension-api/).

= Repeatable fields =

Often just a single field is not enough. Why limit yourself to just one image or one attachment? With Repeatable Fields you can add as many images, text fields, textareas, or any other field type, as you want to to a post. This is a very useful feature when you want to create for example image slideshows or pages with many file attachments.

Add attachments and image slideshows in a snap.

Also, the fields in a a repeatable field group are easily sorted using drag and drop.

= Simple PHP functions to speed up your development =

`
<?php
simple_fields_value("field_slug");
simple_fields_values("field_slug_1, field_slug_2, field_slug_n");
?>
`

= Works with all post types =

With Simple Fields you can add fields to both regular pages and posts and to any custom post type.

Different post types can use different field groups - actually you can even use different field groups even for same post type, on a post to post basis.

= Unit testing to minimize risk of errors =

A lot of the functionality in Simple Fields is tested using unit testing. Test cases exists for all the functions that involve getting field values from your posts. This way the risk of anything breaking on a site after upgrade in minimized.

= Help and Support =

If you have questions/bug reports/feature requests for Simple Fields then:

* use the [WordPress Support Forum](http://wordpress.org/support/plugin/simple-fields) 
* visit the [GitHub project page for Simple Fields](http://github.com/bonny/WordPress-Simple-Fields)
* view the [getting started guide](http://simple-fields.com/documentation/getting-started/)

= Donate to keep this plugin free =
 * If you like this plugin don't forget to [donate to support further development](http://simple-fields.com/about/donate/).

== Installation ==

As always, make a backup of your database first!

1. Upload the folder "simple-fields" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Start poking around with Simple Fields under Settings > Simple Fields
1. Get help at http://simple-fields.com/documentation/ and ask your questions at http://wordpress.org/support/plugin/simple-fields
1. That's it; you know have a super cool and powerful CMS based on WordPress!


== Screenshots ==

1. A post in edit, showing two field groups: "Article options" and "Article images".
These groups are just example: you can create your own field groups with any combinatin for fields.
See that "Add"-link above "Article images"? That means that it is repeatable, so you can add as many images as you want to the post.

2. One field group being created (or modified).

3. Group field groups together and make them available for different post types.

4. A field group with some HTML5 input types date, url, color, email and range added.

5. The settings screen, as it looks on http://simple-fields.com. And yes, that's the real fields I use on the domain for this plugin :)


== Changelog ==

#### Version 1.4.2
- Post Connectors can now be set from within a page template. Just add
Simple Fields Connector: slugOfPostConnector
to your template and that connector will be used for all pages with that template.
- New filters as a result from above: set_post_connector_from_template, get_post_connector_from_template

#### Version 1.4.1
- Bugfix for WPML and "Call to undefined function"-errors

#### Version 1.4

- Added support for [WPML](http://wpml.org/). Now the names and descriptions of field groups, their fields, and post connectors, and values of drop downs and radiobuttons can be translated using WPML.
- Added a little teaser about [WordPress Web Agency Earth People](http://earthpeople.se/?utm_source=wordpress&utm_medium=readme&utm_campaign=simplefields), the company I work for. If you need any professional help with for example Simple Fields or WordPress in general then we might be the ones to talk to :)

#### Version 1.3.4

- Fixed some PHP strict standards warnings

#### Version 1.3.3

- Added support for using "sf_meta_key" as an argument to regular wp_query. just add sf_meta_key = "field_group_slug/field_slug" to the args of wp_query and then the argument meta_key will automatically be filled with the correct custom field key to use for that field. Useful when you need to for example sort things based on simple fields in a wp_query. Please note that different field types store their values in different ways, but it works really good for plan text, date/timepicker, and probably some more too.

- Added method get_field_by_fieldgroup_and_slug_string() that can retrive a field array based on a field group slug and a field slug. like this: $sf->get_field_by_fieldgroup_and_slug_string("my_fieldgroup_slug/my_field_slug");

#### Version 1.3.2

- Updated field extension example with code to notify user that they must have simple fields
installed to use the plugin

- simple_fields_register_post_connector can now accept a string in the post_types argument, 
if only one post type is to be connected

- fixed a problem with the date/timepicker v2 not being se correctly when option "no date" was selected

#### Version 1.3.1
- Fixed: used short tags in options screen.

#### Version 1.3
- Added: tabs! in the gui that is.
- Added: tab with export and import functions!
- Added: notice in GUI when editing field group or post connctor that has been added with PHP code, because if you try to change that field group/post connector then the changed may not sticks since it gets overwritten by php code.
- Fixed: could not add fields with ajax when plugin All-in-One Event Calendar was installed
- Added: developers can now add their own tabs to the simple fields options page
- Added: some new filters and actions: simple_fields_settings_admin_head, simple_fields_after_last_options_nav_tab, simple_fields_subpage, simple_fields_subpage_<subpage-name>. Check inc-admin-options-export-import.php for an example how to use these filters and actions.
- Fixed: empty/not saved post connectors could be visible in GUI
- Fixed: misc GUI changes here and there
- Fixed: filter simple_fields_get_meta_key_template now fully works, so you for example can use it to enable storing fields by its slugs instead of ids. useful when ids have been messed up between development server and production server. also useful when working with import and export, since ids are easily increased.

#### Version 1.2.4
- Fixed: was unable to enable use html editor when using gui

#### Version 1.2.3
- Fixed: could not unset option use_html_editor for textarea field type. props Hayden.
- Fixed: javascript in editor would break in some situations.

#### Version 1.2.2
- Fixed: file field did not work on custom post types that did not have an editor

#### Version 1.2.1
- Added: you can now add custom attributes to text and HTML5 fields. Like "required" or "pattern".
- Fixed: a console.log was of course left in the code... :/ could make the file field not work.

#### Version 1.2
- (Lotsa things modified, so please make a backup of your database before upgrade. I haven't had any problems at all, but... please be safe out there!)
- Added support for the new media manager that was introduced in WordPress 3.5 (yes, finally!)
- Added support for HTML5 input types like color, date, range, email, url. Bevare, support depends on the browser. These are subtypes of the text field, since browsers do fall back to text field if they don't support the new fancy input type.
- Added support for adding fields to attachments, attachments now works the way as regular posts: just any amount of fields to an attachment.
- Fixed bug with repeatable fields containing tiny mce-editors (textarea with wysiwyg/html-capabilities). Fixes https://github.com/bonny/WordPress-Simple-Fields/issues/73.
- Added a new view for repeatable field groups on the edit post screen: table. It's a much more compact view with a greater overview of the added field. Mostly suitable for field groups with between 1 and 10 fields.
- Added support for placeholder text on text and textarea fields
- Added debug panel to debug bar (if installed). Makes it possible to preview stored values when inside WordPress admin and editing posts. Will automatically be enabled when debug bar is installed and activated.
- Changed debug box to stop using jQuery, so it should work with more themes and in more situations where jQuery may not be available
- Fixed bug with post field type dialog
- Fixed passing additional arguments for field type post
- Fixed memory leak when using cache. When using functions that cleared the cache, for example simple_fields_set_value(), then memory usage could increase pretty much, and when the function is used in a loop then eventionally the script would eat up all memory. Nom nom nom. But in a bad way.
- Changed the way some cache keys where stored, beacuse a key in an array that contains quotes is just silly looking (but valid, apparently)
- Little better looking output of "Show custom field keys"
- Fixed ordering of fields when using simple_fields_register_field_group
- Fixed bug with post field and extended return values, 
  where a field with no post/page selected would return the post of the current post in the loop. 
  Now returns empty array instead. Thanks to [hjalle](https://twitter.com/hjalle) for finding.
- simple_fields_register_post_connector now uses name from each field group, so no need to enter that manually any more (if entered manually, it will be removed)
- Added filters and actions that you can use in your functions.php-file or in your plugin or field extension or whatever. Added filters are:
  simple_fields_add_post_edit_side_field_settings<br>
  simple_fields_get_selected_connector_for_post<br>
  simple_fields_debug_output<br>
  simple_fields_get_post_value<br>
  simple_fields_get_post_group_values<br>
  simple_fields_get_all_fields_and_values_for_post<br>
  simple_fields_get_meta_query<br>
  simple_fields_query_posts<br>
  simple_fields_values<br>
  simple_fields_value<br>
  simple_fields_connector<br>
  simple_fields_fieldgroup<br>
  simple_fields_get_slug_pattern<br>
  simple_fields_get_default_connector_for_post_type<br>
  simple_fields_get_post_type_defaults<br>
  simple_fields_get_field_groups<br>
  simple_fields_get_field_group<br>
  simple_fields_get_field_in_group<br>
  simple_fields_get_pages_args<br>
  simple_fields_get_pages_output<br>
  simple_fields_get_post_connectors_for_post_type<br>
  simple_fields_get_options<br>
  simple_fields_save_options<br>
  simple_fields_get_extended_return_values_for_field<br>
  simple_fields_get_field_group_by_slug<br>
  simple_fields_clear_caches<br>
  simple_fields_get_meta_key_template<br>
  simple_fields_get_meta_key<br>
- Added method get_meta_key(). Use it to retrieve the key that simple fields uses for meta/custom fields storage.
- Added "meta_key" as key to all fields when using get_field_by_slug() somewhere. 
  Makes it easy to know/get the meta key if you need it. returns the meta key for the first field in that field group. 
  If group is repeatable you must add number to numInSet yourself. Will contain a value like "_simple_fields_fieldGroupID_1_fieldID_2_numInSet_0"
- Fixed so plugin works with jQuery 1.9, because now jQuery(htmlString) requires first char to be < for string to be considered HTML. Was not working due to spaces before first HMTL tag.
- Added Slovak translation by Branco. Thanks a lot!
- Added nonces to admin to prevent CSRF
- Added table view to admin instead of ul-li-lists. Makes the admin looks nicer and more WordPress-ish.
- Lotsa code cleanups and stuff and unit tests added and a bit of this and also a bit of that

#### Version 1.1.6
- Fixed bug that could lead to memoryleak. Thanks to plux/angry creative for patch.
- Fixed some notice messages
- Changed CDN for Date Picker 2 to use Google instead of Microsoft, because the one from Microsoft - ajax.aspnetcdn.com - was always slow for me + it have been completely down too.
- Fixed problem with slug for field group info inside a field
- Perhaps fixed a problem with the wp object cache
- Updated readme to contain lots of more information

#### Version 1.1.5
- Added "view" link to file field, so you can view images/attachments/pdfs/whatever directly from the edit post screen.

#### Version 1.1.4
- Added support for dropdowns with multiple selected values. Just check "multiple" in the settings for the dropdown field and voila!
- Changed all jQuery javascript live events to on events, because live is deprecated.
- Removed several javscript actions that were called on edit post page. Hopefully makes the edit post screen a bit quicker when working with repeatable custom fields
- Probably some misc stuff I don't remember

#### Version 1.1.3
- Fixed date and time picker not working in Firefox (I spend way too much time in Chrome nowadays...)
- Changed date and time picker to use simplified ISO 8601 return format
- Changed repeatable fields to be a bit more nice looking, IMHO anyway :)
- Changed file field to show "edit" and "remove"-links only on mouse over. Yes, I really want less clutter in my plugin.
- Fixed some notice warnings
- Fixed: use built in function to remove meta instead of direct sql calls. fixes some problem in wp 3.5b. And it feel a lot less dirty.
- Misc other stuff

#### Version 1.1.2
- Fixed script error (sortable not found) on settings page.
- Fixed problem with return values, field divider gave an error...
- Fixed wrong position of repeater drag handle (wrong position on repeating fields)
- Added: method edit_save() for Field Extensions API. Let you modify the values before being saved in database. (Actually this was added in last version, but I forgot about it then..)

#### Version 1.1.1
- Fixed: styles and scripts where not outputed on all post types

#### Version 1.1
- Added: new field type "Date and Time Picker". It's a better version of the Date field. With this new field type you can choose to have a date picker, time picker, or a picker with both date and time. The saved values are stored in ISO 8601-format in the database, so they should be fine to sort posts by when using wp_query.
- Addded: action "simple_fields_admin_head". Use it to add content to the head of admin pages that use simple fields, i.e. the edit post screen. This action is better than admin_head because it's only fired on pages that use simple fields, so all other admin pages will be faster since they don't have to parse any unnecessary.
- Added: action "simple_fields_enqueue_scripts". Same as above, but used to enqueue scripts.
- Fixed: don't output debug info when calling the_excerpt()
- Changed: some GUI changes, like moving the description to below the labels on edit post screen. It became to inconsistent to have the description at different places for different field types.
- Changed: only load styles and scripts on screens that use simple fields. Should speed up other parts of WordPress a little bit.

#### Version 1.0.6
- Added: date_i8n-format for date field when using extended return values
- Added: support for extended return values for function simple_fields_get_post_value()
- Fxed: added group to cache functions + clears the cache when using the register-functions
- Fixed: file dialog javascript now checks that the pagenow variable exists before trying to use it. Hopefully fixes problems with Post Thumbnail Editor
 and other plugins that also use the file dialog.
- Changed: hide "show time" for date field, since we don't use it for anything

#### Version 1.0.5
- Added: field group slug to field group wrapper css + field wrapper css on edit post screen, so you can style different field groups differently.
- Added: new method: get_field_group(): returns an array with info about a field group by using id or slug
- Added: new function: simple_fields_fieldgroup(). Works like simple_fields_value(s) but for a complete fieldgroup. 
Pretty good "shortcut" when you want to get all the field values in a field group.
- Added: new method get_field_by_slug. Returns a field from a fieldgroup using their slugs.
- Fixed: Added wp_cache to some very commonly used functions. Quick tests with Xdebug shows a noticable faster performance.
- Changed: method get_field_groups() adds a key with name field_group with some info about the field group that the field belongs to. 
Useful since sometimes when you use for example get_field_by_slug() to get a single field, you want to know some basic info about the 
field group it belongs to, like the id or slug.
- Changed: function sf_d also shows if a variable is a boolean.

#### Version 1.0.4
- Added: Add button added to bottom of the added field groups. Will be visible when more than 1 field group is added.
- Changed: moved to a two column mode/appearance. Makes the fields take upp less space, and making it more clutter free.
- Changed: minor CSS fixes here and there

#### Version 1.0.3
- Added: Added options for returning values as "Extended Return Values". 
Very useful when working with for example files: 
instead of just the ID of the file you get the id, the full path to the file, the dimensions if it's a image, and more. 
This feature is available for these field types: 
file, radiobutton, dropdown, post, user, taxonomy, taxonomy term and date. 
Please see the [documentation for Extended Return Values](http://simple-fields.com/documentation/api/extended-return-values/) for more information and examples.
- Added: option to set the height for textarea fields (inlcuding HTML/TinyMCE-editor-mode)
- Added: new field type: divider. Useful if you have a field group with many fields. By adding the divider field to a field group it simply created a shite space or a space with a line. It's written using the new Extension API btw, so feel free to check out the source if you want to create something similar yourself.
- Changed: The debug output also includes example on how to get all field in a field group at once.
- Changed: The edit field group admin page now is a bit more compact. Makes easier to get an overview of all the added fields.

#### Version 1.0.2
- Changed: Don't load example field types
- Fixed: jQuery is needed for debug output but was not enqueued
- Added: French translation. Thank you very much, translator!
- Small bugfixes
- Added unit tests. Hopefully this makes it easier for me to spot bugs in the future. Btw: these are the first unit tests I've ever written, so please feel free to comment on the way I've done it! :)

#### Version 1.0.1
- Fixed: some warning and notice-errors, for example when a post connector did not have any field groups connected

#### Version 1.0
- Note: This is a pretty big update, so please backup your databases before installation!
- Added: Support for adding Custom Field Types/Field Type extensions. Makes Simple Fields Endless Extendable! :)
- Added: New functions for registering field groups and fields in php, see example usage.
- Added: New better/easier functions for getting the saved values for a post: simple_fields_value() and simple_fields_values()
- Added: Field slugs and field group slugs. Now you can use the slug instead of the id to get the values.
- Added: Added CSS classes to field groups in admin area, so developers can style things there.
- Added: Actions and filter so developers can modify parts of Simple Fields behavior.
- Added: Functions for getting the connector for the current post. See simple_fields_connector() and simple_fields_is_connector().
- Added: Function to set value, see simple_fields_set_value()
- Added: German translation by Johann Heyne (http://johannheyne.de). Thanks a lot!
- Fixed: Simple Fields is now mostly a class.
- Fixed: Various bugfixes.

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

