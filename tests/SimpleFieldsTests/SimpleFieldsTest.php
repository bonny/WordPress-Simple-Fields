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

	// test defaults, should all be empty since we cleared the db...
	function testDefaults()
	{
		$this->assertEquals(array(), $this->sf->get_post_connectors());
		$this->assertEquals(array(), $this->sf->get_field_groups());
		$this->assertEquals(array(), $this->sf->get_field_groups());
	}

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


	function testInsertManuallyAddedFields() {
		_insert_manually_added_fields();
	}	

	// insert and test manually added fields
	function testManuallyAddedFields()
	{

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
	
		$post_id = 11;
	
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
			'is_image' => true,
			'url' => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
			'mime' => 'image/jpeg',
			'link' => array(
				'full' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="1024" height="768" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-full" alt="product-cat-2" title="product-cat-2" /></a>',
				'thumbnail' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="150" height="112" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-thumbnail" alt="product-cat-2" title="product-cat-2" /></a>',
				'medium' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="300" height="225" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-medium" alt="product-cat-2" title="product-cat-2" /></a>',
				'large' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="584" height="438" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-large" alt="product-cat-2" title="product-cat-2" /></a>',
				'post-thumbnail' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="384" height="288" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-post-thumbnail" alt="product-cat-2" title="product-cat-2" /></a>',
				'large-feature' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="384" height="288" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-large-feature" alt="product-cat-2" title="product-cat-2" /></a>',
				'small-feature' => '<a href=\'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg\' title=\'product-cat-2\'><img width="400" height="300" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-small-feature" alt="product-cat-2" title="product-cat-2" /></a>'
			),
			'image' => array(
				'full' => '<img width="1024" height="768" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-full" alt="product-cat-2" title="product-cat-2" />',
				'thumbnail' => '<img width="150" height="112" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-thumbnail" alt="product-cat-2" title="product-cat-2" />',
				'medium' => '<img width="300" height="225" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-medium" alt="product-cat-2" title="product-cat-2" />',
				'large' => '<img width="584" height="438" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-large" alt="product-cat-2" title="product-cat-2" />',
				'post-thumbnail' => '<img width="384" height="288" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-post-thumbnail" alt="product-cat-2" title="product-cat-2" />',
				'large-feature' => '<img width="384" height="288" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-large-feature" alt="product-cat-2" title="product-cat-2" />',
				'small-feature' => '<img width="400" height="300" src="http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg" class="attachment-small-feature" alt="product-cat-2" title="product-cat-2" />'
			),
			'image_src' => array(
				'full' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 1024,
					2 => 768,
					3 => false
				),
				'thumbnail' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 150,
					2 => 112,
					3 => false
				),
				'medium' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 300,
					2 => 225,
					3 => false
				),
				'large' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 584,
					2 => 438,
					3 => false
				),
				'post-thumbnail' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 384,
					2 => 288,
					3 => false
				),
				'large-feature' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 384,
					2 => 288,
					3 => false
				),
				'small-feature' => array(
					0 => 'http://unit-test.simple-fields.com/wp/wp-content/uploads/2012/10/product-cat-2.jpeg',
					1 => 400,
					2 => 300,
					3 => false
				)
			),
			'metadata' => array(
				'width' => '1024',
				'height' => '768',
				'hwstring_small' => 'height=\'96\' width=\'128\'',
				'file' => '2012/10/product-cat-2.jpeg',
				'image_meta' => array(
					'aperture' => '0',
					'credit' => '',
					'camera' => '',
					'caption' => '',
					'created_timestamp' => '0',
					'copyright' => '',
					'focal_length' => '0',
					'iso' => '0',
					'shutter_speed' => '0',
					'title' => ''
				)
			),
			'post' => get_post($attachment_id)
		);
		$this->assertEquals($vals_expected, $vals);
		
		$vals = simple_fields_value("field_post", $post_id, "extended_return=1");
		$vals_expected = array(
			'id' => 11,
			'title' => 'Post with fields',
			'permalink' => 'http://unit-test.simple-fields.com/?p=11',
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
		$vals_expected = array ( 'id' => 1, 'first_name' => '', 'last_name' => '', 'user_login' => 'admin', 'user_email' => 'admin@simple-fields.com', 'user_nicename' => 'admin', 'display_name' => 'admin', 'user' => get_user_by("id", 1));
		$this->assertEquals($vals_expected, $vals);
				
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

		$arr = array(
		    0 => 'post',
		    1 => 'page'
		);
		$this->assertEquals( $arr, $this->sf->get_post_connector_attached_types() );

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
		$this->assertEquals($arr, $this->sf->get_connector_by_id(1));
		
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
		$this->assertEquals($arr, $this->sf->get_post_connectors() );

	}

	public function testSaveGetOptions() {
		
		$this->sf->save_options(array(
			"phpunittest_save_option" => "new saved value"
		));
		
		$options = $this->sf->get_options();
		$this->assertArrayHasKey("phpunittest_save_option", $options);

		$this->sf->save_options(array(
			"phpunittest_save_option" => "new saved value",
			"phpunittest_save_another_option" => "another value",
		));

		$options = $this->sf->get_options();
		$this->assertArrayHasKey("phpunittest_save_option", $options);
		$this->assertArrayHasKey("phpunittest_save_another_option", $options);

		$this->assertEquals($options["phpunittest_save_another_option"], "another value");

	}
	
	public function testGetAllForPost() {

		$post_id = 11;
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
		    'id' => 4,
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
						"id" => 4,
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
		    'id' => 3,
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
		    "added_with_code" => true
		);

		unset($arr_return["fields_by_slug"]);
		
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
                'id' => 2,
                'key' => 'test_connector',
                'slug' => 'test_connector',
                'name' => 'A test connector',
                'field_groups' => array(
                                4 => array(
                                                'id' => 4,
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
                'id' => 3,
                'key' => 'another_connector',
                'slug' => 'another_connector',
                'name' => 'Another connector',
                'field_groups' => array(
                                3 => array(
                                                'id' => 3,
                                                'slug' => 'my_new_field_group_all_fields',
                                                'key' => 'my_new_field_group_all_fields',
                                                'name' => 'Test field group with all fields',
                                                'deleted' => 0,
                                                'context' => 'normal',
                                                'priority' => 'low'
                                ),
                                4 => array(
                                                'id' => 4,
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
		$this->testManuallyAddedFields();

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

	public function test_misc() {
		
		// Test meta key
		
		// older format
		$key = $this->sf->get_meta_key(1, 2, 3);
		$this->assertEquals("_simple_fields_fieldGroupID_1_fieldID_2_numInSet_3", $key);

		// newer format
		$key = $this->sf->get_meta_key(1, 2, 3);
		$this->assertEquals("_simple_fields_fieldGroupID_1_fieldID_2_numInSet_3", $key);

		// test that normalization of fields works
		$field_groups = unserialize('a:1:{i:19;a:12:{s:2:"id";i:19;s:3:"key";s:19:"wpml_radiosandstuff";s:4:"slug";s:19:"wpml_radiosandstuff";s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:6:"fields";a:4:{i:1;a:11:{s:4:"name";s:18:"Here is checkboxes";s:4:"slug";s:3:"cbs";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"1";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:2;a:12:{s:4:"name";s:21:"Here is radio buttons";s:4:"slug";s:3:"rds";s:11:"description";s:0:"";s:4:"type";s:12:"radiobuttons";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_radiobuttons_options";a:4:{s:17:"radiobutton_num_2";a:2:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";}s:17:"radiobutton_num_3";a:2:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";s:17:"radiobutton_num_4";a:2:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";}}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"2";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:3;a:11:{s:4:"name";s:0:"";s:4:"slug";s:0:"";s:11:"description";s:0:"";s:4:"type";s:4:"text";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"3";s:7:"deleted";s:1:"1";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}i:4;a:11:{s:4:"name";s:16:"Here is dropdown";s:4:"slug";s:4:"drps";s:11:"description";s:0:"";s:4:"type";s:8:"dropdown";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:4:{s:15:"enable_multiple";s:1:"0";s:14:"dropdown_num_2";a:2:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_3";a:2:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_4";a:2:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";}}s:2:"id";s:1:"4";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}}}s:14:"fields_by_slug";a:0:{}s:7:"deleted";b:0;s:8:"gui_view";s:4:"list";s:15:"added_with_code";b:0;s:12:"fields_count";i:3;}}');
		$field_groups_normalized_expected = unserialize('a:1:{i:19;a:12:{s:2:"id";i:19;s:3:"key";s:19:"wpml_radiosandstuff";s:4:"slug";s:19:"wpml_radiosandstuff";s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:6:"fields";a:4:{i:1;a:12:{s:4:"name";s:18:"Here is checkboxes";s:4:"slug";s:3:"cbs";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"1";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:2;a:13:{s:4:"name";s:21:"Here is radio buttons";s:4:"slug";s:3:"rds";s:11:"description";s:0:"";s:4:"type";s:12:"radiobuttons";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_radiobuttons_options";a:4:{s:17:"radiobutton_num_2";a:2:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";}s:17:"radiobutton_num_3";a:2:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";s:17:"radiobutton_num_4";a:2:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";}}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"2";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:5:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"radiobuttons";a:2:{s:6:"values";a:3:{i:0;a:3:{s:5:"value";s:13:"Radiobutton 1";s:7:"deleted";s:1:"0";s:3:"num";i:2;}i:1;a:4:{s:5:"value";s:14:"And the second";s:7:"deleted";s:1:"0";s:3:"num";i:3;s:7:"checked";b:1;}i:2;a:3:{s:5:"value";s:17:"How about a third";s:7:"deleted";s:1:"0";s:3:"num";i:4;}}s:22:"checked_by_default_num";s:17:"radiobutton_num_3";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:3;a:12:{s:4:"name";s:0:"";s:4:"slug";s:0:"";s:11:"description";s:0:"";s:4:"type";s:4:"text";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:1:{s:15:"enable_multiple";s:1:"0";}s:2:"id";s:1:"3";s:7:"deleted";s:1:"1";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:1:{s:15:"enable_multiple";s:1:"0";}}}i:4;a:12:{s:4:"name";s:16:"Here is dropdown";s:4:"slug";s:4:"drps";s:11:"description";s:0:"";s:4:"type";s:8:"dropdown";s:21:"type_textarea_options";a:1:{s:11:"size_height";s:7:"default";}s:17:"type_post_options";a:1:{s:20:"additional_arguments";s:0:"";}s:25:"type_taxonomyterm_options";a:1:{s:20:"additional_arguments";s:0:"";}s:21:"type_dropdown_options";a:4:{s:15:"enable_multiple";s:1:"0";s:14:"dropdown_num_2";a:2:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_3";a:2:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";}s:14:"dropdown_num_4";a:2:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";}}s:2:"id";s:1:"4";s:7:"deleted";s:1:"0";s:11:"field_group";a:6:{s:2:"id";i:19;s:4:"name";s:32:"Radiobuttons, checkbox, dropdown";s:4:"slug";s:19:"wpml_radiosandstuff";s:11:"description";s:0:"";s:10:"repeatable";b:0;s:12:"fields_count";i:3;}s:7:"options";a:4:{s:8:"textarea";a:1:{s:11:"size_height";s:7:"default";}s:4:"post";a:1:{s:20:"additional_arguments";s:0:"";}s:12:"taxonomyterm";a:1:{s:20:"additional_arguments";s:0:"";}s:8:"dropdown";a:2:{s:15:"enable_multiple";s:1:"0";s:6:"values";a:3:{i:0;a:3:{s:5:"value";s:10:"Dropdown 1";s:7:"deleted";s:1:"0";s:3:"num";i:2;}i:1;a:3:{s:5:"value";s:21:"And a second dropdown";s:7:"deleted";s:1:"0";s:3:"num";i:3;}i:2;a:3:{s:5:"value";s:29:"Dropdowns has third value too";s:7:"deleted";s:1:"0";s:3:"num";i:4;}}}}}}s:14:"fields_by_slug";a:0:{}s:7:"deleted";b:0;s:8:"gui_view";s:4:"list";s:15:"added_with_code";b:0;s:12:"fields_count";i:3;}}');
		$field_groups_normalized = $this->sf->normalize_fieldgroups( $field_groups );
		$this->assertEquals( $field_groups_normalized_expected, $field_groups_normalized);

	}

	/**
	 * A contrived example using some WordPress functionality
	 */
	 /*
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
	*/
}
