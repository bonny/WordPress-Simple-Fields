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
		<script>
			
			jQuery(function($) {
			
				var custom_wrapper = $(".simple-fields-export-custom-wrapper"),
					form = $("form[name='simple-fields-tools-export-form']"),
					textarea = form.find("[name='export-json']");
					console.log(textarea);

				$(document).on("click", ".simple-fields-tools-export-import .simple-fields-export-what", function(e) {
					custom_wrapper.toggle( this.value == "custom" );
				});

				$(document).on("click", ".simple-fields-export-custom-wrapper input[type='checkbox']", function(e) {
					
					// Get all checked things
					textarea.text("Getting customized JSON ...");
					var postData = form.serializeArray();
					$.post(ajaxurl, postData, function(data) {
						textarea.text(data);
					}, "text");

				});

			});

		</script>
		<style>
			.simple-fields-export-custom-wrapper table th,
			.simple-fields-export-custom-wrapper table td {
				vertical-align: top;
				text-align: left;
			}
			.simple-fields-export-custom-wrapper ul {
				margin: 0;
				list-style-type: none;
			}
		</style>
		<?php

		// Collect for export...
		$field_groups_for_export = $this->get_field_groups(false);
		$post_connectors_for_export = $this->get_post_connectors();
		$post_type_defaults_for_export = $this->get_post_type_defaults();

		// Remove deleted connectors and possibly make other selection
		foreach ($post_connectors_for_export as $key => $val) {
			if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
		}

		?>

		<form method="post" action="" name="simple-fields-tools-export-form">

			<h3><?php echo __('Export', 'simple-fields') ?></h3>
			
			<p>Export Field Groups, Post Connectors and Post Type Defaults as JSON</p>

			<p>
				Export:
				<br>
				<label><input type="radio" name="export-what" class="simple-fields-export-what" value="all" checked> All</label>
				<br>
				<label><input type="radio" name="export-what" class="simple-fields-export-what" value="custom"> Custom</label>
			</p>

			<div class="simple-fields-export-custom-wrapper hidden">
				
				<table>
					<tr>
						<th>
							Field Groups
						</th>
						<th>
							Post connectors
						</th>
						<th>
							Post type defaults
						</th>
					</tr>

					<tr>
						<td>
							
							<?php
							echo "<ul class='simple-fields-export-custom-field-groups'>";
							foreach ($field_groups_for_export as $one_field_group) {
								printf('
									<li>
										<label>
											<input type="checkbox" value="%2$d" name="field-groups[]">
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
						</td>
						
						<td>
							<?php
							echo "<ul class='simple-fields-export-custom-post-connectors'>";
							foreach ($post_connectors_for_export as $one_post_connector) {
								printf('
									<li>
										<label>
											<input type="checkbox" value="%2$d" name="post-connectors[]">
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
						</td>

						<td>
							<?php
							echo "<ul class='simple-fields-export-custom-post-type-defaults'>";
							foreach ($post_type_defaults_for_export as $one_post_type_default_post_type => $one_post_type_default_key) {
								#sf_d($one_post_type_default);
								printf('
									<li>
										<label>
											<input type="checkbox" value="%2$s" name="post-type-defaults[]">
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
						</td>

					</tr>
				</table>

			</div>

			<?php
			// Get array with all export data
			$arr_export_data = $this->get_export();

			// beautify json if php version is more than or including 5.4.0
			if ( version_compare ( PHP_VERSION , "5.4.0" ) >= 0 ) {
				$export_json_string = json_encode( $arr_export_data , JSON_PRETTY_PRINT);
			} else {
				$export_json_string = json_encode( $arr_export_data );
			}

			?>	
			<textarea name="export-json" readonly cols=100 rows=10><?php echo $export_json_string ;?></textarea>

			<p>
				<input type="submit" class="button" value="Download export">
				<input type="hidden" name="action" value="simple_fields_get_export">
			</p>
		
		</form>

	</div><!-- simple-fields-tools-export-import -->

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
