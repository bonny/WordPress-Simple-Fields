<?php
/**
 *
 *
 */
add_action("simple_fields_register_field_types", "init_simple_fields_field_date_v2");

function init_simple_fields_field_date_v2() {

	class simple_fields_field_date_v2 extends simple_fields_field {
	
		public $key = "date_v2", $name = "Datepicker 2";
		
		function __construct() {

			parent::__construct();
			
			add_action('admin_head', array($this, 'action_admin_head'));    
		    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

		}

		/**
		 * Load scripts and styles
		 */
		function enqueue_scripts() {
			// https://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css

			// Load jquery styles using method found here:
			// http://snippets.webaware.com.au/snippets/load-a-nice-jquery-ui-theme-in-wordpress/
			global $wp_scripts;
			wp_enqueue_script('jquery-ui-datepicker');
			$ui = $wp_scripts->query('jquery-ui-core');
			$url = "https://ajax.aspnetcdn.com/ajax/jquery.ui/{$ui->ver}/themes/smoothness/jquery.ui.all.css";
			wp_enqueue_style('jquery-ui-smoothness', $url, false, $ui->ver);


		}
		
		/**
		 *  Output CSS in admin head
		 */
		function action_admin_head() {
			?>
			<style>
				.simple-fields-fieldgroups-field-type-date_v2-gui-date {
					width: 10em;
				}
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
				<div class="simple-fields-field-group-one-field-row">
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
				</div>
				',
				_x("Show", "Date v2 field type", "simple-fields"),
				$this->get_options_name("show"), 
				_x("Always", "Date v2 field type", "simple-fields"),	// 3
				_x("On click", "Date v2 field type", "simple-fields"), 	// 4
				( (isset($existing_vals["show"]) && $existing_vals["show"] == "always") || !isset($existing_vals["show"]) ) ? " checked " : "", 	// 5
				isset($existing_vals["show"]) && $existing_vals["show"] == "on_click" ? " checked " : "" 	// 6
			);


			// Default date
			//  - No date
			//  - Todays date
			$out .= sprintf('
				<div class="simple-fields-field-group-one-field-row">
					<div class="simple-fields-field-group-one-field-row-col-first">
						<p>
							<label>%1$s</label>
						</p>
					</div>
					<div class="simple-fields-field-group-one-field-row-col-second">
						<p>
							<label>
								<input type="radio" name="%2$s" value="today" %6$s>
								%4$s
							</label>
							<label>
								<input type="radio" name="%2$s" value="no_date" %5$s>
								%3$s
							</label>
						</p>
					</div>
				</div>
				',
				_x("Default date", "Date v2 field type", "simple-fields"),
				$this->get_options_name("default_date"), 
				_x("No date", "Date v2 field type", "simple-fields"),	// 3
				_x("Todays date", "Date v2 field type", "simple-fields"), 	// 4
				((isset($existing_vals["default_date"]) && $existing_vals["default_date"] == "no_date") || !isset($existing_vals["default_date"])) ? " checked " : "", 	// 5
				isset($existing_vals["default_date"]) && $existing_vals["default_date"] == "today" ? " checked " : "" 	// 6
			);


			// Date format
			// http://docs.jquery.com/UI/Datepicker/formatDate
			$out .= sprintf('
				<div class="simple-fields-field-group-one-field-row">
					<div class="simple-fields-field-group-one-field-row-col-first">
						<p>
							<label>%1$s</label>
						</p>
					</div>
					<div class="simple-fields-field-group-one-field-row-col-second">
						<p>
							<select name="%2$s">
								<option value="MM d, yy" %4$s>%3$s</option>
								<option value="yy-mm-dd" %6$s>%5$s</option>
								<option value="mm/dd/yy" %8$s>%7$s</option>
							</select>
						</p>
					</div>
				</div>
				',
				_x("Date format", "Date v2 field type", "simple-fields"),
				$this->get_options_name("date_format"), 
				date("F j, Y"),	// 3 - MM d, yy
				isset($existing_vals["date_format"]) && $existing_vals["date_format"] == "MM d, yy" ? " selected " : "",
				date("Y-m-d") . " (ISO 8601)", 	// 4 - yy-mm-dd
				isset($existing_vals["date_format"]) && $existing_vals["date_format"] == "yy-mm-dd" ? " selected " : "",
				date("m-d-Y"), // default 11/01/2012 	5 -	mm/dd/yy,
				isset($existing_vals["date_format"]) && $existing_vals["date_format"] == "mm/dd/yy" ? " selected " : ""
			);

			return $out;

		}
		
		function edit_output($saved_values, $options) {

			#echo "Saved values:";sf_d($saved_values);

			if (isset($saved_values[0])) {
				$saved_values["date_unixtime"] = $saved_values[0];
			} else {
				$saved_values["date_unixtime"] = "";
			}

			// When to show: always or on_click
			$str_target_elm = "";
			if ($options["show"] === "always") {
				$str_target_elm = '<div id="%1$s"></div>';
			} elseif ("on_click" === $options["show"]) {
				$str_target_elm = '<input class="%9$s" type="text" id="%1$s" name="%2$s" value="">';
			}

			// if new field = use default date
			$str_saved_unixtime = "";
			$str_set_date = "";
			$str_unixtime_to_set = "";
			if (isset($options["use_defaults"]) && $options["use_defaults"]) {
				if ($options["default_date"] === "today") {
					$str_unixtime_to_set = time() * 1000;
				} elseif ($options["default_date"] === "no_date") {
					
				}
			} else {
				$str_saved_unixtime = $saved_values["date_unixtime"];
				$str_unixtime_to_set = $str_saved_unixtime * 1000;
			}

			if ($str_unixtime_to_set) {
				// If saved value exists then set date to this value on load
				// The display: none-thingie is added beause the date picker get shown by setDate-method
				// Unsure if bug or feature, but annoying anyway.
				$str_set_date = '
					var date_saved = new Date('.$str_unixtime_to_set.');
					$( "#%1$s" ).datepicker("setDate", date_saved);
					$( "#ui-datepicker-div" ).css("display","none");
				';
			}

			// Set Date Format
			$str_date_format = "ISO_8601";
			if (isset($options["date_format"])) $str_date_format = $options["date_format"];

			// First day. 0 = sunday, 1 = monday
			// Use same as in wordpress
			$str_first_day = get_option("start_of_week", 1);

			$output = sprintf(
				'
				
					'.$str_target_elm.'

					<input type="hidden" id="%3$s" name="%4$s" value="%6$s">
					
					<script>
						jQuery(function($) {
							$( "#%1$s" ).datepicker({
								altField: "#%3$s",
								altFormat: "@",
								showWeek: true,
								dateFormat: "%7$s",
								firstDay: %8$s,
								changeYear: true,
								changeMonth: true,
								xshowOn: "both",
								autoSizeType: true
								%5$s
							});
							
							'.$str_set_date.'

						});
					</script>
				',
				$this->get_options_id("gui_selected_date"),
				$this->get_options_name("gui_selected_date"),
				$this->get_options_id("date_unixtime"),
				$this->get_options_name("date_unixtime"),
				"", // 5
				$str_saved_unixtime, // 6
				$str_date_format,
				$str_first_day,
				$this->get_class_name("gui-date") // 9
			);

			return $output;

		} // end options output

		/**
		 * Change so saved value is a single one, instead of array, so we can sort by the unixtime in wp_query etc.
		 */
		function edit_save($values) {
			
			#sf_d($values);
			/*
				Array
				(
				    [gui_selected_date] => November 5, 2012
				    [date_unixtime] => 1352070000000
				)
			*/
			if ( is_array($values) && isset($values["date_unixtime"]) && !empty($values["date_unixtime"]) ) {
				return ((float) $values["date_unixtime"]) / 1000;
			} else {
				return "";
			}

		}

		/**
		 * Add Extended Return Values and then return the values
		 */
		function return_values($values, $parsed_options_for_this_field) {

			// @todo: what if no value?

			foreach ($values as $key => $one_value) {
				$arr_extended = array(
					"date_unixtime" => $one_value,
					"ISO_8601" => date("c", $one_value),
					"RFC_2822" => date("r", $one_value),
					"Y-m-d" => date("Y-m-d", $one_value),
					"date_format" => date_i18n(get_option('date_format'), $one_value)
					// http://codex.wordpress.org/Function_Reference/date_i18n
					// echo date_i18n( $dateformatstring, $unixtimestamp, $gmt )
				);
				$values[$key] = $arr_extended;
			}

			return $values;

		}

	} // end class

	simple_fields::register_field_type("simple_fields_field_date_v2");

}

