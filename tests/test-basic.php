<?php

class BasicTest extends SimpleFields_TestCase {

	/*
	setUpBeforeClass
	tearDownAfterClass
	setUp
	tearDown
	*/
	public static $post_id_for_manual_tests;

	function testSetupCorrectly() {

		$this->assertTrue( class_exists("simple_fields"), "Simple Fields class must exist" );
		$this->assertTrue( isset( $GLOBALS['sf']), "Global variable must be set" );

	}

	function testCache() {

		// cache namespace key must be numeric
		$this->assertTrue( is_numeric( self::$sf->ns_key ) );
		$old_ns = self::$sf->ns_key;

		// clear caches and check that key is increased
		self::$sf->clear_caches();
		$this->assertTrue( is_numeric( self::$sf->ns_key ) );
		$this->assertTrue( self::$sf->ns_key > $old_ns );

		// sf_d( self::$sf->ns_key );

		// check that cache stores and gets things correctly
		$key = 'simple_fields_unittest_' . self::$sf->ns_key . '_cache_test';
		$group = "simple_fields";
		$val = rand_str();
		$this->assertFalse( wp_cache_get( $key, $group), "Cached value must not exist" );
		wp_cache_set($key , $val, $group );
		$cached_val = wp_cache_get( $key, $group);
		$this->assertEquals( $cached_val, $val, "Value from cache must be same as orginal value" );

		// clear caches again and make sure values not in cache anymore
		self::$sf->clear_caches();
		$cached_val = wp_cache_get( $key, $group);
		$this->assertNotEquals( $cached_val, $val, "Value from cache must no be same as orginal value" );
		$this->assertFalse( $cached_val , "Cached value must not exist" );

	 }

	 function testDefaultValues() {

	 	// check that all configs are empty
	 	$this->assertEquals( self::$sf->get_post_connectors(), array() );
	 	$this->assertEquals( self::$sf->get_post_type_defaults(), array(0 => null) );
	 	$this->assertEquals( self::$sf->get_field_groups(), array() );

	 }

	 /*
	 Check if we can get settings from old installation/unit testing
	 */


	// Test output of debug function
	function testDebug()
	{

		$expected = <<<EOD

<pre class='sf_box_debug'>
this is simple fields debug function
</pre>
EOD;

		$this->expectOutputString($expected);
		sf_d("this is simple fields debug function");
	}

	// Test output of debug function
	function testDebug2()
	{
		$expected = <<<EOD

<pre class='sf_box_debug'>
<b>With headline:</b>
this is simple fields debug function
</pre>
EOD;
		$this->expectOutputString($expected);
		sf_d("this is simple fields debug function", "With headline");
	}

	function insertDataForManualAddedFields() {

		global $wpdb;

		// Create post that has manually added simple fields
		// First we create the post, then we add the custom fields
		// post_name=post-with-fields
		// post_content=I am a post with fields attached.
		// post_title=Post with fields

		$post_id = $this->factory->post->create(array(
			"post_title" => "Post with fields"
		));
		self::$post_id_for_manual_tests = $post_id;

		// Insert options for Simple Fields
		// Nothing here is for specific posts
		$query = <<<EOT
			INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`)
			VALUES ('simple_fields_options', 'a:4:{i:0;b:0;s:10:\"debug_type\";i:1;s:23:\"phpunittest_save_option\";s:15:\"new saved value\";s:31:\"phpunittest_save_another_option\";s:13:\"another value\";}', 'yes')
EOT;
		$wpdb->query($query);
		
		$query = <<<EOT
			INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`)
			VALUES 
				('simple_fields_groups', 'a:3:{i:1;a:9:{s:2:\"id\";i:1;s:3:\"key\";s:20:\"field_group_manually\";s:4:\"slug\";s:20:\"field_group_manually\";s:4:\"name\";s:26:\"Manually added field group\";s:11:\"description\";s:50:\"A group that is added manually from within the GUI\";s:10:\"repeatable\";b:1;s:6:\"fields\";a:14:{i:1;a:11:{s:4:\"name\";s:10:\"Text field\";s:11:\"description\";s:12:\"A text field\";s:4:\"slug\";s:10:\"field_text\";s:4:\"type\";s:4:\"text\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"1\";s:7:\"deleted\";s:1:\"0\";}i:2;a:11:{s:4:\"name\";s:14:\"Field textarea\";s:11:\"description\";s:16:\"A texteara field\";s:4:\"slug\";s:14:\"field_textarea\";s:4:\"type\";s:8:\"textarea\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"2\";s:7:\"deleted\";s:1:\"0\";}i:3;a:11:{s:4:\"name\";s:19:\"Field textarea HTML\";s:11:\"description\";s:41:\"A textarea field with HTML-editor enabled\";s:4:\"slug\";s:19:\"field_textarea_html\";s:4:\"type\";s:8:\"textarea\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:2:{s:11:\"size_height\";s:7:\"default\";s:15:\"use_html_editor\";s:1:\"1\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"3\";s:7:\"deleted\";s:1:\"0\";}i:4;a:11:{s:4:\"name\";s:14:\"FIeld checkbox\";s:11:\"description\";s:16:\"A checkbox field\";s:4:\"slug\";s:14:\"field_checkbox\";s:4:\"type\";s:8:\"checkbox\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"4\";s:7:\"deleted\";s:1:\"0\";}i:5;a:12:{s:4:\"name\";s:19:\"Field radioibuttons\";s:11:\"description\";s:20:\"A radiobuttons field\";s:4:\"slug\";s:18:\"field_radiobuttons\";s:4:\"type\";s:12:\"radiobuttons\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_radiobuttons_options\";a:4:{s:17:\"radiobutton_num_2\";a:2:{s:5:\"value\";s:13:\"Radiobutton 1\";s:7:\"deleted\";s:1:\"0\";}s:17:\"radiobutton_num_3\";a:2:{s:5:\"value\";s:13:\"Radiobutton 2\";s:7:\"deleted\";s:1:\"0\";}s:22:\"checked_by_default_num\";s:17:\"radiobutton_num_3\";s:17:\"radiobutton_num_4\";a:2:{s:5:\"value\";s:13:\"Radiobutton 3\";s:7:\"deleted\";s:1:\"0\";}}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"5\";s:7:\"deleted\";s:1:\"0\";}i:6;a:11:{s:4:\"name\";s:14:\"Field dropdown\";s:11:\"description\";s:16:\"A dropdown field\";s:4:\"slug\";s:14:\"field_dropdown\";s:4:\"type\";s:8:\"dropdown\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:4:{s:15:\"enable_multiple\";s:1:\"0\";s:14:\"dropdown_num_2\";a:2:{s:5:\"value\";s:10:\"Dropdown 1\";s:7:\"deleted\";s:1:\"0\";}s:14:\"dropdown_num_3\";a:2:{s:5:\"value\";s:10:\"Dropdown 2\";s:7:\"deleted\";s:1:\"0\";}s:14:\"dropdown_num_4\";a:2:{s:5:\"value\";s:10:\"Dropdown 3\";s:7:\"deleted\";s:1:\"0\";}}s:2:\"id\";s:1:\"6\";s:7:\"deleted\";s:1:\"0\";}i:7;a:11:{s:4:\"name\";s:10:\"Field file\";s:11:\"description\";s:12:\"A file field\";s:4:\"slug\";s:10:\"field_file\";s:4:\"type\";s:4:\"file\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"7\";s:7:\"deleted\";s:1:\"0\";}i:8;a:11:{s:4:\"name\";s:10:\"Field post\";s:11:\"description\";s:12:\"A post field\";s:4:\"slug\";s:10:\"field_post\";s:4:\"type\";s:4:\"post\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:2:{i:0;s:4:\"post\";i:1;s:4:\"page\";}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"8\";s:7:\"deleted\";s:1:\"0\";}i:9;a:12:{s:4:\"name\";s:14:\"Field taxonomy\";s:11:\"description\";s:16:\"A taxonomy field\";s:4:\"slug\";s:14:\"field_taxonomy\";s:4:\"type\";s:8:\"taxonomy\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_taxonomy_options\";a:1:{s:18:\"enabled_taxonomies\";a:2:{i:0;s:8:\"category\";i:1;s:8:\"post_tag\";}}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:1:\"9\";s:7:\"deleted\";s:1:\"0\";}i:10;a:11:{s:4:\"name\";s:19:\"Field Taxonomy Term\";s:11:\"description\";s:21:\"A taxonomy term field\";s:4:\"slug\";s:19:\"field_taxonomy_term\";s:4:\"type\";s:12:\"taxonomyterm\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:2:{s:16:\"enabled_taxonomy\";s:8:\"category\";s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:2:\"10\";s:7:\"deleted\";s:1:\"0\";}i:11;a:11:{s:4:\"name\";s:11:\"Field Color\";s:11:\"description\";s:13:\"A color field\";s:4:\"slug\";s:11:\"field_color\";s:4:\"type\";s:5:\"color\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";%%THING1%%;s:7:\"deleted\";s:1:\"0\";}i:12;a:11:{s:4:\"name\";s:10:\"Field Date\";s:11:\"description\";s:12:\"A date field\";s:4:\"slug\";s:10:\"field_date\";s:4:\"type\";s:4:\"date\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:2:\"12\";s:7:\"deleted\";s:1:\"0\";}i:13;a:11:{s:4:\"name\";s:10:\"Field user\";s:11:\"description\";s:12:\"A user field\";s:4:\"slug\";s:10:\"field_user\";s:4:\"type\";s:4:\"user\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:4:\"date\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:2:\"13\";s:7:\"deleted\";s:1:\"0\";}i:14;a:11:{s:4:\"name\";s:19:\"Field date picker 2\";s:11:\"description\";s:21:\"A date picker 2 field\";s:4:\"slug\";s:19:\"field_date_picker_2\";s:4:\"type\";s:7:\"date_v2\";s:7:\"options\";a:2:{s:7:\"divider\";a:1:{s:10:\"appearance\";s:4:\"line\";}s:7:\"date_v2\";a:3:{s:7:\"show_as\";s:8:\"datetime\";s:4:\"show\";s:6:\"always\";s:12:\"default_date\";s:7:\"no_date\";}}s:21:\"type_textarea_options\";a:1:{s:11:\"size_height\";s:7:\"default\";}s:17:\"type_post_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:21:\"type_dropdown_options\";a:1:{s:15:\"enable_multiple\";s:1:\"0\";}s:2:\"id\";s:2:\"14\";s:7:\"deleted\";s:1:\"0\";}}s:7:\"deleted\";b:0;s:12:\"fields_count\";i:13;}i:2;a:9:{s:2:\"id\";i:2;s:3:\"key\";s:22:\"my_new_field_group_old\";s:4:\"slug\";s:22:\"my_new_field_group_old\";s:4:\"name\";s:16:\"Test field group\";s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:6:\"fields\";a:1:{i:0;a:9:{s:4:\"name\";s:16:\"A new text field\";s:4:\"slug\";s:16:\"my_new_textfield\";s:11:\"description\";s:36:\"Enter some text in my new text field\";s:4:\"type\";s:4:\"text\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:0;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:16:\"Test field group\";s:4:\"slug\";i:2;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:1;}}}s:7:\"deleted\";b:0;s:12:\"fields_count\";i:1;}i:3;a:9:{s:2:\"id\";i:3;s:3:\"key\";s:29:\"my_new_field_group_all_fields\";s:4:\"slug\";s:29:\"my_new_field_group_all_fields\";s:4:\"name\";s:32:\"Test field group with all fields\";s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:6:\"fields\";a:12:{i:0;a:9:{s:4:\"name\";s:24:\"A new field of type text\";s:4:\"slug\";s:19:\"slug_fieldtype_text\";s:11:\"description\";s:34:\"Description for field of type text\";s:4:\"type\";s:4:\"text\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:0;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:1;a:9:{s:4:\"name\";s:28:\"A new field of type textarea\";s:4:\"slug\";s:23:\"slug_fieldtype_textarea\";s:11:\"description\";s:38:\"Description for field of type textarea\";s:4:\"type\";s:8:\"textarea\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:1;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:2;a:9:{s:4:\"name\";s:28:\"A new field of type checkbox\";s:4:\"slug\";s:23:\"slug_fieldtype_checkbox\";s:11:\"description\";s:38:\"Description for field of type checkbox\";s:4:\"type\";s:8:\"checkbox\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:2;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:3;a:9:{s:4:\"name\";s:31:\"A new field of type radiobutton\";s:4:\"slug\";s:26:\"slug_fieldtype_radiobutton\";s:11:\"description\";s:41:\"Description for field of type radiobutton\";s:4:\"type\";s:11:\"radiobutton\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:3;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:4;a:9:{s:4:\"name\";s:28:\"A new field of type dropdown\";s:4:\"slug\";s:23:\"slug_fieldtype_dropdown\";s:11:\"description\";s:38:\"Description for field of type dropdown\";s:4:\"type\";s:8:\"dropdown\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:4;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:5;a:9:{s:4:\"name\";s:24:\"A new field of type file\";s:4:\"slug\";s:19:\"slug_fieldtype_file\";s:11:\"description\";s:34:\"Description for field of type file\";s:4:\"type\";s:4:\"file\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:5;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:6;a:9:{s:4:\"name\";s:24:\"A new field of type post\";s:4:\"slug\";s:19:\"slug_fieldtype_post\";s:11:\"description\";s:34:\"Description for field of type post\";s:4:\"type\";s:4:\"post\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:6;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:7;a:9:{s:4:\"name\";s:28:\"A new field of type taxonomy\";s:4:\"slug\";s:23:\"slug_fieldtype_taxonomy\";s:11:\"description\";s:38:\"Description for field of type taxonomy\";s:4:\"type\";s:8:\"taxonomy\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:7;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:8;a:9:{s:4:\"name\";s:32:\"A new field of type taxonomyterm\";s:4:\"slug\";s:27:\"slug_fieldtype_taxonomyterm\";s:11:\"description\";s:42:\"Description for field of type taxonomyterm\";s:4:\"type\";s:12:\"taxonomyterm\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:8;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:9;a:9:{s:4:\"name\";s:25:\"A new field of type color\";s:4:\"slug\";s:20:\"slug_fieldtype_color\";s:11:\"description\";s:35:\"Description for field of type color\";s:4:\"type\";s:5:\"color\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:9;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:10;a:9:{s:4:\"name\";s:24:\"A new field of type date\";s:4:\"slug\";s:19:\"slug_fieldtype_date\";s:11:\"description\";s:34:\"Description for field of type date\";s:4:\"type\";s:4:\"date\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:10;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}i:11;a:9:{s:4:\"name\";s:24:\"A new field of type user\";s:4:\"slug\";s:19:\"slug_fieldtype_user\";s:11:\"description\";s:34:\"Description for field of type user\";s:4:\"type\";s:4:\"user\";s:17:\"type_post_options\";a:2:{s:18:\"enabled_post_types\";a:0:{}s:20:\"additional_arguments\";s:0:\"\";}s:25:\"type_taxonomyterm_options\";a:1:{s:20:\"additional_arguments\";s:0:\"\";}s:2:\"id\";i:11;s:7:\"deleted\";i:0;s:11:\"field_group\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:32:\"Test field group with all fields\";s:4:\"slug\";i:3;s:11:\"description\";s:22:\"Test field description\";s:10:\"repeatable\";i:1;s:12:\"fields_count\";i:12;}}}s:7:\"deleted\";b:0;s:12:\"fields_count\";i:12;}}', 'yes')
EOT;

		#'s:2:\"11\"';
		$stringlen = strlen($post_id);
		#echo "len: $stringlen";
		#echo "postid: $post_id";
		// replace %%THING1%% with string length and val of previosly inserted post
		
		$query = str_replace('%%THING1%%', 's:' . $stringlen . ':\"' . $post_id . '\"', $query);
		#$query = str_replace('%%THING1%%', 's:2:\"11\"', $query);
		#echo $query;
		$wpdb->query($query);

		$query = <<<EOT
			INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`)
			VALUES
				('simple_fields_post_connectors', 'a:1:{i:1;a:9:{s:2:\"id\";i:1;s:3:\"key\";s:23:\"post_connector_manually\";s:4:\"slug\";s:23:\"post_connector_manually\";s:4:\"name\";s:29:\"Manually added post connector\";s:12:\"field_groups\";a:1:{i:1;a:5:{s:2:\"id\";s:1:\"1\";s:4:\"name\";s:26:\"Manually added field group\";s:7:\"deleted\";s:1:\"0\";s:7:\"context\";s:6:\"normal\";s:8:\"priority\";s:4:\"high\";}}s:10:\"post_types\";a:2:{i:0;s:4:\"post\";i:1;s:4:\"page\";}s:7:\"deleted\";b:0;s:11:\"hide_editor\";b:0;s:18:\"field_groups_count\";i:1;}}', 'yes')
EOT;
		$wpdb->query($query);


		#sf_d( $this->factory->post->create_many( 5 ) );exit;
		#$post_id = 11;

		$query = <<<EOT
			INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
			VALUES
				($post_id, '_simple_fields_selected_connector', '1'),
				($post_id, '_simple_fields_been_saved', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_14_numInSet_0', '2013-01-31 09:30'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_13_numInSet_0', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_13_numInSet_1', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_12_numInSet_1', '15/10/2012'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_12_numInSet_0', '12/10/2012'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_11_numInSet_0', 'FF3C26'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_11_numInSet_1', '8B33FF'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_10_numInSet_1', ''),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_10_numInSet_0', 'a:1:{i:0;s:1:\"1\";}'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_9_numInSet_1', 'category'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_9_numInSet_0', 'post_tag'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_8_numInSet_1', '5'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_8_numInSet_0', '$post_id'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_7_numInSet_1', '17'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_6_numInSet_1', 'dropdown_num_2'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_7_numInSet_0', '14'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_3_numInSet_0', '<p>Text entered in the TinyMCE-editor.</p>\n'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_3_numInSet_1', '<p>Tiny editors are great!</p>\n<p>You can style the content and insert images and stuff. Groovy! Funky!</p>\n<h2>A list</h2>\n<ul>\n<li>List item 1</li>\n<li>List item 2</li>\n</ul>\n<h2>And images can be inserted</h2>\n<p><a href=\"http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg\"><img class=\"alignnone  wp-image-14\" title=\"product-cat-2\" src=\"http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg\" alt=\"\" width=\"368\" height=\"277\" /></a></p>\n'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_6_numInSet_0', 'dropdown_num_3'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_5_numInSet_1', 'radiobutton_num_2'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_4_numInSet_1', ''),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_5_numInSet_0', 'radiobutton_num_4'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_4_numInSet_0', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_1_numInSet_1', 'text in textfield 2<span>yes it is</span>'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_2_numInSet_0', 'Text entered in the textarea'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_2_numInSet_1', 'Textera with more funky text in it.\r\n\r\n<h2>Headline</h2>\r\n<ul>\r\n	<li>Item 1</li>\r\n	<li>Item 2</li>\r\n</ul>\r\n'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_added_numInSet_0', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_1_numInSet_0', 'Text entered in the text field'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_added_numInSet_1', '1'),
				($post_id, '_simple_fields_fieldGroupID_1_fieldID_14_numInSet_1', '2012-12-10 18:00');
EOT;
		$wpdb->query($query);


	}

	// insert and test manually added fields
	function testManuallyAddedFields() {

		$this->insertDataForManualAddedFields();

		// test single/first values
		$post_id = self::$post_id_for_manual_tests;
		
		$this->assertEquals("Text entered in the text field", simple_fields_value("field_text", $post_id));
		$this->assertEquals("Text entered in the textarea", simple_fields_value("field_textarea", $post_id));
		$this->assertEquals("<p>Text entered in the TinyMCE-editor.</p>\n", simple_fields_value("field_textarea_html", $post_id));
		$this->assertEquals("1", simple_fields_value("field_checkbox", $post_id));
		$this->assertEquals("radiobutton_num_4", simple_fields_value("field_radiobuttons", $post_id));
		$this->assertEquals("dropdown_num_3", simple_fields_value("field_dropdown", $post_id));
		$this->assertEquals(14, simple_fields_value("field_file", $post_id));
		$this->assertEquals($post_id, simple_fields_value("field_post", $post_id));
		$this->assertEquals("post_tag", simple_fields_value("field_taxonomy", $post_id));
		$this->assertEquals(array(0 => 1), simple_fields_value("field_taxonomy_term", $post_id));
		$this->assertEquals("FF3C26", simple_fields_value("field_color", $post_id));
		$this->assertEquals("12/10/2012", simple_fields_value("field_date", $post_id));
		$this->assertEquals(1, simple_fields_value("field_user", $post_id));

		// test repeatable/all values

		#echo "xxx";
		#var_dump( simple_fields_values("field_text") );
		#exit;
		#print_r($allvals);

		$val = array(
			0 => "Text entered in the text field",
			1 => "text in textfield 2<span>yes it is</span>"
		);
		$this->assertEquals($val, simple_fields_values("field_text", $post_id));

		$val = array(
			0 => "Text entered in the textarea",
			1 => "Textera with more funky text in it.\r\n\r\n<h2>Headline</h2>\r\n<ul>\r\n	<li>Item 1</li>\r\n	<li>Item 2</li>\r\n</ul>\r\n");

		$get_vals = simple_fields_values("field_textarea", $post_id);
		$this->assertEquals($val, $get_vals);

		$val = array(
			0 => "<p>Text entered in the TinyMCE-editor.</p>\n",
			1 => "<p>Tiny editors are great!</p>\n<p>You can style the content and insert images and stuff. Groovy! Funky!</p>\n<h2>A list</h2>\n<ul>\n<li>List item 1</li>\n<li>List item 2</li>\n</ul>\n<h2>And images can be inserted</h2>\n<p><a href=\"http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg\"><img class=\"alignnone  wp-image-14\" title=\"product-cat-2\" src=\"http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg\" alt=\"\" width=\"368\" height=\"277\" /></a></p>\n");
		$get_vals = simple_fields_values("field_textarea_html", $post_id);
		$this->assertEquals($val[1], $get_vals[1]);
		
		$val = array(
			0 => 1,
			1 => ""
		);
		$this->assertEquals($val, simple_fields_values("field_checkbox", $post_id));

		$val = array(
			0 => "radiobutton_num_4",
			1 => "radiobutton_num_2"
		);
		$this->assertEquals($val, simple_fields_values("field_radiobuttons", $post_id));

		$val = array(
			0 => "dropdown_num_3",
			1 => "dropdown_num_2"
		);
		$this->assertEquals($val, simple_fields_values("field_dropdown", $post_id));

		$val = array(
			0 => 14,
			1 => 17
		);
		$this->assertEquals($val, simple_fields_values("field_file", $post_id));

		$val = array(
			0 => $post_id,
			1 => 5
		);
		$this->assertEquals($val, simple_fields_values("field_post", $post_id));

		$val = array(
			0 => "post_tag",
			1 => "category"
		);
		$this->assertEquals($val, simple_fields_values("field_taxonomy", $post_id));

		$val = array(
			0 => array(0 => 1),
			1 => ""
		);
		$this->assertEquals($val, simple_fields_values("field_taxonomy_term", $post_id));

		$val = array(
			0 => "FF3C26",
			1 => "8B33FF"
		);
		$this->assertEquals($val, simple_fields_values("field_color", $post_id));

		$val = array(
			0 => "12/10/2012",
			1 => "15/10/2012"
		);
		$this->assertEquals($val, simple_fields_values("field_date", $post_id));

		$val = array(
			0 => "1",
			1 => "1"
		);
		$this->assertEquals($val, simple_fields_values("field_user", $post_id));
		
		// date & time picker 2
		$val = array(
			    0 => array(
			            "type" => "datetime",
			            "date_unixtime" => "1359624600",
			            "ISO_8601" => "2013-01-31 09:30",
			            "RFC_2822" => "Thu, 31 Jan 2013 09:30:00 +0000",
			            "Y-m-d" => "2013-01-31",
			            "Y-m-d H:i" => "2013-01-31 09:30",
			            "date_format" => "January 31, 2013",
			            "date_time_format" => "January 31, 2013 9:30 am"
			        ),			
				    1 => array(
			            "type" => "datetime",
			            "date_unixtime" => "1355162400",
			            "ISO_8601" => "2012-12-10 18:00",
			            "RFC_2822" => "Mon, 10 Dec 2012 18:00:00 +0000",
			            "Y-m-d" => "2012-12-10",
			            "Y-m-d H:i" => "2012-12-10 18:00",
			            "date_format" => "December 10, 2012",
			            "date_time_format" => "December 10, 2012 6:00 pm",
			        )			
				);
		$this->assertEquals($val, simple_fields_values("field_date_picker_2", $post_id));

	}

	function testManuallyAddedFieldsExtendedReturn()
	{
	
		$this->insertDataForManualAddedFields();

		$post_id = self::$post_id_for_manual_tests;
	
		// test single/first values
		$vals = simple_fields_value("field_radiobuttons", $post_id, "extended_return=1");
		
		$vals_expected = array(
			"selected_value" => "Radiobutton 3",
			"selected_radiobutton" => array(
				"value" => "Radiobutton 3",
				"key" => "radiobutton_num_4",
				"is_selected" => 1
			),
			"radiobuttons" => array(
				array(
					"value" => "Radiobutton 1",
					"key" 	=> "radiobutton_num_2",
					"is_selected" => ""
				),
				array(
					"value"	=> "Radiobutton 2",
					"key" 	=> "radiobutton_num_3",
					"is_selected" => ""
				),
				array(
					"value" => "Radiobutton 3",
					"key" => "radiobutton_num_4",
					"is_selected" => 1
				)
			)
		);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_dropdown", $post_id, "extended_return=1");
		$vals_expected = array(
							'selected_value' => 'Dropdown 2',
							'selected_option' => array(
								'value' => 'Dropdown 2',
								'key' => 'dropdown_num_3',
								'is_selected' => true
							),
							'options' => array(
								0 => array(
									'value' => 'Dropdown 1',
									'key' => 'dropdown_num_2',
									'is_selected' => false
								),
								1 => array(
									'value' => 'Dropdown 2',
									'key' => 'dropdown_num_3',
									'is_selected' => true
								),
								2 => array(
									'value' => 'Dropdown 3',
									'key' => 'dropdown_num_4',
									'is_selected' => false
								)
							)
						);

		$this->assertEquals($vals_expected, $vals);

		// check just keys for now, should check more of course
		$vals = simple_fields_value("field_file", $post_id, "extended_return=1");
		#echo var_export($vals);
		$attachment_id = 14;
		$vals_expected = array(
			'id' => 14,
			'is_image' => false,
			'url' => false,
			'mime' => false,
			'link' => array(
				'full' => 'Missing Attachment',
				'thumbnail' => 'Missing Attachment',
				'medium' => 'Missing Attachment',
				'large' => 'Missing Attachment',
				'post-thumbnail' => 'Missing Attachment',
			),
			'image' => array(
				'full' => '',
				'thumbnail' => '',
				'medium' => '',
				'large' => '',
				'post-thumbnail' => '',
			),
			'image_src' => array(
				'full' => false,
				'thumbnail' => false,
				'medium' => false,
				'large' => false,
				'post-thumbnail' => false,
			),
			'metadata' => false,
			"post" => null
		);
		#var_dump($vals);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_post", $post_id, "extended_return=1");
		$vals_expected = array(
			'id' => $post_id,
			'title' => 'Post with fields',
			'permalink' => 'http://example.org/?p=' . $post_id,
			'post' => get_post($post_id)
		);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_taxonomy", $post_id, "extended_return=1");
		$vals_expected = array(
			'name' => 'post_tag',
			'singular_name' => 'Tag',
			'plural_name' => 'Tags',
			"taxonomy" => get_taxonomy("post_tag")
		);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_taxonomy_term", $post_id, "extended_return=1");
		$vals_expected = array(
			"terms" => array(
				0 => array(
					"name" => "Uncategorized",
					"slug" => "uncategorized",
					"id" => 1,
					"term" => get_term_by("id", 1, "category")
				)
			)
		);
		$this->assertEquals($vals_expected, $vals);

		$vals = simple_fields_value("field_date", $post_id, "extended_return=1");
		$vals_expected = array ( 
			'saved_value' => '12/10/2012', 
			'timestamp' => 1350000000, 
			'date_format' => 'October 12, 2012',
			'date_format_i18n' => 'October 12, 2012'
		);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_user", $post_id, "extended_return=1");
		$vals_expected = array ( 
			'id' => 1, 
			'first_name' => '', 
			'last_name' => '', 
			'user_login' => 'admin', 
			'user_email' => 'admin@example.org', 
			'user_nicename' => 'admin', 'display_name' => 'admin', 
			'user' => get_user_by("id", 1)
		);
		$this->assertEquals($vals_expected, $vals);
				
	}

}

