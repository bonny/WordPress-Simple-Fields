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
			
			// add some styling in the admin head
			add_action('admin_head', array($this, 'action_admin_head'));
		    
		    //add_action('wp_enqueue_scripts', array($this, 'action_enqueue_scripts'));
		    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		    //echo "yyy";

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

			#sf_d($saved_values);
			#sf_d($options);
			//sf_d($options);
			/*
			Array
			(
			    [show] => always
			    [default_date] => no_date
			    [date_format] => MM d, yy
			)
			$this->get_options_name("option1"),
			$this->get_options_id("option1"),
			*/

			/*
			Output:
				input to show date / click to select date
				icon for date (provided by datepicker?)
				hidden field to store date in common format
			*/

			// When to show: always or on_click
			$str_target_elm = "";
			if ($options["show"] === "always") {
				$str_target_elm = '<div id="%1$s"></div>';
			} elseif ("on_click" === $options["show"]) {
				$str_target_elm = '<input type="text" id="%1$s" name="%2$s" value="">';
			}

			// if new field = use default date
			$str_default_date = "";
			$str_default_date_alt_field = "";
			$str_saved_unixtime = "";
			$str_set_date = "";
			if (isset($options["use_defaults"]) && $options["use_defaults"]) {
				if ($options["default_date"] === "today") {
					$str_default_date = ",defaultDate: 0";
					$str_default_date_alt_field = time();
				} elseif ($options["default_date"] === "no_date") {
					//$str_default_date = ",defaultDate: null";
				}
			} else {
				$str_saved_unixtime = $saved_values["date_unixtime"];
				$str_set_date = '
					var date_saved = new Date('.$str_saved_unixtime.');
					$( "#%1$s" ).datepicker("setDate", date_saved);
					$( "#ui-datepicker-div" ).css("display","none");

				';
			}

			//$( "#%1$s" ).datepicker("show"); // picker is opened by default for some reason...
			//$( "#%1$s" ).datepicker("hide"); // so we show and then immediately hide it

			// Set Date Format
			$str_date_format = "ISO_8601";
			if (isset($options["date_format"])) $str_date_format = $options["date_format"];

			// First day. 0 = sunday, 1 = monday
			$str_first_day = 1;

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
								xautoSizeType: true
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
				$str_default_date, // 5
				$str_saved_unixtime, // 6
				$str_date_format,
				$str_first_day
			);

			return $output;

		}			

	}

	simple_fields::register_field_type("simple_fields_field_date_v2");

}

