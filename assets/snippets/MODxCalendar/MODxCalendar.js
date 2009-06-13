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
		$(this).before('<div class="toggleMark"><a class="ui-icon ui-icon-circle-plus" href="#">+</a></div>');
	});

	// Create links for toggling all descriptions
	$("#calendar").before(
		"<span class='expandAll'><a class='ui-icon ui-icon-circle-plus' href='#'>[+]</a></span>"+
		"<span class='contractAll'><a class='ui-icon ui-icon-circle-minus' href='#'>[-]</a></span>");

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
		
	// Add hidden edit dialog div
	// $('#calendar').before("<div id='edit_dialog' title='Edit calendar entry'><p><span class='ui-icon ui-icon-alert' style='float:left; margin:0 7px 20px 0;'></span>Do you really want to delete the event?</p></div>");
	// $('#delete_dialog').dialog({
	// 	autoOpen: false,
	// 	resizable: false,
	// 	bgiframe: true,
	// 	height: 140,
	// 	width: 400,
	// 	modal: true,
	// 	buttons: {
	// 		'Delete the calendar entry?': function() {
	// 			window.location = delete_path + '&confirmed=1';
	// 		},
	// 		Cancel: function() {
	// 			$(this).dialog('close');
	// 		}
	// 	}
	// });
	// 
	// // Add action to all delete links
	// var delete_path = '';
	// $('#calendar a.delete').click(function(e) {
	// 	e.preventDefault();
	// 	delete_path = $(this).attr('href');
	// 	
	// 	$('#delete_dialog').dialog('open');
	// });
	// 	
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
		this.children('.toggleMark').children('a').html('-').addClass('ui-icon-circle-minus').removeClass('ui-icon-circle-plus');
	} else{
		this.addClass('hiddenDesc');
		this.children('.toggleMark').children('a').html('+').addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-minus');
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