
jscolor.bindClass = "simple-fields-field-type-color";
var simple_fields_datepicker_args = { "clickInput": true };
var simple_fields_tinymce_iframes = new Array;

(function($) {

	// add new field to the field group
	function simple_fields_field_group_add_field() {
		simple_fields_highest_field_id++;
		var data = {
			action: 'simple_fields_field_group_add_field',
			simple_fields_highest_field_id: simple_fields_highest_field_id
		};
		$.post(ajaxurl, data, function(response) {
			var ul = $("#simple-fields-field-group-existing-fields ul:first");
			$response = $(response);
			ul.append($response);
			ul.find(".simple-fields-field-group-one-field:last").effect("highlight").find(".simple-fields-field-group-one-field-name").focus();
			//$response.effect("highlight").find(".simple-fields-field-group-one-field-name").focus();
		});
	}
	
	function simple_fields_metabox_tinymce_attach() {
		if (typeof( tinyMCE ) == "object" && typeof( tinyMCEPreInit ) == "object" ) {
			var tiny_init = {};
			var qt_init = {};
			var elms_to_convert = jQuery("textarea.simple-fields-metabox-field-textarea-tinymce");
			var dom = tinymce.DOM;
			var id, qt, visual_tab, html_tab, is_new, new_ed, new_qt, wrap_id, qttb, txtarea_el, qtname, qtbuttons;
			for (var i=0; i<elms_to_convert.length; i++) {
				id = elms_to_convert[i].id;
				is_new = (id + '').indexOf('new', 0);
				is_new = is_new === -1 ? false : true;
				if (is_new) {
					wrap_id = 'wp-'+id+'-wrap';
					iframe_id = id+"_ifr";
					iframe_el = jQuery("#"+iframe_id);
					txtarea_el = dom.get(id);
					qtname = 'qt_'+id;
					qttb = 'qt_'+id+'_toolbar';
					if ( typeof(QTags) != undefined && iframe_el.canvas != undefined ) {
						QTags.closeAllTags(iframe_el.id);
					}
					if (!tinyMCEPreInit.qtInit[id]) {
						qt_init = tinyMCEPreInit.qtInit[id] = jQuery.extend({}, tinyMCEPreInit.qtInit['content']);
						qt_init.id = id;
						qt_init.buttons = qt_init.buttons.replace(",fullscreen", "");
						try { new_qt = new QTags( tinyMCEPreInit.qtInit[id] ); } catch(e){}
						QTags._buttonsInit();
					}
					
					if (!tinyMCEPreInit.mceInit[id]) {
						tiny_init = tinyMCEPreInit.mceInit[id] = jQuery.extend({}, tinyMCEPreInit.mceInit['content']);
					} else {
						tiny_init = tinyMCEPreInit.mceInit[id];
					}
					tiny_init.mode = 'exact';
					tiny_init.elements = id;
					tiny_init.theme_advanced_resizing = true;
					new_ed = new tinymce.Editor(id, tiny_init);
					new_ed.render();
					
					visual_tab = jQuery("#"+id+"-tmce");
					visual_tab.removeAttr('onclick').click(function() {
						var id = this.id.substr(0, (this.id.length-5));
						var wrap_id = 'wp-'+id+'-wrap';
						var qttb = 'qt_'+id+'_toolbar';
						var dom = tinyMCE.DOM;
						var ed = tinyMCE.get(id);
						ed.show();
						dom.hide(qttb);
						dom.addClass(wrap_id, 'tmce-active');
						dom.removeClass(wrap_id, 'html-active');
					});
					
					html_tab = jQuery("#"+id+"-html");
					html_tab.removeAttr('onclick').click(function() {
						var id = this.id.substr(0, (this.id.length-5));
						var wrap_id = 'wp-'+id+'-wrap';
						var qttb = 'qt_'+id+'_toolbar';
						var dom = tinyMCE.DOM;
						var ed = tinyMCE.get(id);
						ed.hide();
						dom.show(qttb);
						dom.addClass(wrap_id, 'html-active');
						dom.removeClass(wrap_id, 'tmce-active');
					});
				dom.hide(qttb);
				dom.addClass(wrap_id, 'tmce-active');
				dom.removeClass(wrap_id, 'html-active');	
				}
			}
		}
		return false;
	}
	
	function simple_fields_buffer_iframes() {
		var id, textareas = jQuery("textarea.simple-fields-metabox-field-textarea-tinymce");
		for (var i=0; i<textareas.length; i++) {
			id = textareas[i].id;
			simple_fields_tinymce_iframes[id] = jQuery("#"+id+"_ifr").contents().find('html').html();
		}
		return false;
	}
	
	function simple_fields_reset_iframes() {
		var id, textareas = jQuery("textarea.simple-fields-metabox-field-textarea-tinymce");
		for (var i=0; i<textareas.length; i++) {
			id = textareas[i].id;
			jQuery("#"+id+"_ifr").contents().find('html').html(simple_fields_tinymce_iframes[id]);
		}
		return false;
	}
	
	function simple_fields_get_fieldID_from_this(t) {
		var $t = $(t);
		return $t.closest(".simple-fields-field-group-one-field").find(".simple-fields-field-group-one-field-id").val();
	}

	/* radiobuttons */
	function simple_fields_field_type_options_radiobutton_values_add(fieldID, fieldRadiobuttonID) {
		var $html = $("<li>\n<div class='simple-fields-field-type-options-radiobutton-handle'></div>\n<input class='regular-text' name='field["+fieldID+"][type_radiobuttons_options][radiobutton_num_"+fieldRadiobuttonID+"][value]' type='text' />\n<input class='simple-fields-field-type-options-radiobutton-deleted' name='field["+fieldID+"][type_radiobuttons_options][radiobutton_num_"+fieldRadiobuttonID+"][deleted]' type='hidden' value='0' />\n<input class='simple-fields-field-type-options-radiobutton-checked-by-default-values' type='radio' name='field["+fieldID+"][type_radiobuttons_options][checked_by_default_num]' value='radiobutton_num_"+fieldRadiobuttonID+"' />\n <a class='simple-fields-field-type-options-radiobutton-delete' href='#' style='display: none;'>"+sfstrings.txtDelete+"</a> </li>");
		var $fieldLI = $(".simple-fields-field-group-one-field-id-"+fieldID);
		$fieldLI.find(".simple-fields-field-type-options-radiobutton-values-added").append($html);
		$html.effect("highlight");
		$html.find("input:first").focus();
		$fieldLI.find(".simple-fields-field-type-options-radiobutton-values-added").sortable({
			axis: 'y',
			containment: "parent",
			handle: ".simple-fields-field-type-options-radiobutton-handle"
		});
	}


	function simple_fields_field_type_options_dropdown_values_add(fieldID, fieldDropdownID) {
		var $html = $("<li>\n<div class='simple-fields-field-type-options-dropdown-handle'></div>\n<input class='regular-text' name='field["+fieldID+"][type_dropdown_options][dropdown_num_"+fieldDropdownID+"][value]' type='text' />\n<input class='simple-fields-field-type-options-dropdown-deleted' name='field["+fieldID+"][type_dropdown_options][dropdown_num_"+fieldDropdownID+"][deleted]' type='hidden' value='0' />\n <a class='simple-fields-field-type-options-dropdown-delete' href='#' style='display: none;'>"+sfstrings.txtDelete+"</a> </li>");
		var $fieldLI = $(".simple-fields-field-group-one-field-id-"+fieldID);
		$fieldLI.find(".simple-fields-field-type-options-dropdown-values-added").append($html);
		$html.find("input:first").focus();
		$html.effect("highlight");
		$("ul.simple-fields-field-type-options-dropdown-values-added").sortable({
			axis: 'y',
			containment: "parent",
			handle: ".simple-fields-field-type-options-dropdown-handle"
		});
	}


	$("select.simple-fields-field-type").live("change", function() {
		// look for simple-fields-field-type-options-<type> and show if
		var $t = $(this);
		var selectedFieldType = $t.val();
		var $li = $t.closest("li");
		$li.find(".simple-fields-field-type-options").hide("slow");
		$li.find(".simple-fields-field-type-options-" + selectedFieldType).show("slow");
	});
	
	$("li.simple-fields-field-group-one-field").live("mouseenter", function() {
		$(this).find("div.delete").show();
	});
	$("li.simple-fields-field-group-one-field").live("mouseleave", function() {
		$(this).find("div.delete").hide();
	});

	$("li.simple-fields-field-group-one-field div.delete a").live("click", function(){
		if (confirm(sfstrings.confirmDelete)) {
			$(this).closest("li").find(".hidden_deleted").attr("value", 1);
			$(this).closest("li").hide("slow");
		} else {
		}
		return false;
	});

	$(".simple-fields-field-group-delete a").live("click", function() {
		if (confirm(sfstrings.confirmDeleteGroup)) {
			return true;
		} else {
		}
		return false;
	});
	
	$(".simple-fields-post-connector-delete a").live("click", function() {
		if (confirm(sfstrings.confirmDeleteConnector)) {
			return true;
		} else {
		}
		return false;
	});

	$("a.simple-fields-field-type-options-radiobutton-values-add").live("click", function() {
		// finds the highest existing button id
		var $fieldRadiobuttonHighestID = $(this).closest(".simple-fields-field-group-one-field").find(".simple-fields-field-group-one-field-radiobuttons-highest-id");
		var fieldRadiobuttonHighestID = $fieldRadiobuttonHighestID.val();
		fieldRadiobuttonHighestID++;
		// add it
		simple_fields_field_type_options_radiobutton_values_add(simple_fields_get_fieldID_from_this(this), fieldRadiobuttonHighestID);
		$fieldRadiobuttonHighestID.val(fieldRadiobuttonHighestID);
		return false;
	});
	$("ul.simple-fields-field-type-options-radiobutton-values-added li").live("mouseenter", function() {
		$(this).find(".simple-fields-field-type-options-radiobutton-delete").show();
	});
	$("ul.simple-fields-field-type-options-radiobutton-values-added li").live("mouseleave", function() {
		$(this).find(".simple-fields-field-type-options-radiobutton-delete").hide();
	});
	$(".simple-fields-field-type-options-radiobutton-delete").live("click", function() {
		if (confirm(sfstrings.confirmDeleteRadio)) {
			$(this).closest("li").hide("slow").find(".simple-fields-field-type-options-radiobutton-deleted").val("1");
		}
		return false;
	});

	$("a.simple-fields-field-type-options-dropdown-values-add").live("click", function() {
		// finds the highest existing button id
		var $fieldDropdownHighestID = $(this).closest(".simple-fields-field-group-one-field").find(".simple-fields-field-group-one-field-dropdown-highest-id");
		var fieldDropdownHighestID = $fieldDropdownHighestID.val();
		fieldDropdownHighestID++;
		// add it
		simple_fields_field_type_options_dropdown_values_add(simple_fields_get_fieldID_from_this(this), fieldDropdownHighestID);
		$fieldDropdownHighestID.val(fieldDropdownHighestID);
		return false;
	});
	$("ul.simple-fields-field-type-options-dropdown-values-added li").live("mouseenter", function() {
		$(this).find(".simple-fields-field-type-options-dropdown-delete").show();
	});
	$("ul.simple-fields-field-type-options-dropdown-values-added li").live("mouseleave", function() {
		$(this).find(".simple-fields-field-type-options-dropdown-delete").hide();
	});
	$(".simple-fields-field-type-options-dropdown-delete").live("click", function() {
		if (confirm(sfstrings.confirmDeleteDropdown)) {
			$(this).closest("li").hide("slow").find(".simple-fields-field-type-options-dropdown-deleted").val("1");
		}
		return false;
	});
	
	// get a field group from the server and add it to the page (aka "the add button")
	// what we need:
	// - field group id
	// - post id
	// - num in (new) set
	var simple_fields_new_fields_count = 0;
	$(".simple-fields-metabox-field-add").live("click", function() {

		var $t = $(this);
		//var $a = $(this).find("a");
		$t.text(sfstrings.adding);
		var $wrapper = $(this).parents(".simple-fields-meta-box-field-group-wrapper");
		var field_group_id = $wrapper.find("input[name=simple-fields-meta-box-field-group-id]").val();
		var post_id = jQuery("#post_ID").val();

		var data = {
			"action": 'simple_fields_metabox_fieldgroup_add',
			"simple_fields_new_fields_count": simple_fields_new_fields_count,
			"field_group_id": field_group_id,
			"post_id": post_id
		};
	
		$.post(ajaxurl, data, function(response) {
			// alert('Got this from the server: ' + response);
			$ul = $wrapper.find("ul.simple-fields-metabox-field-group-fields");
			$response = $(response);
			$response.hide();
			$ul.prepend($response);
			$response.slideDown("slow", function() {
				simple_fields_metabox_tinymce_attach();
				$response.effect("highlight", 1000);
				// add jscolor to possibly new fields
				jscolor.init();
				// add datepicker too
				$('input.simple-fields-field-type-date', $ul).datePicker(simple_fields_datepicker_args);
			});
			$t.html("<a href='#'>+ "+sfstrings.add+"</a>");

		});
		
		simple_fields_new_fields_count++;

		return false;
	});

	$(".simple-fields-post-connector-addded-fields-delete").live("click", function() {
		if (confirm(sfstrings.confirmRemoveGroupConnector)) {
			$(this).closest("li").hide("slow").find(".simple-fields-post-connector-added-field-deleted").val("1");
		}
		return false;
	});

	$("ul.simple-fields-metabox-field-group-fields-repeatable li").live("hover", function(e) {
		if (e.type == "mouseover") {
			$(this).addClass("hover");
		} else if (e.type == "mouseout") {
			$(this).removeClass("hover");
		}
	});
	// on click on any input in a repeatable field group: highlight whole group
	$("ul.simple-fields-metabox-field-group-fields-repeatable li input").live("focus", function() {
		$(this).closest("li").addClass("active");
	}).live("blur", function() {
		$(this).closest("li").removeClass("active");
	});
	
	$(".simple-fields-metabox-field-group").live("mouseenter", function() {
		$(this).find(".simple-fields-metabox-field-group-delete").show();
	});
	$(".simple-fields-metabox-field-group").live("mouseleave", function() {
		$(this).find(".simple-fields-metabox-field-group-delete").hide();
	});
	$(".simple-fields-metabox-field-group-delete").live("click", function() {
		if (confirm(sfstrings.confirmRemoveGroup)) {
			var li = $(this).closest("li");
			li.hide("slow", function() { li.remove(); });
		}
		return false;
	});
	
	// click on select file for a field
	$(".simple-fields-metabox-field-file-select").live("click", function() {
		var input = $(this).closest(".simple-fields-metabox-field").find(".simple-fields-metabox-field-file-fileID");
		simple_fields_metabox_field_file_select_input_selectedID = input;
	});
	
	// select a file in the file browser (that is in a popup)
	$(".simple-fields-file-browser-file-select").live("click", function() {

		sfmfli.find(".simple-fields-metabox-field-file-edit").show();
		sfmf.find(".simple-fields-metabox-field-file-clear").show();

		var file_id = $(this).closest("li").find("input[name='simple-fields-file-browser-list-file-id']").val();
		var file_thumb = $(this).closest("li").find(".thumbnail img").attr("src");
		var file_name = $(this).closest("li").find("h3").text();

		self.parent.simple_fields_metabox_file_select(file_id, file_thumb, file_name);
		self.parent.tb_remove();
	});

	// clear the file
	$(".simple-fields-metabox-field-file-clear").live("click", function() {
		var $li = $(this).closest(".simple-fields-metabox-field-file");
		$li.find(".simple-fields-metabox-field-file-fileID").val("");
		
		$li.find(".simple-fields-metabox-field-file-selected-image").fadeOut();
		$li.find(".simple-fields-metabox-field-file-selected-image-name").fadeOut();
				
		// hide clear and edit
		$li.find(".simple-fields-metabox-field-file-edit").attr("href", "#").fadeOut();
		$li.find(".simple-fields-metabox-field-file-clear").fadeOut();
		
		return false;
	});

	// media buttons
	$(".simple_fields_tiny_media_button").live("click", function(){
		var id = $(this).closest(".simple-fields-metabox-field").find("textarea").attr("id");
		simple_fields_focusTextArea(id);
		simple_fields_thickbox($(this).get(0));
		return false;
	});
	
	// field type post
	// popup a dialog where the user can choose  the post to attach
	$("a.simple-fields-metabox-field-post-select").live("click", function(e) {
		e.preventDefault();
		
		var a = $(this);
		// get post types to show
		var div = a.closest(".simple-fields-metabox-field");
		var enabled_post_types = div.find("input[name='simple-fields-metabox-field-post-enabled-post-types']").val();
		
		$("div.simple-fields-meta-box-field-group-field-type-post-dialog").data("originLink", this).dialog({
			width: 480,
			height: 'auto',
			modal: true,
			dialogClass: 'wp-dialog',
			zIndex: 300000,
			open: function(event, ui) {
				//console.log("event", event);
				//console.log("ui", ui);
				//console.log("originLink", $(this).data("originLink"));
				var originLink = $($(this).data("originLink"));
				//console.log(enabled_post_types);
				//var select_type = $("div.simple-fields-meta-box-field-group-field-type-post-dialog-select-type");
				arr_enabled_post_types = enabled_post_types.split(",");
				$(this).text("Loading...").load(ajaxurl, {
					"action": "simple_fields_field_type_post_dialog_load",
					"arr_enabled_post_types": arr_enabled_post_types
				});
			}
		});

	});
	$(".simple-fields-postdialog-link-cancel").live("click", function(e) {
		e.preventDefault();
		$("div.simple-fields-meta-box-field-group-field-type-post-dialog").dialog("close");
	});
	
	// in dialog: click on post type
	$(".simple-fields-meta-box-field-group-field-type-post-dialog-post-types a").live("click", function(e) {

		e.preventDefault();
		var a = $(this);
		var dialog = $("div.simple-fields-meta-box-field-group-field-type-post-dialog");
		var originLink = dialog.data("originLink");
		originLink = $(originLink);
		var div = originLink.closest(".simple-fields-metabox-field");
		var enabled_post_types = div.find("input[name='simple-fields-metabox-field-post-enabled-post-types']").val();

		dialog.load(ajaxurl, {
			"action": "simple_fields_field_type_post_dialog_load",
			"arr_enabled_post_types": arr_enabled_post_types,
			"selected_post_type": a.attr("href")
		});

	});
	
	// in dialog: click on post = update input in field group
	$(".simple-fields-meta-box-field-group-field-type-post-dialog-post-posts a").live("click", function(e) {
		
		e.preventDefault();
		
		var a = $(this);
		var post_id = a.attr("href").match(/post=([\d]+)/)[1];
		var dialog = $("div.simple-fields-meta-box-field-group-field-type-post-dialog");
		var originLink = dialog.data("originLink");
		originLink = $(originLink);
		
		var div = originLink.closest(".simple-fields-metabox-field");
		div.find(".simple-fields-field-type-post-postID").attr("value", post_id);
		div.find(".simple-fields-field-type-post-postName").text(a.text());
		div.find(".simple-fields-metabox-field-post-clear").show();
		div.find(".simple-fields-field-type-post-postName").show();

		
		dialog.dialog("close");
		
	});
	
	// clear post id and name
	$(".simple-fields-metabox-field-post-clear").live("click", function(e) {
		e.preventDefault();
		var a = $(this);
		var div = a.closest(".simple-fields-metabox-field");
		div.find(".simple-fields-metabox-field-post-clear").hide("fast");
		div.find(".simple-fields-field-type-post-postName").hide("fast");
		div.find(".simple-fields-field-type-post-postID").attr("value", "");
	});
	
	/**
	 * ondomready stuff
	 */
	$(function() {

		$("#simple-fields-field-group-existing-fields ul:first").sortable({
			distance: 10,
			axis: 'y',
			handle: ".simple-fields-field-group-one-field-handle"
		});
		
		// radiobutton
		$(".simple-fields-field-type-options-radiobutton-values-added").sortable({
			axis: 'y',
			containment: "parent",
			handle: ".simple-fields-field-type-options-radiobutton-handle"
		});

		$("#simple-fields-field-group-add-field").click(function() {
			simple_fields_field_group_add_field();
			return false;
		});

		/* dropdown */
		$("ul.simple-fields-field-type-options-dropdown-values-added").sortable({
			axis: 'y',
			containment: "parent",
			handle: ".simple-fields-field-type-options-dropdown-handle"
		});

		/**
		 * post connector
		 */
		$("#simple-fields-post-connector-add-fields").change(function() {
			var selectedVal = $(this).val();
			var selectedValName = $(this).find(":selected").text();
			$(this).val("");
			
			var str_html = "";
			str_html += "<li>";
			
			str_html += "<div class='simple-fields-post-connector-addded-fields-handle'></div>";
			str_html += "<div class='simple-fields-post-connector-addded-fields-field-name'>" + selectedValName + "</div>";
			str_html += "<input type='hidden' name='added_fields["+selectedVal+"][id]' value='"+selectedVal+"' />";
			str_html += "<input type='hidden' name='added_fields["+selectedVal+"][name]' value='"+selectedValName+"' />";
			str_html += "<input type='hidden' name='added_fields["+selectedVal+"][deleted]' value='0' />";

			str_html += "<div class='simple-fields-post-connector-addded-fields-options'>";
			str_html += sfstrings.context;
			str_html += "<select class='simple-fields-post-connector-addded-fields-option-context' name='added_fields["+selectedVal+"][context]'>";
			str_html += "<option value='normal'>"+sfstrings.normal+"</option>";
			str_html += "<option value='advanced'>"+sfstrings.advanced+"</option>";
			str_html += "<option value='side'>"+sfstrings.side+"</option>";
			str_html += "</select>";
			
			str_html += "Priority";
			str_html += "<select class='simple-fields-post-connector-addded-fields-option-priority' name='added_fields["+selectedVal+"][priority]'>";
			str_html += "<option value='low'>"+sfstrings.low+"</option>";
			str_html += "<option value='high'>"+sfstrings.high+"</option>";
			str_html += "</select>";
			str_html += "</div>";

			str_html += "<a href='#' class='simple-fields-post-connector-addded-fields-delete'>"+sfstrings.txtDelete+"</a>";

			str_html += "</li>";
			
			var $html = $(str_html);
			
			$("#simple-fields-post-connector-added-fields").append($html);
			
			$html.effect("highlight");
			
			
		});
		$("#simple-fields-post-connector-added-fields").sortable({
			axis: 'y',
			xcontainment: "parent",
			handle: ".simple-fields-post-connector-addded-fields-handle"
		});
		$("ul#simple-fields-post-connector-added-fields li").hover(function() {
			$(this).find(".simple-fields-post-connector-addded-fields-delete").show();
		}, function() {
			$(this).find(".simple-fields-post-connector-addded-fields-delete").hide();
		});


		/**
		 * edit posts
		 */
		$("#simple-fields-post-edit-side-field-settings-select-connector").change(function() {
			$("#simple-fields-post-edit-side-field-settings-select-connector-please-save").show("fast");
		});
		$("#simple-fields-post-edit-side-field-settings-show-keys").click(function() {
			$(".simple-fields-metabox-field-custom-field-key").toggle();
			return false;
		});

		$("ul.simple-fields-metabox-field-group-fields-repeatable").sortable({
			distance: 10,
			axis: 'y',
			handle: ".simple-fields-metabox-field-group-handle",
			start: function(event, ui) {
				// buffer, or there will be errors
				simple_fields_buffer_iframes();
			},
			stop: function(event, ui) {
				// reset iframes from buffer
				simple_fields_reset_iframes();
			}
		});

		
		// attach TinyMCE to textareas
		simple_fields_metabox_tinymce_attach();
		
		// Media browser: make sure search and filter works by adding hidden inputs
		// would have been best to do this in PHP, but I can't find any filter for it
		if ( pagenow == "media-upload-popup" && window.location.search.match(/simple_fields_dummy=/) ) {

			var frm_filter = $("form#filter");
			
			// http://localhost/wp-admin/media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=simple_fields_fieldgroups_12_1_0&post_id=-1&
			// get these
			// simple_fields_dummy=1
			// simple_fields_action=select_file
			// simple_fields_file_field_unique_id=simple_fields_fieldgroups_12_1_0
			var params = {
				"simple_fields_dummy": 1,
				"simple_fields_action": "select_file"
			}
			
			var match = window.location.search.match(/simple_fields_file_field_unique_id=([\w]+)/);
			params.simple_fields_file_field_unique_id = match[1];
			
			// all params that start with "simple_fields_"
			$.each(params, function(key, val) {
				frm_filter.append("<input type='hidden' name='"+key+"' value='"+val+"' />");
			});

		}
		
		// type date
		$('input.simple-fields-field-type-date').datePicker(simple_fields_datepicker_args);
		
	});


}(jQuery));


// for media selectors
// code from custom field template by Hiroaki Miyashita
var simple_fields_tmpFocus; // will contain the id of the tinymce field we are adding a file to
var simple_fields_isTinyMCE = false;
// when click the thickbox-link, "unset" our vars
jQuery(".thickbox").bind("click", function (e) {
	simple_fields_tmpFocus = undefined;
	simple_fields_isTinyMCE = false;
});
function simple_fields_focusTextArea(id) {
	var elm;
	if ( typeof tinyMCE != "undefined" ) {
		elm = tinyMCE.get(id);
	}
	if (!elm || elm.isHidden()) {
		elm = document.getElementById(id);
		simple_fields_isTinyMCE = false;
	} else {
		simple_fields_isTinyMCE = true;
	}
	simple_fields_tmpFocus = elm;
	elm.focus();
	if (elm.createTextRange) {
		var range = elm.createTextRange();
		range.move("character", elm.value.length);
		range.select();
	} else if (elm.setSelectionRange) {
		elm.setSelectionRange(elm.value.length, elm.value.length);
	}
}
function simple_fields_thickbox(link) {
	var t = link.title || link.name || null;
	var a = link.href || link.alt;
	var g = link.rel || false;
	// alert(t); // title
	// alert(a); // http://localhost/wp-admin/media-upload.php?type=image&post_id=1060&TB_iframe=true
	// alert(g); // false
	tb_show(t,a,g);
	link.blur();
	return false;
}

// global js stuff; sorry about that...
var simple_fields_metabox_field_file_select_input_selectedID = null;
var simple_fields_is_simple_fields_popup = false;

// called when selecting file from tiny-area, if I remember correct
function simple_fields_metabox_file_select(file_id, file_thumb, file_name) {
	simple_fields_metabox_field_file_select_input_selectedID.val(file_id);
	$file_thumb_tag = jQuery("<img src='"+file_thumb+"' alt='' />");
	var sfmf = simple_fields_metabox_field_file_select_input_selectedID.closest(".simple-fields-metabox-field");
	sfmf.find(".simple-fields-metabox-field-file-selected-image").html($file_thumb_tag);
	sfmf.find(".simple-fields-metabox-field-file-selected-image-name").text(file_name);
	sfmf.effect("highlight", 4000);

}
// simple-fields-metabox-field-file

