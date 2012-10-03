<?php

/**
 * Class that represents a Simple Fields Field
 * Extend this class to add your own fields. See field_types/example.php
 */ 
class simple_fields_field {

	public
		$key         = "", // Unique key for this field type. just a-z please, no spaces or funky stuff. don't change this once set.
		$name        = "", // The name that users will see.
		$description = "" // A longer description. Not used right now...
		;

	private
		// Variables used as base when outputing form fields on options page
		$options_base_id,
		$options_base_name
		;

	function __construct() {
	}

	/**
	 * Output on options screen
	 * @return string
	 */
	function options_output() {
		return "<p>Please add method ".__METHOD__."().</p>";
	}
	function options_save() {
		// should we be able to hook onto to save process?
	}

	/**
	 * Output fields and stuff on post edit page
	 * This is the output a regular user will see
	 *
	 * @param array $saved_values key => value with saved values
	 * @param array $options array with the options that are set for this field in the options screen
	 * @return string
	 */
	function edit_output($saved_value, $options) {
		return "<p>Please add method " . __METHOD__ . "().</p>";
	}
	
	/**
	 * Sets the base for the generation of input ids in options screen
	 * Called by options screen.
	 * @param string $string
	 */
	function set_options_base_id($string) {
		$this->options_base_id = $string;
	}

	/**
	 * Sets the base for the generation of input names in options screen
	 * Called by options screen.
	 * @param string $string;
	 */
	function set_options_base_name($string) {
		$this->options_base_name = $string;
	}
	
	/**
	 * Get the id to use in input or label or similiar to be used in options screen
	 * @param $name a-z
	 * @return string 
	 */
	function get_options_id($name) {
		return $this->options_base_id . "_$name";
	}

	/**
	 * Get the name to use in input or label or similiar to be used in options screen
	 * @param $name a-z
	 * @return string 
	 */
	function get_options_name($name) {
		return $this->options_base_name . "[$name]";
	}
	
	/**
	 * Possibly modify values before returning them
	 * Used from functions simple_fields_value and simple_fields_values
	 * $values is an array beginning at 0, for each field,
	 * so loop to change all your values (there are several if using repeatable)
	 */
	function return_values($values) {
		// Simply return values if not redefined by child class
		// Thought: to make it more work like core/legacy plugins, let's return the first thing if only one thing exists
		// Or always, as long as developer does not haz overridz the methodz
		foreach ($values as &$one_value) {
			if (sizeof($one_value) == 1) {
				$one_value = current($one_value);
			}
		}
		return $values;
	}

} // class
