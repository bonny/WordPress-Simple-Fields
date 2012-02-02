<?php

/**
 * return an array of the post types that we have set up post connectors for
 * @param return array
 */
function simple_fields_post_connector_attached_types() {
	$post_connectors = (array) get_option("simple_fields_post_connectors");
	$arr_post_types = array();
	foreach ($post_connectors as $one_post_connector) {
		$arr_post_types = array_merge($arr_post_types, (array) $one_post_connector["post_types"]);
	}
	$arr_post_types = array_unique($arr_post_types);
	return $arr_post_types;
}

function simple_fields_get_post_connectors_for_post_type($post_type) {

	$arr_post_connectors = simple_fields_get_post_connectors();
	$arr_found_connectors = array();

	foreach ($arr_post_connectors as $one_connector) {
		if ($one_connector && in_array($post_type, $one_connector["post_types"])) {
			$arr_found_connectors[] = $one_connector;
		}
	}
	return $arr_found_connectors;
}

function simple_fields_get_post_connectors() {
	return (array) get_option("simple_fields_post_connectors");
}

function simple_fields_get_field_groups() {
	return (array) get_option("simple_fields_groups");
}

function simple_fields_admin_menu() {
	add_submenu_page( 'options-general.php' , EASY_FIELDS_NAME, EASY_FIELDS_NAME, "administrator", "simple-fields-options", "simple_fields_options");
}

function simple_fields_options() {

	$field_groups = get_option("simple_fields_groups");
	$post_connectors = get_option("simple_fields_post_connectors");

	/*
	$field_groups = get_option("easy_fields_groups");
	$post_connectors = get_option("easy_fields_post_connectors");
	update_option("simple_fields_groups", $field_groups);
	update_option("simple_fields_post_connectors", $post_connectors);
	// */

	// for debug purposes, here we can reset the option
	#$field_groups = array(); update_option("simple_fields_groups", $field_groups);
	#$post_connectors = array(); update_option("simple_fields_post_connectors", $post_connectors);

	// first run? make sure field groups is an array
	if (!$field_groups) {
		$field_groups = array();
	}
	if (!$post_connectors) {
		$post_connectors = array();
	}

	// sort them by name
	function simple_fields_uasort($a, $b) {
		if ($a["name"] == $b["name"]) { return 0; }
		return strcasecmp($a["name"], $b["name"]);
	}
	
	uasort($field_groups, "simple_fields_uasort");
	uasort($post_connectors, "simple_fields_uasort");
	
	// sometimes we get a empty field group on pos zero.. wierd.. can't find the reason for it right now.. :(
	#if ($field_groups[0] && empty($field_groups[0]["name"])) {
	#	unset($field_groups[0]);
	#}

	?>
	<div class="wrap">

		<h2><?php echo EASY_FIELDS_NAME ?></h2>

		<div class="clear"></div>

		<div class="simple-fields-bonny-plugins-inner-sidebar">
			<p>
				If you like this plugin you are welcome to support the author by donating:
			</p>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="text-align: center">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
				">
				<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>

			<p style="text-align: center">
				<a href="https://flattr.com/thing/116510/Simple-Fields" target="_blank">
				<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>
			</p>

			<p>
				You can also show your appreciation 
				by giving the plugin a <a href="http://wordpress.org/extend/plugins/simple-fields/">good rating over at the plugin directory</a>
				or blog or tweet something nice about this plugin.
			</p>

			<h3>Support</h3>
			<p>If you have any problems with this plugins please check out the <a href="http://wordpress.org/tags/simple-fields?forum_id=10">support forum</a>.</p>
			
			<h3>More CMS related plugins</h3>
			<p>These are some more plugins that enhance the CMS functionality of WordPress. Please check them out!</p>
			<ul>
				<li><a href="http://wordpress.org/extend/plugins/cms-tree-page-view/">CMS Tree Page View</a></li>
				<li><a href="http://wordpress.org/extend/plugins/admin-menu-tree-page-view/">Admin Menu Tree Page View</a></li>
				<li><a href="http://wordpress.org/extend/plugins/simple-history/">Simple History</a></li>
			</ul>
			
			
		</div>
	<div class="simple-fields-settings-wrap">

		<?php
		$action = (isset($_GET["action"])) ? $_GET["action"] : null;
		
		/**
		 * save post type defaults
		 */
		if ("edit-post-type-defaults-save" == $action) {
			$post_type = $_POST["simple_fields_save-post_type"];
			$post_type_connector = $_POST["simple_fields_save-post_type_connector"];
			$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
			$post_type_defaults["$post_type"] = $post_type_connector;
			update_option("simple_fields_post_type_defaults", $post_type_defaults);
			$simple_fields_did_save_post_type_defaults = true;
			$action = "";
		}

		/**
		 * edit post type defaults
		 */
		if ("edit-post-type-defaults" == $action) {
			$post_type = $_GET["post-type"];
			global $wp_post_types;
			if (isset($wp_post_types[$post_type])) {
				$selected_post_type = $wp_post_types[$post_type];
				?>
				<h3><?php echo __('Post type', 'simple-fields').' '.$post_type ?></h3>
				
				<form action="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-post-type-defaults-save" method="post">
					<table class="form-table">
						<tr>
							<th><?php _e('Default post connector', 'simple-fields') ?></th>
							<td>
								<?php
								$arr_post_connectors = simple_fields_get_post_connectors_for_post_type($post_type);
								if ($arr_post_connectors) {
									$selected_post_type_default = simple_fields_get_default_connector_for_post_type($post_type);
									?>
									<select name="simple_fields_save-post_type_connector">
										<option <?php echo ($selected_post_type_default=="__none__") ? " selected='selected' " : "" ?> value="__none__"><?php _e('No post connector', 'simple-fields') ?></option>
										<option <?php echo ($selected_post_type_default=="__inherit__") ? " selected='selected' " : "" ?> value="__inherit__"><?php _e('Inherit from parent post', 'simple-fields') ?></option>
										<?php
										foreach ($arr_post_connectors as $one_post_connector) {
											echo "<option " . (($selected_post_type_default==$one_post_connector["id"]) ? " selected='selected' " : "") . "value='{$one_post_connector["id"]}'>" . $one_post_connector["name"] . "</option>";
										}
										?>
									</select>
									<?php
								} else {
									?><p><?php _e('There are no post connectors for this post type.', 'simple-fields') ?></p><?php
								}
								?>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input class="button-primary" type="submit" value="Save Changes" />
						<input type="hidden" name="simple_fields_save-post_type" value="<?php echo $post_type ?>" />
						<?php _e('or', 'simple_fields');  ?>
						<a href="<?php echo EASY_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
					</p>
				</form>
				<?php
				#d($selected_post_type);
			}
		}

		/**
		 * Delete a field group
		 */
		if ("delete-field-group" == $action) {
			$field_group_id = (int) $_GET["group-id"];
			$field_groups[$field_group_id]["deleted"] = true;
			update_option("simple_fields_groups", $field_groups);
			$simple_fields_did_delete = true;
			$action = "";
		}

		/**
		 * Delete a post connector
		 */
		if ("delete-post-connector" == $action) {
			$post_connector_id = (int) $_GET["connector-id"];
			$post_connectors[$post_connector_id]["deleted"] = 1;
			update_option("simple_fields_post_connectors", $post_connectors);
			$simple_fields_did_delete_post_connector = true;
			$action = "";
		}
		
		
		/**
		 * save a field group
		 * including fields
		 */
		if ("edit-field-group-save" == $action) {
			/*
			Array
			(
			    [field_group_name] => Unnamed field group 59 changed
			    [action] => update
			    [page_options] => field_group_name
			    [field_group_id] => 59
			)
					[type_taxonomy_options] => Array
                        (
                            [enabled_taxonomies] => Array
                                (
                                    [0] => category
                                    [1] => post_tag
                                    [2] => post_format
                                    [3] => mentions
                                )

                        )
			*/
			#echo "<pre>";print_r($_POST);exit;
			if ($_POST) {
			
				$field_group_id = (int) $_POST["field_group_id"];
				$field_groups[$field_group_id]["name"] = stripslashes($_POST["field_group_name"]);
				$field_groups[$field_group_id]["description"] = stripslashes($_POST["field_group_description"]);
				$field_groups[$field_group_id]["repeatable"] = (bool) (isset($_POST["field_group_repeatable"]));
				
				$field_groups[$field_group_id]["fields"] = (array) stripslashes_deep($_POST["field"]);
				/*
				if just one empty array like this, unset first elm
				happens if no fields have been added (now why would you do such an evil thing?!)
	            [fields] => Array
	                (
	                    [0] => 
	                )
				*/
				if (sizeof($field_groups[$field_group_id]["fields"]) == 1 && empty($field_groups[$field_group_id]["fields"][0])) {
					unset($field_groups[$field_group_id]["fields"][0]);
				}
				
				// @todo: are these used? options are saved on a per field basis… right?!
				$field_groups[$field_group_id]["type_textarea_options"] = (array) @$_POST["type_textarea_options"];
				$field_groups[$field_group_id]["type_radiobuttons_options"] = (array) @$_POST["type_radiobuttons_options"];
				$field_groups[$field_group_id]["type_taxonomy_options"] = (array) @$_POST["type_taxonomy_options"];
				//$field_groups[$field_group_id]["type_taxonomyterm_options"] = (array) @$_POST["type_taxonomyterm_options"];

				// echo "<pre>fields_groups:"; print_r($field_groups);exit;
						
				update_option("simple_fields_groups", $field_groups);
				// echo "<pre>";print_r($field_groups);echo "</pre>";
				// we can have changed the options of a field group, so update connectors using this field group
				$post_connectors = (array) simple_fields_get_post_connectors();
				foreach ($post_connectors as $connector_id => $connector_options) {
					if (isset($connector_options["field_groups"][$field_group_id])) {
						// field group existed, update name
						$post_connectors[$connector_id]["field_groups"][$field_group_id]["name"] = stripslashes($_POST["field_group_name"]);
					}
				}
				update_option("simple_fields_post_connectors", $post_connectors);
				
				$simple_fields_did_save = true;
			}
			#$action = "simple-fields-edit-field-groups";
			$action = "";
					
		}

		/**
		 * save a post connector
		 */
		if ("edit-post-connector-save" == $action) {
			if ($_POST) {
				
				//d($_POST);
				#d($post_connectors);
				
				$connector_id = (int) $_POST["post_connector_id"];
				$post_connectors[$connector_id]["name"] = (string) stripslashes($_POST["post_connector_name"]);
				$post_connectors[$connector_id]["field_groups"] = (array) $_POST["added_fields"];
				$post_connectors[$connector_id]["post_types"] = (array) @$_POST["post_types"];
				$post_connectors[$connector_id]["hide_editor"] = (bool) @$_POST["hide_editor"];

				// a post type can only have one default connector, so make sure only the connector
				// that we are saving now has it; remove it from all others;
				/*
				$post_types_type_default = (array) $_POST["post_types_type_default"];
				foreach ($post_types_type_default as $one_default_post_type) {
					foreach ($post_connectors as $one_post_connector) {
						if (in_array($one_default_post_type, $one_post_connector["post_types_type_default"])) {
							$array_key = array_search($one_default_post_type, $one_post_connector["post_types_type_default"]);
							if ($array_key !== false) {
								unset($post_connectors[$one_post_connector["id"]]["post_types_type_default"][$array_key]);
							}
						}
					}
				}
				$post_connectors[$connector_id]["post_types_type_default"] = $post_types_type_default;
				*/
				
				// for some reason I got an empty connector (array key was empty) so check for these and remove
				$post_connectors_tmp = array();
				foreach ($post_connectors as $key => $one_connector) {
					if (!empty($one_connector)) {
						$post_connectors_tmp[$key] = $one_connector;
					}
				}
				$post_connectors = $post_connectors_tmp;

				update_option("simple_fields_post_connectors", $post_connectors);

				$simple_fields_did_save_connector = true;
			}
			#$action = "simple-fields-edit-connectors";
			$action = "";
		}

		
		/**
		 * edit new or existing post connector
		 */
		if ("edit-post-connector" == $action) {
			$connector_id =  (int) $_GET["connector-id"];
			$highest_connector_id = 0;
	
			// if new, save it as unnamed, and then set to edit that
			if ($connector_id == 0) {
				foreach ($post_connectors as $oneConnector) {
					if ($oneConnector["id"]>$highest_connector_id) {
						$highest_connector_id = $oneConnector["id"];
					}
				}
				$highest_connector_id++;
				$connector_id = $highest_connector_id;
				
				$post_connectors[$connector_id] = array(
					"id" => $connector_id,
					"name" => "Unnamed post connector $connector_id",
					"field_groups" => array(),
					"post_types" => array(),
					#"post_types_type_default" = array(),
					"deleted" => FALSE,
					"hide_editor" => FALSE
				);
				
				update_option("simple_fields_post_connectors", $post_connectors);

			} else {
				// existing post connector
				
				// set a default value for hide_editor if it does not exist. did not exist until 0.5
				$post_connectors[$connector_id]["hide_editor"] = (bool) @$post_connectors[$connector_id]["hide_editor"];
			}

			$post_connector_in_edit = $post_connectors[$connector_id];
			// echo "<pre>";print_r($post_connector_in_edit);echo "</pre>";
			?>
			<h3><?php _e('Post Connector details', 'simple-fields') ?></h3>

			<form method="post" action="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-post-connector-save">

				<table class="form-table">
					<tr>
						<th><label><?php _e('Name', 'simple-fields') ?></label></th>
						<td><input type="text" id="post_connector_name" name="post_connector_name" class="regular-text" value="<?php echo esc_html($post_connector_in_edit["name"]) ?>" /></td>
					</tr>
					<tr>
						<th><?php _e('Field Groups', 'simple-fields') ?></th>
	
						<td>
							<p>
								<select id="simple-fields-post-connector-add-fields">
									<option value=""><?php _e('Add field group...', 'simple-fields') ?></option>
									<?php
									foreach ($field_groups as $one_field_group) {
										if ($one_field_group["deleted"]) { continue; }
										?><option value='<?php echo $one_field_group["id"] ?>'><?php echo esc_html($one_field_group["name"]) ?></option><?php
									}
									?>
								</select>
							</p>
							<ul id="simple-fields-post-connector-added-fields">
								<?php
								foreach ($post_connector_in_edit["field_groups"] as $one_post_connector_added_field) {
									if ($one_post_connector_added_field["deleted"]) { continue; }
									
									#d($one_post_connector_added_field);
									
									?>
									<li>
										<div class='simple-fields-post-connector-addded-fields-handle'></div>
										<div class='simple-fields-post-connector-addded-fields-field-name'><?php echo $one_post_connector_added_field["name"] ?></div>
										<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][id]' value='<?php echo $one_post_connector_added_field["id"] ?>' />
										<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][name]' value='<?php echo $one_post_connector_added_field["name"] ?>' />
										<input type='hidden' name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][deleted]' value='0' class="simple-fields-post-connector-added-field-deleted" />
										<div class="simple-fields-post-connector-addded-fields-options">
											<?php _e('Context', 'simple-fields') ?>
											<select name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][context]' class="simple-fields-post-connector-addded-fields-option-context">
												<option <?php echo ("normal" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="normal"><?php _e('normal') ?></option>
												<option <?php echo ("advanced" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="advanced"><?php _e('advanced') ?></option>
												<option <?php echo ("side" == $one_post_connector_added_field["context"]) ? " selected='selected' " : "" ?> value="side"><?php _e('side') ?></option>
											</select>
											
											<?php _e('Priority', 'simple-fields') ?>
											<select name='added_fields[<?php echo $one_post_connector_added_field["id"] ?>][priority]' class="simple-fields-post-connector-addded-fields-option-priority">
												<option <?php echo ("low" == $one_post_connector_added_field["priority"]) ? " selected='selected' " : "" ?> value="low"><?php _e('low') ?></option>
												<option <?php echo ("high" == $one_post_connector_added_field["priority"]) ? " selected='selected' " : "" ?> value="high"><?php _e('high') ?></option>
											</select>
										</div>
										<a href='#' class='simple-fields-post-connector-addded-fields-delete'><?php _e('Delete', 'simple-fields') ?></a>
									</li>
									<?php
								}
								?>
							</ul>
						</td>
					</tr>
					
					<tr>
						<th><?php _e('Options', 'simple-fields') ?></th>
						<td><input
							 type="checkbox" 
							 <?php echo $post_connector_in_edit["hide_editor"] == TRUE ? " checked='checked' " : "" ?>
							 name="hide_editor" 
							 class="" 
							 value="1" />
							 <?php _e('Hide the built in editor', 'simple-fields') ?>
						</td>
					</tr>
					
					<tr>
						<th>
							<?php _e('Available for post types', 'simple-fields') ?>
						</th>
						<td>
							<table>
								<?php
								global $wp_post_types;
								$arr_post_types_to_ignore = array("attachment", "revision", "nav_menu_item");
								foreach ($wp_post_types as $one_post_type) {
									if (!in_array($one_post_type->name, $arr_post_types_to_ignore)) {
										?>
										<tr>
											<td>
												<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types"]) ? " checked='checked' " : ""); ?> type="checkbox" name="post_types[]" value="<?php echo $one_post_type->name ?>" />
												<?php echo $one_post_type->name ?>
											</td>
											<?php
											/*
											<!-- <td>
												<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types_type_default"]) ? " checked='checked' " : "") ?> type="checkbox" name="post_types_type_default[]" value="<?php echo $one_post_type->name ?>" />
												Default connector for post type <?php echo $one_post_type->name ?>
											</td> -->
											*/
										?>
										</tr>
										<?php
									}
								}
								?>
							</table>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input class="button-primary" type="submit" value="<?php _e('Save Changes', 'simple-fields') ?>" />
					<input type="hidden" name="action" value="update" />
					<!-- <input type="hidden" name="page_options" value="field_group_name" /> -->
					<input type="hidden" name="post_connector_id" value="<?php echo $post_connector_in_edit["id"] ?>" />
					or 
					<a href="<?php echo EASY_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
				</p>
				<p class="simple-fields-post-connector-delete">
					<a href="<?php echo EASY_FIELDS_FILE ?>&amp;action=delete-post-connector&amp;connector-id=<?php echo $post_connector_in_edit["id"] ?>"><?php _e('Delete') ?></a>
				</p>

			</form>
			<?php
		}

	
		/**
		 * edit new or existing group
		 */
		if ("edit-field-group" == $action) {
	
			$field_group_id = (int) $_GET["group-id"];
			$highest_field_id = 0;
	
			// if new, save it as unnamed, and then set to edit that
			if ($field_group_id == 0) {
				$highest_id = 0;
				foreach ($field_groups as $oneGroup) {
					if ($oneGroup["id"]>$highest_id) {
						$highest_id = $oneGroup["id"];
					}
				}
				$highest_id++;
				$field_group_id = $highest_id;
				
				$field_groups[$field_group_id] = array(
					"id" => $field_group_id,
					"name" => "Unnamed field group $field_group_id",
					"repeatable" => false,
					"fields" => array(),
					"deleted" => false
				);
				
				update_option("simple_fields_groups", $field_groups);

			} else {
				// existing field group
				// get highest group and field id
				foreach ($field_groups[$field_group_id]["fields"] as $one_field) {
					if ($one_field["id"] > $highest_field_id) {
						$highest_field_id = $one_field["id"];
					}
				}
			}
			
			$field_group_in_edit = $field_groups[$field_group_id];
			
			?>
			<form method="post" action="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-field-group-save">
				<?php #settings_fields('simple_fields_options'); ?>
	            <h3><?php _e('Field group details', 'simple-fields') ?></h3>
	            <table class="form-table">
	            	<tr>
	            		<th><label for="field_group_name"><?php _e('Name', 'simple-fields') ?></label></th>
	            		<td>
	            			<input type="text" name="field_group_name" id="field_group_name" class="regular-text" value="<?php echo esc_html($field_group_in_edit["name"]) ?>" />
	            			
							<br />
	            			<label for="field_group_description">
								<?php _e('Description', 'simple-fields') ?>
								<input type="text" name="field_group_description" id="field_group_description" class="regular-text" value="<?php echo esc_html($field_group_in_edit["description"]) ?>" />
							</label>

	            			<br />	
	            			<label for="field_group_repeatable">
								<input type="checkbox" <?php echo ($field_group_in_edit["repeatable"] == true) ? "checked='checked'" : ""; ?> value="1" id="field_group_repeatable" name="field_group_repeatable" />
								<?php _e('Repeatable', 'simple-fields') ?>
							</label>
								
	            		</td>
	            	</tr>
	            	<tr>
	            		<th><?php _e('Fields', 'simple-fields') ?></th>
	            		<td>
	            			<div id="simple-fields-field-group-existing-fields">
	            				<ul class='simple-fields-edit-field-groups-added-fields'>
									<?php
									foreach ($field_group_in_edit["fields"] as $oneField) {
										if (!$oneField["deleted"]) {
											echo simple_fields_field_group_add_field_template($oneField["id"], $field_group_in_edit);
										}
									}
									?>
	            				</ul>
	            			</div>
	            			<p><a href="#" id="simple-fields-field-group-add-field">+ <?php _e('Add field', 'simple-fields') ?></a></p>
	            		</td>
	            	</tr>			
				</table>

				<p class="submit">
					<input class="button-primary" type="submit" value="<?php _e('Save Changes', 'simple-fields') ?>" />
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="field_group_name" />
					<input type="hidden" name="field_group_id" value="<?php echo $field_group_in_edit["id"] ?>" />
					<?php _e('or', 'simple-fields') ?> 
					<a href="<?php echo EASY_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
				</p>
				<p class="simple-fields-field-group-delete">
					<a href="<?php echo EASY_FIELDS_FILE ?>&amp;action=delete-field-group&amp;group-id=<?php echo $field_group_in_edit["id"] ?>"><?php _e('Delete', 'simple-fields') ?></a>
				</p>
				
			</form>
	
			<script type="text/javascript">
				var simple_fields_highest_field_id = <?php echo (int) $highest_field_id ?>;
			</script>
	
			<?php
		
		}

		// view debug information
		if ("simple-fields-view-debug-info" == $action) {

			echo "<h3>Post Connectors</h3>\n";
			echo "<p>Called with function <code>simple_fields_get_post_connectors()</code>";
			echo "<pre>";
			print_r( simple_fields_get_post_connectors() );
			echo "</pre>";

			echo "<hr />";
			
			echo "<h3>Field Groups</h3>\n";
			echo "<p>Called with function <code>simple_fields_get_field_groups()</code>";
			echo "<pre>";
			print_r( simple_fields_get_field_groups() );
			echo "</pre>";
			
		}


		// overview, if no action
		if (!$action) {


			/**
			 * view post connectors
			 */
			$post_connector_count = 0;
			foreach ($post_connectors as $onePostConnector) {
				if (!$onePostConnector["deleted"]) {
					$post_connector_count++;
				}
			}


			/**
			 * view existing field groups
			 */	
			?>
			<div class="simple-fields-edit-field-groups">

				<h3><?php _e('Field groups', 'simple-fields') ?></h3>

				<?php
				if (isset($simple_fields_did_save) && $simple_fields_did_save) {
					?><div id="message" class="updated"><p><?php _e('Field group saved', 'simple-fields') ?></p></div><?php
				} elseif (isset($simple_fields_did_delete) && $simple_fields_did_delete) {
					?><div id="message" class="updated"><p><?php _e('Field group deleted', 'simple-fields') ?></p></div><?php
				} elseif (isset($simple_fields_did_delete_post_connector) && $simple_fields_did_delete_post_connector) {
					?><div id="message" class="updated"><p><?php _e('Post connector deleted', 'simple-fields') ?></p></div><?php
				} elseif (isset($simple_fields_did_save_post_type_defaults) && $simple_fields_did_save_post_type_defaults) {
					?><div id="message" class="updated"><p><?php _e('Post type defaults saved', 'simple-fields') ?></p></div><?php
				}
				
				$field_group_count = 0;
				foreach ($field_groups as $oneFieldGroup) {
					if (!$oneFieldGroup["deleted"]) {
						$field_group_count++;
					}
				}

				if ($field_groups == $field_group_count) {
					echo "<p>".__('No field groups yet.', 'simple-fields')."</p>";
				} else {
					echo "<ul class=''>";
					foreach ($field_groups as $oneFieldGroup) {
						if (!$oneFieldGroup["deleted"]) {
							echo "<li><a href='" . EASY_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=$oneFieldGroup[id]'>$oneFieldGroup[name]</a></li>";
						}
					}
					echo "</ul>";
				}
				echo "<p><a class='button' href='" . EASY_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=0'>+ ".__('New field group', 'simple-fields')."</a></p>";
				?>
			</div>
		
		
			<div class="simple-fields-edit-post-connectors">

				<h3><?php _e('Post Connectors', 'simple-fields') ?></h3>

				<?php
				if (isset($simple_fields_did_save_connector) && $simple_fields_did_save_connector) {
					?><div id="message" class="updated"><p><?php _e('Post connector saved', 'simple-fields') ?></p></div><?php
				}

				if ($post_connector_count) {
					?><ul><?php
						foreach ($post_connectors as $one_post_connector) {
							if ($one_post_connector["deleted"]) {
								continue;
							}
							?>
							<li>
								<a href="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-post-connector&amp;connector-id=<?php echo $one_post_connector["id"] ?>"><?php echo $one_post_connector["name"] ?></a>
							</li>
							<?php
							
						}
					?></ul><?php
				} else {
					?>
					<!-- <p>No post connectors</p> -->
					<?php
				}
				?>
				<p>
					<a href="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-post-connector&amp;connector-id=0" class="button">+ <?php _e('New post connector', 'simple-fields') ?></a>
				</p>
				
			</div>

			<div class="easy-fields-post-type-defaults">
				<h3><?php _e('Post type defaults', 'simple-fields') ?></h3>
				<?php
				#$post_types = get_post_types();
				#d($post_types);
				?>
				<ul>
					<?php
					$post_types = get_post_types();
					$arr_post_types_to_ignore = array("attachment", "revision", "nav_menu_item");
					foreach ($post_types as $one_post_type) {
						$one_post_type_info = get_post_type_object($one_post_type);
						#d($one_post_type_info);
						if (!in_array($one_post_type, $arr_post_types_to_ignore)) {
							?><li>
								<a href="<?php echo EASY_FIELDS_FILE ?>&amp;action=edit-post-type-defaults&amp;post-type=<?php echo $one_post_type ?>"><?php echo $one_post_type_info->label ?></a>
							</li><?php
						}
					}
					?>
				</ul>
			</div>	
			
			<div class="simple-fields-debug">
				<h3><?php echo __('Debug', 'simple-fields') ?></h3>
				<ul>
					<li><a href='<?php echo EASY_FIELDS_FILE ?>&amp;action=simple-fields-view-debug-info'><?php echo __('View debug information', 'simple-fields') ?></a></li>
				</ul>
			</div>
			
			<?php

		} // end simple_fields_options

		?>
		</div>
	</div>	

	<?php
} // end func simple_fields_options

function simple_fields_field_group_add_field() {
	$simple_fields_highest_field_id = (int) $_POST["simple_fields_highest_field_id"];
	echo simple_fields_field_group_add_field_template($simple_fields_highest_field_id);
	exit;
}

/**
 * Returns the output for a new or existing field
 * That it: 
 */
function simple_fields_field_group_add_field_template($fieldID, $field_group_in_edit = null) {
	$fields = $field_group_in_edit["fields"];
	$field_name = esc_html($fields[$fieldID]["name"]);
	$field_description = esc_html($fields[$fieldID]["description"]);
	$field_type = $fields[$fieldID]["type"];
	$field_deleted = (int) $fields[$fieldID]["deleted"];
	
	$field_type_textarea_option_use_html_editor = (int) @$fields[$fieldID]["type_textarea_options"]["use_html_editor"];
	$field_type_checkbox_option_checked_by_default = (int) @$fields[$fieldID]["type_checkbox_options"]["checked_by_default"];
	$field_type_radiobuttons_options = (array) @$fields[$fieldID]["type_radiobuttons_options"];
	$field_type_dropdown_options = (array) @$fields[$fieldID]["type_dropdown_options"];

	$field_type_post_options = (array) @$fields[$fieldID]["type_post_options"];
	$field_type_post_options["enabled_post_types"] = (array) @$field_type_post_options["enabled_post_types"];

	$field_type_taxonomy_options = (array) @$fields[$fieldID]["type_taxonomy_options"];
	$field_type_taxonomy_options["enabled_taxonomies"] = (array) @$field_type_taxonomy_options["enabled_taxonomies"];

	$field_type_date_options = (array) @$fields[$fieldID]["type_date_options"];
	$field_type_date_option_use_time = @$field_type_date_options["use_time"];

	$field_type_taxonomyterm_options = (array) @$fields[$fieldID]["type_taxonomyterm_options"];
	$field_type_taxonomyterm_options["enabled_taxonomy"] = (string) @$field_type_taxonomyterm_options["enabled_taxonomy"];

	// echo "<pre>field_type_taxonomyterm_options:"; print_r($field_type_taxonomyterm_options);
	// echo "<pre>";print_r($field_type_taxonomy_options);echo "</pre>";
	// echo "<pre>";print_r($fields[$fieldID]);echo "</pre>";
	
	$out = "";
	$out .= "
	<li class='simple-fields-field-group-one-field simple-fields-field-group-one-field-id-{$fieldID}'>
		<div class='simple-fields-field-group-one-field-handle'></div>

		<div class='simple-fields-field-group-one-field-row'>
			<label class='simple-fields-field-group-one-field-name-label'>".__('Name', 'simple-fields')."</label>
			<!-- <br /> -->
			<input type='text' class='regular-text simple-fields-field-group-one-field-name' name='field[{$fieldID}][name]' value='{$field_name}' />
		</div>
		
		<div class='simple-fields-field-group-one-field-row simple-fields-field-group-one-field-row-description'>
			<label>".__('Description', 'simple-fields')."</label>
			<!-- <br /> -->
			<input type='text' class='regular-text' name='field[{$fieldID}][description]' value='{$field_description}' />
		</div>
		
		<div class='simple-fields-field-group-one-field-row'>
			<label>".__('Type', 'simple-fields')."</label>
			<!-- <br /> -->
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
			</select>

			<div class='simple-fields-field-group-one-field-row " . (($field_type=="text") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-text'>
			</div>
		</div>

		<div class='simple-fields-field-group-one-field-row " . (($field_type=="textarea") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-textarea'>
			<input type='checkbox' name='field[{$fieldID}][type_textarea_options][use_html_editor]' " . (($field_type_textarea_option_use_html_editor) ? " checked='checked'" : "") . " value='1' /> ".__('Use HTML-editor', 'simple-fields')."
		</div>
		";
		
		// date
		$out .= "<div class='" . (($field_type=="date") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-date'>";
		$out .= "<input type='checkbox' name='field[{$fieldID}][type_date_options][use_time]' " . (($field_type_date_option_use_time) ? " checked='checked'" : "") . " value='1' /> ".__('Also show time', 'simple-fields');
		$out .= "</div>";

		// connect post - select post types
		$out .= "<div class='" . (($field_type=="post") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-post'>";
		$out .= "<div class='simple-fields-field-group-one-field-row'>";
		$out .= sprintf("<label>%s</label>", __('Post types to select from', 'simple-fields'));
		//$out .= sprintf("<select name='%s'>", "field[$fieldID][type_post_options][post_type]");
		//$out .= sprintf("<option %s value='%s'>%s</option>", (empty($field_type_post_options["post_type"]) ? " selected='selected' " : "") ,"", "Any");

		// list all post types in checkboxes
		$post_types = get_post_types(NULL, "objects");
		$loopnum = 0;
		foreach ($post_types as $one_post_type) {
			// skip some built in types
			if (in_array($one_post_type->name, array("attachment", "revision", "nav_menu_item"))) {
				continue;
			}
			$input_name = "field[{$fieldID}][type_post_options][enabled_post_types][]";
			$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s</input>", 
								($loopnum>0 ? "<br />" : ""), 
								$input_name,
								((in_array($one_post_type->name, $field_type_post_options["enabled_post_types"])) ? " checked='checked' " : ""), 
								$one_post_type->name, 
								$one_post_type->labels->name . " ($one_post_type->name)"
							);
			$loopnum++;
		}
		$out .= "</div>";

		$out .= "<div class='simple-fields-field-group-one-field-row'>";
		$out .= "<label>Additional arguments</label>";
		$out .= sprintf("<input type='text' name='%s' value='%s' />", "field[$fieldID][type_post_options][additional_arguments]", @$field_type_post_options["additional_arguments"]);
		$out .= sprintf("<br /><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Class_Reference/WP_Query'>WP_Query</a>.</span>");
		$out .= "</div>";
		$out .= "</div>"; // whole divs that shows/hides


		// connect taxonomy - select taxonomies
		$out .= "<div class='" . (($field_type=="taxonomy") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomy'>";
		$out .= sprintf("<label>%s</label>", __('Taxonomies to show in dropdown', 'simple-fields'));
		$taxonomies = get_taxonomies(NULL, "objects");
		$loopnum = 0;
		foreach ($taxonomies as $one_tax) {
			// skip some built in types
			if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
			    continue;
			}
			$input_name = "field[{$fieldID}][type_taxonomy_options][enabled_taxonomies][]";
			$out .= sprintf("%s<input name='%s' type='checkbox' %s value='%s'> %s", 
								($loopnum>0 ? "<br />" : ""), 
								$input_name, 
								((in_array($one_tax->name, $field_type_taxonomy_options["enabled_taxonomies"])) ? " checked='checked' " : ""), 
								$one_tax->name, 
								$one_tax->labels->name . " ($one_tax->name)"
							);
			$loopnum++;
		}
		$out .= "</div>";

		// taxonomyterm - select taxonomies, like above
		$out .= "<div class='" . (($field_type=="taxonomyterm") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-taxonomyterm'>";
		$out .= "<div class='simple-fields-field-group-one-field-row'>";
		$out .= sprintf("<label>%s</label>", __('Taxonomy to select terms from', 'simple-fields'));
		$taxonomies = get_taxonomies(NULL, "objects");
		$loopnum = 0;
		foreach ($taxonomies as $one_tax) {
			// skip some built in types
			if (in_array($one_tax->name, array("attachment", "revision", "nav_menu_item"))) {
			    continue;
			}
			$input_name = "field[{$fieldID}][type_taxonomyterm_options][enabled_taxonomy]";
			$out .= sprintf("%s<input name='%s' type='radio' %s value='%s'> %s", 
								($loopnum>0 ? "<br />" : ""), 
								$input_name, 
								($one_tax->name == $field_type_taxonomyterm_options["enabled_taxonomy"]) ? " checked='checked' " : "", 
								$one_tax->name, 
								$one_tax->labels->name . " ($one_tax->name)"
							);
			$loopnum++;
		}
		$out .= "</div>";
		
		$out .= "<div class='simple-fields-field-group-one-field-row'>";
		$out .= "<label>Additional arguments</label>";
		$out .= sprintf("<input type='text' name='%s' value='%s' />", "field[$fieldID][type_taxonomyterm_options][additional_arguments]", @$field_type_taxonomyterm_options["additional_arguments"]);
		$out .= sprintf("<br /><span class='description'>Here you can <a href='http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters'>pass your own parameters</a> to <a href='http://codex.wordpress.org/Function_Reference/get_terms#Parameters'>get_terms()</a>.</span>");
		$out .= "</div>";
		
		$out .= "</div>";

		// radiobuttons
		$radio_buttons_added = "";
		$radio_buttons_highest_id = 0;
		if ($field_type_radiobuttons_options) {
			foreach ($field_type_radiobuttons_options as $key => $val) {
				if (strpos($key, "radiobutton_num_") !== false && $val["deleted"] != true) {
					// found one button in format radiobutton_num_0
					$radiobutton_num = str_replace("radiobutton_num_", "", $key);
					if ($radiobutton_num > $radio_buttons_highest_id) {
						$radio_buttons_highest_id = $radiobutton_num;
					}
					$radiobutton_val = esc_html($val["value"]);
					$checked = ($key == $field_type_radiobuttons_options["checked_by_default_num"]) ? " checked='checked' " : "";
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
		$out .= "
			<div class='" . (($field_type=="radiobuttons") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-radiobuttons'>
				<div>Added radio buttons</div>
				<div class='simple-fields-field-type-options-radiobutton-checked-by-default'>".__('Default', 'simple-fields')."</div>
				<ul class='simple-fields-field-type-options-radiobutton-values-added'>
					$radio_buttons_added
				</ul>
				<div><a class='simple-fields-field-type-options-radiobutton-values-add' href='#'>+ ".__('Add radio button', 'simple-fields')."</a></div>
				<input type='hidden' name='' class='simple-fields-field-group-one-field-radiobuttons-highest-id' value='{$radio_buttons_highest_id}' />
			</div>
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
				if (strpos($key, "dropdown_num_") !== false && $val["deleted"] != true) {
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
		$out .= "
			<div class='" . (($field_type=="dropdown") ? "" : " hidden ") . " simple-fields-field-type-options simple-fields-field-type-options-dropdown'>
				<div>".__('Added dropdown values', 'simple-fields')."</div>
				<ul class='simple-fields-field-type-options-dropdown-values-added'>
					$dropdown_values_added
				</ul>
				<div><a class='simple-fields-field-type-options-dropdown-values-add' href='#'>+ ".__('Add dropdown value', 'simple-fields')."</a></div>
				<input type='hidden' name='' class='simple-fields-field-group-one-field-dropdown-highest-id' value='{$dropdown_values_highest_id}' />
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

}

