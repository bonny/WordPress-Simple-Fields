<?php

/**
 * Class to extend Debug Bar with groovy Simple Fields stuff
 */
class class_simple_fields_debug_panel {

	function title() {
		return 'Simple Fields';
	}

	function prerender() {}

	function is_visible() {
		return true;
	}

	function render() {

		global $sf;
		echo $sf->simple_fields_content_debug_output("", array("always_show" => TRUE, "show_expanded" => TRUE));

	}

}

