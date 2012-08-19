<?php

/**
 * Functions that should be available outside the simple fields class
 */


/**
 * get all values or just the from a field in a field group
 *
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
	$connector_to_use = $sf->get_selected_connector_for_post($post);
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

