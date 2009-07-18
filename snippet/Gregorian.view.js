var toggleSpeed = 200;
var toggleAllSpeed = 100;

$(document).ready(function() {
	// Hide descriptions
	$("#calendar .event").toggleDesc(0,0);
	
	// Create links for toggling each events description
	$("#calendar .event .summary")
	.filter(function() {
		return ! $(this).siblings('.desc').is(':empty');
	})
	.each(function() {
		$(this).html('<a href="#">' + $(this).html() + '</a>');
		$(this).before('<div class="toggleMark"><a class="ui-icon ui-icon-plus" href="#">+</a></div>');
	});

	// Create links for toggling all descriptions
	$("#calendar").before(
		"<span class='expandAll'><a class='ui-icon ui-icon-plus' href='#'>[+]</a></span>"+
		"<span class='contractAll'><a class='ui-icon ui-icon-minus' href='#'>[-]</a></span>");

	// Toggle event description
	$("#calendar .event").filterEmptyDesc().click(function(e) {
		$(this).toggleDesc();
		e.preventDefault();
	});
	
	// Expand/contract all
	$(".expandAll").click(function(e) {
		$('#calendar .event').filterEmptyDesc().toggleAll(1);
		e.preventDefault();
	});
	$(".contractAll").click(function(e) {
		$('#calendar .event').filterEmptyDesc().toggleAll(0);
		e.preventDefault();
	});


	// Add hidden delete dialog div
	$('#calendar').before("<div id='delete_dialog' title='Delete calendar entry?'><p><span class='ui-icon ui-icon-alert' style='float:left; margin:0 7px 20px 0;'></span>Do you really want to delete the event?</p></div>");
	$('#delete_dialog').dialog({
		autoOpen: false,
		resizable: false,
		bgiframe: true,
		height: 140,
		width: 400,
		modal: true,
		buttons: {
			'Delete the calendar entry?': function() {
				window.location = delete_path + '&confirmed=1';
			},
			Cancel: function() {
				$(this).dialog('close');
			}
		}
	});

	
	// Add action to all delete links
	var delete_path = '';
	$('#calendar a.delete').click(function(e) {
		e.preventDefault();
		delete_path = $(this).attr('href');
		
		$('#delete_dialog').dialog('open');
	});
	
	$("#dtstart,#dtend").datepicker({dateFormat: 'yy-mm-dd'});

	
	//	// Add hidden edit dialog div
	$('#calendar').before('<div id="edit_dialog" title="Edit calendar entry?"><fieldset><legend>Edit event</legend><form action="[+formAction+]" method="post">'+
		'<input type="hidden" name="eventId" value="[+eventId+]" />'+
		'<input type="hidden" name="action" value="[+action+]" />'+
		'<fieldset><legend>Summary:</legend><input type="text" name="summary" value="[+summary+]" /></fieldset>'+
		'<fieldset><legend>Tags:</legend>[+tagCheckboxes+]</fieldset>'+
		'<fieldset><legend>Location:</legend><input type="text" name="location" value="[+location+]" /></fieldset>'+
		'<fieldset><legend>Description:</legend><textarea cols="60" rows="10" name="description">[+description+]</textarea></fieldset>'+
		'<fieldset><legend>Date & Time</legend><label>Start:</label><input type="text" id="dtstart" name="dtstart" value="[+dtstart+]" /><br />'+
		'<label>End:</label><input type="text" id="dtend" name="dtend" value="[+dtend+]" /><br />'+
		'<label>All day:</label><input type="checkbox" name="allday" value="allday" [+allday+] /></fieldset>'+
		'<fieldset>'+
		'<input type="submit" name="submit" value="Save" />'+
		'<input type="reset" name="reset" value="Reset" />'+
		'</fieldset>'+
		'</form></div>');
	
	$('#edit_dialog').dialog({
		autoOpen: false,
		resizable: true,
		bgiframe: true,
		height: 140,
		width: 400,
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
	var edit_path = '';
	$('#calendar a.edit').click(function(e) {
		e.preventDefault();
		edit_path = $(this).attr('href');
		
		$('#edit_dialog').dialog('open');
	});
	
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

jQuery.fn.toggleAll = function(show,speed) {
	if (speed == undefined) speed = toggleAllSpeed;
	this.each(function() {
		$(this).toggleDesc(show);
	});
	return this;
};

jQuery.fn.toggleDesc = function (show,speed) {
	// 'this' is .event
	if (speed == undefined) speed = toggleSpeed;
	var desc = this.children('.desc');
	if (show==undefined && desc.is(':hidden') || show) {
		this.removeClass('hiddenDesc');
		this.children('.toggleMark').children('a').html('-').addClass('ui-icon-minus').removeClass('ui-icon-plus');
	} else{
		this.addClass('hiddenDesc');
		this.children('.toggleMark').children('a').html('+').addClass('ui-icon-plus').removeClass('ui-icon-minus');
	};
	if (show!=undefined) {
		if (show) {
			desc.slideDown(speed);
		} else{
			desc.slideUp(speed);
		};
	}
	else {
		desc.slideToggle(speed);
	}
	return this;
}

jQuery.fn.filterEmptyDesc = function() {
	return this.filter(function() { // Filter out empty description events
		return ! $(this).children('.desc').is(':empty');
	});
}
