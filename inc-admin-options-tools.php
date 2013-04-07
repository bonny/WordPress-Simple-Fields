<?php

/**
 * Options screen for tools
 */

/**
 * If no action is set then show tools overview screen
 */
if ( ! $action ) {

	do_action("simple_fields_options_print_nav_tabs", $subpage);
	
	?>

	<!-- debug -->
	<div class="simple-fields-debug">
		
		<h3><?php echo __('Debug', 'simple-fields') ?></h3>
		
		<?php
		// Dropdown with debug options

		// Debug type. 0 = no debug, 1 = debug for admins only, 2 = debug for all
		$options = $this->get_options();
		$debug_type = isset($options["debug_type"]) ? (int) $options["debug_type"] : "0";
		// capability edit_themes
		?>
		<form action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-options-save" method="post">
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
			<li><a href='<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=simple-fields-view-debug-info'><?php echo __('View debug information', 'simple-fields') ?></a></li>
		</ul>

	</div>
	
	<?php

} // end simple_fields_options
