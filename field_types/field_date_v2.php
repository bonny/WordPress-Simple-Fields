<?php
/**
 *
 *
 */
add_action("simple_fields_register_field_types", "init_simple_fields_field_date_v2");

function init_simple_fields_field_date_v2() {

	class simple_fields_field_date_v2 extends simple_fields_field {
	
		public $key = "date_v2", $name = "Date v2";
		
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
			</style>
			<?php
		}
		
		/**
		 * Output options for the date field
		 * We use jquery ui date picker, so we should be able to customize it a bit
		 *
		 * Things to inlcude to start with:
		 *  - Show on click or always
		 *  - Date format
		 *  - Default date
		 *  - 
		 */
		function options_output($existing_vals) {

			$out = "";

			// Show on click or always
			$out .= sprintf('
				<div class="simple-fields-field-group-one-field-row-col-first">
					<p>
						<label>%1$s</label>
					</p>
				</div>
				<div class="simple-fields-field-group-one-field-row-col-second">
					<p>
						<label>
							<input type="radio" name="%2$s" value="always" %5$s>
							%3$s
						</label>
						<label>
							<input type="radio" name="%2$s" value="on_click" %6$s>
							%4$s
						</label>
					</p>
				</div>
				',
				_x("Show", "Date v2 field type", "simple-fields"),
				$this->get_options_name("show"), 
				_x("Always", "Date v2 field type", "simple-fields"),	// 3
				_x("On click", "Date v2 field type", "simple-fields"), 	// 4
				isset($existing_vals["show"]) && $existing_vals["show"] == "always" ? " checked " : "", 	// 5
				isset($existing_vals["show"]) && $existing_vals["show"] == "on_click" ? " checked " : "" 	// 6
			);

			// Show on click or always
			$out .= sprintf('
				<div class="simple-fields-field-group-one-field-row-col-first">
					<p>
						<label>%1$s</label>
					</p>
				</div>
				<div class="simple-fields-field-group-one-field-row-col-second">
					<p>
						<label>
							<input type="radio" name="%2$s" value="always" %5$s>
							%3$s
						</label>
						<label>
							<input type="radio" name="%2$s" value="on_click" %6$s>
							%4$s
						</label>
					</p>
				</div>
				',
				_x("Show", "Date v2 field type", "simple-fields"),
				$this->get_options_name("show"), 
				_x("Always", "Date v2 field type", "simple-fields"),	// 3
				_x("On click", "Date v2 field type", "simple-fields"), 	// 4
				isset($existing_vals["show"]) && $existing_vals["show"] == "always" ? " checked " : "", 	// 5
				isset($existing_vals["show"]) && $existing_vals["show"] == "on_click" ? " checked " : "" 	// 6
			);


			return $out;

		}
		
		function edit_output($saved_values, $options) {
			$output = sprintf(
				'<div class="%1$s"></div>',
				@$this->get_class_name($options["appearance"])
			);

			return $output;

		}			

	}

	simple_fields::register_field_type("simple_fields_field_date_v2");

}

