<?php

/**
 * Simple FIelds options page for import and export
 */
class simple_fields_options_page_debug {

	var 
		$slug = "debug",
		$sf = null;
	
	function __construct() {		

		add_action("simple_fields_init", array($this, "init"));

	}

	function init($sf) {

		$this->sf = $sf;
		
		
		// Add tab and output content when on that tab
		add_action("simple_fields_after_last_options_nav_tab", array($this, "print_nav_tab"));
		add_action("simple_fields_subpage_$this->slug", array($this, "output_page"));

		// Save options on admin init
		add_action("simple_fields_options_page_save", array($this, "save_debug_options"));

	}

	/**
	 * Get name of this options page tab
	 *
	 * @return string
	 */
	function get_name() {
		return _e('Debug', 'simple-fields');
	}

	function output_scripts_and_styles() {
		?>
		<script>
		</script>
		<style>
		</style>
		<?php
	}

	/**
	 * Print the tab for this tab
	 * 
	 * @param string $subpage Name of current tab
	 */
	function print_nav_tab($subpage) {
		?>
		<a href="<?php echo add_query_arg(array("sf-options-subpage" => $this->slug), SIMPLE_FIELDS_FILE) ?>" class="nav-tab <?php echo $this->slug === $subpage ? "nav-tab-active" : "" ?>"><?php esc_html( $this->get_name() ) ?></a>
		<?php
	}

	/**
	 * Save options for debug
	 */
	function save_debug_options($action) {
	
		if ("edit-options-save" == $action) {
			
			if ( ! wp_verify_nonce( $_POST["_wpnonce"], "save-debug-options" ) ) wp_die( __("Cheatin&#8217; uh?") );
			
			$this->sf->save_options(array(
				"debug_type" => (int) $_POST["debug_type"]
			));
			
			wp_redirect( add_query_arg( array(
				"message" => "debug-options-saved",
				"sf-options-subpage" => "debug"
			), SIMPLE_FIELDS_FILE ) );
			exit;

		}
	}

	/**
	 * Output contents for this options page
	 */
	function output_page() {
		
		do_action("simple_fields_options_print_nav_tabs", $this->slug);

		?>

		<div class="simple-fields-debug">
			
			<h3><?php echo __('Debug', 'simple-fields') ?></h3>
			
			<?php
			// Dropdown with debug options

			// Debug type. 0 = no debug, 1 = debug for admins only, 2 = debug for all
			$options = $this->sf->get_options();
			$debug_type = isset($options["debug_type"]) ? (int) $options["debug_type"] : "0";
			// capability edit_themes
			?>
			<form action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-options-save&amp;sf-options-subpage=debug" method="post">
				<?php
				printf('
					<p>
						<select name=debug_type>
							<option value=0 %1$s>%4$s</option>
							<option value=1 %2$s>%5$s</option>
							<option value=2 %3$s>%6$s</option>
						</select>
					</p>
					', 
					$debug_type === 0 ? "selected" : "",
					$debug_type === 1 ? "selected" : "",
					$debug_type === 2 ? "selected" : "",
					__("Don't enable debug output", "simple-fields"),
					__("Enable debug output for administrators", "simple-fields"),
					__("Enable debug output for all users", "simple-fields")
				);
				?>
				<p class=description>
					<?php _e("Automatically append information about attached fields on posts (using filter 'the_content').", "simple-fields"); ?>
				</p>

				<p>
					<input class="button" type=submit value="<?php _e("Save changes", "simple-fields") ?>">
				</p>

				<?php wp_nonce_field( "save-debug-options" ) ?>

			</form><!-- // enable debug -->
		
			<ul>
				<li><a href='<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=simple-fields-view-debug-info&amp;sf-options-subpage=debug'><?php echo __('View debug information', 'simple-fields') ?></a></li>
			</ul>

			<?php
			// view debug information
			$action = isset( $_REQUEST["action"] ) ? $_REQUEST["action"] : "";
			if ("simple-fields-view-debug-info" == $action) {

				echo "<h3>Post Connectors</h3>\n";
				echo "<p>Called with function <code>get_post_connectors()</code>";
				sf_d( $this->sf->get_post_connectors() );

				echo "<hr>";
				
				echo "<h3>Field Groups</h3>\n";
				echo "<p>Called with function <code>get_field_groups()</code>";
				sf_d( $this->sf->get_field_groups() );
				
				echo "<hr>";
				echo "<h3>simple_fields_post_type_defaults</h3>";
				echo '<p>Called with: get_option("simple_fields_post_type_defaults")';
				sf_d( $this->sf->get_post_type_defaults() );
				
			}

			?>

		</div>

		<?php

	}

}

new simple_fields_options_page_debug();
