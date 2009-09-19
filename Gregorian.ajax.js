// TODO load form-specific javascript when loading form
jQuery.noConflict();
jQuery(document).ready(function($) {
	//	// Add hidden edit dialog div
	$('#calendar').before('<div id="edit_dialog" title="Edit calendar entry?"></div>');
	
	$('#edit_dialog').dialog({
		autoOpen: false,
		resizable: true,
		bgiframe: true,
		modal: true,
		buttons: {
			Save: function() {
				$('#edit_dialog form').submit();
			},
			Cancel: function() {
				$(this).dialog('close');
			}
		}
	});
	
	// Add action to all edit links
	$('#calendar a.edit').click(function(e) {
		e.preventDefault();
		edit_path = $(this).attr('href');
		edit_path = edit_path.replace(/.*?id=[0-9]*/,'');
		$('#edit_dialog').load(ajaxUrl + edit_path).dialog('open');
		// TODO datepicker doesn't work since form is dynamically loaded, perhaps js should be included with form? 
		
	});
});