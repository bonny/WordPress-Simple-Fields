<?php

/**
 * Simple FIelds options page for import and export
 */
class simple_fields_options_page_import_export {

	var 
		$slug = "import_export",
		$sf = null;
	
	function __construct() {		

		add_action("simple_fields_init", array($this, "init"));

	}

	function init($sf) {

		$this->sf = $sf;

		// Add tab and output content when on that tab
		add_action("simple_fields_after_last_options_nav_tab", array($this, "print_nav_tab"));
		add_action("simple_fields_subpage_$this->slug", array($this, "output_page"));

		add_action("admin_init", array($this, "maybe_download_export_file") );
		add_action("simple_fields_settings_admin_head", array($this, "output_scripts_and_styles"));
		add_action("wp_ajax_simple_fields_get_export", array($this, "ajax_get_export") );

		add_action("admin_init", array($this, "maybe_do_import"));

	}

	function maybe_do_import() {
		
		if ( isset($_POST) && isset( $_POST["action"] ) && ( $_POST["action"] === "simple_fields_do_import" ) ) {
			
			if ("file" === $_POST["import-what"]) {

				if ( empty($_FILES["import-file"]["tmp_name"]) || ! is_uploaded_file($_FILES["import-file"]["tmp_name"]) ||  $_FILES["import-file"]["error"] !== 0 ) {
					wp_die( __("Import failed: something went wrong while uploading import file.", "simple-fields") );
				}

				$import_json = file_get_contents( $_FILES["import-file"]["tmp_name"] );

			} elseif ("textarea" === $_POST["import-what"]) {

				$import_json = stripslashes( $_POST["import-json"] );

			}

			// We have JSON contents from file or textarea
			// @todo: create function of the next part
			$arr_import = json_decode($import_json, true);
			if ( is_null( $arr_import ) ) {
				wp_die( __("Import failed: JSON data is not valid.", "simple-fields") );
			}
			
			$arr_field_groups = isset($arr_import["field_groups"]) ? (array) $arr_import["field_groups"] : array();
			$arr_post_type_defaults = isset($arr_import["post_type_defaults"]) ? (array) $arr_import["post_type_defaults"] : array();
			$arr_post_connectors = isset($arr_import["post_connectors"]) ? (array) $arr_import["post_connectors"] : array();

			$import_type = $_POST["simple-fields-import-type"];
			/*
			$import_type:
			replace
			overwrite-append
			append-new
			*/
			#sf_d( $arr_import, '$arr_import');
			
			if ( "replace" === $import_type) {
				
				// Just update our options with 
				update_option("simple_fields_post_connectors", $arr_post_connectors);
				update_option("simple_fields_groups", $arr_field_groups);
				update_option("simple_fields_post_type_defaults", $arr_post_type_defaults);
				
				wp_redirect( add_query_arg( array(
					"sf-options-subpage" => "import_export",
					"message" => "import-done"
				), SIMPLE_FIELDS_FILE ) );

				exit;

				//simple_fields_register_post_type_default($post_type_connector, $post_type);
				
			} else if ( "append-new" === $import_type) {

				// import new fields
				// i.e. fields with slugs that do not exist in current data
				

			}

			exit;

		}
	}

	/**
	 * Get name of this options page tab
	 *
	 * @return string
	 */
	function get_name() {
		return _e('Import & Export', 'simple-fields');
	}

	function output_scripts_and_styles() {
		?>
		<script>
			
			jQuery(function($) {
			
				var 
					wrapper = $(".simple-fields-tools-export-import"),
					custom_wrapper = $(".simple-fields-export-custom-wrapper"),
					form = $("form[name='simple-fields-tools-export-form']"),
					textarea = form.find("[name='export-json']"),
					btnSubmit = form.find("input[type='submit']"),
					ajaxPost = null,
					textarea_export = wrapper.find("[name='import-json']"),
					file_import = wrapper.find("input[name='import-file']"),
					btn_import_submit = wrapper.find(".btn-submit-import")
				;

				// Click on radio button "export all" or "export custom"
				// = enable download button, show textarea, update export json
				$(document).on("click", ".simple-fields-tools-export-import .simple-fields-export-what", function(e) {
					
					custom_wrapper.toggle( this.value == "custom" );
					textarea.show();
					update_export_preview();
					btnSubmit.removeClass("button-disabled").removeAttr("disabled");

				});

				// Update json export when a checkbox is clicked
				$(document).on("click", ".simple-fields-export-custom-wrapper input[type='checkbox']", function(e) {
					update_export_preview();
				});

				// Show import textbox or file
				$(document).on("click", ".simple-fields-tools-export-import input[name='import-what']", function(e) {
					
					textarea_export.toggle( this.value == "textarea" );
					file_import.toggle( this.value === "file" );
					btn_import_submit.show();
					wrapper.find(".simple-fields-tools-export-import-section-import-type").show();

				});

				// Show import button when click on import type
				$(document).on("click", "input[name='simple-fields-import-type']", function(e) {
					btn_import_submit.removeAttr("disabled");
				});

				// Get json export from server via ajax
				function update_export_preview() {
					
					// Abort prev call
					if (ajaxPost && ajaxPost.readyState !== 4) {
//						console.log("aborted");
						ajaxPost.abort();
					}

					// Get all checked things
					textarea.text("Getting JSON ...");
					var postData = form.serializeArray();
					ajaxPost = $.post(ajaxurl, postData, function(data) {
						textarea.text(data);
					}, "text");
				}

			});

		</script>
		<style>
			.simple-fields-export-custom-wrapper table th,
			.simple-fields-export-custom-wrapper table td {
				vertical-align: top;
				text-align: left;
				padding: 0 20px 0 0;
			}
			.simple-fields-export-custom-wrapper ul {
				margin: 0;
				list-style-type: none;
			}
			form[name=simple-fields-tools-export-form] textarea {
				font-family: Consolas,Monaco,monospace;
				font-size: 12px;
				width: 50em;
				background: #f9f9f9;
				outline: 0;
			}


			.simple-fields-tools-export-import-section-import {
				border-top: 1px solid #dfdfdf;
				padding-top: 10px;
				margin-top: 30px;
			}

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
	 * Output contents for this options page
	 */
	function output_page() {
		
		do_action("simple_fields_options_print_nav_tabs", $this->slug);

		?>
		<div class="simple-fields-tools-export-import">

			<?php
			if ( isset( $_GET["message"] ) ) {
				
				switch ( $_GET["message"] ) {

					case "import-done":
						$message = "Import done";
						break;

					default:
						$message = "";

				}
				?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
				<?php
				
			} // if message
			?>

			<?php

			// Collect for export...
			$field_groups_for_export = $this->sf->get_field_groups(false);
			$post_connectors_for_export = $this->sf->get_post_connectors();
			$post_type_defaults_for_export = $this->sf->get_post_type_defaults();

			// Remove deleted connectors and possibly make other selection
			foreach ($post_connectors_for_export as $key => $val) {
				if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
			}

			?>

			<form method="post" action="" name="simple-fields-tools-export-form">

				<h3><?php _e("Export", "simple-fields" ) ?></h3>

				<p><?php _e("Export Field Groups, Post Connectors and Post Type Defaults as JSON.", "simple-fields") ?></p>

				<p>
					<label><input type="radio" name="export-what" class="simple-fields-export-what" value="all"> <?php _e("Export all data", "simple-fields") ?></label>
					<br>
					<label><input type="radio" name="export-what" class="simple-fields-export-what" value="custom"> <?php _e("Choose what to export", "simple-fields") ?></label>
				</p>

				<div class="simple-fields-export-custom-wrapper hidden">
					
					<table>
						<tr>
							<th>
								<?php _e("Field Groups", "simple-fields"); ?>
							</th>
							<th>
								<?php _e("Post connectors", "simple-fields"); ?>
							</th>
							<th>
								<?php _e("Post type defaults", "simple-fields"); ?>
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
									printf('
										<li>
											<label>
												<input type="checkbox" value="%1$s" name="post-type-defaults[]">
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
				<textarea class="hidden" name="export-json" readonly cols=100 rows=10><?php echo $export_json_string ;?></textarea>

				<p>
					<input type="submit" class="button button-disabled" disabled value="Download export">
					<input type="hidden" name="action" value="simple_fields_get_export">
				</p>
			
			</form>

			<form class="simple-fields-tools-export-import-section-import" enctype="multipart/form-data" method="post" action="<?php echo add_query_arg(array("sf-options-subpage" => "import_export"), SIMPLE_FIELDS_FILE) ?>">
	
				<h3><?php _e("Import", "simple-fields" ) ?></h3>

				<p><?php _e("Import Field Groups, Post Connectors and Post Type Defaults from JSON.") ?></p>
				<p><?php _e("Remember to backup your database before importing data.", "simple-fields") ?></p>

				<p>
					<label><input type="radio" name="import-what" class="simple-fields-import-what" value="textarea"> <?php _e("Import by pasting data from clipboard", "simple-fields") ?></label>
					<br>
					<label><input type="radio" name="import-what" class="simple-fields-import-what" value="file"> <?php _e("Import by uploading file", "simple-fields") ?></label>
				</p>

				<textarea class="hidden" name="import-json" cols=100 rows=10 placeholder="<?php _e("Paste your JSON data here", "simple-fields") ?>"></textarea>
				
				<p><input class="hidden" type="file" name="import-file" value="Select file"></p>
				
				<div class="simple-fields-tools-export-import-section-import-type hidden">

					<p><strong>Type of import</strong></p>

					<p>
						<label><input value="replace" type="radio" name="simple-fields-import-type"> Replace</label>
						<br>
						<span class="description">Replaces all existing data at server with the data from this import.
						<!-- If the slug is the same for an object then the id for that object will be kept. -->
						</span>
					</p>

					<!-- <p>
						<label><input value="overwrite-append" type="radio" name="simple-fields-import-type"> Overwrite & Append</label>
						<br>
						<span class="description">Keep existing data at server, but overwrite it if data exists both at server and in this import.
							Data that only exist in import is appended to server data.
						</span>
					</p> -->
					
					<!-- 
					<p>
						<label><input value="append-new" type="radio" name="simple-fields-import-type"> Append</label>
						<br>
						<span class="description">Existing data at server it left alone. New data in this import is added.</span>
					</p>
					-->

					<p>
						<input class="hidden button btn-submit-import" type="submit" value="Begin import" disabled>
						<input type="hidden" name="action" value="simple_fields_do_import">
					</p>
				
				</div>

			</form>

		</div><!-- simple-fields-tools-export-import -->
		<?php

	}

	function get_export( array $selection = array()) {

		$arr_export_data = array();

		$field_groups_for_export = $this->sf->get_field_groups(false);
		$post_connectors_for_export = $this->sf->get_post_connectors();
		$post_type_defaults_for_export = $this->sf->get_post_type_defaults();

		// Remove deleted connectors and possibly make other selection
		foreach ($post_connectors_for_export as $key => $val) {
			if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
		}

		// if selection is not empty then only include whats in there
		if ( ! empty( $selection ) && ( "custom" === $selection["export-what"] ) ) {
			
			$field_groups_to_keep = array();
			if ( ! empty( $_POST["field-groups"] ) ) {
				foreach ( (array) $_POST["field-groups"] as $one_field_group_id_to_keep) {
					$field_groups_to_keep[ $one_field_group_id_to_keep ] = $field_groups_for_export[ $one_field_group_id_to_keep ];
				}
			}
			$field_groups_for_export = $field_groups_to_keep;

			$post_connectors_to_keep = array();
			if ( ! empty( $_POST["post-connectors"] ) ) {
				foreach ( (array) $_POST["post-connectors"] as $one_post_connector_id_to_keep ) {
					$post_connectors_to_keep[ $one_post_connector_id_to_keep ] = $post_connectors_for_export[ $one_post_connector_id_to_keep ];
				}
			}
			$post_connectors_for_export = $post_connectors_to_keep;

			$post_type_defaults_to_keep = array();
			if ( ! empty( $_POST["post-type-defaults"] ) ) {
				foreach ( (array) $_POST["post-type-defaults"] as $one_post_type_to_keep) {
					$post_type_defaults_to_keep[ $one_post_type_to_keep ] = $post_type_defaults_for_export[ $one_post_type_to_keep ];
				}
			}
			$post_type_defaults_for_export = $post_type_defaults_to_keep;

		} // if selection


		if ( ! empty( $field_groups_for_export ) ) $arr_export_data["field_groups"] = $field_groups_for_export;
		if ( ! empty( $post_connectors_for_export ) ) $arr_export_data["post_connectors"] = $post_connectors_for_export;
		if ( ! empty( $post_type_defaults_for_export ) ) $arr_export_data["post_type_defaults"] = $post_type_defaults_for_export;
		
		return $arr_export_data;

	} // get_export

	function ajax_get_export() {
		
		$arr_export_data = $this->get_export( $_POST );

		// beautify json if php version is more than or including 5.4.0
		if ( version_compare ( PHP_VERSION , "5.4.0" ) >= 0 ) {
			$export_json_string = json_encode( $arr_export_data , JSON_PRETTY_PRINT);
		} else {
			$export_json_string = json_encode( $arr_export_data );
		}
		
		header('Content-Type: text/plain');
		echo $export_json_string;

		exit;

	} // ajax_get_export


	/**
	 * Check if export file should be downloaded,
	 * and if so send headers and the actual json contents
	 *
	 * @since 1.2.4
	 */
	function maybe_download_export_file() {

		// Don't do anything if this is an ajax call
		if ( defined("DOING_AJAX") && DOING_AJAX ) return;

		// And only do download then all post variables are set
		if ( isset($_POST) && isset( $_POST["action"] ) && ( $_POST["action"] === "simple_fields_get_export" ) ) {

			header('Content-disposition: attachment; filename=simple-fields-export.json');
			header('Content-type: application/json');

			echo stripslashes($_POST["export-json"]);
			exit;
			
		}

	} // maybe_download_export_file

}

new simple_fields_options_page_import_export();
