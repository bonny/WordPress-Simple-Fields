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
	} else {
		echo $var;
	}
	echo "</pre>";
}
}


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
					$is_found = true;
				} else if (!$fetch_by_id && (intval($one_field_group["id"]) === intval($field_group_id)) && (intval($one_field["id"]) === intval($field_id))) {
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
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id) {
	
	global $sf;
	
	$post                     = get_post($post_id);
	$connector_to_use         = $sf->get_selected_connector_for_post($post);
	$existing_post_connectors = $sf->get_post_connectors();
	$field_groups             = $sf->get_field_groups();
	$selected_post_connector  = $existing_post_connectors[$connector_to_use];
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
 * @param string $unique_name
 * @param array $new_field_group settings/options for the new group
 * @param return the new field group as an array
 */
function simple_fields_register_field_group($unique_name = "", $new_field_group = array()) {
	
	global $sf;
	
	$field_groups = $sf->get_field_groups();
	
	$highest_id = 0;

	foreach ($field_groups as $oneGroup) {
		if ($oneGroup["key"] == $unique_name && !empty($unique_name)) {
			// Field group already exists

			// @todo: but if we don't have a unique name when we are creating a new connector
			// and there are old connectors with no names either

			$field_group_id = $oneGroup["id"];
		} else if (!isset($field_group_id) && $oneGroup["id"] > $highest_id) {
			$highest_id = $oneGroup["id"];
		}
	}

	if (!isset($field_group_id) || !is_numeric($field_group_id)) {
		if (!empty($field_groups[$highest_id]) || $highest_id > 0) {
			$highest_id++;
		}
		$field_group_id = $highest_id;
	}

	// if $highest_id = 0 here then this is a new field group that is created
	// the first group gets id 1
	if ($highest_id === 0) {
		$field_group_id = 1;
	}
	
	if (empty($unique_name)) {
		$unique_name = "field_group_" . $field_group_id;
	} else if (!isset($new_field_group["name"]) || empty($new_field_group["name"])) {
		$new_field_group["name"] = $unique_name;
	}
	
	if (!isset($field_groups[$field_group_id])) {
		$field_group_defaults = array(
			"id" => $field_group_id,
			"key" => $unique_name,
			"name" => "Unnamed field group $field_group_id",
			"description" => "",
			"repeatable" => false,
			"fields" => array(),
			"deleted" => false
		);
	} else {
		$field_group_defaults = $field_groups[$field_group_id];
	}
	
	$field_groups[$field_group_id] = simple_fields_merge_arrays($field_group_defaults, $new_field_group);

	if (isset($new_field_group["fields"]) && is_array($new_field_group["fields"]) && !empty($new_field_group["fields"])) {
		$fields = array();
		$field_id = 0;
		foreach($new_field_group["fields"] as $oneField) {
			$fields[$field_id] = array();
			
			$field_defaults = array(
					"name" => "",
					"description" => "",
					"type" => "",
					"type_post_options" => array("enabled_post_types" => array(), "additional_arguments" => ""),
					"type_taxonomyterm_options" => array("additional_arguments" => ""),
					"id" => "",
					"deleted" => 0
			);
			
			if (isset($field_groups[$field_group_id]['fields'][$field_id])) {
				$field_defaults = simple_fields_merge_arrays($field_defaults, $field_groups[$field_group_id]['fields'][$field_id]);
			}
			
			foreach($field_defaults as $oneDefaultFieldKey => $oneDefaultFieldValue) {
				if ($oneDefaultFieldKey == "id") {
					$fields[$field_id]["id"] = $field_id;
				} else {
					if (!isset($oneField[$oneDefaultFieldKey])) {
						$fields[$field_id][$oneDefaultFieldKey] = $oneDefaultFieldValue;
					}
					
				}
				if (isset($oneField[$oneDefaultFieldKey]) && is_array($oneField[$oneDefaultFieldKey]) && !empty($oneField[$oneDefaultFieldKey])) {
					$options_type = preg_replace("/type_([a-z]+)_options/i", '$1', $oneDefaultFieldKey);
					if (!empty($options_type)) {
						foreach(array_keys($oneField[$oneDefaultFieldKey]) as $optionKey) {
							//echo "<pre>" . print_r($fields[$field_id][$oneDefaultFieldKey][$optionKey], true) . "</pre>";
							if (is_numeric($optionKey)) {
								$newOptionKey = $options_type . "_num_" . $optionKey;
								$fields[$field_id][$oneDefaultFieldKey][$newOptionKey] = $oneField[$oneDefaultFieldKey][$optionKey];
								unset($fields[$field_id][$oneDefaultFieldKey][$optionKey]);
								$optionKey = $newOptionKey;
							}
							if (isset($fields[$field_id][$oneDefaultFieldKey][$optionKey]) && is_array($fields[$field_id][$oneDefaultFieldKey][$optionKey]) && !empty($fields[$field_id][$oneDefaultFieldKey][$optionKey]["value"])) {
								if (!isset($fields[$field_id][$oneDefaultFieldKey][$optionKey]["deleted"])) {
									$fields[$field_id][$oneDefaultFieldKey][$optionKey]["deleted"] = 0;
								}
							}
							
						}
					}
				}
				if (!isset($fields[$field_id][$oneDefaultFieldKey])) {
					$fields[$field_id][$oneDefaultFieldKey] = $oneDefaultFieldValue;
				}
			}
			$field_id++;
		}
		$field_groups[$field_group_id]["fields"] = $fields;
	}

	update_option("simple_fields_groups", $field_groups);
	
	return $field_groups[$field_group_id];
	
}

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
	
	$post_connector_defaults = array(
		"id" => $connector_id,
		"key" => $unique_name,
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
	
	if (isset($new_post_connector["field_groups"]) && is_array($new_post_connector["field_groups"]) && !empty($new_post_connector["field_groups"])) {
		$field_group_connectors = array();
		$field_groups = $sf->get_field_groups();
		$field_group_connector_defaults = array(
							"id" => "",
							"key" => "",
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

	simple_fields::debug("simple_fields_register_post_type_default()", array("post_type_connector" => $connector_id_or_special_type, "post_type" => $post_type));

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
	
	$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");

	$post_type_defaults[$post_type] = $connector_id_or_special_type;
	if (isset($post_type_defaults[0])) {
		unset($post_type_defaults[0]);
	}

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

		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 
			
			// Skip deleted fields
			if ($one_field_group_field["deleted"]) continue;
			
			if ($field_slug === $one_field_group_field["slug"]) {
				
				// Found field with selected slug
				
				$field_group_id = $one_field_group["id"];
				$field_id = $one_field_group_field["id"];
				
				// check number of added field groups
		        $num_added_field_groups = 0; 
		        while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
		            $num_added_field_groups++;
		        }
		        
		        if (empty($new_numInSet)) {
			        $num_in_set = $new_numInSet;
		        } else {
			        $num_in_set = $num_added_field_groups;			        
		        }

				update_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}", $new_value);
				update_post_meta($post_id, "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_added_numInSet_{$num_in_set}", 1);

				// value updated. exit function.
				return;

			} // if
			
		} // foreach
		
	} // foreach
}


/**
 * Gets a single value.
 * The first value if field group is repeatable
 */
function simple_fields_value($field_slug = NULL, $post_id = NULL, $options = NULL) {
	$values = simple_fields_values($field_slug, $post_id, $options);
	$value = $values[0];
	return $value;
}

/**
 * Gets all values as array
 */
function simple_fields_values($field_slug = NULL, $post_id = NULL, $options = NULL) {
	
	if (empty($field_slug)) {
		return FALSE;
	}
	
	if (is_null($post_id)) {
		$post_id = get_the_ID();
	}
	
	global $sf;
	
	// Post connector for this post, with lots of info
	$post_connector_info = simple_fields_get_all_fields_and_values_for_post($post_id);
#sf_d($post_connector_info);
	// Loop through the field groups that this post connector has and locate the field_slug we are looking for
	foreach ($post_connector_info["field_groups"] as $one_field_group) {
		// Loop the fields in this field group
		foreach ($one_field_group["fields"] as $one_field_group_field) { 
			
			// Skip deleted fields
			if ($one_field_group_field["deleted"]) continue;
			
			if ($field_slug === $one_field_group_field["slug"]) {
				
				// Slug is found. Get and return values.
				$saved_values = $one_field_group_field["saved_values"];
				
				// If no values just return
				if (!sizeof($saved_values)) return;
				
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
				// If first element is an array then it's a new cool and funky custom field value
				// Oups, some old types have arrays to, like taxonomyterm
				// So this should work: if the type is among the registered_field_types then use it
				if (isset($sf->registered_field_types[$one_field_group_field["type"]]) && is_array($saved_values[0])) {

					// custom field type. should it be responsible for the return of the values?
					// some fields may have several stuff entered in the. some just one thing. we don't want to guess!
					
					// Use the custom field object to output this value, since we can't guess how the data is supposed to be used
					$custom_field_type = $sf->registered_field_types[$one_field_group_field["type"]];
					$saved_values = $custom_field_type->return_values($saved_values, $options);

				} else {
					
					// legacy/core field type

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

	
}



// Some debug functions
if (simple_fields::DEBUG_ENABLED) {

	// Outputs the names of the post connectors attached to the post you view + outputs the values
	add_filter("the_content", "simple_fields_value_get_functions_test");
	function simple_fields_value_get_functions_test($content) {
	
		$post_connector_with_values = simple_fields_get_all_fields_and_values_for_post(get_the_ID());
		if ($post_connector_with_values) {
			foreach ($post_connector_with_values["field_groups"] as $one_field_group) {
				if ($one_field_group["deleted"]) continue;
				foreach ($one_field_group["fields"] as $one_field) {
					if ($one_field["deleted"]) continue;
					#$content .= "<hr>";
					$content .=  "<br>Found Simple Fields Field:";
					$content .=  " name: <b>" . $one_field["name"] . "</b>";
					$content .=  " | type: <b>" . $one_field["type"] . "</b>";
					if (isset($one_field["slug"])) {
						$content .=  " | slug: <b>" . $one_field["slug"] . "</b>";
						#echo '<br>function to use to get first/one value:';
						#echo '<br><code>simple_fields_value("'.$one_field["slug"].'");</code>';
						#echo '<br>function to use to get all values, as array:';
						#echo '<br><code>simple_fields_values("'.$one_field["slug"].'");</code>';
						#echo "<br><b>saved values</b>:";
						#sf_d($one_field["saved_values"]);
						
						$content .= "<br>Use <code><b>simple_fields_values('".$one_field["slug"]."')</b></code> to get:";
						ob_start();
						sf_d( simple_fields_values($one_field["slug"]) );
						$content .= ob_get_clean();
		
						$content .= "<br>Use <code><b>simple_fields_value('".$one_field["slug"]."')</b></code> to get:";
						ob_start();
						sf_d( simple_fields_value($one_field["slug"]) );
						$content .= ob_get_clean();
					} else {
						$content .= "<br>No slug for this field found (probably old field that has not been edited and saved).";
					}
					
				}
				#$fieldgroup_values = simple_fields_get_post_group_values(get_the_ID(), $one_field_group["id"], false, 2);
				#echo "<p>Simple Fields, Field Group name: <b>" . $one_field_group["name"] . "</b></p>";
				#echo "<p>Values in this Field Group:</p>";
				#sf_d($fieldgroup_values);
			}
		}
		
		return $content;
	}

}

