<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://eskapism.se/code-playground/simple-fields/
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 0.x
Author: Pär Thernström
Author URI: http://eskapism.se/
License: GPL2

*/

/*  Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


load_plugin_textdomain( 'simple-fields', null, basename(dirname(__FILE__)).'/languages/');



/**
 * Class to keep all simple fields stuff together i bit better
 */ 
class simple_fields {

	const DEBUG_ENABLED = TRUE;

	/**
	 * Init is where we setup actions and filers and loads stuff and a little bit of this and that
	 *
	 */
	function init() {

		require("functions_admin.php");
		require("functions_post.php");

		define( "EASY_FIELDS_URL", plugins_url(basename(dirname(__FILE__))). "/");
		define( "EASY_FIELDS_NAME", "Simple Fields");
		define( "EASY_FIELDS_VERSION", "0.5");

		// Actions
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_menu', "simple_fields_admin_menu" );
		add_action( 'admin_head', 'simple_fields_admin_head' );
		add_action('wp_ajax_simple_fields_field_group_add_field', 'simple_fields_field_group_add_field');

		// Filters
		add_filter( 'plugin_row_meta', array($this, 'set_plugin_row_meta'), 10, 2 );

	}
	
	/**
	 * Returns a post connector
	 * @param int $connector_id
	 */
	function get_connector_by_id($connector_id) {
		$connectors = simple_fields_get_post_connectors();
		if (isset($connectors[$connector_id])) {
			return $connectors[$connector_id];
		} else {
			return FALSE;
		}
	}

	/**
	 * If setting debug = true then output some debug stuff a little here and there
	 * Hopefully this saves us some var_dump/sf_d/echo all the time
	 * usage:
	 * first set DEBUG_ENABLED = true in beginning of class
	 * then:
	 * simple_fields("Saved post connector", array("description" => $value, "description n" => $value_n));
	 */
	public static function debug($description, $details) {
		if (self::DEBUG_ENABLED) {
			echo "<pre>";
			echo $description;
			if ($details) {
				echo "<br>";
				print_r($details);
			}
			echo "</pre>";
		}
	}

	function admin_init() {

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_script("jquery-effects-highlight");
		wp_enqueue_script("thickbox");
		wp_enqueue_style("thickbox");
		wp_enqueue_script("jscolor", EASY_FIELDS_URL . "jscolor/jscolor.js"); // color picker for type color
		wp_enqueue_script("date", EASY_FIELDS_URL . "datepicker/date.js"); // date picker for type date
		wp_enqueue_script("jquery-datepicker", EASY_FIELDS_URL . "datepicker/jquery.datePicker.js"); // date picker for type date
		wp_enqueue_style('jquery-datepicker', EASY_FIELDS_URL.'datepicker/datePicker.css', false, EASY_FIELDS_VERSION);

		// add css and scripts
		wp_enqueue_style('simple-fields-styles', EASY_FIELDS_URL.'styles.css', false, EASY_FIELDS_VERSION);
		wp_register_script('simple-fields-scripts', EASY_FIELDS_URL.'scripts.js', false, EASY_FIELDS_VERSION);
		wp_localize_script('simple-fields-scripts', 'sfstrings', array(
			'txtDelete' => __('Delete', 'simple-fields'),
			'confirmDelete' => __('Delete this field?', 'simple-fields'),
			'confirmDeleteGroup' => __('Delete this group?', 'simple-fields'),
			'confirmDeleteConnector' => __('Delete this post connector?', 'simple-fields'),
			'confirmDeleteRadio' => __('Delete radio button?', 'simple-fields'),
			'confirmDeleteDropdown' => __('Delete dropdown value?', 'simple-fields'),
			'adding' => __('Adding...', 'simple-fields'),
			'add' => __('Add', 'simple-fields'),
			'confirmRemoveGroupConnector' => __('Remove field group from post connector?', 'simple-fields'),
			'confirmRemoveGroup' => __('Remove this field group?', 'simple-fields'),
			'context' => __('Context', 'simple-fields'),
			'normal' => __('normal'),
			'advanced' => __('advanced'),
			'side' => __('side'),
			'low' => __('low'),
			'high' => __('high'),
		));
		wp_enqueue_script('simple-fields-scripts');

		define( "EASY_FIELDS_FILE", menu_page_url("simple-fields-options", false) );

	}

	/**
	 * Add settings link to plugin page
	 * Hopefully this helps some people to find the settings page quicker
	 */
	function set_plugin_row_meta($links, $file) {

		$plugin_plugin_path_and_filename = basename(dirname(__FILE__)) . "/" . basename(__FILE__);
		if ($file == $plugin_plugin_path_and_filename) {
			return array_merge(
				$links,
				array( sprintf( '<a href="options-general.php?page=%s">%s</a>', "simple-fields-options", __('Settings') ) )
			);
		}
		return $links;

	}

}

$sf = new simple_fields();
$sf->init();
