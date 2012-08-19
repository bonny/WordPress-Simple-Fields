<?php


// little debug version
function sf_d($var) {

	echo "<pre>";
	if (is_array($var) || is_object($var)) {
		print_r($var);
	} else if( is_null($var) ) {
		echo "Var is NULL";
	} else {
		echo $var;
	}
	echo "</pre>";
}

/**
 * Field type: post
 * Fetch content for field type post dialog via AJAX
 * Used for field type post
 * Called from ajax with action wp_ajax_simple_fields_field_type_post_dialog_load
 * Ajax defined in scripts.js -> $("a.simple-fields-metabox-field-post-select")
 */
function simple_fields_field_type_post_dialog_load() {

	global $sf;

	$arr_enabled_post_types = (array) $_POST["arr_enabled_post_types"];
	$additional_arguments = isset($_POST["additional_arguments"]) ? $_POST["additional_arguments"] : "";
	$existing_post_types = get_post_types(NULL, "objects");
	$selected_post_type = (string) @$_POST["selected_post_type"];
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
		
			$output = $sf->get_pages($args);
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

