<?php
/*
Plugin Name: Simple Fields
Plugin URI: http://simple-fields.com
Description: Add groups of textareas, input-fields, dropdowns, radiobuttons, checkboxes and files to your edit post screen.
Version: 1.4.2
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

/**
 * Class to keep all simple fields stuff together a bit better
 */ 
class simple_fields {

	const DEBUG_ENABLED = false; // set to true to enable some debug output
	
	public 

		// Looks something like this: "Simple-Fields-GIT/simple_fields.php"
		$plugin_foldername_and_filename,
	
		// array with registered field type objects
		$registered_field_types,
		
		// key to use in cache
		$ns_key
		
	;

	private

		$wpml_context = "Simple Fields";


	/**
	 * Init is where we setup actions and filers and loads stuff and a little bit of this and that
	 *
	 */
	function init() {


		define( "SIMPLE_FIELDS_VERSION", "1.4.2");
		define( "SIMPLE_FIELDS_URL", plugins_url(basename(dirname(__FILE__))). "/");
		define( "SIMPLE_FIELDS_NAME", "Simple Fields");

		load_plugin_textdomain( 'simple-fields', null, basename(dirname(__FILE__)).'/languages/');
		
		// setup cache
		// based on stuff found here:
		// http://core.trac.wordpress.org/ticket/4476
		$ns_key = wp_cache_get( 'simple_fields_namespace_key', 'simple_fields' );
		if ( $ns_key === false ) {
			wp_cache_set( 'simple_fields_namespace_key', 1, 'simple_fields' );
			// echo "cache key init set";
		}
		$this->ns_key = wp_cache_get( 'simple_fields_namespace_key', 'simple_fields' );
		// echo "ns_key is: $this->ns_key"; // 1

		require( dirname(__FILE__) . "/functions.php" );
		require( dirname(__FILE__) . "/class_simple_fields_field.php" );
		
		// require( dirname(__FILE__) . "/field_types/field_example.php" );
		// require( dirname(__FILE__) . "/field_types/field_minimalistic_example.php" );
		
		// Load field types
		require( dirname(__FILE__) . "/field_types/field_divider.php" );
		require( dirname(__FILE__) . "/field_types/field_date_v2.php" );

		// Load option pages
		require( dirname(__FILE__) . "/inc-admin-options-export-import.php" );
		require( dirname(__FILE__) . "/inc-admin-options-debug.php" );

		$this->plugin_foldername_and_filename = basename(dirname(__FILE__)) . "/" . basename(__FILE__);
		$this->registered_field_types = array();

		// Actions and filters
		add_action( 'admin_init', array($this, 'admin_init') );
		add_action( 'admin_init', array($this, 'check_upgrade_stuff') );
		add_action( 'admin_init', array($this, "options_page_save" ));
		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action( 'admin_menu', array($this, "admin_menu") );
		add_action( 'admin_head', array($this, 'admin_head') );
		add_action( 'admin_head', array($this, 'settings_admin_head') );
		add_action( 'admin_head', array($this, 'admin_head_select_file') );
		add_filter( 'plugin_row_meta', array($this, 'set_plugin_row_meta'), 10, 2 );
		add_action( 'admin_footer', array($this, 'admin_footer') );
		add_action( 'admin_init', array($this,'post_admin_init') );
		add_action( 'dbx_post_sidebar', array($this, 'post_dbx_post_sidebar') );
		add_action( 'save_post', array($this, 'save_postdata') );
		add_action( 'save_post', array($this, 'clear_caches') );
		add_action( 'edit_attachment', array($this, 'save_postdata') );
		add_action( "simple_fields_get_selected_connector_for_post", array($this, "set_post_connector_from_template"), 10, 2 );

		// Query filters
		add_action( 'pre_get_posts', array($this, 'action_pre_get_posts_meta') );

		add_action( 'plugins_loaded', array($this, 'plugins_loaded') );
		add_action( 'init', array($this, "maybe_add_debug_info") ); 

		// Hacks for media select dialog
		add_filter( 'media_send_to_editor', array($this, 'media_send_to_editor'), 15, 2 );
		add_filter( 'media_upload_tabs', array($this, 'media_upload_tabs'), 15 );
		add_filter( 'media_upload_form_url', array($this, 'media_upload_form_url') );

		// Ajax calls
		add_action( 'wp_ajax_simple_fields_metabox_fieldgroup_add', array($this, 'metabox_fieldgroup_add') );
		add_action( 'wp_ajax_simple_fields_field_type_post_dialog_load', array($this, 'field_type_post_dialog_load') );
		add_action( 'wp_ajax_simple_fields_field_group_add_field', array($this, 'field_group_add_field') );

		// Options page
		add_action("simple_fields_options_print_nav_tabs", array($this, "promote_ep_on_nav_tabs"));
		add_action("simple_fields_options_print_nav_tabs", array($this, "get_options_nav_tabs"));

		// Add to debug bar if debug is enabled
		add_filter( 'debug_bar_panels', array($this, "debug_panel_insert") );

		// Enable slugs as meta keys. Works. Enable by setting, by default for new installs, or require filter hook like below?
		/*
		add_filter("simple_fields_get_meta_key_template", function($str) {
			$str = '_simple_fields_fieldGroupSlug_%4$s_fieldSlug_%5$s_numInSet_%3$s';
			return $str;
		});
		*/

		// Setup things for WPML-support
		add_action("init", array($this, "setup_wpml_support"));

		// Boot up
		do_action("simple_fields_init", $this);

	}

	/**
	 * Init support for WPML translations
	 */
	function setup_wpml_support() {

		// If wpml is not active then don't do anything
		if ( ! $this->is_wpml_active()) return;

		// http://wpml.org/documentation/support/translation-for-texts-by-other-plugins-and-themes/

		// 1. Register the strings that need translation
		// icl_register_string($context, $name, $value)
		// run on settings screen, go through all fields and register string
		add_action("simple_fields_settings_admin_head", array($this, "register_wpml_strings"));

		// 2. Using the translation when displaying
		// icl_t($context, $name, $value)

	}



	/**
	 * Make sure a field group has the correct format
	 * It can be wrong because prior to version ?.? the options
	 * for a field was not stored in the options array. but nowadays we
	 * assume it is. so..if it's not: fix that!
	 *
	 * @param array $fieldgroup Field group to normalize
	 * @return array $fieldgroup Normalized/fixed field group
	 */
	function normalize_fieldgroups( $field_groups ) {
	
		// wierd, but i moved to code so this is the way it is.. 
		foreach ( $field_groups as & $fieldgroup_by_reference ) {

			// If field was not added with code then move all options to the options-array
			if ( ! isset($fieldgroup_by_reference["added_with_code"]) || false === $fieldgroup_by_reference["added_with_code"] ) {
			
				foreach ($fieldgroup_by_reference["fields"] as & $field_by_reference) {

					#if ( "drps" === $field_by_reference["slug"] ) {
						
						// make sure field has an options-key that is an array
						if ( ! isset( $field_by_reference["options"] ) || ! is_array( $field_by_reference["options"] ) ) $field_by_reference["options"] = array();

						foreach ( $field_by_reference as $field_key => $field_vals ) {
							
							// if field has key with name
							// type_<textarea|post|taxonyterm|dropdown|whatever>_options
							// then move that info to the field[options]-array
							if ( 1 === preg_match('/type_([a-z]+)/', $field_key, $field_key_matches) ) {
								
								// $field_key_matches[1] = field type
								$field_key_type = $field_key_matches[1];

								// make sure field type is key in options array
								if ( ! isset( $field_by_reference["options"][ $field_key_type ] ) || ! is_array( $field_by_reference["options"][ $field_key_type ] ) ) $field_by_reference["options"][ $field_key_type ] = array();
								
								// move keys to options array
								// keys with name dropdown_num_, checkbox_num_, radiobutton_num_ need special treatment
								$values = array();
								$values_index = 0;

								// check if checked by default exists
								$checked_by_default_num = false;
								if ( isset( $field_vals["checked_by_default_num"] ) ) {
									$checked_by_default_num = $field_vals["checked_by_default_num"];
									if ( 1 === preg_match('/_num_([\d]+)/', $checked_by_default_num, $checked_num_matches ) ) {
										$checked_by_default_num = (int) $checked_num_matches[1];
									}
								}

								foreach ( $field_vals as $field_vals_key => $field_vals_val ) {
									
									if ( 1 === preg_match('/([a-z]+)_num_(\d+)/i', $field_vals_key, $matches) ) {

										// $matches[1] = field type
										// $matches[2] = field type num
										#sf_d($field_vals_key, '$field_vals_key');
										#sf_d($field_vals_val, '$field_vals_val');

										$values[ $values_index ] = $field_vals_val;
										$values[ $values_index ]["num"] = (int) $matches[2];
										
										if ( false !== $checked_by_default_num && $checked_by_default_num === $values[ $values_index ]["num"] ) {
											$values[ $values_index ]["checked"] = true;
											$field_by_reference["options"][ $field_key_type ]["checked_by_default_num"] = $field_key_type . "_num_" . $checked_by_default_num;
										}

										$values_index++;

									} else {

										// "regular" option key key
										$field_by_reference["options"][ $field_key_type ][ $field_vals_key ] = $field_vals_val;
										
									}

									if ($values) {
									
										$field_by_reference["options"][ $field_key_type ]["values"] = $values;
									
									}

									
								} // foreach field vals
								
							} // if type_

							// sf_d($field);

						} // foreach field key


					#}

				} // foreach field


			} // if not added with code

		}

		return $field_groups;

	} // func

	/**
	 * Register strings so they are translateable with WPML
	 */
	function register_wpml_strings() {
		
		// sometimes icl_register_string does not exist, 
		// probably because simple fields hooks on tings early 
		// when wpml is not loaded (like when saving)
		if ( ! function_exists("icl_register_string" ) ) return;

		// Get all fieldgroups and fields
		$field_groups = $this->get_field_groups();

		foreach ($field_groups as & $fieldgroup_by_reference) {
						
			// register name and description of each field group
			icl_register_string($this->wpml_context, "Field group name, " . $fieldgroup_by_reference["slug"], $fieldgroup_by_reference["name"]);
			icl_register_string($this->wpml_context, "Field group description, " . $fieldgroup_by_reference["slug"], $fieldgroup_by_reference["description"]);

			// register name for each field
			foreach ($fieldgroup_by_reference["fields"] as $field) {

				icl_register_string($this->wpml_context, "Field name, " . $field["slug"], $field["name"]);
				icl_register_string($this->wpml_context, "Field description, " . $field["slug"], $field["description"]);

				// register names for dropdowns and radiobuttons
				// several fields can have the same slug, if they are in different field groups
				// how to solve that? 
				// 	- can't prefix with field group, because they can be in several of those
				// 	- can't prefix with id because can be different between dev/prod/live-servers
				// to much to worry about here, let's go with just the slug and then it's up to the
				// user to not use a slug more than once.
				if ( isset( $field["options"] ) && is_array( $field["options"] ) ) {

					if ( isset( $field["options"]["radiobuttons"]["values"] ) && is_array( $field["options"]["radiobuttons"]["values"] ) ) {
	
						foreach ( $field["options"]["radiobuttons"]["values"] as $one_radio_option_key => $one_radio_option_val) {
							
							$string_name = "Field radiobuttons value, " . $field["slug"] . " " . "radiobutton_num_" . $one_radio_option_val["num"];
							// sf_d($this->wpml_context);sf_d($string_name);sf_d($one_radio_option_val["value"]);
							icl_register_string($this->wpml_context, $string_name, $one_radio_option_val["value"]);

						} // foreach

					} // if radiobuttons

					if ( isset( $field["options"]["dropdown"]["values"] ) && is_array( $field["options"]["dropdown"]["values"] ) ) {
	
						foreach ( $field["options"]["dropdown"]["values"] as $one_dropdown_val) {
							
							$string_name = "Field dropdown value, " . $field["slug"] . " " . "dropdown_num_" . $one_dropdown_val["num"];
							// sf_d($string_name);
							icl_register_string($this->wpml_context, $string_name, $one_dropdown_val["value"]);

						} // foreach

					} // if dropdowns
				
				} // if options
/*
// @todo: make above for dropdowns too

				} elseif ( isset( $field["type_dropdown_options"] ) && is_array( $field["type_radiobuttons_options"] ) ) {
					
					foreach ( $field["type_radiobuttons_options"] as $one_radio_option_key => $one_radio_option_val) {

						// only values like radiobutton_num_2 are allowed
						if ( strpos($one_radio_option_key, "radiobutton_num_") === FALSE) continue;
						icl_register_string($this->wpml_context, "Field checkbox value, " . $field["slug"] . " " . $one_radio_option_key, $one_radio_option_val["value"]);

					}

				}
*/

			} // foreach

		} // foreach field groups
		
		// Get and register post connectors
		$post_connectors = $this->get_post_connectors();
		foreach ($post_connectors as $connector) {
			icl_register_string($this->wpml_context, "Post connector name, " . $connector["slug"], $connector["name"]);
		}

	} // func

	/**
	 * Get maybe translated string
	 * If WPML is installed and activated then icl_t() is used on the string
	 * If WPML is not instaled, then it's just returned unmodified
	 *
	 * @param string $name Name to use in icl_t
	 * @param string $value Value to use in icl_t
	 */
	function get_string($name = "", $value = "") {

		if ( $this->is_wpml_active() && function_exists("icl_t") ) {
			$value = icl_t($this->wpml_context, $name, $value);
			// $value = "WPML: $value"; // debug to check that function actually runs
			return $value;
		} else {
			return $value;
		}

	}


	/**
	 * If sf_meta_key is set then that is assumed to be the slugs of a field group and a field
	 * and the meta_key of the value will be replaced by the meta_key value of that simple field-field
	 */
	function action_pre_get_posts_meta( $query ) {

		$sf_meta_key = $query->get("sf_meta_key");
		if ( ! empty( $sf_meta_key ) ) {

			$field = $this->get_field_by_fieldgroup_and_slug_string( $sf_meta_key );

			if ( false !== $field ) {

				$field_meta_key = $this->get_meta_key( $field["field_group"]["id"], $field["id"], 0, $field["field_group"]["slug"], $field["slug"] );
				$query->set("meta_key", $field_meta_key );
				
			}

		}

	}

	/**
	 * Inserts debug panel to debug bar
	 * Called form debug bar filter "debug_bar_panels", so will only be run'ed when debug bar is activated
	 */
	function debug_panel_insert( $panels ) {
		
		$options = $this->get_options();
		// if (isset($options["debug_type"]) && $options["debug_type"] !== 0) {

			// 1 = debug for admins only, 2 = debug for all
			//if ( ($options["debug_type"] === 1 && current_user_can("edit_themes")) || $options["debug_type"] === 2) {

				include_once( dirname(__FILE__) . "/class_simple_fields_debug_panel.php" );
				$panels[] = new class_simple_fields_debug_panel;

			//}

		//}

		return $panels;

	}

	// check some things regarding update
	function check_upgrade_stuff() {

		global $wpdb;

		$db_version = (int) get_option("simple_fields_db_version");
		
		if ($db_version === 0) {

			// 1 = the first version, nothing done during update
			$new_db_version = 1;
		
		} else if ( 1 === $db_version ) {

			// if prev db version was 1 then clear cache so field group options get updated
			$this->clear_caches();
			$new_db_version = 2;

		}
		
		if ( isset( $new_db_version ) ) {
			update_option("simple_fields_db_version", $new_db_version);
		}
		
	}
	
		
	/**
	 * When all plugins have loaded = simple fields has also loaded = safe to add custom field types
	 */
	function plugins_loaded() {
		do_action("simple_fields_register_field_types");
	}

	/**
	 * Gets the pattern that are allowed for slugs
	 * @return string
	 */
	function get_slug_pattern() {
		$pattern = "[A-Za-z0-9_]+";
		$pattern = apply_filters( "simple_fields_get_slug_pattern", $pattern);
		return $pattern;
	}
	
	/**
	 * Get the title for a slug
	 * I.e. the help text that the input field will show when the slug pattern is not matched
	 */
	function get_slug_title() {
		return __("Allowed chars: a-z and underscore.", 'simple-fields');
	}
	
	/**
	 * Returns a post connector
	 * @param int $connector_id
	 */
	function get_connector_by_id($connector_id) {

		$connectors = $this->get_post_connectors();
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
			echo "<pre class='sf_box_debug'>";
			echo "<strong>".$description."</strong>";
			if ($details) {
				echo "<br>";
				echo htmlspecialchars(print_r($details, TRUE), ENT_QUOTES, 'UTF-8');
			} else {
				echo "<br>&lt;Empty thing.&gt;";
			}
			echo "</pre>";
		}
	}

	/**
	 * Run action if we are on a settings/options page that belongs to Simple Fields
	 */
	function settings_admin_head() {
		
		$is_on_simple_fields_page = FALSE;
		$page_type = "";

		$current_screen = get_current_screen();
		if ($current_screen->id === "settings_page_simple-fields-options") {
			$is_on_simple_fields_page = TRUE;
			$page_type = "settings";
		}
		
		if ( ! $is_on_simple_fields_page ) return;

		if ("settings" === $page_type) {
			do_action("simple_fields_settings_admin_head");
		}
	}

	/**
	 * Enqueue styles and scripts, but on on pages that use simple fields
	 * Should speed up the loading of other pages a bit
	 */
	function admin_enqueue_scripts($hook) {

		// pages to load on = admin/settings page for SF + edit post
		$is_on_simple_fields_page = FALSE;
		$page_type = "";

		$current_screen = get_current_screen();
		if ($current_screen->base == "post" && in_array($current_screen->post_type, $this->get_post_connector_attached_types())) {
			$is_on_simple_fields_page = TRUE;
			$page_type = "post";
		} elseif ($current_screen->base === "media-upload") {
			$is_on_simple_fields_page = TRUE;
			$page_type = "media-upload";
		} elseif ($current_screen->id === "settings_page_simple-fields-options") {
			$is_on_simple_fields_page = TRUE;
			$page_type = "settings";
		}
		
		if ( ! $is_on_simple_fields_page ) return;

		if ("settings" === $page_type) {

			// Settings page
			wp_enqueue_style('simple-fields-styles', SIMPLE_FIELDS_URL.'styles.css', false, SIMPLE_FIELDS_VERSION);

		} else {

			// Edit post etc.
			wp_enqueue_script("thickbox");
			wp_enqueue_style("thickbox");
			wp_enqueue_script("jscolor", SIMPLE_FIELDS_URL . "jscolor/jscolor.js"); // color picker for type color
			wp_enqueue_script("simple-fields-date", SIMPLE_FIELDS_URL . "datepicker/date.js"); // date picker for type date
			
			// Date picker for type date
			wp_enqueue_script("sf-jquery-datepicker", SIMPLE_FIELDS_URL . "datepicker/jquery.datePicker.js");
			wp_enqueue_style('sf-jquery-datepicker', SIMPLE_FIELDS_URL.'datepicker/datePicker.css', false, SIMPLE_FIELDS_VERSION);

			// Chosen for multi selects
			// wp_enqueue_script("chosen.jquery", SIMPLE_FIELDS_URL . "js/chosen/chosen.jquery.min.js");
			// wp_enqueue_style("chosen", SIMPLE_FIELDS_URL.'js/chosen/chosen.css', false, SIMPLE_FIELDS_VERSION);

			wp_enqueue_style('simple-fields-styles-post', SIMPLE_FIELDS_URL.'styles-edit-post.css', false, SIMPLE_FIELDS_VERSION);

			// Media must be enqueued if we are editing a post with no editor (probably custom post type)
			wp_enqueue_media(); // edit-form-advanced passes this also: array( 'post' => $post_ID
	
		}

		// Common scripts
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-effects-highlight");
		wp_register_script('simple-fields-scripts', SIMPLE_FIELDS_URL.'scripts.js', false, SIMPLE_FIELDS_VERSION);		
		wp_localize_script('simple-fields-scripts', 'sfstrings', array(
			'page_type' => $page_type,
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

		// Common styles
		wp_enqueue_style('wp-jquery-ui-dialog');

		// Hook for plugins
		do_action("simple_fields_enqueue_scripts", $this);

	}

	/**
	 * Stuff that is being runned only when in admin (i.e. not on front of site)
	 */
	function admin_init() {

		define( "SIMPLE_FIELDS_FILE", menu_page_url("simple-fields-options", false) );

	}

	/**
	 * Add settings link to plugin page
	 * Hopefully this helps some people to find the settings page quicker
	 */
	function set_plugin_row_meta($links, $file) {

		if ($file == $this->plugin_foldername_and_filename) {
			return array_merge(
				$links,
				array( sprintf( '<a href="options-general.php?page=%s">%s</a>', "simple-fields-options", __('Settings') ) )
			);
		}
		return $links;

	}


	/**
	 * Return an array of the post types that we have set up post connectors for
	 *
	 * Format of return:
	 *
	 * Array
	 * (
	 *     [0] => post
	 *     [1] => page
	 *     [2] => testposttype
	 * )
	 *
	 * @param return array
	 */
	function get_post_connector_attached_types() {
		global $sf;
		$post_connectors = $this->get_post_connectors();
		$arr_post_types = array();
		foreach ($post_connectors as $one_post_connector) {
			$arr_post_types = array_merge($arr_post_types, (array) $one_post_connector["post_types"]);
		}
		$arr_post_types = array_unique($arr_post_types);
		return $arr_post_types;
	}


	/**
	 * Get default connector for a post type
	 * If no connector has been set, __none__ is returned
	 *
	 * @param string $post_type
	 * @return mixed int connector id or string __none__ or __inherit__
	 */
	function get_default_connector_for_post_type($post_type) {

		$post_type_defaults = $this->get_post_type_defaults();
		$selected_post_type_default = (isset($post_type_defaults[$post_type]) ? $post_type_defaults[$post_type] : "__none__");
		$selected_post_type_default = apply_filters( "simple_fields_get_default_connector_for_post_type", $selected_post_type_default, $post_type );

		return $selected_post_type_default;

	}

	/**
	 * Output HTML for dialog in bottom
	 */
	function admin_footer() {
		// HTML for post dialog
		?><div class="simple-fields-meta-box-field-group-field-type-post-dialog hidden"></div><?php
	}
	
	/**
	 * output nonce
	 */
	function post_dbx_post_sidebar() {
		?>
		<input type="hidden" name="simple_fields_nonce" id="simple_fields_nonce" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>" />
		<?php
	}

	/**
	 * Saves simple fields data when post is being saved
	 */
	function save_postdata($post_id = null, $post = null) {

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		// so not checking nonce can lead to errors, for example losing post connector
		if (!isset($_POST['simple_fields_nonce']) || !wp_verify_nonce( $_POST['simple_fields_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
	
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return $post_id; }

		// dont's save if is revision
		if (wp_is_post_revision($post_id) !== FALSE) return $post_id;
		
		// attach post connector
		// only save if being found in post variable, beacuse it may not exist if meta box is hidden/not outputted on page
		if ( isset($_POST["simple_fields_selected_connector"]) ) {
			$simple_fields_selected_connector = (isset($_POST["simple_fields_selected_connector"])) ? $_POST["simple_fields_selected_connector"] : null;
			update_post_meta($post_id, "_simple_fields_selected_connector", $simple_fields_selected_connector);
		}
	
		$post_id = (int) $post_id;
		$fieldgroups = (isset($_POST["simple_fields_fieldgroups"])) ? $_POST["simple_fields_fieldgroups"] : null;
		$field_groups_option = $this->get_field_groups();
	
		if ( !$table = _get_meta_table("post") ) { return false; }

		global $wpdb;

		// We have a post_id and we have fieldgroups
		if ($post_id && is_array($fieldgroups)) {
	
			// Delete all exisiting custom fields meta that are not part of the keep-list
			$post_meta = get_post_custom($post_id);

			// new format.. can be anything... how to get it?
			$arr_meta_keys_to_keep = array(
				"_simple_fields_been_saved",
				"_simple_fields_selected_connector"
			);
			foreach ($post_meta as $meta_key => $meta_val) {

				if ( strpos($meta_key, "_simple_fields_") === 0 ) {
					// this is a meta for simple fields, check if it should be kept or deleted
					if ( ! in_array($meta_key, $arr_meta_keys_to_keep ) ) {
						delete_post_meta($post_id, $meta_key);
					}
				}

			}
	
			// cleanup missing keys, due to checkboxes not being checked
			$fieldgroups_fixed = $fieldgroups;
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
			
				foreach ($one_field_group_fields as $posted_id => $posted_vals) {
					if ($posted_id == "added") {
						continue;
					}
					$fieldgroups_fixed[$one_field_group_id][$posted_id] = array();
					// loopa igenom "added"-värdena och fixa så att allt finns
					foreach ($one_field_group_fields["added"] as $added_id => $added_val) {
						$fieldgroups_fixed[$one_field_group_id][$posted_id][$added_id] = @$fieldgroups[$one_field_group_id][$posted_id][$added_id];
					}
				}
			
			}
			$fieldgroups = $fieldgroups_fixed;
	
			// Save info about the fact that this post have been saved. This info is used to determine if a post should get default values or not.
			update_post_meta($post_id, "_simple_fields_been_saved", "1");

			// Loop through each fieldgroups
#sf_d($fieldgroups, '$fieldgroups');
			foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
				
				// Loop through each field in each field group
#simple_fields::debug("one_field_group_fields", $one_field_group_fields);
#sf_d($one_field_group_fields);

				// Get info about the field group that are saved
				// (We only get ID:s posted, so no meta info about the group)
				$arr_fieldgroup_info = $this->get_field_group( $one_field_group_id );

				foreach ($one_field_group_fields as $one_field_id => $one_field_values) {

					// one_field_id = id på fältet vi sparar. t.ex. id:et på "måndag" eller "tisdag"
					// one_field_values = sparade värden för detta fält, sorterat i den ordning som syns i admin
					//					  dvs. nyaste överst (med key "new0"), och sedan key 0, key 1, osv.

#simple_fields::debug("save, loop fields, one_field_id", $one_field_id);
#simple_fields::debug("save, loop fields, one_field_values", $one_field_values);

					// determine type of field we are saving
					$field_info = isset($field_groups_option[$one_field_group_id]["fields"][$one_field_id]) ? $field_groups_option[$one_field_group_id]["fields"][$one_field_id] : NULL;
					$field_type = $field_info["type"]; // @todo: this should be a function

#simple_fields::debug("save, field_type", $field_type);

					$do_wpautop = false;
					if ($field_type == "textarea" && isset($field_info["type_textarea_options"]["use_html_editor"]) && $field_info["type_textarea_options"]["use_html_editor"] == 1) {
						// it's a tiny edit area, so use wpautop to fix p and br
						$do_wpautop = true;
					}
					$do_wpautop = apply_filters("simple_fields_save_postdata_do_wpautop", $do_wpautop, $post_id);
					
					// save entered value for each added group
					$num_in_set = 0;

					foreach ($one_field_values as $one_field_value) {


// $one_field_id may be "added" because it's... a special kind of input field
$arr_field_info = array();
$one_field_slug = "";
if ("added" === $one_field_id) {
	$one_field_slug = "added";
} else {
	#sf_d($arr_fieldgroup_info["fields"], 'fields');
	foreach ($arr_fieldgroup_info["fields"] as $one_field_in_fieldgroup) {
		if ( intval( $one_field_in_fieldgroup["id"] ) === intval( $one_field_id ) ) {
			$arr_field_info = $one_field_in_fieldgroup;
			break;
		}
	}
	$one_field_slug = $arr_field_info["slug"];
	#sf_d($one_field_slug, 'one_field_slug');
	#sf_d($one_field_id, 'one_field_id');
	#exit;
}

						$custom_field_key = $this->get_meta_key( $one_field_group_id, $one_field_id, $num_in_set, $arr_fieldgroup_info["slug"], $one_field_slug );

						$custom_field_value = $one_field_value;

/*sf_d($custom_field_key, '$custom_field_key');
sf_d($one_field_group_id, '$one_field_group_id');
sf_d($one_field_id, '$one_field_id');
sf_d($num_in_set, 'num_in_set');
sf_d($arr_fieldgroup_info["slug"], 'arr_fieldgroup_info["slug"]');
sf_d($one_field_slug, 'one_field_slug');*/


						if (array_key_exists($field_type, $this->registered_field_types)) {
							
							// Custom field type	
							$custom_field_value = $this->registered_field_types[$field_type]->edit_save($custom_field_value);
							/*
							
							Date field:
							Array
							(
								[date_unixtime] => 1351983600000
							)
							
							Map field:
							Array
							(
								[lat] => 59.312089
								[lng] => 18.074117
								[name] => Monki Skrapan
								[formatted_address] => GÃ¶tgatan 78, Stockholm, Sverige
								[address_components] => [{\"long_name\":\"78\",\"short_name\":\"78\",\"types\":[\"street_number\"]},{\"long_name\":\"GÃ¶tgatan\",\"short_name\":\"GÃ¶tgatan\",\"types\":[\"route\"]},{\"long_name\":\"SÃ¶dermalm\",\"short_name\":\"SÃ¶dermalm\",\"types\":[\"sublocality\",\"political\"]},{\"long_name\":\"Stockholm\",\"short_name\":\"Stockholm\",\"types\":[\"locality\",\"political\"]},{\"long_name\":\"Stockholms lÃ¤n\",\"short_name\":\"Stockholms lÃ¤n\",\"types\":[\"administrative_area_level_2\",\"political\"]},{\"long_name\":\"SE\",\"short_name\":\"SE\",\"types\":[\"country\",\"political\"]},{\"long_name\":\"11830\",\"short_name\":\"11830\",\"types\":[\"postal_code\"]}]
							)
							*/
							//echo "xxx save value for custom field type"; sf_d($custom_field_value);

						} else {
							// core/legacy field type
							if ($do_wpautop) {
								$custom_field_value = wpautop($custom_field_value);
							}
	
						}
						
						// echo "<br>Saving value for post with id $post_id. Custom_field_key is $custom_field_key, custom_field_value is:";sf_d($custom_field_value);
						update_post_meta($post_id, $custom_field_key, $custom_field_value);
						$num_in_set++;
					
					}
	
				}
				
			}
			// if array
		} else if (empty($fieldgroups)) {
			// if fieldgroups are empty we still need to save it
			// remove existing simple fields custom fields for this post
			// @todo: this should also be using wordpress own functions
			// TODO: use new meta keys names
			$wpdb->query("DELETE FROM $table WHERE post_id = $post_id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");
		} 
		// echo "end save";
	
	} // save postdata

	
	/**
	 * adds a fieldgroup through ajax = also fetch defaults
	 * called when clicking "+ add" in post edit screen
	 */
	function metabox_fieldgroup_add() {
	
		global $sf;
	
		$simple_fields_new_fields_count = (int) $_POST["simple_fields_new_fields_count"];
		$post_id = (int) $_POST["post_id"];
		$field_group_id = (int) $_POST["field_group_id"];
	
		$num_in_set = "new{$simple_fields_new_fields_count}";
		$this->meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, true);
	
		exit;
	}


	/**
	 * Output the html for a field group in the meta box on the post edit screen
	 * Also called from ajax when clicking "+ add"
	 */
	function meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, $use_defaults) {
	
		$post = get_post($post_id);
		
		$field_groups = $this->get_field_groups();
		$current_field_group = $field_groups[$field_group_id];
		$repeatable = (bool) $current_field_group["repeatable"];
		$field_group_css = "simple-fields-fieldgroup-$field_group_id";
		/* if (isset($current_field_group["slug"]) && !empty($current_field_group["slug"])) {
			$field_group_css .= " simple-fields-fieldgroup-" . $current_field_group["slug"];
		}*/

		?><li class="sf-cf simple-fields-metabox-field-group <?php echo $field_group_css ?>">
			<?php // must use this "added"-thingie do be able to track added field group that has no added values (like unchecked checkboxes, that we can't detect ?>
			<input type="hidden" name="simple_fields_fieldgroups[<?php echo $field_group_id ?>][added][<?php echo $num_in_set ?>]" value="1" />
			<div class="simple-fields-metabox-field-group-handle"></div>
			<?php
			// if repeatable: add remove-link
			if ($repeatable) {
				?><div class="hidden simple-fields-metabox-field-group-delete"><a href="#" title="<?php _e('Remove field group', 'simple-fields') ?>"></a></div><?php
			}
			
			// Output content for each field in this fieldgroup
			// LI = fieldgroup
			// DIV = field
			foreach ($current_field_group["fields"] as $field) {
			
				if ($field["deleted"]) { continue; }

				$field_id = $field["id"];
				$field_unique_id = "simple_fields_fieldgroups_{$field_group_id}_{$field_id}_{$num_in_set}";
				$field_name = "simple_fields_fieldgroups[$field_group_id][$field_id][$num_in_set]";
				$field_class = "simple-fields-fieldgroups-field-{$field_group_id}-{$field_id} ";
				$field_class .= "simple-fields-fieldgroups-field-type-" . $field["type"];
				if (isset($field["slug"]) && !empty($field["slug"])) {
					$field_class .= " simple-fields-fieldgroups-field-slug-" . $field["slug"];
				}
				
				// Fetch saved value for field from db/post meta
				// Returned value is:
				//  - string if core fields
				//  - array if field type extension, unless the field extension overrides this
				$custom_field_key = $this->get_meta_key($field_group_id, $field_id, $num_in_set, $current_field_group["slug"], $field["slug"]);

				/*sf_d($field_group_id, '$field_group_id');
				sf_d($field_id, '$field_id');
				sf_d($num_in_set, '$num_in_set');
				sf_d($current_field_group["slug"], '$current_field_group["slug"]');
				sf_d($field["slug"], '$field["slug"]');
				sf_d($custom_field_key, '$custom_field_key');*/
				
				$saved_value = get_post_meta($post_id, $custom_field_key, true);

				// Options, common for all fields
				$field_maybe_translated_name = $this->get_string( "Field name, " . $field["slug"], $field["name"] );
				$description = "";
				if ( ! empty( $field["description"] ) ) {
					$description = sprintf("<div class='simple-fields-metabox-field-description'>%s</div>", esc_html( $this->get_string("Field description, " . $field["slug"], $field["description"] ) ) );
				}

				// Options, common for most core field
				$field_type_options = ! isset( $field["options"] ) || ! isset( $field["options"][$field["type"]] ) ? array() : (array) $field["options"][ $field["type"] ];
				$placeholder = empty( $field_type_options["placeholder"] ) ? "" : esc_attr( $field_type_options["placeholder"] );
				
				// div that wraps around each outputed field
				// Output will be similar to this
				// <div class="simple-fields-metabox-field simple-fields-fieldgroups-field-1-1 simple-fields-fieldgroups-field-type-text" data-fieldgroup_id="1" data-field_id="1" data-num_in_set="0">
				
				?>
				<div class="simple-fields-metabox-field sf-cf <?php echo $field_class ?>" 
					data-fieldgroup_id=<?php echo $field_group_id ?>
					data-field_id="<?php echo $field_id ?>"
					data-num_in_set=<?php echo $num_in_set ?>
					>
					<?php

					// different output depending on field type
					if ("checkbox" == $field["type"]) {
		
						if ($use_defaults) {
							$checked = @$field["type_checkbox_options"]["checked_by_default"];
						} else {
							$checked = (bool) $saved_value;
						}
						
						if ($checked) {
							$str_checked = " checked='checked' ";
						} else {
							$str_checked = "";
						}

						echo "<div class='simple-fields-metabox-field-first'>";
						echo $description;
						echo "</div>";
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input $str_checked id='$field_unique_id' type='checkbox' name='$field_name' value='1' />";
						echo "<label class='simple-fields-for-checkbox' for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo "</div>";
		
					} elseif ("radiobuttons" == $field["type"]) {
		
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label>" . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";

						$radio_options = $field["type_radiobuttons_options"];
						$radio_checked_by_default_num = @$radio_options["checked_by_default_num"];
	
						$loopNum = 0;
						foreach ($radio_options as $one_radio_option_key => $one_radio_option_val) {
							
							// only values like radiobutton_num_2 are allowed
							if ( strpos($one_radio_option_key, "radiobutton_num_") === FALSE) { continue; }
							
							// Skip deleted ones
							if (isset($one_radio_option_val["deleted"]) && $one_radio_option_val["deleted"]) { continue; }

							$radio_field_unique_id = $field_unique_id . "_radio_".$loopNum;
							$one_radio_option_val_val = isset($one_radio_option_val["value"]) ? $one_radio_option_val["value"] : "";
							
							$selected = "";
							if ($use_defaults) {
								if ($radio_checked_by_default_num == $one_radio_option_key) { $selected = " checked='checked' "; }
							} else {
								if ($saved_value == $one_radio_option_key) { $selected = " checked='checked' "; }
							}
							
							$radiobutton_maybe_translation_val = $this->get_string("Field radiobuttons value, " . $field["slug"] . " " . $one_radio_option_key, $one_radio_option_val_val );

							echo "<div class='simple-fields-metabox-field-radiobutton'>";
							echo "	<input $selected name='$field_name' id='$radio_field_unique_id' type='radio' value='$one_radio_option_key' />";
							echo "	<label for='$radio_field_unique_id' class='simple-fields-for-radiobutton'> " . $radiobutton_maybe_translation_val . "</label>";
							echo "</div>";							
							
							$loopNum++;
						}

						echo "</div>";
		
					} elseif ("dropdown" == $field["type"]) {

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";

						$enable_multiple = (isset($field["type_dropdown_options"]["enable_multiple"]) && ($field["type_dropdown_options"]["enable_multiple"] == 1));
						$str_multiple = "";
						$field_name_dropdown = $field_name;
						$field_size = 1;
						if ($enable_multiple) {
							$str_multiple = "multiple";
							$field_name_dropdown = $field_name . "[]";
							$field_size = 6;
						}

						echo "<select id='$field_unique_id' name='$field_name_dropdown' $str_multiple size='$field_size' >";

						foreach ($field["type_dropdown_options"] as $one_option_internal_name => $one_option) {
							
							if (isset($one_option["deleted"]) && $one_option["deleted"]) { continue; }
							if (strpos($one_option_internal_name, "dropdown_num_") === FALSE) continue;

							#$dropdown_value_esc = esc_html( $one_option["value"] );

							$option_name = $one_option["value"];
							$options_maybe_translation_name = $this->get_string("Field dropdown value, " . $field["slug"] . " " . $one_option_internal_name, $option_name );
							$dropdown_value_esc = esc_html( $options_maybe_translation_name );

							$selected = "";

							// Different ways of detecting selected dropdown value if multiple or single
							if ($enable_multiple) {

								$arr_saved_value_dropdown = (array) $saved_value;
								/*
								Array
								(
									[0] => dropdown_num_2
									[1] => dropdown_num_3
								)
								*/
								if (in_array($one_option_internal_name, $arr_saved_value_dropdown)) {
									$selected = " selected ";
								}

								
							} else {

								if ($use_defaults == false && $saved_value == $one_option_internal_name) {
									$selected = " selected ";
								}

							}

							echo "<option $selected value='$one_option_internal_name'>$dropdown_value_esc</option>";
						}
						echo "</select>";
						echo "</div>";
	
					} elseif ("file" == $field["type"]) {
	
						$current_post_id = !empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
						$attachment_id = (int) $saved_value;
						$image_html = "";
						$image_name = "";
						$view_file_url = "";
						$class = "";
						if ($attachment_id) {
							$class .= " simple-fields-metabox-field-file-is-selected ";
							$image_post = get_post($attachment_id);
							if ($image_post === NULL) {
								// hm.. image that no longer exists? trashed?
							} else {
								$image_thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
								$image_thumbnail = $image_thumbnail[0];
								$image_html = "<img src='$image_thumbnail' alt='' />";
								$image_name = esc_html($image_post->post_title);
							}
							$view_file_url = wp_get_attachment_url($attachment_id);
						}
						if ($description) {
							//$class = "simple-fields-metabox-field-file-with-description";
						}
						echo "<div class='simple-fields-metabox-field-file $class'>";

							echo "<div class='simple-fields-metabox-field-first'>";
							echo "<label>{$field_maybe_translated_name}</label>";
							echo $description;
							echo "</div>";

							echo "<div class='simple-fields-metabox-field-second'>";

							echo "<div class='simple-fields-metabox-field-file-col1'>";
								echo "<div class='simple-fields-metabox-field-file-selected-image'>$image_html</div>";
							echo "</div>";

							echo "<div class='simple-fields-metabox-field-file-col2'>";
								
								echo "<input type='hidden' class='text simple-fields-metabox-field-file-fileID' name='$field_name' id='$field_unique_id' value='$attachment_id' />";							
	
								// File name
								echo "<div class='simple-fields-metabox-field-file-selected-image-name'>$image_name</div>";

								// File action links (view, select, edit, etc.)
								$field_unique_id_esc = rawurlencode($field_unique_id);
								$file_url = get_bloginfo('wpurl') . "/wp-admin/media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=$field_unique_id_esc&post_id=$current_post_id&TB_iframe=true";
								
								echo "<a class='simple-fields-metabox-field-file-select' href='$file_url'>".__('Select', 'simple-fields')."</a> ";
								echo "<a href='#' class='simple-fields-metabox-field-file-clear'>".__('Clear', 'simple-fields')."</a> ";
								echo "<a class='simple-fields-metabox-field-file-view' target='_blank' href='$view_file_url'>".__('View', 'simple-fields')."</a> ";
								
								//$class = ($attachment_id) ? " " : " hidden ";
								// old format: http://viacom.ep/wp-admin/media.php?attachment_id=20314&action=edit
								// new format: http://viacom.ep/wp-admin/post.php?post=20314&action=edit
								$href_edit = ($attachment_id) ? admin_url("post.php?post={$attachment_id}&action=edit") : "#";
								echo "<a href='{$href_edit}' target='_blank' class='simple-fields-metabox-field-file-edit'>".__('Edit', 'simple-fields') . "</a>";

							echo "</div>";


							echo "</div>"; // second

						echo "</div>";
							
					} elseif ("textarea" == $field["type"]) {
		
						$textarea_value_esc = esc_html($saved_value);
						$textarea_options = isset($field["type_textarea_options"]) ? $field["type_textarea_options"] : array();
						
						$textarea_class = "";
						$textarea_class_wrapper = "";
						$textarea_html_extra_classes = "";
						
						// default num rows to same as WordPress uses / 2 beacuse it's always been smaller
						$textarea_rows = ((int) get_option('default_post_edit_rows', 10)) / 2;
						
						// if user has set custom height
						// since 1.0.3
						if (isset($textarea_options["size_height"])) {
							// size is small, medium, large
							$textarea_html_extra_classes .= " simple-fields-metabox-field-textarea-tinymce-size-{$textarea_options['size_height']} ";
							switch ($textarea_options["size_height"]) {
								case "small":
									$textarea_rows = 3;
									break;
								case "medium":
									$textarea_rows = 15;
									break;
								case "large":
									$textarea_rows = 30;
									break;
							}
						}
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
	
						if ( isset( $textarea_options["use_html_editor"] ) && $textarea_options["use_html_editor"] ) {
							
							// This helps get_upload_iframe_src() determine the correct post id for the media upload button
							global $post_ID;
							if (intval($post_ID) == 0) {
								if (intval($_REQUEST['post_id']) > 0) {
									$post_ID = intval($_REQUEST['post_id']);
								} elseif (intval($_REQUEST['post']) > 0) {
									$post_ID = intval($_REQUEST['post']);
								}
							}
							$args = array(
								"textarea_name"	=> $field_name, 
								"editor_class" 	=> "simple-fields-metabox-field-textarea-tinymce $textarea_html_extra_classes",
								// "teeny" 		=> TRUE // possibly add in future. does not actually gain/loose much using it, right?,
								"textarea_rows"	=> $textarea_rows,
								"media_buttons"	=> TRUE
							);

							echo "<div class='simple-fields-metabox-field-textarea-tinymce-wrapper'>";
							#sf_d($saved_value, "saved_value");
							#$field_unique_id = "GORILLA" . rand();
							#sf_d($args, "args");
							#sf_d($use_defaults, "use_defaults");
							/*
							$editor_id
							(string) (required) HTML ID attribute value for the textarea and TinyMCE. (may only contain lower-case letters)
							Note that the ID that is passed to the wp_editor() function can only be composed of lower-case letters. No underscores, no hyphens. Anything else will cause the WYSIWYG editor to malfunction.
							Default: None
							
							Ny, funkar ej:
								field_unique_id:
								simple_fields_fieldgroups_6_1_new0
								args:
								Array
								(
								    [textarea_name] => simple_fields_fieldgroups[6][1][new0]
								    [editor_class] => simple-fields-metabox-field-textarea-tinymce  simple-fields-metabox-field-textarea-tinymce-size-small 
								    [textarea_rows] => 3
								    [media_buttons] => 1
								)
							Befintlig, funkar:
								saved_value:
								field_unique_id:
								simple_fields_fieldgroups_6_1_0
								args:
								Array
								(
								    [textarea_name] => simple_fields_fieldgroups[6][1][0]
								    [editor_class] => simple-fields-metabox-field-textarea-tinymce  simple-fields-metabox-field-textarea-tinymce-size-small 
								    [textarea_rows] => 3
								    [media_buttons] => 1
								)							
							*/
							// sf_d($field_unique_id, "adding wp_editor with field_unique_id");
							wp_editor( $saved_value, $field_unique_id, $args );

							// use_defauls = first time fields are outputed = new post or new fielgroup from ajax call ("+ Add"-link)
							if ($use_defaults) {

								// Do stuff with wp editor, but only when called from ajax
								if ( defined("DOING_AJAX") && DOING_AJAX ) {
			
									// Must call footer scripts so wp_editor outputs it's stuff
									// It works with TinyMCE but the quicktags are not outputted due to quicktags being activated on domready

									// remove scripts that we don't need								
									remove_action( "admin_print_footer_scripts", "wp_auth_check_js");

									// don't load tinymce plugins
									add_filter('mce_external_plugins', "__return_empty_array");

									// Remove some scripts that cause problems
									global $wp_scripts;

									// From plugin http://time.ly/
									// Timely’s All-in-One Event Calendar
									// For some reason the scripts outputed are html escaped, so scripts are broken
									$wp_scripts->remove("ai1ec_requirejs");
									$wp_scripts->remove("ai1ec_common_backend");

									// Start output buffering and output scripts and then get them into a variable
									ob_start();
									do_action("admin_print_footer_scripts");
									$footer_scripts = ob_get_clean();								

									// only keep scripts. works pretty ok, but we get some stray text too, so use preg match to get only script tags
									$footer_scripts = wp_kses($footer_scripts, array("script" => array()));
									
									preg_match_all('/<script>(.*)<\/script>/msU', $footer_scripts, $matches);
									$footer_scripts = "";
									foreach ($matches[1] as $one_script_tag_contents) {
										if ( ! empty( $one_script_tag_contents ) )
											$footer_scripts .= sprintf('<script>%1$s</script>', $one_script_tag_contents);
									}

									// the scripts only output correct id for the first editor
									// (for unknown reasons, something to do with the fact that we do this everal times while it's meant do only be done once)
									// so replace strings like simple_fields_fieldgroups_6_1_new0 to the current editor
									// mceInit : {'simple_fields_fieldgroups_6_1_new0'
									/*
									$pattern = "#simple_fields_fieldgroups_(\d+)_(\d+)_new(\d+)#";
									$replacement = "$field_unique_id";
									$footer_scripts = preg_replace($pattern, $replacement, $footer_scripts);

									$footer_scripts = str_replace("tinyMCEPreInit", "tinyMCEPreInit_$field_unique_id", $footer_scripts);
									$footer_scripts = str_replace("wpActiveEditor", "wpActiveEditor_$field_unique_id", $footer_scripts);
									*/

									// the line that begins with 
									// (function(){var t=tinyMCEPreInit,sl=tinymce.ScriptLoader
									// breaks my install... so let's remove it
									// it's only outputed sometimes, something to do with compressed scripts or not. or simpething.
									$footer_scripts = preg_replace('/\(function\(\){var t=tinyMCEPreInit,sl=tinymce.ScriptLoader.*/', '', $footer_scripts);

									echo "$footer_scripts";

									?>
									<script>
										// We need to call _buttonsInit to make quicktags buttons appear/work, but it's private. however calling addButtons calls _buttonsInit
										// so we fake-add a button, just to fire _buttonsInit again.
										QTags.addButton( 'simple_fields_dummy_button', '...', '<br />', null, null, null, null, "apa" );
									</script>
									<?php

								} // if ajax

							}  // if use defaults 

							echo "</div>";

						} else {

							echo "<div class='simple-fields-metabox-field-textarea-wrapper'>";
							echo "<textarea class='simple-fields-metabox-field-textarea' name='$field_name' id='$field_unique_id' cols='50' rows='$textarea_rows' placeholder='$placeholder'>$textarea_value_esc</textarea>";
							echo "</div>";

						}

						echo "</div>";
		
					} elseif ("text" == $field["type"]) {

						$text_value_esc = esc_html($saved_value);

						$type_attr = isset( $field_type_options["subtype"] ) ? $field_type_options["subtype"] : "text";
						$extra_attributes = isset( $field_type_options["attributes"] ) ? $field_type_options["attributes"] : "";

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input type='$type_attr' class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' placeholder='$placeholder' $extra_attributes />";
						echo "</div>";
		
					} elseif ("color" == $field["type"]) {
						
						$text_value_esc = esc_html($saved_value);

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";
						
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input class='text simple-fields-field-type-color {pickerClosable:true}' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						echo "</div>";
	
					} elseif ("date" == $field["type"]) {
	
						// $datef = __( 'M j, Y @ G:i' ); // same format as in meta-boxes.php
						// echo date_i18n( $datef, strtotime( current_time('mysql') ) );
						
						$text_value_esc = esc_html($saved_value);
						
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";
						
						echo "<div class='simple-fields-metabox-field-second'>";
						echo "<input class='text simple-fields-field-type-date' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
						echo "</div>";
	
					} elseif ("taxonomy" == $field["type"]) {
						
						$arr_taxonomies = get_taxonomies(array(), "objects");
						$enabled_taxonomies = (array) @$field["type_taxonomy_options"]["enabled_taxonomies"];
						
						//echo "<pre>";print_r($enabled_taxonomies );echo "</pre>";
						
						$text_value_esc = esc_html($saved_value);
						// var_dump($saved_value);
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						
						echo "<select name='$field_name'>";
						printf("<option value=''>%s</option>", __('Select...', 'simple-fields'));
						foreach ($arr_taxonomies as $one_taxonomy) {
							if (!in_array($one_taxonomy->name, $enabled_taxonomies)) {
								continue;
							}
							$selected = ($saved_value == $one_taxonomy->name) ? ' selected="selected" ' : '';
							printf ("<option %s value='%s'>%s</option>", $selected, $one_taxonomy->name, $one_taxonomy->label);
						}
						echo "</select>";


						echo "</div>";
	
	
					} elseif ("taxonomyterm" == $field["type"]) {
						
						$enabled_taxonomy = @$field["type_taxonomyterm_options"]["enabled_taxonomy"];
						$additional_arguments = @$field["type_taxonomyterm_options"]["additional_arguments"];
	
						// Check that selected taxonomy exists
						$enabled_taxonomy_obj = get_taxonomy ( $enabled_taxonomy );


						// hämta alla terms som finns för taxonomy $enabled_taxonomy
						// @todo: kunna skicka in args här, t.ex. för orderby
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";

						if ( $enabled_taxonomy_obj === false ) {

							echo __("The selected Taxonomy Term for this field type does not exist", "simple-fields");

						} else {

							$arr_selected_cats = (array) $saved_value;
							
							$walker = new Simple_Fields_Walker_Category_Checklist();
							$args = array(
								"taxonomy" => $enabled_taxonomy,
								"selected_cats" => $arr_selected_cats,
								"walker" => $walker,
								"sf_field_name" => $field_name // walker is ot able to get this one, therefor global
							);

							// Add additional argument to args array
							$args = wp_parse_args( $additional_arguments, $args );
							
							global $simple_fields_taxonomyterm_walker_field_name; // sorry for global...
							$simple_fields_taxonomyterm_walker_field_name = $field_name;
							echo "<ul class='simple-fields-metabox-field-taxonomymeta-terms'>";
							wp_terms_checklist(NULL, $args);
							echo "</ul>";
						
						}

						echo "</div>";
						
					} elseif ("post" == $field["type"]) {
						
						$saved_value_int = (int) $saved_value;
						if ($saved_value_int) {
							$saved_post_name = get_the_title($saved_value_int);
							$showHideClass = "";
						} else {
							$saved_post_name = "";
							$showHideClass = "hidden";
						}
						
						$type_post_options = isset($field["type_post_options"]) ? (array) $field["type_post_options"] : array();
						$enabled_post_types = isset($type_post_options["enabled_post_types"]) ? (array) $type_post_options["enabled_post_types"] : array();
						
						echo "<div class='simple-fields-metabox-field-post'>";

						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
	
						// name of the selected post
						echo "<div class='simple-fields-field-type-post-postName $showHideClass'>$saved_post_name</div>";

						// Output action buttons (select, clear, etc.)
						echo "<div>";
						printf("<a class='%s' href='#'>%s</a>", "simple-fields-metabox-field-post-select", __("Select post", "simple-fields"));
						printf("<a class='%s' href='#'>%s</a>", "simple-fields-metabox-field-post-clear $showHideClass", __("Clear", "simple-fields"));
						echo "</div>";
						
						// output the post types that are selected for this post field
						printf("<input type='hidden' name='%s' value='%s' />", "simple-fields-metabox-field-post-enabled-post-types", join(",", $enabled_post_types));
																	
						// print the id of the current post
						echo "<input type='hidden' class='simple-fields-field-type-post-postID' name='$field_name' id='$field_unique_id' value='$saved_value_int' />";
						
						// output additional arguments for this post field
                                                $type_post_options_additional_arguments = isset( $type_post_options['additional_arguments'] ) ? $type_post_options['additional_arguments'] : "";
                                                echo "<input type='hidden' name='additional_arguments' id='additional_arguments' value='" . $type_post_options_additional_arguments . "' />";
						
						echo "</div>";

						echo "</div>";
	
					} elseif ("user" == $field["type"]) {
					
						$saved_value_int = (int) $saved_value;
					
						#echo "<div class='simple-fields-metabox-field-post'>";
						// echo "<pre>"; print_r($type_post_options); echo "</pre>";
						echo "<div class='simple-fields-metabox-field-first'>";
						echo "<label for='$field_unique_id'> " . $field_maybe_translated_name . "</label>";
						echo $description;
						echo "</div>";

						echo "<div class='simple-fields-metabox-field-second'>";
						
						// must set orderby or it will not get any users at all. yes. it's that weird.
						$args = array(
							//' role' => 'any'
							"orderby" => "login",
							"order" => "asc"
						);
						$users_query = new WP_User_Query( $args );
						$users = $users_query->results;
						
						echo "<select name='$field_name' id='$field_unique_id'>";
						printf("<option value=''>%s</option>", __('Select...', 'simple-fields'));
						foreach ($users as $one_user) {
							$first_name = get_the_author_meta("first_name", $one_user->ID);
							$last_name = get_the_author_meta("last_name", $one_user->ID);
							$first_and_last_name = "";
							if (!empty($first_name) || !empty($last_name)) {
								$first_and_last_name = $first_name . " " . $last_name;
								$first_and_last_name = trim($first_and_last_name);
								$first_and_last_name = " ($first_and_last_name)";
							}
							
							printf("<option %s value='%s'>%s</option>", 
								($saved_value_int == $one_user->ID) ? " selected='selected' " : "",
								$one_user->ID,
								$one_user->display_name . "$first_and_last_name"
							);
						}
						echo "</select>";

						
						echo "</div>";
						#echo "</div>";
	
	
					} else {
						
						// Filed type is not "core", so check for added field types
						if (isset($this->registered_field_types[$field["type"]])) {
						
							$custom_field_type = $this->registered_field_types[$field["type"]];
							$custom_field_type->set_options_base_id($field_unique_id);
							$custom_field_type->set_options_base_name($field_name);

							// Get the options that are saved for this field type.
							// @todo: should be a method of the class? must know what field group it's connected to to be able to fetch the right one
							$custom_field_type_options = isset($field["options"][$field["type"]]) ? $field["options"][$field["type"]] : array();

							// Always output label and description, for consistency
							echo "<div class='simple-fields-metabox-field-first'>";
							echo "<label>" . $field_maybe_translated_name . "</label>";
							echo $description;
							echo "</div>";
							
							echo "<div class='simple-fields-metabox-field-second'>";

							// if use_defaults is set then pass that arg to custom field types too
							if ($use_defaults) $custom_field_type_options["use_defaults"] = $use_defaults;

							// Get and output the edit-output from the field type
							// Return as array if field type has not specified other
							$custom_field_type_saved_value = $saved_value;
							#echo "saved value"; sf_d($custom_field_type_saved_value);
							// always return array, or just sometimes?
							// if a field has saved a value as a single value it will be returned as the value at position [0]
							$custom_field_type_saved_value = (array) $custom_field_type_saved_value;
							echo $custom_field_type->edit_output($custom_field_type_saved_value, $custom_field_type_options);

							echo "</div>";

						}
					
					} // field types
					
					// Output hidden field that can be shown with JS to see the name and slug of a field
					?>
					<div class="simple-fields-metabox-field-custom-field-key hidden">
						<strong><?php _e('Meta key:', 'simple-fields') ?></strong>
						<?php echo $custom_field_key ?>
						<?php if (isset($field["slug"])) { ?>
							<br><strong><?php _e('Slug:', 'simple-fields') ?></strong>
							<?php echo $field["slug"] ?>
						<?php } ?>
					</div>
				</div><!-- // end simple-fields-metabox-field -->
				<?php
			} // foreach
			
			?>
		</li>
		<?php
	} // end function print


	/**
	 * Head of admin area
	 * - Add meta box with info about currently selected connector + options to choose another one
	 * - Add meta boxes with field groups
	 */
	function admin_head() {

		// Only run code if on a SF page
		$current_screen = get_current_screen();
		$is_on_simple_fields_page = FALSE;
		if ($current_screen->base == "post" && in_array($current_screen->post_type, $this->get_post_connector_attached_types())) {
			$is_on_simple_fields_page = TRUE;
			$page_type = "post";
		}

		if (!$is_on_simple_fields_page) return;

		// Add meta box to post
		global $post, $sf;
	
		// Tell pluings etc that they can output stuff now
		do_action("simple_fields_admin_head", $this);

		if ($post) {
	
			$post_type = $post->post_type;
			$arr_post_types = $this->get_post_connector_attached_types();
			
			// check if the post type being edited is among the post types we want to add boxes for
			if (in_array($post_type, $arr_post_types)) {
				
				// general meta box to select fields for the post
				$add_post_edit_side_field_settings_box = apply_filters("simple_fields_add_post_edit_side_field_settings", true, $post);
				if ($add_post_edit_side_field_settings_box) add_meta_box('simple-fields-post-edit-side-field-settings', 'Simple Fields', array($this, 'edit_post_side_field_settings'), $post_type, 'side', 'low');
				
				$connector_to_use = $this->get_selected_connector_for_post($post);
				
				// get connector to use for this post
				$post_connectors = $this->get_post_connectors();
				if (isset($post_connectors[$connector_to_use])) {
					
					$field_groups = $this->get_field_groups();
					$selected_post_connector = $post_connectors[$connector_to_use];
					
					// check if we should hide the editor, using css to keep things simple
					// echo "<pre>";print_r($selected_post_connector);echo "</pre>";
					$hide_editor = (bool) isset($selected_post_connector["hide_editor"]) && $selected_post_connector["hide_editor"];
					if ($hide_editor) {
						?><style type="text/css">#postdivrich, #postdiv { display: none; }</style><?php
					}
					
					// get the field groups for the selected connector
					$selected_post_connector_field_groups = $selected_post_connector["field_groups"];
	
					foreach ($selected_post_connector_field_groups as $one_post_connector_field_group) {
	
						// check that the connector is not deleted
						if ($one_post_connector_field_group["deleted"]) {
							continue;
						}
	
						// check that the field group for the connector we want to add also actually exists
						if (isset($field_groups[$one_post_connector_field_group["id"]])) {
													
							$field_group_to_add = $field_groups[$one_post_connector_field_group["id"]];
	
							$meta_box_id = "simple_fields_connector_" . $field_group_to_add["id"];
							$meta_box_title = $this->get_string("Field group name, " . $field_group_to_add["slug"], $field_group_to_add["name"] );
							$meta_box_context = $one_post_connector_field_group["context"];
							$meta_box_priority = $one_post_connector_field_group["priority"];
							// @todo: could we just create an anonymous function the "javascript way" instead? does that require a too new version of PHP?
							$meta_box_callback = create_function ("", "global \$sf; \$sf->meta_box_output({$one_post_connector_field_group["id"]}, $post->ID); ");
							
							add_meta_box( $meta_box_id, $meta_box_title, $meta_box_callback, $post_type, $meta_box_context, $meta_box_priority );
							
						}
						
					}
				}
				
			}
		}
		
	} // end function admin head


	/**
	 * print out fields for a meta box
	 */
	function meta_box_output($post_connector_field_id, $post_id) {
	 
		// if not repeatable, just print it out
		// if repeatable: only print out the ones that have a value
		// and + add-button
		
		global $sf;
	 
		$field_groups = $this->get_field_groups( false );
		$current_field_group = $field_groups[$post_connector_field_id];

		// check for prev. saved fieldgroups
		// can be found because a custom field with "added" instead of field id is always added
		// key is something like "_simple_fields_fieldGroupID_1_fieldID_added_numInSet_0"
		// try until returns empty
		$num_added_field_groups = 0;
		$meta_key_num_added = $this->get_meta_key_num_added( $current_field_group["id"], $current_field_group["slug"] );
		while (get_post_meta($post_id, "{$meta_key_num_added}{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}
		
		$num_added_field_groups_css = "";
		if ($num_added_field_groups > 0) $num_added_field_groups_css = "simple-fields-meta-box-field-group-wrapper-has-fields-added";

		$field_group_slug_css = "";
		if (isset($current_field_group["slug"]) && !empty($current_field_group["slug"])) {
			$field_group_slug_css = "simple-fields-meta-box-field-group-wrapper-slug-" . $current_field_group["slug"];
		}
	 
	 	$field_group_wrapper_css = "";

		$default_gui_view = isset( $current_field_group["gui_view"] ) ? $current_field_group["gui_view"] : "list";
		if ("table" === $default_gui_view) {
			$field_group_wrapper_css .= " simple-fields-meta-box-field-group-wrapper-view-table ";
		}

		echo "<div class='simple-fields-meta-box-field-group-wrapper $num_added_field_groups_css $field_group_slug_css $field_group_wrapper_css'>";
		echo "<input type='hidden' name='simple-fields-meta-box-field-group-id' value='$post_connector_field_id' />";
	 
		// show description
		if ( ! empty($current_field_group["description"]) ) {
			printf("<p class='%s'>%s</p>", "simple-fields-meta-box-field-group-description", esc_html( $this->get_string("Field group description, " . $current_field_group["slug"], $current_field_group["description"]) ) );
		}
		//echo "<pre>";print_r($current_field_group);echo "</pre>";
		
		if ($current_field_group["repeatable"]) {

			// Generate headline for the table view
			#sf_d($current_field_group);
			if ("table" === $default_gui_view) {
				echo "<div class='sf-cf simple-fields-metabox-field-group-view-table-headline-wrap'>";
				foreach ( $current_field_group["fields"] as $field_id => $field_arr ) {
					// sf_d($field_arr);
					printf('<div class="simple-fields-metabox-field-group-view-table-headline simple-fields-metabox-field-group-view-table-headline-count-%1$d">', $current_field_group["fields_count"]);
					printf('<div class="simple-fields-field-group-view-table-headline-name">%1$s</div>', $this->get_string( "Field name, " . $field_arr["slug"], $field_arr["name"] ) );
					printf('<div class="simple-fields-field-group-view-table-headline-description">%1$s</div>', $this->get_string("Field description, " . $field_arr["slug"], $field_arr["description"] ) );
					printf('</div>');
				}
				echo "</div>";
			}

			// Start of list with added field groups
			$ul_add_css = "";

			// add link at top	 
			echo "
				<div class='simple-fields-metabox-field-add simple-fields-metabox-field-add-top'>
					<a href='#'>+ ".__('Add', 'simple-fields')."</a>
					<!-- 
					|
					<a href='#' id='sfToggleView{$current_field_group["id"]}'>Toggle view</a>
					-->
				</div>
			";

			/*
			?>
			<script>
				jQuery(function($) {
					$("#sfToggleView<?php echo $current_field_group["id"] ?>").click(function(e) {
						e.preventDefault();
						$(this).closest(".simple-fields-meta-box-field-group-wrapper").find("ul:first").toggleClass("simple-fields-metabox-field-group-fields-view-table");
					});
				});
			</script>
			<?php
			*/

			// add test class to test table layout
			if ("table" === $default_gui_view) {
				$ul_add_css .= " simple-fields-metabox-field-group-fields-view-table";
			}

			// add class with number of fields in field group
			$ul_add_css .= " simple-fields-metabox-field-group-fields-count-" . $current_field_group["fields_count"];
			echo "<ul class='sf-cf simple-fields-metabox-field-group-fields simple-fields-metabox-field-group-fields-repeatable $ul_add_css'>";
	 
			// now add them. ooooh my, this is fancy stuff.
			$use_defaults = null;
			for ($num_in_set=0; $num_in_set<$num_added_field_groups; $num_in_set++) {
				$this->meta_box_output_one_field_group($post_connector_field_id, $num_in_set, $post_id, $use_defaults);  
			}
	 
			// end list with added field groups
			echo "</ul>";

			// add link at bottom
			echo "
				<div class='simple-fields-metabox-field-add simple-fields-metabox-field-add-bottom'>
					<a href='#'>+ ".__('Add', 'simple-fields')."</a>
				</div>
			";

	 
		} else {
			 
			// is this a new post, ie. should default values be used
			$been_saved = (bool) get_post_meta($post_id, "_simple_fields_been_saved", true);
			if ($been_saved) { $use_defaults = false; } else { $use_defaults = true; }
			 
			echo "<ul>";
			$this->meta_box_output_one_field_group($post_connector_field_id, 0, $post_id, $use_defaults);
			echo "</ul>";
	 
		}
		 
		echo "</div>";
	 
	} // end

	/**
	 * Returns all defined post connectors
	 * @return array
	 */
	function get_post_connectors() {

		// use wp_cache
		$cache_key = 'simple_fields_'.$this->ns_key.'_post_connectors';
		$connectors = wp_cache_get( $cache_key, 'simple_fields' );
		if (FALSE === $connectors) {

			$connectors = get_option("simple_fields_post_connectors");
	
			if ($connectors === FALSE) $connectors = array();
		
			// calculate number of active field groups
			// @todo: check this a bit more, does not seem to be any deleted groups. i thought i saved the deletes ones to, but with deleted flag set
			foreach (array_keys($connectors) as $i) {
				
				// Sanity check the connector id
				if (empty($connectors[$i]["id"]) && empty($connectors[$i]["deleted"])) {
					
					// Found field group without id, let's try to repair it
					$highest_id = 0;
					foreach($connectors as $one_connector) {
						if ($one_connector["id"] > $highest_id)
							$highest_id = $one_connector["id"];
						if ($one_connector["id"] === $i)
							$id_already_exists = true;
					}
					
					if ($i > 0 && !$id_already_exists) {
						// If the array key is larger than 0 and
						// not used as id by any other connector,
						// then it's the perfect id
						$connectors[$i]["id"] = $i;
					} else {
						// The array key is either less than or equal to 0,
						// or another connector is using it as id. In any case,
						// let's treat it as a new connector and give it a new id.
						$new_id = $highest_id + 1;
						$connectors[$i]["id"] = $new_id;
						
						// Now make sure the array key matches the new id
						$connectors[$new_id] = $connectors[$i];
						unset($connectors[$i]);
						$i = $new_id;
					}
					
				}
			
				// compatibility fix key vs slug
				if (isset($connectors[$i]["slug"]) && $connectors[$i]["slug"]) {
					$connectors[$i]["key"] = $connectors[$i]["slug"];
				} else if (isset($connectors[$i]["key"]) && $connectors[$i]["key"]) {
					$connectors[$i]["slug"] = $connectors[$i]["key"];
				}
			
				$num_fields_in_group = 0;
				if (isset($connectors[$i]["field_groups"]) && is_array($connectors[$i]["field_groups"])) {
					foreach ($connectors[$i]["field_groups"] as $one_group) {
						if (isset($one_group["deleted"]) && !$one_group["deleted"]) $num_fields_in_group++;
					}
				}
				$connectors[$connectors[$i]["id"]]["field_groups_count"] = $num_fields_in_group;
			}
			
			wp_cache_set( $cache_key, $connectors, 'simple_fields' );
			
		}
	
		return $connectors;
	}

	/**
	 * Returns 
	 */
	function get_post_type_defaults() {

		$post_type_defaults = wp_cache_get( 'simple_fields_'.$this->ns_key.'_post_type_defaults', 'simple_fields' );
		if (FALSE === $post_type_defaults) {
			$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
			wp_cache_set( 'simple_fields_'.$this->ns_key.'_post_type_defaults', $post_type_defaults, 'simple_fields' );
		}

		$post_type_defaults = apply_filters( "simple_fields_get_post_type_defaults", $post_type_defaults );

		return $post_type_defaults;

	}
	
	/**
	 * Returns all defined field groups
	 *
	 * @param $include_deleted should deletd fieldgroups and fields also be included? defaults to true for backwards compat
	 * @return array
	 */
	function get_field_groups($include_deleted = true) {
		
		$field_groups = wp_cache_get( 'simple_fields_'.$this->ns_key.'_groups', 'simple_fields' );

		if (FALSE === $field_groups) {
			
			$field_groups = get_option("simple_fields_groups");
			
			if ( $field_groups === FALSE || ! is_array( $field_groups ) ) $field_groups = array();

			// Calculate the number of active fields
			// And some other things

			// With each field group among all field groups
			foreach (array_keys($field_groups) as $i) {

				// Sanity check the field group id
				if (empty($field_groups[$i]["id"]) && empty($field_groups[$i]["deleted"])) {
					
					// Found field group without id, let's try to repair it
					$highest_id = 0;
					foreach($field_groups as $one_field_group) {
						if ($one_field_group["id"] > $highest_id)
							$highest_id = $one_field_group["id"];
						if ($one_field_group["id"] === $i)
							$id_already_exists = true;
					}
					
					if ($i > 0 && !$id_already_exists) {
						// If the array key is larger than 0 and
						// not used as id by any other field group,
						// then it's the perfect id
						$field_groups[$i]["id"] = $i;
					} else {
						// The array key is either less than or equal to 0,
						// or another field group is using it as id. In any case,
						// let's treat it as a new field group and give it a new id.
						$new_id = $highest_id + 1;
						$field_groups[$i]["id"] = $new_id;
						
						// Now make sure the array key matches the new id
						$field_groups[$new_id] = $field_groups[$i];
						unset($field_groups[$i]);
						$i = $new_id;
					}
					
				}
	
				// Make sure we have both key and slug set to same. key = old name for slug
				if (isset($field_groups[$i]["slug"]) && $field_groups[$i]["slug"]) {
					$field_groups[$i]["key"] = $field_groups[$i]["slug"];
				} else if (isset($field_groups[$i]["key"]) && $field_groups[$i]["key"]) {
					$field_groups[$i]["slug"] = $field_groups[$i]["key"];
				} else {
					// no slug set at all (for the pre-slug installs that are upgraded)
					$field_groups[$i]["slug"] = "";
					$field_groups[$i]["key"] = "";
				}
	
				// Calculate number of active fields in this field group
				// and add some extra info that is nice to have
				$num_active_fields = 0;
				foreach ( $field_groups[$i]["fields"] as $one_field ) {

					if ( ! $one_field["deleted"] ) $num_active_fields++;

					$one_field["meta_key"] = $this->get_meta_key( $field_groups[$i]["id"], $one_field["id"], null, $field_groups[$i]["slug"], $one_field["slug"] );

				}
				$field_groups[$i]["fields_count"] = $num_active_fields;

				// Also add some info about the field group is belongs to
				// This can be useful to have if you're only fetching a single field
				// but need to do something with that fields field group 
				// (like getting the id to calcualte that custom field meta key to use)
				foreach ($field_groups[$i]["fields"] as $one_field_id => $one_field) {

					// Info about the field group that this field belongs to did not exist, so add it
					$field_groups[$i]["fields"][$one_field_id]["field_group"] = array(
						"id"           => $field_groups[$i]["id"],
						"name"         => $field_groups[$i]["name"],
						"slug"         => $field_groups[$i]["slug"],
						"description"  => $field_groups[$i]["description"],
						"repeatable"   => $field_groups[$i]["repeatable"],
						"fields_count" => $field_groups[$i]["fields_count"]
					);

				}
			}

			// normalize it so all info is available in the new funky way
			$field_groups = $this->normalize_fieldgroups( $field_groups );

			wp_cache_set( 'simple_fields_'.$this->ns_key.'_groups', $field_groups, 'simple_fields' );
			
		} // cache false

		// Maybe remove deleted groups and fields
		if ( false === $include_deleted) {

			foreach ( $field_groups as $one_field_group_key => $one_field_group ) {

				if ( $one_field_group["deleted"] ) {
					unset( $field_groups[ $one_field_group_key ] );
					continue;
				}
				
				// Check fields now
				// Note: field keys are not in numerical order, they are in "visually order"
				foreach ( $field_groups[ $one_field_group_key ]["fields"] as $one_field_key => $one_field_value ) {
					if ( $one_field_value["deleted"] ) {
						// Remove both field by id and field by slug
						unset( $field_groups[ $one_field_group_key ]["fields"][ $one_field_key ] );
						unset( $field_groups[ $one_field_group_key ]["fields_by_slug"][ $one_field_value["slug"] ] );
					}
				}

			} // foreach

		} // if don't include deleted

		$field_groups = apply_filters( "simple_fields_get_field_groups", $field_groups );
		return $field_groups;
		
	}

	/**
	 * Get a field group
	 *
	 * Example:
	 * <code>
	 * global $sf;
	 * $my_field_group_id = 10;
	 * $field_group_info = $sf->get_field_group( $my_field_group_id );
	 * sf_d( $field_group_info , '$field_group_info' );
	 * </code>
	 *
	 * @param int $group_id
	 * @return array with field group or false if field group is not found
	 */
	function get_field_group($group_id) {

		$field_groups = $this->get_field_groups();
		$return = false;
		if (is_array($field_groups)) {
			foreach($field_groups as $field_group) {
				if (is_numeric($group_id)) {
					if ($field_group['id'] == $group_id) {
						$return = $field_group;
						break;
					}
				} else {
					if ($field_group['name'] == $group_id) {
						$return = $field_group;
						break;
					}
				}
			}
		}

		$return = apply_filters( "simple_fields_get_field_group", $return );
		return $return;

	}


	/**
	 * Returns a field from a field group
	 *
	 * @param int $field_group
	 * @param mixed $field_id id or name of field
	 * @return false on error
	 */
	function get_field_in_group($field_group, $field_id) {

		$return = false;
		if (is_array($field_group) && is_array($field_group['fields'])) {
			foreach($field_group['fields'] as $field) {
				if (is_numeric($field_id)) {
					if ($field['id'] == $field_id) {
						$return = $field;
						break;
					}
				} else {
					if ($field['name'] == $field_id) {
						$return = $field;
						break;
					}
				}
			}
		}
	
		$return = apply_filters( "simple_fields_get_field_in_group", $return, $field_group, $field_id);
		return $return;
	
	}


	/**
	 * meta box in sidebar in post edit screen
	 * let user select post connector to use for current post
	 */
	function edit_post_side_field_settings() {
		
		global $post, $sf;
		
		$arr_connectors = $this->get_post_connectors_for_post_type($post->post_type);
		$connector_default = $this->get_default_connector_for_post_type($post->post_type);
		$connector_selected = $this->get_selected_connector_for_post($post);
	
		// $connector_selected returns the id of the connector to use, yes, but we want the "real" connector, not the id of the inherited or so
		// this will be empty if this is a new post and default connector is __inherit__
		// if this is empty then use connector_selected. this may happen in post is new and not saved
		$saved_connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
		if (empty($saved_connector_to_use)) {
			$saved_connector_to_use = $connector_default;
		}
		/*
		echo "<br>saved_connector_to_use: $saved_connector_to_use";
		echo "<br>connector_selected: $connector_selected";
		echo "<br>connector_default: $connector_default";
		on parent post we can use simple_fields_get_selected_connector_for_post($post) to get the right one?
		can't use that function on the current post, because it won't work if we don't acually have inherit
		confused? I AM!
		*/
		
		// get name of inherited post connector
		$parents = get_post_ancestors($post);
		$str_inherit_parent_connector_name = __('(no parent found)', 'simple-fields');
		if (empty($parents)) {
		} else {
			$post_parent = get_post($post->post_parent);
			$parent_selected_connector = $this->get_selected_connector_for_post($post_parent);
			$str_parent_connector_name = "";
			if ($parent_selected_connector)
			foreach ($arr_connectors as $one_connector) {
				if ($one_connector["id"] == $parent_selected_connector) {
					$str_parent_connector_name = $one_connector["name"];
					break;
				}
			}
			if ($str_parent_connector_name) {
				$str_inherit_parent_connector_name = "({$str_parent_connector_name})";
			}
		}

		?>
		<div class="inside">

			<?php

			// If connector is set from template then that overrides dropdown
			if ( $this->post_has_template_connector( $post ) ) {
			
				$template = !empty($post->page_template) ? $post->page_template : false;
				$post_connector_from_template = $this->get_post_connector_from_template( $template );
				?>
				<p><?php _e( sprintf('Post connector is defined in template and is set to "%1$s"', $post_connector_from_template), "simple-fields") ?></p>
				<?php
			
			} else {

				// dropdown with post connectors ?>
				<div>
					<select name="simple_fields_selected_connector" id="simple-fields-post-edit-side-field-settings-select-connector">
						<option <?php echo ($saved_connector_to_use == "__none__") ? " selected='selected' " : "" ?> value="__none__"><?php _e('None', 'simple-fields') ?></option>
						<option <?php echo ($saved_connector_to_use == "__inherit__") ? " selected='selected' " : "" ?> value="__inherit__"><?php _e('Inherit from parent', 'simple-fields') ?>
							<?php
							echo $str_inherit_parent_connector_name;
							?>
						</option>
						<?php foreach ($arr_connectors as $one_connector) : ?>
							<?php if ($one_connector["deleted"]) { continue; } ?>
							<option <?php echo ($saved_connector_to_use == $one_connector["id"]) ? " selected='selected' " : "" ?> value="<?php echo $one_connector["id"] ?>"><?php echo $one_connector["name"] ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php

				// If connector has been changed with filter then show was connector is being used
				if ( is_numeric($connector_selected) && $connector_selected != $saved_connector_to_use ) {
					$connector_selected_info = $this->get_connector_by_id($connector_selected);
					?><div><p><?php _e("Actual used connector:", "simple-fields") ?> <?php echo $connector_selected_info["name"]; ?></p></div><?php
				}

				?>
				<div id="simple-fields-post-edit-side-field-settings-select-connector-please-save" class="hidden">
					<p><?php _e('Save post to switch to selected fields.', 'simple-fields') ?></p>
				</div>
				<?php
			
			}

			?>
			<div>
				<p><a href="#" id="simple-fields-post-edit-side-field-settings-show-keys"><?php _e('Show custom field keys', 'simple-fields') ?></a></p>
			</div>
		</div>
		<?php
	} // function 


	/**
	 * get selected post connector for a post
	 * a post has a post connector, or no connector
	 * this function will return the inherited connector if post is set to inherit connector
	 * unless it's the top most post since then nere are no more to inherit
	 * should not return be __none__ then?
	 *
	 * @param object $post or int post id
	 * @return id or string __none__
	 */
	function get_selected_connector_for_post($post) {
		/*
		om sparad connector finns för denna artikel, använd den
		om inte sparad connector, använd default
		om sparad eller default = inherit, leta upp connector för parent post
		*/
		#d($post);
		
		global $sf;
		
		// make sure $post is a post object
		if (is_numeric($post)) $post = get_post($post);
		
		$post_type = $post->post_type;
		$connector_to_use = null;
		if (!$post->ID) {
			// no id (new post), use default for post type
			$connector_to_use = $this->get_default_connector_for_post_type($post_type);
		} elseif ($post->ID) {
			// get saved connector for post
			$connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
			#var_dump($connector_to_use);
			if ($connector_to_use == "") {
				// no previous post connector saved, use default for post type
				$connector_to_use = $this->get_default_connector_for_post_type($post_type);
			}
		}
		
		// $connector_to_use is now a id or __none__ or __inherit__
	
		// if __inherit__, get connector from post_parent
		if ("__inherit__" == $connector_to_use && $post->post_parent > 0) {
			$parent_post_id = $post->post_parent;
			$parent_post = get_post($parent_post_id);
			$connector_to_use = $this->get_selected_connector_for_post($parent_post);
		} elseif ("__inherit__" == $connector_to_use && 0 == $post->post_parent) {
			// already at the top, so inherit should mean... __none__..? right?
			// hm.. no.. then the wrong value is selected in the drop down.. hm...
			#$connector_to_use = "__none__";
		}
		
		// if selected connector is deleted, then return none
		$post_connectors = $this->get_post_connectors();
		if (isset($post_connectors[$connector_to_use]["deleted"]) && $post_connectors[$connector_to_use]["deleted"]) {
			$connector_to_use = "__none__";
		}
	
		// Let user change to connector being used for post
		// Filter here can return a string that is the slug, 
		// and then we will get the id for that post before continuing
		$connector_to_use = apply_filters( "simple_fields_get_selected_connector_for_post", $connector_to_use, $post);
		if ( ! is_numeric($connector_to_use) && ! is_null( $connector_to_use ) && (preg_match('/^__/', $connector_to_use) !== 1) ) {
			$connector_to_use_info = $this->get_post_connector_by_slug( $connector_to_use );
			$connector_to_use = $connector_to_use_info["id"];
		}

		return $connector_to_use;
	
	} // function get_selected_connector_for_post


	/**
	 * Get post connector by its slug
	 *
	 * @param string $post_slug
	 * @return connector if found, false if not
	 */
	function get_post_connector_by_slug($post_slug) {
		$connectors = $this->get_post_connectors();
		foreach ($connectors as $one_connector) {
			if ( $one_connector["slug"] === $post_slug) return $one_connector;
		}
		return false;
	}


	/**
	 * Code from Admin Menu Tree Page View
	 */
	function get_pages($args) {
	
		global $sf;
	
		$defaults = array(
			"post_type" => "page",
			"xparent" => "0",
			"xpost_parent" => "0",
			"numberposts" => "-1",
			"orderby" => "menu_order",
			"order" => "ASC",
			"post_status" => "any"
		);
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( "simple_fields_get_pages_args", $args);
		$pages = get_posts($args);
	
		$output = "";
		$str_child_output = "";
		foreach ($pages as $one_page) {
			$edit_link = get_edit_post_link($one_page->ID);
			$title = get_the_title($one_page->ID);
			$title = esc_html($title);
					
			$class = "";
			if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["post"]) && $_GET["post"] == $one_page->ID) {
				$class = "current";
			}
	
			// add css if we have childs
			$args_childs = $args;
			$args_childs["parent"] = $one_page->ID;
			$args_childs["post_parent"] = $one_page->ID;
			$args_childs["child_of"] = $one_page->ID;
			$str_child_output = $this->get_pages($args_childs);
			
			$output .= "<li class='$class'>";
			$output .= "<a href='$edit_link' data-post-id='".$one_page->ID."'>";
			$output .= $title;
			$output .= "</a>";
	
			// add child articles
			$output .= $str_child_output;
			
			$output .= "</li>";
		}
		
		// if this is a child listing, add ul
		if (isset($args["child_of"]) && $args["child_of"] && $output != "") {
			$output = "<ul class='simple-fields-tree-page-tree_childs'>$output</ul>";
		}
		
		$output = apply_filters( "simple_fields_get_pages_output", $output, $args);
		return $output;
	}


	/**
	 * File browser dialog:
	 * hide some things there to make it more clean and user friendly
	 */
	function admin_head_select_file() {

		// Only output this css when we are showing a file dialog for simple fields
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			?>
			<style type="text/css">
				.wp-post-thumbnail, tr.image_alt, tr.post_title, tr.align, tr.image-size,tr.post_excerpt, tr.url, tr.post_content {
					display: none; 
				}
			</style>
			<?php
		}

	}

	
	/**
	 * used from file selector popup
	 * send the selected file to simple fields
	 */
	function media_send_to_editor($html, $id) {
	
		parse_str( isset( $_POST["_wp_http_referer"] ) ? $_POST["_wp_http_referer"] : "" , $arr_postinfo);
	
		// only act if file browser is initiated by simple fields
		if (isset($arr_postinfo["simple_fields_action"]) && $arr_postinfo["simple_fields_action"] == "select_file") {
	
			// add the selected file to input field with id simple_fields_file_field_unique_id
			$simple_fields_file_field_unique_id = $arr_postinfo["simple_fields_file_field_unique_id"];
			$file_id = (int) $id;
			
			$image_thumbnail = wp_get_attachment_image_src( $file_id, 'thumbnail', true );
			$image_thumbnail = $image_thumbnail[0];
			$image_html = "<img src='$image_thumbnail' alt='' />";
			$file_name = get_the_title($file_id);
			$post_file = get_post($file_id);
			$post_title = $post_file->post_title;
			$post_title = esc_html($post_title);
			$post_title = utf8_decode($post_title);
			$file_name = rawurlencode($post_title);
	
			?>
			<script>
				var win = window.dialogArguments || opener || parent || top;
				var file_id = <?php echo $file_id ?>;
				win.jQuery("#<?php echo $simple_fields_file_field_unique_id ?>").val(file_id);
				var sfmff = win.jQuery("#<?php echo $simple_fields_file_field_unique_id ?>").closest(".simple-fields-metabox-field-file");
				sfmff.find(".simple-fields-metabox-field-file-selected-image").html("<?php echo $image_html ?>").show();
				sfmff.closest(".simple-fields-metabox-field").find(".simple-fields-metabox-field-file-selected-image-name").html(unescape("<?php echo $file_name?>")).show();
				
				// show clear and edit-links
				var url = "<?php echo admin_url("post.php?post={$file_id}&action=edit") ?>";
	
				sfmff.find(".simple-fields-metabox-field-file-edit").attr("href", url).show();
				sfmff.find(".simple-fields-metabox-field-file-clear").show();
				
				// close popup
				win.tb_remove();
			</script>
			<?php
			exit;
		} else {
			return $html;
		}
	
	}
	

	/**
	 * if we have simple fields args in GET, make sure our simple fields-stuff are added to the form
	 */
	function media_upload_form_url($url) {
	
		foreach ($_GET as $key => $val) {
			if (strpos($key, "simple_fields_") === 0) {
				$url = add_query_arg($key, $val, $url);
			}
		}
		return $url;
	
	}


	/**
	 * remove gallery and remote url tab in file select
	 * also remove some
	 */
	function media_upload_tabs($arr_tabs) {
	
		if ( (isset($_GET["simple_fields_action"]) || isset($_GET["simple_fields_action"]) ) && ($_GET["simple_fields_action"] == "select_file" || $_GET["simple_fields_action"] == "select_file_for_tiny") ) {
			unset($arr_tabs["gallery"], $arr_tabs["type_url"]);
		}
	
		return $arr_tabs;
	}

	
	/**
	 * In file dialog:
	 * Change "insert into post" to something better
	 * 
	 * Code inspired by/gracefully stolen from
	 * http://mondaybynoon.com/2010/10/12/attachments-1-5/#comment-27524
	 */
	function post_admin_init() {
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			add_filter('gettext', array($this, 'hijack_thickbox_text'), 1, 3);
		}
	}
	
	function hijack_thickbox_text($translated_text, $source_text, $domain) {
		if (isset($_GET["simple_fields_action"]) && $_GET["simple_fields_action"] == "select_file") {
			if ('Insert into Post' == $source_text) {
				return __('Select', 'simple_fields' );
			}
		}
		return $translated_text;
	}


	/**
	 * Field type: post
	 * Fetch content for field type post dialog via AJAX
	 * Used for field type post
	 * Called from ajax with action wp_ajax_simple_fields_field_type_post_dialog_load
	 * Ajax defined in scripts.js -> $("a.simple-fields-metabox-field-post-select")
	 */
	function field_type_post_dialog_load() {
	
		global $sf;
	
		$arr_enabled_post_types = isset($_POST["arr_enabled_post_types"]) ? $_POST["arr_enabled_post_types"] : array();
		$str_enabled_post_types = isset($_POST["str_enabled_post_types"]) ? $_POST["str_enabled_post_types"] : "";
		$additional_arguments = isset($_POST["additional_arguments"]) ? $_POST["additional_arguments"] : "";
		$existing_post_types = get_post_types(NULL, "objects");
		$selected_post_type = isset($_POST["selected_post_type"]) ? (string) $_POST["selected_post_type"] : "";

		if (empty($arr_enabled_post_types)) {
			$arr_enabled_post_types = explode(",", $str_enabled_post_types);
		}

		/*echo "<br>selected_post_type: $selected_post_type";
		echo "<br>str_enabled_post_types: $str_enabled_post_types";
		echo "<br>enabled post types:"; print_r($arr_enabled_post_types);*/

		// If no post type is selected then don't show any posts
		if (empty($arr_enabled_post_types)) {
			_e("<p>No post type is selected. Please at at least one post type in Simple Fields.</p>", "simple-fields");
			exit;
		}
		?>
	
		<?php if (count($arr_enabled_post_types) > 1) { ?>
			<p>Show posts of type:</p>
			<ul class="simple-fields-meta-box-field-group-field-type-post-dialog-post-types">
				<?php
				$loopnum = 0;
				foreach ($existing_post_types as $key => $val) {
					if (!in_array($key, $arr_enabled_post_types)) {
						continue;
					}
					if (empty($selected_post_type) && $loopnum == 0) {
						$selected_post_type = $key;
					}
					$class = "";
					if ($selected_post_type == $key) {
						$class = "selected";
					}
					printf("\n<li class='%s'><a href='%s'>%s</a></li>", $class, "$key", $val->labels->name);
					$loopnum++;
				}
			?>
			</ul>
			<?php 
		} else {
			$selected_post_type = $arr_enabled_post_types[0];
			?>
			<p>Showing posts of type: <a href="<?php echo $selected_post_type; ?>"><?php echo $existing_post_types[$selected_post_type]->labels->name; ?></a></p>
			<?php 
		} ?>
		
		<div class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts-wrap">
			<ul class="simple-fields-meta-box-field-group-field-type-post-dialog-post-posts">
				<?php
	
				// get root items
				$args = array(
					"echo" => 0,
					"sort_order" => "ASC",
					"sort_column" => "menu_order",
					"post_type" => $selected_post_type,
					"post_status" => "publish"
				);
				
				$hierarchical = (bool) $existing_post_types[$selected_post_type]->hierarchical;
				if ($hierarchical) {
					$args["parent"] = 0;
					$args["post_parent"] = 0;
				}
				
				if (!empty($additional_arguments)) {
					$args = wp_parse_args( $additional_arguments, $args );
				}
			
				$output = $this->get_pages($args);
				echo $output;
				?>
			</ul>
		</div>
		<div class="submitbox">
			<div class="simple-fields-postdialog-link-cancel">
				<a href="#" class="submitdelete deletion">Cancel</a>
			</div>
		</div>
		<?php
			
		exit;
	}
	
	/**
	 * Returns the output for a new or existing field with all it's options
	 * Used in options screen / admin screen
	 */
	function field_group_add_field_template($fieldID, $field_group_in_edit = null) {

		$fields = $field_group_in_edit["fields"];
		// simple_fields::debug("field_grup_in_edit", $fields);
		$field_name = esc_html($fields[$fieldID]["name"]);
		$field_description = esc_html($fields[$fieldID]["description"]);
		$field_slug = esc_html(@$fields[$fieldID]["slug"]);
		$field_type = $fields[$fieldID]["type"];
		$field_deleted = (int) $fields[$fieldID]["deleted"];

		// If this is a new field then set default type to text so user does not save field with no field type set
		if ($field_type === NULL) $field_type = "text";
		
		$field_type_file_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_file_options"]["enable_extended_return_values"];

		$field_type_user_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_user_options"]["enable_extended_return_values"];

		$field_type_textarea_option_use_html_editor	= (int) 	@$fields[$fieldID]["type_textarea_options"]["use_html_editor"];
		$field_type_textarea_option_size_height		= (string) 	@$fields[$fieldID]["type_textarea_options"]["size_height"];
		
		$field_type_checkbox_option_checked_by_default = (int) @$fields[$fieldID]["type_checkbox_options"]["checked_by_default"];
		$field_type_checkbox_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_checkbox_options"]["enable_extended_return_values"];
		
		$field_type_radiobuttons_options = (array) @$fields[$fieldID]["type_radiobuttons_options"];
		$field_type_radiobuttons_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_radiobuttons_options"]["enable_extended_return_values"];
		
		$field_type_dropdown_options = (array) @$fields[$fieldID]["type_dropdown_options"];
		$field_type_dropdown_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_dropdown_options"]["enable_extended_return_values"];
		$field_type_dropdown_option_enable_multiple = (int) @$fields[$fieldID]["type_dropdown_options"]["enable_multiple"];
		
		$field_type_post_options = (array) @$fields[$fieldID]["type_post_options"];
		$field_type_post_options["enabled_post_types"] = (array) @$field_type_post_options["enabled_post_types"];
		$field_type_post_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_post_options"]["enable_extended_return_values"];

		$field_type_taxonomy_options = (array) @$fields[$fieldID]["type_taxonomy_options"];
		$field_type_taxonomy_options["enabled_taxonomies"] = (array) @$field_type_taxonomy_options["enabled_taxonomies"];
		$field_type_taxonomy_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_taxonomy_options"]["enable_extended_return_values"];
	
		$field_type_date_options = (array) @$fields[$fieldID]["type_date_options"];
		$field_type_date_option_use_time = @$field_type_date_options["use_time"];
		$field_type_date_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_date_options"]["enable_extended_return_values"];
	
		$field_type_taxonomyterm_options = (array) @$fields[$fieldID]["type_taxonomyterm_options"];
		$field_type_taxonomyterm_options["enabled_taxonomy"] = (string) @$field_type_taxonomyterm_options["enabled_taxonomy"];
		$field_type_taxonomyterm_option_enable_extended_return_values = (int) @$fields[$fieldID]["type_date_options"]["enable_taxonomyterm_return_values"];
	
		// Options saved for this field
		// Options is an array with key = field_type and value = array with options key => saved value
		$field_options = (array) @$fields[$fieldID]["options"];

		// Generate output for registred field types
		$registred_field_types_output = "";
		$registred_field_types_output_options = "";
		foreach ($this->registered_field_types as $one_field_type) {

			// Output for field type selection dropdown
			$registred_field_types_output .= sprintf('<option %3$s value="%1$s">%2$s</option>', 
				$one_field_type->key, 
				$one_field_type->name, 
				($field_type == $one_field_type->key) ? " selected " : ""
			);

			$field_type_options = isset($field_options[$one_field_type->key]) && is_array($field_options[$one_field_type->key]) ? $field_options[$one_field_type->key] : array();
			/*
			$field_type_options looks like this:
			Array
			(
				[myTextOption] => No value entered yet
				[mapsTextarea] => Enter some cool text here please!
				[funkyDropdown] => 
			)
			*/
			
			// Generate common and unique classes for this field types options row
			$div_class  = "simple-fields-field-group-one-field-row ";
			$div_class .= "simple-fields-field-type-options ";
			$div_class .= "simple-fields-field-type-options-" . $one_field_type->key . " ";
			$div_class .= ($field_type == $one_field_type->key) ? "" : " hidden ";
			
			// Generate and set the base for ids and names that the field will use for input-elements and similar
			$field_options_id 	= "field_{$fieldID}_options_" . $one_field_type->key . "";
			$field_options_name	= "field[$fieldID][options][" . $one_field_type->key . "]";
			$one_field_type->set_options_base_id($field_options_id);
			$one_field_type->set_options_base_name($field_options_name);
			
			// Gather together the options output for this field type
			// Only output fieldset if field has options
			$field_options_output = $one_field_type->options_output($field_type_options);
			if ($field_options_output) {
				$field_options_output = "
					<!-- <fieldset> 
						<legend>Options</legend> -->
						$field_options_output
					<!-- </fieldset> -->
				";
				
			}
			$registred_field_types_output_options .= sprintf(
				'
					<div class="%1$s">
						%2$s
					</div>
				', 
				$div_class, 
				$field_options_output
			);

		} // end output registered field types
		
		$out = "";
		$out .= "<li class='simple-fields-field-group-one-field simple-fields-field-group-one-field-id-{$fieldID}'>
			<div class='simple-fields-field-group-one-field-handle'></div>
	
			<div class='simple-fields-field-group-one-field-row'>
				<label class='simple-fields-field-group-one-field-name-label'>".__('Name', 'simple-fields')."</label>
				<input type='text' class='regular-text simple-fields-field-group-one-field-name' name='field[{$fieldID}][name]' value='{$field_name}' />
			</div>
					
			<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-slug'>
				<label>".__('Slug', 'simple-fields')."</label>
				<input 
					type='text' class='regular-text' 
					name='field[{$fieldID}][slug]' 
					value='{$field_slug}' 
					pattern='".$this->get_slug_pattern()."'
					title='".$this->get_slug_title()."'
					required
					 /> 
				<br><span class='description'>" . __('A unique identifier used in your theme to get the saved values of this field.', 'simple-fields') . "</span>
			</div>

			<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-description'>
				<label>".__('Description', 'simple-fields')."</label>
				<input type='text' class='regular-text' name='field[{$fieldID}][description]' value='{$field_description}' />
			</div>

			<div class='simple-fields-field-group-one-field-row'>
				<label>".__('Type', 'simple-fields')."</label>
				<!-- <br> -->
				<select name='field[{$fieldID}][type]' class='simple-fields-field-type'>
					<option value=''>".__('Select', 'simple-fields')."...</option>
					<option value='text'" . (($field_type=="text") ? " selected='selected' " : "") . ">".__('Text', 'simple-fields')."</option>
					<option value='textarea'" . (($field_type=="textarea") ? " selected='selected' " : "") . ">".__('Textarea', 'simple-fields')."</option>
					<option value='checkbox'" . (($field_type=="checkbox") ? " selected='selected' " : "") . ">".__('Checkbox', 'simple-fields')."</option>
					<option value='radiobuttons'" . (($field_type=="radiobuttons") ? " selected='selected' " : "") . ">".__('Radio buttons', 'simple-fields')."</option>
					<option value='dropdown'" . (($field_type=="dropdown") ? " selected='selected' " : "") . ">".__('Dropdown', 'simple-fields')."</option>
					<option value='file'" . (($field_type=="file") ? " selected='selected' " : "") . ">".__('File', 'simple-fields')."</option>
					<option value='post'" . (($field_type=="post") ? " selected='selected' " : "") . ">".__('Post', 'simple-fields')."</option>
					<option value='taxonomy'" . (($field_type=="taxonomy") ? " selected='selected' " : "") . ">".__('Taxonomy', 'simple-fields')."</option>
					<option value='taxonomyterm'" . (($field_type=="taxonomyterm") ? " selected='selected' " : "") . ">".__('Taxonomy Term', 'simple-fields')."</option>
					<option value='color'" . (($field_type=="color") ? " selected='selected' " : "") . ">".__('Color', 'simple-fields')."</option>
					<option value='date'" . (($field_type=="date") ? " selected='selected' " : "") . ">".__('Date', 'simple-fields')."</option>
					<option value='user'" . (($field_type=="user") ? " selected='selected' " : "") . ">".__('User', 'simple-fields')."</option>
					$registred_field_types_output
				</select>
	
				<div class='simple-fields-field-group-one-field-row " . (($field_type=="text") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-text'>
				</div>
			</div>
	
			$registred_field_types_output_options
			";
			
			// options for text
			$field_text_options = isset($field_options["text"]) ? (array) $field_options["text"] : array();
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="text") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-text'>";

			$arr_text_input_types = $this->get_html_text_types();
			$str_input_types_select = "";
			$prev_input_version = "";

			// default sub type is text, since that's the only was that existed before 1.2
			$selected_sub_type = isset( $field_text_options["subtype"] ) ? $field_text_options["subtype"] : "text";

			foreach ( $arr_text_input_types as $one_input_type_key => $one_input_type ) {
				if ($prev_input_version != $one_input_type["version"]) {
					if ("html" == $one_input_type["version"]) {
						$str_input_types_select .= "<optgroup label='" . _x("Plain Old HTML", "Text field options", "simple-fields") . "'>";
					} else if ("html5" == $one_input_type["version"]) {
						$str_input_types_select .= "<optgroup label='" . _x("New Fancy HTML5 Input Types", "Text field options", "simple-fields") . "'>";
					}
					$prev_input_version = $one_input_type["version"];
				}
				$str_input_types_select .= sprintf('<option %3$s value="%2$s">%1$s</option>', $one_input_type["description"], $one_input_type_key, ($one_input_type_key === $selected_sub_type) ? "selected" : "" );
			}

			$out .= sprintf('
				<div class="simple-fields-field-group-one-field-row">
					<div class="simple-fields-field-group-one-field-row-col-first">
						<label>%1$s</label>
					</div>
					<div class="simple-fields-field-group-one-field-row-col-second">
						<select name="%4$s">
							%2$s
						</select>
						<br>
						<span class="description">%3$s</span>
					</div>
				</div>
				', 
			 	_x("Sub type", "Text field options", "simple-fields"),
			 	$str_input_types_select,
			 	sprintf( _x( 'HTML5 introduces new input types, that fall back to text input in browsers that don\'t support them. <a target="_blank" href="%1$s">Read more at HTML5 Rocks</a>.', "simple-fields"), "http://www.html5rocks.com/en/tutorials/forms/html5forms/" ),
			 	"field[{$fieldID}][options][text][subtype]"
			);

			// text, placeholder
			$out .= "
				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<label>" . _x('Placeholder text', 'Text field options', 'simple-fields') . "</label>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input class='regular-text' type='text' name='field[{$fieldID}][options][text][placeholder]' value='" . esc_attr( isset( $field_text_options["placeholder"] ) ? $field_text_options["placeholder"] : "" ) . "'>
						<br>
						<span class='description'>" . __("A hint to the user of what can be entered in the field.", "simple-fields") . "</span>
					</div>
				</div>
			";

			// text, custom attributes
			$out .= "
				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<label>" . _x('Attributes', 'Text field options', 'simple-fields') . "</label>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input 
							class='regular-text' 
							type='text' 
							name='field[{$fieldID}][options][text][attributes]' 
							placeholder='" . _x('attribute_1="value_1" attribute_2="value_2"', 'Text field options', 'simple-fields') . "'
							value='" . esc_attr( isset( $field_text_options["attributes"] ) ? esc_attr( $field_text_options["attributes"] ) : "" ) . "'>
						<br>
						<span class='description'>" . __("Add your own attributes to the input tag.", "simple-fields") . "</span>
					</div>
				</div>
			";

			// end text options
			$out .= "
			</div>
			";

			// options for textarea
			$field_textarea_options = isset($field_options["textarea"]) ? (array) $field_options["textarea"] : array();
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="textarea") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-textarea'>
				
				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<label>" . _x('Height', 'Textarea default height', 'simple-fields') . "</label>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input " . ((empty($field_type_textarea_option_size_height) || $field_type_textarea_option_size_height == "default") ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='default'> " . _x('Default', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "small" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='small'> " . _x('Small', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "medium" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='medium'> " . _x('Medium', 'Textarea default height', 'simple-fields') . " &nbsp;
						<input " . ($field_type_textarea_option_size_height == "large" ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_textarea_options][size_height]' value='large'> " . _x('Large', 'Textarea default height', 'simple-fields') . " &nbsp;
					</div>
				</div>

				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input type='checkbox' name='field[{$fieldID}][type_textarea_options][use_html_editor]' " . (($field_type_textarea_option_use_html_editor) ? " checked='checked'" : "") . " value='1' /> " . __('Use HTML-editor', 'simple-fields') . "
					</div>
				</div>

				<div class='simple-fields-field-group-one-field-row'>
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<label>" . _x('Placeholder text (does not work with HTML editor enabled', 'Textarea field options', 'simple-fields') . "</label>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<input class='regular-text' type='text' name='field[{$fieldID}][options][textarea][placeholder]' value='" . esc_attr( isset( $field_textarea_options["placeholder"] ) ? $field_textarea_options["placeholder"] : "" ) . "'>
					</div>
				</div>

			</div>
			";

			// User
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="user") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-user'>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-first'></div>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= "		<p><input type='checkbox' name='field[{$fieldID}][type_user_options][enable_extended_return_values]' " . (($field_type_user_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
			$out .= 		__('Enable Extended Return Values', 'simple-fields') . "</p>";
			$out .= "		<p class='description'>" . __('Return an array with the name, email and full WP_User-object of the selected user, instead of just the user ID.', 'simple-fields') . "</p>";
			$out .= "	</div>";
			$out .= "</div>";

			// File
			$out .= "<div class='simple-fields-field-group-one-field-row " . (($field_type=="file") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-file'>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-first'></div>";
			$out .= "	<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= "		<p><input type='checkbox' name='field[{$fieldID}][type_file_options][enable_extended_return_values]' " . (($field_type_file_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
			$out .= 		__('Enable Extended Return Values', 'simple-fields') . "</p>";
			$out .= "		<p class='description'>" . __('Return an array with attachment title, path, etc., instead of just a post ID.', 'simple-fields') . "</p>";
			$out .= "	</div>";
			$out .= "</div>";

			// Date
			$out .= "<div class='" . (($field_type=="date") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-date'>";
				
				$out .= "<div class='simple-fields-field-group-one-field-row'>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
					$out .= "	<!-- <p><input type='checkbox' name='field[{$fieldID}][type_date_options][use_time]' " . (($field_type_date_option_use_time) ? " checked='checked'" : "") . " value='1' /> ".__('Also show time', 'simple-fields') . "</p> -->";
					$out .= "</div>";
				$out .= "</div>";
	
				$out .= "<div class='simple-fields-field-group-one-field-row'>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
					$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
					$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_date_options][enable_extended_return_values]' " . (($field_type_date_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
					$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
					$out .= "	<p class='description'>" . __('Return an array with the selected date as a unix timestamp and as the date format set in WordPress settings.', 'simple-fields') . "</p>";
					$out .= "</div>";
				$out .= "	</div>";

			$out .= "</div>";
		
	
			// connect post - select post types
			$out .= "<div class='" . (($field_type=="post") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-post'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Post types to select from', 'simple-fields'));
			$out .= "</div>";
			//$out .= sprintf("<select name='%s'>", "field[$fieldID][type_post_options][post_type]");
			//$out .= sprintf("<option %s value='%s'>%s</option>", (empty($field_type_post_options["post_type"]) ? " selected='selected' " : "") ,"", "Any");
	
			// list all post types in checkboxes
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$post_types = get_post_types(NULL, "objects");
			$loopnum = 0;
			foreach ($post_types as $one_post_type) {
				// skip some built in types
				if (in_array($one_post_type->name, array("attachment", "revision", "nav_menu_item"))) {
					continue;
				}
				$input_name = "field[{$fieldID}][type_post_options][enabled_post_types][]";
				$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s</input>", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name,
									((in_array($one_post_type->name, $field_type_post_options["enabled_post_types"])) ? " checked='checked' " : ""), 
									$one_post_type->name, 
									$one_post_type->labels->name . " ($one_post_type->name)"
								);
				$loopnum++;
			}
			$out .= "</div>";
			$out .= "</div>";
	
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= "<label>Additional arguments</label>";
			$out .= "</div>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= sprintf("<input class='regular-text' type='text' name='%s' value='%s' />", "field[$fieldID][type_post_options][additional_arguments]", @$field_type_post_options["additional_arguments"]);
			$out .= sprintf("<br><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Class_Reference/WP_Query'>WP_Query</a>.</span>");
			$out .= "</div>"; // second
			$out .= "</div>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_post_options][enable_extended_return_values]' " . (($field_type_post_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the title, permalink, and complete post object of the selected post, instead of just the ID.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "</div>"; // whole divs that shows/hides
	
	
			// connect taxonomy - select taxonomies
			$out .= "<div class='" . (($field_type=="taxonomy") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomy'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Taxonomies to show in dropdown', 'simple-fields'));
			$out .= "</div>"; // col first
			
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$taxonomies = get_taxonomies(NULL, "objects");
			$loopnum = 0;
			foreach ($taxonomies as $one_tax) {
				// skip some built in types
				if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
					continue;
				}
				$input_name = "field[{$fieldID}][type_taxonomy_options][enabled_taxonomies][]";
				$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name, 
									((in_array($one_tax->name, $field_type_taxonomy_options["enabled_taxonomies"])) ? " checked='checked' " : ""), 
									$one_tax->name, 
									$one_tax->labels->name . " ($one_tax->name)"
								);
				$loopnum++;
			}
			$out .= "</div>"; // second
			$out .= "</div>"; // row

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_taxonomy_options][enable_extended_return_values]' " . (($field_type_taxonomy_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with name and complete taxonomy object of the selected taxonomy, instead of just the ID.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "</div>";
			
	
			// taxonomyterm - select taxonomies, like above
			$out .= "<div class='" . (($field_type=="taxonomyterm") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomyterm'>";
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= sprintf("<label>%s</label>", __('Taxonomy to select terms from', 'simple-fields'));
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$taxonomies = get_taxonomies(NULL, "objects");
			$loopnum = 0;
			foreach ($taxonomies as $one_tax) {
				// skip some built in types
				if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
					continue;
				}
				$input_name = "field[{$fieldID}][type_taxonomyterm_options][enabled_taxonomy]";
				$out .= sprintf("%s<input name='%s' type='radio' %s value='%s'> %s", 
									($loopnum>0 ? "<br>" : ""), 
									$input_name, 
									($one_tax->name == $field_type_taxonomyterm_options["enabled_taxonomy"]) ? " checked='checked' " : "", 
									$one_tax->name, 
									$one_tax->labels->name . " ($one_tax->name)"
								);
				$loopnum++;
			}
			$out .= "</div>"; // second
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-first'>";
			$out .= "<label>".__("Additional arguments", "simple-fields")."</label>";
			$out .= "</div>";
			$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
			$out .= sprintf("<input class='regular-text' type='text' name='%s' value='%s' />", "field[$fieldID][type_taxonomyterm_options][additional_arguments]", @$field_type_taxonomyterm_options["additional_arguments"]);
			$out .= sprintf("<br><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Function_Reference/get_terms#Parameters'>get_terms()</a>.</span>");
			$out .= "</div>"; // second
			$out .= "</div>";
			
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_taxonomyterm_options][enable_extended_return_values]' " . (($field_type_taxonomyterm_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return a multi dimensional array with all the taxonomy terms objects, instead of just the IDs of the terms.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";
			
			$out .= "</div>";
	
			// radiobuttons
			$radio_buttons_added = "";
			$radio_buttons_highest_id = 0;
			if ($field_type_radiobuttons_options) {
				foreach ($field_type_radiobuttons_options as $key => $val) {

					$is_deleted = isset( $val["deleted"] ) && $val["deleted"] == true;

					if ( strpos( $key, "radiobutton_num_" ) !== false && ! $is_deleted ) {
						// found one button in format radiobutton_num_0
						$radiobutton_num = str_replace("radiobutton_num_", "", $key);
						if ($radiobutton_num > $radio_buttons_highest_id) {
							$radio_buttons_highest_id = $radiobutton_num;
						}
						$radiobutton_val = esc_html($val["value"]);
						$checked = ($key == @$field_type_radiobuttons_options["checked_by_default_num"]) ? " checked='checked' " : "";
						$radio_buttons_added .= "
							<li>
								<div class='simple-fields-field-type-options-radiobutton-handle'></div>
								<input class='regular-text' value='$radiobutton_val' name='field[$fieldID][type_radiobuttons_options][radiobutton_num_{$radiobutton_num}][value]' type='text' />
								<input class='simple-fields-field-type-options-radiobutton-checked-by-default-values' type='radio' name='field[$fieldID][type_radiobuttons_options][checked_by_default_num]' value='radiobutton_num_{$radiobutton_num}' {$checked} />
								<input class='simple-fields-field-type-options-radiobutton-deleted' name='field[$fieldID][type_radiobuttons_options][radiobutton_num_{$radiobutton_num}][deleted]' type='hidden' value='0' />
								<a href='#' class='simple-fields-field-type-options-radiobutton-delete'>Delete</a>
							</li>";
					}
				}

			}
			$radio_buttons_highest_id++;
			$out .= "<div class='" . (($field_type=="radiobuttons") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-radiobuttons'>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "	<p><input type='checkbox' name='field[{$fieldID}][type_radiobuttons_options][enable_extended_return_values]' " . (($field_type_radiobuttons_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the value of the selected radiobutton + the values of the non-selected radiobuttons.', 'simple-fields') . "</p>";
				$out .= "</div>";
			$out .= "	</div>";

			$out .= "
				<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-radiobuttons-values'>

					<div class='simple-fields-field-group-one-field-row-col-first'>
						<div>" . __("Values", "simple-fields") . "</div>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<div class='simple-fields-field-type-options-radiobutton-checked-by-default'>".__('Default', 'simple-fields')."</div>
						<ul class='simple-fields-field-type-options-radiobutton-values-added'>
							$radio_buttons_added
						</ul>
						<div><a class='simple-fields-field-type-options-radiobutton-values-add' href='#'>+ ".__('Add radio button', 'simple-fields')."</a></div>
						<input type='hidden' name='' class='simple-fields-field-group-one-field-radiobuttons-highest-id' value='{$radio_buttons_highest_id}' />
					</div><!-- // second -->
				
				</div>
			</div><!-- show/hide div -->
			";
			// end radiobuttons
	
			// checkbox
			$out .= "
			<div class='simple-fields-field-group-one-field-row " . (($field_type=="checkbox") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-checkbox'>
				<input type='checkbox' name='field[{$fieldID}][type_checkbox_options][checked_by_default]' " . (($field_type_checkbox_option_checked_by_default) ? " checked='checked'" : "") . " value='1' /> ".__('Checked by default', 'simple-fields')."
			</div>
			";
			// end checkbox
	
			// start dropdown
			$dropdown_values_added = "";
			$dropdown_values_highest_id = 0;
			if ($field_type_dropdown_options) {
				foreach ($field_type_dropdown_options as $key => $val) {
					
					$is_deleted = isset( $val["deleted"] ) && $val["deleted"] == true;
					if (strpos($key, "dropdown_num_") !== false && ! $is_deleted ) {
						// found one button in format radiobutton_num_0
						$dropdown_num = str_replace("dropdown_num_", "", $key);
						if ($dropdown_num > $dropdown_values_highest_id) {
							$dropdown_values_highest_id = $dropdown_num;
						}
						$dropdown_val = esc_html($val["value"]);
						$dropdown_values_added .= "
							<li>
								<div class='simple-fields-field-type-options-dropdown-handle'></div>
								<input class='regular-text' value='$dropdown_val' name='field[$fieldID][type_dropdown_options][dropdown_num_{$dropdown_num}][value]' type='text' />
								<input class='simple-fields-field-type-options-dropdown-deleted' name='field[$fieldID][type_dropdown_options][dropdown_num_{$dropdown_num}][deleted]' type='hidden' value='0' />
								<a href='#' class='simple-fields-field-type-options-dropdown-delete'>".__('Delete', 'simple-fields')."</a>
							</li>";
					}

				}
			}
			$dropdown_values_highest_id++;
			$out .= "<div class='" . (($field_type=="dropdown") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-dropdown'>";

			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";

				// Enable extended
				$out .= "	<p>";
				$out .= "		<input type='checkbox' name='field[{$fieldID}][type_dropdown_options][enable_extended_return_values]' " . (($field_type_dropdown_option_enable_extended_return_values) ? " checked='checked'" : "") . " value='1' /> ";
				$out .= 	__('Enable Extended Return Values', 'simple-fields') . "</p>";
				$out .= "	<p class='description'>" . __('Return an array with the value of the selected item in the dropdown + the values of the non-selected items.', 'simple-fields') . "</p>";

				$out .= "</div>";
			$out .= "	</div>";

			// Enable multiple
			$out .= "<div class='simple-fields-field-group-one-field-row'>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-first'></div>";
				$out .= "<div class='simple-fields-field-group-one-field-row-col-second'>";
				$out .= "<input " . ($field_type_dropdown_option_enable_multiple === 0 ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_dropdown_options][enable_multiple]' value='0'> ";
				$out .= _x('Single', 'Field type dropdown', 'simple-fields') . " &nbsp;";

				$out .= "<input " . ($field_type_dropdown_option_enable_multiple === 1 ? " checked=checked " : "")  . " type='radio' name='field[{$fieldID}][type_dropdown_options][enable_multiple]' value='1'> ";
				$out .= _x('Multiple', 'Field type dropdown', 'simple-fields') . " &nbsp;";

				$out .= "</div>";
			$out .= "	</div>";

			$out .= "
					<div class='simple-fields-field-group-one-field-row-col-first'>
						<p>".__('Values', 'simple-fields')."</p>
					</div>
					<div class='simple-fields-field-group-one-field-row-col-second'>
						<ul class='simple-fields-field-type-options-dropdown-values-added'>
							$dropdown_values_added
						</ul>
						<div><a class='simple-fields-field-type-options-dropdown-values-add' href='#'>+ ".__('Add dropdown value', 'simple-fields')."</a></div>
						<input type='hidden' name='' class='simple-fields-field-group-one-field-dropdown-highest-id' value='{$dropdown_values_highest_id}' />
					</div>
				</div>
			";
			// end dropdown
	
	
			$out .= "
			<div class='delete'>
				<a href='#'>".__('Delete field', 'simple-fields')."</a>
			</div>
			<input type='hidden' name='field[{$fieldID}][id]' class='simple-fields-field-group-one-field-id' value='{$fieldID}' />
			<input type='hidden' name='field[{$fieldID}][deleted]' value='{$field_deleted}' class='hidden_deleted' />
	
		</li>";

		return $out;
	
	} // /simple_fields_field_group_add_field_template

	/**
	 * Called from AJAX call to add a field group to the post in edit
	 */
	function field_group_add_field() {

		global $sf;
		$simple_fields_highest_field_id = (int) $_POST["simple_fields_highest_field_id"];
		echo $this->field_group_add_field_template($simple_fields_highest_field_id);
		exit;

	}


	/**
	 * Output all stuff for the options page
	 */
	function options_page() {

		require( dirname(__FILE__) . "/inc-admin-options.php" );

	}

	/**
	 * Add the admin menu page for simple fields
	 * If you want to hide this for some reason (maybe you are a theme developer that want to use simple fields, but not show the options page to your users)
	 * you can add a filter like this:
	 *
	 * add_filter("simple-fields-add-admin-menu", function($bool) {
	 *     return FALSE;
	 * });
	 *
	 */
	function admin_menu() {
				
		$show_submenu_page = TRUE;
		$show_submenu_page = apply_filters("simple-fields-add-admin-menu", $show_submenu_page);
		if ($show_submenu_page) {
			add_submenu_page( 'options-general.php' , SIMPLE_FIELDS_NAME, SIMPLE_FIELDS_NAME, "administrator", "simple-fields-options", array($this, "options_page"));
		}
		
	}


	/**
	 * Gets the post connectors for a post type
	 *
	 * @return array
	 */
	function get_post_connectors_for_post_type($post_type) {
		
		global $sf;
		
		$arr_post_connectors = $this->get_post_connectors();
		$arr_found_connectors = array();
	
		foreach ($arr_post_connectors as $one_connector) {
			if ($one_connector && in_array($post_type, $one_connector["post_types"])) {
				$arr_found_connectors[] = $one_connector;
			}
		}

		$arr_found_connectors = apply_filters( "simple_fields_get_post_connectors_for_post_type", $arr_found_connectors, $post_type);
		return $arr_found_connectors;

	}
	
	/**
	 * Registers a new field type
	 * @param string $field_type_name Name of the class with the new field type
	 */
	static function register_field_type($field_type_name) {
		global $sf;
		$sf->_register_field_type($field_type_name);
	}

	function _register_field_type($field_type_name) {
		$custom_field_type = new $field_type_name;
		$this->registered_field_types[$custom_field_type->key] = $custom_field_type;
	}

	/**
	 * Get all options
	 * @return array
	 */
	function get_options() {

		$options = (array) get_option("simple_fields_options");
		$options = apply_filters( "simple_fields_get_options", $options);
		return $options;

	}
	
	/**
	 * Save options
	 * @param array $new_options. will be merged with old options, so you only need to add your modified stuff to the array, and then all old stuff will be untouched.
	 */
	function save_options($new_options) {

		$old_options = $this->get_options();
		$new_options = wp_parse_args($new_options, $old_options);
		$new_options = apply_filters( "simple_fields_save_options", $new_options);
		update_option("simple_fields_options", $new_options);
		$this->clear_caches();

	}
	
	/**
	 * If debug option is enabled then output debug-box by hooking onto the_content
	 */
	function maybe_add_debug_info() {

		global $sf;
		$options = $sf->get_options();
		if (isset($options["debug_type"]) && $options["debug_type"] !== 0) {
		
			// 1 = debug for admins only, 2 = debug for all
			if ( ($options["debug_type"] === 1 && current_user_can("edit_themes")) || $options["debug_type"] === 2) {
			
				add_filter("the_content", array($this, "simple_fields_content_debug_output"));
			}	
	
		}

	}
	
	/** 
	 * Outputs the names of the post connectors attached to the post you view + outputs the values
	 * @param string $the_content
	 * @param bool $allow_always Set to true to bypass checks that we are inside the correct the_content-filter
	 */
	function simple_fields_content_debug_output($the_content, $args = "") {

		$defaults = array(
			"always_show" => FALSE,
			"show_expanded" => FALSE
		);

		$args = wp_parse_args( $args, $defaults);

		// we only want to appen the debug code when being used from get_the_content or the_content
		// but for example get_the_excerpt is also using filter the_content which leads to problems
		// so check that we are somewhere inside the right functions
		if ($args["always_show"] === FALSE) {

			$is_inside_righ_function = FALSE;
			$arr_trace = debug_backtrace();
			$arr_trace_count = count($arr_trace);

			for ($i = 0; $i < $arr_trace_count; $i++) {
				if ( isset($arr_trace[$i]["function"]) && in_array($arr_trace[$i]["function"], array("the_content", "get_the_content"))) {
					$is_inside_righ_function = TRUE;
					break;
				}
			}

			if ( ! $is_inside_righ_function ) {

				// Don't do the debug, since we're not in the_content
				return $the_content;

			}
		}
		
		$output = "";
		$output_all = "";
		$field_count = 0;
		
		if ( ! isset( $GLOBALS['post'] ) ) {
			return $the_content;
		}
		
		$post_id = get_the_ID();
		$post_connector_with_values = simple_fields_get_all_fields_and_values_for_post($post_id, "include_deleted=0");
		if ($post_connector_with_values) {

			foreach ($post_connector_with_values["field_groups"] as $one_field_group) {

				if ($one_field_group["deleted"]) continue;
				
				$output_all .= "<div style='font-weight:bold;margin:1em 0 0 0;'>";
				$str_is_repeatable = $one_field_group["repeatable"] ? __(" (Repeatable)", "simple-fields") : "";
				$output_all .= sprintf(
					__('Fieldgroup %1$s %2$s', "simple-fields"),
					$one_field_group["name"],
					$str_is_repeatable
				);
				$output_all .= "</div>";
				
				$str_all_group_fields = "";
				foreach ($one_field_group["fields"] as $one_field) {

					if ($one_field["deleted"]) continue;

					$field_count++;
					$content = "";
					$content .= "<ul style='background:#eee;padding:.5em;margin:0;display:block;'>";
					$content .= "<li>" . __("Field", "simple-fields") . " <b>" . $one_field["name"] . "</b>";
					$content .= ", " . __("type", "simple-fields") . " <b>" . $one_field["type"] . "</b>";

					if (isset($one_field["slug"])) {
						
						$content .=  ", slug <b>" . $one_field["slug"] . "</b>";
						$str_all_group_fields .= $one_field["slug"] . ",";
						
						if ($one_field_group["repeatable"]) {
							$content .= "<br>Use <code><b>simple_fields_values('".$one_field["slug"]."')</b></code>.";
							/*ob_start();
							sf_d( simple_fields_values($one_field["slug"]) );
							$content .= ob_get_clean();*/
						} else {		
							$content .= "<br>Use <code><b>simple_fields_value('".$one_field["slug"]."')</b></code>.";
							/*ob_start();
							sf_d( simple_fields_value($one_field["slug"]) );
							$content .= ob_get_clean();*/
						}
						
					} else {
						$content .= "<br>" . __("No slug for this field found (probably old field that has not been edited and saved).", "simple-fields");
					}
					$content .= "</ul>";
					$output_all .= $content;
				}

				// Show example how to get all fields in one shot
				// But only show if field has more than one field, otherwise it's kinda not useful
				if ( sizeof($one_field_group["fields"]) > 1 ) {
					$str_all_group_fields = preg_replace('!,$!', '', $str_all_group_fields);
					$output_all .= "<ul style='background:#eee;padding:.5em;margin:0;display:block;'>";
					if ($one_field_group["repeatable"]) {
						$content = "<li>Get all fields at once: use <code><b>simple_fields_values('".$str_all_group_fields."')</b></code>.";
						/*ob_start();
						sf_d( simple_fields_values($str_all_group_fields) );
						$content .= ob_get_clean();*/
					} else {
						$content = "<li>Get all fields at once: use <code><b>simple_fields_value('".$str_all_group_fields."')</b></code>.";
						/*ob_start();
						sf_d( simple_fields_value($str_all_group_fields) );
						$content .= ob_get_clean();*/
					}
					$output_all .= $content;
					$output_all .= "</ul>";
				}
			
			} // for each field group
		}
		
		if ($output_all) {
			$str_show_fields = __("Show fields.", "simple-fields");
			$str_hide_fields = __("Hide fields.", "simple-fields");
			?>
			<script>
			window.simple_fields_post_debug_show_hide = window.simple_fields_post_debug_show_hide || function(t) {

				var div_debug_contents = t.parentNode.parentNode.getElementsByClassName("simple-fields-post-debug-content"),
					new_style,
					link_text;

				if (div_debug_contents.length) {
					
					if (div_debug_contents[0].style.display === "block") {
						new_style = "none";
						link_text = "<?php echo $str_show_fields ?>";
					} else {
						new_style = "block";
						link_text = "<?php echo $str_hide_fields ?>";
					}

					div_debug_contents[0].style.display = new_style;
					t.innerHTML = link_text;

				}

				return false;
				
			}
			</script>
			<?php

			$str_show_hide = "";
			$display = "block";
			if ($args["show_expanded"] === FALSE) {
				$str_show_hide = '<a href="#" onclick="return simple_fields_post_debug_show_hide(this);">'.$str_show_fields.'</a></p>';
				$display = "none";
			}

			$output_all = sprintf('
				<div class="simple-fields-post-debug-wrap" style="display:block;margin:0;padding:0;">
					<p style="margin:0;padding:0;display:block;">This post has %1$s Simple Fields-fields attached. 
					%2$s
					<div class="simple-fields-post-debug-content" style="display:%3$s;">%4$s</div>
				</div>
				', $field_count, $str_show_hide, $display, $output_all);
		}
		// if a field has the slug caption the output will be [caption] and then it will crash with some shortcodes, so we try to fix that here
		$output_all = str_replace("[", "&#91;", $output_all);
		$output_all = str_replace("]", "&#93;", $output_all);

		return $the_content . $output_all;
	
	}

	/**
	 * Retrieve and return extended return values for a field type
	 * Only used for internal/built in file types.
	 *
	 * @param mixed $field array or string or int or whatever with field info
	 * @param mixed $field_value the saved value
	 */
	function get_extended_return_values_for_field($field, $field_value) {

		$return_field_value = array();

		if ("file" === $field["type"]) {

			// field is of type file
			// lets get more info about that file then, so we have most useful stuff in an array – hooray!
			
			if (isset($field_value) && is_numeric($field_value)) {

				$file_id                             = (int) $field_value;
				$return_field_value["id"]            = $file_id;
				$return_field_value["is_image"]      = wp_attachment_is_image( $file_id );
				$return_field_value["url"]           = wp_get_attachment_url( $file_id );
				$return_field_value["mime"]          = get_post_mime_type( $file_id );

				// generate html for all registered image sizes
				$arr_sizes = array_merge(array("full"), get_intermediate_image_sizes());
				$return_field_value["link"]      = array();
				$return_field_value["image"]     = array();
				$return_field_value["image_src"] = array();
				foreach ($arr_sizes as $size_key) {
					$return_field_value["link"][$size_key]      = wp_get_attachment_link( $file_id, $size_key );
					$return_field_value["image"][$size_key]     = wp_get_attachment_image( $file_id, $size_key );
					$return_field_value["image_src"][$size_key] = wp_get_attachment_image_src( $file_id, $size_key );
				}
			
				$return_field_value["metadata"] = wp_get_attachment_metadata( $file_id );
				$return_field_value["post"] = get_post( $file_id );
				
			}

		} else if ("radiobuttons" === $field["type"]) {
			
			// if radiobutton: get all values and mark which one is the selected

			$type_radiobuttons_options = $field["type_radiobuttons_options"];

			$return_field_value["selected_value"] 		= FALSE;
			$return_field_value["selected_radiobutton"]	= array();
			$return_field_value["radiobuttons"] 		= array();

			foreach ($type_radiobuttons_options as $button_key => $button_value) {
			
				if ($button_key == "checked_by_default_num") continue;
				
				if (isset($button_value["deleted"]) && $button_value["deleted"]) continue;
				
				$button_value_value = isset($button_value["value"]) ? $button_value["value"] : "";
				
				$return_field_value["radiobuttons"][] = array(
					"value"       => $button_value_value,
					"key"         => $button_key,
					"is_selected" => ($field_value === $button_key)
				);
				if ($field_value === $button_key) {
					$return_field_value["selected_radiobutton"] = array(
						"value"       => $button_value_value,
						"key"         => $button_key,
						"is_selected" => TRUE
					);
					$return_field_value["selected_value"] = $button_value["value"];
				}
			}
						
		} else if ("dropdown" === $field["type"]) {
			
			$type_dropdown_options = $field["type_dropdown_options"];

			// dropdown can be multiple since 1.1.4
			if (isset($type_dropdown_options["enable_multiple"]) && $type_dropdown_options["enable_multiple"]) {
				
				// multiple = return array with same info as single values
				$arr_dropdown_values = $field_value;

				$return_field_value["selected_values"]	= array();
				$return_field_value["selected_options"]	= array();
				$return_field_value["options"] = array();

				foreach ($type_dropdown_options as $dropdown_key => $dropdown_value) {

					// Only values like dropdown_num_2 are allowed
					if ( strpos($dropdown_key, "dropdown_num_") === FALSE) { continue; }

					// Skip deleted
					if (isset($dropdown_value["deleted"]) && $dropdown_value["deleted"]) continue;					
					
					$dropdown_value_value = isset($dropdown_value["value"]) ? $dropdown_value["value"] : "";
					
					$return_field_value["options"][] = array(
						"value"       => $dropdown_value_value,
						"key"         => $dropdown_key,
						"is_selected" => in_array($dropdown_key, $arr_dropdown_values)
					);

					if (in_array($dropdown_key, $arr_dropdown_values)) {
						
						$return_field_value["selected_options"][] = array(
							"value"       => $dropdown_value_value,
							"key"         => $dropdown_key,
							"is_selected" => TRUE
						);
						
						$return_field_value["selected_values"][] = $dropdown_value["value"];
					}
				}

			} else {

				// Single value
				$return_field_value["selected_value"]	= FALSE;
				$return_field_value["selected_option"]	= array();
				$return_field_value["options"] 			= array();

				foreach ($type_dropdown_options as $dropdown_key => $dropdown_value) {

					// Only values like dropdown_num_2 are allowed
					if ( strpos($dropdown_key, "dropdown_num_") === FALSE) { continue; }

					// Skip deleted
					if ( isset( $dropdown_value["deleted"] ) && $dropdown_value["deleted"] ) continue;
					
					$return_field_value["options"][] = array(
						"value"       => $dropdown_value["value"],
						"key"         => $dropdown_key,
						"is_selected" => ($field_value === $dropdown_key)
					);

					if ($field_value === $dropdown_key) {
					
						$return_field_value["selected_option"] = array(
							"value"       => $dropdown_value["value"],
							"key"         => $dropdown_key,
							"is_selected" => TRUE
						);
						
						$return_field_value["selected_value"] = $dropdown_value["value"];
					
					}
				}

			} // if single
			
		} else if ("post" === $field["type"]) {

			// For post field
			// Get shortcut for id, title, and permalink
			// and then the whole post
			if ( isset( $field_value ) && is_numeric( $field_value ) && (int) $field_value !== 0) {
				$post_id = (int) $field_value;
				$return_field_value["id"] 			= $post_id;
				$return_field_value["title"] 		= get_the_title( $post_id );
				$return_field_value["permalink"] 	= get_permalink( $post_id );
				$return_field_value["post"] 		= get_post( $post_id );
			}
				
		} else if ("user" === $field["type"]) {

			if (isset($field_value) && is_numeric($field_value)) {
				
				$user_id = (int) $field_value;
				$return_field_value["id"]	= $user_id;
				
				// user is a WP_User object,
				// see this url for more info on what data you can get:
				// http://codex.wordpress.org/Function_Reference/get_userdata
				$user                                = get_user_by( "id", $user_id );
				$return_field_value["first_name"]    = $user->first_name;
				$return_field_value["last_name"]     = $user->last_name;
				$return_field_value["user_login"]    = $user->user_login;
				$return_field_value["user_email"]    = $user->user_email;
				$return_field_value["user_nicename"] = $user->user_nicename;
				$return_field_value["display_name"]  = $user->display_name;
				$return_field_value["user"]          = $user;
				
			}

		} else if ("taxonomy" === $field["type"]) {

			$taxonomy = get_taxonomy($field_value);
			$return_field_value["name"]          	= "";
			$return_field_value["singular_name"] 	= "";
			$return_field_value["plural_name"] 		= "";
			$return_field_value["taxonomy"]      	= "";
			if ($taxonomy) {
				$return_field_value["name"]          = $taxonomy->name;
				$return_field_value["singular_name"] = $taxonomy->labels->singular_name;
				$return_field_value["plural_name"]   = $taxonomy->labels->name;
				$return_field_value["taxonomy"]      = $taxonomy;
			}

		} else if ("taxonomyterm" === $field["type"]) {
			
			$type_taxonomyterm_options = $field["type_taxonomyterm_options"];

			// multiple tags can be selected
			$arr_terms = array();
			if (isset($field_value) && is_array($field_value)) {
				foreach ($field_value as $one_term_id) {
					
					$term = get_term_by("id", $one_term_id, $type_taxonomyterm_options["enabled_taxonomy"]);
					$arr_terms[] = array(
						"name" => $term->name,
						"slug" => $term->slug,
						"id"   => $term->term_id,
						"term" => $term
					);
					
				}
			}
			
			$return_field_value["terms"] = $arr_terms;
		
		} else if ("date" === $field["type"]) {

			// format = default in jquery = mm/dd/yy (year 4 digits)
			// sf_d($field_value); // 14/10/2012
			$return_field_value["saved_value"] = $field_value;
			if (isset($field_value)) {
				$field_value = trim($field_value);
				if (preg_match('!^\d{2}\/\d{2}\/\d{4}$!', $field_value)) {
					$date = strtotime( str_replace('/', "-", $field_value) );
					$return_field_value["timestamp"] = $date;
					$return_field_value["date_format"] = date(get_option('date_format'), $date);
					$return_field_value["date_format_i18n"] = date_i18n( get_option('date_format'), $date);
					// $timezone_format = _x('Y-m-d G:i:s', 'timezone date format');
					//echo get_option("gmt_offset"); // 14 if UTC+14
				}
			}
			
		}
		
		$return_field_value = apply_filters( "simple_fields_get_extended_return_values_for_field", $return_field_value, $field, $field_value);

		return $return_field_value;

	}

	/**
	 * Gets a field group using it's slug. Deleted field groups are not included
	 *
	 * @since 1.0.5
	 * @param string slug of field group (or id, actually)
	 * @return mixed array with field group info if field groups exists, false if does not exist
	 */
	function get_field_group_by_slug($field_group_slug, $include_deleted = false) {

		$cache_key = 'simple_fields_'.$this->ns_key.'_get_field_group_by_slug_deleted_' . (int) $include_deleted . "_" . $field_group_slug;
		$return_val = wp_cache_get( $cache_key, 'simple_fields' );
		
		if (FALSE === $return_val) {

			$field_groups = $this->get_field_groups();

			if ( ! is_numeric($field_group_slug) ) {
	
				// not number so look for field group with this variable as slug
				foreach ($field_groups as $one_field_group) {
					
					if ( $one_field_group["deleted"] && ! $include_deleted ) continue;
					
					if ($one_field_group["slug"] == $field_group_slug) {

						wp_cache_set( $cache_key, $one_field_group, 'simple_fields' );
						$one_field_group = apply_filters( "simple_fields_get_field_group_by_slug", $one_field_group, $field_group_slug);
						return $one_field_group;
					}
				}
				
				wp_cache_set( $cache_key, FALSE, 'simple_fields' );
				
				$return_val = FALSE;
				$return_val = apply_filters( "simple_fields_get_field_group_by_slug", $return_val, $field_group_slug);
				return $return_val;
	
			} else {
	
				// look for group using id
				if ( isset($field_groups[$field_group_slug]) && is_array($field_groups[$field_group_slug]) ) {

					if ( $field_groups[$field_group_slug]["deleted"] && ! $include_deleted) {
	
						// deleted and we don't want deleted ones
						wp_cache_set( $cache_key, FALSE, 'simple_fields' );					
						$return_val = apply_filters( "simple_fields_get_field_group_by_slug", $return_val, $field_group_slug);
						return $return_val;

					}

					wp_cache_set( $cache_key, $field_groups[$field_group_slug], 'simple_fields' );
					$return_val = $field_groups[$field_group_slug];
					$return_val = apply_filters( "simple_fields_get_field_group_by_slug", $return_val, $field_group_slug);
					return $return_val;

				} else {

					wp_cache_set( $cache_key, FALSE, 'simple_fields' );					
					$return_val = apply_filters( "simple_fields_get_field_group_by_slug", $return_val, $field_group_slug);
					return $return_val;

				}
				
			}
				
		} // if not in cache

		$return_val = apply_filters( "simple_fields_get_field_group_by_slug", $return_val, $field_group_slug);
		return $return_val;

	}

	/**
	 * Get meta key name for the custom field used for determine how many fields that has been added to a post
	 * hm... this is the same as get_meta_key but with the field id "added" instead of a real id? i think so...
	 */
	function get_meta_key_num_added( $field_group_id = null, $field_group_slug = null ) {

		if ( ! isset( $field_group_id ) || ! is_numeric( $field_group_id ) ) return false;

		// Generate string to be used as template in sprintf
		// Arguments:
		// 1 = field group id
		// 2 = field group slug

		// Legacy version with ids
		// _simple_fields_fieldGroupID_1_fieldID_added_numInSet_0
		/*$custom_field_key_template = '_simple_fields_fieldGroupID_%1$s_fieldID';

		// Possibly new version with slugs
		#$custom_field_key_template = '_simple_fields_fieldGroupSlug_%2$s_fieldID';

		$custom_field_key_template = apply_filters("simple_fields_get_meta_key_num_added_template", $custom_field_key_template);

		$custom_field_key = sprintf(
			$custom_field_key_template, 
			$field_group_id, // 1
			$field_group_slug // 2
		);

		$custom_field_key = $custom_field_key . "_added_numInSet_";
		$custom_field_key = apply_filters("simple_fields_get_meta_key_num_added", $custom_field_key);
		*/

		$custom_field_key = $this->get_meta_key( $field_group_id, "added", 0, $field_group_slug, "added" );

		// Remove last with num in set
		$custom_field_key = rtrim($custom_field_key, "0");
		#sf_d($custom_field_key);

		return $custom_field_key;

	}

	/**
	 * Get meta key name for a field id + field group id combination
	 *
	 * @param int $field_group_id
	 * @param int field_id
	 * @param int num_in_set
	 * @return string
	 */
	function get_meta_key($field_group_id = NULL, $field_id = NULL, $num_in_set = 0, $field_group_slug = "", $field_slug = "") {

		if ( ! isset($field_group_id) || ! isset($field_group_id) || ! is_numeric($field_group_id) || ! isset($field_id) || ! isset($num_in_set) ) return FALSE;

		// Generate string to be used as template in sprintf
		// Arguments:
		// 1 = field group id
		// 2 = field id
		// 3 = num_in_set
		// 4 = field group slug
		// 5 = field slug

		// Legacy version based on ids:
		$custom_field_key_template = '_simple_fields_fieldGroupID_%1$d_fieldID_%2$s_numInSet_%3$s';

		// Possibly new version with slugs instead
		#$custom_field_key_template = '_simple_fields_fieldGroupSlug_%4$s_fieldSlug_%5$s_numInSet_%3$d';

		$custom_field_key_template = apply_filters("simple_fields_get_meta_key_template", $custom_field_key_template);

		$custom_field_key = sprintf(
			$custom_field_key_template, 
			$field_group_id, // 1
			$field_id, // 2
			$num_in_set, // 3
			$field_group_slug, // 4
			$field_slug // 5
		);
		$custom_field_key = apply_filters("simple_fields_get_meta_key", $custom_field_key);
		
		return $custom_field_key;

	}

	/**
	 * Returns a field from a fieldgroup using their slugs
	 *
	 * @since 1.0.5
	 * @param string $field_slug
	 * @param string $fieldgroup_slug
	 * @return mixed Array with field info if field is found, false if not found
	 */
	function get_field_by_slug($field_slug = "", $fieldgroup_slug = "") {

		$field_group = $this->get_field_group_by_slug($fieldgroup_slug);
		if (!$field_group) return FALSE;
		
		foreach ($field_group["fields"] as $one_field) {
			if ($field_slug === $one_field["slug"]) {
				$one_field = apply_filters( "simple_fields_get_field_by_slug", $one_field, $field_slug, $fieldgroup_slug);
				return $one_field;
			}
		}
		
		// No field with that slug found
		$return_val = FALSE;
		$return_val = apply_filters( "simple_fields_get_field_by_slug", $return_val, $field_slug, $fieldgroup_slug);
		return $return_val;
	}

	/**
	 * Clear the key used for wp_cache_get and wp_cache_set
	 * Run this when options etc have been changed so fresh values are fetched upon next get
	 */
	function clear_caches() {

		do_action("simple_fields_clear_caches");

		$prev_key = $this->ns_key;
		$this->ns_key = wp_cache_incr( 'simple_fields_namespace_key', 1, 'simple_fields' );

		// this can consume lots of memory if we for example use set_value a lot because
		// it gets all field values and such and then increase the namespace key, bloating up the memory
		// kinda correct, but kinda too much memory
		// this is perhaps naughty, but will work: get all keys belonging to simple fields and just remove them
		global $wp_object_cache;
		if (isset($wp_object_cache->cache["simple_fields"])) {
			// cache exists for simple fields
			// remove all keys
			$wp_object_cache->cache["simple_fields"] = array();
		}

		if ($this->ns_key === FALSE) {
			// I really don't know why, but wp_cache_incr returns false...always or sometimes?
			// Manually update namespace key by one
			$this->ns_key = $prev_key + 1;
			wp_cache_set( 'simple_fields_namespace_key', $this->ns_key, 'simple_fields' );
		}
		// echo "clear_key";var_dump($this->ns_key);

	} // clear caches


	/**
	 * @param string $str_field_key Key of field type to get
	 * @return mixed. Returns false if field is not found. Returns array with field info if field is found.
	 */
	function get_core_field_type_by_key($str_field_key = "") {
		
		if ( empty( $str_field_key ) ) return FALSE;

		$arr_field_types = $this->get_core_field_types();
		if ( isset( $arr_field_types[ $str_field_key ] ) ) {
			return $arr_field_types[ $str_field_key ];
		} else {
			return FALSE;
		}

	} // end func get_core_field_type_by_key

	/**
	 * Check if field type with key $str_field_type is one of the core ones
	 *
	 * @param string $field_key
	 * @return Bool
	 */
	function field_type_is_core($str_field_key = "")  {

		if ( empty( $str_field_key ) ) return FALSE;

		$arr_field_types = $this->get_core_field_types();

		if ( isset( $arr_field_types[ $str_field_key ] ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get a list of all core field types
	 * Core = all field types that are not extensions
	 * Core field types use old and less smart way of storing options
	 * 
	 * @return array with all field types
	 */
	function get_core_field_types() {

		$arr_core_field_types = array(
			"text" => array(
				"key" => 'text',
				"name" => __('Text', 'simple-fields'),
			),
			"textarea" => array(
				"key" => 'textarea',
				"name" => __('Textarea', 'simple-fields'),
			),
			"checkbox" => array(
				"key" => 'checkbox',
				"name" => __('Checkbox', 'simple-fields'),
			),
			"radiobuttons" => array(
				"key" => 'radiobuttons',
				"name" => __('Radio buttons', 'simple-fields'),
			),
			"dropdown" => array(
				"key" => 'dropdown',
				"name" => __('Dropdown', 'simple-fields'),
			),
			"file" => array(
				"key" => 'file',
				"name" => __('File', 'simple-fields'),
			),
			"post" => array(
				"key" => 'post',
				"name" => __('Post', 'simple-fields'),
			),
			"taxonomy" => array(
				"key" => 'taxonomy',
				"name" => __('Taxonomy', 'simple-fields'),
			),
			"taxonomyterm" => array(
				"key" => 'taxonomyterm',
				"name" => __('Taxonomy Term', 'simple-fields'),
			),
			"color" => array(
				"key" => 'color',
				"name" => __('Color', 'simple-fields'),
			),
			"date" => array(
				"key" => 'date',
				"name" => __('Date', 'simple-fields'),
			),
			"user" => array(
				"key" => 'user',
				"name" => __('User', 'simple-fields'),
			),
		);

		return $arr_core_field_types;

	} // end func get_core_field_types


	function options_page_save() {

		// only perform action on fields pages
		if ( isset( $_GET["page"] ) && ("simple-fields-options" == $_GET["page"]) ) {
		
			if ( ! isset($_GET["action"]) || empty( $_GET["action"] ) ) return;
			$action = $_GET["action"];

			do_action("simple_fields_options_page_save", $action);

			global $sf;
		
			$field_groups = $this->get_field_groups();
			$post_connectors = $this->get_post_connectors();
			$menu_page_url  = menu_page_url("simple-fields-options", false);

			/**
			 * save a post connector
			 */
			if ("edit-post-connector-save" == $action) {
				
				if ( ! wp_verify_nonce( $_POST["simple-fields"], "save-post-connector" ) ) wp_die( __("Cheatin&#8217; uh?") );		

				$connector_id = (int) $_POST["post_connector_id"];
				$post_connectors[$connector_id]["name"] = (string) stripslashes($_POST["post_connector_name"]);
				$post_connectors[$connector_id]["slug"] = (string) ($_POST["post_connector_slug"]);
				$post_connectors[$connector_id]["field_groups"] = (array) @$_POST["added_fields"];
				$post_connectors[$connector_id]["post_types"] = (array) @$_POST["post_types"];
				$post_connectors[$connector_id]["hide_editor"] = (bool) @$_POST["hide_editor"];
				$post_connectors[$connector_id]["added_with_code"] = false;

				// When field group is created it's set to deleted in case we don't save, so undo that
				$post_connectors[$connector_id]["deleted"] = false;

				// for some reason I got an empty connector (array key was empty) so check for these and remove
				$post_connectors_tmp = array();
				foreach ($post_connectors as $key => $one_connector) {
					if (!empty($one_connector)) {
						$post_connectors_tmp[$key] = $one_connector;
					}
				}
				$post_connectors = $post_connectors_tmp;

				update_option("simple_fields_post_connectors", $post_connectors);
				$this->clear_caches();

				$simple_fields_did_save_connector = true;

				wp_redirect( add_query_arg( "message", "post-connector-saved", $menu_page_url ) );
				exit;
			
			}


			/**
			 * save a field group
			 * including fields
			 */
			if ("edit-field-group-save" == $action) {

				if ( ! wp_verify_nonce( $_POST["simple-fields"], "save-field-group" ) ) wp_die( __("Cheatin&#8217; uh?") );

				$field_group_id                               = (int) $_POST["field_group_id"];
				$field_groups[$field_group_id]["name"]        = stripslashes($_POST["field_group_name"]);
				$field_groups[$field_group_id]["description"] = stripslashes($_POST["field_group_description"]);
				$field_groups[$field_group_id]["slug"]        = stripslashes($_POST["field_group_slug"]);
				$field_groups[$field_group_id]["repeatable"]  = (bool) (isset($_POST["field_group_repeatable"]));
				$field_groups[$field_group_id]["gui_view"]    = isset( $_POST["field_group_gui_view"] )  ? "table" : "list";
				$field_groups[$field_group_id]["fields"]      = isset($_POST["field"]) ? (array) stripslashes_deep($_POST["field"]) : array();

				// When field group is created it's set to deleted in case we don't save, so undo that
				$field_groups[$field_group_id]["deleted"] = false;

				// Since 0.6 we really want all things to have slugs, so add one if it's not set
				if (empty($field_groups[$field_group_id]["slug"])) {
					$field_groups[$field_group_id]["slug"] = "field_group_" . $field_group_id;
				}
				
				/*
				if just one empty array like this, unset first elm
				happens if no fields have been added (now why would you do such an evil thing?!)
				*/
				if (sizeof($field_groups[$field_group_id]["fields"]) == 1 && empty($field_groups[$field_group_id]["fields"][0])) {
					unset($field_groups[$field_group_id]["fields"][0]);
				}
				
				update_option("simple_fields_groups", $field_groups);
				$this->clear_caches();

				// we can have changed the options of a field group, so update connectors using this field group
				$post_connectors = (array) $this->get_post_connectors();
				foreach ($post_connectors as $connector_id => $connector_options) {
					if (isset($connector_options["field_groups"][$field_group_id])) {
						// field group existed, update name
						$post_connectors[$connector_id]["field_groups"][$field_group_id]["name"] = stripslashes($_POST["field_group_name"]);
					}
				}
				update_option("simple_fields_post_connectors", $post_connectors);
				$this->clear_caches();
				
				$simple_fields_did_save = true;
			
				wp_redirect( add_query_arg( "message", "field-group-saved", $menu_page_url ) );
				exit;

			} // edit field group


			/**
			 * Delete a field group
			 */
			if ("delete-field-group" == $action) {

				if ( ! wp_verify_nonce( $_REQUEST["_wpnonce"], "delete-field-group" ) ) {
					wp_die( __("Cheatin&#8217; uh?") );
				}

				$field_group_id = (int) $_GET["group-id"];
				$field_groups[$field_group_id]["deleted"] = true;
				update_option("simple_fields_groups", $field_groups);
				$this->clear_caches();

				wp_redirect( add_query_arg( "message", "field-group-deleted", $menu_page_url ) );
				exit;

			} // delete field group

			/**
			 * Delete a post connector
			 */
			if ("delete-post-connector" == $action) {

				if ( ! wp_verify_nonce( $_REQUEST["_wpnonce"], "delete-post-connector" ) ) {
					wp_die( __("Cheatin&#8217; uh?") );
				}

				$post_connector_id = (int) $_GET["connector-id"];
				$post_connectors[$post_connector_id]["deleted"] = 1;
				update_option("simple_fields_post_connectors", $post_connectors);
				$this->clear_caches();

				wp_redirect( add_query_arg( "message", "post-connector-deleted", $menu_page_url ) );
				exit;

			} // delete post connector

			/**
			 * save post type defaults
			 */
			if ("edit-post-type-defaults-save" == $action) {
	
				if ( ! wp_verify_nonce( $_POST["simple-fields"], "save-default-post-connector" ) ) wp_die( __("Cheatin&#8217; uh?") );

				if ( isset($_POST["simple_fields_save-post_type"]) && isset($_POST["simple_fields_save-post_type_connector"]) ) {

					$post_type = $_POST["simple_fields_save-post_type"];
					$post_type_connector = $_POST["simple_fields_save-post_type_connector"];
								
					simple_fields_register_post_type_default($post_type_connector, $post_type);
				}					

				wp_redirect( add_query_arg( "message", "post-type-defaults-saved", $menu_page_url ) );
				exit;				
	
			}

		} // perform action on simple fields pages

	} // save options

	/**
	 * Get the html input types that we show in text field type
	 * @since 1.2
	 * @return array
	 */
	function get_html_text_types() {

		$arr_text_input_types = array(
			"text" => array(
				"description" => "text",
				"version" => "html"
			),
			"password" => array(
				"description" => "password",
				"version" => "html"
			),
			"tel" => array(
				"description" => "tel",
				"version" => "html5"
			),
			"url" => array(
				"description" => "url",
				"version" => "html5"
			),
			"email" => array(
				"description" => "email",
				"version" => "html5"
			),
			"datetime" => array(
				"description" => "datetime",
				"version" => "html5"
			),
			"date" => array(
				"description" => "date",
				"version" => "html5"
			),
			"month" => array(
				"description" => "month",
				"version" => "html5"
			),
			"week" => array(
				"description" => "week",
				"version" => "html5"
			),
			"time" => array(
				"description" => "time",
				"version" => "html5"
			),
			"datetime-local	" => array(
				"description" => "datetime",
				"version" => "html5"
			),
			"number" => array(
				"description" => "number",
				"version" => "html5"
			),
			"range" => array(
				"description" => "range",
				"version" => "html5"
			),
			"color" => array(
				"description" => "color",
				"version" => "html5"
			),
		);
		$arr_text_input_types = apply_filters("simple_fields_get_html_text_types", $arr_text_input_types);
		return $arr_text_input_types;
	} // get html text types


	/**
	 * Get tabs for options output
	 */
	function get_options_nav_tabs($subpage) {
		?>		
		<h3 class="nav-tab-wrapper">
			<a href="<?php echo add_query_arg(array("sf-options-subpage" => "manage"), SIMPLE_FIELDS_FILE) ?>" class="nav-tab <?php echo "manage" === $subpage ? "nav-tab-active" : "" ?>"><?php _e('Manage', 'simple-fields') ?></a>
			<?php
			do_action("simple_fields_after_last_options_nav_tab", $subpage);
			?>
		</h3>
		<?php
	}

	/**
	 * Promote Earth People
	 */
	function promote_ep_on_nav_tabs() {
		?>
		<style>
			.simple-fields-promote {
				float: right;
				background: #999;
				width: 375px;
				margin-top: -3.5em;
				padding: .5em;
				font-size: 12px;
				display: inline-block;
				vertical-align: center;
			}
			
			.simple-fields-promote p {
				color: #eee;
				font-size: inherit;
				margin: 0 0 .25em 0;
			}
			.simple-fields-promote a {
				color: inherit;
			}
			.ep_logo {
				float: left;
			}
		</style>
		<div class="simple-fields-promote">

			<!-- <img src="http://d3m1jlakmz8guo.cloudfront.net/application/views/assets/img/earth_people.png"> -->

			<p>This plugin is made by swedish web agency <a href="http://earthpeople.se/?utm_source=wordpress&utm_medium=plugin&utm_campaign=simplefields">Earth People</a>.</p>
			<p>We specialize in web development, user experience and design.</p>
			<p><a href="mailto:peder@earthpeople.se">Contact us</a> if you need a professional WordPress partner.</p>
		
		</div>
		<?php
	}

	/**
	 * Retrive a field by a string in the format <fieldgroup_slug>/<field_slug>
	 * used when fieldgroups and fields need to be passed as string
	 *
	 * @param string $string
	 * @return array field info or false if field not found
	 */
	function get_field_by_fieldgroup_and_slug_string($string) {
		
		if ( empty($string) ) {
			return false;
		}

		$arr = explode("/", $string);
		if ( 2 !== sizeof($arr) ) {
			return false;
		}
		
		// sf_d($arr, "arr"); // 0 timeline 1 timeline_date
		$field = $this->get_field_by_slug( $arr[1], $arr[0] );

		return $field;

	} // end get_field_by_fieldgroup_and_slug_string

	/**
	 * Check if wpml is active
	 *
	 * @return bool
	 */
	public function is_wpml_active() {
		
		global $sitepress;		
		return ( isset( $sitepress ) && $sitepress instanceof SitePress );

	}

	/**
	 * Look for post connector defined in template
	 * Format in template is:
	 *
	 * Simple Fields Connector: useMeAsThePostConnector
	 *
	 * Hooked into action simple_fields_get_selected_connector_for_post
	 *
	 */
	function set_post_connector_from_template($connector_to_use, $post) {
		
		// Look for connector defined in template
		$template = !empty($post->page_template) ? $post->page_template : false;
		$post_connector_from_template = $this->get_post_connector_from_template( $template );
		if ($post_connector_from_template) $connector_to_use = $post_connector_from_template;

		$connector_to_use = apply_filters("set_post_connector_from_template", $connector_to_use, $post);

		return $connector_to_use;

	}

	/**
	 * Returns true if post has a template connector defined
	 */
	function post_has_template_connector($post) {

		$template = !empty($post->page_template) ? $post->page_template : false;
		$post_connector_from_template = $this->get_post_connector_from_template( $template );
		return (bool) $post_connector_from_template;

	}


	/**
	 * @param string $template template filename
	 * @return string Slug of post connector. Empty if no one set
	 */
	function get_post_connector_from_template($template) {

		$template_file = locate_template($template);
		if ( is_file( $template_file ) ) {
			$template_data = get_file_data( $template_file, array("Name" => "Template Name", "PostConnector" => "Simple Fields Connector") );
			$post_connector = trim($template_data["PostConnector"]);
		} else {
			$post_connector = "";
		}
		
		$post_connector = apply_filters("get_post_connector_from_template", $post_connector, $template);
		
		return $post_connector;

	}

} // end class


// Boot it up!
global $sf;
$sf = new simple_fields();
$sf->init();
