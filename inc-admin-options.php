<?php
/**
 * All options page output
 */

global $sf;

$field_groups = $this->get_field_groups();
$post_connectors = $this->get_post_connectors();

uasort($field_groups, "simple_fields_uasort");
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
		.settings_page_simple-fields-options form > h3:first-child {
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
	
	<div class="simple-fields-settings-wrap">
	
		<?php
		
		$subpage = isset( $_REQUEST["sf-options-subpage"] ) ? $_REQUEST["sf-options-subpage"] : "manage";
		$action = (isset($_GET["action"])) ? $_GET["action"] : null;

		add_action("simple_fields_options_print_nav_tabs", function($subpage) {
			?>		
			<h3 class="nav-tab-wrapper">
				<a href="<?php echo add_query_arg(array("sf-options-subpage" => "manage"), SIMPLE_FIELDS_FILE) ?>" class="nav-tab <?php echo "manage" === $subpage ? "nav-tab-active" : "" ?>"><?php _e('Manage', 'simple-fields') ?></a>
				<a href="<?php echo add_query_arg(array("sf-options-subpage" => "tools"), SIMPLE_FIELDS_FILE) ?>" class="nav-tab <?php echo "tools" === $subpage ? "nav-tab-active" : "" ?>"><?php _e('Tools', 'simple-fields') ?></a>
			</h3>
			<?php
		});

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

		// Include selected sub page
		if ("manage" === $subpage) {
			require( dirname(__FILE__) . "/inc-admin-options-manage.php" );
		} else if ("tools" === $subpage) {
			require( dirname(__FILE__) . "/inc-admin-options-tools.php" );
		}

	?>
	</div><!-- simple-fields-settings-wrap -->
</div><!-- wrap -->