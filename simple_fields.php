<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://eskapism.se/code-playground/simple-fields/
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 0.x
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


load_plugin_textdomain( 'simple-fields', null, basename(dirname(__FILE__)).'/languages/');


/**
 * Class to keep all simple fields stuff together a bit better
 */ 
class simple_fields {

	const DEBUG_ENABLED = TRUE; // set to true to enable some debug output
	
	public 
		// Looks something like this: "Simple-Fields-GIT/simple_fields.php"
		$plugin_foldername_and_filename;
		

	/**
	 * Init is where we setup actions and filers and loads stuff and a little bit of this and that
	 *
	 */
	function init() {

		define( "EASY_FIELDS_URL", plugins_url(basename(dirname(__FILE__))). "/");
		define( "EASY_FIELDS_NAME", "Simple Fields");
		define( "EASY_FIELDS_VERSION", "0.x");

		require( dirname(__FILE__) . "/functions_admin.php" );
		require( dirname(__FILE__) . "/functions_post.php" );

		$this->plugin_foldername_and_filename = basename(dirname(__FILE__)) . "/" . basename(__FILE__);

		// Actions
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_menu', "simple_fields_admin_menu" );
		add_action( 'admin_head', array($this, 'admin_head') );
		add_action( 'wp_ajax_simple_fields_field_group_add_field', 'simple_fields_field_group_add_field' );
		add_action( 'admin_head', array($this, 'admin_head_select_file') );
		add_action( 'wp_ajax_simple_fields_field_type_post_dialog_load', 'simple_fields_field_type_post_dialog_load' );
		
		// Filters
		add_filter( 'plugin_row_meta', array($this, 'set_plugin_row_meta'), 10, 2 );

		add_filter( 'media_send_to_editor', 'simple_fields_media_send_to_editor', 15, 2 );
		add_filter( 'media_upload_tabs', 'simple_fields_media_upload_tabs', 15);
		add_filter( 'media_upload_form_url', 'simple_fields_media_upload_form_url');
		add_action( 'admin_footer', array($this, 'admin_footer') );
		add_action( 'admin_init', 'simple_fields_post_admin_init' );
		add_action( 'dbx_post_sidebar', array($this, 'post_dbx_post_sidebar') );

		add_action( 'save_post', array($this, 'save_postdata') );
		add_action( 'wp_ajax_simple_fields_metabox_fieldgroup_add', array($this, 'metabox_fieldgroup_add') );
		
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
				print_r($details);
			}
			echo "</pre>";
		}
	}

	function admin_init() {

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_script("jquery-effects-highlight");
		wp_enqueue_script("thickbox");
		wp_enqueue_style("thickbox");
		wp_enqueue_script("jscolor", EASY_FIELDS_URL . "jscolor/jscolor.js"); // color picker for type color
		wp_enqueue_script("simple-fields-date", EASY_FIELDS_URL . "datepicker/date.js"); // date picker for type date
		wp_enqueue_script("jquery-datepicker", EASY_FIELDS_URL . "datepicker/jquery.datePicker.js"); // date picker for type date
		wp_enqueue_style('jquery-datepicker', EASY_FIELDS_URL.'datepicker/datePicker.css', false, EASY_FIELDS_VERSION);

		// add css and scripts
		wp_enqueue_style('simple-fields-styles', EASY_FIELDS_URL.'styles.css', false, EASY_FIELDS_VERSION);
		wp_register_script('simple-fields-scripts', EASY_FIELDS_URL.'scripts.js', false, EASY_FIELDS_VERSION);
		wp_localize_script('simple-fields-scripts', 'sfstrings', array(
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

		define( "EASY_FIELDS_FILE", menu_page_url("simple-fields-options", false) );

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
	 * File browsre:
	 * hide some things there to make it more clean and user friendly
	 */
	function admin_head_select_file() {
	
		// Only output this css when we are showing a file dialog for simple fields
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			?>
			<style type="text/css">
				.wp-post-thumbnail,
				tr.image_alt,
				tr.post_title,
				tr.align,
				tr.image-size,
				tr.post_excerpt,
				tr.url,
				tr.post_content
				 {
					display: none;
				}
			</style>
			<?php
		}
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
		$post_connectors = $sf->get_post_connectors();
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
		$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
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
		
		// attach post connector
		$simple_fields_selected_connector = (isset($_POST["simple_fields_selected_connector"])) ? $_POST["simple_fields_selected_connector"] : null;
		update_post_meta($post_id, "_simple_fields_selected_connector", $simple_fields_selected_connector);
	
		$post_id = (int) $post_id;
		$fieldgroups = (isset($_POST["simple_fields_fieldgroups"])) ? $_POST["simple_fields_fieldgroups"] : null;
		
		$field_groups_option = get_option("simple_fields_groups");
	
		if ( !$table = _get_meta_table("post") ) { return false; }
		global $wpdb;
	
		if ($post_id && is_array($fieldgroups)) {
	
			// remove existing simple fields custom fields for this post
			$wpdb->query("DELETE FROM $table WHERE post_id = $post_id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");
	
			// cleanup missing keys, due to checkboxes not being checked
			$fieldgroups_fixed = $fieldgroups;
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
			
				foreach ($one_field_group_fields as $posted_id => $posted_vals) {
					if ($posted_id == "added") {
						// echo "<br><br>posted_id: $posted_id";
						// echo "<br>posted_vals: "; bonny_d($posted_vals);
						// $fieldgroups_fixed[$one_field_group_id][$posted_id]["added"] = $posted_vals;
						continue;
					}
					$fieldgroups_fixed[$one_field_group_id][$posted_id] = array();
					// echo "<br><br>posted_id: $posted_id";
					// echo "<br>posted_vals: "; bonny_d($posted_vals);
					// bonny_d($added_vals);
					// loopa igenom "added"-värdena och fixa så att allt finns
					foreach ($one_field_group_fields["added"] as $added_id => $added_val) {
						// $fieldgroups_fixed
						// echo "<br>added_id: $added_id";
						// echo "<br>added_val: $added_val";
						$fieldgroups_fixed[$one_field_group_id][$posted_id][$added_id] = $fieldgroups[$one_field_group_id][$posted_id][$added_id];
					}
				}
			
			}
			$fieldgroups = $fieldgroups_fixed;
	
			update_post_meta($post_id, "_simple_fields_been_saved", "1");
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
	
				foreach ($one_field_group_fields as $one_field_id => $one_field_values) {
					// one_field_id = id på fältet vi sparar. t.ex. id:et på "måndag" eller "tisdag"
					// one_field_values = sparade värden för detta fält, sorterat i den ordning som syns i admin
					//					  dvs. nyaste överst (med key "new0"), och sedan key 0, key 1, osv.
					
				
					// determine type of field we are saving
					$field_info = isset($field_groups_option[$one_field_group_id]["fields"][$one_field_id]) ? $field_groups_option[$one_field_group_id]["fields"][$one_field_id] : NULL;
					$field_type = $field_info["type"]; // @todo: this should be a function
					$do_wpautop = false;
					if ($field_type == "textarea" && isset($field_info["type_textarea_options"]["use_html_editor"]) && $field_info["type_textarea_options"]["use_html_editor"] == 1) {
						// it's a tiny edit area, so use wpautop to fix p and br
						$do_wpautop = true;
					}
					
					// @todo: empty checkboxes = values saved for the wrong fieldgroup
					// it "jumps" past one of the groups when saving, so the wrong group gets the value
					// ide: korrigera arrayen? istället för sparandet
					$num_in_set = 0;
					// save entered value for each added group
					foreach ($one_field_values as $one_field_value) {
					
						$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group_id}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";
						$custom_field_value = $one_field_value;
	
						if ($do_wpautop) {
							$custom_field_value = wpautop($custom_field_value);
							#var_dump($custom_field_value);#exit;
						}
	
						update_post_meta($post_id, $custom_field_key, $custom_field_value);
	
						$num_in_set++;
					
					}
	
				}
				
			}
			// if array
		} else if (empty($fieldgroups)) {
			// if fieldgroups are empty we still need to save it
			// remove existing simple fields custom fields for this post
			$wpdb->query("DELETE FROM $table WHERE post_id = $post_id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");
		} 
	
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
		$sf->meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, true);
	
		exit;
	}


	/**
	 * output the html for a field group in the meta box
	 */
	function meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, $use_defaults) {
	
		$post = get_post($post_id);
		
		$field_groups = get_option("simple_fields_groups");
		$current_field_group = $field_groups[$field_group_id];
		$repeatable = (bool) $current_field_group["repeatable"];
		$field_group_css = "simple-fields-fieldgroup-$field_group_id";
		?>
		<li class="simple-fields-metabox-field-group <?php echo $field_group_css ?>">
			<?php // must use this "added"-thingie do be able to track added field group that has no added values (like unchecked checkboxes, that we can't detect ?>
			<input type="hidden" name="simple_fields_fieldgroups[<?php echo $field_group_id ?>][added][<?php echo $num_in_set ?>]" value="1" />
			
			<div class="simple-fields-metabox-field-group-handle"></div>
			<?php
			// if repeatable: add remove-link
			if ($repeatable) {
				?><div class="hidden simple-fields-metabox-field-group-delete"><a href="#" title="<?php _e('Remove field group', 'simple-fields') ?>"></a></div><?php
			}
			?>
			<?php
			
			// Output content for each field in this fieldgroup
			// LI = fieldgroup
			// DIV = field
			foreach ($current_field_group["fields"] as $field) {
				
				if ($field["deleted"]) { continue; }
				
				$field_id = $field["id"];
				$field_unique_id = "simple_fields_fieldgroups_{$field_group_id}_{$field_id}_{$num_in_set}";
				$field_name = "simple_fields_fieldgroups[$field_group_id][$field_id][$num_in_set]";
				$field_class = "simple-fields-fieldgroups-field-{$field_group_id}-{$field_id}";
	
				$custom_field_key = "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}";
				$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist
				
				$description = "";
				if (!empty($field["description"])) {
					$description = sprintf("<div class='simple-fields-metabox-field-description'>%s</div>", esc_html($field["description"]));
				}
				
				?>
				<div class="simple-fields-metabox-field <?php echo $field_class ?>">
					<?php
					// different output depending on field type
					if ("checkbox" == $field["type"]) {
		
						if ($use_defaults) {
							$checked = $field["type_checkbox_options"]["checked_by_default"];
						} else {
							$checked = (bool) $saved_value;
						}
						
						if ($checked) {
							$str_checked = " checked='checked' ";
						} else {
							$str_checked = "";
						}
						echo "<input $str_checked id='$field_unique_id' type='checkbox' name='$field_name' value='1' />";
						echo "<label class='simple-fields-for-checkbox' for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
		
					} elseif ("radiobuttons" == $field["type"]) {
		
						echo "<label>" . $field["name"] . "</label>";
						echo $description;
						$radio_options = $field["type_radiobuttons_options"];
						$radio_checked_by_default_num = $radio_options["checked_by_default_num"];
	
						$loopNum = 0;
						foreach ($radio_options as $one_radio_option_key => $one_radio_option_val) {
							if ($one_radio_option_key == "checked_by_default_num") { continue; }
							if ($one_radio_option_val["deleted"]) { continue; }
							$radio_field_unique_id = $field_unique_id . "_radio_".$loopNum;
							
							$selected = "";
							if ($use_defaults) {
								if ($radio_checked_by_default_num == $one_radio_option_key) { $selected = " checked='checked' "; }
							} else {
								if ($saved_value == $one_radio_option_key) { $selected = " checked='checked' "; }
							}
													
							echo "<div class='simple-fields-metabox-field-radiobutton'>";
							echo "<input $selected name='$field_name' id='$radio_field_unique_id' type='radio' value='$one_radio_option_key' />";
							echo "<label for='$radio_field_unique_id' class='simple-fields-for-radiobutton'> ".$one_radio_option_val["value"]."</label>";
							echo "</div>";
							
							$loopNum++;
						}
		
					} elseif ("dropdown" == $field["type"]) {
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "<select id='$field_unique_id' name='$field_name'>";
						foreach ($field["type_dropdown_options"] as $one_option_internal_name => $one_option) {
							// $one_option_internal_name = dropdown_num_3
							if ($one_option["deleted"]) { continue; }
							$dropdown_value_esc = esc_html($one_option["value"]);
							$selected = "";
							if ($use_defaults == false && $saved_value == $one_option_internal_name) {
								$selected = " selected='selected' ";
							}
							echo "<option $selected value='$one_option_internal_name'>$dropdown_value_esc</option>";
						}
						echo "</select>";
	
					} elseif ("file" == $field["type"]) {
	
						$current_post_id = !empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
						$attachment_id = (int) $saved_value;
						$image_html = "";
						$image_name = "";
						if ($attachment_id) {
							$image_thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
							$image_thumbnail = $image_thumbnail[0];
							$image_html = "<img src='$image_thumbnail' alt='' />";
							$image_post = get_post($attachment_id);
							$image_name = esc_html($image_post->post_title);
						}
						$class = "";
						if ($description) {
							$class = "simple-fields-metabox-field-with-description";
						}
						echo "<div class='simple-fields-metabox-field-file $class'>";
							echo "<label>{$field["name"]}</label>";
							echo $description;
							echo "<div class='simple-fields-metabox-field-file-col1'>";
								echo "<div class='simple-fields-metabox-field-file-selected-image'>$image_html</div>";
							echo "</div>";
							echo "<div class='simple-fields-metabox-field-file-col2'>";
								echo "<input type='hidden' class='text simple-fields-metabox-field-file-fileID' name='$field_name' id='$field_unique_id' value='$attachment_id' />";							
	
								$field_unique_id_esc = rawurlencode($field_unique_id);
								// $file_url = "media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=$field_unique_id_esc&post_id=$post_id&TB_iframe=true";
								$file_url = get_bloginfo('url') . "/wp-admin/media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=$field_unique_id_esc&post_id=$current_post_id&TB_iframe=true";
								echo "<a class='thickbox simple-fields-metabox-field-file-select' href='$file_url'>".__('Select file', 'simple-fields')."</a>";
								
								$class = ($attachment_id) ? " " : " hidden ";
								$href_edit = ($attachment_id) ? admin_url("media.php?attachment_id={$attachment_id}&action=edit") : "#";
								echo " <a href='{$href_edit}' class='simple-fields-metabox-field-file-edit $class'>".__('Edit', 'simple-fields') . "</a>";
								echo " <a href='#' class='simple-fields-metabox-field-file-clear $class'>".__('Clear', 'simple-fields')."</a>";							
								echo "<div class='simple-fields-metabox-field-file-selected-image-name'>$image_name</div>";
								
							echo "</div>";
						echo "</div>";
	
					} elseif ("image" == $field["type"]) {
	
						$text_value_esc = esc_html($saved_value);
						echo "<label>".__('image', 'simple-fields')."</label>";
						echo $description;
						echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						
					} elseif ("textarea" == $field["type"]) {
		
						$textarea_value_esc = esc_html($saved_value);
						$textarea_options = isset($field["type_textarea_options"]) ? $field["type_textarea_options"] : array();
						
						$textarea_class = "";
						$textarea_class_wrapper = "";
	
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
	
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
							$args = array("textarea_name" => $field_name, "editor_class" => "simple-fields-metabox-field-textarea-tinymce");
							echo "<div class='simple-fields-metabox-field-textarea-tinymce-wrapper'>";
							wp_editor( $saved_value, $field_unique_id, $args );
							echo "</div>";
						} else {
							echo "<div class='simple-fields-metabox-field-textarea-wrapper'>";
							echo "<textarea class='simple-fields-metabox-field-textarea' name='$field_name' id='$field_unique_id' cols='50' rows='5'>$textarea_value_esc</textarea>";
							echo "</div>";
						}
		
					} elseif ("text" == $field["type"]) {
		
						$text_value_esc = esc_html($saved_value);
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
		
					} elseif ("color" == $field["type"]) {
						
						$text_value_esc = esc_html($saved_value);
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "<input class='text simple-fields-field-type-color' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
	
					} elseif ("date" == $field["type"]) {
	
						// $datef = __( 'M j, Y @ G:i' ); // same format as in meta-boxes.php
						// echo date_i18n( $datef, strtotime( current_time('mysql') ) );
						
						$text_value_esc = esc_html($saved_value);
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						echo "<input class='text simple-fields-field-type-date' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
	
					} elseif ("taxonomy" == $field["type"]) {
						
						$arr_taxonomies = get_taxonomies(array(), "objects");					
						$enabled_taxonomies = (array) @$field["type_taxonomy_options"]["enabled_taxonomies"];
						
						//echo "<pre>";print_r($enabled_taxonomies );echo "</pre>";
						
						$text_value_esc = esc_html($saved_value);
						// var_dump($saved_value);
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						
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
	
	
					} elseif ("taxonomyterm" == $field["type"]) {
						
						$enabled_taxonomy = @$field["type_taxonomyterm_options"]["enabled_taxonomy"];
						$additional_arguments = @$field["type_taxonomyterm_options"]["additional_arguments"];
	
						// hämta alla terms som finns för taxonomy $enabled_taxonomy
						// @todo: kunna skicka in args här, t.ex. för orderby
	
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
	
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
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;					
	
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
	
					} elseif ("user" == $field["type"]) {
					
						$saved_value_int = (int) $saved_value;
					
						echo "<div class='simple-fields-metabox-field-post'>";
						// echo "<pre>"; print_r($type_post_options); echo "</pre>";
						echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
						echo $description;
						
						// must set orderby or it will not get any users at all. yes. it's that weird.
						$args = array(
							//' role' => 'any'
							"orderby" => "login",
							"order" => "asc"
						);
						$users_query = new WP_User_Query( $args );
						$users = $users_query->results;
						
						// echo "<pre>";print_r($users);
						/*
					    [0] => stdClass Object
					        (
					            [ID] => 1
					            [user_login] => admin
					            [user_pass] => $P$BKPla7vRGQ4h/6tgUDdIad11Jv5GHX.
					            [user_nicename] => admin
					            [user_email] => par.thernstrom@gmail.com
					            [user_url] => 
					            [user_registered] => 2011-05-06 07:53:19
					            [user_activation_key] => 
					            [user_status] => 0
					            [display_name] => admin
					        )					
						*/
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
	
	
					} // field types
					// echo "<pre>";print_r($field);echo "</pre>";
					?>
					<div class="simple-fields-metabox-field-custom-field-key hidden highlight"><strong><?php _e('Meta key:', 'simple-fields') ?></strong> <?php echo $custom_field_key ?></div>
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
	
		// Add meta box to post
		global $post, $sf;
	
		if ($post) {
	
			$post_type = $post->post_type;
			$arr_post_types = $sf->get_post_connector_attached_types();
			
			// check if the post type being edited is among the post types we want to add boxes for
			if (in_array($post_type, $arr_post_types)) {
				
				// general meta box to select fields for the post
				add_meta_box('simple-fields-post-edit-side-field-settings', 'Simple Fields', 'simple_fields_edit_post_side_field_settings', $post_type, 'side', 'low');
				
				$connector_to_use = simple_fields_get_selected_connector_for_post($post);
				
				// get connector to use for this post
				$post_connectors = $sf->get_post_connectors();
				if (isset($post_connectors[$connector_to_use])) {
					
					$field_groups = $sf->get_field_groups();
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
	 
	    $field_groups = get_option("simple_fields_groups");
	    $current_field_group = $field_groups[$post_connector_field_id];
	 
	    echo "<div class='simple-fields-meta-box-field-group-wrapper'>";
	    echo "<input type='hidden' name='simple-fields-meta-box-field-group-id' value='$post_connector_field_id' />";
	 
	    // show description
	    if (!empty($current_field_group["description"])) {
	        printf("<p class='%s'>%s</p>", "simple-fields-meta-box-field-group-description", esc_html($current_field_group["description"]));
	    }
	    //echo "<pre>";print_r($current_field_group);echo "</pre>";
	 
	    if ($current_field_group["repeatable"]) {
	 
	        echo "
	            <div class='simple-fields-metabox-field-add'>
	                <a href='#'>+ ".__('Add', 'simple-fields')."</a>
	            </div>
	        ";
	        echo "<ul class='simple-fields-metabox-field-group-fields simple-fields-metabox-field-group-fields-repeatable'>";
	 
	        // check for prev. saved fieldgroups
	        // _simple_fields_fieldGroupID_1_fieldID_added_numInSet_0
	        // try until returns empty
	        $num_added_field_groups = 0;
	 
	        while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$post_connector_field_id}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
	            $num_added_field_groups++;
	        }
	        //var_dump( get_post_meta($post_id, "_simple_fields_fieldGroupID_{$post_connector_field_id}_fieldID_added_numInSet_0", true) );
	        //echo "num_added_field_groups: $num_added_field_groups";
	        // now add them. ooooh my, this is fancy stuff.
	        $use_defaults = null;
	        for ($num_in_set=0; $num_in_set<$num_added_field_groups; $num_in_set++) {
	            $sf->meta_box_output_one_field_group($post_connector_field_id, $num_in_set, $post_id, $use_defaults);  
	        }
	 
	        echo "</ul>";
	 
	    } else {
	         
	        // is this a new post, ie. should default values be used
	        $been_saved = (bool) get_post_meta($post_id, "_simple_fields_been_saved", true);
	        if ($been_saved) { $use_defaults = false; } else { $use_defaults = true; }
	         
	        echo "<ul>";
	        $sf->meta_box_output_one_field_group($post_connector_field_id, 0, $post_id, $use_defaults);
	        echo "</ul>";
	 
	    }
	     
	    echo "</div>";
	 
	} // end

	/**
	 * Returns all defined post connectors
	 * @return array
	 */
	function get_post_connectors() {
		$connectors = get_option("simple_fields_post_connectors");
		if ($connectors === FALSE) $connectors = array();
	
		// calculate number of active field groups
		// @todo: check this a bit more, does not seem to be any deleted groups. i thought i saved the deletes ones to, but with deleted flag set
		foreach ($connectors as $one_connector) {
			$num_fields_in_group = 0;
			foreach ($one_connector["field_groups"] as $one_group) {
				if (!$one_group["deleted"]) $num_fields_in_group++;
			}
			$connectors[$one_connector["id"]]["field_groups_count"] = $num_fields_in_group;
		}
	
		return $connectors;
	}
	
	/**
	 * Returns all defined field groups
	 *
	 * @return array
	 */
	function get_field_groups() {
		$field_groups = get_option("simple_fields_groups");
		if ($field_groups === FALSE) $field_groups = array();
		
		// Calculate the number of active fields
		foreach ($field_groups as & $one_group) {
			$num_active_fields = 0;
			foreach ($one_group["fields"] as $one_field) {
				if (!$one_field["deleted"]) $num_active_fields++;
			}
			$one_group["fields_count"] = $num_active_fields;
		}
		
		return $field_groups;
	}


} // end class

// Boot it up!
$sf = new simple_fields();
$sf->init();
