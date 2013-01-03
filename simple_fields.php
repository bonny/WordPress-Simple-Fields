<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://simple-fields.com
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 1.1.6
Author: Pär Thernström
Author URI: http://eskapism.se/
License: GPL2
*/

/*  Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Class to keep all simple fields stuff together a bit better
 */ 
class simple_fields {

	const DEBUG_ENABLED = false; // set to true to enable some debug output
	
	public 

		// Looks something like this: "Simple-Fields-GIT/simple_fields.php"
		$plugin_foldername_and_filename,
	
		// array with registered field type objects
		$registered_field_types,
		
		// key to use in cache
		$ns_key
		
	;


	/**
	 * Init is where we setup actions and filers and loads stuff and a little bit of this and that
	 *
	 */
	function init() {

		define( "SIMPLE_FIELDS_URL", plugins_url(basename(dirname(__FILE__))). "/");
		define( "SIMPLE_FIELDS_NAME", "Simple Fields");
		define( "SIMPLE_FIELDS_VERSION", "1.1.6");

		load_plugin_textdomain( 'simple-fields', null, basename(dirname(__FILE__)).'/languages/');
		
		// setup cache
		// based on stuff found here:
		// http://core.trac.wordpress.org/ticket/4476
		$ns_key = wp_cache_get( 'simple_fields_namespace_key', 'simple_fields' );
		if ( $ns_key === false ) {
			wp_cache_set( 'simple_fields_namespace_key', 1, 'simple_fields' );
			// echo "cache key init set";
		}
		$this->ns_key = wp_cache_get( 'simple_fields_namespace_key', 'simple_fields' );
		// echo "ns_key is: $this->ns_key"; // 1

		require( dirname(__FILE__) . "/functions.php" );
		require( dirname(__FILE__) . "/class_simple_fields_field.php" );
		
		// require( dirname(__FILE__) . "/field_types/field_example.php" );
		// require( dirname(__FILE__) . "/field_types/field_minimalistic_example.php" );
		
		// Load field types
		require( dirname(__FILE__) . "/field_types/field_divider.php" );
		require( dirname(__FILE__) . "/field_types/field_date_v2.php" );

		$this->plugin_foldername_and_filename = basename(dirname(__FILE__)) . "/" . basename(__FILE__);
		$this->registered_field_types = array();

		// Actions and filters
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_init', array($this, 'check_upgrade_stuff') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action( 'admin_menu', array($this, "admin_menu") );
		add_action( 'admin_head', array($this, 'admin_head') );
		add_action( 'admin_head', array($this, 'admin_head_select_file') );
		add_filter( 'plugin_row_meta', array($this, 'set_plugin_row_meta'), 10, 2 );
		add_action( 'admin_footer', array($this, 'admin_footer') );
		add_action( 'admin_init', array($this,'post_admin_init') );
		add_action( 'dbx_post_sidebar', array($this, 'post_dbx_post_sidebar') );
		add_action( 'save_post', array($this, 'save_postdata') );
		add_action( 'plugins_loaded', array($this, 'plugins_loaded') );
		add_action( 'init', array($this, "maybe_add_debug_info") ); 

		// Hacks for media select dialog
		add_filter( 'media_send_to_editor', array($this, 'media_send_to_editor'), 15, 2 );
		add_filter( 'media_upload_tabs', array($this, 'media_upload_tabs'), 15 );
		add_filter( 'media_upload_form_url', array($this, 'media_upload_form_url') );

		// Ajax calls
		add_action( 'wp_ajax_simple_fields_metabox_fieldgroup_add', array($this, 'metabox_fieldgroup_add') );
		add_action( 'wp_ajax_simple_fields_field_type_post_dialog_load', array($this, 'field_type_post_dialog_load') );
		add_action( 'wp_ajax_simple_fields_field_group_add_field', array($this, 'field_group_add_field') );

		// Boot up
		do_action("simple_fields_init", $this);

	}

	// check some things regarding update
	function check_upgrade_stuff() {

		global $wpdb;

		$db_version = get_option("simple_fields_db_version");

		if ($db_version === FALSE) {

			// 1 = the first version, nothing done during update
			$db_version = 1;
			update_option("simple_history_db_version", 1);
		
		}

		// Do things depending on current version
		// ...to come...
		
	}
	
		
	/**
	 * When all plugins have loaded = simple fields has also loaded = safe to add custom field types
	 */
	function plugins_loaded() {
		do_action("simple_fields_register_field_types");
	}

	/**
	 * Gets the pattern that are allowed for slugs
	 * @return string
	 */
	function get_slug_pattern() {
		return "[A-Za-z0-9_]+";
	}
	
	/**
	 * Get the title for a slug
	 * I.e. the help text that the input field will show when the slug pattern is not matched
	 */
	function get_slug_title() {
		return __("Allowed chars: a-z and underscore.", 'simple-fields');
	}
	
	/**
	 * Returns a post connector
	 * @param int $connector_id
	 */
	function get_connector_by_id($connector_id) {

		$connectors = $this->get_post_connectors();
		if (isset($connectors[$connector_id])) {
			return $connectors[$connector_id];
		} else {
			return FALSE;
		}
	}

	/**
	 * If setting debug = true then output some debug stuff a little here and there
	 * Hopefully this saves us some var_dump/sf_d/echo all the time
	 * usage:
	 * first set DEBUG_ENABLED = true in beginning of class
	 * then:
	 * simple_fields("Saved post connector", array("description" => $value, "description n" => $value_n));
	 */
	public static function debug($description, $details) {
		if (self::DEBUG_ENABLED) {
			echo "<pre class='sf_box_debug'>";
			echo "<strong>".$description."</strong>";
			if ($details) {
				echo "<br>";
				echo htmlspecialchars(print_r($details, TRUE), ENT_QUOTES, 'UTF-8');
			} else {
				echo "<br>&lt;Empty thing.&gt;";
			}
			echo "</pre>";
		}
	}

	/**
	 * Enqueue styles and scripts, but on on pages that use simple fields
	 * Should speed up the loading of other pages a bit
	 */
	function admin_enqueue_scripts($hook) {

		// pages to load on = admin/settings page for SF + edit post
		$is_on_simple_fields_page = FALSE;
		$page_type = "";

		$current_screen = get_current_screen();
		if ($current_screen->base == "post" && in_array($current_screen->post_type, $this->get_post_connector_attached_types())) {
			$is_on_simple_fields_page = TRUE;
			$page_type = "post";
		} elseif ($current_screen->base === "media-upload") {
			$is_on_simple_fields_page = TRUE;
			$page_type = "media-upload";
		} elseif ($current_screen->id === "settings_page_simple-fields-options") {
			$is_on_simple_fields_page = TRUE;
			$page_type = "settings";
		}
		
		if (!$is_on_simple_fields_page) return;

		if ("settings" === $page_type) {

			// Settings page
			wp_enqueue_style('simple-fields-styles', SIMPLE_FIELDS_URL.'styles.css', false, SIMPLE_FIELDS_VERSION);


		} else {

			// Edit post etc.
			wp_enqueue_script("thickbox");
			wp_enqueue_style("thickbox");
			wp_enqueue_script("jscolor", SIMPLE_FIELDS_URL . "jscolor/jscolor.js"); // color picker for type color
			wp_enqueue_script("simple-fields-date", SIMPLE_FIELDS_URL . "datepicker/date.js"); // date picker for type date
			
			// Date picker for type date
			wp_enqueue_script("sf-jquery-datepicker", SIMPLE_FIELDS_URL . "datepicker/jquery.datePicker.js");
			wp_enqueue_style('sf-jquery-datepicker', SIMPLE_FIELDS_URL.'datepicker/datePicker.css', false, SIMPLE_FIELDS_VERSION);

			// Chosen for multi selects
			// wp_enqueue_script("chosen.jquery", SIMPLE_FIELDS_URL . "js/chosen/chosen.jquery.min.js");
			// wp_enqueue_style("chosen", SIMPLE_FIELDS_URL.'js/chosen/chosen.css', false, SIMPLE_FIELDS_VERSION);

			wp_enqueue_style('simple-fields-styles-post', SIMPLE_FIELDS_URL.'styles-edit-post.css', false, SIMPLE_FIELDS_VERSION);
	
		}

		// Common scripts

		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_script("jquery-effects-highlight");

		wp_register_script('simple-fields-scripts', SIMPLE_FIELDS_URL.'scripts.js', false, SIMPLE_FIELDS_VERSION);
		wp_localize_script('simple-fields-scripts', 'sfstrings', array(
			'page_type' => $page_type,
			'txtDelete' => __('Delete', 'simple-fields'),
			'confirmDelete' => __('Delete this field?', 'simple-fields'),
			'confirmDeleteGroup' => __('Delete this group?', 'simple-fields'),
			'confirmDeleteConnector' => __('Delete this post connector?', 'simple-fields'),
			'confirmDeleteRadio' => __('Delete radio button?', 'simple-fields'),
			'confirmDeleteDropdown' => __('Delete dropdown value?', 'simple-fields'),
			'adding' => __('Adding...', 'simple-fields'),
			'add' => __('Add', 'simple-fields'),
			'confirmRemoveGroupConnector' => __('Remove field group from post connector?', 'simple-fields'),
			'confirmRemoveGroup' => __('Remove this field group?', 'simple-fields'),
			'context' => __('Context', 'simple-fields'),
			'normal' => __('normal'),
			'advanced' => __('advanced'),
			'side' => __('side'),
			'low' => __('low'),
			'high' => __('high'),
		));
		wp_enqueue_script('simple-fields-scripts');

		// Hook for plugins
		do_action("simple_fields_enqueue_scripts", $this);

	}

	/**
	 * Stuff that is being runned only when in admin (i.e. not on front of site)
	 */
	function admin_init() {

		define( "SIMPLE_FIELDS_FILE", menu_page_url("simple-fields-options", false) );

	}

	/**
	 * Add settings link to plugin page
	 * Hopefully this helps some people to find the settings page quicker
	 */
	function set_plugin_row_meta($links, $file) {

		if ($file == $this->plugin_foldername_and_filename) {
			return array_merge(
				$links,
				array( sprintf( '<a href="options-general.php?page=%s">%s</a>', "simple-fields-options", __('Settings') ) )
			);
		}
		return $links;

	}


	/**
	 * Return an array of the post types that we have set up post connectors for
	 *
	 * Format of return:
	 *
	 * Array
	 * (
	 *     [0] => post
	 *     [1] => page
	 *     [2] => testposttype
	 * )
	 *
	 * @param return array
	 */
	function get_post_connector_attached_types() {
		global $sf;
		$post_connectors = $this->get_post_connectors();
		$arr_post_types = array();
		foreach ($post_connectors as $one_post_connector) {
			$arr_post_types = array_merge($arr_post_types, (array) $one_post_connector["post_types"]);
		}
		$arr_post_types = array_unique($arr_post_types);
		return $arr_post_types;
	}


	/**
	 * Get default connector for a post type
	 * If no connector has been set, __none__ is returned
	 *
	 * @param string $post_type
	 * @return mixed int connector id or string __none__ or __inherit__
	 */
	function get_default_connector_for_post_type($post_type) {
		$post_type_defaults = $this->get_post_type_defaults();
		$selected_post_type_default = (isset($post_type_defaults[$post_type]) ? $post_type_defaults[$post_type] : "__none__");
		return $selected_post_type_default;
	}

	/**
	 * Output HTML for dialog in bottom
	 */
	function admin_footer() {
		// HTML for post dialog
		?><div class="simple-fields-meta-box-field-group-field-type-post-dialog hidden"></div><?php
	}
	
	/**
	 * output nonce
	 */
	function post_dbx_post_sidebar() {
		?>
		<input type="hidden" name="simple_fields_nonce" id="simple_fields_nonce" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>" />
		<?php
	}

	/**
	 * Saves simple fields data when post is being saved
	 */
	function save_postdata($post_id = null, $post = null) {

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		// so not checking nonce can lead to errors, for example losing post connector
		if (!isset($_POST['simple_fields_nonce']) || !wp_verify_nonce( $_POST['simple_fields_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
	
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return $post_id; }

		// dont's save if is revision
		if (wp_is_post_revision($post_id) !== FALSE) return $post_id;
		
		// attach post connector
		$simple_fields_selected_connector = (isset($_POST["simple_fields_selected_connector"])) ? $_POST["simple_fields_selected_connector"] : null;
		update_post_meta($post_id, "_simple_fields_selected_connector", $simple_fields_selected_connector);
	
		$post_id = (int) $post_id;
		$fieldgroups = (isset($_POST["simple_fields_fieldgroups"])) ? $_POST["simple_fields_fieldgroups"] : null;
		$field_groups_option = $this->get_field_groups();
	
		if ( !$table = _get_meta_table("post") ) { return false; }

		global $wpdb;

		// We have a post_id and we have fieldgroups
		if ($post_id && is_array($fieldgroups)) {
	
			#echo "fieldgroups is:";sf_d($fieldgroups);

			// Delete all exisiting custom fields meta that begins with "_simple_fields_fieldGroupID_", .ie. position 0
			$post_meta = get_post_custom($post_id);
			foreach ($post_meta as $meta_key => $meta_val) {
				if ( strpos($meta_key, "_simple_fields_fieldGroupID_") === 0 ) delete_post_meta($post_id, $meta_key);
			}
	
			// cleanup missing keys, due to checkboxes not being checked
			$fieldgroups_fixed = $fieldgroups;
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
			
				foreach ($one_field_group_fields as $posted_id => $posted_vals) {
					if ($posted_id == "added") {
						continue;
					}
					$fieldgroups_fixed[$one_field_group_id][$posted_id] = array();
					// loopa igenom "added"-värdena och fixa så att allt finns
					foreach ($one_field_group_fields["added"] as $added_id => $added_val) {
						$fieldgroups_fixed[$one_field_group_id][$posted_id][$added_id] = @$fieldgroups[$one_field_group_id][$posted_id][$added_id];
					}
				}
			
			}
			$fieldgroups = $fieldgroups_fixed;
	
			// Save info about the fact that this post have been saved. This info is used to determine if a post should get default values or not.
			update_post_meta($post_id, "_simple_fields_been_saved", "1");

			// Loop through each fieldgroups
#sf_d($fieldgroups);
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
				
				// Loop through each field in each field group
#simple_fields::debug("one_field_group_fields", $one_field_group_fields);
#sf_d($one_field_group_fields);
				foreach ($one_field_group_fields as $one_field_id => $one_field_values) {

					// one_field_id = id på fältet vi sparar. t.ex. id:et på "måndag" eller "tisdag"
					// one_field_values = sparade värden för detta fält, sorterat i den ordning som syns i admin
					//					  dvs. nyaste överst (med key "new0"), och sedan key 0, key 1, osv.

#simple_fields::debug("save, loop fields, one_field_id", $one_field_id);
#simple_fields::debug("save, loop fields, one_field_values", $one_field_values);

					// determine type of field we are saving
					$field_info = isset($field_groups_option[$one_field_group_id]["fields"][$one_field_id]) ? $field_groups_option[$one_field_group_id]["fields"][$one_field_id] : NULL;
					$field_type = $field_info["type"]; // @todo: this should be a function

#simple_fields::debug("save, field_type", $field_type);

					$do_wpautop = false;
					if ($field_type == "textarea" && isset($field_info["type_textarea_options"]["use_html_editor"]) && $field_info["type_textarea_options"]["use_html_editor"] == 1) {
						// it's a tiny edit area, so use wpautop to fix p and br
						$do_wpautop = true;
					}
					
					// save entered value for each added group
					$num_in_set = 0;

					foreach ($one_field_values as $one_field_value) {
					
						$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group_id}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";
						$custom_field_value = $one_field_value;

						if (array_key_exists($field_type, $this->registered_field_types)) {
							
							// Custom field type	
							$custom_field_value = $this->registered_field_types[$field_type]->edit_save($custom_field_value);
							/*
							
							Date field:
							Array
							(
							    [date_unixtime] => 1351983600000
							)
							
							Map field:
							Array
							(
							    [lat] => 59.312089
							    [lng] => 18.074117
							    [name] => Monki Skrapan
							    [formatted_address] => GÃ¶tgatan 78, Stockholm, Sverige
							    [address_components] => [{\"long_name\":\"78\",\"short_name\":\"78\",\"types\":[\"street_number\"]},{\"long_name\":\"GÃ¶tgatan\",\"short_name\":\"GÃ¶tgatan\",\"types\":[\"route\"]},{\"long_name\":\"SÃ¶dermalm\",\"short_name\":\"SÃ¶dermalm\",\"types\":[\"sublocality\",\"political\"]},{\"long_name\":\"Stockholm\",\"short_name\":\"Stockholm\",\"types\":[\"locality\",\"political\"]},{\"long_name\":\"Stockholms lÃ¤n\",\"short_name\":\"Stockholms lÃ¤n\",\"types\":[\"administrative_area_level_2\",\"political\"]},{\"long_name\":\"SE\",\"short_name\":\"SE\",\"types\":[\"country\",\"political\"]},{\"long_name\":\"11830\",\"short_name\":\"11830\",\"types\":[\"postal_code\"]}]
							)
							*/
							//echo "xxx save value for custom field type"; sf_d($custom_field_value);

						} else {
							// core/legacy field type
							if ($do_wpautop) {
								$custom_field_value = wpautop($custom_field_value);
							}
	
						}
						
						// echo "<br>Saving value for post with id $post_id. Custom_field_key is $custom_field_key, custom_field_value is:";sf_d($custom_field_value);
						update_post_meta($post_id, $custom_field_key, $custom_field_value);
						$num_in_set++;
					
					}
	
				}
				
			}
			// if array
		} else if (empty($fieldgroups)) {
			// if fieldgroups are empty we still need to save it
			// remove existing simple fields custom fields for this post
			// @todo: this should also be using wordpress own functions
			$wpdb->query("DELETE FROM $table WHERE post_id = $post_id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");
		} 
		// echo "end save";
	
	} // save postdata

	/**
	 * adds a fieldgroup through ajax = also fetch defaults
	 * called when clicking "+ add" in post edit screen
	 */
	function metabox_fieldgroup_add() {
	
		global $sf;
	
		$simple_fields_new_fields_count = (int) $_POST["simple_fields_new_fields_count"];
		$post_id = (int) $_POST["post_id"];
		$field_group_id = (int) $_POST["field_group_id"];
	
		$num_in_set = "new{$simple_fields_new_fields_count}";
		$this->meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, true);
	
		exit;
	}


	/**
	 * Output the html for a field group in the meta box on the post edit screen
	 * Also called from ajax when clicking "+ add"
	 */
	function meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, $use_defaults) {
	
		$post = get_post($post_id);
		
		$field_groups = $this->get_field_groups();
		$current_field_group = $field_groups[$field_group_id];
		$repeatable = (bool) $current_field_group["repeatable"];
		$field_group_css = "simple-fields-fieldgroup-$field_group_id";
		/* if (isset($current_field_group["slug"]) && !empty($current_field_group["slug"])) {
			$field_group_css .= " simple-fields-fieldgroup-" . $current_field_group["slug"];
		}*/

		?>
		<li class="sf-cf simple-fields-metabox-field-group <?php echo $field_group_css ?>">
			<?php // must use this "added"-thingie do be able to track added field group that has no added values (like unchecked checkboxes, that we can't detect ?>
			<input type="hidden" name="simple_fields_fieldgroups[<?php echo $field_group_id ?>][added][<?php echo $num_in_set ?>]" value="1" />
			<div class="simple-fields-metabox-field-group-handle"></div>
			<?php
			// if repeatable: add remove-link
			if ($repeatable) {
				?><div class="hidden simple-fields-metabox-field-group-delete"><a href="#" title="<?php _e('Remove field group', 'simple-fields') ?>"></a></div><?php
			}
			
			// Output content for each field in this fieldgroup
			// LI = fieldgroup
			// DIV = field
			foreach ($current_field_group["fields"] as $field) {
			
				if ($field["deleted"]) { continue; }

				$field_id = $field["id"];
				$field_unique_id = "simple_fields_fieldgroups_{$field_group_id}_{$field_id}_{$num_in_set}";
				$field_name = "simple_fields_fieldgroups[$field_group_id][$field_id][$num_in_set]";
				$field_class = "simple-fields-fieldgroups-field-{$field_group_id}-{$field_id} ";
				$field_class .= "simple-fields-fieldgroups-field-type-" . $field["type"];
				if (isset($field["slug"]) && !empty($field["slug"])) {
					$field_class .= " simple-fields-fieldgroups-field-slug-" . $field["slug"];
				}
				
				// Fetch saved value for field from db/post meta
				// Returned value is:
				//  - string if core fields
				//  - array if field type extension, unless the field extension overrides this
				$custom_field_key = "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}";
				$saved_value = get_post_meta($post_id, $custom_field_key, true);
				
				$description = "";
				if (!empty($field["description"])) {
					$description = sprintf("<div class='simple-fields-metabox-field-description'>%s</div>", esc_html($field["description"]));
				}
				
				// div that wraps around each outputed field
				// Output will be similar to this
				// <div class="simple-fields-metabox-field simple-fields-fieldgroups-field-1-1 simple-fields-fieldgroups-field-type-text" data-fieldgroup_id="1" data-field_id="1" data-num_in_set="0">
				?>
				<div class="simple-fields-metabox-field sf-cf <?php echo $field_class ?>" 
					data-fieldgroup_id=<?php echo $field_group_id ?>
					data-field_id="<?php echo $field_id ?>"
					data-num_in_set=<?php echo $num_in_set ?>
					>
					<?php

					// different output depending on field type
					if ("checkbox" == $field["type"]) {
		
						if ($use_defaults) {
							$checked = @$field["type_checkbox_options"]["checked_by_default"];
						} else {
							$checked = (bool) $saved_value;
						}
						
						if ($checked) {
							$str_checked = " checked='checked' ";
						} else {
							$str_checked = "";
						}

						echo "<div class='simple-fields-metabox-field-first'>";
						echo $description;
						echo "</div>";
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input $str_checked id='$field_unique_id' type='checkbox' name='$field_name' value='1' />";
						echo "<label class='simple-fields-for-checkbox' for='$field_unique_id'> " . $field["name"] . "</label>";
						echo "</div>";
		
					} elseif ("radiobuttons" == $field["type"]) {
		
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label>" . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";

						$radio_options = $field["type_radiobuttons_options"];
						$radio_checked_by_default_num = @$radio_options["checked_by_default_num"];
	
						$loopNum = 0;
						foreach ($radio_options as $one_radio_option_key => $one_radio_option_val) {
							
							// only values like radiobutton_num_2 are allowed
							if ( strpos($one_radio_option_key, "radiobutton_num_") === FALSE) { continue; }
							
							// Skip deleted ones
							if (isset($one_radio_option_val["deleted"]) && $one_radio_option_val["deleted"]) { continue; }

							$radio_field_unique_id = $field_unique_id . "_radio_".$loopNum;
							$one_radio_option_val_val = isset($one_radio_option_val["value"]) ? $one_radio_option_val["value"] : "";
							
							$selected = "";
							if ($use_defaults) {
								if ($radio_checked_by_default_num == $one_radio_option_key) { $selected = " checked='checked' "; }
							} else {
								if ($saved_value == $one_radio_option_key) { $selected = " checked='checked' "; }
							}
													
							echo "<div class='simple-fields-metabox-field-radiobutton'>";
							echo "	<input $selected name='$field_name' id='$radio_field_unique_id' type='radio' value='$one_radio_option_key' />";
							echo "	<label for='$radio_field_unique_id' class='simple-fields-for-radiobutton'> " . $one_radio_option_val_val . "</label>";
							echo "</div>";							
							
							$loopNum++;
						}

						echo "</div>";
		
					} elseif ("dropdown" == $field["type"]) {

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";

						$enable_multiple = (isset($field["type_dropdown_options"]["enable_multiple"]) && ($field["type_dropdown_options"]["enable_multiple"] == 1));
						$str_multiple = "";
						$field_name_dropdown = $field_name;
						$field_size = 1;
						if ($enable_multiple) {
							$str_multiple = "multiple";
							$field_name_dropdown = $field_name . "[]";
							$field_size = 6;
						}
						echo "<select id='$field_unique_id' name='$field_name_dropdown' $str_multiple size='$field_size' >";
						foreach ($field["type_dropdown_options"] as $one_option_internal_name => $one_option) {
							
							if (isset($one_option["deleted"]) && $one_option["deleted"]) { continue; }
							if (strpos($one_option_internal_name, "dropdown_num_") === FALSE) continue;

							$dropdown_value_esc = esc_html($one_option["value"]);
							$selected = "";

							// Different ways of detecting selected dropdown value if multiple or single
							if ($enable_multiple) {

								$arr_saved_value_dropdown = (array) $saved_value;
								/*
								Array
								(
								    [0] => dropdown_num_2
								    [1] => dropdown_num_3
								)
								*/
								if (in_array($one_option_internal_name, $arr_saved_value_dropdown)) {
									$selected = " selected ";
								}

								
							} else {

								if ($use_defaults == false && $saved_value == $one_option_internal_name) {
									$selected = " selected ";
								}

							}

							echo "<option $selected value='$one_option_internal_name'>$dropdown_value_esc</option>";
						}
						echo "</select>";
						echo "</div>";
	
					} elseif ("file" == $field["type"]) {
	
						$current_post_id = !empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
						$attachment_id = (int) $saved_value;
						$image_html = "";
						$image_name = "";
						$view_file_url = "";
						if ($attachment_id) {
							$image_post = get_post($attachment_id);
							if ($image_post === NULL) {
								// hm.. image that no longer exists? trashed?
							} else {
								$image_thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
								$image_thumbnail = $image_thumbnail[0];
								$image_html = "<img src='$image_thumbnail' alt='' />";
								$image_name = esc_html($image_post->post_title);
							}
							$view_file_url = wp_get_attachment_url($attachment_id);
						}
						$class = "";
						if ($description) {
							//$class = "simple-fields-metabox-field-file-with-description";
						}
						echo "<div class='simple-fields-metabox-field-file $class'>";

							echo "<div class='simple-fields-metabox-field-first'>";
							echo "<label>{$field["name"]}</label>";
							echo $description;
							echo "</div>";

							echo "<div class='simple-fields-metabox-field-second'>";

							echo "<div class='simple-fields-metabox-field-file-col1'>";
								echo "<div class='simple-fields-metabox-field-file-selected-image'>$image_html</div>";
							echo "</div>";
							echo "<div class='simple-fields-metabox-field-file-col2'>";
								echo "<input type='hidden' class='text simple-fields-metabox-field-file-fileID' name='$field_name' id='$field_unique_id' value='$attachment_id' />";							
	
								$field_unique_id_esc = rawurlencode($field_unique_id);
								$file_url = get_bloginfo('wpurl') . "/wp-admin/media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=$field_unique_id_esc&post_id=$current_post_id&TB_iframe=true";
								
								echo "<a class='simple-fields-metabox-field-file-view' href='$view_file_url'>".__('View', 'simple-fields')."</a>";
								echo "<a class='thickbox simple-fields-metabox-field-file-select' href='$file_url'>".__('Select', 'simple-fields')."</a>";
								
								$class = ($attachment_id) ? " " : " hidden ";
								$href_edit = ($attachment_id) ? admin_url("media.php?attachment_id={$attachment_id}&action=edit") : "#";
								echo " <a href='{$href_edit}' class='simple-fields-metabox-field-file-edit $class'>".__('Edit', 'simple-fields') . "</a>";
								echo " <a href='#' class='simple-fields-metabox-field-file-clear $class'>".__('Clear', 'simple-fields')."</a>";							
								echo "<div class='simple-fields-metabox-field-file-selected-image-name'>$image_name</div>";
								
							echo "</div>";


							echo "</div>"; // second

						echo "</div>";
	
					} elseif ("image" == $field["type"]) {
						
						// @todo: does this field type exist??
						$text_value_esc = esc_html($saved_value);
						echo "<label>".__('image', 'simple-fields')."</label>";
						//echo $description;
						echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						
					} elseif ("textarea" == $field["type"]) {
		
						$textarea_value_esc = esc_html($saved_value);
						$textarea_options = isset($field["type_textarea_options"]) ? $field["type_textarea_options"] : array();
						
						$textarea_class = "";
						$textarea_class_wrapper = "";
						$textarea_html_extra_classes = "";
						
						// default num rows to same as WordPress uses / 2 beacuse it's always been smaller
						$textarea_rows = ((int) get_option('default_post_edit_rows', 10)) / 2;
						
						// if user has set custom height
						// since 1.0.3
						if (isset($textarea_options["size_height"])) {
							// size is small, medium, large
							$textarea_html_extra_classes .= " simple-fields-metabox-field-textarea-tinymce-size-{$textarea_options['size_height']} ";
							switch ($textarea_options["size_height"]) {
								case "small":
									$textarea_rows = 3;
									break;
								case "medium":
									$textarea_rows = 15;
									break;
								case "large":
									$textarea_rows = 30;
									break;
							}
						}
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
	
						if (isset($textarea_options["use_html_editor"])) {
							// This helps get_upload_iframe_src() determine the correct post id for the media upload button
							global $post_ID;
							if (intval($post_ID) == 0) {
								if (intval($_REQUEST['post_id']) > 0) {
									$post_ID = intval($_REQUEST['post']);
								} elseif (intval($_REQUEST['post']) > 0) {
									$post_ID = intval($_REQUEST['post']);
								}
							}
							$args = array(
								"textarea_name"	=> $field_name, 
								"editor_class" 	=> "simple-fields-metabox-field-textarea-tinymce $textarea_html_extra_classes",
								// "teeny" 		=> TRUE // possibly add in future. does not actually gain/loose much using it, right?,
								"textarea_rows"	=> $textarea_rows,
								"media_buttons"	=> TRUE
							);
							echo "<div class='simple-fields-metabox-field-textarea-tinymce-wrapper'>";
							wp_editor( $saved_value, $field_unique_id, $args );
							echo "</div>";
						} else {
							echo "<div class='simple-fields-metabox-field-textarea-wrapper'>";
							echo "<textarea class='simple-fields-metabox-field-textarea' name='$field_name' id='$field_unique_id' cols='50' rows='$textarea_rows'>$textarea_value_esc</textarea>";
							echo "</div>";
						}
						

						echo "</div>";
		
					} elseif ("text" == $field["type"]) {
		
						$text_value_esc = esc_html($saved_value);
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						echo "</div>";
		
					} elseif ("color" == $field["type"]) {
						
						$text_value_esc = esc_html($saved_value);
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";
						
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input class='text simple-fields-field-type-color {pickerClosable:true}' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						echo "</div>";
	
					} elseif ("date" == $field["type"]) {
	
						// $datef = __( 'M j, Y @ G:i' ); // same format as in meta-boxes.php
						// echo date_i18n( $datef, strtotime( current_time('mysql') ) );
						
						$text_value_esc = esc_html($saved_value);
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";
						
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input class='text simple-fields-field-type-date' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						echo "</div>";
	
					} elseif ("taxonomy" == $field["type"]) {
						
						$arr_taxonomies = get_taxonomies(array(), "objects");					
						$enabled_taxonomies = (array) @$field["type_taxonomy_options"]["enabled_taxonomies"];
						
						//echo "<pre>";print_r($enabled_taxonomies );echo "</pre>";
						
						$text_value_esc = esc_html($saved_value);
						// var_dump($saved_value);
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						
						echo "<select name='$field_name'>";
						printf("<option value=''>%s</option>", __('Select...', 'simple-fields'));
						foreach ($arr_taxonomies as $one_taxonomy) {
							if (!in_array($one_taxonomy->name, $enabled_taxonomies)) {
								continue;
							}
							$selected = ($saved_value == $one_taxonomy->name) ? ' selected="selected" ' : '';
							printf ("<option %s value='%s'>%s</option>", $selected, $one_taxonomy->name, $one_taxonomy->label);
						}
						echo "</select>";


						echo "</div>";
	
	
					} elseif ("taxonomyterm" == $field["type"]) {
						
						$enabled_taxonomy = @$field["type_taxonomyterm_options"]["enabled_taxonomy"];
						$additional_arguments = @$field["type_taxonomyterm_options"]["additional_arguments"];
	
						// hämta alla terms som finns för taxonomy $enabled_taxonomy
						// @todo: kunna skicka in args här, t.ex. för orderby
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
	

						$arr_selected_cats = (array) $saved_value;
						
						$walker = new Simple_Fields_Walker_Category_Checklist();
						$args = array(
							"taxonomy" => $enabled_taxonomy,
							"selected_cats" => $arr_selected_cats,
							"walker" => $walker,
							"sf_field_name" => $field_name // walker is ot able to get this one, therefor global
						);
						global $simple_fields_taxonomyterm_walker_field_name; // sorry for global…!
						$simple_fields_taxonomyterm_walker_field_name = $field_name;
						echo "<ul class='simple-fields-metabox-field-taxonomymeta-terms'>";
						wp_terms_checklist(NULL, $args);
						echo "</ul>";
						

						echo "</div>";
						
					} elseif ("post" == $field["type"]) {
						
						$saved_value_int = (int) $saved_value;
						if ($saved_value_int) {
							$saved_post_name = get_the_title($saved_value_int);
							$showHideClass = "";
						} else {
							$saved_post_name = "";
							$showHideClass = "hidden";
						}
						
						$type_post_options = (array) @$field["type_post_options"];
						$enabled_post_types = $type_post_options["enabled_post_types"];
						
						echo "<div class='simple-fields-metabox-field-post'>";

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
	
						echo "<div>";
						printf("<a class='%s' href='#'>%s</a>", "simple-fields-metabox-field-post-select", __("Select post", "simple-fields"));
						printf("<a class='%s' href='#'>%s</a>", "simple-fields-metabox-field-post-clear $showHideClass", __("Clear", "simple-fields"));
						echo "</div>";
						
						// output the post types that are selected for this post field
						printf("<input type='hidden' name='%s' value='%s' />", "simple-fields-metabox-field-post-enabled-post-types", join(",", $enabled_post_types));
											
						// name of the selected post
						echo "<div class='simple-fields-field-type-post-postName $showHideClass'>$saved_post_name</div>";
						
						// print the id of the current post
						echo "<input type='hidden' class='simple-fields-field-type-post-postID' name='$field_name' id='$field_unique_id' value='$saved_value_int' />";
						
						// output additional arguments for this post field
						echo "<input type='hidden' name='additional_arguments' id='additional_arguments' value='".$type_post_options['additional_arguments']."' />";
						

						echo "</div>";

						echo "</div>";
	
					} elseif ("user" == $field["type"]) {
					
						$saved_value_int = (int) $saved_value;
					
						#echo "<div class='simple-fields-metabox-field-post'>";
						// echo "<pre>"; print_r($type_post_options); echo "</pre>";
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						
						// must set orderby or it will not get any users at all. yes. it's that weird.
						$args = array(
							//' role' => 'any'
							"orderby" => "login",
							"order" => "asc"
						);
						$users_query = new WP_User_Query( $args );
						$users = $users_query->results;
						
						echo "<select name='$field_name' id='$field_unique_id'>";
						printf("<option value=''>%s</option>", __('Select...', 'simple-fields'));
						foreach ($users as $one_user) {
							$first_name = get_the_author_meta("first_name", $one_user->ID);
							$last_name = get_the_author_meta("last_name", $one_user->ID);
							$first_and_last_name = "";
							if (!empty($first_name) || !empty($last_name)) {
								$first_and_last_name = $first_name . " " . $last_name;
								$first_and_last_name = trim($first_and_last_name);
								$first_and_last_name = " ($first_and_last_name)";
							}
							
							printf("<option %s value='%s'>%s</option>", 
								($saved_value_int == $one_user->ID) ? " selected='selected' " : "",
								$one_user->ID,
								$one_user->display_name . "$first_and_last_name"
							);
						}
						echo "</select>";

						
						echo "</div>";
						#echo "</div>";
	
	
					} else {
						
						// Filed type is not "core", so check for added field types
						if (isset($this->registered_field_types[$field["type"]])) {
						
							$custom_field_type = $this->registered_field_types[$field["type"]];
							$custom_field_type->set_options_base_id($field_unique_id);
							$custom_field_type->set_options_base_name($field_name);

							// Get the options that are saved for this field type.
							// @todo: should be a method of the class? must know what field group it's connected to to be able to fetch the right one
							$custom_field_type_options = isset($field["options"][$field["type"]]) ? $field["options"][$field["type"]] : array();

							// Always output label and description, for consistency
							echo "<div class='simple-fields-metabox-field-first'>";
							echo "<label>" . $field["name"] . "</label>";
							echo $description;
							echo "</div>";
							
							echo "<div class='simple-fields-metabox-field-second'>";

							// if use_defaults is set then pass that arg to custom field types too
							if ($use_defaults) $custom_field_type_options["use_defaults"] = $use_defaults;

							// Get and output the edit-output from the field type
							// Return as array if field type has not specified other
							// xxx
							$custom_field_type_saved_value = $saved_value;
							#echo "saved value"; sf_d($custom_field_type_saved_value);
							// always return array, or just sometimes?
							// if a field has saved a value as a single value it will be returned as the value at position [0]
							$custom_field_type_saved_value = (array) $custom_field_type_saved_value;
							echo $custom_field_type->edit_output($custom_field_type_saved_value, $custom_field_type_options);

							echo "</div>";

						}
					
					} // field types
					
					// Output hidden field that can be shown with JS to see the name and slug of a field
					?>
					<div class="simple-fields-metabox-field-custom-field-key hidden highlight">
						<strong><?php _e('Meta key:', 'simple-fields') ?></strong>
						<?php echo $custom_field_key ?>
						<?php if (isset($field["slug"])) { ?>
							<br><strong><?php _e('Field slug:', 'simple-fields') ?></strong>
							<?php echo $field["slug"] ?>
						<?php } ?>
					</div>
				</div><!-- // end simple-fields-metabox-field -->
				<?php
			} // foreach
			
			?>
		</li>
		<?php
	} // end function print


	/**
	 * Head of admin area
	 * - Add meta box with info about currently selected connector + options to choose another one
	 * - Add meta boxes with field groups
	 */
	function admin_head() {

		// Only run code if on a SF page
		$current_screen = get_current_screen();
		$is_on_simple_fields_page = FALSE;
		if ($current_screen->base == "post" && in_array($current_screen->post_type, $this->get_post_connector_attached_types())) {
			$is_on_simple_fields_page = TRUE;
			$page_type = "post";
		}

		if (!$is_on_simple_fields_page) return;

		// Add meta box to post
		global $post, $sf;
	
		// Tell pluings etc that they can output stuff now
		do_action("simple_fields_admin_head", $this);

		if ($post) {
	
			$post_type = $post->post_type;
			$arr_post_types = $this->get_post_connector_attached_types();
			
			// check if the post type being edited is among the post types we want to add boxes for
			if (in_array($post_type, $arr_post_types)) {
				
				// general meta box to select fields for the post
				add_meta_box('simple-fields-post-edit-side-field-settings', 'Simple Fields', array($this, 'edit_post_side_field_settings'), $post_type, 'side', 'low');
				
				$connector_to_use = $this->get_selected_connector_for_post($post);
				
				// get connector to use for this post
				$post_connectors = $this->get_post_connectors();
				if (isset($post_connectors[$connector_to_use])) {
					
					$field_groups = $this->get_field_groups();
					$selected_post_connector = $post_connectors[$connector_to_use];
					
					// check if we should hide the editor, using css to keep things simple
					// echo "<pre>";print_r($selected_post_connector);echo "</pre>";
					$hide_editor = (bool) isset($selected_post_connector["hide_editor"]) && $selected_post_connector["hide_editor"];
					if ($hide_editor) {
						?><style type="text/css">#postdivrich, #postdiv { display: none; }</style><?php
					}
					
					// get the field groups for the selected connector
					$selected_post_connector_field_groups = $selected_post_connector["field_groups"];
	
					foreach ($selected_post_connector_field_groups as $one_post_connector_field_group) {
	
						// check that the connector is not deleted
						if ($one_post_connector_field_group["deleted"]) {
							continue;
						}
	
						// check that the field group for the connector we want to add also actually exists
						if (isset($field_groups[$one_post_connector_field_group["id"]])) {
													
							$field_group_to_add = $field_groups[$one_post_connector_field_group["id"]];
	
							$meta_box_id = "simple_fields_connector_" . $field_group_to_add["id"];
							$meta_box_title = $field_group_to_add["name"];
							$meta_box_context = $one_post_connector_field_group["context"];
							$meta_box_priority = $one_post_connector_field_group["priority"];
							// @todo: could we just create an anonymous function the "javascript way" instead? does that require a too new version of PHP?
							$meta_box_callback = create_function ("", "global \$sf; \$sf->meta_box_output({$one_post_connector_field_group["id"]}, $post->ID); ");
							
							add_meta_box( $meta_box_id, $meta_box_title, $meta_box_callback, $post_type, $meta_box_context, $meta_box_priority );
							
						}
						
					}
				}
				
			}
		}
		
	} // end function admin head


	/**
	 * print out fields for a meta box
	 */
	function meta_box_output($post_connector_field_id, $post_id) {
	 
	    // if not repeatable, just print it out
	    // if repeatable: only print out the ones that have a value
	    // and + add-button
	    
	    global $sf;
	 
	    $field_groups = $this->get_field_groups();
	    $current_field_group = $field_groups[$post_connector_field_id];

		// check for prev. saved fieldgroups
		// _simple_fields_fieldGroupID_1_fieldID_added_numInSet_0
		// try until returns empty
		$num_added_field_groups = 0;

		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$post_connector_field_id}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}
		
		$num_added_field_groups_css = "";
		if ($num_added_field_groups > 0) $num_added_field_groups_css = "simple-fields-meta-box-field-group-wrapper-has-fields-added";

		$field_group_slug_css = "";
		if (isset($current_field_group["slug"]) && !empty($current_field_group["slug"])) {
			$field_group_slug_css = "simple-fields-meta-box-field-group-wrapper-slug-" . $current_field_group["slug"];
		}
	 
	    echo "<div class='simple-fields-meta-box-field-group-wrapper $num_added_field_groups_css $field_group_slug_css'>";
	    echo "<input type='hidden' name='simple-fields-meta-box-field-group-id' value='$post_connector_field_id' />";
	 
	    // show description
	    if (!empty($current_field_group["description"])) {
	        printf("<p class='%s'>%s</p>", "simple-fields-meta-box-field-group-description", esc_html($current_field_group["description"]));
	    }
	    //echo "<pre>";print_r($current_field_group);echo "</pre>";
	 
	    if ($current_field_group["repeatable"]) {

			// Start of list with added field groups
	        $ul_add_css = "";

			// add link at top	 
	        echo "
				<div class='simple-fields-metabox-field-add'>
					<a href='#'>+ ".__('Add', 'simple-fields')."</a>
					<!-- 
					|
					<a href='#' id='sfToggleView{$current_field_group["id"]}'>Toggle view</a>
					-->
				</div>
	        ";

			/*
	        ?>
	        <script>
	        	jQuery(function($) {
	        		$("#sfToggleView<?php echo $current_field_group["id"] ?>").click(function(e) {
	        			e.preventDefault();
	        			$(this).closest(".simple-fields-meta-box-field-group-wrapper").find("ul:first").toggleClass("simple-fields-metabox-field-group-fields-view-table");
	        		});
	        	});
	        </script>
	        <?php
	        */

	        // add test class to test table layout
	        // $ul_add_css .= " simple-fields-metabox-field-group-fields-view-table";

	        // add class with number of fields in field group
	        $ul_add_css .= " simple-fields-metabox-field-group-fields-count-" . $current_field_group["fields_count"];
	        echo "<ul class='sf-cf simple-fields-metabox-field-group-fields simple-fields-metabox-field-group-fields-repeatable $ul_add_css'>";
	 
	        // now add them. ooooh my, this is fancy stuff.
	        $use_defaults = null;
	        for ($num_in_set=0; $num_in_set<$num_added_field_groups; $num_in_set++) {
	            $this->meta_box_output_one_field_group($post_connector_field_id, $num_in_set, $post_id, $use_defaults);  
	        }
	 
	 		// end list with added field groups
	        echo "</ul>";

			// add link at bottom
			echo "
	            <div class='simple-fields-metabox-field-add simple-fields-metabox-field-add-bottom'>
	                <a href='#'>+ ".__('Add', 'simple-fields')."</a>
	            </div>
	        ";

	 
	    } else {
	         
	        // is this a new post, ie. should default values be used
	        $been_saved = (bool) get_post_meta($post_id, "_simple_fields_been_saved", true);
	        if ($been_saved) { $use_defaults = false; } else { $use_defaults = true; }
	         
	        echo "<ul>";
	        $this->meta_box_output_one_field_group($post_connector_field_id, 0, $post_id, $use_defaults);
	        echo "</ul>";
	 
	    }
	     
	    echo "</div>";
	 
	} // end

	/**
	 * Returns all defined post connectors
	 * @return array
	 */
	function get_post_connectors() {

		// use wp_cache
		$connectors = wp_cache_get( 'simple_fields_'.$this->ns_key.'_post_connectors', 'simple_fields' );
		if (FALSE === $connectors) {

			$connectors = get_option("simple_fields_post_connectors");
	
			if ($connectors === FALSE) $connectors = array();
		
			// calculate number of active field groups
			// @todo: check this a bit more, does not seem to be any deleted groups. i thought i saved the deletes ones to, but with deleted flag set
			foreach (array_keys($connectors) as $i) {
	                    
	                    // Sanity check the connector id
	                    if (empty($connectors[$i]["id"]) && empty($connectors[$i]["deleted"])) {
	                        
	                        // Found field group without id, let's try to repair it
	                        $highest_id = 0;
	                        foreach($connectors as $one_connector) {
	                            if ($one_connector["id"] > $highest_id)
	                                $highest_id = $one_connector["id"];
	                            if ($one_connector["id"] === $i)
	                                $id_already_exists = true;
	                        }
	                        
	                        if ($i > 0 && !$id_already_exists) {
	                            // If the array key is larger than 0 and
	                            // not used as id by any other connector,
	                            // then it's the perfect id
	                            $connectors[$i]["id"] = $i;
	                        } else {
	                            // The array key is either less than or equal to 0,
	                            // or another connector is using it as id. In any case,
	                            // let's treat it as a new connector and give it a new id.
	                            $new_id = $highest_id + 1;
	                            $connectors[$i]["id"] = $new_id;
	                            
	                            // Now make sure the array key matches the new id
	                            $connectors[$new_id] = $connectors[$i];
	                            unset($connectors[$i]);
	                            $i = $new_id;
	                        }
	                        
	                    }
			
				// compatibility fix key vs slug
				if (isset($connectors[$i]["slug"]) && $connectors[$i]["slug"]) {
					$connectors[$i]["key"] = $connectors[$i]["slug"];
				} else if (isset($connectors[$i]["key"]) && $connectors[$i]["key"]) {
					$connectors[$i]["slug"] = $connectors[$i]["key"];
				}
			
				$num_fields_in_group = 0;
				if (isset($connectors[$i]["field_groups"]) && is_array($connectors[$i]["field_groups"])) {
					foreach ($connectors[$i]["field_groups"] as $one_group) {
						if (isset($one_group["deleted"]) && !$one_group["deleted"]) $num_fields_in_group++;
					}
				}
				$connectors[$connectors[$i]["id"]]["field_groups_count"] = $num_fields_in_group;
			}
			
			wp_cache_set( 'simple_fields_'.$this->ns_key.'_post_connectors', $connectors, 'simple_fields' );
			
		}
	
		return $connectors;
	}

	function get_post_type_defaults() {

		$post_type_defaults = wp_cache_get( 'simple_fields_'.$this->ns_key.'_post_type_defaults', 'simple_fields' );
		if (FALSE === $post_type_defaults) {
			$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
			wp_cache_set( 'simple_fields_'.$this->ns_key.'_post_type_defaults', $post_type_defaults, 'simple_fields' );
		}

		return $post_type_defaults;

	}
	
	/**
	 * Returns all defined field groups
	 *
	 * @return array
	 */
	function get_field_groups() {
		
		$field_groups = wp_cache_get( 'simple_fields_'.$this->ns_key.'_groups', 'simple_fields' );
		if (FALSE === $field_groups) {
			
			$field_groups = get_option("simple_fields_groups");
			if ($field_groups === FALSE) $field_groups = array();
			
			// Calculate the number of active fields
			// And some other things
			foreach (array_keys($field_groups) as $i) {
	                    
                // Sanity check the field group id
                if (empty($field_groups[$i]["id"]) && empty($field_groups[$i]["deleted"])) {
                    
                    // Found field group without id, let's try to repair it
                    $highest_id = 0;
                    foreach($field_groups as $one_field_group) {
                        if ($one_field_group["id"] > $highest_id)
                            $highest_id = $one_field_group["id"];
                        if ($one_field_group["id"] === $i)
                            $id_already_exists = true;
                    }
                    
                    if ($i > 0 && !$id_already_exists) {
                        // If the array key is larger than 0 and
                        // not used as id by any other field group,
                        // then it's the perfect id
                        $field_groups[$i]["id"] = $i;
                    } else {
                        // The array key is either less than or equal to 0,
                        // or another field group is using it as id. In any case,
                        // let's treat it as a new field group and give it a new id.
                        $new_id = $highest_id + 1;
                        $field_groups[$i]["id"] = $new_id;
                        
                        // Now make sure the array key matches the new id
                        $field_groups[$new_id] = $field_groups[$i];
                        unset($field_groups[$i]);
                        $i = $new_id;
                    }
                    
                }
	
				// Make sure we have both key and slug set to same. key = old name for slug
				if (isset($field_groups[$i]["slug"]) && $field_groups[$i]["slug"]) {
					$field_groups[$i]["key"] = $field_groups[$i]["slug"];
				} else if (isset($field_groups[$i]["key"]) && $field_groups[$i]["key"]) {
					$field_groups[$i]["slug"] = $field_groups[$i]["key"];
				}
	
				$num_active_fields = 0;
				foreach ($field_groups[$i]["fields"] as $one_field) {
					if (!$one_field["deleted"]) $num_active_fields++;
				}
				$field_groups[$i]["fields_count"] = $num_active_fields;
				
				// Also add some info about the field group is belongs to
				// This can be useful to have if you're only fetching a single field
				// but need to do something with that fields field group 
				// (like getting the id to calcualte that custom field meta key to use)
				foreach ($field_groups[$i]["fields"] as $one_field_id => $one_field) {

					if (!isset($field_groups[$i]["fields"][$one_field_id]["field_group"])) {

						$field_groups[$i]["fields"][$one_field_id]["field_group"] = array(
							"id"           => $field_groups[$i]["id"],
							"name"         => $field_groups[$i]["name"],
							"slug"         => $field_groups[$i]["slug"],
							"description"  => $field_groups[$i]["description"],
							"repeatable"   => $field_groups[$i]["repeatable"],
							"fields_count" => $field_groups[$i]["fields_count"]
						);
						
					}
					
				}
			}

			wp_cache_set( 'simple_fields_'.$this->ns_key.'_groups', $field_groups, 'simple_fields' );
			
		}

		return $field_groups;
		
	}

	/**
	 * Get a field group
	 * @param int $group_id
	 * @return array with field group or false if field group is not found
	 */
	function get_field_group($group_id) {
		$field_groups = $this->get_field_groups();
		$return = false;
		if (is_array($field_groups)) {
			foreach($field_groups as $field_group) {
				if (is_numeric($group_id)) {
					if ($field_group['id'] == $group_id) {
						$return = $field_group;
						break;
					}
				} else {
					if ($field_group['name'] == $group_id) {
						$return = $field_group;
						break;
					}
				}
			}
		}
		return $return;
	}


	/**
	 * Returns a field from a field group
	 * @param int $field_group
	 * @param int $field_id
	 * @return false on error
	 */
	function get_field_in_group($field_group, $field_id) {
		$return = false;
		if (is_array($field_group) && is_array($field_group['fields'])) {
			foreach($field_group['fields'] as $field) {
				if (is_numeric($field_id)) {
					if ($field['id'] == $field_id) {
						$return = $field;
						break;
					}
				} else {
					if ($field['name'] == $field_id) {
						$return = $field;
						break;
					}
				}
			}
		}
		return $return;
	}


	/**
	 * meta box in sidebar in post edit screen
	 * let user select post connector to use for current post
	 */
	function edit_post_side_field_settings() {
		
		global $post, $sf;
		
		$arr_connectors = $this->get_post_connectors_for_post_type($post->post_type);
		$connector_default = $this->get_default_connector_for_post_type($post->post_type);
		$connector_selected = $this->get_selected_connector_for_post($post);
	
		// $connector_selected returns the id of the connector to use, yes, but we want the "real" connector, not the id of the inherited or so
		// this will be empty if this is a new post and default connector is __inherit__
		// if this is empty then use connector_selected. this may happen in post is new and not saved
		$saved_connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
		if (empty($saved_connector_to_use)) {
			$saved_connector_to_use = $connector_default;
		}
		/*
		echo "<br>saved_connector_to_use: $saved_connector_to_use";
		echo "<br>connector_selected: $connector_selected";
		echo "<br>connector_default: $connector_default";
		on parent post we can use simple_fields_get_selected_connector_for_post($post) to get the right one?
		can't use that function on the current post, because it won't work if we don't acually have inherit
		confused? I AM!
		*/
		
		// get name of inherited post connector
		$parents = get_post_ancestors($post);
		$str_inherit_parent_connector_name = __('(no parent found)', 'simple-fields');
		if (empty($parents)) {
		} else {
			$post_parent = get_post($post->post_parent);
			$parent_selected_connector = $this->get_selected_connector_for_post($post_parent);
			$str_parent_connector_name = "";
			if ($parent_selected_connector)
			foreach ($arr_connectors as $one_connector) {
				if ($one_connector["id"] == $parent_selected_connector) {
					$str_parent_connector_name = $one_connector["name"];
					break;
				}
			}
			if ($str_parent_connector_name) {
				$str_inherit_parent_connector_name = "({$str_parent_connector_name})";
			}
		}
		
		?>
		<div class="inside">
			<div>
				<select name="simple_fields_selected_connector" id="simple-fields-post-edit-side-field-settings-select-connector">
					<option <?php echo ($saved_connector_to_use == "__none__") ? " selected='selected' " : "" ?> value="__none__"><?php _e('None', 'simple-fields') ?></option>
					<option <?php echo ($saved_connector_to_use == "__inherit__") ? " selected='selected' " : "" ?> value="__inherit__"><?php _e('Inherit from parent', 'simple-fields') ?>
						<?php
						echo $str_inherit_parent_connector_name;
						?>
					</option>
					<?php foreach ($arr_connectors as $one_connector) : ?>
						<?php if ($one_connector["deleted"]) { continue; } ?>
						<option <?php echo ($saved_connector_to_use == $one_connector["id"]) ? " selected='selected' " : "" ?> value="<?php echo $one_connector["id"] ?>"><?php echo $one_connector["name"] ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div id="simple-fields-post-edit-side-field-settings-select-connector-please-save" class="hidden">
				<p><?php _e('Save post to switch to selected fields.', 'simple-fields') ?></p>
			</div>
			<div>
				<p><a href="#" id="simple-fields-post-edit-side-field-settings-show-keys"><?php _e('Show custom field keys', 'simple-fields') ?></a></p>
			</div>
		</div>
		<?php
	} // function 


	/**
	 * get selected post connector for a post
	 * a post has a post connector, or no connector
	 * this function will return the inherited connector if post is set to inherit connector
	 * unless it's the top most post since then nere are no more to inherit
	 * should not return be __none__ then?
	 *
	 * @param object $post or int post id
	 * @return id or string __none__
	 */
	function get_selected_connector_for_post($post) {
		/*
		om sparad connector finns för denna artikel, använd den
		om inte sparad connector, använd default
		om sparad eller default = inherit, leta upp connector för parent post
		*/
		#d($post);
		
		global $sf;
		
		// make sure $post is a post object
		if (is_numeric($post)) $post = get_post($post);
		
		$post_type = $post->post_type;
		$connector_to_use = null;
		if (!$post->ID) {
			// no id (new post), use default for post type
			$connector_to_use = $this->get_default_connector_for_post_type($post_type);
		} elseif ($post->ID) {
			// get saved connector for post
			$connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
			#var_dump($connector_to_use);
			if ($connector_to_use == "") {
				// no previous post connector saved, use default for post type
				$connector_to_use = $this->get_default_connector_for_post_type($post_type);
			}
		}
		
		// $connector_to_use is now a id or __none__ or __inherit__
	
		// if __inherit__, get connector from post_parent
		if ("__inherit__" == $connector_to_use && $post->post_parent > 0) {
			$parent_post_id = $post->post_parent;
			$parent_post = get_post($parent_post_id);
			$connector_to_use = $this->get_selected_connector_for_post($parent_post);
		} elseif ("__inherit__" == $connector_to_use && 0 == $post->post_parent) {
			// already at the top, so inherit should mean... __none__..? right?
			// hm.. no.. then the wrong value is selected in the drop down.. hm...
			#$connector_to_use = "__none__";
		}
		
		// if selected connector is deleted, then return none
		$post_connectors = $this->get_post_connectors();
		if (isset($post_connectors[$connector_to_use]["deleted"]) && $post_connectors[$connector_to_use]["deleted"]) {
			$connector_to_use = "__none__";
		}
	
		return $connector_to_use;
	
	} // function get_selected_connector_for_post


	/**
	 * Code from Admin Menu Tree Page View
	 */
	function get_pages($args) {
	
		global $sf;
	
		$defaults = array(
	    	"post_type" => "page",
			"xparent" => "0",
			"xpost_parent" => "0",
			"numberposts" => "-1",
			"orderby" => "menu_order",
			"order" => "ASC",
			"post_status" => "any"
		);
		$args = wp_parse_args( $args, $defaults );
		$pages = get_posts($args);
	
		$output = "";
		$str_child_output = "";
		foreach ($pages as $one_page) {
			$edit_link = get_edit_post_link($one_page->ID);
			$title = get_the_title($one_page->ID);
			$title = esc_html($title);
					
			$class = "";
			if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["post"]) && $_GET["post"] == $one_page->ID) {
				$class = "current";
			}
	
			// add css if we have childs
			$args_childs = $args;
			$args_childs["parent"] = $one_page->ID;
			$args_childs["post_parent"] = $one_page->ID;
			$args_childs["child_of"] = $one_page->ID;
			$str_child_output = $this->get_pages($args_childs);
			
			$output .= "<li class='$class'>";
			$output .= "<a href='$edit_link' data-post-id='".$one_page->ID."'>";
			$output .= $title;
			$output .= "</a>";
	
			// add child articles
			$output .= $str_child_output;
			
			$output .= "</li>";
		}
		
		// if this is a child listing, add ul
		if (isset($args["child_of"]) && $args["child_of"] && $output != "") {
			$output = "<ul class='simple-fields-tree-page-tree_childs'>$output</ul>";
		}
		
		return $output;
	}


	/**
	 * File browser dialog:
	 * hide some things there to make it more clean and user friendly
	 */
	function admin_head_select_file() {
		// Only output this css when we are showing a file dialog for simple fields
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			?>
			<style type="text/css">
				.wp-post-thumbnail, tr.image_alt, tr.post_title, tr.align, tr.image-size,tr.post_excerpt, tr.url, tr.post_content {
					display: none; 
				}
			</style>
			<?php
		}
	}

	
	/**
	 * used from file selector popup
	 * send the selected file to simple fields
	 */
	function media_send_to_editor($html, $id) {
	
		parse_str($_POST["_wp_http_referer"], $arr_postinfo);
	
		// only act if file browser is initiated by simple fields
		if (isset($arr_postinfo["simple_fields_action"]) && $arr_postinfo["simple_fields_action"] == "select_file") {
	
			// add the selected file to input field with id simple_fields_file_field_unique_id
			$simple_fields_file_field_unique_id = $arr_postinfo["simple_fields_file_field_unique_id"];
			$file_id = (int) $id;
			
			$image_thumbnail = wp_get_attachment_image_src( $file_id, 'thumbnail', true );
			$image_thumbnail = $image_thumbnail[0];
			$image_html = "<img src='$image_thumbnail' alt='' />";
			$file_name = get_the_title($file_id);
			$post_file = get_post($file_id);
			$post_title = $post_file->post_title;
			$post_title = esc_html($post_title);
			$post_title = utf8_decode($post_title);
			$file_name = rawurlencode($post_title);
	
			?>
			<script>
				var win = window.dialogArguments || opener || parent || top;
				var file_id = <?php echo $file_id ?>;
				win.jQuery("#<?php echo $simple_fields_file_field_unique_id ?>").val(file_id);
				var sfmff = win.jQuery("#<?php echo $simple_fields_file_field_unique_id ?>").closest(".simple-fields-metabox-field-file");
				sfmff.find(".simple-fields-metabox-field-file-selected-image").html("<?php echo $image_html ?>").show();
				sfmff.closest(".simple-fields-metabox-field").find(".simple-fields-metabox-field-file-selected-image-name").html(unescape("<?php echo $file_name?>")).show();
				
				// show clear and edit-links
				//var url = ajaxurl.replace(/admin-ajax.php$/, "") + "media.php?attachment_id="+file_id+"&action=edit";
				var url = "<?php echo admin_url("media.php?attachment_id={$file_id}&action=edit") ?>";
	
				sfmff.find(".simple-fields-metabox-field-file-edit").attr("href", url).show();
				sfmff.find(".simple-fields-metabox-field-file-clear").show();
				
				// close popup
				win.tb_remove();
			</script>
			<?php
			exit;
		} else {
			return $html;
		}
	
	}
	

	/**
	 * if we have simple fields args in GET, make sure our simple fields-stuff are added to the form
	 */
	function media_upload_form_url($url) {
	
		foreach ($_GET as $key => $val) {
			if (strpos($key, "simple_fields_") === 0) {
				$url = add_query_arg($key, $val, $url);
			}
		}
		return $url;
	
	}


	/**
	 * remove gallery and remote url tab in file select
	 * also remove some
	 */
	function media_upload_tabs($arr_tabs) {
	
		if ( (isset($_GET["simple_fields_action"]) || isset($_GET["simple_fields_action"]) ) && ($_GET["simple_fields_action"] == "select_file" || $_GET["simple_fields_action"] == "select_file_for_tiny") ) {
			unset($arr_tabs["gallery"], $arr_tabs["type_url"]);
		}
	
		return $arr_tabs;
	}

	
	/**
	 * In file dialog:
	 * Change "insert into post" to something better
	 * 
	 * Code inspired by/gracefully stolen from
	 * http://mondaybynoon.com/2010/10/12/attachments-1-5/#comment-27524
	 */
	function post_admin_init() {
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			add_filter('gettext', array($this, 'hijack_thickbox_text'), 1, 3);
		}
	}
	
	function hijack_thickbox_text($translated_text, $source_text, $domain) {
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			if ('Insert into Post' == $source_text) {
				return __('Select', 'simple_fields' );
			}
		}
		return $translated_text;
	}


	/**
	 * Field type: post
	 * Fetch content for field type post dialog via AJAX
	 * Used for field type post
	 * Called from ajax with action wp_ajax_simple_fields_field_type_post_dialog_load
	 * Ajax defined in scripts.js -> $("a.simple-fields-metabox-field-post-select")
	 */
	function field_type_post_dialog_load() {
	
		global $sf;
	
		$arr_enabled_post_types = (array) $_POST["arr_enabled_post_types"];
		$additional_arguments = isset($_POST["additional_arguments"]) ? $_POST["additional_arguments"] : "";
		$existing_post_types = get_post_types(NULL, "objects");
		$selected_post_type = (string) @$_POST["selected_post_type"];
		?>
	
		<?php if (count($arr_enabled_post_types) > 1) { ?>
			<p>Show posts of type:</p>
			<ul class="simple-fields-meta-box-field-group-field-type-post-dialog-post-types">
				<?php
				$loopnum = 0;
				foreach ($existing_post_types as $key => $val) {
					if (!in_array($key, $arr_enabled_post_types)) {
						continue;
					}
					if (empty($selected_post_type) && $loopnum == 0) {
						$selected_post_type = $key;
					}
					$class = "";
					if ($selected_post_type == $key) {
						$class = "selected";
					}
					printf("\n<li class='%s'><a href='%s'>%s</a></li>", $class, "$key", $val->labels->name);
					$loopnum++;
				}
			?>
			</ul>
			<?php 
		} else {
			$selected_post_type = $arr_enabled_post_types[0];
			?>
			<p>Showing posts of type: <a href="<?php echo $selected_post_type; ?>"><?php echo $existing_post_types[$selected_post_type]->labels->name; ?></a></p>
			<?php 
		} ?>
		
		<div class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts-wrap">
			<ul class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts">
				<?php
	
				// get root items
				$args = array(
					"echo" => 0,
					"sort_order" => "ASC",
					"sort_column" => "menu_order",
					"post_type" => $selected_post_type,
					"post_status" => "publish"
				);
				
				$hierarchical = (bool) $existing_post_types[$selected_post_type]->hierarchical;
				if ($hierarchical) {
					$args["parent"] = 0;
					$args["post_parent"] = 0;
				}
				
				if (!empty($additional_arguments)) {
					$args = wp_parse_args( $additional_arguments, $args );
				}
			
				$output = $this->get_pages($args);
				echo $output;
				?>
			</ul>
		</div>
		<div class="submitbox">
			<div class="simple-fields-postdialog-link-cancel">
				<a href="#" class="submitdelete deletion">Cancel</a>
			</div>
		</div>
		<?php
			
		exit;
	}
	
	/**
	 * Returns the output for a new or existing field with all it's options
	 * Used in options screen / admin screen
	 */
	function field_group_add_field_template($fieldID, $field_group_in_edit = null) {

		$fields = $field_group_in_edit["fields"];
		// simple_fields::debug("field_grup_in_edit", $fields);
		$field_name = esc_html($fields[$fieldID]["name"]);
		$field_description = esc_html($fields[$fieldID]["description"]);
		$field_slug = esc_html(@$fields[$fieldID]["slug"]);
		$field_type = $fields[$fieldID]["type"];
		$field_deleted = (int) $fields[$fieldID]["deleted"];

		// If this is a new field then set default type to text so user does not save field with no field type set
		if ($field_type === NULL) $field_type = "text";
		
		$field_type_file_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_file_options"]["enable_extended_return_values"];

		$field_type_user_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_user_options"]["enable_extended_return_values"];

		$field_type_textarea_option_use_html_editor	= (int) 	@$fields[$fieldID]["type_textarea_options"]["use_html_editor"];
		$field_type_textarea_option_size_height		= (string) 	@$fields[$fieldID]["type_textarea_options"]["size_height"];
		
		$field_type_checkbox_option_checked_by_default = (int) @$fields[$fieldID]["type_checkbox_options"]["checked_by_default"];
		$field_type_checkbox_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_checkbox_options"]["enable_extended_return_values"];
		
		$field_type_radiobuttons_options = (array) @$fields[$fieldID]["type_radiobuttons_options"];
		$field_type_radiobuttons_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_radiobuttons_options"]["enable_extended_return_values"];
		
		$field_type_dropdown_options = (array) @$fields[$fieldID]["type_dropdown_options"];
		$field_type_dropdown_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_dropdown_options"]["enable_extended_return_values"];
		$field_type_dropdown_option_enable_multiple = (int) @$fields[$fieldID]["type_dropdown_options"]["enable_multiple"];
		
		$field_type_post_options = (array) @$fields[$fieldID]["type_post_options"];
		$field_type_post_options["enabled_post_types"] = (array) @$field_type_post_options["enabled_post_types"];
		$field_type_post_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_post_options"]["enable_extended_return_values"];

		$field_type_taxonomy_options = (array) @$fields[$fieldID]["type_taxonomy_options"];
		$field_type_taxonomy_options["enabled_taxonomies"] = (array) @$field_type_taxonomy_options["enabled_taxonomies"];
		$field_type_taxonomy_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_taxonomy_options"]["enable_extended_return_values"];
	
		$field_type_date_options = (array) @$fields[$fieldID]["type_date_options"];
		$field_type_date_option_use_time = @$field_type_date_options["use_time"];
		$field_type_date_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_date_options"]["enable_extended_return_values"];
	
		$field_type_taxonomyterm_options = (array) @$fields[$fieldID]["type_taxonomyterm_options"];
		$field_type_taxonomyterm_options["enabled_taxonomy"] = (string) @$field_type_taxonomyterm_options["enabled_taxonomy"];
		$field_type_taxonomyterm_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_date_options"]["enable_taxonomyterm_return_values"];
	
		// Options saved for this field
		// Options is an array with key = field_type and value = array with options key => saved value
		$field_options = (array) @$fields[$fieldID]["options"];

		// Generate output for registred field types
		$registred_field_types_output = "";
		$registred_field_types_output_options = "";
		foreach ($this->registered_field_types as $one_field_type) {

			// Output for field type selection dropdown
			$registred_field_types_output .= sprintf('<option %3$s value="%1$s">%2$s</option>', 
				$one_field_type->key, 
				$one_field_type->name, 
				($field_type == $one_field_type->key) ? " selected " : ""
			);

			$field_type_options = isset($field_options[$one_field_type->key]) && is_array($field_options[$one_field_type->key]) ? $field_options[$one_field_type->key] : array();
			/*
			$field_type_options looks like this:
			Array
			(
			    [myTextOption] => No value entered yet
			    [mapsTextarea] => Enter some cool text here please!
			    [funkyDropdown] => 
			)
			*/
			
			// Generate common and unique classes for this field types options row
			$div_class  = "simple-fields-field-group-one-field-row ";
			$div_class .= "simple-fields-field-type-options ";
			$div_class .= "simple-fields-field-type-options-" . $one_field_type->key . " ";
			$div_class .= ($field_type == $one_field_type->key) ? "" : " hidden ";
			
			// Generate and set the base for ids and names that the field will use for input-elements and similar
			$field_options_id 	= "field_{$fieldID}_options_" . $one_field_type->key . "";
			$field_options_name	= "field[$fieldID][options][" . $one_field_type->key . "]";
			$one_field_type->set_options_base_id($field_options_id);
			$one_field_type->set_options_base_name($field_options_name);
			
			// Gather together the options output for this field type
			// Only output fieldset if field has options
			$field_options_output = $one_field_type->options_output($field_type_options);
			if ($field_options_output) {
				$field_options_output = "
					<!-- <fieldset> 
						<legend>Options</legend> -->
						$field_options_output
					<!-- </fieldset> -->
				";
				
			}
			$registred_field_types_output_options .= sprintf(
				'
					<div class="%1$s">
						%2$s
					</div>
				', 
				$div_class, 
				$field_options_output
			);

		}
		
		$out = "";
		$out .= "
		<li class='simple-fields-field-group-one-field simple-fields-field-group-one-field-id-{$fieldID}'>
			<div class='simple-fields-field-group-one-field-handle'></div>
	
			<div class='simple-fields-field-group-one-field-row'>
				<label class='simple-fields-field-group-one-field-name-label'>".__('Name', 'simple-fields')."</label>
				<input type='text' class='regular-text simple-fields-field-group-one-field-name' name='field[{$fieldID}][name]' value='{$field_name}' />
			</div>
			
			<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-description'>
				<label>".__('Description', 'simple-fields')."</label>
				<input type='text' class='regular-text' name='field[{$fieldID}][description]' value='{$field_description}' />
			</div>
			
			<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-slug'>
				<label>".__('Slug', 'simple-fields')."</label>
				<input 
					type='text' class='regular-text' 
					name='field[{$fieldID}][slug]' 
					value='{$field_slug}' 
					pattern='".$this->get_slug_pattern()."'
					title='".$this->get_slug_title()."'
					required
					 /> 
				<br><span class='description'>" . __('A unique identifier used in your theme to get the saved values of this field.', 'simple-fields') . "</span>
			</div>
			
			<div class='simple-fields-field-group-one-field-row'>
				<label>".__('Type', 'simple-fields')."</label>
				<!-- <br> -->
				<select name='field[{$fieldID}][type]' class='simple-fields-field-type'>
					<option value=''>".__('Select', 'simple-fields')."...</option>
					<option value='text'" . (($field_type=="text") ? " selected='selected' " : "") . ">".__('Text', 'simple-fields')."</option>
					<option value='textarea'" . (($field_type=="textarea") ? " selected='selected' " : "") . ">".__('Textarea', 'simple-fields')."</option>
					<option value='checkbox'" . (($field_type=="checkbox") ? " selected='selected' " : "") . ">".__('Checkbox', 'simple-fields')."</option>
					<option value='radiobuttons'" . (($field_type=="radiobuttons") ? " selected='selected' " : "") . ">".__('Radio buttons', 'simple-fields')."</option>
					<option value='dropdown'" . (($field_type=="dropdown") ? " selected='selected' " : "") . ">".__('Dropdown', 'simple-fields')."</option>
					<option value='file'" . (($field_type=="file") ? " selected='selected' " : "") . ">".__('File', 'simple-fields')."</option>
					<option value='post'" . (($field_type=="post") ? " selected='selected' " : "") . ">".__('Post', 'simple-fields')."</option>
					<option value='taxonomy'" . (($field_type=="taxonomy") ? " selected='selected' " : "") . ">".__('Taxonomy', 'simple-fields')."</option>
					<option value='taxonomyterm'" . (($field_type=="taxonomyterm") ? " selected='selected' " : "") . ">".__('Taxonomy Term', 'simple-fields')."</option>
					<option value='color'" . (($field_type=="color") ? " selected='selected' " : "") . ">".__('Color', 'simple-fields')."</option>
					<option value='date'" . (($field_type=="date") ? " selected='selected' " : "") . ">".__('Date', 'simple-fields')."</option>
					<option value='user'" . (($field_type=="user") ? " selected='selected' " : "") . ">".__('User', 'simple-fields')."</option>
					$registred_field_types_output
				</select>
	
				<div class='simple-fields-field-group-one-field-row " . (($field_type=="text") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-text'>
				</div>
			</div>
	
			$registred_field_types_output_options

			<!-- options for field type  textarea -->
			<div class='simple-fields-field-group-one-field-row " . (($field_type=="textarea") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-textarea'>
				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<label>Height</label>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input " . ((empty($field_type_textarea_option_size_height) || $field_type_textarea_option_size_height == "default") ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='default'> " . _x('Default', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "small" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='small'> " . _x('Small', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "medium" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='medium'> " . _x('Medium', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "large" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='large'> " . _x('Large', 'Textarea default height', 'simple-fields') . " &nbsp;
					</div>
				</div>

				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input type='checkbox' name='field[{$fieldID}][type_textarea_options][use_html_editor]' " . (($field_type_textarea_option_use_html_editor) ? " checked='checked'" : "") . " value='1' /> ".__('Use HTML-editor', 'simple-fields')."
					</div>
				</div>

			</div>
			";

			// User
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="user") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-user'>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-first'></div>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= "		<p><input type='checkbox' name='field[{$fieldID}][type_user_options][enable_extended_return_values]' " . (($field_type_user_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
			$out .= 		__('Enable Extended Return Values', 'simple-fields') . "</p>";
			$out .= "		<p class='description'>" . __('Return an array with the name, email and full WP_User-object of the selected user, instead of just the user ID.', 'simple-fields') . "</p>";
			$out .= "	</div>";
			$out .= "</div>";

			// File
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="file") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-file'>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-first'></div>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= "		<p><input type='checkbox' name='field[{$fieldID}][type_file_options][enable_extended_return_values]' " . (($field_type_file_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
			$out .= 		__('Enable Extended Return Values', 'simple-fields') . "</p>";
			$out .= "		<p class='description'>" . __('Return an array with attachment title, path, etc., instead of just a post ID.', 'simple-fields') . "</p>";
			$out .= "	</div>";
			$out .= "</div>";

			// Date
			$out .= "<div class='" . (($field_type=="date") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-date'>";
				
				$out .= "<div class='simple-fields-field-group-one-field-row'>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
					$out .= "	<!-- <p><input type='checkbox' name='field[{$fieldID}][type_date_options][use_time]' " . (($field_type_date_option_use_time) ? " checked='checked'" : "") . " value='1' /> ".__('Also show time', 'simple-fields') . "</p> -->";
					$out .= "</div>";
				$out .= "</div>";
	
				$out .= "<div class='simple-fields-field-group-one-field-row'>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
					$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_date_options][enable_extended_return_values]' " . (($field_type_date_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
					$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
					$out .= "	<p class='description'>" . __('Return an array with the selected date as a unix timestamp and as the date format set in WordPress settings.', 'simple-fields') . "</p>";
					$out .= "</div>";
				$out .= "	</div>";

			$out .= "</div>";
		
	
			// connect post - select post types
			$out .= "<div class='" . (($field_type=="post") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-post'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Post types to select from', 'simple-fields'));
			$out .= "</div>";
			//$out .= sprintf("<select name='%s'>", "field[$fieldID][type_post_options][post_type]");
			//$out .= sprintf("<option %s value='%s'>%s</option>", (empty($field_type_post_options["post_type"]) ? " selected='selected' " : "") ,"", "Any");
	
			// list all post types in checkboxes
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$post_types = get_post_types(NULL, "objects");
			$loopnum = 0;
			foreach ($post_types as $one_post_type) {
				// skip some built in types
				if (in_array($one_post_type->name, array("attachment", "revision", "nav_menu_item"))) {
					continue;
				}
				$input_name = "field[{$fieldID}][type_post_options][enabled_post_types][]";
				$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s</input>", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name,
									((in_array($one_post_type->name, $field_type_post_options["enabled_post_types"])) ? " checked='checked' " : ""), 
									$one_post_type->name, 
									$one_post_type->labels->name . " ($one_post_type->name)"
								);
				$loopnum++;
			}
			$out .= "</div>";
			$out .= "</div>";
	
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= "<label>Additional arguments</label>";
			$out .= "</div>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= sprintf("<input class='regular-text' type='text' name='%s' value='%s' />", "field[$fieldID][type_post_options][additional_arguments]", @$field_type_post_options["additional_arguments"]);
			$out .= sprintf("<br><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Class_Reference/WP_Query'>WP_Query</a>.</span>");
			$out .= "</div>"; // second
			$out .= "</div>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_post_options][enable_extended_return_values]' " . (($field_type_post_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the title, permalink, and complete post object of the selected post, instead of just the ID.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "</div>"; // whole divs that shows/hides
	
	
			// connect taxonomy - select taxonomies
			$out .= "<div class='" . (($field_type=="taxonomy") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomy'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Taxonomies to show in dropdown', 'simple-fields'));
			$out .= "</div>"; // col first
			
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$taxonomies = get_taxonomies(NULL, "objects");
			$loopnum = 0;
			foreach ($taxonomies as $one_tax) {
				// skip some built in types
				if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
				    continue;
				}
				$input_name = "field[{$fieldID}][type_taxonomy_options][enabled_taxonomies][]";
				$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name, 
									((in_array($one_tax->name, $field_type_taxonomy_options["enabled_taxonomies"])) ? " checked='checked' " : ""), 
									$one_tax->name, 
									$one_tax->labels->name . " ($one_tax->name)"
								);
				$loopnum++;
			}
			$out .= "</div>"; // second
			$out .= "</div>"; // row

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_taxonomy_options][enable_extended_return_values]' " . (($field_type_taxonomy_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with name and complete taxonomy object of the selected taxonomy, instead of just the ID.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "</div>";
			
	
			// taxonomyterm - select taxonomies, like above
			$out .= "<div class='" . (($field_type=="taxonomyterm") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomyterm'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Taxonomy to select terms from', 'simple-fields'));
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$taxonomies = get_taxonomies(NULL, "objects");
			$loopnum = 0;
			foreach ($taxonomies as $one_tax) {
				// skip some built in types
				if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
				    continue;
				}
				$input_name = "field[{$fieldID}][type_taxonomyterm_options][enabled_taxonomy]";
				$out .= sprintf("%s<input name='%s' type='radio' %s value='%s'> %s", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name, 
									($one_tax->name == $field_type_taxonomyterm_options["enabled_taxonomy"]) ? " checked='checked' " : "", 
									$one_tax->name, 
									$one_tax->labels->name . " ($one_tax->name)"
								);
				$loopnum++;
			}
			$out .= "</div>"; // second
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= "<label>".__("Additional arguments", "simple-fields")."</label>";
			$out .= "</div>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= sprintf("<input class='regular-text' type='text' name='%s' value='%s' />", "field[$fieldID][type_taxonomyterm_options][additional_arguments]", @$field_type_taxonomyterm_options["additional_arguments"]);
			$out .= sprintf("<br><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Function_Reference/get_terms#Parameters'>get_terms()</a>.</span>");
			$out .= "</div>"; // second
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_taxonomyterm_options][enable_extended_return_values]' " . (($field_type_taxonomyterm_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return a multi dimensional array with all the taxonomy terms objects, instead of just the IDs of the terms.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";
			
			$out .= "</div>";
	
			// radiobuttons
			$radio_buttons_added = "";
			$radio_buttons_highest_id = 0;
			if ($field_type_radiobuttons_options) {
				foreach ($field_type_radiobuttons_options as $key => $val) {
					if (strpos($key, "radiobutton_num_") !== false && $val["deleted"] != true) {
						// found one button in format radiobutton_num_0
						$radiobutton_num = str_replace("radiobutton_num_", "", $key);
						if ($radiobutton_num > $radio_buttons_highest_id) {
							$radio_buttons_highest_id = $radiobutton_num;
						}
						$radiobutton_val = esc_html($val["value"]);
						$checked = ($key == @$field_type_radiobuttons_options["checked_by_default_num"]) ? " checked='checked' " : "";
						$radio_buttons_added .= "
							<li>
								<div class='simple-fields-field-type-options-radiobutton-handle'></div>
								<input class='regular-text' value='$radiobutton_val' name='field[$fieldID][type_radiobuttons_options][radiobutton_num_{$radiobutton_num}][value]' type='text' />
								<input class='simple-fields-field-type-options-radiobutton-checked-by-default-values' type='radio' name='field[$fieldID][type_radiobuttons_options][checked_by_default_num]' value='radiobutton_num_{$radiobutton_num}' {$checked} />
								<input class='simple-fields-field-type-options-radiobutton-deleted' name='field[$fieldID][type_radiobuttons_options][radiobutton_num_{$radiobutton_num}][deleted]' type='hidden' value='0' />
								<a href='#' class='simple-fields-field-type-options-radiobutton-delete'>Delete</a>
							</li>";
					}
				}
			}
			$radio_buttons_highest_id++;
			$out .= "<div class='" . (($field_type=="radiobuttons") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-radiobuttons'>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_radiobuttons_options][enable_extended_return_values]' " . (($field_type_radiobuttons_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the value of the selected radiobutton + the values of the non-selected radiobuttons.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "
				<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-radiobuttons-values'>

					<div class='simple-fields-field-group-one-field-row-col-first'>
						<div>" . __("Values", "simple-fields") . "</div>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<div class='simple-fields-field-type-options-radiobutton-checked-by-default'>".__('Default', 'simple-fields')."</div>
						<ul class='simple-fields-field-type-options-radiobutton-values-added'>
							$radio_buttons_added
						</ul>
						<div><a class='simple-fields-field-type-options-radiobutton-values-add' href='#'>+ ".__('Add radio button', 'simple-fields')."</a></div>
						<input type='hidden' name='' class='simple-fields-field-group-one-field-radiobuttons-highest-id' value='{$radio_buttons_highest_id}' />
					</div><!-- // second -->
				
				</div>
			</div><!-- show/hide div -->
			";
			// end radiobuttons
	
			// checkbox
			$out .= "
			<div class='simple-fields-field-group-one-field-row " . (($field_type=="checkbox") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-checkbox'>
				<input type='checkbox' name='field[{$fieldID}][type_checkbox_options][checked_by_default]' " . (($field_type_checkbox_option_checked_by_default) ? " checked='checked'" : "") . " value='1' /> ".__('Checked by default', 'simple-fields')."
			</div>
			";
			// end checkbox
	
			// start dropdown
			$dropdown_values_added = "";
			$dropdown_values_highest_id = 0;
			if ($field_type_dropdown_options) {
				foreach ($field_type_dropdown_options as $key => $val) {
					if (strpos($key, "dropdown_num_") !== false && $val["deleted"] != true) {
						// found one button in format radiobutton_num_0
						$dropdown_num = str_replace("dropdown_num_", "", $key);
						if ($dropdown_num > $dropdown_values_highest_id) {
							$dropdown_values_highest_id = $dropdown_num;
						}
						$dropdown_val = esc_html($val["value"]);
						$dropdown_values_added .= "
							<li>
								<div class='simple-fields-field-type-options-dropdown-handle'></div>
								<input class='regular-text' value='$dropdown_val' name='field[$fieldID][type_dropdown_options][dropdown_num_{$dropdown_num}][value]' type='text' />
								<input class='simple-fields-field-type-options-dropdown-deleted' name='field[$fieldID][type_dropdown_options][dropdown_num_{$dropdown_num}][deleted]' type='hidden' value='0' />
								<a href='#' class='simple-fields-field-type-options-dropdown-delete'>".__('Delete', 'simple-fields')."</a>
							</li>";
					}
				}
			}
			$dropdown_values_highest_id++;
			$out .= "<div class='" . (($field_type=="dropdown") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-dropdown'>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";

				// Enable extended
				$out .= "	<p>";
				$out .= "		<input type='checkbox' name='field[{$fieldID}][type_dropdown_options][enable_extended_return_values]' " . (($field_type_dropdown_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the value of the selected item in the dropdown + the values of the non-selected items.', 'simple-fields') . "</p>";

				$out .= "</div>";
			$out .= "	</div>";

			// Enable multiple
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "<input " . ($field_type_dropdown_option_enable_multiple === 0 ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_dropdown_options][enable_multiple]' value='0'> ";
				$out .= _x('Single', 'Field type dropdown', 'simple-fields') . " &nbsp;";

				$out .= "<input " . ($field_type_dropdown_option_enable_multiple === 1 ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_dropdown_options][enable_multiple]' value='1'> ";
				$out .= _x('Multiple', 'Field type dropdown', 'simple-fields') . " &nbsp;";

				$out .= "</div>";
			$out .= "	</div>";

			$out .= "
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<p>".__('Values', 'simple-fields')."</p>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<ul class='simple-fields-field-type-options-dropdown-values-added'>
							$dropdown_values_added
						</ul>
						<div><a class='simple-fields-field-type-options-dropdown-values-add' href='#'>+ ".__('Add dropdown value', 'simple-fields')."</a></div>
						<input type='hidden' name='' class='simple-fields-field-group-one-field-dropdown-highest-id' value='{$dropdown_values_highest_id}' />
					</div>
				</div>
			";
			// end dropdown
	
	
			$out .= "
			<div class='delete'>
				<a href='#'>".__('Delete field', 'simple-fields')."</a>
			</div>
			<input type='hidden' name='field[{$fieldID}][id]' class='simple-fields-field-group-one-field-id' value='{$fieldID}' />
			<input type='hidden' name='field[{$fieldID}][deleted]' value='{$field_deleted}' class='hidden_deleted' />
	
		</li>";
		return $out;
	
	} // /simple_fields_field_group_add_field_template

	/**
	 * Called from AJAX call to add a field group to the post in edit
	 */
	function field_group_add_field() {
		global $sf;
		$simple_fields_highest_field_id = (int) $_POST["simple_fields_highest_field_id"];
		echo $this->field_group_add_field_template($simple_fields_highest_field_id);
		exit;
	}


	/**
	 * Output all stuff for the options page
	 * Should be modularized a bit, it's way to long/big right now
	 */
	function options_page() {
	
		global $sf;
	
		$field_groups = $this->get_field_groups();
		$post_connectors = $this->get_post_connectors();

		// for debug purposes, here we can reset the option
		#$field_groups = array(); update_option("simple_fields_groups", $field_groups);
		#$post_connectors = array(); update_option("simple_fields_post_connectors", $post_connectors);
	
		// sort them by name
		function simple_fields_uasort($a, $b) {
			if ($a["name"] == $b["name"]) { return 0; }
			return strcasecmp($a["name"], $b["name"]);
		}
		
		uasort($field_groups, "simple_fields_uasort");
		uasort($post_connectors, "simple_fields_uasort");
			
		?>
		<div class="wrap">
	
			<h2><?php echo SIMPLE_FIELDS_NAME ?></h2>
	
			<div class="clear"></div>
			
			<!-- 
			<div class="simple-fields-bonny-plugins-inner-sidebar">
				<h3>Keep this plugin alive</h3>
				<p>
					I develop this plugin mostly on my spare time. Please consider <a href="http://eskapism.se/sida/donate/">donating</a>
					or <a href="https://flattr.com/thing/116510/Simple-Fields">Flattr</a>
					to keep the development going.
				</p>
	
				<h3>Support</h3>
				<p>If you have any problems with this plugins please check out the <a href="http://wordpress.org/tags/simple-fields?forum_id=10">support forum</a>.</p>
				<p>You can <a href="https://github.com/bonny/WordPress-Simple-Fields">follow the development of this plugin at GitHub</a>.</p>
										
			</div>
			-->
	
		<div class="simple-fields-settings-wrap">
	
			<?php
			
			$action = (isset($_GET["action"])) ? $_GET["action"] : null;

			/**
			 * save post type defaults
			 */
			if ("edit-post-type-defaults-save" == $action) {
	
				$post_type = $_POST["simple_fields_save-post_type"];
				$post_type_connector = $_POST["simple_fields_save-post_type_connector"];
							
				simple_fields_register_post_type_default($post_type_connector, $post_type);
				
				$simple_fields_did_save_post_type_defaults = true;
				$action = "";
	
			}
	
			/**
			 * edit post type defaults
			 */
			if ("edit-post-type-defaults" == $action) {
				$post_type = $_GET["post-type"];
				global $wp_post_types;
				if (isset($wp_post_types[$post_type])) {
					$selected_post_type = $wp_post_types[$post_type];
					?>
					<h3><?php echo __( sprintf('Edit default post connector for post type %1$s', $selected_post_type->label), "simple-fields" ) ?></h3>
					
					<form action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-type-defaults-save" method="post">
						<table class="form-table">
							<tr>
								<th><?php _e('Default post connector', 'simple-fields') ?></th>
								<td>
									<?php
									$arr_post_connectors = $this->get_post_connectors_for_post_type($post_type);
									if ($arr_post_connectors) {
										$selected_post_type_default = $this->get_default_connector_for_post_type($post_type);
										?>
										<select name="simple_fields_save-post_type_connector">
											<option <?php echo ($selected_post_type_default==="__none__") ? " selected='selected' " : "" ?> value="__none__"><?php _e('No post connector', 'simple-fields') ?></option>
											<option <?php echo ($selected_post_type_default==="__inherit__") ? " selected='selected' " : "" ?> value="__inherit__"><?php _e('Inherit from parent post', 'simple-fields') ?></option>
											<?php
											foreach ($arr_post_connectors as $one_post_connector) {
												echo "<option " . ((intval($selected_post_type_default)==intval($one_post_connector["id"])) ? " selected='selected' " : "") . "value='{$one_post_connector["id"]}'>" . $one_post_connector["name"] . "</option>";
											}
											?>
										</select>
										<?php
									} else {
										?><p><?php _e('There are no post connectors for this post type.', 'simple-fields') ?></p><?php
									}
									?>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input class="button-primary" type="submit" value="<?php _e("Save changes", "simple-fields") ?>" />
							<input type="hidden" name="simple_fields_save-post_type" value="<?php echo $post_type ?>" />
							<?php _e('or', 'simple_fields');  ?>
							<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
						</p>
					</form>
					<?php
					
				}
			}
	
			/**
			 * Delete a field group
			 */
			if ("delete-field-group" == $action) {
				$field_group_id = (int) $_GET["group-id"];
				$field_groups[$field_group_id]["deleted"] = true;
				update_option("simple_fields_groups", $field_groups);
				$this->clear_caches();
				$simple_fields_did_delete = true;
				$action = "";
			}
	
	
			/**
			 * Delete a post connector
			 */
			if ("delete-post-connector" == $action) {
				$post_connector_id = (int) $_GET["connector-id"];
				$post_connectors[$post_connector_id]["deleted"] = 1;
				update_option("simple_fields_post_connectors", $post_connectors);
				$this->clear_caches();
				$simple_fields_did_delete_post_connector = true;
				$action = "";
			}
			
			
			/**
			 * save a field group
			 * including fields
			 */
			if ("edit-field-group-save" == $action) {
			
				if ($_POST) {
					#sf_d($_POST);
					$field_group_id                               = (int) $_POST["field_group_id"];
					$field_groups[$field_group_id]["name"]        = stripslashes($_POST["field_group_name"]);
					$field_groups[$field_group_id]["description"] = stripslashes($_POST["field_group_description"]);
					$field_groups[$field_group_id]["slug"]        = stripslashes($_POST["field_group_slug"]);
					$field_groups[$field_group_id]["repeatable"]  = (bool) (isset($_POST["field_group_repeatable"]));					
					$field_groups[$field_group_id]["fields"]      = (array) stripslashes_deep($_POST["field"]);

					// Since 0.6 we really want all things to have slugs, so add one if it's not set
					if (empty($field_groups[$field_group_id]["slug"])) {
						$field_groups[$field_group_id]["slug"] = "field_group_" . $field_group_id;
					}
					
					/*
					if just one empty array like this, unset first elm
					happens if no fields have been added (now why would you do such an evil thing?!)
					*/
					if (sizeof($field_groups[$field_group_id]["fields"]) == 1 && empty($field_groups[$field_group_id]["fields"][0])) {
						unset($field_groups[$field_group_id]["fields"][0]);
					}
					
					update_option("simple_fields_groups", $field_groups);
					$this->clear_caches();

					// we can have changed the options of a field group, so update connectors using this field group
					$post_connectors = (array) $this->get_post_connectors();
					foreach ($post_connectors as $connector_id => $connector_options) {
						if (isset($connector_options["field_groups"][$field_group_id])) {
							// field group existed, update name
							$post_connectors[$connector_id]["field_groups"][$field_group_id]["name"] = stripslashes($_POST["field_group_name"]);
						}
					}
					update_option("simple_fields_post_connectors", $post_connectors);
					$this->clear_caches();
					
					$simple_fields_did_save = true;
				}
				$action = "";
						
			}
	
			/**
			 * save a post connector
			 */
			if ("edit-post-connector-save" == $action) {
				if ($_POST) {
										
					$connector_id = (int) $_POST["post_connector_id"];
					$post_connectors[$connector_id]["name"] = (string) stripslashes($_POST["post_connector_name"]);
					$post_connectors[$connector_id]["slug"] = (string) ($_POST["post_connector_slug"]);
					$post_connectors[$connector_id]["field_groups"] = (array) @$_POST["added_fields"];
					$post_connectors[$connector_id]["post_types"] = (array) @$_POST["post_types"];
					$post_connectors[$connector_id]["hide_editor"] = (bool) @$_POST["hide_editor"];
	
					// a post type can only have one default connector, so make sure only the connector
					// that we are saving now has it; remove it from all others;
					/*
					$post_types_type_default = (array) $_POST["post_types_type_default"];
					foreach ($post_types_type_default as $one_default_post_type) {
						foreach ($post_connectors as $one_post_connector) {
							if (in_array($one_default_post_type, $one_post_connector["post_types_type_default"])) {
								$array_key = array_search($one_default_post_type, $one_post_connector["post_types_type_default"]);
								if ($array_key !== false) {
									unset($post_connectors[$one_post_connector["id"]]["post_types_type_default"][$array_key]);
								}
							}
						}
					}
					$post_connectors[$connector_id]["post_types_type_default"] = $post_types_type_default;
					*/
					
					// for some reason I got an empty connector (array key was empty) so check for these and remove
					$post_connectors_tmp = array();
					foreach ($post_connectors as $key => $one_connector) {
						if (!empty($one_connector)) {
							$post_connectors_tmp[$key] = $one_connector;
						}
					}
					$post_connectors = $post_connectors_tmp;
	
					update_option("simple_fields_post_connectors", $post_connectors);
					$this->clear_caches();
	
					$simple_fields_did_save_connector = true;
				}
				$action = "";
			}
	
			
			/**
			 * edit new or existing post connector
			 * If new then connector-id = 0
			 */
			if ("edit-post-connector" == $action) {
	
				$connector_id = (isset($_GET["connector-id"])) ? intval($_GET["connector-id"]) : false;
				$highest_connector_id = 0;
	
				// if new, save it as unnamed, and then set to edit that
				if ($connector_id === 0) {
	
					// is new connector
					$post_connector_in_edit = simple_fields_register_post_connector();
	
				} else {
	
					// existing post connector
					
					// set a default value for hide_editor if it does not exist. did not exist until 0.5
					$post_connectors[$connector_id]["hide_editor"] = (bool) @$post_connectors[$connector_id]["hide_editor"];
					
					$post_connector_in_edit = $post_connectors[$connector_id];
				}
	
				?>
				<h3><?php _e('Post Connector details', 'simple-fields') ?></h3>
	
				<form method="post" action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-connector-save">
	
					<table class="form-table">

						<tr>
							<th><label><?php _e('Name', 'simple-fields') ?></label></th>
							<td><input type="text" id="post_connector_name" name="post_connector_name" class="regular-text" value="<?php echo esc_html($post_connector_in_edit["name"]) ?>" /></td>
						</tr>

						<tr>
							<th>
								<label for="post_connector_slug"><?php _e('Slug', 'simple-fields') ?></label>
							</th>
							<td>
								<input 	type="text" name="post_connector_slug" id="post_connector_slug" class="regular-text" 
										value="<?php echo esc_html(@$post_connector_in_edit["slug"]) ?>"
										pattern='<?php echo $this->get_slug_pattern() ?>'
										title='<?php echo $this->get_slug_title() ?>'
										required
										 />
								 <br>
								 <span class="description"><?php echo __("A unique identifier for this connector", 'simple-fields') ?></span>
								 <?php
								 // sf_d($post_connector_in_edit);
								 ?>
							</td>
						</tr>

						<tr>
							<th><?php _e('Field Groups', 'simple-fields') ?></th>
							<td>
								<p>
									<select id="simple-fields-post-connector-add-fields">
										<option value=""><?php _e('Add field group...', 'simple-fields') ?></option>
										<?php
										foreach ($field_groups as $one_field_group) {
											if ($one_field_group["deleted"]) { continue; }
											?><option value='<?php echo $one_field_group["id"] ?>'><?php echo esc_html($one_field_group["name"]) ?></option><?php
										}
										?>
									</select>
								</p>
								<ul id="simple-fields-post-connector-added-fields">
									<?php
									foreach ($post_connector_in_edit["field_groups"] as $one_post_connector_added_field) {
										if ($one_post_connector_added_field["deleted"]) { continue; }
										
										#d($one_post_connector_added_field);
										
										?>
										<li>
											<div class='simple-fields-post-connector-addded-fields-handle'></div>
											<div class='simple-fields-post-connector-addded-fields-field-name'><?php echo $one_post_connector_added_field["name"] ?></div>
											<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][id]' value='<?php echo $one_post_connector_added_field["id"] ?>' />
											<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][name]' value='<?php echo $one_post_connector_added_field["name"] ?>' />
											<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][deleted]' value='0' class="simple-fields-post-connector-added-field-deleted" />
											<div class="simple-fields-post-connector-addded-fields-options">
												<?php _e('Context', 'simple-fields') ?>
												<select name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][context]' class="simple-fields-post-connector-addded-fields-option-context">
													<option <?php echo ("normal" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="normal"><?php _e('normal') ?></option>
													<option <?php echo ("advanced" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="advanced"><?php _e('advanced') ?></option>
													<option <?php echo ("side" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="side"><?php _e('side') ?></option>
												</select>
												
												<?php _e('Priority', 'simple-fields') ?>
												<select name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][priority]' class="simple-fields-post-connector-addded-fields-option-priority">
													<option <?php echo ("low" == $one_post_connector_added_field["priority"]) ? " selected='selected' " : "" ?> value="low"><?php _e('low') ?></option>
													<option <?php echo ("high" == $one_post_connector_added_field["priority"]) ? " selected='selected' " : "" ?> value="high"><?php _e('high') ?></option>
												</select>
											</div>
											<a href='#' class='simple-fields-post-connector-addded-fields-delete'><?php _e('Delete', 'simple-fields') ?></a>
										</li>
										<?php
									}
									?>
								</ul>
							</td>
						</tr>
						
						<tr>
							<th><?php _e('Options', 'simple-fields') ?></th>
							<td><input
								 type="checkbox" 
								 <?php echo $post_connector_in_edit["hide_editor"] == TRUE ? " checked='checked' " : "" ?>
								 name="hide_editor" 
								 class="" 
								 value="1" />
								 <?php _e('Hide the built in editor', 'simple-fields') ?>
							</td>
						</tr>
						
						<tr>
							<th>
								<?php _e('Available for post types', 'simple-fields') ?>
							</th>
							<td>
								<table>
									<?php
									global $wp_post_types;
									$arr_post_types_to_ignore = array("attachment", "revision", "nav_menu_item");
									foreach ($wp_post_types as $one_post_type) {
										if (!in_array($one_post_type->name, $arr_post_types_to_ignore)) {
											?>
											<tr>
												<td>
													<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types"]) ? " checked='checked' " : ""); ?> type="checkbox" name="post_types[]" value="<?php echo $one_post_type->name ?>" />
													<?php echo $one_post_type->name ?>
												</td>
												<?php
												/*
												<!-- <td>
													<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types_type_default"]) ? " checked='checked' " : "") ?> type="checkbox" name="post_types_type_default[]" value="<?php echo $one_post_type->name ?>" />
													Default connector for post type <?php echo $one_post_type->name ?>
												</td> -->
												*/
											?>
											</tr>
											<?php
										}
									}
									?>
								</table>
							</td>
						</tr>
	
					</table>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e('Save Changes', 'simple-fields') ?>" />
						<input type="hidden" name="action" value="update" />
						<!-- <input type="hidden" name="page_options" value="field_group_name" /> -->
						<input type="hidden" name="post_connector_id" value="<?php echo $post_connector_in_edit["id"] ?>" />
						or 
						<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
					</p>
					<p class="simple-fields-post-connector-delete">
						<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=delete-post-connector&amp;connector-id=<?php echo $post_connector_in_edit["id"] ?>"><?php _e('Delete') ?></a>
					</p>
	
				</form>
				<?php
			}
	
		
			/**
			 * Edit new or existing Field Group
			 */
			if ("edit-field-group" == $action) {
				
				$field_group_id = (isset($_GET["group-id"])) ? intval($_GET["group-id"]) : false;
				
				$highest_field_id = 0;

				// check if field group is new or existing
				if ($field_group_id === 0) {

					// new: save it as unnamed, and then set to edit that
					$field_group_in_edit = simple_fields_register_field_group();

					simple_fields::debug("Added new field group", $field_group_in_edit);
	
				} else {

					// existing: get highest field id
					foreach ($field_groups[$field_group_id]["fields"] as $one_field) {
						if ($one_field["id"] > $highest_field_id) {
							$highest_field_id = $one_field["id"];
						}
					}
	
					$field_group_in_edit = $field_groups[$field_group_id];

				}

				?>
				<form method="post" action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-field-group-save">
		            <h3><?php _e('Field group details', 'simple-fields') ?></h3>
		            <table class="form-table">
		            	<tr>
		            		<th>
		            			<label for="field_group_name"><?php _e('Name', 'simple-fields') ?></label>
		            		</th>
		            		<td>
		            			<input type="text" name="field_group_name" id="field_group_name" class="regular-text" value="<?php echo esc_html($field_group_in_edit["name"]) ?>" required />
							</td>
						</tr>
						<tr>
							<th>
								<label for="field_group_description"><?php _e('Description', 'simple-fields') ?></label>
							</th>
							<td>
								<input 	type="text" name="field_group_description" id="field_group_description" class="regular-text" 
										value="<?php echo esc_html(@$field_group_in_edit["description"]) ?>"
										 />
							</td>
						</th>

						<tr>
							<th>
								<label for="field_group_slug"><?php _e('Slug', 'simple-fields') ?></label>
							</th>
							<td>
								<input 	type="text" name="field_group_slug" id="field_group_slug" class="regular-text" 
										value="<?php echo esc_html(@$field_group_in_edit["slug"]) ?>"
										pattern='<?php echo $this->get_slug_pattern() ?>'
										title='<?php echo $this->get_slug_title() ?>'
										required
										title="<?php _e("Allowed chars: a-z and underscore.", 'simple-fields') ?>"
										 />
								 <br>
								 <span class="description"><?php echo __("A unique identifier for this field group.", 'simple-fields') ?></span>
							</td>
						</tr>

						<tr>
							<th>
								<?php echo __("Options", 'simple-fields') ?>
							</th>
							<td>
		            			<label for="field_group_repeatable">
									<input type="checkbox" <?php echo ($field_group_in_edit["repeatable"] == true) ? "checked='checked'" : ""; ?> value="1" id="field_group_repeatable" name="field_group_repeatable" />
									<?php _e('Repeatable', 'simple-fields') ?>
								</label>								
		            		</td>
		            	</tr>
		            	<tr>
		            		<th><?php _e('Fields', 'simple-fields') ?></th>
		            		<td>
		            			<div id="simple-fields-field-group-existing-fields">
		            				<ul class='simple-fields-edit-field-groups-added-fields'>
										<?php
										foreach ($field_group_in_edit["fields"] as $oneField) {
											if (!$oneField["deleted"]) {
												echo $this->field_group_add_field_template($oneField["id"], $field_group_in_edit);
											}
										}
										?>
		            				</ul>
		            			</div>
		            			<p><a href="#" id="simple-fields-field-group-add-field">+ <?php _e('Add field', 'simple-fields') ?></a></p>
		            		</td>
		            	</tr>			
					</table>
	
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e('Save Changes', 'simple-fields') ?>" />
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="page_options" value="field_group_name" />
						<input type="hidden" name="field_group_id" value="<?php echo $field_group_in_edit["id"] ?>" />
						<?php _e('or', 'simple-fields') ?> 
						<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
					</p>
					<p class="simple-fields-field-group-delete">
						<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=delete-field-group&amp;group-id=<?php echo $field_group_in_edit["id"] ?>"><?php _e('Delete', 'simple-fields') ?></a>
					</p>
					
				</form>
		
				<script type="text/javascript">
					var simple_fields_highest_field_id = <?php echo (int) $highest_field_id ?>;
				</script>
		
				<?php
			
			}
	
			// view debug information
			if ("simple-fields-view-debug-info" == $action) {
	
				echo "<h3>Post Connectors</h3>\n";
				echo "<p>Called with function <code>get_post_connectors()</code>";
				sf_d( $this->get_post_connectors() );
	
				echo "<hr>";
				
				echo "<h3>Field Groups</h3>\n";
				echo "<p>Called with function <code>get_field_groups()</code>";
				sf_d( $this->get_field_groups() );
				
				echo "<hr>";
				echo "<h3>simple_fields_post_type_defaults</h3>";
				echo '<p>Called with: get_option("simple_fields_post_type_defaults")';
				sf_d( $this->get_post_type_defaults() );
				
			}
	
			// Save options
			if ("edit-options-save" == $action) {
				
				$this->save_options(array(
					"debug_type" => (int) $_POST["debug_type"]
				));
				
				$action = "";
				$simple_fields_did_save_options = TRUE;
				
			}
	
			// overview, if no action
			if (!$action) {
	
	
				/**
				 * view post connectors
				 */
				$post_connector_count = 0;
				foreach ($post_connectors as $onePostConnector) {
					if (!$onePostConnector["deleted"]) {
						$post_connector_count++;
					}
				}
	
	
				/**
				 * view existing field groups
				 */	
				?>
				<div class="simple-fields-edit-field-groups">
	
					<h3><?php _e('Field groups', 'simple-fields') ?></h3>
	
					<?php
					
					// Show messages, like "saved" and so on
					if (isset($simple_fields_did_save) && $simple_fields_did_save) {
						?><div id="message" class="updated"><p><?php _e('Field group saved', 'simple-fields') ?></p></div><?php
					} elseif (isset($simple_fields_did_delete) && $simple_fields_did_delete) {
						?><div id="message" class="updated"><p><?php _e('Field group deleted', 'simple-fields') ?></p></div><?php
					} elseif (isset($simple_fields_did_delete_post_connector) && $simple_fields_did_delete_post_connector) {
						?><div id="message" class="updated"><p><?php _e('Post connector deleted', 'simple-fields') ?></p></div><?php
					} elseif (isset($simple_fields_did_save_post_type_defaults) && $simple_fields_did_save_post_type_defaults) {
						?><div id="message" class="updated"><p><?php _e('Post type defaults saved', 'simple-fields') ?></p></div><?php
					} elseif (isset($simple_fields_did_save_options) && $simple_fields_did_save_options) {
						?><div id="message" class="updated"><p><?php _e('Simple Fields options saved', 'simple-fields') ?></p></div><?php
					}
					
					$field_group_count = 0;
					foreach ($field_groups as $oneFieldGroup) {
						if (!$oneFieldGroup["deleted"]) {
							$field_group_count++;
						}
					}
	
					if ($field_groups == $field_group_count) {
						echo "<p>".__('No field groups yet.', 'simple-fields')."</p>";
					} else {
						echo "<ul class=''>";
						foreach ($field_groups as $oneFieldGroup) {
							if ($oneFieldGroup["id"] && !$oneFieldGroup["deleted"]) {
								
								echo "<li>";
								echo "<a href='" . SIMPLE_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=$oneFieldGroup[id]'>$oneFieldGroup[name]</a>";
								if ($oneFieldGroup["fields_count"]) {
									$format = $oneFieldGroup["repeatable"] ? _n('1 added field, repeatable', '%d added fields, repeatable', $oneFieldGroup["fields_count"]) : _n('1 added field', '%d added fields', $oneFieldGroup["fields_count"]);
									echo "<br>" . __( sprintf($format, $oneFieldGroup["fields_count"]) );
								}
								echo "</li>";
							}
						}
						echo "</ul>";
					}
					echo "<p><a class='button' href='" . SIMPLE_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=0'>+ ".__('New field group', 'simple-fields')."</a></p>";
					?>
				</div>
			
			
				<div class="simple-fields-edit-post-connectors">
	
					<h3><?php _e('Post Connectors', 'simple-fields') ?></h3>
	
					<?php
					if (isset($simple_fields_did_save_connector) && $simple_fields_did_save_connector === true) {
						?><div id="message" class="updated"><p><?php _e('Post connector saved', 'simple-fields') ?></p></div><?php
					}
	
					if ($post_connector_count) {
						?><ul><?php
							foreach ($post_connectors as $one_post_connector) {
								if ($one_post_connector["deleted"] || !$one_post_connector["id"]) {
									continue;
								}
	
								?>
								<li>
									<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-connector&amp;connector-id=<?php echo $one_post_connector["id"] ?>"><?php echo $one_post_connector["name"] ?></a>
									<?php
									if ($one_post_connector["field_groups_count"]) {
										echo "<br>" . sprintf( _n('1 added field group', '%d added field groups', $one_post_connector["field_groups_count"]), $one_post_connector["field_groups_count"] );
									}
									?>
								</li>
								<?php
								
							}
						?></ul><?php
					} else {
						?>
						<!-- <p>No post connectors</p> -->
						<?php
					}
					?>
					<p>
						<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-connector&amp;connector-id=0" class="button">+ <?php _e('New post connector', 'simple-fields') ?></a>
					</p>
					
				</div>
	
				<div class="simple-fields-post-type-defaults">
					<h3><?php _e('Post type defaults', 'simple-fields') ?></h3>
					<ul>
						<?php
						$post_types = get_post_types();
						$arr_post_types_to_ignore = array("attachment", "revision", "nav_menu_item");
						foreach ($post_types as $one_post_type) {
							$one_post_type_info = get_post_type_object($one_post_type);
							if (!in_array($one_post_type, $arr_post_types_to_ignore)) {
	
								$default_connector = $this->get_default_connector_for_post_type($one_post_type);
								switch ($default_connector) {
									case "__none__":
										$default_connector_str = __('Default is to use <em>no connector</em>', 'simple-fields');
										break;
									case "__inherit__":
										$default_connector_str = __('Default is to inherit from <em>parent connector</em>', 'simple-fields');
										break;
									default:
										if (is_numeric($default_connector)) {
											
											$connector = $this->get_connector_by_id($default_connector);
											if ($connector !== FALSE) {
												$default_connector_str = sprintf(__('Default is to use connector <em>%s</em>', 'simple-fields'), $connector["name"]);
											}
										}
	
								}
	
								?><li>
									<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-type-defaults&amp;post-type=<?php echo $one_post_type ?>">
										<?php echo $one_post_type_info->label ?>
									</a>
									<br>
									<span><?php echo $default_connector_str ?></span>
								</li><?php
							}
						}
						?>
					</ul>
				</div>	


				<div class="simple-fields-debug">
					<h3><?php echo __('Debug', 'simple-fields') ?></h3>
					<?php
					// Dropdown with debug options

					// Debug type. 0 = no debug, 1 = debug for admins only, 2 = debug for all
					$options = $this->get_options();
					$debug_type = isset($options["debug_type"]) ? (int) $options["debug_type"] : "0";
					// capability edit_themes
					?>
					<form action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-options-save" method="post">
						<?php
						printf('
							<p>
								<select name=debug_type>
									<option value=0 %1$s>%4$s</option>
									<option value=1 %2$s>%5$s</option>
									<option value=2 %3$s>%6$s</option>
								</select>
							</p>
							', 
							$debug_type === 0 ? "selected" : "",
							$debug_type === 1 ? "selected" : "",
							$debug_type === 2 ? "selected" : "",
							__("Don't enable debug output", "simple-fields"),
							__("Enable debug output for administrators", "simple-fields"),
							__("Enable debug output for all users", "simple-fields")
						);
						?>
						<p class=description>
							<?php _e("Automatically append information about attached fields on posts (using filter 'the_content').", "simple-fields"); ?>
						</p>

						<p>
							<input class="button-primary" type=submit value="<?php _e("Save changes", "simple-fields") ?>">
						</p>
					</form> <!-- // enable debug -->
				
					<ul>
						<li><a href='<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=simple-fields-view-debug-info'><?php echo __('View debug information', 'simple-fields') ?></a></li>
					</ul>

				</div>
				
				<?php
	
			} // end simple_fields_options
	
			?>
			</div>
		</div>	
	
		<?php
	} // end func simple_fields_options


	/**
	 * Add the admin menu page for simple fields
	 * If you want to hide this for some reason (maybe you are a theme developer that want to use simple fields, but not show the options page to your users)
	 * you can add a filter like this:
	 *
	 * add_filter("simple-fields-add-admin-menu", function($bool) {
	 *     return FALSE;
	 * });
	 *
	 */
	function admin_menu() {
				
		$show_submenu_page = TRUE;
		$show_submenu_page = apply_filters("simple-fields-add-admin-menu", $show_submenu_page);
		if ($show_submenu_page) {
			add_submenu_page( 'options-general.php' , SIMPLE_FIELDS_NAME, SIMPLE_FIELDS_NAME, "administrator", "simple-fields-options", array($this, "options_page"));
		}
		
	}


	/**
	 * Gets the post connectors for a post type
	 *
	 * @return array
	 */
	function get_post_connectors_for_post_type($post_type) {
		
		global $sf;
		
		$arr_post_connectors = $this->get_post_connectors();
		$arr_found_connectors = array();
	
		foreach ($arr_post_connectors as $one_connector) {
			if ($one_connector && in_array($post_type, $one_connector["post_types"])) {
				$arr_found_connectors[] = $one_connector;
			}
		}
		return $arr_found_connectors;
	}
	
	/**
	 * Registers a new field type
	 * @param string $field_type_name Name of the class with the new field type
	 */
	static function register_field_type($field_type_name) {
		global $sf;
		$sf->_register_field_type($field_type_name);
	}

	function _register_field_type($field_type_name) {
		$custom_field_type = new $field_type_name;
		$this->registered_field_types[$custom_field_type->key] = $custom_field_type;
	}

	/**
	 * Get all options
	 * @return array
	 */
	function get_options() {
		$options = (array) get_option("simple_fields_options");
		return $options;
	}
	
	/**
	 * Save options
	 * @param array $new_options. will be merged with old options, so you only need to add your modified stuff to the array, and then all old stuff will be untouched.
	 */
	function save_options($new_options) {
		$old_options = $this->get_options();
		$new_options = wp_parse_args($new_options, $old_options);
		update_option("simple_fields_options", $new_options);
		$this->clear_caches();
	}
	
	/**
	 * If debug option is enabled then output debug-box by hooking onto the_content
	 */
	function maybe_add_debug_info() {

		global $sf;
		$options = $sf->get_options();
		if (isset($options["debug_type"]) && $options["debug_type"] !== 0) {
		
			// 1 = debug for admins only, 2 = debug for all
			if ( ($options["debug_type"] === 1 && current_user_can("edit_themes")) ||  $options["debug_type"] === 2) {

				// enqueu jquery because that is used to show/hide the debug box
				wp_enqueue_script("jquery");
				
				// add filter
				add_filter("the_content", array($this, "simple_fields_content_debug_output"));
			}	
	
		}

	}
	
	/** 
	 * Outputs the names of the post connectors attached to the post you view + outputs the values
	 */
	function simple_fields_content_debug_output($the_content) {

		// we only want to appen the debug code when being used from get_the_content or the_content
		// but for example get_the_excerpt is also using filter the_content which leads to problems
		// so check that we are somewhere inside the right functions
		$is_inside_righ_function = FALSE;
	    $arr_trace = debug_backtrace();
	    $arr_trace_count = count($arr_trace);

	    for ($i = 0; $i < $arr_trace_count; $i++) {
		    if ( isset($arr_trace[$i]["function"]) && in_array($arr_trace[$i]["function"], array("the_content", "get_the_content"))) {
		    	$is_inside_righ_function = TRUE;
		    	break;
		    }
	    }

	    if (!$is_inside_righ_function) {

		    // Don't do the debug, since we're not in the_content
		    return $the_content;

	    }

		
		$output = "";
		$output_all = "";
		$field_count = 0;
		
		$post_connector_with_values = simple_fields_get_all_fields_and_values_for_post(get_the_ID(), "include_deleted=0");
		if ($post_connector_with_values) {

			foreach ($post_connector_with_values["field_groups"] as $one_field_group) {

				if ($one_field_group["deleted"]) continue;
				
				$output_all .= "<div style='font-weight:bold;margin:1em 0 0 0;'>";
				$str_is_repeatable = $one_field_group["repeatable"] ? __(" (Repeatable)", "simple-fields") : "";
				$output_all .= sprintf(
					__('Fieldgroup %1$s %2$s', "simple-fields"),
					$one_field_group["name"],
					$str_is_repeatable
				);
				$output_all .= "</div>";
				
				$str_all_group_fields = "";
				foreach ($one_field_group["fields"] as $one_field) {

					if ($one_field["deleted"]) continue;

					$field_count++;
					$content = "";
					$content .= "<ul style='background:#eee;padding:.5em;margin:0;display:block;'>";
					$content .= "<li>Field <b>" . $one_field["name"] . "</b>";
					$content .= ", type <b>" . $one_field["type"] . "</b>";

					if (isset($one_field["slug"])) {
						
						$content .=  ", slug <b>" . $one_field["slug"] . "</b>";
						$str_all_group_fields .= $one_field["slug"] . ",";
						
						if ($one_field_group["repeatable"]) {
							$content .= "<br>Use <code><b>simple_fields_values('".$one_field["slug"]."')</b></code> to get:";
							ob_start();
							sf_d( simple_fields_values($one_field["slug"]) );
							$content .= ob_get_clean();
						} else {		
							$content .= "<br>Use <code><b>simple_fields_value('".$one_field["slug"]."')</b></code> to get:";
							ob_start();
							sf_d( simple_fields_value($one_field["slug"]) );
							$content .= ob_get_clean();
						}
						
					} else {
						$content .= "<br>No slug for this field found (probably old field that has not been edited and saved).";
					}
					$content .= "</ul>";
					$output_all .= $content;
				}

				// Show example how to get all fields in one shot
				// But only show if field has more than one field, otherwise it's kinda not useful
				if ( sizeof($one_field_group["fields"]) > 1 ) {
					$str_all_group_fields = preg_replace('!,$!', '', $str_all_group_fields);
					$output_all .= "<ul style='background:#eee;padding:.5em;margin:0;display:block;'>";
					if ($one_field_group["repeatable"]) {
						$content = "<li>Get all fields at once: use <code><b>simple_fields_values('".$str_all_group_fields."')</b></code> to get:";
						ob_start();
						sf_d( simple_fields_values($str_all_group_fields) );
						$content .= ob_get_clean();
					} else {
						$content = "<li>Get all fields at once: use <code><b>simple_fields_value('".$str_all_group_fields."')</b></code> to get:";
						ob_start();
						sf_d( simple_fields_value($str_all_group_fields) );
						$content .= ob_get_clean();
					}
					$output_all .= $content;
					$output_all .= "</ul>";
				}
			
			} // for each field group
		}
		
		if ($output_all) {
			$str_show_fields = __("Show fields.", "simple-fields");
			$str_hide_fields = __("Hide fields.", "simple-fields");
			?>
			<script>
			window.simple_fields_post_debug_show_hide = window.simple_fields_post_debug_show_hide || function(t) {
				var $t = jQuery(t);
				var $div_wrap = $t.closest("div.simple-fields-post-debug-wrap");
				var debug_content = $div_wrap.find("div.simple-fields-post-debug-content");
				debug_content.toggle();
				if (debug_content.is(":visible")) {
					$t.text("<?php echo $str_hide_fields ?>");
				} else {
					$t.text("<?php echo $str_show_fields ?>");
				}
				return false;
			}
			</script>
			<?php
			$output_all = '
				<div class="simple-fields-post-debug-wrap" style="display:block;margin:0;padding:0;">
					<p style="margin:0;padding:0;display:block;">This post has ' . $field_count . ' Simple Fields-fields attached. <a href="#" onclick="return simple_fields_post_debug_show_hide(this);">'.$str_show_fields.'</a></p>
					<div class="simple-fields-post-debug-content" style="display:none;">'.$output_all.'</div>
				</div>
				';
		}
		// if a field has the slug caption the output will be [caption] and then it will crash with some shortcodes, so we try to fix that here
		$output_all = str_replace("[", "&#91;", $output_all);
		$output_all = str_replace("]", "&#93;", $output_all);
		return $the_content . $output_all;
	}

	/**
	 * Retrieve and return extended return values for a field type
	 * @param mixed $field array or string or int or whatever with field info
	 * @param mixed $field_value the saved value
	 */
	function get_extended_return_values_for_field($field, $field_value) {
	

		$return_field_value = array();

		if ("file" === $field["type"]) {

			// field is of type file
			// lets get more info about that file then, so we have most useful stuff in an array – hooray!
			
			if (isset($field_value) && is_numeric($field_value)) {
				$file_id                             = (int) $field_value;
				$return_field_value["id"]            = $file_id;
				$return_field_value["is_image"]      = wp_attachment_is_image( $file_id );
				$return_field_value["url"]           = wp_get_attachment_url( $file_id );
				$return_field_value["mime"]          = get_post_mime_type( $file_id );

				// generate html for all registered image sizes
				$arr_sizes = array_merge(array("full"), get_intermediate_image_sizes());
				$return_field_value["link"]      = array();
				$return_field_value["image"]     = array();
				$return_field_value["image_src"] = array();
				foreach ($arr_sizes as $size_key) {
					$return_field_value["link"][$size_key]      = wp_get_attachment_link( $file_id, $size_key );
					$return_field_value["image"][$size_key]     = wp_get_attachment_image( $file_id, $size_key );
					$return_field_value["image_src"][$size_key] = wp_get_attachment_image_src( $file_id, $size_key );
				}
			
				$return_field_value["metadata"] = wp_get_attachment_metadata( $file_id );
				$return_field_value["post"] = get_post( $file_id );
				
			}

		} else if ("radiobuttons" === $field["type"]) {
			
			// if radiobutton: get all values and mark which one is the selected

			$type_radiobuttons_options = $field["type_radiobuttons_options"];

			$return_field_value["selected_value"] 		= FALSE;
			$return_field_value["selected_radiobutton"]	= array();
			$return_field_value["radiobuttons"] 		= array();

			foreach ($type_radiobuttons_options as $button_key => $button_value) {
			
				if ($button_key == "checked_by_default_num") continue;
				
				if (isset($button_value["deleted"]) && $button_value["deleted"]) continue;
				
				$button_value_value = isset($button_value["value"]) ? $button_value["value"] : "";
				
				$return_field_value["radiobuttons"][] = array(
					"value"       => $button_value_value,
					"key"         => $button_key,
					"is_selected" => ($field_value === $button_key)
				);
				if ($field_value === $button_key) {
					$return_field_value["selected_radiobutton"] = array(
						"value"       => $button_value_value,
						"key"         => $button_key,
						"is_selected" => TRUE
					);
					$return_field_value["selected_value"] = $button_value["value"];
				}
			}
						
		} else if ("dropdown" === $field["type"]) {
			
			$type_dropdown_options = $field["type_dropdown_options"];

			// dropdown can be multiple since 1.1.4
			if (isset($type_dropdown_options["enable_multiple"]) && $type_dropdown_options["enable_multiple"]) {
				
				// multiple = return array with same info as single values
				$arr_dropdown_values = $field_value;

				$return_field_value["selected_values"]	= array();
				$return_field_value["selected_options"]	= array();
				$return_field_value["options"] = array();

				foreach ($type_dropdown_options as $dropdown_key => $dropdown_value) {

					// Only values like dropdown_num_2 are allowed
					if ( strpos($dropdown_key, "dropdown_num_") === FALSE) { continue; }

					// Skip deleted
					if (isset($dropdown_value["deleted"]) && $dropdown_value["deleted"]) continue;					
					
					$dropdown_value_value = isset($dropdown_value["value"]) ? $dropdown_value["value"] : "";
					
					$return_field_value["options"][] = array(
						"value"       => $dropdown_value_value,
						"key"         => $dropdown_key,
						"is_selected" => in_array($dropdown_key, $arr_dropdown_values)
					);

					if (in_array($dropdown_key, $arr_dropdown_values)) {
						
						$return_field_value["selected_options"][] = array(
							"value"       => $dropdown_value_value,
							"key"         => $dropdown_key,
							"is_selected" => TRUE
						);
						
						$return_field_value["selected_values"][] = $dropdown_value["value"];
					}
				}

			} else {

				// Single value
				$return_field_value["selected_value"]	= FALSE;
				$return_field_value["selected_option"]	= array();
				$return_field_value["options"] 			= array();

				foreach ($type_dropdown_options as $dropdown_key => $dropdown_value) {

					// Only values like dropdown_num_2 are allowed
					if ( strpos($dropdown_key, "dropdown_num_") === FALSE) { continue; }

					// Skip deleted
					if ($dropdown_value["deleted"]) continue;
					
					$return_field_value["options"][] = array(
						"value"       => $dropdown_value["value"],
						"key"         => $dropdown_key,
						"is_selected" => ($field_value === $dropdown_key)
					);

					if ($field_value === $dropdown_key) {
					
						$return_field_value["selected_option"] = array(
							"value"       => $dropdown_value["value"],
							"key"         => $dropdown_key,
							"is_selected" => TRUE
						);
						
						$return_field_value["selected_value"] = $dropdown_value["value"];
					
					}
				}

			} // if single
			
		} else if ("post" === $field["type"]) {

			if (isset($field_value) && is_numeric($field_value)) {
				$post_id = (int) $field_value;
				$return_field_value["id"] 			= $post_id;
				$return_field_value["title"] 		= get_the_title( $post_id );
				$return_field_value["permalink"] 	= get_permalink( $post_id );
				$return_field_value["post"] 		= get_post( $post_id );
			}
			
		
		} else if ("user" === $field["type"]) {

			if (isset($field_value) && is_numeric($field_value)) {
				
				$user_id = (int) $field_value;
				$return_field_value["id"]	= $user_id;
				
				// user is a WP_User object,
				// see this url for more info on what data you can get:
				// http://codex.wordpress.org/Function_Reference/get_userdata
				$user                                = get_user_by( "id", $user_id );				
				$return_field_value["first_name"]    = $user->first_name;
				$return_field_value["last_name"]     = $user->last_name;
				$return_field_value["user_login"]    = $user->user_login;
				$return_field_value["user_email"]    = $user->user_email;
				$return_field_value["user_nicename"] = $user->user_nicename;
				$return_field_value["display_name"]  = $user->display_name;
				$return_field_value["user"]          = $user;
				
			}

		} else if ("taxonomy" === $field["type"]) {

			$taxonomy = get_taxonomy($field_value);
			$return_field_value["name"]          	= "";
			$return_field_value["singular_name"] 	= "";
			$return_field_value["plural_name"] 		= "";
			$return_field_value["taxonomy"]      	= "";
			if ($taxonomy) {
				$return_field_value["name"]          = $taxonomy->name;
				$return_field_value["singular_name"] = $taxonomy->labels->singular_name;
				$return_field_value["plural_name"]   = $taxonomy->labels->name;
				$return_field_value["taxonomy"]      = $taxonomy;
			}

		} else if ("taxonomyterm" === $field["type"]) {
			
			$type_taxonomyterm_options = $field["type_taxonomyterm_options"];

			// multiple tags can be selected
			$arr_terms = array();
			if (isset($field_value) && is_array($field_value)) {
				foreach ($field_value as $one_term_id) {
					
					$term = get_term_by("id", $one_term_id, $type_taxonomyterm_options["enabled_taxonomy"]);
					$arr_terms[] = array(
						"name" => $term->name,
						"slug" => $term->slug,
						"id"   => $term->term_id,
						"term" => $term
					);
					
				}
			}
			
			$return_field_value["terms"] = $arr_terms;
		
		} else if ("date" === $field["type"]) {

			// format = default in jquery = mm/dd/yy (year 4 digits)
			// sf_d($field_value); // 14/10/2012
			$return_field_value["saved_value"] = $field_value;
			if (isset($field_value)) {
				$field_value = trim($field_value);
				if (preg_match('!^\d{2}\/\d{2}\/\d{4}$!', $field_value)) {
					$date = strtotime( str_replace('/', "-", $field_value) );
					$return_field_value["timestamp"] = $date;
					$return_field_value["date_format"] = date(get_option('date_format'), $date);
					$return_field_value["date_format_i18n"] = date_i18n( get_option('date_format'), $date);
					// $timezone_format = _x('Y-m-d G:i:s', 'timezone date format');
					//echo get_option("gmt_offset"); // 14 if UTC+14
				}
			}
			
		}
			
		return $return_field_value;
	}

	/**
	 * Gets a field group using it's id. Deleted field groups are not included
	 * @since 1.0.5
	 * @param string slug of field group (or id, actually)
	 * @return mixed array with field group info if field groups exists, false if does not exist
	 */
	function get_field_group_by_slug($field_group_slug) {
#echo 111;		
#var_dump($this->ns_key);
		$cache_key = 'simple_fields_'.$this->ns_key.'_get_field_group_by_slug_' . $field_group_slug;
#echo $cache_key;
		$return_val = wp_cache_get( $cache_key, 'simple_fields' );
		if (FALSE === $return_val) {
		
		 	$field_groups = $this->get_field_groups();
		 	
			if (!is_numeric($field_group_slug)) {
	
				// not number so look for field group with this variable as slug
				foreach ($field_groups as $one_field_group) {
					if ($one_field_group["deleted"]) continue;
					if ($one_field_group["slug"] == $field_group_slug) {
						wp_cache_set( $cache_key, $one_field_group, 'simple_fields' );
						return $one_field_group;
					}
				}
				
				wp_cache_set( $cache_key, FALSE, 'simple_fields' );
				return FALSE;
	
			} else {
	
				// look for group using id
			 	if (isset($field_groups[$field_group_slug]) && is_array($field_groups[$field_group_slug]) && !$field_groups[$field_group_slug]["deleted"]) {
					wp_cache_set( $cache_key, $field_groups[$field_group_slug], 'simple_fields' );
				 	return $field_groups[$field_group_slug];
			 	} else {
				 	wp_cache_set( $cache_key, FALSE, 'simple_fields' );
				 	return FALSE;
			 	}
			 	
			}
				
		}

		return $return_val;

	}


	/**
	 * Returns a field from a fieldgroup using their slugs
	 *
	 * @since 1.0.5
	 * @param string $field_slug
	 * @param string $fieldgroup_slug
	 * @return mixed Array with field info if field is found, false if not found
	 */
	function get_field_by_slug($field_slug = "", $fieldgroup_slug = "") {

		$field_group = $this->get_field_group_by_slug($fieldgroup_slug);
		if (!$field_group) return FALSE;
		
		foreach ($field_group["fields"] as $one_field) {
			if ($field_slug === $one_field["slug"]) {
				return $one_field;
			}
		}
		
		// No field with that slug found
		return FALSE;
	}

	/**
	 * Clear the key used for wp_cache_get and wp_cache_set
	 * Run this when options etc have been changed so fresh values are fetched upon next get
	 */
	function clear_caches() {

		$prev_key = $this->ns_key;
		$this->ns_key = wp_cache_incr( 'simple_fields_namespace_key', 1, 'simple_fields' );
		if ($this->ns_key === FALSE) {
			// I really don't know why, but wp_cache_incr returns false...always or sometimes?
			// Manually update namespace key by one
			$this->ns_key = $prev_key + 1;
			wp_cache_set( 'simple_fields_namespace_key', $this->ns_key, 'simple_fields' );
		}
		// echo "clear_key";var_dump($this->ns_key);
	}
	
} // end class


// Boot it up!
global $sf;
$sf = new simple_fields();
$sf->init();
