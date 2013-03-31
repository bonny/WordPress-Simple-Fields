<?php
/**
 * All options page output
 */

global $sf;

$field_groups = $this->get_field_groups();
$post_connectors = $this->get_post_connectors();

// for debug purposes, here we can reset the option
#$field_groups = array(); update_option("simple_fields_groups", $field_groups);
#$post_connectors = array(); update_option("simple_fields_post_connectors", $post_connectors);

// sort them by name
function simple_fields_uasort($a, $b) {
	if ($a["name"] == $b["name"]) { return 0; }
	return strcasecmp($a["name"], $b["name"]);
}

uasort($field_groups, "simple_fields_uasort");
#sf_d($field_groups);
uasort($post_connectors, "simple_fields_uasort");

if ( isset($_GET["action"]) ) {
	?>
	<style>

		.settings_page_simple-fields-options #icon-options-general {
			margin-top: 16px;
		}
		.settings_page_simple-fields-options h2 {
			position: relative;
			top: -10px;
			font-size: 12px;
			font-family: inherit;
		}
		.settings_page_simple-fields-options form > h3 {
			margin-left: 44px;
			line-height: 1;
			margin-top: -30px;
			font: 23px normal "HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",sans-serif;
		}
	</style>
	<?php
}
?>
<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo SIMPLE_FIELDS_NAME ?></h2>

	<div class="clear"></div>
	
	<!-- 
	<div class="simple-fields-bonny-plugins-inner-sidebar">
		<h3>Keep this plugin alive</h3>
		<p>
			I develop this plugin mostly on my spare time. Please consider <a href="http://eskapism.se/sida/donate/">donating</a>
			or <a href="https://flattr.com/thing/116510/Simple-Fields">Flattr</a>
			to keep the development going.
		</p>

		<h3>Support</h3>
		<p>If you have any problems with this plugins please check out the <a href="http://wordpress.org/tags/simple-fields?forum_id=10">support forum</a>.</p>
		<p>You can <a href="https://github.com/bonny/WordPress-Simple-Fields">follow the development of this plugin at GitHub</a>.</p>
								
	</div>
	-->

<div class="simple-fields-settings-wrap">

	<?php
	
	$action = (isset($_GET["action"])) ? $_GET["action"] : null;

	// Print messages at top
	if ( ! empty( $_GET["message"] ) ) {

		$messages = array(
			"field-group-saved" => __('Field group saved', 'simple-fields'),
			"post-connector-saved" => __('Post connector saved', 'simple-fields'),
			"field-group-deleted" => __('Field group deleted', 'simple-fields'),
			"post-connector-deleted" => __('Post connector deleted', 'simple-fields'),
			"post-type-defaults-saved" => __('Post type defaults saved', 'simple-fields'),
			"debug-options-saved" => __('Debug options saved', 'simple-fields')
		);
		
		if ( array_key_exists($_GET["message"], $messages) ) {
			?><div id="message" class="updated"><p><?php echo $messages[$_GET["message"]] ?></p></div><?php
		}

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
			<form action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-type-defaults-save" method="post">
				
				<h3><?php echo __( sprintf('Edit default post connector for post type %1$s', $selected_post_type->label), "simple-fields" ) ?></h3>
				
				<table class="form-table">
					<tr>
						<th><?php _e('Default post connector', 'simple-fields') ?></th>
						<td>
							<?php
							$arr_post_connectors = $this->get_post_connectors_for_post_type($post_type);
							if ($arr_post_connectors) {
								$selected_post_type_default = $this->get_default_connector_for_post_type($post_type);
								?>
								<select name="simple_fields_save-post_type_connector">
									<option <?php echo ($selected_post_type_default==="__none__") ? " selected='selected' " : "" ?> value="__none__"><?php _e('No post connector', 'simple-fields') ?></option>
									<option <?php echo ($selected_post_type_default==="__inherit__") ? " selected='selected' " : "" ?> value="__inherit__"><?php _e('Inherit from parent post', 'simple-fields') ?></option>
									<?php
									foreach ($arr_post_connectors as $one_post_connector) {
										echo "<option " . ((intval($selected_post_type_default)==intval($one_post_connector["id"])) ? " selected='selected' " : "") . "value='{$one_post_connector["id"]}'>" . $one_post_connector["name"] . "</option>";
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
					<input class="button-primary" type="submit" value="<?php _e("Save changes", "simple-fields") ?>" />
					<input type="hidden" name="simple_fields_save-post_type" value="<?php echo $post_type ?>" />
					<?php wp_nonce_field( "save-default-post-connector", "simple-fields" ) ?>
					<?php _e('or', 'simple_fields');  ?>
					<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
				</p>
			</form>
			<?php
			
		}
	}


	/**
	 * edit new or existing post connector
	 * If new then connector-id = 0
	 */
	if ("edit-post-connector" == $action) {

		$connector_id = (isset($_GET["connector-id"])) ? intval($_GET["connector-id"]) : false;
		$highest_connector_id = 0;

		// if new, save it as unnamed, and then set to edit that
		if ($connector_id === 0) {

			// is new connector
			$post_connector_in_edit = simple_fields_register_post_connector();

		} else {

			// existing post connector
			
			// set a default value for hide_editor if it does not exist. did not exist until 0.5
			$post_connectors[$connector_id]["hide_editor"] = (bool) @$post_connectors[$connector_id]["hide_editor"];
			
			$post_connector_in_edit = $post_connectors[$connector_id];
		}

		?>
		<form method="post" action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-connector-save">
		
			<h3><?php _e('Post Connector details', 'simple-fields') ?></h3>

			<table class="form-table">

				<tr>
					<th><label><?php _e('Name', 'simple-fields') ?></label></th>
					<td><input type="text" id="post_connector_name" name="post_connector_name" class="regular-text" value="<?php echo esc_html($post_connector_in_edit["name"]) ?>" /></td>
				</tr>

				<tr>
					<th>
						<label for="post_connector_slug"><?php _e('Slug', 'simple-fields') ?></label>
					</th>
					<td>
						<input 	type="text" name="post_connector_slug" id="post_connector_slug" class="regular-text" 
								value="<?php echo esc_html(@$post_connector_in_edit["slug"]) ?>"
								pattern='<?php echo $this->get_slug_pattern() ?>'
								title='<?php echo $this->get_slug_title() ?>'
								required
								 />
						 <br>
						 <span class="description"><?php echo __("A unique identifier for this connector", 'simple-fields') ?></span>
						 <?php
						 // sf_d($post_connector_in_edit);
						 ?>
					</td>
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
						<?php
						global $wp_post_types;
						$arr_post_types_to_ignore = array("revision", "nav_menu_item");
						foreach ($wp_post_types as $one_post_type) {
							if (!in_array($one_post_type->name, $arr_post_types_to_ignore)) {
								?>
								<p>
									<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types"]) ? " checked='checked' " : ""); ?> type="checkbox" name="post_types[]" value="<?php echo $one_post_type->name ?>" />
									<?php echo $one_post_type->name ?>
								<?php
								/*
								<!-- <td>
									<input <?php echo (in_array($one_post_type->name, $post_connector_in_edit["post_types_type_default"]) ? " checked='checked' " : "") ?> type="checkbox" name="post_types_type_default[]" value="<?php echo $one_post_type->name ?>" />
									Default connector for post type <?php echo $one_post_type->name ?>
								</td> -->
								*/
								?>
								</p>
								<?php
							}
						}
						?>
					</td>
				</tr>

			</table>
			<p class="submit">
				<input class="button-primary" type="submit" value="<?php _e('Save Changes', 'simple-fields') ?>" />
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="post_connector_id" value="<?php echo $post_connector_in_edit["id"] ?>" />
				<?php wp_nonce_field( "save-post-connector", "simple-fields" ) ?>
				or 
				<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
			</p>
			<p class="simple-fields-post-connector-delete">
				<?php
				$action_url = add_query_arg(array("action" => "delete-post-connector", "connector-id" => $post_connector_in_edit["id"]), SIMPLE_FIELDS_FILE);
				$action_url = wp_nonce_url( $action_url, "delete-post-connector");
				?>
				<a href="<?php echo $action_url ?>"><?php _e('Delete') ?></a>
			</p>

		</form>
		<?php
	}


	/**
	 * Edit new or existing Field Group
	 */
	if ("edit-field-group" == $action) {
		
		$field_group_id = (isset($_GET["group-id"])) ? intval($_GET["group-id"]) : false;
		
		$highest_field_id = 0;
		$is_new_field_group = false;

		// check if field group is new or existing
		if ($field_group_id === 0) {

			// new: save it as unnamed, and then set to edit that
			$is_new_field_group = true;
			$field_group_in_edit = simple_fields_register_field_group("", array("deleted" => true));
			$field_group_in_edit["name"] = "";
			$field_group_in_edit["slug"] = "";

			simple_fields::debug("Added new field group", $field_group_in_edit);

		} else {

			// check that field group exists
			if ( ! isset($field_groups[$field_group_id]) ) {
				wp_die( __("Could not find field group. Perhaps it has been deleted?", "simple-fields") );
			}
			$field_group_in_edit = $field_groups[$field_group_id];

			// existing: get highest field id
			foreach ($field_groups[$field_group_id]["fields"] as $one_field) {
				if ($one_field["id"] > $highest_field_id) {
					$highest_field_id = $one_field["id"];
				}
			}

		}

		?>
		<form method="post" action="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-field-group-save">
			
			<h3><?php _e('Field group details', 'simple-fields') ?></h3>
			
			<table class="form-table">
				<tr>
					<th>
						<label for="field_group_name"><?php _e('Name', 'simple-fields') ?></label>
					</th>
					<td>
						<input 
							type="text" 
							name="field_group_name" 
							id="field_group_name" 
							class="regular-text" 
							value="<?php echo esc_html($field_group_in_edit["name"]) ?>" required
							placeholder="<?php _e("Enter a name for this field group", "simple-fields"); ?>"
							<?php if ($is_new_field_group) { ?>
								autofocus
							<?php } ?>
							/>
					</td>
				</tr>
				
				<tr>
					<th>
						<label for="field_group_slug"><?php _e('Slug', 'simple-fields') ?></label>
					</th>
					<td>
						<input 	type="text" name="field_group_slug" id="field_group_slug" class="regular-text" 
								value="<?php echo esc_html(@$field_group_in_edit["slug"]) ?>"
								pattern='<?php echo $this->get_slug_pattern() ?>'
								title='<?php echo $this->get_slug_title() ?>'
								required
								title="<?php _e("Allowed chars: a-z and underscore.", 'simple-fields') ?>"
								 />
						 <br>
						 <span class="description"><?php echo __("A unique identifier for this field group.", 'simple-fields') ?></span>
					</td>
				</tr>

				<tr>
					<th>
						<label for="field_group_description"><?php _e('Description', 'simple-fields') ?></label>
					</th>
					<td>
						<input 	type="text" name="field_group_description" id="field_group_description" class="regular-text" 
								value="<?php echo esc_html(@$field_group_in_edit["description"]) ?>"
							/>
					</td>
				</th>

				<tr>
					<th>
						<?php echo __("Options", 'simple-fields') ?>
					</th>
					<td>
						
						<p>
							<label for="field_group_repeatable">
								<input type="checkbox" <?php echo ($field_group_in_edit["repeatable"] == true) ? "checked='checked'" : ""; ?> value="1" id="field_group_repeatable" name="field_group_repeatable" />
								<?php _e('Repeatable', 'simple-fields') ?>
								<br>
								<span class="description"><?php echo __("With repeatable checked you can add the fields below to a post multiple times. Great for image slideshow, attachments, and similar.", 'simple-fields') ?></span>
							</label>
						</p>

						<p>
							<label for="field_group_gui_view">
								<input type="checkbox" <?php echo ( isset( $field_group_in_edit["gui_view"] ) && $field_group_in_edit["gui_view"] === "table") ? "checked='checked'" : ""; ?> value="1" id="field_group_gui_view" name="field_group_gui_view" />
								<?php _e('Use table view', 'simple-fields') ?>
								<br>
								<span class="description"><?php echo __("List fields in a more compact and overviewable table view. Works best when you use just a few fields.", 'simple-fields') ?></span>
							</label>
						</p>

					</td>
				</tr>
				<tr>
					<th><h3><?php _e('Fields', 'simple-fields') ?></h3></th>
					<td>
						<div id="simple-fields-field-group-existing-fields">
							<ul class='simple-fields-edit-field-groups-added-fields'>
								<?php
								foreach ($field_group_in_edit["fields"] as $oneField) {
									if (!$oneField["deleted"]) {
										echo $this->field_group_add_field_template($oneField["id"], $field_group_in_edit);
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
				<?php wp_nonce_field( "save-field-group", "simple-fields" ) ?>
				<?php _e('or', 'simple-fields') ?> 
				<a href="<?php echo SIMPLE_FIELDS_FILE ?>"><?php _e('cancel', 'simple-fields') ?></a>
			</p>
			<p class="simple-fields-field-group-delete">
				<?php
				$action_url = add_query_arg(array("action" => "delete-field-group", "group-id" => $field_group_in_edit["id"]), SIMPLE_FIELDS_FILE);
				$action_url = wp_nonce_url( $action_url, "delete-field-group");
				?>
				<a href="<?php echo $action_url ?>"><?php _e('Delete', 'simple-fields') ?></a>
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
		echo "<p>Called with function <code>get_post_connectors()</code>";
		sf_d( $this->get_post_connectors() );

		echo "<hr>";
		
		echo "<h3>Field Groups</h3>\n";
		echo "<p>Called with function <code>get_field_groups()</code>";
		sf_d( $this->get_field_groups() );
		
		echo "<hr>";
		echo "<h3>simple_fields_post_type_defaults</h3>";
		echo '<p>Called with: get_option("simple_fields_post_type_defaults")';
		sf_d( $this->get_post_type_defaults() );
		
	}

	// overview, if no action
	if ( ! $action ) {


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
		 * view list of existing field groups
		 */	
		?>
		<div class="simple-fields-edit-field-groups">
			
			<?php

			printf('
				<h3>
					%1$s
					<a href="%2$s" class="add-new-h2">%3$s</a>
				</h3>
				',
				__('Field groups', 'simple-fields'),
				SIMPLE_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=0",
				__('Add new')
			);
			
			// Count num of non deleted field groups		
			$field_group_count = 0;
			foreach ($field_groups as $oneFieldGroup) {
				if (!$oneFieldGroup["deleted"]) {
					$field_group_count++;
				}
			}

			if ($field_groups == $field_group_count) {
			
				echo "<p>".__('No field groups yet.', 'simple-fields')."</p>";

			} else {

				?>
				<table class="wp-list-table widefat fixed">
					
					<thead>
						<tr>
							<th>Name</th>
							<th>Slug</th>
							<th>Fields</th>
							<th>Added fields</th>
						</tr>
					</thead>

					<tbody>
						<?php
						$loopnum = 0;
						foreach ($field_groups as $oneFieldGroup) {
	
							if ($oneFieldGroup["id"] && !$oneFieldGroup["deleted"]) {
				
								$row_class = $loopnum % 2 == 0 ? "alternate" : "";

								$editlink = SIMPLE_FIELDS_FILE . "&amp;action=edit-field-group&amp;group-id=$oneFieldGroup[id]";
								$remove_url = add_query_arg(array("action" => "delete-field-group", "group-id" => $oneFieldGroup["id"]), SIMPLE_FIELDS_FILE);
								$remove_url = wp_nonce_url( $remove_url, "delete-field-group");

								echo "<tr class='$row_class'>";

								echo "<td><a href='$editlink'><strong>$oneFieldGroup[name]</strong></a>";
								?><div class="row-actions">
									<span class="edit"><a href="<?php echo $editlink ?>" title="<?php _e("Edit this item") ?>"><?php _e("Edit") ?></a></span>
									<!-- <span class="trash"><a class="submitdelete" href="<?php echo $remove_url ?>"><?php _e("Trash") ?></a></span> -->
								</div><?php
								echo "</td>";

								printf('<td>%1$s</td>', $oneFieldGroup["slug"]);

								echo "<td>";
								if ($oneFieldGroup["fields_count"]) {
									$format = $oneFieldGroup["repeatable"] ? _n('1 repeatable', '%d repeatable', $oneFieldGroup["fields_count"]) : _n('1', '%d', $oneFieldGroup["fields_count"]);
									echo __( sprintf($format, $oneFieldGroup["fields_count"]) );
								}
								echo "</td>";

								// Fields in this field group
								echo "<td>";
								$fields_out = "";
								foreach ( $oneFieldGroup["fields"] as $one_field ) {
									if ($one_field["deleted"]) continue;
									$fields_out .= sprintf(
										'%1$s (%2$s), ',
										esc_html($one_field["name"]),
										esc_html($one_field["type"])
									);
								}
								$fields_out = preg_replace('/, $/', '', $fields_out);
								echo $fields_out;
								echo "</td>";

								echo "</tr>";

								$loopnum++;
	
							}
						}
						?>
					</tbody>
				</table>
				<?php

			}

			?>
		</div>
	
	
		<div class="simple-fields-edit-post-connectors">

			<h3>
				<?php _e('Post Connectors', 'simple-fields') ?>
				<a class="add-new-h2" href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-connector&amp;connector-id=0"><?php _e('Add new') ?></a>
			</h3>

			<?php

			if ($post_connector_count) {
				?>				
				<table class="wp-list-table widefat fixed">
					
					<thead>
						<tr>
							<th>Name</th>
							<th>Slug</th>
							<th>Field groups</th>
							<th>Added field groups</th>
						</tr>
					</thead>

					<tbody>
					<?php

					$loopnum = 0;
					foreach ($post_connectors as $one_post_connector) {
						
						if ($one_post_connector["deleted"] || !$one_post_connector["id"]) {
							continue;
						}
						
						$row_class = $loopnum % 2 == 0 ? "alternate" : "";
						$edit_url = SIMPLE_FIELDS_FILE . "&amp;action=edit-post-connector&amp;connector-id=" . $one_post_connector["id"];
						?>
						<tr class='<?php echo $row_class ?>'>
							<td>
								<a href="<?php echo $edit_url ?>"><strong><?php echo esc_html( $one_post_connector["name"] ) ?></strong></a>
								<div class="row-actions">
									<span class="edit"><a href="<?php echo $edit_url ?>" title="<?php _e("Edit this item") ?>"><?php _e("Edit") ?></a></span>
									<!-- <span class="trash"><a class="submitdelete" href="<?php echo $remove_url ?>"><?php _e("Trash") ?></a></span> -->
								</div>
							</td>
							<td>
								<?php echo $one_post_connector["slug"] ?>
							</td>
							<td>
								<?php
								if ($one_post_connector["field_groups_count"]) {
									printf( _n('1', '%d', $one_post_connector["field_groups_count"]), $one_post_connector["field_groups_count"] );
								}
								?>
							</td>
							<td>
								<?php
								$field_groups_output = "";
								foreach ( $one_post_connector["field_groups"] as $one_field_group ) {
									if ( $one_field_group["deleted"] ) continue;
									$field_groups_output .= sprintf( '%1$s, ', esc_attr( $one_field_group["name"] ) );
								}
								$field_groups_output = preg_replace('/, $/', '', $field_groups_output);
								echo $field_groups_output;
								?>
							</td>
						</tr>
						<?php

						$loopnum++;
						
					}

					?>
					</tbody>

				</table>
				<?php
				
			} else {
				?>
				<!-- <p>No post connectors</p> -->
				<?php
			}
			?>

		</div>

		<div class="simple-fields-post-type-defaults">
			
			<h3><?php _e('Post type defaults', 'simple-fields') ?></h3>
			
			<table class="wp-list-table widefat fixed">
					
				<thead>
					<tr>
						<th>Post type</th>
						<th>Default connector</th>
						<th></th><!-- two empty to make table widths same as the other tables -->
						<th></th>
					</tr>
				</thead>

				<tbody>

					<?php
					$post_types = get_post_types();
					$arr_post_types_to_ignore = array("revision", "nav_menu_item");
					foreach ($post_types as $one_post_type) {
						
						$one_post_type_info = get_post_type_object($one_post_type);
						
						if (!in_array($one_post_type, $arr_post_types_to_ignore)) {

							$default_connector = $this->get_default_connector_for_post_type($one_post_type);
							switch ($default_connector) {
								case "__none__":
									$default_connector_str = __('Default is to use <em>no connector</em>', 'simple-fields');
									break;
								case "__inherit__":
									$default_connector_str = __('Default is to inherit from <em>parent connector</em>', 'simple-fields');
									break;
								default:
									if (is_numeric($default_connector)) {
										
										$connector = $this->get_connector_by_id($default_connector);
										if ($connector !== FALSE) {
											$default_connector_str = sprintf(__('Default is to use connector <em>%s</em>', 'simple-fields'), $connector["name"]);
										}
									}

							}

							?>
							<tr>
								<td>
									<a href="<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=edit-post-type-defaults&amp;post-type=<?php echo $one_post_type ?>">
										<?php echo $one_post_type_info->label ?>
									</a>
								</td>
								<td>
									<span><?php echo $default_connector_str ?></span>
								</td>
								<td></td>
								<td></td>
							</tr>
							<?php
					
						} // if in array ignore types

					} // foreach post type
					?>
				</tbody>

			</table>

		</div>	


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
					<input class="button-primary" type=submit value="<?php _e("Save changes", "simple-fields") ?>">
				</p>

				<?php wp_nonce_field( "save-debug-options" ) ?>

			</form><!-- // enable debug -->
		
			<ul>
				<li><a href='<?php echo SIMPLE_FIELDS_FILE ?>&amp;action=simple-fields-view-debug-info'><?php echo __('View debug information', 'simple-fields') ?></a></li>
			</ul>

		</div>
		
		<?php

	} // end simple_fields_options

	?>
	</div>
</div>	

<?php

