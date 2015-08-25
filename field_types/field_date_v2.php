<?php
/**
 *
 *
 */
add_action("simple_fields_register_field_types", "init_simple_fields_field_date_v2");

function init_simple_fields_field_date_v2() {

	class simple_fields_field_date_v2 extends simple_fields_field {
	
		public $key = "date_v2", $name = "Date & Time Picker";
		
		function __construct() {

			parent::__construct();
			
			add_action('simple_fields_admin_head', array($this, 'action_admin_head'));    
		    add_action('simple_fields_enqueue_scripts', array($this, 'enqueue_scripts'));

		}

		/**
		 * Load scripts and styles
		 */
		function enqueue_scripts() {

			wp_enqueue_script('jquery-ui-datepicker');
			
			// Styles for datepicker
			wp_enqueue_style("jquery-ui-datepicker-theme", SIMPLE_FIELDS_URL . "datepicker/jquery-ui-base/jquery.ui.theme.css");			
			wp_enqueue_style("jquery-ui-datepicker", SIMPLE_FIELDS_URL . "datepicker/jquery-ui-base/jquery.ui.datepicker.css");
			
			// Language files
			wp_enqueue_script("jquery-ui-18n", SIMPLE_FIELDS_URL . "datepicker/jquery-ui-i18n.min.js", array("jquery-ui-datepicker"));

			// Timepicker
			wp_enqueue_script("jquery-ui-timepicker", SIMPLE_FIELDS_URL . "js/jquery-ui-timepicker-addon.js", array("jquery-ui-datepicker"));
			wp_enqueue_script("jquery-ui-slideraccess", SIMPLE_FIELDS_URL . "js/jquery-ui-sliderAccess.js", array("jquery-ui-timepicker"));
			wp_enqueue_style("jquery-ui-timepicker", SIMPLE_FIELDS_URL . "js/jquery-ui-timepicker-addon.css");

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
		function options_output($existing_vals = array()) {

			$out = "";

			// Type: date & time, only date, only time
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
								<option value="date" %6$s>
									%3$s
								</option>
								<option value="time" %7$s>
									%4$s
								</option>
								<option value="datetime" %8$s>
									%5$s
								</option>
							</select>
						</p>
					</div>
				</div>
				',
				_x("Show picker as", "Date v2 field type", "simple-fields"),
				$this->get_options_name("show_as"), 
				_x("Only Date", "Date v2 field type", "simple-fields"),	// 3
				_x("Only Time", "Date v2 field type", "simple-fields"), 	// 4
				_x("Both Date & Time", "Date v2 field type", "simple-fields"), 	// 5
				isset($existing_vals["show_as"]) && $existing_vals["show_as"] == "date" ? " selected " : "", 	// 6
				isset($existing_vals["show_as"]) && $existing_vals["show_as"] == "time" ? " selected " : "", 	// 7
				isset($existing_vals["show_as"]) && $existing_vals["show_as"] == "datetime" ? " selected " : "" 	// 8
			);

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
			// No longer, since jquery datepicker uses locale for that (which is better/smarter, I think)
			// http://docs.jquery.com/UI/Datepicker/formatDate
			/*
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
			*/

			return $out;

		}
		
		/**
		 * Output datepicker and timepicker on post edit screen
		 */
		function edit_output($saved_values, $options) {


			if (isset($saved_values[0])) {
				$saved_values["saved_date_time"] = $saved_values[0];
			} else {
				$saved_values["saved_date_time"] = "";
			}

			// When to show: always or on_click
			$str_target_elm = "";
			$showButtonPanel = "false";
			if ($options["show"] === "always") {
				$str_target_elm = '<div id="%1$s"></div>';
			} elseif ("on_click" === $options["show"]) {
				$str_target_elm = '<input class="%9$s" type="text" id="%1$s" name="%2$s" value="">';
				$showButtonPanel = "true";
			}

			// if new field = use default date
			$str_saved_unixtime = "";
			$str_set_date = "";
			$str_unixtime_to_set = "";

			if (isset($options["use_defaults"]) && $options["use_defaults"]) {

				if (isset($options["default_date"]) && $options["default_date"] === "today") {

					$str_unixtime_to_set = time() * 1000;
					$str_iso_to_set = date("Y/m/d H:i");

				} elseif (isset($options["default_date"]) && $options["default_date"] === "no_date") {
					
				}
			} else {

				$str_saved_unixtime = $saved_values["saved_date_time"];

				// convert saved values to unixtime
				// echo "Saved value: $str_saved_unixtime";
				if (preg_match('!^\d{2}:\d{2}$!', $str_saved_unixtime)) {
					// if only time, then make it a full date to be able to create javascript date object
					//$str_saved_unixtime = "2000-01-01 $str_saved_unixtime";
					$str_iso_to_set = "1970/01/01 $str_saved_unixtime";
				} else {
					$str_iso_to_set = date("Y/m/d H:i", strtotime($saved_values["saved_date_time"]) );
				}
				$str_unixtime_to_set = strtotime($str_saved_unixtime);
				//sf_d( date("Y-m-d H:i", $str_unixtime_to_set) );
				//sf_d( $str_iso_to_set );
				$str_unixtime_to_set = $str_unixtime_to_set * 1000;
			}

			// Set Date Format
			$str_date_format = "ISO_8601";
			if (isset($options["date_format"])) $str_date_format = $options["date_format"];
			// echo "date_format:";sf_d($str_date_format);

			// First day. 0 = sunday, 1 = monday
			// Use same as in wordpress
			// $str_first_day = get_option("start_of_week", 1);

			// Type to show
			// name of method to use/call.
			$method_name = "datepicker";
			$altFieldTimeOnly = "false";
			$show_as = isset($options["show_as"]) ? $options["show_as"] : "date";
			$alt_format = "yy-mm-dd";
			if ("datetime" === $show_as) {
				$method_name = "datetimepicker";
			} else if ("time" === $show_as) {
				$method_name = "timepicker";
				// $altFieldTimeOnly = "true";
				$altFieldTimeOnly = "false";
				$alt_format = " ";
			}

			$locale = substr(get_locale(), 0, 2);
			// $locale = "sv";

			if ( $str_unixtime_to_set ) {
				// If saved value exists then set date to this value on load
				// The display: none-thingie is added beause the date picker get shown by setDate-method
				// Unsure if bug or feature, but annoying anyway.

				$str_set_date = '
					var date_saved = new Date("'.$str_iso_to_set.'");
					$( "#%1$s" ).'.$method_name.'("setDate", date_saved);
					$( "#ui-datepicker-div" ).css("display","none");
				';

			}

			// don't set a date if default date is no_date and no date is selected
			// (actually unset what we previosly set)
			if ( empty( $str_saved_unixtime ) && isset($options["default_date"]) && $options["default_date"] === "no_date") {
				
				// echo "<br>unset date/make no date selected";
				$str_set_date .= '
					$( "#%1$s" ).'.$method_name.'("setDate", "");
				';

			}

			$output = sprintf(
				'
					'.$str_target_elm.'
					<input type="hidden" id="%3$s" name="%4$s" value="%6$s">
					<script>
						jQuery(function($) {
							
							// Init picker
							$( "#%1$s" ).%10$s({
								altField: "#%3$s",
								altFormat: "%14$s",
								altTimeFormat: "HH:mm",
								altFieldTimeOnly: %12$s, // was buggy when true, overwrite org field with full date instead of just time
								showButtonPanel: %13$s,
								showWeek: true,
								dateFormat: "%7$s",
								changeYear: true,
								changeMonth: true,
								xshowOn: "both",
								autoSizeType: true
								%5$s
							});

							// Set locale
							if (typeof jQuery.datepicker.regional["%11$s"] == "object") {
								$("#%1$s").datepicker("option", $.datepicker.regional["%11$s"]);
							} else {
								$("#%1$s").datepicker("option", $.datepicker.regional[""]);
							}

							'.$str_set_date.'
						});
					</script>
				',
				$this->get_options_id("gui_selected_date"),
				$this->get_options_name("gui_selected_date"),
				$this->get_options_id("saved_date_time"), // 3
				$this->get_options_name("saved_date_time"), //
				"", // 5
				$str_saved_unixtime, // 6
				$str_date_format,
				"", // 8, was firstDay
				$this->get_class_name("gui-date"), // 9
				$method_name, // 10
				$locale, // 11
				$altFieldTimeOnly, // 12
				$showButtonPanel, // 13
				$alt_format // 14
			);

			return $output;

		} // end options output

		/**
		 * Change so saved value is a single one, instead of array, so we can sort by the unixtime in wp_query etc.
		 * Lets go with ISO_8601 instead of unix time. Standards rule, and we can save just time too. Hooray!
		 * http://en.wikipedia.org/wiki/ISO_8601#General_principles 
		 * They are sortable too = good.
		 * http://stackoverflow.com/questions/9576860/sort-iso-iso-8601-dates-forward-or-backwards
		 * http://en.wikipedia.org/wiki/Lexicographical_order
		 */
		function edit_save($values = null) {
			
			/*
				// echo "Saving these values for field:";sf_d($values);
				Array
				(
					# datum
					[saved_date_time] => 1970-01-01

					# tid
					[saved_date_time] => 01:00

					# datum + tid
					[saved_date_time] => 1970-01-01 01:00
				)
			*/
			if ( is_array($values) && isset($values["saved_date_time"]) && !empty($values["saved_date_time"]) ) {
				

				// Determine format
				// Bah! Format is in ISO 8601
				/*
				$saved_date_time = $values["saved_date_time"];
				if (preg_match('!^\d{4}-\d{2}-\d{2}$!', $saved_date_time)) {
					// echo "date"; [saved_date_time] => 1970-01-01
					// just date, so append with some time
				} else if (preg_match('!^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$!', $saved_date_time)) {
					// [saved_date_time] => 1970-01-01 01:00
					// echo "date + time";

				} else if (preg_match('!^\d{2}:\d{2}$!', $saved_date_time)) {
					// [saved_date_time] => 01:00
					// time, so prefix with 1970-01-01 (I've got to append with something!)
					// echo "time";
				}
				*/

				// echo "<br>end";

				return trim($values["saved_date_time"]);

			} else {
				return "";
			}

		}

		/**
		 * Add Extended Return Values and then return the values
		 */
		function return_values($values = null, $parsed_options_for_this_field = null) {

			// @todo: what if no value?
			$arr_extended = array();
			foreach ($values as $key => $one_value) {
				
				$one_value_unix = strtotime($one_value);
				
				// Detect full date or just time
				if (preg_match('!^\d{4}-\d{2}-\d{2}$!', $one_value)) {
					// echo "date"; [saved_date_time] => 1970-01-01
					$arr_extended = array(
						"type" => "date",
						"date_unixtime" => $one_value_unix,
						"ISO_8601" => date("Y-m-d", $one_value_unix),
						"RFC_2822" => date("r", $one_value_unix),
						"Y-m-d" => date("Y-m-d", $one_value_unix),
						"date_format" => date_i18n(get_option('date_format'), $one_value_unix)
					);

				} else if (preg_match('!^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$!', $one_value)) {

					// [saved_date_time] => 1970-01-01 01:00
					$arr_extended = array(
						"type" => "datetime",
						"date_unixtime" => $one_value_unix,
						"ISO_8601" => date("Y-m-d H:i", $one_value_unix),
						"RFC_2822" => date("r", $one_value_unix),
						"Y-m-d" => date("Y-m-d", $one_value_unix),
						"Y-m-d H:i" => date("Y-m-d H:i", $one_value_unix),
						"date_format" => date_i18n(get_option('date_format'), $one_value_unix),
						"date_time_format" => date_i18n(get_option('date_format') . " " . get_option('time_format'), $one_value_unix)
						// http://codex.wordpress.org/Function_Reference/date_i18n
						// echo date_i18n( $dateformatstring, $unixtimestamp, $gmt )
					);

				} else if (preg_match('!^\d{2}:\d{2}$!', $one_value)) {
					// [saved_date_time] => 01:00
					$arr_extended = array(
						"type" => "time",
						"ISO_8601" => date("H:i", $one_value_unix),
						"time_format" => date_i18n(get_option('time_format'), $one_value_unix)
					);

				}

				$values[$key] = $arr_extended;
			}

			return $values;

		}

	} // end class

	simple_fields::register_field_type("simple_fields_field_date_v2");

}

