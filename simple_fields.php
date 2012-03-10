<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://eskapism.se/code-playground/simple-fields/
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 0.5
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

// if called directly, load wordpress
/*
if (isset($_GET["wp_abspath"])) {
	define( 'WP_USE_THEMES', false );
	require( $_GET["wp_abspath"] . './wp-blog-header.php' );
}
*/

define( "EASY_FIELDS_URL", WP_PLUGIN_URL . '/simple-fields/');
define( "EASY_FIELDS_NAME", "Simple Fields"); 
define( "EASY_FIELDS_VERSION", "0.4");
#define( "EASY_FIELDS_FILE", "options-general.php?page=simple-fields-options"); // this still feels nasty...

load_plugin_textdomain( 'simple-fields', null, basename(dirname(__FILE__)).'/languages/');

// on admin init: add styles and scripts
add_action( 'admin_init', 'simple_fields_admin_init' );
add_action( 'admin_menu', "simple_fields_admin_menu" );
add_action( 'admin_head', 'simple_fields_admin_head' );

// ajax. that's right baby.
add_action('wp_ajax_simple_fields_field_group_add_field', 'simple_fields_field_group_add_field');

function simple_fields_admin_init() {

	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-sortable");
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

require("functions_admin.php");
require("functions_post.php");

