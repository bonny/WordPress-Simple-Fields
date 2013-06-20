<?php

/**
 * Functions that should be available outside the simple fields class
 */

/**
 * Quicky debug a variable
 *
 * @param mixed $var the variable to output
 * @param string $heading Optional heading/description of what you're debugging
 * @return echo output
 */
if (!function_exists("sf_d")) {
function sf_d($var, $heading = "") {
	$out = "";
	$out .= "\n<pre class='sf_box_debug'>\n";
	if ($heading && ! empty($heading)) {
		$out .= "<b>" . esc_html($heading) . ":</b>\n";
	}
	if (is_array($var) || is_object($var)) {
		$out .= htmlspecialchars( print_r($var, true), ENT_QUOTES, 'UTF-8' );
	} else if( is_null($var) ) {
		$out .= "Var is NULL";
	} else if ( is_bool($var)) {
		$out .= "Var is BOOLEAN ";
		$out .= $var ? "TRUE" : "FALSE";
	} else {
		$out .= htmlspecialchars( $var, ENT_QUOTES, 'UTF-8' );
	}
	$out .= "\n</pre>";
	echo apply_filters( "simple_fields_debug_output", $out );
}
}


/**
 * get values from a field in a field group
 * deprecated, use simple_fields_value or simple_fields_values
 *
 * @param $post_id
 * @param $field_name_or_id name as string or field group id and field id as array. 
 * 		  for example array(3,2) to fetch field 2 from field group 3
 * @param $single bool return a single (the first) value or all values (as array)
 * @return string or array
 */
function simple_fields_get_post_value($post_id, $field_name_or_id, $single = true) {

	global $sf;

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
					$is_found = true;
				} else if (!$fetch_by_id && (intval($one_field_group["id"]) === intval($field_group_id)) && (intval($one_field["id"]) === intval($field_id))) {
					$is_found = true;
				}
	
				$saved_values = isset($one_field["saved_values"]) ? $one_field["saved_values"] : null;

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

				// check for settings saved for the field (in gui or through register_field_group)
				$parsed_options_for_this_field = array();
				$field_options_key = "type_".$one_field["type"]."_options";
				if (isset($one_field[$field_options_key])) {
					// settings exist for this field
					if (isset($one_field[$field_options_key]["enable_extended_return_values"]) && $one_field[$field_options_key]["enable_extended_return_values"]) {
						$parsed_options_for_this_field["extended_return"] = 1;
					}

					if (isset($parsed_options_for_this_field["extended_return"]) && $parsed_options_for_this_field["extended_return"]) {
						// Yep, use extended return values
						$num_values = count($saved_values);
						while ($num_values--) {
							$saved_values[$num_values] = $sf->get_extended_return_values_for_field($one_field, $saved_values[$num_values]);
						}
					}

				}
				
				if ($is_found && $single) {
					$return_val = $saved_values[0];
				} else if ($is_found) {
					$return_val = $saved_values;
				}

				if ($is_found) {
					$return_val = apply_filters( "simple_fields_get_post_value", $return_val, $post_id, $field_name_or_id, $single);
					return $return_val;
				}


			}
		}
	}

	// Nothing found
	$return_val = NULL;
	$return_val = apply_filters( "simple_fields_get_post_value", $return_val, $post_id, $field_name_or_id, $single);
	return $return_val;

}


/**
 * get all values from a field group
 *
 * @param int $post_id
 * @param name or ir $field_group_name_or_id
 * @param bool use_name return array with names or id as key
 * @param int $return_format 1|2
 * @return array
 */
function simple_fields_get_post_group_values($post_id, $field_group_name_or_id, $use_name = true, $return_format = 1) {

	$fetch_by_id = false;
	if (is_numeric($field_group_name_or_id)) {
		$field_group_name_or_id = (int) $field_group_name_or_id;
		$fetch_by_id = true;
	}

	$connector = simple_fields_get_all_fields_and_values_for_post($post_id);

	if (!$connector) {
		$return_val = apply_filters( "simple_fields_get_post_group_values", array(), $post_id, $field_group_name_or_id, $use_name, $return_format);
		return $return_val;
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

				$saved_values = isset( $one_field["saved_values"] ) ? $one_field["saved_values"] : null;

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

			$set_count = isset( $one_field["saved_values"] ) ? sizeof( $one_field["saved_values"] ) : 0;

			$arr_return2 = array();
			for ($i=0; $i<$set_count; $i++) {
				$arr_return2[$i] = array();
				foreach ($arr_return as $key => $val) {
					$arr_return2[$i][$key] = $val[$i];
				}
			}

			if ($return_format == 1) {

				$arr_return = apply_filters( "simple_fields_get_post_group_values", $arr_return, $post_id, $field_group_name_or_id, $use_name, $return_format);
				return $arr_return;

			} elseif ($return_format == 2) {

				$arr_return2 = apply_filters( "simple_fields_get_post_group_values", $arr_return2, $post_id, $field_group_name_or_id, $use_name, $return_format);
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
 * used from many places
 *
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id, $args = "") {
	
	global $sf;
	$cache_key = 'simple_fields_'.$sf->ns_key.'_get_all_fields_and_values_for_post_' . $post_id . "_" . md5(json_encode($args));
	$selected_post_connector = wp_cache_get( $cache_key , 'simple_fields' );

	if (FALSE === $selected_post_connector) {

		$defaults = array(
			"include_deleted" => TRUE
		);
		$args = wp_parse_args($args, $defaults);
	
		$post                     = get_post($post_id);
		$connector_to_use         = $sf->get_selected_connector_for_post($post);
		$existing_post_connectors = $sf->get_post_connectors();
		$field_groups             = $sf->get_field_groups();
		$selected_post_connector  = isset($existing_post_connectors[$connector_to_use]) ? $existing_post_connectors[$connector_to_use] : NULL;
	
		if ($selected_post_connector == null) {
			$return_val = FALSE;
			$return_val = apply_filters( "simple_fields_get_all_fields_and_values_for_post", $return_val, $post_id, $args);
			return $return_val;
		}
	
		// Remove deleted field groups
		if (!$args["include_deleted"]) {
	
			$arr_field_groups_to_keep = array();
			foreach ($selected_post_connector["field_groups"] as $one_field_group_id => $one_field_group) {
	
				if ($one_field_group["deleted"]) continue;
	
				$arr_field_groups_to_keep[$one_field_group_id] = $one_field_group;
	
			}
			$selected_post_connector["field_groups"] = $arr_field_groups_to_keep;
		}
	
		// Do stuff
		foreach ($selected_post_connector["field_groups"] as $one_field_group) { // one_field_group = name, deleted, context, priority, id
	
			// now get all fields for that fieldgroup and join them together
			$selected_post_connector["field_groups"][ $one_field_group["id"] ] = array_merge( $selected_post_connector["field_groups"][ $one_field_group["id"] ], $field_groups[ $one_field_group["id"] ] );
	
			// Older versions don't have slug, so don't bail out if not exists
			$field_group_slug = isset( $one_field_group["slug"] ) ? $one_field_group["slug"] : "";

			// loop through all fields within this field group
			// now find out how many times this field group has been added
			// can be zero, 1 and several (if field group is repeatable)
			$num_added_field_groups = 0;
			$meta_key_num_added = $sf->get_meta_key_num_added( $one_field_group["id"], $field_group_slug );
			
			while (get_post_meta($post_id, "{$meta_key_num_added}{$num_added_field_groups}", true)) {
				$num_added_field_groups++;
			}
			
			// Field groups should only be allowed to be 0 if the group is repeatable
			if ($num_added_field_groups == 0 && (isset($one_field_group['repeatable']) && !$one_field_group['repeatable']) ) {
			    $num_added_field_groups++;
			}
	
			// now fetch the stored values, one field at a time
			// echo "<br>num_added_field_groups: $num_added_field_groups";
			// for repeatable field groups num_added_field_groups is the number of added field groups
			for ($num_in_set = 0; $num_in_set < $num_added_field_groups; $num_in_set++) {
	
				// fetch value for each field
				foreach ($selected_post_connector["field_groups"][$one_field_group["id"]]["fields"] as $one_field_id => $one_field_value) {
	
					$one_field_group_slug = isset( $one_field_group["slug"] ) ? $one_field_group["slug"] : "";
					$one_field_value_slug = isset( $one_field_value["slug"] ) ? $one_field_value["slug"] : "";

					$custom_field_key = $sf->get_meta_key( $one_field_group["id"], $one_field_id, $num_in_set, $one_field_group_slug, $one_field_value_slug );
	
					$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist
	
					// Modify values for some field types
					if ("textarea" === $one_field_value["type"]) {
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
					} else if ("dropdown" === $one_field_value["type"]) {
						
						// dropdown can be multiple since 1.1.4
						if (isset($one_field_value["type_dropdown_options"]["enable_multiple"]) && $one_field_value["type_dropdown_options"]["enable_multiple"]) {

							// value should always be array when using multiple
							if (!is_array($saved_value)) $saved_value = array();

						}

					}

					// 
	
					$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["saved_values"][$num_in_set] = $saved_value;
					$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["meta_keys"][$num_in_set] = $custom_field_key;
	
				}
			}
	
		}
		wp_cache_set( $cache_key, $selected_post_connector, 'simple_fields' );
	}

	$selected_post_connector = apply_filters( "simple_fields_get_all_fields_and_values_for_post", $selected_post_connector, $post_id, $args);
	return $selected_post_connector;
}

class Simple_Fields_Walker_Category_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {

		global $simple_fields_taxonomyterm_walker_field_name;

		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		// @todo: use custom simple fields name for all inputs
		$name = $simple_fields_taxonomyterm_walker_field_name;

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li $class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" ' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
		$output .= "</li>\n";
	}
}


// Returns an array for merging with WP_Query() arguments.
// TODO: A variable in simple_fields_groups that keeps track of the most number
// of times a field has been repeated on any single post so that $num_in_set can
// be determined dynamically.
function simple_fields_get_meta_query($group_id, $field_id, $value, $compare = "=", $type = "CHAR", $order = "", $num_in_set = 1) {

	global $sf;
	$field_group = $sf->get_field_group($group_id);
	$field 		 = $sf->get_field_in_group($field_group, $field_id);

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
		$query_args['meta_query'][$i]['key'] = $sf->get_meta_key( $field_group['id'], $field['id'], $i, $field_group['slug'], $field['slug'] );
		$query_args['meta_query'][$i]['value'] = $value;
		$query_args['meta_query'][$i]['compare'] = $compare;
		$query_args['meta_query'][$i]['type'] = $type;
	}

	if ($order == "ASC" || $order == "DESC") {
		$query_args['meta_key'] = $query_args['meta_query'][0]['key'];
		$query_args['orderby'] = 'meta_value';
		$query_args['order'] = $order;
	}

	$query_args = apply_filters( "simple_fields_get_meta_query", $query_args, $group_id, $field_id, $value, $compare, $type, $order, $num_in_set);
	return $query_args;
}

/**
 * Extends args for WP_Query() with simple fields meta query args
 * and returns query result object
 * 
 * Example:
 *  $args = array(
 *		"post_type" => "books", 
 * 		"sf_group" => "Book details", 
 *		"sf_field" => "Author", 
 * 		"sf_value" => "Stephen King"
 *	);
 *
 *	$my_query = simple_fields_query_posts($args);
 */
function simple_fields_query_posts($query_args = array()) {

		$query_keys = array(
			'sf_group',
			'sf_field',
			'sf_value',
			'sf_compare',
			'sf_type',
			'sf_order',
			'sf_num_in_set'
		);
        
        foreach($query_keys as $key) {
			switch($key) {
				case "sf_group":
				case "sf_field":
				case "sf_value":
					if(empty($query_args[$key]))
						return false;
					break;
				case "sf_compare":
					if(empty($query_args[$key]))
						$query_args[$key] = "=";
					break;
				case "sf_type":
					if(empty($query_args[$key]))
						$query_args[$key] = "CHAR";
					break;
				case "sf_order":
					if($query_args[$key] != "ASC" && $query_args[$key] != "DESC")
						$query_args[$key] = "";
					break;
				case "sf_num_in_set":
					if(!is_numeric($query_args[$key]) || $query_args[$key] < 1)
						$query_args[$key] = 1;
					break;
			}
	}
	
	$meta_query_args = simple_fields_get_meta_query($query_args['sf_group'], $query_args['sf_field'], $query_args['sf_value'], $query_args['sf_compare'], $query_args['sf_type'], $query_args['sf_order'], $query_args['sf_num_in_set']);
	$query_args = array_merge($query_args, $meta_query_args);
	
	$query_args = apply_filters( "simple_fields_query_posts", $query_args);
	return new WP_Query($query_args);

}


/**
 * Merge arrays
 * Seems to combine, not write over
 */
function simple_fields_merge_arrays($array1 = array(), $array2 = array()) {

	// Make sure args is arrays
	$array1 = (array) $array1;
	$array2 = (array) $array2;

	foreach($array2 as $key => $value) {
	
		if ( is_array($value) ) {
			if ( isset( $array1[$key] ) && isset( $array2[$key] ) ) {
				$array1[$key] = simple_fields_merge_arrays( $array1[$key], $array2[$key] );
			} else {
				// only array 2 left
				$array1[$key] = $array2[$key];
			}
		} else {
			$array1[$key] = $value;
		}
	}

	return $array1;
}


/**
 * Adds a new field group
 *
 * See this gist for example and more info:
 * https://gist.github.com/3851387
 *
 * @param string $slug the slug of this field group. must be unique.
 * @param array $new_field_group settings/options for the new group
 * @return array the new field group as an array
 */
function simple_fields_register_field_group($slug = "", $new_field_group = array()) {

	// Make sure options are not completely out of order
	if ( ! is_string($slug) || ! is_array($new_field_group) ) {
		_doing_it_wrong( __FUNCTION__, __("Wrong type of arguments passed", "simple-fields"),  1);
		return false;
	}

	global $sf;

	$field_groups = $sf->get_field_groups();
	$highest_id = 0;
	$is_new_field_group = TRUE;

	// First get the id of the field group we are adding. Existing or highest new.
	// Loop through all existing field groups to see if the field group we are adding already exists
	// Exists = an existing field group has the same slug as the group we are adding
	foreach ($field_groups as $oneGroup) {

		if ($oneGroup["slug"] == $slug && !empty($slug)) {
		
			// Field group with this slug already exists
			// So we have found our field group, no need to loop further
			$field_group_id = $oneGroup["id"];
			$is_new_field_group = FALSE;
			break;

		} else if ($oneGroup["id"] > $highest_id) {

			// We have not found a field id yet
			// and the id of the current group is higher than the current highest id
			$highest_id = $oneGroup["id"];

		}

	}


	// If a new field group then new field group should get the id of the highest field group id + 1
	// If this is the very first field group created then it gets num 1
	if ($is_new_field_group) {
		$highest_id++;
		$field_group_id = $highest_id;
	}

	// Set default values for slug and name
	if (empty($slug)) {

		// Make sure that the field group gets a slug
		$slug = "field_group_" . $field_group_id;

	} else if ( ! isset($new_field_group["name"]) || empty($new_field_group["name"]) ) {

		// Slug is given, but no field group name = use slug as name
		$new_field_group["name"] = $slug;

	}
	
	// Make sure slug is valid
	$slug = sanitize_key($slug);

	// Set up default values 
	if ($is_new_field_group) {

		$field_group_defaults = array(
			"id" => $field_group_id,
			"key" => $slug,
			"slug" => $slug,
			"name" => "Unnamed field group $field_group_id",
			"description" => "",
			"repeatable" => false,
			"fields" => array(),
			"fields_by_slug" => array(),
			"deleted" => false,
			"gui_view" => "list", // list | table
			"added_with_code" => true
		);

	} else {

		// This is an existing field group so get values from existing group
		$field_group_defaults = $field_groups[$field_group_id];

		// make sure all values are set
		// added_with_code since 1.2.4
		if ( ! isset( $field_group_defaults["added_with_code"] ) ) $field_group_defaults["added_with_code"] = true;

		// Add the field id of each field to fields array, since the keys get lost when merging below
		$field_group_defaults["fields_by_slug"] = array();
		if ( is_array( $field_group_defaults["fields"] ) && sizeof( $field_group_defaults["fields"] > 0 ) ) {

			// Check for deleted fields
			// Check for fields that exists among the saved values, but that are not in the new array of fields = that field should be considered deleted
			// a slug in $field_group_defaults["fields"] does not exist in $new_field_group["fields"] = mark that field as deleted
			foreach ( $field_group_defaults["fields"] as $one_field_key => $one_field ) {
				
				if ( ! isset( $one_field["slug"] ) || empty( $one_field["slug"] ) ) continue;
				if ( ! isset( $new_field_group["fields"] ) || ! is_array( $new_field_group["fields"] ) ) continue;

				$old_field_was_found_among_new_fields = false;

				foreach ( $new_field_group["fields"] as $one_new_field ) {

					if ( isset( $one_new_field["slug"] ) && ! empty( $one_new_field["slug"] ) && $one_new_field["slug"] === $one_field["slug"] ) {
						$old_field_was_found_among_new_fields = true;
						break;
					} 
				}

				if ( ! $old_field_was_found_among_new_fields) {
					// echo "<br>not found, considered deleted:"; sf_d($one_field);
					// unset( $field_group_defaults["fields"][ $one_field_key ] );
					$field_group_defaults["fields"][ $one_field_key ]["deleted"] = true;
				}

			} // foreach

			// Create an array with all fields by slug, for faster/easier access
			foreach ( $field_group_defaults["fields"] as $field_id => & $field_array ) {

				$field_array["id"] = $field_id;
				$field_slug = isset( $field_array["slug"] ) ? $field_array["slug"] : "field_$field_id";
				$field_group_defaults["fields_by_slug"][$field_slug] = $field_array;

			} // foreach

		}

	} // if new or not

	// Find the highest existing id. New fields will get this id plus one
	// Note that the highest ID is not the last, since the order of the keys is in custom order, not ascending
	if ( isset($field_groups[$field_group_id]["fields"]) && is_array($field_groups[$field_group_id]["fields"]) && sizeof($field_groups[$field_group_id]["fields"]) > 0) {
		$field_id = max( array_keys( $field_groups[$field_group_id]["fields"] ) ) + 1;
	} else {
		$field_id = 0;
	}

	// Add fields by slug for new fields
	$new_field_group["fields_by_slug"] = array();
	if ( isset($new_field_group["fields"]) ) {
		foreach ( $new_field_group["fields"] as $field_array ) {
			$new_field_group["fields_by_slug"][$field_array["slug"]] = $field_array;
		}
	}

	// Merge the new values of this field group with the old values
	// Let the new values overwrite the hold ones
	// This merge is the reason why we use fields_by_slug
	$field_groups[$field_group_id] = simple_fields_merge_arrays($field_group_defaults, $new_field_group);

	// Now the existing fields that has new values, has new values
	// Brand new fields have no id set, so thats how we can detect them

	// If the field group has an array of fields
	if ( isset($new_field_group["fields"]) && is_array($new_field_group["fields"]) && ! empty($new_field_group["fields"]) ) {

		// Loop through all fields that are passed to function, 
		// make sure new fields has all necessary keys and values
		foreach ( $new_field_group["fields_by_slug"] as $one_new_field ) {

			// Set up default values for this field

			// New field get highest taken id + 1
			$field_defaults = array(
					"id"		  => "",
					"name"        => "",
					"slug"        => "",
					"description" => "",
					"type"        => "",
					"type_post_options" => array(
						"enabled_post_types" => array(), 
						"additional_arguments" => ""
					),
					"type_taxonomyterm_options" => array(
						"additional_arguments" => ""
					),
					"id"      => NULL,
					"deleted" => 0,
					// add general field options
					// each field has its own array here, with field key as key
					// old format with type_<field name>_options was/is kinda crappy
					"options" => array(), 
			);

			// If a field with this index/id exists then merge that values of that field with our default values
			// so if you add one field in php, then one in the gui, and then extend the php with one more field = 
			// field from gui is overwritten since it get's the id that the php version want
			// use only slug instead and we should be fine

			// Find id of possibly existing field using the slug
			// If existing field is found then merge old values with new

			// If fields exist for the old/saved field group, then merge new fields with old ones
			// If existing/old field does not exist then use defaults directly
			if ( isset( $field_groups[$field_group_id]["fields"] ) && is_array( $field_groups[$field_group_id]["fields"] ) ) {

				// Check if our current field has an old version
				// Loop through all fields to find any field with our slug
				if ( isset( $field_groups[$field_group_id]["fields_by_slug"][$one_new_field["slug"]] ) ) {

					$existing_field_array_from_slug = & $field_groups[$field_group_id]["fields_by_slug"][$one_new_field["slug"]];

					// Update old/existings fields by mergering with new fields
					$field_defaults = simple_fields_merge_arrays($field_defaults, $existing_field_array_from_slug );

					// Do stuff with field default values
					// Key = name, slug, type etc.
					// Value = string, array, etc.
					foreach ($field_defaults as $oneDefaultFieldKey => $oneDefaultFieldValue) {

						if ($oneDefaultFieldKey === "id") {

							// If this is a field with no id set, then it's a new field that should get a id
							if ( is_null($oneDefaultFieldValue) || $oneDefaultFieldValue === "" ) {
								#echo "<br>new field - added id $field_id";
								$existing_field_array_from_slug["id"] = $field_id;
								$field_id++;
							}
						
						}

						// If a value in the new/updated field is an array
						// and is among the default values keys, and it's is not empty
						// then if the key is an old school option field with name type_<field type>_options
						// then set defaults for that array
						if ( isset($one_new_field[$oneDefaultFieldKey]) && is_array($one_new_field[$oneDefaultFieldKey]) && !empty($one_new_field[$oneDefaultFieldKey]) ) {

							// If this is an array with options for a field type
							// For example "type_post_options" or "type_taxonomyterm_options"
							$options_type = preg_replace("/type_([a-z]+)_options/i", '$1', $oneDefaultFieldKey);
							if ( ! empty($options_type) ) {

								// Do things the old way. No slugs used here.
								foreach ( array_keys($one_new_field[$oneDefaultFieldKey]) as $optionKey ) {

									// Only continue if key is numeric
									// This code will generate the  "dropdown_num_1"-stuff
									// and the number is based on the index (the array key)
									if ( is_numeric($optionKey) ) {

										if ("radiobuttons" === $options_type) $options_type = "radiobutton";
										$newOptionKey = $options_type . "_num_" . $optionKey;

										$existing_field_array_from_slug[$oneDefaultFieldKey][$newOptionKey] = $one_new_field[$oneDefaultFieldKey][$optionKey];
										unset($existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]);
										$optionKey = $newOptionKey;

									}

									// mark value as non-deleted if deleted is not in the array of dropdown/radiobutton values
									if ( isset( $existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]) && is_array($existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]) && ! empty($existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]["value"]) ) {

										if ( ! isset( $existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]["deleted"]) ) {
											$existing_field_array_from_slug[$oneDefaultFieldKey][$optionKey]["deleted"] = 0;
										}

									}

								} // foreach

							} // if not empty options type

						} // if isset

						// If default value does not exist in new field then add it
						if ( ! isset( $existing_field_array_from_slug[$oneDefaultFieldKey] ) ) {
							$existing_field_array_from_slug[$oneDefaultFieldKey] = $oneDefaultFieldValue;
						}

					} // foreach field default

					// Setup new options array that exists since 1.2
					// existing_field_array_from_slug = the merged array, with old + new options
					// new values from arg = $one_new_field
					// move new options to sub-array by field type
					$arr_merged_options = isset($one_new_field["options"]) ? wp_parse_args( $one_new_field["options"] ) : array();

					// Make sure options key for this field type exists
					if ( ! isset( $existing_field_array_from_slug["options"][ $existing_field_array_from_slug["type"] ] ) ) {
						$existing_field_array_from_slug["options"][ $existing_field_array_from_slug["type"] ] = array();
					}

					// what about for example "type_post_options" that may already exist?
					// if they exist, move to merge with options, then merge with options, before new values are merged
					// do that first since those values are the oldest (pre-upgrade pre-save values)
/*
sf_d( isset( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ] ) );
sf_d( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ] );
This array will alsways exist after we've added a field
Array
(
    [0] => Array
        (
            [num] => 1
            [value] => Stor
        )

)
*/
#echo "<br>before:<br>";
#sf_d($arr_merged_options);
					if ( isset( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ] ) ) {
# denna ökar antalet dropdown values vid varje körning
						#sf_d( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ] );
						$arr_old_vals_to_merge = array();
						$arr_old_vals_to_merge_values = array();
						foreach ( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ] as $one_key => $one_val ) {
							
							// $new_values_key = ( "radiobuttons" === $one_new_field["type"] ) ? "radiobutton_num_"  : "dropdown_num_";
							if ( strpos( $one_key, "dropdown_num_" ) !== FALSE || strpos( $one_key, "radiobutton_num_" ) !== FALSE ) {
								$num = str_replace( array("dropdown_num_", "checkbox_num_"), "", $one_key );
								$one_val["num"] = $num;
								$arr_old_vals_to_merge_values[] = $one_val;
							} else {
								$arr_old_vals_to_merge[ $one_key ] = $one_val;
							}
							
							$arr_old_vals_to_merge["values"] = $arr_old_vals_to_merge_values;

						} // foreach

						#$arr_merged_options = array_merge( $existing_field_array_from_slug[ "type_". $existing_field_array_from_slug["type"] . "_options" ], $arr_merged_options );
#sf_d($arr_old_vals_to_merge);
						$arr_merged_options = array_merge( $arr_old_vals_to_merge, $arr_merged_options );
					}
#sf_d($arr_merged_options);
#echo "<br>after:<br>";
#sf_d($arr_merged_options);

					// Merge in new values, overwriting existing, but also letting existing keys that have no new value be
# denna ökar antalet dropdown values vid varje körning
					$arr_merged_options = array_merge( $existing_field_array_from_slug["options"][ $existing_field_array_from_slug["type"] ], $arr_merged_options );
					$existing_field_array_from_slug["options"][ $existing_field_array_from_slug["type"] ] = $arr_merged_options;

					/*
					problem with field group values from multiple sources:

					Example below: "products" and "monkeys" won't get removed, due. to merge.. well.. merging!
					Instead array sent in to function as arg should be allowed to overwrite existing array
					But only overwrite keys that already exists, leaving other keys

					If this is the already stored format (from for example GUI, or from previous register_field_group)
					[post] => Array
					    (
					        [enabled_post_types] => Array
					            (
					                [0] => post
					                [1] => page
					                [2] => products
					                [3] => monkeys
					            )
					        [additional_arguments] => cat=10
					        [enable_extended_return_values] => 1
					    )

					 And this is the new as sent in as arg to register_field_group:
					 [post] => Array
					    (
					        [enabled_post_types] => Array
					            (
					                [0] => post
					                [1] => page
					            )
					        [additional_arguments] => cat=10
					        [enable_extended_return_values] => 1
					    )
		      
					*/

					// Remove the keys we added from the options array (just keep them in the sub-array)
					$new_options_keys = isset( $one_new_field["options"] ) ? array_keys( (array) $one_new_field["options"] ) : array();

					// if someone did enter values like this:
					// options[field_type] => array(options..)
					// then don't break that by removing

					if ( isset( $one_new_field["type"] ) && ( $key_to_remove_pos = array_search( $one_new_field["type"], $new_options_keys ) ) !== FALSE ) {
						unset( $new_options_keys[ $key_to_remove_pos ] );
						unset( $existing_field_array_from_slug["options"][ $one_new_field["type"] ][ $one_new_field["type"] ] );
					} 

					// Remove the keys we added from the options array (but keep them in the sub-array)
					foreach ( $new_options_keys as $one_key_to_remove ) {
						unset( $existing_field_array_from_slug["options"][ $one_key_to_remove ] );
					}

					// Fix dropdown and radiobuttons values for the array in with the "options" key
					// If field type is dropdown or radiobuttons then convert new format to old format,
					// because old format is used internally in many places
					if ( isset( $one_new_field["type"] ) && ( $one_new_field["type"] === "dropdown" || $one_new_field["type"] === "radiobuttons" ) ) {

						if ( isset( $one_new_field["options"]["values"] ) && is_array( $one_new_field["options"]["values"] ) ) {
							
							$new_values = array();
							$did_set_checked_by_default = FALSE;
							foreach ( $one_new_field["options"]["values"] as $one_dropdown_or_radio_value ) {

								// Each value must have num and value
								if ( ! isset( $one_dropdown_or_radio_value["value"] ) || ! isset( $one_dropdown_or_radio_value["value"] ) ) continue;

								$new_values_key = ( "radiobuttons" === $one_new_field["type"] ) ? "radiobutton_num_"  : "dropdown_num_";
								$new_values_key .=  isset( $one_dropdown_or_radio_value["num"] ) ? (int) $one_dropdown_or_radio_value["num"] : 0;
								$new_values[ $new_values_key ] = array(
									"value" => $one_dropdown_or_radio_value["value"],
									"deleted" => isset( $one_dropdown_or_radio_value["deleted"] ) ? (bool) $one_dropdown_or_radio_value["deleted"] : FALSE
								);

								// "checked_by_default_num" => "radiobutton_num_2"
								if ( isset( $one_dropdown_or_radio_value["checked"] ) && TRUE === $one_dropdown_or_radio_value["checked"] ) {
									$new_values["checked_by_default_num"] = $new_values_key;
									$did_set_checked_by_default = TRUE;
								}

							} // foreach

							if ( FALSE === $did_set_checked_by_default ) $new_values["checked_by_default_num"] = NULL;

							// Unset all existing radiobuttons or dropdowns
							// This will remove dropdowns/radiobuttons that are not in the new field setup, 
							// and it will make sure that the order is the new order
							foreach ( $arr_merged_options as $one_key => $one_val ) {
								// radiobutton_num_10 dropdown_num_2
								if ( strpos( $one_key, "dropdown_num_" ) !== FALSE || strpos( $one_key, "radiobutton_num_" ) !== FALSE ) {
									unset( $arr_merged_options[ $one_key ] );
								}
							}

							$arr_merged_options = array_merge($arr_merged_options, $new_values);
#echo 111; sf_d($arr_merged_options);

						} // if
					
					} // if

/*
is like:
[options] => Array
    (
        [dropdown] => Array
            (
                [enable_extended_return_values] => 1
                [enable_multiple] => 1
                [values] => Array
                    (
                        [0] => Array
                            (
                                [num] => 0
                                [value] => Yes New
                                [deleted] => 1
                                [possibly_other_stuff_in_future] => yes
                            )

                        [1] => Array
                            (
                                [num] => 1
                                [value] => No New
                            )

                        [2] => Array
                            (
                                [num] => 2
                                [value] => Maybe New
                            )

                    )

            )

must be like:
[type_dropdown_options] => Array
    (
        [dropdown_num_0] => Array
            (
                [value] => Yes
                [deleted] => 1
            )

        [dropdown_num_1] => Array
            (
                [value] => No
                [deleted] => 0
            )

        [dropdown_num_2] => Array
            (
                [value] => Maybe
                [deleted] => 0
            )

        [enable_extended_return_values] => 1
        [0] => enable_multiple
        [enable_multiple] => 1
    )
*/
					// If this is any of the core fields types then save back all options to type_<fieldtype>_options
					// Can't remove that reference completely because it is used at so many places
					/*
					*/
#echo "<br>arr_merged_options:";
#sf_d($arr_merged_options);
					if ( isset( $one_new_field["type"] ) &&  $sf->field_type_is_core( $one_new_field["type"] ) ) {

						$arr_type_old_school_options = $arr_merged_options;

						// Oh, and convert back to the old funky _num_ format for dropdown and radiobuttons. Bah.
						if ( isset( $arr_type_old_school_options["values"] ) ) {

							// Move back from sub values arrays yo the main array
							foreach ( $arr_type_old_school_options["values"] as $one_key => $one_val ) {

								if ( is_int( $one_key ) ) {

									$key = ( "radiobuttons" === $one_new_field["type"] ) ? "radiobutton_num_"  : "dropdown_num_";
									$key .= $one_val["num"];
									$arr_type_old_school_options[ $key ] = $one_val;
									unset( $arr_type_old_school_options[ $one_key ] );

								}

							}

							unset( $arr_type_old_school_options["values"] );

						}

						$existing_field_array_from_slug[ "type_" . $one_new_field["type"] . "_options"] = $arr_type_old_school_options;
#echo "<br>arr_type_old_school_options:";sf_d( $arr_type_old_school_options );
					}
					// end move options in place
				
				} // if field exists among fields by slugs

			} // foreach field default

		} // for each field in a field grouo

		$merged_fields = $field_groups[$field_group_id]["fields_by_slug"];

		// Update fields (by id) from fields by slugs
		$field_groups[$field_group_id]["fields"] = array();
		foreach( $merged_fields as $one_field_array) {
			$field_groups[$field_group_id]["fields"][$one_field_array["id"]] = $one_field_array;
		}


		// Re-order fields to be in same way as in passed arg, because that's the order the user wants it
		// Use order from $new_field_group["fields"]
		// Old fields created in GUI will be added last. That's fine and most logical, right?
		$fields_in_new_order = array();

		// First add new fields in the order we got them to this function
		foreach ( $new_field_group["fields"] as $field_array ) {
			
			$this_field_id = $field_groups[ $field_group_id ]["fields_by_slug"][ $field_array["slug"] ]["id"];
			$fields_in_new_order[ $this_field_id ] = $field_groups[$field_group_id]["fields"][ $this_field_id ];

		}

		// Then add old fields
		foreach ( $field_groups[ $field_group_id ]["fields"] as $field_array ) {

			if ( ! isset ($fields_in_new_order[ $field_array["id"] ] ) ) {
				$fields_in_new_order[ $field_array["id"] ] = $field_array;
			}

		}

		// Update the field group with
		$field_groups[$field_group_id]["fields"] = $fields_in_new_order;

		// And add the correct order to fields_by_slugs too
		$field_groups[$field_group_id]["fields_by_slug"] = array();
		foreach ( $field_groups[$field_group_id]["fields"] as $field_array) {
			$field_groups[$field_group_id]["fields_by_slug"][$field_array["slug"]] = $field_array;
		}

	} // if passed as arg field group has fields

	// Save to options and clear cache
	update_option("simple_fields_groups", $field_groups);
	$sf->clear_caches();

	// Re-get the field so it's the same as when getting a field group manually
	$field_group_by_slug = $sf->get_field_group_by_slug($slug, true);

	return $field_group_by_slug;

}

/**
 * @todo: documentation
 * Register a post connector
 * @param string $unique_name The slug for this connector
 * @param array $new_post_connector Args for this connector
 */
function simple_fields_register_post_connector($unique_name = "", $new_post_connector = array()) {
	#sf_d($new_post_connector);
	global $sf;

	$post_connectors = $sf->get_post_connectors();

	// Id of found or new connector
	$connector_id = NULL;

	// Id of highest connector, if no connector found for slug
	$highest_connector_id = 0;

	$is_new_connector = FALSE;

	// Check if connector already exist 
	// or if it does not then get a new id for it
	foreach ($post_connectors as $oneConnector ) {

		if ( $oneConnector["slug"] == $unique_name && !empty( $unique_name) ) {
			
			// Connector already exists
			$connector_id = $oneConnector["id"];
			
			// No need to loop further once we found the connector
			break;

		} else if ( ! isset($connector_id) && $oneConnector["id"] > $highest_connector_id ) {
		
			// Connector not found so far and id of this connector is the highest so far
			$highest_connector_id = $oneConnector["id"];

		}

	}

	// If no connector_id was found then this is a new connector
	// Set connector_id to the highest connector_id + 1
	if ( ! isset($connector_id) || ! is_numeric($connector_id) ) {
		
		$is_new_connector = TRUE;

		if ( ! empty($post_connectors[$highest_connector_id]) || $highest_connector_id > 0 ) {
			$highest_connector_id++;
		}

		$connector_id = $highest_connector_id;

		// If $connector_id is 0 here then it's the first ever created
		// But 0 is the id to tell SF to create new (in admin), so we must up it to 1
		if ( $connector_id === 0 ) $connector_id = 1;

	}

	// Make sure connector has a slug
	if (empty($unique_name)) {
		$unique_name = "post_connector_" . $connector_id;
	}

	$unique_name = sanitize_key($unique_name);

	// Make sure name is not empty
	if (! isset($new_post_connector["name"]) || empty($new_post_connector["name"])) {
		$new_post_connector["name"] = $unique_name;
	}

	// Make sure post_types is an array (this allows for post_types to be a string)
	if ( isset($new_post_connector["post_types"]) && ! is_array($new_post_connector["post_types"]) ) {
		$new_post_connector["post_types"] = (array) $new_post_connector["post_types"];
	}

	// Setup defaults to merge to
	if ($is_new_connector) {

		// New connector, setup defaults
		$post_connector_defaults = array(
			"id" => $connector_id,
			"key" => $unique_name,
			"slug" => $unique_name,
			"name" => $unique_name."_".$connector_id,
			"field_groups" => array(),
			"post_types" => array(),
			"deleted" => false,
			"hide_editor" => false,
			"added_with_code" => true
		);

	} else {

		// Existing connector, get old values
		$post_connector_defaults = $post_connectors[$connector_id];

		// make sure all values are set
		// added_with_code since 1.2.4
		if ( ! isset( $post_connector_defaults["added_with_code"] ) ) $post_connector_defaults["added_with_code"] = true;

	}

	// Create or update this connector_id id the array of existing connectors
	$post_connectors[$connector_id] = simple_fields_merge_arrays($post_connector_defaults, $new_post_connector);
	$post_connectors[$connector_id]['post_types'] = array_unique($post_connectors[$connector_id]['post_types']);
	
	// If field group passed as args is a non-empty array
	// This is where field groups get attached to this connector
	if (isset($new_post_connector["field_groups"]) && is_array($new_post_connector["field_groups"]) && !empty($new_post_connector["field_groups"])) {

		// Array with all field groups that this connector has
		$field_group_connectors = array();

		// Default values
		$field_group_connector_defaults = array(
							"id" => "",
							"key" => "",
							"deleted" => 0,
							"context" => "normal",
							"priority" => "low"
						);
		
		// For each field group that we want to connect to this connector
		foreach ( $new_post_connector["field_groups"] as $field_group_options ) {
		
			// Key is deprecated, use slug
			if ( isset( $field_group_options["key"] ) && ! empty( $field_group_options["key"] ) ) {
				$field_group_options["slug"] = $field_group_options["key"];
			} else if ( isset( $field_group_options["slug"] ) && ! empty( $field_group_options["slug"] ) ) {
				$field_group_options["key"] = $field_group_options["slug"];
			}

			// Check if the field group we want to connect actually exists
			// First check by id, then if not found by slug - slug is prefered and the only options kinda supported...by me at least :)
			$found_field_group = NULL;
			$found_field_group = isset( $field_group_options["id"] ) ? $sf->get_field_group_by_slug($field_group_options["id"]) : NULL;
			$found_field_group = ! isset( $found_field_group ) && isset( $field_group_options["slug"] ) ? $sf->get_field_group_by_slug($field_group_options["slug"]) : NULL;

			// Field group was found, so now add it to the connector
			if ( isset( $found_field_group ) ) {

				$field_group_id = $found_field_group["id"];
				$field_group_slug = $found_field_group["slug"];
				$field_group_name = $found_field_group["name"];

				// If field group is deleted or not
				// default_field_group_connector = a copy of new field group defaults or a copy of existing field group values
				$default_field_group_connector = NULL;
				if ( isset( $field_group_connectors[$field_group_id] ) && ! $field_group_connectors[$field_group_id]["deleted"] ) {
					// A little unsure on how when we get here
					$default_field_group_connector = $field_group_connectors[$field_group_id];
				} else {
					$default_field_group_connector = $field_group_connector_defaults;
				}

				// Add id from found field group
				// And slug + also key for backwards compatibility
				// And name
				$field_group_connectors[$field_group_id]["id"] = $field_group_id;
				$field_group_connectors[$field_group_id]["slug"] = $field_group_slug;
				$field_group_connectors[$field_group_id]["key"] = $field_group_slug;
				$field_group_connectors[$field_group_id]["name"] = $field_group_name;

				// Go through all default values and make sure field array has each of them set
				foreach ($default_field_group_connector as $oneGroupConnectorDefaultKey => $oneGroupConnectorDefaultValue) {
				
					// Skip some keys, that are added always above
					if ( in_array( $oneGroupConnectorDefaultKey, array("id", "slug", "name") ) ) {
						continue;
					}

					// Ok, what happens here?
					// field_group_options is one of the field groups what we want to connect to post connector, as send here by parameter (not as saved in db or such)
					// field_group_connectors is array with all field groups that this connector will have
					if ( isset( $field_group_options[$oneGroupConnectorDefaultKey] ) ) {
						
						// If the key from the defaults array did exist in the field group sent in to this function
						// So use the value that we got as an parameter, overwriting the default value
						$field_group_connectors[$field_group_id][$oneGroupConnectorDefaultKey] = $field_group_options[$oneGroupConnectorDefaultKey];

					} else {
						
						// Key from defaults array was not found in the field group sent to this function
						// So use default value
						$field_group_connectors[$field_group_id][$oneGroupConnectorDefaultKey] = $oneGroupConnectorDefaultValue;

					}

				} // make sure all keys form default values exists
			
			} // if field group found

		} // foreach field group

		// Done adding field group
		$post_connectors[$connector_id]["field_groups"] = $field_group_connectors;

	} // if field groups was sent along as arg
	
	// Clear cache, store values, and return
	$sf->clear_caches();
	update_option("simple_fields_post_connectors", $post_connectors);
	return $sf->get_connector_by_id($connector_id);

}

/**
 * Sets the default post connector for a post type
 * 
 * @param $post_type_connector = connector id (int) or key (string) or string __inherit__
 * 
 */
function simple_fields_register_post_type_default($connector_id_or_special_type = "", $post_type = "post") {

	global $sf;

	// simple_fields::debug("simple_fields_register_post_type_default()", array("post_type_connector" => $connector_id_or_special_type, "post_type" => $post_type));

	if (is_numeric($connector_id_or_special_type)) {

		$connector_id_or_special_type = (int) $connector_id_or_special_type;

	} else if ( in_array($connector_id_or_special_type, array("__inherit__", "__none__") ) ) {

		// Nothing to do here - It's good already

	} else if (!is_numeric($connector_id_or_special_type)) {

		// Connector is probably the name

		if (empty($connector_id_or_special_type)) {
			return false;
		}
		$post_connectors = $sf->get_post_connectors();
		foreach ($post_connectors as $oneConnector) {
			if ($oneConnector["key"] == $connector_id_or_special_type) {
				$connector_id_or_special_type = $oneConnector["id"];
			}
		}

		if (!is_numeric($connector_id_or_special_type)) {
			// Still not numeric?
			return false;
		}

	}

	$post_type_defaults = $sf->get_post_type_defaults();

	$post_type_defaults[$post_type] = $connector_id_or_special_type;
	if (isset($post_type_defaults[0])) {
		unset($post_type_defaults[0]);
	}
	
	$sf->clear_caches();
	update_option("simple_fields_post_type_defaults", $post_type_defaults);

}


/**
 * Update/Set a value for a field for a post
 * Warning: Highly untested!
 *
 * @param int $post_id
 * @param string $field_slug field key
 * @param int $numInSet if field is repeatable this tells what position it should be stored at. Default is null = add new
 * @param mixed $post_connector string __none__, __inherit__, or int id of post connector to use for this post, if no connector is defined already
 * @param probably array @new_value A bit sneaky, since every field can store it's field in it's own way. An array with key -> value is not that uncommon and is a pretty good guess.
 *		  the map plugins stores it like this anyway:
 * 			Array
 * 			(
 * 			    [lat] => 59.94765909298758
 * 			    [lng] => 17.537915482803328
 * 			)
 */
function simple_fields_set_value($post_id, $field_slug, $new_numInSet = null, $new_post_connector = null, $new_value) {

	/*
	echo "<br><br>Setting field with slug " . $field_slug . " for post " . $post_id;
	echo "<br>value to set is: " . $new_value;
	// */

	global $sf;

	// First check the post connector for this post
	// The post must have a connector or there will be problems getting the saved values
	$post = get_post($post_id);
	$saved_connector = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
	// $selected_connector = $sf->get_selected_connector_for_post($post);
	$default_connector_to_use = $sf->get_default_connector_for_post_type($post->post_type);

	/*echo "saved_connector:"; sf_d($saved_connector);
	echo "default_connector_to_use:"; sf_d($default_connector_to_use);
	exit;*/

	// if no saved connector, set it to $post_connector.
	// if no $post_connector, set it to default connector to use
	$set_post_connector_to = null;
	if (empty($saved_connector)) {

		if (empty($new_post_connector)) {
			$set_post_connector_to = $default_connector_to_use;
		} else {
			$set_post_connector_to = $new_post_connector;
		}

		update_post_meta($post->ID, "_simple_fields_selected_connector", $set_post_connector_to);			

	}

	// Loop through the field groups that this post connector has and locate the field_slug we are looking for
	$post_connector_info = simple_fields_get_all_fields_and_values_for_post($post_id);
	// echo "post_connector_info: "; sf_d($post_connector_info); exit;
	foreach ($post_connector_info["field_groups"] as $one_field_group) {

		$field_group_id = $one_field_group["id"];
		$meta_key_num_added = $sf->get_meta_key_num_added( $one_field_group["id"], $one_field_group["slug"] );

		// check number of added field groups
		$num_added_field_groups = 0; 
		while (get_post_meta($post_id, "{$meta_key_num_added}{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}

		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 

			// Skip deleted fields
			if ($one_field_group_field["deleted"]) continue;

			if ($field_slug === $one_field_group_field["slug"]) {

				// Found field with selected slug
				$field_id = $one_field_group_field["id"];

				// If we have a numInSet argument
				if ( is_numeric( $new_numInSet ) ) {
					$num_in_set = $new_numInSet;
				} else {
					$num_in_set = $num_added_field_groups;			        
				}

				$meta_key = $sf->get_meta_key( $field_group_id, $field_id, $num_in_set, $one_field_group_field["slug"], $one_field_group_field["slug"] );
				update_post_meta($post_id, $meta_key, $new_value);
				update_post_meta($post_id, "{$meta_key_num_added}{$num_in_set}", 1);

				update_post_meta($post_id, "_simple_fields_been_saved", 1);
				
				// value updated. clear cache and exit function.
				$sf->clear_caches();
				return TRUE;

			} // if

		} // foreach

	} // foreach
}


/**
 * Gets a single value.
 * The first value if field group is repeatable
 *
 * @param string $field_slug
 * @param int $post_id ID of post or null to use current post in loop
 * @param array $options Array or query string of options to send to field type
 * @return mixed string or array, depending on the field type
 */
function simple_fields_value($field_slug = NULL, $post_id = NULL, $options = NULL) {
	
	$values = simple_fields_values($field_slug, $post_id, $options);
	$value = isset($values[0]) ? $values[0] : "";
	$value = apply_filters( "simple_fields_value", $value, $field_slug, $post_id, $options);
	return $value;

}

/**
 * Gets all values as array
 * @param string $field_slug or comman separated list of several slugs
 * @param int $post_id ID of post or null to use current post in loop
 * @param array $options Array or query string of options to send to field type
 * @return mixed string or array, depending on the field type and if first arg contains comma or not
 * 
 * Example output if field_slug contains comma = get serveral fields from a repeatable field group
 *  Array
 * (
 *     [0] => Array
 *         (
 *             [image] => 1156
 *             [text] => Text 1
 *             [link] => /hej/
 *         )
 * 
 *     [1] => Array
 *         (
 *             [image] => 1159
 *             [text] => Hej svejs i ditt fejs
 *             [link] => /hopp/
 *         )
 *
 *  Example output if field_slug with no comma:
 *  Array
 * (
 *     [0] => Text 1
 *     [1] => Hej svejs i ditt fejs
 *     [2] => Lorem ipsum dolor sit amet
 * )
 */
function simple_fields_values($field_slug = NULL, $post_id = NULL, $options = NULL) {

	if (empty($field_slug)) {
		return FALSE;
	}

	if (is_null($post_id)) {
		$post_id = get_the_ID();
	}

	// if field_slug contains commas then get all fields. awe-some!
	if (strpos($field_slug, ",") !== FALSE) {

		$arr_comma_slugs_values = array();
		$arr_field_slugs = explode(",", $field_slug);
		if ($arr_field_slugs) {
			
			foreach ($arr_field_slugs as $one_of_the_comma_separated_slug) {
			
				$one_of_the_comma_separated_slug = trim($one_of_the_comma_separated_slug);

				$one_slug_values = simple_fields_values($one_of_the_comma_separated_slug, $post_id, $options);

				// no value, don't add
				if (empty($one_slug_values)) continue;

				$loopnum = 0;

				if (!isset($arr_comma_slugs_values[$loopnum])) $arr_comma_slugs_values[$loopnum] = array();

				foreach ($one_slug_values as $one_slug_sub_value) {
					$arr_comma_slugs_values[$loopnum][$one_of_the_comma_separated_slug] = $one_slug_sub_value;
					$loopnum++;
				}

			}
		}

		$arr_comma_slugs_values = apply_filters( "simple_fields_values", $arr_comma_slugs_values, $field_slug, $post_id, $options);
		return $arr_comma_slugs_values;

	}

	global $sf;

	// Post connector for this post, with lots of info
	$post_connector_info = simple_fields_get_all_fields_and_values_for_post($post_id, "include_deleted=0");

	if ($post_connector_info === FALSE) {
		$return_val = apply_filters( "simple_fields_values", FALSE, $field_slug, $post_id, $options);
		return $return_val;
	}

	$parsed_options = wp_parse_args($options);

	// Loop through the field groups that this post connector has and locate the field_slug we are looking for
	foreach ($post_connector_info["field_groups"] as $one_field_group) {

		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 

			// Skip deleted fields
			if ($one_field_group_field["deleted"]) continue;

			if ($field_slug === $one_field_group_field["slug"]) {
			
				// Detect options for the field with this slug
				// options are in format:
				// extended_output=1&file[extended_output]=1&file[anotherOptions]=yepp indeed
				// where the first arg is for all fields, and the one with square-brackets are for specific slugs
				$parsed_options_for_this_field = array();

				// First check for settings saved for the field (in gui or through register_field_group)
				$field_options_key = "type_".$one_field_group_field["type"]."_options";
				if (isset($one_field_group_field[$field_options_key])) {
					// settings exist for this field
					if (isset($one_field_group_field[$field_options_key]["enable_extended_return_values"]) && $one_field_group_field[$field_options_key]["enable_extended_return_values"]) {
						$parsed_options_for_this_field["extended_return"] = 1;
					}

				}
				
				// check for options savailable for all fields
				// all keys for values that are not arrays. these are args that are meant for all slugs
				foreach ($parsed_options as $key => $val) {
					if (!is_array($val)) {
						$parsed_options_for_this_field = array_merge($parsed_options_for_this_field, array($key => $val));
					}
				}

				// check for options for just this specific slug
				// if our field slug is available as a key and that key is an array = value is for this field slug
				if ( isset($parsed_options[$one_field_group_field["slug"]]) && is_array($parsed_options[$one_field_group_field["slug"]]) ) {
					$parsed_options_for_this_field = array_merge($parsed_options_for_this_field, $parsed_options[$one_field_group_field["slug"]]);
				}

				// that's it, we have the options that should be available for this field slug
				// echo "<br>field: " . $one_field_group_field["slug"];
				// sf_d($parsed_options_for_this_field);
					
				// Slug is found. Get and return values.
				// If no value is set. Should we return string, null, or false? NULL as in "no value exists"?
				$saved_values = isset($one_field_group_field["saved_values"]) ? $one_field_group_field["saved_values"] : NULL;

				// If no values just return
				// But return an array, since that's what we except it to return
				// if (!sizeof($saved_values)) return array(); // no, don't return here. let the action further down run.
				if (!sizeof($saved_values)) $saved_values = array();
				
				/*
					For old/core/legacy fields it's like this:
					Array
					(
						[0] => Entered text into field one
						[1] => Entered text into field two
					)

					For new/cool/custom field types it's like this:
					Array
					(
						[0] => Array
							(
								[option1] => Yeah
								[option2] => aha
							)

						[1] => Array
							(
								[option1] => hejhopp
								[option2] => snopp-pop
							)
					)
				*/

				// If the type is among the registered_field_types then use it
				//if (isset($sf->registered_field_types[$one_field_group_field["type"]]) && isset($saved_values[0]) && is_array($saved_values[0])) {
				if ( isset($sf->registered_field_types[$one_field_group_field["type"]]) && isset($saved_values[0]) ) {

					// Use the custom field object to output this value, since we can't guess how the data is supposed to be used
					$custom_field_type = $sf->registered_field_types[$one_field_group_field["type"]];
					$saved_values = $custom_field_type->return_values($saved_values, $parsed_options_for_this_field);

				} else {

					// legacy/core field type, uses plain $saved_values
					// ...but since 1.0.3 you can use extened return
					// $parsed_options_for_this_field


					// Check if field should return extended return values
					if ( isset($parsed_options_for_this_field["extended_return"]) && (bool) $parsed_options_for_this_field["extended_return"] ) {
						
						// check if current field type supports this
						if ( in_array($one_field_group_field["type"], array("file", "radiobuttons", "dropdown", "post", "user", "taxonomy", "taxonomyterm", "date")) ) {
							
							foreach ($saved_values as $one_saved_value_key => $one_saved_value) {
								$saved_values[$one_saved_value_key] = $sf->get_extended_return_values_for_field($one_field_group_field, $one_saved_value);
							}
							
						}
					}

				}

				/*
				// You can modify the return values by adding a filter for the field type you want to modify
				// Example: adds "appended text" to all text values upon retrieval
				add_filter("simple-fields-return-values-text", function($values) {
					$values[0] = $values[0] . " appended text";
					return $values;
				});
				*/
				$saved_values = apply_filters("simple-fields-return-values-" . $one_field_group_field["type"], $saved_values);
				$saved_values = apply_filters( "simple_fields_values", $saved_values, $field_slug, $post_id, $options, $one_field_group_field["type"]);
				return $saved_values;					

			}
		}
	}

} // simple field values


/**
 * Return the slug of the post connector for the current post in the loop or for the post specified in $post_id
 * @param $post_id optional post or post id
 * @return mixed False if no connector or connector not found. String slug of connector if found.
 */
function simple_fields_connector($post_id = NULL) {

	global $post, $sf;
	
	if (is_numeric($post_id)) {
		$post_this = get_post($post_id);
	} else {
		$post_this = $post;
	}

	$connector_id = $sf->get_selected_connector_for_post($post_this);

	if ($connector_id == "__none__") {

		// no connector selected
		$return_val = FALSE;
		$return_val = apply_filters( "simple_fields_connector", $return_val, $post_id);
		return $return_val;

	} else {

		// connector is selected, get slug of it
		$post_connectors = $sf->get_post_connectors();
		if (!isset($post_connectors[$connector_id])) {
			$return_val = FALSE;
			$return_val = apply_filters( "simple_fields_connector", $return_val, $post_id);
			return $return_val;
		} 

		$return_val = $post_connectors[$connector_id]["slug"];
		$return_val = apply_filters( "simple_fields_connector", $return_val, $post_id);
		return $post_connectors[$connector_id]["slug"];

	}
}

/**
 * Checks if the current post in the loop has the connector with slug $slug selected
 *
 * @param string $slug Slug of post connector to check
 * @return bool
 */
function simple_fields_is_connector($slug) {
	$connector_slug = simple_fields_connector();
	return ($connector_slug === $slug);
}

/**
 * Returns allt the values in a field group
 * It's a shortcut to running simple_fields_value(slugs) with all slugs already entered
 * Depending if the field group is repeatable or not, simple_field_value or simple_fields_values will be used
 *
 * @param mixed $field_group_id_or_slug
 * @return mixed, but probably array, depending on how many field the group has (just one field, and not repeatable = no array for you!)
 */
function simple_fields_fieldgroup($field_group_id_or_slug, $post_id = NULL, $options = array()) {

	if (!is_numeric($post_id)) {
		global $post;
		$post_id = $post->ID;
	}

	global $sf;
	$cache_key = "simple_fields_".$sf->ns_key."_fieldgroup_" . $field_group_id_or_slug . "_" . $post_id . "_" . md5(json_encode($options));
	$values = wp_cache_get( $cache_key, 'simple_fields');
	if (FALSE === $values) {
	
		$field_group = $sf->get_field_group_by_slug($field_group_id_or_slug);
	
		$arr_fields = array();
		foreach ($field_group["fields"] as $one_field) {
			if ($one_field["deleted"]) continue;
			$arr_fields[] = trim($one_field["slug"]);
		}
		
		$str_field_slugs = join(",", $arr_fields);
		if ($field_group["repeatable"]) {
			$values = simple_fields_values($str_field_slugs, $post_id);
		} else {
			$values = simple_fields_value($str_field_slugs, $post_id);
		}
		wp_cache_set( $cache_key, $values, 'simple_fields' );
	}

	$values = apply_filters( "simple_fields_fieldgroup", $values, $field_group_id_or_slug, $post_id, $options);
	return $values;

}

/**
 * helper to sort fields by name. used on options screen
 * to be used with uasort()
 */
function simple_fields_uasort($a, $b) {
	if ($a["name"] == $b["name"]) { return 0; }
	return strcasecmp($a["name"], $b["name"]);
}
