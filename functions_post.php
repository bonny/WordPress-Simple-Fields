<?php


// little debug version
function sf_d($var) {

	echo "<pre>";
	if (is_array($var) || is_object($var)) {
		print_r($var);
	} else if( is_null($var) ) {
		echo "Var is NULL";
	} else {
		echo $var;
	}
	echo "</pre>";
}

/**
 * Field type: post
 * Fetch content for field type post dialog via AJAX
 * Used for field type post
 * Called from ajax with action wp_ajax_simple_fields_field_type_post_dialog_load
 * Ajax defined in scripts.js -> $("a.simple-fields-metabox-field-post-select")
 */
function simple_fields_field_type_post_dialog_load() {

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
		
			$output = simple_fields_get_pages($args);
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
 * In file dialog:
 * Change "insert into post" to something better
 * 
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




/**
 * if we have simple fields args in GET, make sure our simple fields-stuff are added to the form
 */
function simple_fields_media_upload_form_url($url) {

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
function simple_fields_media_upload_tabs($arr_tabs) {

	if ( (isset($_GET["simple_fields_action"]) || isset($_GET["simple_fields_action"]) ) && ($_GET["simple_fields_action"] == "select_file" || $_GET["simple_fields_action"] == "select_file_for_tiny") ) {
		unset($arr_tabs["gallery"], $arr_tabs["type_url"]);
	}

	return $arr_tabs;
}

/**
 * used from file selector popup
 * send the selected file to simple fields
 */
function simple_fields_media_send_to_editor($html, $id) {

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
	global $sf;
	
	$post_type = $post->post_type;
	$connector_to_use = null;
	if (!$post->ID) {
		// no id (new post), use default for post type
		$connector_to_use = $sf->get_default_connector_for_post_type($post_type);
	} elseif ($post->ID) {
		// get saved connector for post
		$connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
		#var_dump($connector_to_use);
		if ($connector_to_use == "") {
			// no previous post connector saved, use default for post type
			$connector_to_use = $sf->get_default_connector_for_post_type($post_type);
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
	$post_connectors = $sf->get_post_connectors();
	if (isset($post_connectors[$connector_to_use]["deleted"]) && $post_connectors[$connector_to_use]["deleted"]) {
		$connector_to_use = "__none__";
	}
	
	return $connector_to_use;

}


/**
 * meta box in sidebar in post edit screen
 * let user select post connector to use for current post
 */
function simple_fields_edit_post_side_field_settings() {
	
	global $post, $sf;
	
	$arr_connectors = simple_fields_get_post_connectors_for_post_type($post->post_type);
	$connector_default = $sf->get_default_connector_for_post_type($post->post_type);
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
} // function admin_head

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
				} else if (!$fetch_by_id && ($one_field_group["id"] === $field_group_id) && ($one_field["id"] === $field_id)) {
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
	echo "</pre>";
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

	$fetch_by_id = false;
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
		} else if (!$fetch_by_id && $field_group_name_or_id == $one_field_group["name"]) {
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
 *
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id) {
	
	global $sf;
	
	$post = get_post($post_id);
	$connector_to_use = simple_fields_get_selected_connector_for_post($post);
	$existing_post_connectors = $sf->get_post_connectors();
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
		// can be zero, 1 and several (if field group is repeatable)
	
		$num_added_field_groups = 0;
		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}
		
		// now fetch the stored values, one field at a time
		for ($num_in_set = 0; $num_in_set < $num_added_field_groups; $num_in_set++) {
			/* echo "<pre>";
			var_dump($one_field_group["id"]);
			var_dump(array_keys($selected_post_connector["field_groups"]));
			var_dump($selected_post_connector["field_groups"]);
			var_dump($selected_post_connector["field_groups"][$one_field_group["id"]]);
			*/
			// fetch value for each field
			foreach ($selected_post_connector["field_groups"][$one_field_group["id"]]["fields"] as $one_field_id => $one_field_value) {

				$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";
				
				$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist

				if ($one_field_value["type"] == "textarea") {
					$match_count = preg_match_all('/http:\/\/[a-z0-9A-Z\.]+[a-z0-9A-Z\.\/%&=\?\-_#]+/i', $saved_value, $match);
					if ($match_count) {
						$links=$match[0];
						for ($j=0;$j<$match_count;$j++) {
							if (strpos($saved_value, 'href="'.$links[$j].'"') === false && strpos($saved_value, "href='".$links[$j]."'") === false) {
								$attr['discover'] = (apply_filters('embed_oembed_discover', false)) ? true : false;
								$oembed_html = wp_oembed_get($links[$j], $attr);
								// If there was a result, oembed the link
								if ($oembed_html) {
									$saved_value = str_replace($links[$j], apply_filters('embed_oembed_html', $oembed_html, $links[$j], $attr), $saved_value);
								}
							}
						}
					}
				}
				
				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["saved_values"][$num_in_set] = $saved_value;
				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["meta_keys"][$num_in_set] = $custom_field_key;

			}
		}
		
	}
	return $selected_post_connector;
}

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

class Simple_Fields_Walker_Category_Checklist extends Walker {
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

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li $class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" ' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}

/**
 * @todo: add description
 */
function simple_fields_get_field_group($group_id) {
	$field_groups = get_option("simple_fields_groups");
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
 * @todo: add description
 */
function simple_fields_get_field_in_group($field_group, $field_id) {
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

// Returns an array for merging with WP_Query() arguments.
// TODO: A variable in simple_fields_groups that keeps track of the most number
// of times a field has been repeated on any single post so that $num_in_set can
// be determined dynamically.
function simple_fields_get_meta_query($group_id, $field_id, $value, $compare = "=", $type = "CHAR", $order = "", $num_in_set = 1) {
	$field_group = simple_fields_get_field_group($group_id);
	$field = simple_fields_get_field_in_group($field_group, $field_id);
	if (!is_array($field_group) || !is_array($field)) {
		return false;
	}
	if(!is_numeric($num_in_set) || $num_in_set < 1) {
		$num_in_set = 1;
	}
	if ($field["type"] == "radiobuttons") {
		$get_value_key = "type_radiobuttons_options";
	} else if ($field["type"] == "dropdown") {
		$get_value_key = "type_dropdown_options";
	}
	if (!empty($get_value_key) && is_array($field[$get_value_key])) {
		foreach($field[$get_value_key] as $option_key => $option) {
			if ($option['value'] == $value && (!isset($option['deleted']) || intval($option['deleted']) == 0)) {
				$value = $option_key;
			}
		}
	}
	$query_args = array('meta_query' => array('relation' => 'OR'));
	for($i=0;$i<$num_in_set;$i++) {
		$query_args['meta_query'][$i]['key'] = "_simple_fields_fieldGroupID_{$field_group['id']}_fieldID_{$field['id']}_numInSet_{$i}";
		$query_args['meta_query'][$i]['value'] = $value;
		$query_args['meta_query'][$i]['compare'] = $compare;
		$query_args['meta_query'][$i]['type'] = $type;
	}
	if ($order == "ASC" || $order == "DESC") {
		$query_args['meta_key'] = $query_args['meta_query'][0]['key'];
		$query_args['orderby'] = 'meta_value';
		$query_args['order'] = $order;
	}
	return $query_args;
}

// Extends args for WP_Query() with simple fields meta query args
// and returns query result object
function simple_fields_query_posts($query_args = array()) {
	foreach($query_args as $key => $val) {
		switch($key) {
			case "sf_group":
			case "sf_field":
			case "sf_value":
				if(empty($val))
					return false;
				break;
			case "sf_compare":
				if(empty($val))
					$query_args[$key] = "=";
				break;
			case "sf_type":
				if(empty($val))
					$query_args[$key] = "CHAR";
				break;
			case "sf_order":
				if($val != "ASC" && $val != "DESC")
					$query_args[$key] = "";
				break;
			case "sf_num_in_set":
				if(!is_numeric($val) || $val < 1)
					$query_args[$key] = 1;
				break;
		}
	}
	$meta_query_args = simple_fields_get_meta_query($query_args['sf_group'], $query_args['sf_field'], $query_args['sf_value'], $query_args['sf_compare'], $query_args['sf_type'], $query_args['sf_order'], $query_args['sf_num_in_set']);
	$query_args = array_merge($query_args, $meta_query_args);
	return new WP_Query($query_args);
}
