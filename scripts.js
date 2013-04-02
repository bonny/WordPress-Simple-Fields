

if (typeof jscolor != "undefined") {
	jscolor.bindClass = "simple-fields-field-type-color";
}

// global js stuff; sorry about that...
var simple_fields_metabox_field_file_select_input_selectedID = null,
	simple_fields_is_simple_fields_popup = false,
	simple_fields_datepicker_args = { "clickInput": true },
	simple_fields_tinymce_iframes = [];

// Global module for Simple Fields, using the reveal module pattern
var simple_fields = (function() {

	var
		my = {};

	// Output debug/log
	my.log = function() {
		if ( typeof console !== "undefined" ) {
			console.log.apply( console, arguments );
		}
	};

	my.init = function() {
		my.addListeners();
	};

	my.addListeners = function() {

	};

	return my;

})();

// Class for file field
// Handles showing media popup, selecting and clearing files
var simple_fields_file_field = (function($) {

	var my = {
		media_frame: null,
		selectors : {
			select: ".simple-fields-metabox-field-file-select",
			clear: ".simple-fields-metabox-field-file-clear"
		}
	};

	my.init = function() {
		simple_fields.log("init simple_fields_file_field");
		my.addListeners();
	};

	my.openFileBrowser = function(e) {
		
		e.preventDefault();

		var target = $(e.target),
			container_div = target.closest(".simple-fields-metabox-field-file");

		// Code based on https://github.com/thomasgriffin/New-Media-Image-Uploader/blob/master/js/media.js
		// TODO: how do i get the filter dropdown?? i think i've tried everything!
        my.media_frame = wp.media({
            className: 'media-frame simple-fields-media-frame',
            frame: 'select', // select | post. select removed left nav (insert media, create gallery, set featured image)
            multiple: false,
            title: _wpMediaViewsL10n.mediaLibraryTitle,
            /*library: {
                //type: 'audio' // image | audio
            },*/
            button: {
                text: _wpMediaViewsL10n.insertIntoPost
            }
        });

        my.media_frame.on('select', function(){
            
            var file_json = my.media_frame.state().get('selection').first().toJSON(),
				file_thumb = "";

			if (file_json.type === "image") {
				var thumb_url = "";
				if (file_json.sizes.thumbnail && file_json.sizes.thumbnail.url) {
					thumb_url = file_json.sizes.thumbnail.url;
				} else {
					thumb_url = file_json.sizes.full.url;
				}
				file_thumb = "<img src='" + thumb_url + "' alt='' />";
			} else {
				file_thumb = "<img src='" + file_json.icon + "' alt='' />";
			}
			container_div.find(".simple-fields-metabox-field-file-selected-image").html( file_thumb );

			container_div.find(".simple-fields-metabox-field-file-fileID").val( file_json.id );
			container_div.find(".simple-fields-metabox-field-file-view").attr( "href", file_json.url );
			container_div.find(".simple-fields-metabox-field-file-edit").attr( "href", file_json.editLink );
			container_div.find(".simple-fields-metabox-field-file-selected-image-name").text( file_json.title + " (" + file_json.filename + ")" );
            
            container_div.addClass("simple-fields-metabox-field-file-is-selected");
			container_div.effect("highlight", 4000);          

        });

        my.media_frame.open();

	};

	my.clearSelectedFile = function(e) {

		e.preventDefault();
	
		var target = $(e.target),
			container_div = target.closest(".simple-fields-metabox-field-file");

			container_div.find(".simple-fields-metabox-field-file-fileID").val("");
			container_div.find(".simple-fields-metabox-field-file-selected-image").html("");
			container_div.removeClass("simple-fields-metabox-field-file-is-selected");

	};

	my.addListeners = function() {
		jQuery(document).on("click", my.selectors.select, my.openFileBrowser);
		jQuery(document).on("click", my.selectors.clear, my.clearSelectedFile);
	};

	return my;

})(jQuery);

// Self invoking function for our JS stuff
(function($) {

	// add new field to the field group
	function simple_fields_field_group_add_field() {

		simple_fields_highest_field_id++;

		var data = {
			action: 'simple_fields_field_group_add_field',
			simple_fields_highest_field_id: simple_fields_highest_field_id
		};

		$.post(ajaxurl, data, function(response) {
			var ul = $("#simple-fields-field-group-existing-fields ul:first"),
				$response = $(response);
			ul.append($response);
			ul.find(".simple-fields-field-group-one-field:last").effect("highlight").find(".simple-fields-field-group-one-field-name").focus();
		});

	}

	// Add TinyMCE-editors to textareas of type WYSIWYG
	// Script for this is usually outputted by wp_editor, but it does not exist when calling via ajax
	function simple_fields_metabox_tinymce_attach() {
		//return;
		simple_fields.log("simple_fields_metabox_tinymce_attach()");

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
				if (is_new && typeof(tinyMCE.editors[id]) === 'undefined') {
					wrap_id = 'wp-'+id+'-wrap';
					iframe_id = id+"_ifr";
					iframe_el = jQuery("#"+iframe_id);
					txtarea_el = dom.get(id);
					qtname = 'qt_'+id;
					qttb = 'qt_'+id+'_toolbar';
					if ( typeof(QTags) !== undefined && iframe_el.canvas !== undefined ) {
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

	/**
	 * Edit field types/fields: on field type dropdown change
	 */
	$(document).on("change", "select.simple-fields-field-type", function(e) {
		// look for simple-fields-field-type-options-<type> and show if
		var $t = $(this);
		var selectedFieldType = $t.val();
		var $li = $t.closest("li");
		$li.find(".simple-fields-field-type-options").hide();
		$li.find(".simple-fields-field-type-options-" + selectedFieldType).fadeIn("slow");
	});
	
	// Field group edit, show delete icon for field
	$(document).on("mouseenter mouseleave", "li.simple-fields-field-group-one-field", function(e) {
		var $t = $(this);
		if ("mouseenter" == e.type) {
			$t.find("div.delete").show();
		} else {
			$t.find("div.delete").hide();
		}
	});

	// field group field: click on delete button
	$(document).on("click", "li.simple-fields-field-group-one-field div.delete a", function() {
		if (confirm(sfstrings.confirmDelete)) {
			$(this).closest("li").find(".hidden_deleted").attr("value", 1);
			$(this).closest("li").hide("slow");
			// Remove required attribute on slug so we can post the form even if slug is empty
			$(this).closest("li").find("input[required]").removeAttr("required");
		} else {
		}
		return false;
	});

	// Field group edit, confirm delete field group
	$(document).on("click", ".simple-fields-field-group-delete a", function() {
		if (confirm(sfstrings.confirmDeleteGroup)) return true;
		return false;
	});
	
	// Edit post connector, confirm connector delete
	$(document).on("click", ".simple-fields-post-connector-delete a", function() {
		if (confirm(sfstrings.confirmDeleteConnector)) return true;
		return false;
	});

	// Edit field group, field radiobutton
	$(document).on("click", "a.simple-fields-field-type-options-radiobutton-values-add", function(e) {
		// finds the highest existing button id
		var $fieldRadiobuttonHighestID = $(this).closest(".simple-fields-field-group-one-field").find(".simple-fields-field-group-one-field-radiobuttons-highest-id");
		var fieldRadiobuttonHighestID = $fieldRadiobuttonHighestID.val();
		fieldRadiobuttonHighestID++;
		// add it
		simple_fields_field_type_options_radiobutton_values_add(simple_fields_get_fieldID_from_this(this), fieldRadiobuttonHighestID);
		$fieldRadiobuttonHighestID.val(fieldRadiobuttonHighestID);
		return false;
	});
	// Radiobutton: show delete link
	$(document).on("mouseenter mouseleave", "ul.simple-fields-field-type-options-radiobutton-values-added li", function(e) {
		var $t = $(this);
		if ("mouseenter" == e.type) {
			$t.find(".simple-fields-field-type-options-radiobutton-delete").show();
		} else {
			$t.find(".simple-fields-field-type-options-radiobutton-delete").hide();
		}
	});

	// Radiobutton: click delete
	$(document).on("click", ".simple-fields-field-type-options-radiobutton-delete", function(e) {
		if (confirm(sfstrings.confirmDeleteRadio)) {
			$(this).closest("li").hide("slow").find(".simple-fields-field-type-options-radiobutton-deleted").val("1");
		}
		return false;
	});

	// Dropdown: add value
	$(document).on("click", "a.simple-fields-field-type-options-dropdown-values-add", function(e) {
		// finds the highest existing button id
		var $fieldDropdownHighestID = $(this).closest(".simple-fields-field-group-one-field").find(".simple-fields-field-group-one-field-dropdown-highest-id");
		var fieldDropdownHighestID = $fieldDropdownHighestID.val();
		fieldDropdownHighestID++;
		// add it
		simple_fields_field_type_options_dropdown_values_add(simple_fields_get_fieldID_from_this(this), fieldDropdownHighestID);
		$fieldDropdownHighestID.val(fieldDropdownHighestID);
		return false;
	});

	$(document).on("mouseenter mouseleave", "ul.simple-fields-field-type-options-dropdown-values-added li", function(e) {
		var $t = $(this);
		if ("mouseenter" == e.type) {
			$t.find(".simple-fields-field-type-options-dropdown-delete").show();
		} else {
			$t.find(".simple-fields-field-type-options-dropdown-delete").hide();
		}
	});

	// Dropdown: delete
	$(document).on("click", ".simple-fields-field-type-options-dropdown-delete", function(e) {
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
	$(document).on("click", "div.simple-fields-metabox-field-add a:nth-child(1)", function(e) {

		var $t = $(this).closest("div.simple-fields-metabox-field-add");
		
		$t.text(sfstrings.adding);
		var $wrapper = $t.parents(".simple-fields-meta-box-field-group-wrapper");
		var field_group_id = $wrapper.find("input[name=simple-fields-meta-box-field-group-id]").val();
		var post_id = jQuery("#post_ID").val();

		var data = {
			"action": 'simple_fields_metabox_fieldgroup_add',
			"simple_fields_new_fields_count": simple_fields_new_fields_count,
			"field_group_id": field_group_id,
			"post_id": post_id
		};

		var is_link_at_bottom = $t.hasClass("simple-fields-metabox-field-add-bottom");
	
		$.post(ajaxurl, data, function(response) {

			$ul = $wrapper.find("ul.simple-fields-metabox-field-group-fields");
			$response = $( response.replace(/^\s+/, '') );
			$response.hide();
			if (is_link_at_bottom) {
				$ul.append($response);
			} else {
				$ul.prepend($response);
			}

			var wrapper = $ul.closest("div.simple-fields-meta-box-field-group-wrapper");

			$response.slideDown("slow", function() {
				
				simple_fields_metabox_tinymce_attach();

				// add jscolor to possibly new fields
				jscolor.init();

				// add datepicker too
				$('input.simple-fields-field-type-date', $ul).datePicker(simple_fields_datepicker_args);
				
				// Fire event so plugins can listen to the add-button
				$(document.body).trigger("field_group_added", $response);

			});

			$t.html("<a href='#'>+ "+sfstrings.add+"</a>");
			
			wrapper.addClass("simple-fields-meta-box-field-group-wrapper-has-fields-added");

		});
		
		simple_fields_new_fields_count++;

		return false;
	});

	// edit post connector: delete
	$(document).on("click", "a.simple-fields-post-connector-addded-fields-delete", function(e) {
		if (confirm(sfstrings.confirmRemoveGroupConnector)) {
			$(this).closest("li").hide("slow").find(".simple-fields-post-connector-added-field-deleted").val("1");
		}
		return false;
	});

	// Edit post, field group delete
	$(document).on("click", "div.simple-fields-metabox-field-group-delete a", function(e) {
	
		if (confirm(sfstrings.confirmRemoveGroup)) {
			var li = $(this).closest("li");
			li.hide("slow", function() {

				var wrapper = li.closest("div.simple-fields-meta-box-field-group-wrapper");
				var ul = li.closest("ul.simple-fields-metabox-field-group-fields");
				li.remove();
				
				// If removed last fieldgroup, hide the add link
				if (ul.find(">li").length === 0) {
					//wrapper.find("div.simple-fields-metabox-field-add-bottom").hide("slow");
					wrapper.removeClass("simple-fields-meta-box-field-group-wrapper-has-fields-added");
				} else {
					wrapper.addClass("simple-fields-meta-box-field-group-wrapper-has-fields-added");
				}

			});

		}
	
		return false;
	
	});
	


	// media buttons
	/*
	$(document).on("click", ".simple_fields_tiny_media_button", function(e){
		var id = $(this).closest(".simple-fields-metabox-field").find("textarea").attr("id");
		simple_fields_focusTextArea(id);
		simple_fields_thickbox($(this).get(0));
		return false;
	});
	*/
	
	// field type post
	// popup a dialog where the user can choose the post to attach
	$(document).on("click", "a.simple-fields-metabox-field-post-select", function(e) {

		e.preventDefault();

		var a = $(this),
			div = a.closest(".simple-fields-metabox-field"),
			enabled_post_types = div.find("input[name='simple-fields-metabox-field-post-enabled-post-types']").val(),
			additional_args = div.find('input[name="additional_arguments"]').val();

		$("div.simple-fields-meta-box-field-group-field-type-post-dialog").data("originLink", this).dialog({
			width: 480,
			height: 'auto',
			modal: true,
			dialogClass: 'wp-dialog',
			zIndex: 300000,
			open: function(event, ui) {
				var originLink = $($(this).data("originLink")),
					arr_enabled_post_types = enabled_post_types.split(",");
				$(this).text("Loading...").load(ajaxurl, {
					"action": "simple_fields_field_type_post_dialog_load",
					"arr_enabled_post_types": arr_enabled_post_types,
					"additional_arguments" : additional_args
				});
			}
		});

	});

	
	/**
	 * Post type dialog: click on cancel link
	 * Close the dialog
	 */
	$(document).on("click", ".simple-fields-postdialog-link-cancel", function(e) {
		e.preventDefault();
		$("div.simple-fields-meta-box-field-group-field-type-post-dialog").dialog("close");
	});
	
	/**
	 * in dialog: click on post type = show posts of that type
	 */
	$(document).on("click", ".simple-fields-meta-box-field-group-field-type-post-dialog-post-types a", function(e) {

		e.preventDefault();

		var a = $(this),
			dialog = $("div.simple-fields-meta-box-field-group-field-type-post-dialog"),
			originLink = $(dialog.data("originLink")),
			div = originLink.closest(".simple-fields-metabox-field"),
			enabled_post_types = div.find("input[name='simple-fields-metabox-field-post-enabled-post-types']").val();

		// add this too?
		// additional_args = div.find('input[name="additional_arguments"]').val();
		
		dialog.load(ajaxurl, {
			"action": "simple_fields_field_type_post_dialog_load",
			"str_enabled_post_types": enabled_post_types,
			"selected_post_type": a.attr("href")
		});

	});
	
	/**
	 * in dialog: click on a post = update input in field group and then close dialog
	 */
	$(document).on("click", ".simple-fields-meta-box-field-group-field-type-post-dialog-post-posts a", function(e) {
		
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
	
	/**
	 * Field type post: link clear = clear post id and name
	 */
	$(document).on("click", ".simple-fields-metabox-field-post-clear", function(e) {
		e.preventDefault();
		var a = $(this);
		var div = a.closest(".simple-fields-metabox-field");
		div.find(".simple-fields-metabox-field-post-clear").hide("fast");
		div.find(".simple-fields-field-type-post-postName").hide("fast");
		div.find(".simple-fields-field-type-post-postID").attr("value", "");
	});
	
	/**
	 * ondomready
	 */
	$(function() {

		// boot up
		simple_fields.init();
		simple_fields_file_field.init();

		// If meta_box_field_group_wrapper exists on the page then it's a page with simple fields-fields
		var meta_box_field_group_wrapper = $("div.simple-fields-meta-box-field-group-wrapper");
		if (meta_box_field_group_wrapper.length) {
			
			// Add chosen to select dropdown
			// ...or not, didn't get the widths to work
			// $("div.simple-fields-fieldgroups-field-type-dropdown select").chosen({});

		}

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

		// Edit post connector, add sortable to list of added fields
		$("#simple-fields-post-connector-added-fields").sortable({
			axis: 'y',
			xcontainment: "parent",
			handle: ".simple-fields-post-connector-addded-fields-handle"
		});

		// Edit post connector, show delete link on mouse over
		$(document).on("mouseenter mouseleave", "#simple-fields-post-connector-added-fields li", function(e) {
			$t = $(this);
			if ("mouseenter" == e.type) {
				$t.find(".simple-fields-post-connector-addded-fields-delete").show();
			} else {
				$t.find(".simple-fields-post-connector-addded-fields-delete").hide();
			}
		});


		/**
		 * edit posts
		 */
		// Change connector, show message that you must save
		$(document).on("change", "#simple-fields-post-edit-side-field-settings-select-connector", function() {
			$("#simple-fields-post-edit-side-field-settings-select-connector-please-save").show("fast");
		});

		// Click show custom field keys
		$(document).on("click", "#simple-fields-post-edit-side-field-settings-show-keys", function() {
			var $this = $(this),
				divs = $("div.simple-fields-metabox-field-custom-field-key");

			if (divs.is(":hidden")) {
				divs.addClass("simple-fields-metabox-field-custom-field-key-visible");
			} else {
				divs.removeClass("simple-fields-metabox-field-custom-field-key-visible");
			}
			return false;
		});

		// array with the ids of the textareas that are converted to tiny editors
		var arr_tiny_mce_buffers;

		// Edit post, make repeatable field sortable
		$("ul.simple-fields-metabox-field-group-fields-repeatable").sortable({
			distance: 10,
			axis: 'y',
			handle: ".simple-fields-metabox-field-group-handle",
			start: function(event, ui) {

				// when sorting starts we must do things with the tinymce editors, or content will get lost

				// the item being moved
				var li = $( ui.item.get(0) );

				// get all the textareas that are wysiwyg-editors
				var tinymce_textareas = li.find(".simple-fields-metabox-field-textarea-tinymce-wrapper textarea.simple-fields-metabox-field-textarea-tinymce");

				arr_tiny_mce_buffers = [];

				// Add contents of each tiny editor to buffer
				tinymce_textareas.each(function() {

					var elm = $(this),
						elm_id = elm.attr("id");

					if ( elm_id ) {

						arr_tiny_mce_buffers.push ({
							"id": elm_id,
							"html": $('#' + elm_id + '_ifr').contents().find('body').html()
						});

						// Remove editor instance
						tinyMCE.execCommand('mceRemoveControl', false, elm_id);


					}
				});

			},
			stop: function(event, ui) {

				// when sorting stops we restore values to tiny mce editors
				if (arr_tiny_mce_buffers && arr_tiny_mce_buffers.length) {

					$.each(arr_tiny_mce_buffers, function(i, val) {

						tinyMCE.execCommand('mceAddControl', false, val.id);
						$('#' + val.id + '_ifr').contents().find('body').html(val.html);
						tinyMCE.get(val.id).execCommand('mceRepaint');

					});

				}

			}
		});

		// Media browser: make sure search and filter works by adding hidden inputs
		// would have been best to do this in PHP, but I can't find any filter for it
		if ( window.pagenow && window.pagenow == "media-upload-popup" && window.location.search.match(/simple_fields_dummy=/) ) {

			var frm_filter = $("form#filter");

			// http://localhost/wp-admin/media-upload.php?simple_fields_dummy=1&simple_fields_action=select_file&simple_fields_file_field_unique_id=simple_fields_fieldgroups_12_1_0&post_id=-1&
			// get these
			// simple_fields_dummy=1
			// simple_fields_action=select_file
			// simple_fields_file_field_unique_id=simple_fields_fieldgroups_12_1_0
			var params = {
				"simple_fields_dummy": 1,
				"simple_fields_action": "select_file"
			};

			var match = window.location.search.match(/simple_fields_file_field_unique_id=([\w]+)/);
			params.simple_fields_file_field_unique_id = match[1];
			
			// all params that start with "simple_fields_"
			$.each(params, function(key, val) {
				frm_filter.append("<input type='hidden' name='"+key+"' value='"+val+"' />");
			});

		}
		
		if (sfstrings.page_type == "post") {
	
			// attach TinyMCE to textareas
			// this is only needen when adding with ajax?
			//simple_fields_metabox_tinymce_attach();

			// type date
			$('input.simple-fields-field-type-date').datePicker();

		}
		
	}); // end domready

}(jQuery)); // self invoke function


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
	tb_show(t,a,g);
	link.blur();
	return false;
}


// called when selecting file from tiny-area, if I remember correct
/*
function simple_fields_metabox_file_select(file_id, file_thumb, file_name) {
	simple_fields_metabox_field_file_select_input_selectedID.val(file_id);
	$file_thumb_tag = jQuery("<img src='"+file_thumb+"' alt='' />");
	var sfmf = simple_fields_metabox_field_file_select_input_selectedID.closest(".simple-fields-metabox-field");
	sfmf.find(".simple-fields-metabox-field-file-selected-image").html($file_thumb_tag);
	sfmf.find(".simple-fields-metabox-field-file-selected-image-name").text(file_name);
	sfmf.effect("highlight", 4000);

}
*/
// simple-fields-metabox-field-file

