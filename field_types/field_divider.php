<?php
/**
 * styling for the line is (well..no longer! :) taken
 * from one of the versions at
 * http://css-tricks.com/forums/discussion/10375/sexy-css3-fading-line/p1
 */
add_action("plugins_loaded", "init_simple_fields_field_divider");

function init_simple_fields_field_divider() {

	class simple_fields_field_divider extends simple_fields_field {
	
		public $key = "divider", $name = "Divider";
		
		function __construct() {
			parent::__construct();
			
			// add some styling in the admin head
			add_action('admin_head', array($this, 'action_admin_head'));
		}
		
		/**
		 *  Output CSS in admin head
		 */
		function action_admin_head() {
			?>
			<style>
				.simple-fields-fieldgroups-field-type-divider label {
					display: none;
				}

				.simple-fields-fieldgroups-field-type-divider-line {
					margin: 1em 0;
				    border: 0;
				    border-top: 1px solid #DFDFDF;
				    border-bottom: 1px solid #FFF;
				    height: 0;
				    background: #DFDFDF;
				}
				
				.simple-fields-fieldgroups-field-type-divider-white_space {
				 	height: 1em;
				}
			</style>
			<?php
		}
		
		function options_output($existing_vals = array()) {
			return sprintf('
			<div class="simple-fields-field-group-one-field-row-col-first">
				<p>
					<label>%1$s</label>
				</p>
			</div>
			<div class="simple-fields-field-group-one-field-row-col-second">
				<p>
					<select name="%2$s">
						<option %6$s value="line">%4$s</option>
						<option %5$s value="white_space">%3$s</option>
					</select>
				</p>
				<!-- <p>					
					<span class="description">%7$s</span>
				</p>-->
			</div>
				',
				_x("Appearance", "Divider field type", "simple-fields"),
				$this->get_options_name("appearance"), 
				_x("White space", "Divider field type", "simple-fields"),
				_x("Line", "Divider field type", "simple-fields"), // 4
				isset($existing_vals["appearance"]) && $existing_vals["appearance"] == "white_space" ? " selected " : "",
				isset($existing_vals["appearance"]) && $existing_vals["appearance"] == "line" ? " selected " : "",
				_x("Select the look of the divider", "Divider field type", "simple-fields")
			);
		}
		
		function edit_output($saved_values, $options) {
			$output = sprintf(
				'<div class="%1$s"></div>',
				$this->get_class_name($options["appearance"])
			);
			#sf_d($options);
			return $output;
			//return sprintf('<input type="text" name="%1$s" value="%2$s">', $this->get_options_name("name"), empty($saved_values["name"]) ? esc_attr($options["textDefaultName"]) : esc_attr($saved_values["name"]));		
		}			

	}

	simple_fields::register_field_type("simple_fields_field_divider");

}

