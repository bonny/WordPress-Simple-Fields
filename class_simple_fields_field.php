<?php

/**
 * Class that represents a Simple Fields Field
 * Extend this class to add your own fields. See field_types/example.php
 */ 
class simple_fields_field {

	public
		$key         = "", // Unique key for this field type. just a-z please, no spaces or funky stuff. don't change this once set.
		$name        = "", // The name that users will see.
		$description = "", // A longer description. Not used right now...
		$field_url
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
	 *
	 * @return string
	 */
	function options_output($existing_vals = array()) {
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
	 * Called when saving fields, i.e. when clicking the Publish-button on a edit post screen
	 * Was is returned from this method will be what is saved in the database,
	 * so this is the place to change from array (the default) to for example a single string value
	 * that is good for sorting.
	 * 
	 * Override this in the fields class to modify the value being saved.
	 *
	 * @param array $values The values that we receive from the post screen. 
	 *						It's the same names as the ones that has been added with $this->get_options_name()
	 * @return mixed, array or string of values to save in db
	 */
	function edit_save($values = NULL) {
		return $values;
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
	 * Return a classname prefixed with simple fields and our field type, to be used in edit post screen
	 * Use this to generate class names to make sure they don't collide with other class names in WordPress (from other plugins for example)
	 * @param string $class Name of class to append
	 */
	function get_class_name($class) {
		return "simple-fields-fieldgroups-field-type-" . $this->key . "-$class";
	}
	
	/**
	 * Possibly modify values before returning them
	 * Used from functions simple_fields_value and simple_fields_values
	 * $values is an array beginning at 0, for each field,
	 * so loop to change all your values (there are several if using repeatable)
	 *
	 * @param array @values
	 *
	 */
	function return_values($values = null, $parsed_options_for_this_field = null) {

		if (is_array($values)) {
			// Thought: to make it more work like core/legacy plugins, let's return the first thing if only one thing exists
			// Or always, as long as developer does not haz overridz the methodz
			foreach ($values as &$one_value) {

				if (is_array($one_value)) {
					if (sizeof($one_value) == 1) {
						$one_value = current($one_value);
					}
				} else {
					// value is not array, then let it be
				}
			}
		} else {
			// Not an array
		}

		return $values;

	}

	/**
	 * @todo: fix this, i'm to stupid to get it to work atm
	 * Returns the URL to the directory where this field type is located
	 * @return string path, for example "http://playground.ep/wordpress/wp-content/plugins/field_types/"
	 */
	 /*
	function get_url() {

		// This is the funky way I do it so it works with my symlinks
		$classinfo = new ReflectionClass($this);
		$filename = $classinfo->getFileName();
		$this->field_url = plugins_url(basename(dirname($filename))) . "/";
		sf_d( plugins_url($filename, basename(dirname($filename))) );
		sf_d( $filename );
		sf_d($classinfo);
		sf_d($classinfo->getParentClass());
		return $this->field_url;
		
	}
	*/

	

	// Add admin scripts that the the plugin uses
	/*
	add_action("admin_enqueue_scripts", function() use ($plugin_url) {
		wp_enqueue_script( "simple-fields-googlemaps", $plugin_url . "scripts.js" );
		wp_enqueue_style( "simple-fields-googlemaps", $plugin_url . "style.css" );
	});
	*/

} // class
