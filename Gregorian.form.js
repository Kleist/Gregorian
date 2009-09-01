$(document).ready(function() {
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
	if (input.id == "dtstart" && $("#dtend").datepicker("getDate") != null) {
		return { maxDate: $("#dtend").datepicker("getDate") };
	}
	
	if (input.id == "dtend" && $("#dtstart").datepicker("getDate") != null) {
		return { minDate: $("#dtstart").datepicker("getDate") };
	}
}