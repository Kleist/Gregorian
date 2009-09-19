jQuery.noConflict();
jQuery(document).ready(function($) {
	$("#dtstart,#dtend").datepicker({dateFormat: 'yy-mm-dd', beforeShow: customRange});

	// Hide time-fields when allday-event
	if ($('#allday').is(':checked')) {
		$('#tmstart,#tmend')
		.fadeTo('fast',0.7)
		.attr("disabled", true);
	}
	
	$('#allday').click(function(e) {
		if ($(this).is(':checked')) {
			$('#tmstart,#tmend')
			.fadeTo('fast',0.7)
			.attr("disabled", true);
		}
		else {
			$('#tmstart,#tmend')
			.fadeTo('fast',1)
			.attr("disabled", false);
		}
	});
});


function customRange(input) 
{ 
	if (input.id == "dtstart" && jQuery("#dtend").datepicker("getDate") != null) {
		return { maxDate: jQuery("#dtend").datepicker("getDate") };
	}
	
	if (input.id == "dtend" && jQuery("#dtstart").datepicker("getDate") != null) {
		return { minDate: jQuery("#dtstart").datepicker("getDate") };
	}
}