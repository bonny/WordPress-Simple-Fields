<?php

// now lets get that file dialog working!
add_filter( 'media_send_to_editor', 'simple_fields_media_send_to_editor', 15, 2 );
add_filter( 'media_upload_tabs', 'simple_fields_media_upload_tabs', 15);
add_filter( 'media_upload_form_url', 'simple_fields_media_upload_form_url');
add_filter( 'attachment_fields_to_edit', 'simple_fields_attachment_fields_to_edit', 10, 2 );
add_action( 'admin_head', 'simple_fields_admin_head_select_file' );
add_action( 'admin_footer', 'simple_fields_admin_footer' );
add_action( 'admin_init', 'simple_fields_post_admin_init' );
add_action( 'dbx_post_sidebar', 'simple_fields_post_dbx_post_sidebar' );

// little debug version
function sf_d($var) {
	echo "<pre>";
	if (is_array($var) || is_object($var)) {
		print_r($var);
	} else {
		echo $var;
	}
	echo "</pre>";
}

/**
 * Fetch content for post type dialog via AJAX
 */
add_action('wp_ajax_simple_fields_field_type_post_dialog_load', 'simple_fields_field_type_post_dialog_load');
function simple_fields_field_type_post_dialog_load() {
	//echo "<pre>";print_r($_POST); 
	/*
	Array
	(
	    [action] => simple_fields_field_type_post_dialog_load
	    [arr_enabled_post_types] => Array
	        (
	            [0] => post
	            [1] => page
	            [2] => feedback
	        )
	
	)
	*/
	$arr_enabled_post_types = (array) $_POST["arr_enabled_post_types"];
	$existing_post_types = get_post_types(NULL, "objects");
	$selected_post_type = (string) @$_POST["selected_post_type"];
	?>
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
	
	<div class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts-wrap">
		<ul class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts">
			<?php
			/*
			$args = array(
				"post_type" => $selected_post_type,
				"numberposts" => -1
			);
			$posts = get_posts($args);
			echo "<ul>";
			foreach ($posts as $post) {
				printf("<li>%s</li>", $post->post_title);
			}
			echo "</ul>";
			*/
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
		
			$output = simple_fields_get_pages($args);
			echo $output;
			?>
		</ul>
	</div>
	<div class="submitbox">
		<div class="simple-fields-postdialog-link-cancel">
			<a href="#" class="submitdelete deletion">Cancel</a>
		</div>
		<!--
		<div class="simple-fields-postdialog-link-update">
			<input type="submit" tabindex="100" value="Add Link" class="button-primary" class="wp-link-submit" name="wp-link-submit">
		</div>
		-->
	</div>
	<?php
		
	exit;
}

/**
 * Output HTML for dialog in bottom
 */
function simple_fields_admin_footer() {
	// HTML for post dialog
	?><div class="simple-fields-meta-box-field-group-field-type-post-dialog hidden"></div><?php
}

/**
 * output nonce
 */
function simple_fields_post_dbx_post_sidebar() {
	?>
	<input type="hidden" name="simple_fields_nonce" id="simple_fields_nonce" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>" />
	<?php
}

/**
 * Change "insert into post" to something better
 * Code inspired by/gracefully stolen from
 * http://mondaybynoon.com/2010/10/12/attachments-1-5/#comment-27524
 */
function simple_fields_post_admin_init() {
	if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
		add_filter('gettext', 'simple_fields_hijack_thickbox_text', 1, 3);
	}
}
function simple_fields_hijack_thickbox_text($translated_text, $source_text, $domain) {
	if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
		if ('Insert into Post' == $source_text) {
			return __('Select', 'simple_fields' );
		}
	}
	return $translated_text;
}


/*
	hide some stuff in the file browser
*/
function simple_fields_admin_head_select_file() {
	if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
		?>
		<style type="text/css">
			.wp-post-thumbnail,
			tr.image_alt,
			tr.post_title,
			tr.align,
			tr.image-size
			 {
				display: none;
			}
	
		</style>
		<?php
	}
}

// remove some fields in the file select dialogue, since simple fields don't use them anyway
function simple_fields_attachment_fields_to_edit($form_fields, $post) {
	if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
		unset(
			$form_fields["post_excerpt"],
			$form_fields["post_content"],
			$form_fields["url"],
			$form_fields["image_url"],
			$form_fields["image_alt"],
			$form_fields["menu_order"]
		);
		#bonny_d($form_fields);
	}
	return $form_fields;
}

// if we have simple fields args in GET, make sure our simple fields-stuff are added to the form
function simple_fields_media_upload_form_url($url) {
	// $url:
	// http://localhost/wp-admin/media-upload.php?type=file&tab=library&post_id=0
	/*
	Array
	(
	    [simple_fields_dummy] => 1
	    [simple_fields_action] => select_file
	    [simple_fields_file_field_unique_id] => simple_fields_fieldgroups_8_4_0
	    [tab] => library
	)
	*/
	foreach ($_GET as $key => $val) {
		if (strpos($key, "simple_fields_") === 0) {
			$url = add_query_arg($key, $val, $url);
		}
	}
	return $url;
}

// remove gallery and remote url tab in file select
function simple_fields_media_upload_tabs($arr_tabs) {
	if ( (isset($_GET["simple_fields_action"]) || isset($_GET["simple_fields_action"]) ) && ($_GET["simple_fields_action"] == "select_file" || $_GET["simple_fields_action"] == "select_file_for_tiny") ) {
		unset($arr_tabs["gallery"], $arr_tabs["type_url"]);
	}
	return $arr_tabs;
}

// send the selected file to simple fields
function simple_fields_media_send_to_editor($html, $id) {
	/*
	post_id	1060, -1 since dda17 October, 2
	tab	library
	type	file
	
	POST
	_wp_http_referer=/wp-admin/media-upload.php?simple_fields_action=select_file&simple_fields_file_field_unique_id=simple_fields_fieldgroups_8_4_new0&tab=library
	*/
	parse_str($_POST["_wp_http_referer"], $arr_postinfo);
	#bonny_d($arr_url);
	/*
	Array
	(
	    [/wp-admin/media-upload_php?simple_fields_dummy] => 1
	    [simple_fields_action] => select_file
	    [simple_fields_file_field_unique_id] => simple_fields_fieldgroups_8_4_new1
	    [tab] => library
	)
	*/
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
		<script type="text/javascript">
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

add_action('save_post', 'simple_fields_save_postdata');
function simple_fields_save_postdata($post_id = null, $post = null) {

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

}


/**
 * adds a fieldgroup through ajax = also fetch defaults
 */
function simple_fields_metabox_fieldgroup_add() {

	$simple_fields_new_fields_count = (int) $_POST["simple_fields_new_fields_count"];
	$post_id = (int) $_POST["post_id"];
	$field_group_id = (int) $_POST["field_group_id"];

	$num_in_set = "new{$simple_fields_new_fields_count}";
	simple_fields_meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, true);

	exit;
}
add_action('wp_ajax_simple_fields_metabox_fieldgroup_add', 'simple_fields_metabox_fieldgroup_add');


/**
 * print out fields for a meta box
 */
function simple_fields_meta_box_output($post_connector_field_id, $post_id) {

	// if not repeatable, just print it out
	// if repeatable: only print out the ones that have a value
	// and + add-button

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
			simple_fields_meta_box_output_one_field_group($post_connector_field_id, $num_in_set, $post_id, $use_defaults);	
		}

		echo "</ul>";

	} else {
		
		// is this a new post, ie. should default values be used
		$been_saved = (bool) get_post_meta($post_id, "_simple_fields_been_saved", true);
		if ($been_saved) { $use_defaults = false; } else { $use_defaults = true; }
		
		echo "<ul>";
		simple_fields_meta_box_output_one_field_group($post_connector_field_id, 0, $post_id, $use_defaults);
		echo "</ul>";

	}
	
	echo "</div>";

}

/**
 * output the html for a field group in the meta box
 */
function simple_fields_meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, $use_defaults) {

	$post = get_post($post_id);
	
	$field_groups = get_option("simple_fields_groups");
	$current_field_group = $field_groups[$field_group_id];
	$repeatable = (bool) $current_field_group["repeatable"];
	?>
	<li class="simple-fields-metabox-field-group">
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
				
		foreach ($current_field_group["fields"] as $field) {
			
			if ($field["deleted"]) { continue; }
			
			$field_id = $field["id"];
			$field_unique_id = "simple_fields_fieldgroups_{$field_group_id}_{$field_id}_{$num_in_set}";
			$field_name = "simple_fields_fieldgroups[$field_group_id][$field_id][$num_in_set]";

			$custom_field_key = "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}";
			$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist
			
			$description = "";
			if (!empty($field["description"])) {
				$description = sprintf("<div class='simple-fields-metabox-field-description'>%s</div>", esc_html($field["description"]));
			}
			
			// echo "<pre>";print_r($field);echo "</pre>";
			
			?>
			<div class="simple-fields-metabox-field">
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
							$file_url = "media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=$field_unique_id_esc&post_id=-1&TB_iframe=true";
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
					if (isset($textarea_options["use_html_editor"])) {
						$textarea_class = "simple-fields-metabox-field-textarea-tinymce";
						$textarea_class_wrapper = "simple-fields-metabox-field-textarea-tinymce-wrapper";
					}

					echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
					echo $description;

					// tiny-insert-media-buttons
					
					if (isset($textarea_options["use_html_editor"])) {

						// switch html/tinymce
						echo "<div class='simple_fields_editor_switch'>".__('View', 'simple-fields')." <a class='selected simple_fields_editor_switch_visual' href='#'>".__('Visual', 'simple-fields')."</a> <a href='#' class='simple_fields_editor_switch_html'>".__('HTML', 'simple-fields')."</a></div>";

						if ( current_user_can( 'upload_files' ) )

						$media = "<div class='simple-fields-metabox-field-textarea-tinymce-media'>";
						$media .= __("Upload/Insert", "simple-fields");
						
						$media_upload_iframe_src = "media-upload.php";

						// from media.php
						$do_image = $do_audio = $do_video = true;
						if ( is_multisite() ) {
							$media_buttons = get_site_option( 'mu_media_buttons' );
							if ( empty($media_buttons['image']) )
								$do_image = false;
							if ( empty($media_buttons['audio']) )
								$do_audio = false;
							if ( empty($media_buttons['video']) )
								$do_video = false;
						}
						// end

						if ($do_image) {
							$image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src?type=image");
							$image_title = __('Add an Image');
							$media .= "<a title='$image_title' class='simple_fields_tiny_media_button' href=\"{$image_upload_iframe_src}&amp;post_id={$post_id}&amp;simple_fields_action=select_file_for_tiny&amp;TB_iframe=true\"><img src='images/media-button-image.gif' alt='' /></a> ";
						}
						
						if ($do_video) {
							$video_upload_iframe_src = apply_filters('video_upload_iframe_src', "$media_upload_iframe_src?type=video");
							$video_title = __('Add Video');	
							$media .= "<a class='simple_fields_tiny_media_button' href=\"{$video_upload_iframe_src}&amp;post_id={$post_id}&amp;simple_fields_action=select_file_for_tiny&amp;TB_iframe=true\" title='$video_title'><img src='images/media-button-video.gif' alt='$video_title' /></a> ";
						}
					
						if ($do_audio) {
							$audio_upload_iframe_src = apply_filters('audio_upload_iframe_src', "$media_upload_iframe_src?type=audio");
							$audio_title = __('Add Audio');
							$media .= "<a class='simple_fields_tiny_media_button' href=\"{$audio_upload_iframe_src}&amp;post_id={$post_id}&amp;simple_fields_action=select_file_for_tiny&amp;TB_iframe=true\" title='$audio_title'><img src='images/media-button-music.gif' alt='$audio_title' /></a> ";
						}
					
						$media_title = __('Add Media');
						$media .= "<a class='simple_fields_tiny_media_button' href=\"{$media_upload_iframe_src}?post_id={$post_id}&amp;simple_fields_action=select_file_for_tiny&amp;TB_iframe=true\" title='$media_title'><img src='images/media-button-other.gif' alt='$media_title' /></a>";
						
						$media .= "</div>";

						echo $media;
					
					}

					echo "<div class='$textarea_class_wrapper'>";
					echo "<textarea class='$textarea_class' name='$field_name' id='$field_unique_id' cols='50' rows='5'>$textarea_value_esc</textarea>";
					echo "</div>";
	
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

					// echo "saved_value:";sf_d($saved_value);

					// hämta alla terms som finns för taxonomy $enabled_taxonomy
					// @todo: kunna skicka in args här, t.ex. för orderby
					
					// check if taxonomy is hierachical
					// _get_term_hierarchy($taxonomy) {
					/*
					$is_hierarchical = is_taxonomy_hierarchical($enabled_taxonomy);
					if ( $is_hierarchical ) {
						// echo "<br>is hierarchical";
						$existing_terms = _get_term_hierarchy($enabled_taxonomy);
					} else {
						// echo "<br>is not hierarchical";
						$existing_terms = get_terms($enabled_taxonomy, "&{$additional_arguments}");
					}
					*/

					echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
					echo $description;

/*
wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
<?php wp_terms_checklist($post_ID, array( 'taxonomy' => 'category', 'popular_cats' => $popular_ids ) ) ?>
xxx
wp_terms_checklist
*/
$arr_selected_cats = (array) $saved_value;

$walker = new Walker_Category_Checklist2();
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
					
					//echo "<pre>terms:";print_r($existing_terms);echo "</pre>";
					
				} elseif ("post" == $field["type"]) {
					
					$saved_value_int = (int) $saved_value;
					if ($saved_value_int) {
						//$saved_post = get_post($saved_value_int);
						$saved_post_name = get_the_title($saved_value_int);
						$showHideClass = "";
					} else {
						$saved_post_name = "";
						$showHideClass = "hidden";
					}
					
					$type_post_options = (array) @$field["type_post_options"];
					$enabled_post_types = $type_post_options["enabled_post_types"];
					
					echo "<div class='simple-fields-metabox-field-post'>";
					// echo "<pre>"; print_r($type_post_options); echo "</pre>";
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
}



#add_filter( "media_send_to_editor", "simple_fields_media_send_to_editor", 15 );
/*
function simple_fields_media_send_to_editor($html) {
	// runs for both simple fields and regular insert media
	$html = addslashes($html);
	?>
	<script type="text/javascript">
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_custom_field("<?php echo $html ?>");
	</script>
	<?
}
*/

/**
 * Head of admin area
 * - Add meta box with info about currently selected connector + options to choose another one
 * - Add meta boxes with field groups
 */
function simple_fields_admin_head() {

	// Add meta box to post
	global $post;

	if ($post) {

		$post_type = $post->post_type;
		$arr_post_types = simple_fields_post_connector_attached_types();
		
		// check if the post type being edited is among the post types we want to add boxes for
		if (in_array($post_type, $arr_post_types)) {
			
			// general meta box to select fields for the post
			add_meta_box('simple-fields-post-edit-side-field-settings', 'Simple Fields', 'simple_fields_edit_post_side_field_settings', $post_type, 'side', 'low');
			
			$connector_to_use = simple_fields_get_selected_connector_for_post($post);
			
			// get connector to use for this post
			$post_connectors = simple_fields_get_post_connectors();
			if (isset($post_connectors[$connector_to_use])) {
				
				//$field_groups = get_option("simple_fields_groups");
				$field_groups = simple_fields_get_field_groups();
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
						$meta_box_callback = create_function ("", " simple_fields_meta_box_output({$one_post_connector_field_group["id"]}, $post->ID); ");
						
						add_meta_box( $meta_box_id, $meta_box_title, $meta_box_callback, $post_type, $meta_box_context, $meta_box_priority );
						
					}
					
				}
			}
			
		}
	}
	
}


/**
 * get selected post connector for a post
 */
function simple_fields_get_selected_connector_for_post($post) {
	/*
	om sparad connector finns för denna artikel, använd den
	om inte sparad connector, använd default
	om sparad eller default = inherit, leta upp connector för parent post

	$post->ID
	$post->post_type
	*/
	#d($post);
	$post_type = $post->post_type;
	$connector_to_use = null;
	if (!$post->ID) {
		// no id (new post), use default for post type
		// @todo: can this happen in wp3 btw? all new posts are assigned id
		$connector_to_use = simple_fields_get_default_connector_for_post_type($post_type);
	} elseif ($post->ID) {
		// get saved connector for post
		$connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
		#var_dump($connector_to_use);
		if ($connector_to_use == "") {
			// no previous post connector saved, use default for post type
			$connector_to_use = simple_fields_get_default_connector_for_post_type($post_type);
		}
	}
	
	// $connector_to_use is now a id or __none__ or __inherit__

	// if __inherit__, get connector from post_parent
	if ("__inherit__" == $connector_to_use && $post->post_parent > 0) {
		$parent_post_id = $post->post_parent;
		$parent_post = get_post($parent_post_id);
		$connector_to_use = simple_fields_get_selected_connector_for_post($parent_post);
	} elseif ("__inherit__" == $connector_to_use && 0 == $post->post_parent) {
		// already at the top, so inherit should mean... __none__..? right?
		// hm.. no.. then the wrong value is selected in the drop down.. hm...
		#$connector_to_use = "__none__";
	}
	
	// if selected connector is deleted, then return none
	$post_connectors = simple_fields_get_post_connectors();
	if (isset($post_connectors[$connector_to_use]["deleted"]) && $post_connectors[$connector_to_use]["deleted"]) {
		$connector_to_use = "__none__";
	}
	
	return $connector_to_use;

}


/**
 * get default connector for a post type
 * if no connector has been set, __none__ is returned
 * @param string $post_type
 * @return mixed int connector id or string __none__ or __inherit__
 */
function simple_fields_get_default_connector_for_post_type($post_type) {
	$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
	$selected_post_type_default = (isset($post_type_defaults[$post_type]) ? $post_type_defaults[$post_type] : "__none__");
	return $selected_post_type_default;
}


/**
 * meta box in sidebar in post edit screen
 * let user select post connector to use for current post
 */
function simple_fields_edit_post_side_field_settings() {
	
	global $post;
	
	$arr_connectors = simple_fields_get_post_connectors_for_post_type($post->post_type);
	$connector_default = simple_fields_get_default_connector_for_post_type($post->post_type);
	$connector_selected = simple_fields_get_selected_connector_for_post($post);

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
		$parent_selected_connector = simple_fields_get_selected_connector_for_post($post_parent);
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
}

if (!function_exists("bonny_d")) {
	function bonny_d($s) {
		echo "<pre>"; print_r($s); echo "</pre>";
	}
}



/**
 * get all values or just the from a field in a field group
 * @param $post_id
 * @param $field_name_or_id name as string or field group id and field id as array. 
 * 		  for example array(3,2) to fetch field 2 from field group 3
 * @param $single bool return a single (the first) value or all values (as array)
 * @return string or array
 */
function simple_fields_get_post_value($post_id, $field_name_or_id, $single = true) {

	$fetch_by_id = true;
	if (is_array($field_name_or_id) && sizeof($field_name_or_id) == 2) {
		$field_group_id = $field_name_or_id[0];
		$field_id = $field_name_or_id[1];
		$fetch_by_id = false;
	}
	$connector = simple_fields_get_all_fields_and_values_for_post($post_id);
	$return_val = null;
	if ($connector) {
		foreach ($connector["field_groups"] as $one_field_group) {
			$is_found = false;
			foreach ($one_field_group["fields"] as $one_field) {
				if ($fetch_by_id && $one_field["name"] == $field_name_or_id) {
					// we got our field, get the value(s)
					$is_found = true;
				} else if (($one_field_group["id"] == $field_group_id) && ($one_field["id"] == $field_id)) {
					$is_found = true;
				}
	
				$saved_values = $one_field["saved_values"];
	
				if ($one_field["type"] == "radiobuttons" || $one_field["type"] == "dropdown") {
					if ($one_field["type"] == "radiobuttons") {
						$get_value_key = "type_radiobuttons_options";
					} else if ($one_field["type"] == "dropdown") {
						$get_value_key = "type_dropdown_options";
					}
					// if radiobutton or dropdown, get value from type_dropdown_options[<saved value>][value]
					// for each saved value, get value from type_dropdown_options[<saved value>]
					for ($saved_i = 0; $saved_i < sizeof($saved_values); $saved_i++) {
						$saved_values[$saved_i] = $one_field[$get_value_key][$saved_values[$saved_i]]["value"];
					}
				}
				
				if ($is_found && $single) {
					$return_val = $saved_values[0];
				} else if ($is_found) {
					$return_val = $saved_values;
				}
	
				if ($is_found) {
					return $return_val;
				}
	
	
			}
		}
	}
	return; // oh no! nothing found. bummer.
}

/**
 * get all values from a field group
 * @param int $post_id
 * @param name or ir $field_group_name_or_id
 * @param bool use_name return array with names or id as key
 * @param int $return_format 1|2
 * @return array
 */
function simple_fields_get_post_group_values($post_id, $field_group_name_or_id, $use_name = true, $return_format = 1) {

	$fetch_by_id = true;
	if (is_int($field_group_name_or_id)) {
		$fetch_by_id = true;
	}
	$connector = simple_fields_get_all_fields_and_values_for_post($post_id);

	if (!$connector) {
		return array();
	}

	foreach ($connector["field_groups"] as $one_field_group) {

		$is_found = false;
		if ($fetch_by_id && $one_field_group["id"] == $field_group_name_or_id) {
			$is_found = true;
		} else if ($field_group_name_or_id == $one_field_group["name"]) {
			$is_found = true;
		}

		if ($is_found) {
			$arr_return = array();
			foreach ($one_field_group["fields"] as $one_field) {
			
				$saved_values = $one_field["saved_values"];

				if (is_null($saved_values)) {
					// no saved values. just continue?
					continue;
				}

				if ($one_field["type"] == "radiobuttons" || $one_field["type"] == "dropdown") {
					if ($one_field["type"] == "radiobuttons") {
						$get_value_key = "type_radiobuttons_options";
					} else if ($one_field["type"] == "dropdown") {
						$get_value_key = "type_dropdown_options";
					}
					// if radiobutton or dropdown, get value from type_dropdown_options[<saved value>][value]
					// for each saved value, get value from type_dropdown_options[<saved value>]
					for ($saved_i = 0; $saved_i < sizeof($saved_values); $saved_i++) {
						$saved_values[$saved_i] = $one_field[$get_value_key][$saved_values[$saved_i]]["value"];
					}
				}

				if ($use_name) {
					$arr_return[$one_field["name"]] = $saved_values;
				} else {
					$arr_return[$one_field["id"]] = $saved_values;
				}
			}
			
			$set_count = sizeof($one_field["saved_values"]);
			
			$arr_return2 = array();
			for ($i=0; $i<$set_count; $i++) {
				$arr_return2[$i] = array();
				foreach ($arr_return as $key => $val) {
					$arr_return2[$i][$key] = $val[$i];
				}
			}
			if ($return_format == 1) {
				return $arr_return;
			} elseif ($return_format == 2) {
				return $arr_return2;
			}
		}
	}
	

}

/**
 * fetch all information about the field group that a post has
 * returns connector structure, field groups, fields, and values
 * well.. everything! it's really funky.
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id) {
	$post = get_post($post_id);
	$connector_to_use = simple_fields_get_selected_connector_for_post($post);
	$existing_post_connectors = simple_fields_get_post_connectors();
	$field_groups = get_option("simple_fields_groups");
	$selected_post_connector = $existing_post_connectors[$connector_to_use];
	if($selected_post_connector == null) {
		return false;
	}
	foreach ($selected_post_connector["field_groups"] as $one_field_group) { // one_field_group = name, deleted, context, priority, id
	
		// now get all fields for that fieldgroup and join them together
		$selected_post_connector["field_groups"][$one_field_group["id"]] = array_merge($selected_post_connector["field_groups"][$one_field_group["id"]], $field_groups[$one_field_group["id"]]);
	
		// loop through all fields within this field group
		// now find out how many times this field group has been added
		// can be zero, 1 och several (if field group is repeatable)
	
		$num_added_field_groups = 0;
		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}
		
		// now fetch the stored values, one field at a time
		for ($num_in_set = 0; $num_in_set < $num_added_field_groups; $num_in_set++) {
			// fetch value for each field
			foreach ($selected_post_connector["field_groups"][$one_field_group["id"]]["fields"] as $one_field_id => $one_field_value) {

				$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";	
				$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist

				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["saved_values"][$num_in_set] = $saved_value;
				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["meta_keys"][$num_in_set] = $custom_field_key;

			}
		}
		
	}
	return $selected_post_connector;
}
# $custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group_id}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";

/**
 * Code from Admin Menu Tree Page View
 */
function simple_fields_get_pages($args) {

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
		$str_child_output = simple_fields_get_pages($args_childs);
		
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

class Walker_Category_Checklist2 extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth, $args) {
		
		global $simple_fields_taxonomyterm_walker_field_name;
				
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		// @todo: use custom simple fields name for all inputs
		$name = $simple_fields_taxonomyterm_walker_field_name;
		/*
		if ( $taxonomy == 'category' ) {
			$name = 'post_category';
		} else {
			$name = 'tax_input['.$taxonomy.']';
		}
		*/

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		//$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
		$output .= "\n<li $class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" ' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}
