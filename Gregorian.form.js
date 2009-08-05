$(document).ready(function() {
	$("#dtstart,#dtend").datepicker({dateFormat: 'yy-mm-dd'});

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