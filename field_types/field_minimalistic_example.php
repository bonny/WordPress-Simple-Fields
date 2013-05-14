<?php

add_action("simple_fields_register_field_types", "init_simple_fields_field_minimalexample");

function init_simple_fields_field_minimalexample() {

	class simple_fields_field_minimalexample extends simple_fields_field {
	
		public $key = "minimalexample", $name = "Minimalistic example field";
		
		function __construct() {
			parent::__construct();
		}
		
		function options_output($existing_vals) {
			return sprintf('
				<p>
					<label>Default name</label>
					<span class="description">Enter a name that will be shown as default</span>
					<input type="text" name="%1$s" value="%2$s">
				</p>',
				$this->get_options_name("textDefaultName"), isset($existing_vals["textDefaultName"]) ? esc_attr($existing_vals["textDefaultName"]) : ""
			);
		}
		
		function edit_output($saved_values, $options) {
			return sprintf('<input type="text" name="%1$s" value="%2$s">', $this->get_options_name("name"), empty($saved_values["name"]) ? esc_attr($options["textDefaultName"]) : esc_attr($saved_values["name"]));		
		}			

	}

	simple_fields::register_field_type("simple_fields_field_minimalexample");

}
