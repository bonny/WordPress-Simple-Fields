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

	<!-- import and export -->
	<div class="simple-fields-tools-export-import">

		<?php

		// Collect for export...
		$field_groups_for_export = $this->get_field_groups(false);
		$post_connectors_for_export = $this->get_post_connectors();
		$post_type_defaults_for_export = $this->get_post_type_defaults();

		?>

		<h3><?php echo __('Export', 'simple-fields') ?></h3>
		
		<p>Export Field Groups, Post Connectors and Post Type Defaults as JSON</p>

		<p>
			Export:
			<br>
			<label><input type="radio" name="export-what" checked> All</label>
			<br>
			<label><input type="radio" name="export-what"> Custom</label>
		</p>

		<div class="simple-fields-export-custom-wrapper">

			<p>Field Groups</p>
			<?php
			echo "<ul>";
			foreach ($field_groups_for_export as $one_field_group) {
				printf('
					<li>
						<label>
							<input type="checkbox" value="%2$d">
								%1$s
						</label>
					</li>
					', 
					esc_html( $one_field_group["name"] ),
					$one_field_group["id"]
				);
			}
			echo "</ul>";
			?>

			<p>Post connectors</p>
			<?php
			echo "<ul>";
			foreach ($post_connectors_for_export as $one_post_connector) {
				printf('
					<li>
						<label>
							<input type="checkbox" value="%2$d">
								%1$s
						</label>
					</li>
					', 
					esc_html( $one_post_connector["name"] ),
					$one_post_connector["id"]
				);
			}
			echo "</ul>";
			?>

			<p>Post type defaults</p>
			<?php
			echo "<ul>";
			foreach ($post_type_defaults_for_export as $one_post_type_default_post_type => $one_post_type_default_key) {
				#sf_d($one_post_type_default);
				printf('
					<li>
						<label>
							<input type="checkbox" value="%2$s">
								%1$s
						</label>
					</li>
					', 
					esc_html( $one_post_type_default_post_type ),
					$one_post_type_default_key
				);
			}
			echo "</ul>";
			?>

			Select Post Connectors to export
			Select Post Type Defaults to export
		</div>

		<?php
		$arr_export_data = array();

		// Remove deleted connectors and possibly make other selection
		foreach ($post_connectors_for_export as $key => $val) {
			if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
		}

		$arr_export_data["post_connectors"] = $post_connectors_for_export;

		// Get field groups for export
		$arr_export_data["field_groups"] = $field_groups_for_export;

		$arr_export_data["post_type_defaults"] = $post_type_defaults_for_export;
			
		// beautify json if php version is more than or including 5.4.0
		if ( version_compare ( PHP_VERSION , "5.4.0" ) >= 0 ) {
			$export_json_string = json_encode( $arr_export_data , JSON_PRETTY_PRINT);
		} else {
			$export_json_string = json_encode( $arr_export_data );
		}
		?>
	
		<textarea cols=100 rows=10><?php echo $export_json_string ;?></textarea>

		<p>
			<input type="submit" class="button" value="Download export">
		</p>

	</div>

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
