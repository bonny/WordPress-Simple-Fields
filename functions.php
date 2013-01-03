<?php

/**
 * Functions that should be available outside the simple fields class
 */

/**
 * Quicky debug a variable
 * @param $var mixed
 * @return echo output
 */
if (!function_exists("sf_d")) {
function sf_d($var) {
	echo "<pre class='sf_box_debug'>";
	if (is_array($var) || is_object($var)) {
		echo htmlspecialchars( print_r($var, true), ENT_QUOTES, 'UTF-8' );
	} else if( is_null($var) ) {
		echo "Var is NULL";
	} else if ( is_bool($var)) {
		echo "Var is BOOLEAN ";
		echo $var ? "TRUE" : "FALSE";
	} else {
		echo htmlspecialchars( $var, ENT_QUOTES, 'UTF-8' );
	}
	echo "</pre>";
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

				// xxx make sure extended return value works here too
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

				// hm.. can't get here right??!
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
 * used from many places
 *
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id, $args = "") {
	
	global $sf;
	$cache_key = 'simple_fields_'.$sf->ns_key.'_get_all_fields_and_values_for_post_' . $post_id . json_encode($args);
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
			return false;
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
			$selected_post_connector["field_groups"][$one_field_group["id"]] = array_merge($selected_post_connector["field_groups"][$one_field_group["id"]], $field_groups[$one_field_group["id"]]);
	
			// loop through all fields within this field group
			// now find out how many times this field group has been added
			// can be zero, 1 and several (if field group is repeatable)
			$num_added_field_groups = 0;
	
			while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
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
	
					#echo "<br>num in set: $num_in_set";
					#sf_d($one_field_value);
	
					$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";
					#echo "<br>custom field key: $custom_field_key";
	
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

	return $selected_post_connector;
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
        $query_keys = array('sf_group',
                'sf_field',
                'sf_value',
                'sf_compare',
                'sf_type',
                'sf_order',
                'sf_num_in_set');
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
	return new WP_Query($query_args);
}


function simple_fields_merge_arrays($array1 = array(), $array2 = array()) {
	foreach($array2 as $key => $value) {
		if(is_array($value)) {
			if (isset($array1[$key]) && isset($array2[$key])) {
				$array1[$key] = simple_fields_merge_arrays($array1[$key], $array2[$key]);
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
 * @param string $slug the slug of this field group. must be unique.
 * @param array $new_field_group settings/options for the new group
 * @return array the new field group as an array
 */
function simple_fields_register_field_group($slug = "", $new_field_group = array()) {

	global $sf;

	$field_groups = $sf->get_field_groups();
	#sf_d($field_groups);
	$highest_id = 0;

	// First get the id of the field group we are adding. Existing or highest new.
	// Loop through all existing field groups to see if the field group we are adding already exists
	// Exists = an existing field group has the same slug as the group we are adding
	foreach ($field_groups as $oneGroup) {	
		if ($oneGroup["slug"] == $slug && !empty($slug)) {
			// Field group already exists
			$field_group_id = $oneGroup["id"];
			// We found our group, no need to loop further
			break;
		} else if (!isset($field_group_id) && $oneGroup["id"] > $highest_id) {
			// We have not found a field id yet and the id of the current group is higher than the current highest id
			$highest_id = $oneGroup["id"];
		}
	}

	// If the highest id is not found or not numeric
	if (!isset($field_group_id) || !is_numeric($field_group_id)) {
		if (!empty($field_groups[$highest_id]) || $highest_id > 0) {
			$highest_id++;
		}
		$field_group_id = $highest_id;
	}

	// If $highest_id = 0 here then this is a new field group that is created
	// So the first group gets id 1
	if ($highest_id === 0) {
		$field_group_id = 1;
	}

	// Set default values for slug and name
	if (empty($slug)) {
		// Make sure that the field group gets a slug
		$slug = "field_group_" . $field_group_id;
	} else if (!isset($new_field_group["name"]) || empty($new_field_group["name"])) {
		// If no name is given the field group, use the slug as name
		$new_field_group["name"] = $slug;
	}
	
	// make sure slug is valid
	$slug = sanitize_key($slug);

	if (!isset($field_groups[$field_group_id])) {
		// Set up default values if this is a new field group
		$field_group_defaults = array(
			"id" => $field_group_id,
			"key" => $slug,
			"slug" => $slug,
			"name" => "Unnamed field group $field_group_id",
			"description" => "",
			"repeatable" => false,
			"fields" => array(),
			"deleted" => false
		);
	} else {
		// This is an existing field group so get the old values
		$field_group_defaults = $field_groups[$field_group_id];
	}

	// Merge the new values of this field group with the old values
	// Let the new values overwrite the hold ones
	// @done: Should not http://codex.wordpress.org/Function_Reference/wp_parse_args work for this?
	// answer: wp_parse_args does not work with multi dimensional arrays
	$field_groups[$field_group_id] = simple_fields_merge_arrays($field_group_defaults, $new_field_group);

	// If the field group has an array of fields
	if (isset($new_field_group["fields"]) && is_array($new_field_group["fields"]) && !empty($new_field_group["fields"])) {

		$fields = array();
		$field_id = 0;

		// Loop through all fields
		// somewhwere here added fields from gui disapears
		foreach($new_field_group["fields"] as $one_new_field) {

			// Set up default values for this field
			$fields[$field_id] = array();
			$field_slug = "field_$field_id";
			$field_defaults = array(
					"name"        => "",
					"slug"        => $field_slug,
					"description" => "",
					"type"        => "",
					"type_post_options" => array(
						"enabled_post_types" => array(), 
						"additional_arguments" => ""
					),
					"type_taxonomyterm_options" => array(
						"additional_arguments" => ""
					),
					"id"      => "",
					"deleted" => 0
			);

			// If a field with this index/id exists then merge that fields values with our default values
			// so if you add one field in php, then one in the gui, and then extend the php with one more field = 
			// field from gui is overwritten since it get's the id that the php version want
			// use only slug instead and we should be fine

			#if (isset($field_groups[$field_group_id]['fields'][$field_id])) {
			#	$field_defaults = simple_fields_merge_arrays($field_defaults, $field_groups[$field_group_id]['fields'][$field_id]);
			#	sf_d($field_defaults);
			#}

			// Find id of possibly existing field using the slug
			// If existing field is found then merge old values with new
			if (isset($field_groups[$field_group_id]["fields"]) && is_array($field_groups[$field_group_id]["fields"])) {
				foreach ($field_groups[$field_group_id]["fields"] as $one_existing_field) {
	
					if ($one_existing_field["slug"] == $one_new_field["slug"]) {
						// Found existing field with same slug
						// Merge new field values with the old values, so $field_defaults will have the combines values
						$field_defaults = simple_fields_merge_arrays($field_defaults, $one_existing_field);
					}
	
				}
			}

			// Do wierd stuff with field default values
			foreach($field_defaults as $oneDefaultFieldKey => $oneDefaultFieldValue) {

				if ($oneDefaultFieldKey == "id") {
					$fields[$field_id]["id"] = $field_id;
				} else {
					if (!isset($one_new_field[$oneDefaultFieldKey])) {
						$fields[$field_id][$oneDefaultFieldKey] = $oneDefaultFieldValue;
					}

				}

				// If the default key is an array
				if (isset($one_new_field[$oneDefaultFieldKey]) && is_array($one_new_field[$oneDefaultFieldKey]) && !empty($one_new_field[$oneDefaultFieldKey])) {

					// If this is an array with options for a field type
					// For example "type_post_options" or "type_taxonomyterm_options"
					$options_type = preg_replace("/type_([a-z]+)_options/i", '$1', $oneDefaultFieldKey);
					if (!empty($options_type)) {

						foreach(array_keys($one_new_field[$oneDefaultFieldKey]) as $optionKey) {

							if (is_numeric($optionKey)) {
								$newOptionKey = $options_type . "_num_" . $optionKey;
								$fields[$field_id][$oneDefaultFieldKey][$newOptionKey] = $one_new_field[$oneDefaultFieldKey][$optionKey];
								unset($fields[$field_id][$oneDefaultFieldKey][$optionKey]);
								$optionKey = $newOptionKey;
							}
							if (isset($fields[$field_id][$oneDefaultFieldKey][$optionKey]) && is_array($fields[$field_id][$oneDefaultFieldKey][$optionKey]) && !empty($fields[$field_id][$oneDefaultFieldKey][$optionKey]["value"])) {
								if (!isset($fields[$field_id][$oneDefaultFieldKey][$optionKey]["deleted"])) {
									$fields[$field_id][$oneDefaultFieldKey][$optionKey]["deleted"] = 0;
								}
							}

						} // foreach

					}
				} // foreach

				if (!isset($fields[$field_id][$oneDefaultFieldKey])) {
					$fields[$field_id][$oneDefaultFieldKey] = $oneDefaultFieldValue;
				}
			}

			$field_id++;

		}


		//$field_groups[$field_group_id]["fields"] = $fields;
		// Merge together new fields with old ones
		// If no merge then fields added in gui are lost
		// @todo: field order is lost, here i think
		// $org_field_group = $field_groups[$field_group_id]["fields"];
		#sf_d($fields);
		#sf_d($field_groups[$field_group_id]["fields"]);
		// ordningen är ok innan denna merge

		// array_merge_recursive = appendar eftersom key är int. vi fixar genom att göra om int till string. nasty.
/*$existing_fields_copy = array();
foreach ($field_groups[$field_group_id]["fields"] as $key => $val) {
	$existing_fields_copy["_$key"] = $val;
}*/

#sf_d($fields_copy);
#sf_d($existing_fields_copy);


		$merged_fields = simple_fields_merge_arrays($fields, $field_groups[$field_group_id]["fields"]);

		// Mergin is done, but we want to get our old order back now...
		// $field_groups[$field_group_id]["fields"] <- order is correct here
		$keys_org_order = array_keys($field_groups[$field_group_id]["fields"]);
		$keys_possibly_wrong_but_new_order = array_keys($merged_fields);
		$keys_all_in_new_and_org_combined = $keys_org_order + $keys_possibly_wrong_but_new_order; // <- this is the order we want it in
		$merged_fields_correct_order = array();
		foreach ($keys_all_in_new_and_org_combined as $order_key) {
			$merged_fields_correct_order[$order_key] = $merged_fields[$order_key];
		}

		// $field_groups[$field_group_id]["fields"] = simple_fields_merge_arrays($fields, $field_groups[$field_group_id]["fields"]);

		// Finally use the new fields
		$field_groups[$field_group_id]["fields"] = $merged_fields_correct_order;


	} // if passed as arg field group has fields

	update_option("simple_fields_groups", $field_groups);
	$sf->clear_caches();

	// Re-get the field so it's the same as when getting a field group manually
	return $sf->get_field_group_by_slug($slug);

	// return $field_groups[$field_group_id];

}

/**
 * @todo: documentation
 */
function simple_fields_register_post_connector($unique_name = "", $new_post_connector = array()) {

	global $sf;

	$post_connectors = $sf->get_post_connectors();

	$highest_connector_id = 0;
	foreach ($post_connectors as $oneConnector) {
		if ($oneConnector["key"] == $unique_name && !empty($unique_name)) {
			// Connector already exists
			$connector_id = $oneConnector["id"];
		} else if (!isset($connector_id) && $oneConnector["id"] > $highest_connector_id) {
			$highest_connector_id = $oneConnector["id"];
		}
	}

	if (!isset($connector_id) || !is_numeric($connector_id)) {
		if (!empty($post_connectors[$highest_connector_id]) || $highest_connector_id > 0) {
			$highest_connector_id++;
		}
		$connector_id = $highest_connector_id;
	}

	// If $connector_id is 0 here then it's the first ever created
	// But 0 is the id to tell SF to create new, so we must up it to 1
	if ($connector_id === 0) $connector_id = 1;

	if (empty($unique_name)) {
		$unique_name = "post_connector_" . $connector_id;
	} else if (!isset($new_post_connector["name"]) || empty($new_post_connector["name"])) {
		$new_post_connector["name"] = $unique_name;
	}

	$unique_name = sanitize_key($unique_name);

	$post_connector_defaults = array(
		"id" => $connector_id,
		"key" => $unique_name,
		"slug" => $unique_name,
		"name" => $unique_name."_".$connector_id,
		"field_groups" => array(),
		"post_types" => array(),
		"deleted" => false,
		"hide_editor" => false
	);

	if (isset($post_connectors[$connector_id])) {
		$post_connector_defaults = $post_connectors[$connector_id];
	}

	$post_connectors[$connector_id] = simple_fields_merge_arrays($post_connector_defaults, $new_post_connector);
	$post_connectors[$connector_id]['post_types'] = array_unique($post_connectors[$connector_id]['post_types']);
	
	if (isset($new_post_connector["field_groups"]) && is_array($new_post_connector["field_groups"]) && !empty($new_post_connector["field_groups"])) {
		$field_group_connectors = array();
		$field_groups = $sf->get_field_groups();
		$field_group_connector_defaults = array(
							"id" => "",
							"key" => "",
							"slug" => "",
							"name" => "",
							"deleted" => 0,
							"context" => "normal",
							"priority" => "low"

		);
		foreach($new_post_connector["field_groups"] as $field_group_options) {
			$field_group_found = false;
			foreach ($field_groups as $oneGroup) {
				if ( (isset($oneGroup["id"]) && isset($field_group_options["id"]) && $oneGroup["id"] == $field_group_options["id"]) || ( $oneGroup["key"] == $field_group_options["key"] )) {
					// Field group found
					$field_group_found = true;
					$field_group_id = $oneGroup["id"];
					$field_group_key = $oneGroup["key"];
				}
			}
			if ($field_group_found !== false) {
				if (isset($field_group_connectors[$field_group_id]) && !$field_group_connectors[$field_group_id]["deleted"]) {
					$default_field_group_connector = $field_group_connectors[$field_group_id];
				} else {
					$default_field_group_connector = $field_group_connector_defaults;
				}
				foreach($default_field_group_connector as $oneGroupConnectorDefaultKey => $oneGroupConnectorDefaultValue) {
					if ($oneGroupConnectorDefaultKey == "id") {
						$field_group_connectors[$field_group_id]["id"] = $field_group_id;
					} else if ($oneGroupConnectorDefaultKey == "key") {
						$field_group_connectors[$field_group_id]["key"] = $field_group_key;
					} else {
						if (isset($field_group_options[$oneGroupConnectorDefaultKey])) {
							$field_group_connectors[$field_group_id][$oneGroupConnectorDefaultKey] = $field_group_options[$oneGroupConnectorDefaultKey];
						} else {
							$field_group_connectors[$field_group_id][$oneGroupConnectorDefaultKey] = $oneGroupConnectorDefaultValue;
						}

					}
				}
			}
		}
		$post_connectors[$connector_id]["field_groups"] = $field_group_connectors;

	}
	
	$sf->clear_caches();
	update_option("simple_fields_post_connectors", $post_connectors);

	return $post_connectors[$connector_id];

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
	foreach ($post_connector_info["field_groups"] as $one_field_group) {

		// check number of added field groups
		$num_added_field_groups = 0; 
		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}

		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 

			// Skip deleted fields
			if ($one_field_group_field["deleted"]) continue;

			if ($field_slug === $one_field_group_field["slug"]) {

				// Found field with selected slug

				$field_group_id = $one_field_group["id"];
				$field_id = $one_field_group_field["id"];

				if (!empty($new_numInSet)) {
					$num_in_set = $new_numInSet;
				} else {
					$num_in_set = $num_added_field_groups;			        
				}
				
				update_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}", $new_value);
				update_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_added_numInSet_{$num_in_set}", 1);
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

		return $arr_comma_slugs_values;

	}

	global $sf;

	// Post connector for this post, with lots of info
	$post_connector_info = simple_fields_get_all_fields_and_values_for_post($post_id, "include_deleted=0");

	if ($post_connector_info === FALSE) {
		return FALSE;
	}

	$parsed_options = wp_parse_args($options);

	// Loop through the field groups that this post connector has and locate the field_slug we are looking for
	foreach ($post_connector_info["field_groups"] as $one_field_group) {

		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 

			//_simple_fields_fieldGroupID_23_fieldID_2_numInSet_
			#file
			#sf_d($one_field_group_field);

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
		return FALSE;
	} else {
		// connector is selected, get slug of it
		$post_connectors = $sf->get_post_connectors();
		if (!isset($post_connectors[$connector_id])) {
			return FALSE;
		} 

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
 * @param mixed $field_group_id_or_slug
 * @return mixed, but probably array, depending on how many field the group has (just one field, and not repeatable = no array for you!)
 */
function simple_fields_fieldgroup($field_group_id_or_slug, $post_id = NULL, $options = array()) {

	if (!is_numeric($post_id)) {
		global $post;
		$post_id = $post->ID;
	}

	global $sf;
	$cache_key = "simple_fields_".$sf->ns_key."_fieldgroup_" . $field_group_id_or_slug . "_" . $post_id . json_encode($options);
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
	return $values;
}

