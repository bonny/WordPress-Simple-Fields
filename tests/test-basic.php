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
Var is string with length 36: this is simple fields debug function
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
Var is string with length 36: this is simple fields debug function
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

		// Posts to test different connectors with
		$sql = "INSERT INTO `$wpdb->posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`)
		VALUES
			(24, 1, '2012-10-11 11:07:42', '2012-10-11 11:07:42', '', 'Post with no connector', '', 'publish', 'open', 'open', '', 'post-with-not-connector', '', '', '2012-10-11 11:08:41', '2012-10-11 11:08:41', '', 0, 'http://unit-test.simple-fields.com/wordpress/?p=24', 0, 'post', '', 0),
			(26, 1, '2012-10-11 11:08:04', '2012-10-11 11:08:04', '', 'Post with inherit connector', '', 'publish', 'open', 'open', '', 'post-with-inherit-connector', '', '', '2012-10-11 11:08:29', '2012-10-11 11:08:29', '', 0, 'http://unit-test.simple-fields.com/wordpress/?p=26', 0, 'post', '', 0),
			(32, 1, '2012-10-11 11:39:31', '2012-10-11 11:39:31', '', 'Page with fields', '', 'publish', 'open', 'open', '', 'page-with-fields', '', '', '2012-10-11 11:39:31', '2012-10-11 11:39:31', '', 0, 'http://unit-test.simple-fields.com/wordpress/?page_id=32', 0, 'page', '', 0),
			(34, 1, '2012-10-11 11:39:43', '2012-10-11 11:39:43', '', 'Page with inherit connector (has parent with fields)', '', 'publish', 'open', 'open', '', 'page-with-inherit-connector', '', '', '2012-10-11 11:40:30', '2012-10-11 11:40:30', '', 32, 'http://unit-test.simple-fields.com/wordpress/?page_id=34', 0, 'page', '', 0),
			(36, 1, '2012-10-11 11:39:59', '2012-10-11 11:39:59', '', 'Post with no connector (has parent with fields)', '', 'publish', 'open', 'open', '', 'post-with-no-connector', '', '', '2012-10-11 11:40:10', '2012-10-11 11:40:10', '', 32, 'http://unit-test.simple-fields.com/wordpress/?page_id=36', 0, 'page', '', 0);
		";
		$wpdb->query($sql);

		// post_with_no_connector
		$sql = "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
		VALUES
			(24, '_edit_lock', '1349955446:1'),
			(24, '_simple_fields_selected_connector', '__none__'),
			(24, '_edit_last', '1');";
		$wpdb->query($sql);

		// post_with_inherit_connector
		$sql = "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
		VALUES
			(26, '_edit_last', '1'),
			(26, '_edit_lock', '1349953596:1'),
			(26, '_simple_fields_selected_connector', '__inherit__');";
		$wpdb->query($sql);

		// page_with_fields
		$sql = "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
		VALUES
			(32, '_edit_last', '1'),
			(32, '_edit_lock', '1357241403:1'),
			(32, '_simple_fields_selected_connector', '1'),
			(32, '_wp_page_template', 'default');";
		$wpdb->query($sql);

		// page_with_no_connector
		$sql = "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
		VALUES
			(36, '_edit_last', '1'),
			(36, '_edit_lock', '1349957025:1'),
			(36, '_simple_fields_selected_connector', '__none__'),
			(36, '_wp_page_template', 'default');";
		$wpdb->query($sql);

		// page_with_inherit_connector
		$sql = "INSERT INTO `$wpdb->postmeta` (`post_id`, `meta_key`, `meta_value`)
		VALUES
			(34, '_edit_last', '1'),
			(34, '_edit_lock', '1349955934:1'),
			(34, '_simple_fields_selected_connector', '__inherit__'),
			(34, '_wp_page_template', 'default');";
		$wpdb->query($sql);

		return $post_id;

	}

	// insert and test manually added fields
	function testManuallyAddedFields() {

		$this->insertDataForManualAddedFields();
		$post_id = self::$post_id_for_manual_tests;
		
		// test single/first values
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

		// test that wp_post object can be passed
		$post_dapost = get_post($post_id);
		$this->assertEquals("Text entered in the text field", simple_fields_value("field_text", $post_dapost));
		
		// test repeatable/all values

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
				// +        'post-thumbnail-full-width' => 'Missing Attachment'

			),
			'image' => array(
				'full' => '',
				'thumbnail' => '',
				'medium' => '',
				'large' => '',
				'post-thumbnail' => '',
				// +        'post-thumbnail-full-width' => ''

			),
			'image_src' => array(
				'full' => false,
				'thumbnail' => false,
				'medium' => false,
				'large' => false,
				'post-thumbnail' => false,
				// 'post-thumbnail-full-width' => false

			),
			'metadata' => false,
			"post" => null
		);
		#var_dump($vals);

		// Test file field with file that does not exist
		// Expect empty array
		$vals_expected = array();
		$this->assertEquals($vals_expected, $vals);
		
		// test with a real attachment
		// @todo: how to test attachments?
		// Upload attachment. code from
		// https://github.com/kurtpayne/wordpress-unit-tests/blob/master/tests/post/attachments.php#L19
		$base_64_image = "R0lGODdhAAQAA+MAAMzMzJaWlre3t5ycnMXFxbGxsaOjo76+vqqqqgAAAAAAAAAAAAAAAAAAAAAAAAAAACwAAAAAAAQAAwAE/hDISau9OOvNu/9gKI5kaZ5oqq5s675wLM90bd94ru987//AoHBILBqPyKRyyWw6n9CodEqtWq/YrHbL7Xq/4LB4TC6bz+i0es1uu9/wuHxOr9vv+Lx+z+/7/4CBgoOEhYaHiImKi4yNjo+QkZKTlJWWl5iZmpucnZ6foKGio6SlpqeoqaqrrK2ur7CxsrO0tba3uLm6u7y9vr/AwcLDxMXGx8jJysvMzc7P0NHS09TV1tfY2drb3N3e3+Dh4uPk5ebn6Onq6+zt7u/w8fLz9PX29/j5+vv8/f7/AAMKHEiwoMGDCBMqXMiwocOHECNKnEixosWLGDNq3Mixo8eP/iBDihxJsqTJkyhTqlzJsqXLlzBjypxJs6bNmzhz6tzJs6fPn0CDCh1KtKjRo0iTKl3KtKnTp1CjSp1KtarVq1izat3KtavXr2DDih1LtqzZs2jTql3Ltq3bt3Djyp1Lt67du3jz6t3Lt6/fv4ADCx5MuLDhw4gTK17MuLHjx5AjS55MubLly5gza97MubPnz6BDix5NurTp06hTq17NurXr17Bjy55Nu7bt27hz697Nu7fv38CDCx9OvLjx48iTK1/OvLnz59CjS59Ovbr169iza9/Ovbv37+DDix9Pvrz58+jTq1/Pvr379/Djy59Pv779+/jz69/Pv7///wAG/ijggAQWaOCBCCao4IIMNujggxBGKOGEFFZo4YUYZqjhhhx26OGHIIYo4ogklmjiiSimqOKKLLbo4oswxijjjDTWaOONOOao44489ujjj0AGKeSQRBZp5JFIJqnkkkw26eSTUEYp5ZRUVmnllVhmqeWWXHbp5ZdghinmmGSWaeaZaKap5ppstunmm3DGKeecdNZp55145qnnnnz26eefgAYq6KCEFmrooYgmquiijDbq6KOQRirppJRWaumlmGaq6aacdurpp6CGKuqopJZq6qmopqrqqqy26uqrsMYq66y01mrrrbjmquuuvPbq66/ABivssMQWa+yxyCar7LLM/jbr7LPQRivttNRWa+212Gar7bbcduvtt+CGK+645JZr7rnopqvuuuy26+678MYr77z01mvvvfjmq+++/Pbr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";
		$upload = wp_upload_bits("testimage_1.gif", null, base64_decode($base_64_image));

		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
					$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		// file is uploaded, add it to post/simple fields-fields
		simple_fields_set_value($post_id, "field_file", 0, null, $id);

		// get field that was added just now
		// big array is returned, but at least check the basics
		$vals_returned = simple_fields_value("field_file", $post_id, "extended_return=1");
		$this->assertArrayHasKey('id', $vals_returned);
		$this->assertEquals($vals_returned["id"], $id);

		$this->assertArrayHasKey('is_image', $vals_returned);
		$this->assertEquals($vals_returned["is_image"], true);

		$this->assertArrayHasKey('mime', $vals_returned);
		$this->assertEquals($vals_returned["mime"], "image/gif");
		
		$this->assertArrayHasKey('link', $vals_returned);
		$this->assertNotEmpty($vals_returned["link"]);

		$this->assertArrayHasKey('url', $vals_returned);
		$this->assertNotEmpty($vals_returned["url"]);

		$this->assertArrayHasKey('image', $vals_returned);
		$this->assertNotEmpty($vals_returned["image"]);

		$this->assertArrayHasKey('image_src', $vals_returned);
		$this->assertNotEmpty($vals_returned["image_src"]);

		$this->assertArrayHasKey('metadata', $vals_returned);
		$this->assertNotEmpty($vals_returned["metadata"]);

		$this->assertArrayHasKey('post', $vals_returned);
		$this->assertNotEmpty($vals_returned["post"]);

		$vals = simple_fields_value("field_post", $post_id, "extended_return=1");
		$vals_expected = array(
			'id' => $post_id,
			'title' => 'Post with fields',
			'permalink' => get_permalink($post_id),
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





	public function testPostConnectors() {
		
		// testa connectors
		// sätt connectors manuellt på några poster
		// testa därefter om det är rätt stuff
		$this->insertDataForManualAddedFields();
		$post_id = self::$post_id_for_manual_tests;
	
		$post_with_fields = $post_id;
		$saved_connector_to_use = get_post_meta($post_with_fields, "_simple_fields_selected_connector", true);
		$this->assertEquals(1, $saved_connector_to_use);
		$this->assertEquals(1, self::$sf->get_selected_connector_for_post($post_with_fields));


		$post_with_no_connector = 24;
		$saved_connector_to_use = get_post_meta($post_with_no_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__none__", $saved_connector_to_use);
		$this->assertEquals("__none__", self::$sf->get_selected_connector_for_post($post_with_no_connector));

		$post_with_inherit_connector = 26;
		$saved_connector_to_use = get_post_meta($post_with_inherit_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__inherit__", $saved_connector_to_use);
		$this->assertEquals("__inherit__", self::$sf->get_selected_connector_for_post($post_with_inherit_connector));

		// pages
		$page_with_fields = 32;
		$saved_connector_to_use = get_post_meta($page_with_fields, "_simple_fields_selected_connector", true);
		$this->assertEquals(1, $saved_connector_to_use);
		$this->assertEquals(1, self::$sf->get_selected_connector_for_post($page_with_fields));
		$this->assertEquals("post_connector_manually", simple_fields_connector($page_with_fields));

		$page_with_no_connector = 36;
		$saved_connector_to_use = get_post_meta($page_with_no_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__none__", $saved_connector_to_use);
		$this->assertEquals("__none__", self::$sf->get_selected_connector_for_post($page_with_no_connector));
		$this->assertEmpty(simple_fields_connector($page_with_no_connector));

		// page is a child of a page with fields, so it will use the connector of the parent
		$page_with_inherit_connector = 34;
		$saved_connector_to_use = get_post_meta($page_with_inherit_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__inherit__", $saved_connector_to_use);
		$this->assertEquals(1, self::$sf->get_selected_connector_for_post($page_with_inherit_connector));
		$this->assertEquals("post_connector_manually", simple_fields_connector($page_with_inherit_connector));

		$arr = array(
			0 => 'post',
			1 => 'page'
		);
		$this->assertEquals( $arr, self::$sf->get_post_connector_attached_types() );

		// formated output from var_export using http://beta.phpformatter.com/
		$arr = array(
			'id' => 1,
			'key' => 'post_connector_manually',
			'slug' => 'post_connector_manually',
			'name' => 'Manually added post connector',
			'field_groups' => array(
				1 => array(
					'id' => '1',
					'name' => 'Manually added field group',
					'deleted' => '0',
					'context' => 'normal',
					'priority' => 'high'
				)
			),
			'post_types' => array(
				0 => 'post',
				1 => 'page'
			),
			'deleted' => false,
			'hide_editor' => false,
			'field_groups_count' => 1
		);
		$this->assertEquals($arr, self::$sf->get_connector_by_id(1));
		
		$arr = array(
			1 => array(
				'id' => 1,
				'key' => 'post_connector_manually',
				'slug' => 'post_connector_manually',
				'name' => 'Manually added post connector',
				'field_groups' => array(
					1 => array(
						'id' => '1',
						'name' => 'Manually added field group',
						'deleted' => '0',
						'context' => 'normal',
						'priority' => 'high'
					)
				),
				'post_types' => array(
					0 => 'post',
					1 => 'page'
				),
				'deleted' => false,
				'hide_editor' => false,
				'field_groups_count' => 1
			)
		);
		$this->assertEquals($arr, self::$sf->get_post_connectors() );

	}

	public function testSaveGetOptions() {
		
		self::$sf->save_options(array(
			"phpunittest_save_option" => "new saved value"
		));
		
		$options = self::$sf->get_options();
		$this->assertArrayHasKey("phpunittest_save_option", $options);

		self::$sf->save_options(array(
			"phpunittest_save_option" => "new saved value",
			"phpunittest_save_another_option" => "another value",
		));

		$options = self::$sf->get_options();
		$this->assertArrayHasKey("phpunittest_save_option", $options);
		$this->assertArrayHasKey("phpunittest_save_another_option", $options);

		$this->assertEquals($options["phpunittest_save_another_option"], "another value");

	}
	
	/**
	 * test simple_fields_get_all_fields_and_values_for_post() that gets all values for a post
	 * simple_fields_get_all_fields_and_values_for_post
	 */
	public function testGetAllForPost() {
		
		$post_id = $this->insertDataForManualAddedFields();
		$all_vals = simple_fields_get_all_fields_and_values_for_post($post_id);

		// this test feels a bit to much, should check sub keys-stuff instead of all
		$vals = array(
			'id' => 1,
			'key' => 'post_connector_manually',
			'slug' => 'post_connector_manually',
			'name' => 'Manually added post connector',
			'field_groups' => array(
				1 => array(
					'id' => 1,
					'name' => 'Manually added field group',
					'deleted' => false,
					'context' => 'normal',
					'priority' => 'high',
					'key' => 'field_group_manually',
					'slug' => 'field_group_manually',
					'description' => 'A group that is added manually from within the GUI',
					'repeatable' => true,
					'fields' => array(
						1 => array(
							'name' => 'Text field',
							'description' => 'A text field',
							'slug' => 'field_text',
							'type' => 'text',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '1',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'Text entered in the text field',
								1 => 'text in textfield 2<span>yes it is</span>'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_1_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_1_numInSet_1'
							)
						),
						2 => array(
							'name' => 'Field textarea',
							'description' => 'A texteara field',
							'slug' => 'field_textarea',
							'type' => 'textarea',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '2',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'Text entered in the textarea',
								1 => 'Textera with more funky text in it.  <h2>Headline</h2> <ul> <li>Item 1</li> <li>Item 2</li> </ul> '
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_2_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_2_numInSet_1'
							)
						),
						3 => array(
							'name' => 'Field textarea HTML',
							'description' => 'A textarea field with HTML-editor enabled',
							'slug' => 'field_textarea_html',
							'type' => 'textarea',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_textarea_options' => array(
								'use_html_editor' => '1'
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '3',
							'deleted' => '0',
							'saved_values' => array(
								0 => '<p>Text entered in the TinyMCE-editor.</p> ',
								1 => '<p>Tiny editors are great!</p> <p>You can style the content and insert images and stuff. Groovy! Funky!</p> <h2>A list</h2> <ul> <li>List item 1</li> <li>List item 2</li> </ul> <h2>And images can be inserted</h2> <p><a href="http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg"><img class="alignnone wp-image-14" title="product-cat-2" src="http://unit-test.simple-fields.com/wordpress/wp-content/uploads/2012/10/product-cat-2.jpeg" alt="" width="368" height="277" /></a></p> '
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_3_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_3_numInSet_1'
							)
						),
						4 => array(
							'name' => 'FIeld checkbox',
							'description' => 'A checkbox field',
							'slug' => 'field_checkbox',
							'type' => 'checkbox',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '4',
							'deleted' => '0',
							'saved_values' => array(
								0 => '1',
								1 => ''
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_4_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_4_numInSet_1'
							)
						),
						5 => array(
							'name' => 'Field radioibuttons',
							'description' => 'A radiobuttons field',
							'slug' => 'field_radiobuttons',
							'type' => 'radiobuttons',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'type_radiobuttons_options' => array(
								'radiobutton_num_2' => array(
									'value' => 'Radiobutton 1',
									'deleted' => '0'
								),
								'radiobutton_num_3' => array(
									'value' => 'Radiobutton 2',
									'deleted' => '0'
								),
								'checked_by_default_num' => 'radiobutton_num_3',
								'radiobutton_num_4' => array(
									'value' => 'Radiobutton 3',
									'deleted' => '0'
								)
							),
							'id' => '5',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'radiobutton_num_4',
								1 => 'radiobutton_num_2'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_5_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_5_numInSet_1'
							)
						),
						6 => array(
							'name' => 'Field dropdown',
							'description' => 'A dropdown field',
							'slug' => 'field_dropdown',
							'type' => 'dropdown',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'type_dropdown_options' => array(
								'dropdown_num_2' => array(
									'value' => 'Dropdown 1',
									'deleted' => '0'
								),
								'dropdown_num_3' => array(
									'value' => 'Dropdown 2',
									'deleted' => '0'
								),
								'dropdown_num_4' => array(
									'value' => 'Dropdown 3',
									'deleted' => '0'
								)
							),
							'id' => '6',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'dropdown_num_3',
								1 => 'dropdown_num_2'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_6_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_6_numInSet_1'
							)
						),
						7 => array(
							'name' => 'Field file',
							'description' => 'A file field',
							'slug' => 'field_file',
							'type' => 'file',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '7',
							'deleted' => '0',
							'saved_values' => array(
								0 => '14',
								1 => '17'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_7_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_7_numInSet_1'
							)
						),
						8 => array(
							'name' => 'Field post',
							'description' => 'A post field',
							'slug' => 'field_post',
							'type' => 'post',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'enabled_post_types' => array(
									0 => 'post',
									1 => 'page'
								),
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '8',
							'deleted' => '0',
							'saved_values' => array(
								0 => '11',
								1 => '5'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_8_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_8_numInSet_1'
							)
						),
						9 => array(
							'name' => 'Field taxonomy',
							'description' => 'A taxonomy field',
							'slug' => 'field_taxonomy',
							'type' => 'taxonomy',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomy_options' => array(
								'enabled_taxonomies' => array(
									0 => 'category',
									1 => 'post_tag'
								)
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '9',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'post_tag',
								1 => 'category'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_9_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_9_numInSet_1'
							)
						),
						10 => array(
							'name' => 'Field Taxonomy Term',
							'description' => 'A taxonomy term field',
							'slug' => 'field_taxonomy_term',
							'type' => 'taxonomyterm',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'enabled_taxonomy' => 'category',
								'additional_arguments' => ''
							),
							'id' => '10',
							'deleted' => '0',
							'saved_values' => array(
								0 => array(
									0 => '1'
								),
								1 => ''
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_10_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_10_numInSet_1'
							)
						),
						11 => array(
							'name' => 'Field Color',
							'description' => 'A color field',
							'slug' => 'field_color',
							'type' => 'color',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '11',
							'deleted' => '0',
							'saved_values' => array(
								0 => 'FF3C26',
								1 => '8B33FF'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_11_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_11_numInSet_1'
							)
						),
						12 => array(
							'name' => 'Field Date',
							'description' => 'A date field',
							'slug' => 'field_date',
							'type' => 'date',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '12',
							'deleted' => '0',
							'saved_values' => array(
								0 => '12/10/2012',
								1 => '15/10/2012'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_12_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_12_numInSet_1'
							)
						),
						13 => array(
							'name' => 'Field user',
							'description' => 'A user field',
							'slug' => 'field_user',
							'type' => 'user',
							'options' => array(
								'fieldExample' => array(
									'myTextOption' => 'No value entered yet',
									'mapsTextarea' => 'Enter some cool text here please!',
									'funkyDropdown' => ''
								),
								'minimalexample' => array(
									'textDefaultName' => ''
								)
							),
							'type_post_options' => array(
								'additional_arguments' => ''
							),
							'type_taxonomyterm_options' => array(
								'additional_arguments' => ''
							),
							'id' => '13',
							'deleted' => '0',
							'saved_values' => array(
								0 => '1',
								1 => '1'
							),
							'meta_keys' => array(
								0 => '_simple_fields_fieldGroupID_1_fieldID_13_numInSet_0',
								1 => '_simple_fields_fieldGroupID_1_fieldID_13_numInSet_1'
							)
						)
					),
					'fields_count' => 13
				)
			),
			'post_types' => array(
				0 => 'post',
				1 => 'page'
			),
			'deleted' => false,
			'hide_editor' => false,
			'field_groups_count' => 1
		);

		// $this->assertEquals($vals, $all_vals);
		
		// perhaps spot differences in keys is a good thing?
		$this->assertEquals( array_keys($vals), array_keys($all_vals));
	}

	public function testRegisterFunctions() {

		$arr_return = simple_fields_register_field_group(
			"my_new_field_group",
			array(
				'name' => 'Test field group',
				'description' => "Test field description",
				'repeatable' => 1,
				'fields' => array(
					array(
						'name' => 'A new text field',
						'description' => 'Enter some text in my new text field',
						'type' => 'text',
						'slug' => "my_new_textfield"
					)
				)
			)
		);

		$expected_return = array(
			'id' => 1,
			'key' => 'my_new_field_group',
			'slug' => 'my_new_field_group',
			'name' => 'Test field group',
			'description' => 'Test field description',
			'repeatable' => 1,
			'fields' => array(
				0 => array(
					'name' => 'A new text field',
					'slug' => 'my_new_textfield',
					'description' => 'Enter some text in my new text field',
					'type' => 'text',
					'type_post_options' => array(
						'enabled_post_types' => array(),
						'additional_arguments' => ''
					),
					'type_taxonomyterm_options' => array(
						'additional_arguments' => ''
					),
					'type_text_options' => array(),
					'id' => 0,
					'deleted' => 0,
					"options" => array(
						"text" => array()
					),
					"field_group" => array(
						"id" => 1,
						"name" => "Test field group",
						"slug" => "my_new_field_group",
						"description" => "Test field description",
						"repeatable" => 1,
						"fields_count" => 1
					),
				),
			),
			'deleted' => false,
			"fields_count" => 1,
			"added_with_code" => true
			// "fields_by_slug" => array()
		);
		
		// check that all keys in expected_return and it's values exist in arr_return
		foreach ($expected_return as $expected_return_key => $expected_return_value) {
			$this->assertArrayHasKey( $expected_return_key, $arr_return );
			$this->assertEquals( $expected_return_value, $arr_return[$expected_return_key] );
		}

		foreach ($expected_return["fields"][0] as $expected_return_key => $expected_return_value) {
			$this->assertArrayHasKey($expected_return_key, $arr_return["fields"][0]);
		}
		

		// generate arr with all field types
		$arr_field_types = array();
		$field_types = explode(",", "text,textarea,checkbox,radiobutton,dropdown,file,post,taxonomy,taxonomyterm,color,date,user");
		foreach ($field_types as $field_type) {
			$arr_field_types[] = array(
					'name' => "A new field of type $field_type",
					'description' => "Description for field of type $field_type",
					'type' => $field_type,
					'slug' => "slug_fieldtype_$field_type"
				);
		}
		
		$arr_return = simple_fields_register_field_group(
			"my_new_field_group_all_fields",
			array(
				'name' => 'Test field group with all fields',
				'description' => "Test field description",
				'repeatable' => 1,
				'fields' => $arr_field_types
			)
		);

		// something like this anyway. we can check keys by it anyway
		$expected_return = array(
			'id' => 2,
			'key' => 'my_new_field_group_all_fields',
			'slug' => 'my_new_field_group_all_fields',
			'name' => 'Test field group with all fields',
			'description' => 'Test field description',
			'repeatable' => 1,
			'fields' => array(
				0 => array(
					'name' => 'A new field of type text',
					'slug' => 'slug_fieldtype_text',
					'description' => 'Description for field of type text',
					'type' => 'text',
					'type_post_options' => array(
						'enabled_post_types' => array(),
						'additional_arguments' => ''
					),
					'type_taxonomyterm_options' => array(
						'additional_arguments' => ''
					),
					'id' => 0,
					'deleted' => 0,
					"field_group" => array(),
					"options" => array(),
				),
			),
			'deleted' => false,
			"fields_count" => 1,
			"added_with_code" => true,
			"gui_view" => "list"
		);


		unset($arr_return["fields_by_slug"]);
		
		ksort($expected_return);
		ksort($arr_return);
		
		#print_r(array_keys($arr_return));
		#print_r(array_keys($expected_return));

		$this->assertEquals( array_keys($expected_return), array_keys($arr_return) );
		
		// @todo: add test of values here also
		foreach ($arr_return["fields"] as $arr_one_field) {
			// cheating a bit, because laziness
			unset( $arr_one_field["type_text_options"], 
				   $arr_one_field["type_textarea_options"], 
				   $arr_one_field["type_checkbox_options"], 
				   $arr_one_field["type_dropdown_options"], 
				   $arr_one_field["type_file_options"], 
				   $arr_one_field["type_taxonomy_options"], 
				   $arr_one_field["type_color_options"],
				   $arr_one_field["type_date_options"],
				   $arr_one_field["type_user_options"]
				);

		
			ksort($expected_return["fields"][0]);
			ksort($arr_one_field);

			$this->assertEquals( array_keys($expected_return["fields"][0]), array_keys($arr_one_field) );
		}
	
		/*			
			simple_fields_register_post_connector($unique_name = "", $new_post_connector = array())
			simple_fields_register_post_type_default($connector_id_or_special_type = "", $post_type = "post")

		*/


		// Test post connectors
		$connector_return1 = simple_fields_register_post_connector('test_connector',
			array (
				'name' => "A test connector",
				'field_groups' => array(
					array(
						'slug' => 'my_new_field_group'
					)
				),
				'post_types' => array('post', "page")
			)
		);	

		$connector_return2 = simple_fields_register_post_connector('another_connector',
			array (
				'name' => "Another connector",
				'field_groups' => array(
					array(
						'slug' => 'my_new_field_group_all_fields'
					),
					array(
						'slug' => 'my_new_field_group'
					),
				),
				'post_types' => array('post', "page")
			)
		);

		$connector_return1_expected = array(
				'id' => 1,
				'key' => 'test_connector',
				'slug' => 'test_connector',
				'name' => 'A test connector',
				'field_groups' => array(
								1 => array(
												'id' => 1,
												'slug' => 'my_new_field_group',
												'key' => 'my_new_field_group',
												'name' => 'Test field group',
												'deleted' => 0,
												'context' => 'normal',
												'priority' => 'low'
								)
				),
				'post_types' => array(
								0 => 'post',
								1 => 'page'
				),
				'deleted' => false,
				'hide_editor' => false,
				'field_groups_count' => 1,
				"added_with_code" => true
			  );
		
		$this->assertEquals($connector_return1_expected, $connector_return1);
		
		$connector_return2_expected = array(
				'id' => 2,
				'key' => 'another_connector',
				'slug' => 'another_connector',
				'name' => 'Another connector',
				'field_groups' => array(
								2 => array(
												'id' => 2,
												'slug' => 'my_new_field_group_all_fields',
												'key' => 'my_new_field_group_all_fields',
												'name' => 'Test field group with all fields',
												'deleted' => 0,
												'context' => 'normal',
												'priority' => 'low'
								),
								1 => array(
												'id' => 1,
												'slug' => 'my_new_field_group',
												'key' => 'my_new_field_group',
												'name' => 'Test field group',
												'deleted' => 0,
												'context' => 'normal',
												'priority' => 'low'
								)
				),
				'post_types' => array(
								0 => 'post',
								1 => 'page'
				),
				'deleted' => false,
				'hide_editor' => false,
				'field_groups_count' => 2,
				"added_with_code" => true
			  );

		$this->assertEquals($connector_return2_expected, $connector_return2);
		
		
		// test manually added fields again to make sure nothing broke
		// does this work btw?
		// $this->testManuallyAddedFields();

		// Some more texts with addings fields
		// Added 14 jan 2013
		$new_field_group_fields = array (
				'name' => 'Attachments',
				'description' => "Add some attachments to this post",
				'repeatable' => 1,
				'fields' => array(
					array(
						'slug' => "attachment_file",
						'name' => 'A file',
						'description' => 'Select a file, for example an image',
						'type' => 'file',
						"type_file_options" => array(
							"enable_extended_return_values" => 1
						)
					),
				)
		);
		$added_field_group = simple_fields_register_field_group('attachments', $new_field_group_fields);

		// check most important things		
		//$this->assertEquals( array_keys($expected_return), array_keys($arr_return) );
		$this->assertArrayHasKey("slug", $added_field_group);
		foreach ($new_field_group_fields as $field_key => $field_val) {
			$this->assertArrayHasKey($field_key, $added_field_group);	
		}
		foreach ($new_field_group_fields["fields"][0] as $field_key => $field_val) {
			$this->assertArrayHasKey($field_key, $added_field_group["fields"][0]);	
		}

		// Change some small things, like adding another fields after the first
		$new_field_group_fields_modified1 = $new_field_group_fields;
		$new_field_group_fields_modified1["name"] = "Attachments changed text";
		$new_field_group_fields_modified1["description"] = "Attachments changed description";
		$new_field_group_fields_modified1["fields"][] = array(
															'slug' => "attachment_description",
															'name' => 'A description',
															'description' => 'bla bla bla',
															'type' => 'text',
															"type_file_options" => array(
																"enable_extended_return_values" => 1
														)
													);
		$added_field_group_after_modified1 = simple_fields_register_field_group('attachments', $new_field_group_fields_modified1);		
		foreach ($expected_return as $field_key => $field_val) {
			$this->assertArrayHasKey($field_key, $added_field_group_after_modified1);			
		}

		$this->assertCount( 2, $added_field_group_after_modified1["fields"] );
		$this->assertEquals( $added_field_group_after_modified1["fields"][0]["slug"], "attachment_file" );
		$this->assertEquals( $added_field_group_after_modified1["fields"][1]["slug"], "attachment_description" );
		
		
		// Update an existing field with, with as little code as possible
		$added_field_group_after_updated_field_with_little_code = simple_fields_register_field_group('attachments', array (
				'key' => 'attachments',
				'fields' => array(
					array(
						'slug' => "attachment_file",
						'name' => 'A file, updated',
						'description' => 'Select a file, for example an image, updated'
					),
				)
			)
		);

		$this->assertEquals( $added_field_group_after_updated_field_with_little_code["fields"][0]["slug"], "attachment_file" );
		$this->assertEquals( $added_field_group_after_updated_field_with_little_code["fields"][1]["slug"], "attachment_description" );

		// Update an existing field with, with as little code as possible
		// after this attachment_description should be the first key in fields
		$added_field_group_after_updated_field_with_little_code = simple_fields_register_field_group('attachments', array (
				'key' => 'attachments',
				'fields' => array(
					array(
						'slug' => "attachment_description"
					),
				)
			)
		);

		$this->assertEquals( $added_field_group_after_updated_field_with_little_code["fields"][0]["slug"], "attachment_file" );
		$this->assertEquals( $added_field_group_after_updated_field_with_little_code["fields"][1]["slug"], "attachment_description" );

		$this->assertEquals( array(1, 0), array_keys( $added_field_group_after_updated_field_with_little_code["fields"] ) );
		
		/*

			left to write tests for:

			simple_fields_query_posts
			function simple_fields_set_value($post_id, $field_slug, $new_numInSet = null, $new_post_connector = null, $new_value) {
			get_field_group($group_id)
			get_field_in_group($field_group, $field_id)
			Extension API
			
		*/
	}

		
	public function test_meta_key_generator() {
		
		// older format
		$key = self::$sf->get_meta_key(1, 2, 3);
		$this->assertEquals("_simple_fields_fieldGroupID_1_fieldID_2_numInSet_3", $key);

		// newer format
		$key = self::$sf->get_meta_key(1, 2, 3, "fieldgroupslug", "fieldslug");
		$this->assertEquals("_simple_fields_fieldGroupID_1_fieldID_2_numInSet_3", $key);

		// test own custom one
		$custom_field_key_template = add_filter("simple_fields_get_meta_key_template", function($str) {
			$custom_field_key_template = '%4$s_%5$s_%3$d';
			return $custom_field_key_template;
		});
		$key = self::$sf->get_meta_key(1, 2, 1, "fgAttachments", "fFile");
		$key_should_be = "fgAttachments_fFile_1";
		$this->assertEquals($key_should_be, $key);

	}

	public function test_misc() {

		// test that normalization of fields works
		$field_groups = unserialize('a:1:{i:19;a:12:{s:2:"id";i:19;s:3:"key";s:19:"wpml_radiosandstuff";s:4:"slug";s:19:"wpml_radiosandstuff";s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:6:"fields";a:4:{i:1;a:11:{s:4:"name";s:18:"Here is checkboxes";s:4:"slug";s:3:"cbs";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"1";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:2;a:12:{s:4:"name";s:21:"Here is radio buttons";s:4:"slug";s:3:"rds";s:11:"description";s:0:"";s:4:"type";s:12:"radiobuttons";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_radiobuttons_options";a:4:{s:17:"radiobutton_num_2";a:2:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";}s:17:"radiobutton_num_3";a:2:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";s:17:"radiobutton_num_4";a:2:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";}}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"2";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:3;a:11:{s:4:"name";s:0:"";s:4:"slug";s:0:"";s:11:"description";s:0:"";s:4:"type";s:4:"text";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"3";s:7:"deleted";s:1:"1";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:4;a:11:{s:4:"name";s:16:"Here is dropdown";s:4:"slug";s:4:"drps";s:11:"description";s:0:"";s:4:"type";s:8:"dropdown";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:4:{s:15:"enable_multiple";s:1:"0";s:14:"dropdown_num_2";a:2:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_3";a:2:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_4";a:2:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";}}s:2:"id";s:1:"4";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}}s:14:"fields_by_slug";a:0:{}s:7:"deleted";b:0;s:8:"gui_view";s:4:"list";s:15:"added_with_code";b:0;s:12:"fields_count";i:3;}}');
		$field_groups_normalized_expected = unserialize('a:1:{i:19;a:12:{s:2:"id";i:19;s:3:"key";s:19:"wpml_radiosandstuff";s:4:"slug";s:19:"wpml_radiosandstuff";s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:6:"fields";a:4:{i:1;a:12:{s:4:"name";s:18:"Here is checkboxes";s:4:"slug";s:3:"cbs";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"1";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:2;a:13:{s:4:"name";s:21:"Here is radio buttons";s:4:"slug";s:3:"rds";s:11:"description";s:0:"";s:4:"type";s:12:"radiobuttons";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_radiobuttons_options";a:4:{s:17:"radiobutton_num_2";a:2:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";}s:17:"radiobutton_num_3";a:2:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";s:17:"radiobutton_num_4";a:2:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";}}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"2";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:5:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"radiobuttons";a:2:{s:6:"values";a:3:{i:0;a:3:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";s:3:"num";i:2;}i:1;a:4:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";s:3:"num";i:3;s:7:"checked";b:1;}i:2;a:3:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";s:3:"num";i:4;}}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:3;a:12:{s:4:"name";s:0:"";s:4:"slug";s:0:"";s:11:"description";s:0:"";s:4:"type";s:4:"text";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"3";s:7:"deleted";s:1:"1";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:4;a:12:{s:4:"name";s:16:"Here is dropdown";s:4:"slug";s:4:"drps";s:11:"description";s:0:"";s:4:"type";s:8:"dropdown";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:4:{s:15:"enable_multiple";s:1:"0";s:14:"dropdown_num_2";a:2:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_3";a:2:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_4";a:2:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";}}s:2:"id";s:1:"4";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:2:{s:15:"enable_multiple";s:1:"0";s:6:"values";a:3:{i:0;a:3:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";s:3:"num";i:2;}i:1;a:3:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";s:3:"num";i:3;}i:2;a:3:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";s:3:"num";i:4;}}}}}}s:14:"fields_by_slug";a:0:{}s:7:"deleted";b:0;s:8:"gui_view";s:4:"list";s:15:"added_with_code";b:0;s:12:"fields_count";i:3;}}');
		$field_groups_normalized = self::$sf->normalize_fieldgroups( $field_groups );
		$this->assertEquals( $field_groups_normalized_expected, $field_groups_normalized);

	}


}

