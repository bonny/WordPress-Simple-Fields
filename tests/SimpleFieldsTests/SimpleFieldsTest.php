<?php
/**
 * MyPlugin Tests
 */
class MyPluginTest extends WP_UnitTestCase {

	public function setUp()
	{
		parent::setUp();
		global $sf;
		$this->sf = $sf;
	}

	public function testAppendContent()
	{
		#$this->assertEquals( "<p>Hello WordPress Unit Tests</p>", $this->my_plugin->append_content(''), '->append_content() appends text' );
	}

	// test defaults, should all be empty since we cleared the db...
	function testDefaults()
	{
		$this->assertEquals(array(), $this->sf->get_post_connectors());
		$this->assertEquals(array(), $this->sf->get_field_groups());
		$this->assertEquals(array(), $this->sf->get_field_groups());
	}

	// Test output of debug function
	function test_debug()
	{
		$this->expectOutputString("<pre class='sf_box_debug'>this is simple fields debug function</pre>");
		sf_d("this is simple fields debug function");
	}

	// insert and test manually added fields
	function testManuallyAddedFields()
	{

		_insert_manually_added_fields();

		$post_id = 11;

		// test single/first values
		$this->assertEquals("Text entered in the text field", simple_fields_value("field_text", $post_id));
		$this->assertEquals("Text entered in the textarea", simple_fields_value("field_textarea", $post_id));
		$this->assertEquals("<p>Text entered in the TinyMCE-editor.</p>\n", simple_fields_value("field_textarea_html", $post_id));
		$this->assertEquals("1", simple_fields_value("field_checkbox", $post_id));
		$this->assertEquals("radiobutton_num_4", simple_fields_value("field_radiobuttons", $post_id));
		$this->assertEquals("dropdown_num_3", simple_fields_value("field_dropdown", $post_id));
		$this->assertEquals(14, simple_fields_value("field_file", $post_id));
		$this->assertEquals(11, simple_fields_value("field_post", $post_id));
		$this->assertEquals("post_tag", simple_fields_value("field_taxonomy", $post_id));
		$this->assertEquals(array(0 => 1), simple_fields_value("field_taxonomy_term", $post_id));
		$this->assertEquals("FF3C26", simple_fields_value("field_color", $post_id));
		$this->assertEquals("12/10/2012", simple_fields_value("field_date", $post_id));
		$this->assertEquals(1, simple_fields_value("field_user", $post_id));

		// test repeatable/all values
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
			0 => 11,
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

	}

	public function testPostConnectors() {
		
		// testa connectors
		// sätt connectors manuellt på några poster
		// testa därefter om det är rätt stuff
		
		$post_with_fields = 11;
		$saved_connector_to_use = get_post_meta($post_with_fields, "_simple_fields_selected_connector", true);
		$this->assertEquals(1, $saved_connector_to_use);
		$this->assertEquals(1, $this->sf->get_selected_connector_for_post($post_with_fields));

		$post_with_no_connector = 24;
		$saved_connector_to_use = get_post_meta($post_with_no_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__none__", $saved_connector_to_use);
		$this->assertEquals("__none__", $this->sf->get_selected_connector_for_post($post_with_no_connector));

		$post_with_inherit_connector = 26;
		$saved_connector_to_use = get_post_meta($post_with_inherit_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__inherit__", $saved_connector_to_use);
		$this->assertEquals("__inherit__", $this->sf->get_selected_connector_for_post($post_with_inherit_connector));

		// pages
		$page_with_fields = 32;
		$saved_connector_to_use = get_post_meta($page_with_fields, "_simple_fields_selected_connector", true);
		$this->assertEquals(1, $saved_connector_to_use);
		$this->assertEquals(1, $this->sf->get_selected_connector_for_post($page_with_fields));
		$this->assertEquals("post_connector_manually", simple_fields_connector($page_with_fields));

		$page_with_no_connector = 36;
		$saved_connector_to_use = get_post_meta($page_with_no_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__none__", $saved_connector_to_use);
		$this->assertEquals("__none__", $this->sf->get_selected_connector_for_post($page_with_no_connector));
		$this->assertEmpty(simple_fields_connector($page_with_no_connector));

		// page is a child of a page with fields, so it will use the connector of the parent
		$page_with_inherit_connector = 34;
		$saved_connector_to_use = get_post_meta($page_with_inherit_connector, "_simple_fields_selected_connector", true);
		$this->assertEquals("__inherit__", $saved_connector_to_use);
		$this->assertEquals(1, $this->sf->get_selected_connector_for_post($page_with_inherit_connector));
		$this->assertEquals("post_connector_manually", simple_fields_connector($page_with_inherit_connector));
		
		var_export($this->sf->get_connector_by_id(1));
		print_r( $this->sf->get_connector_by_id(1) );

		
		
	}

	public function testNextThing() {
		/*
			what to write tests for:
			simple_fields_query_posts
			function simple_fields_set_value($post_id, $field_slug, $new_numInSet = null, $new_post_connector = null, $new_value) {
			get_connector_by_id($connector_id) {
			get_post_connector_attached_types
			get_post_connectors
			get_field_group($group_id)
			get_field_in_group($field_group, $field_id)
			get_post_connectors_for_post_type($post_type)
			Extension API
			save_options
			get_options
			simple_fields_get_all_fields_and_values_for_post
			simple_fields_register_field_group($slug = "", $new_field_group = array())
			simple_fields_register_post_connector($unique_name = "", $new_post_connector = array())
			simple_fields_register_post_type_default($connector_id_or_special_type = "", $post_type = "post")
		*/
	}

	/**
	 * A contrived example using some WordPress functionality
	 */
	public function testPostTitle()
	{

		// This will simulate running WordPress' main query.
		// See wordpress-tests/lib/testcase.php
		# $this->go_to('http://unit-test.simple-fields.com/wordpress/?p=1');

		// Now that the main query has run, we can do tests that are more functional in nature
		#global $wp_query;
		#sf_d($wp_query);
		#$post = $wp_query->get_queried_object();
		#var_dump($post);
		#$this->assertEquals('Hello world!', $post->post_title );
		#$this->assertEquals('Hello world!', $post->post_title );
	}
}
