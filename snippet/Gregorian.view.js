var toggleSpeed = 200;
var toggleAllSpeed = 100;
var ajaxUrl; 
$(document).ready(function() {
	// Hide descriptions
	$("#calendar .summary").toggleDesc(0,0);
	
	// Create links for toggling each events description
	$("#calendar .summary")
	.filterEmptyDesc()
	.each(function() {
		$(this).html('<a href="#">' + $(this).html() + '</a>');
		$(this).before('<div class="toggleMark"><a class="ui-icon ui-icon-plus" href="#">+</a></div>');
	});

	// Create links for toggling all descriptions at once
	$("#calendar").before(
		"<span class='expandAll'><a class='ui-icon ui-icon-plus' href='#'>[+]</a></span>"+
		"<span class='contractAll'><a class='ui-icon ui-icon-minus' href='#'>[-]</a></span>");

	// Toggle event description
	// TODO This selection is not effective, could be optimized, since .summary and .togglemark are sibblings
	// TODO Follow links in description
	$("#calendar .summary,#calendar .toggleMark").filterEmptyDesc().click(function(e) {
		$(this).toggleDesc();
		e.preventDefault();
	});
	
	// Expand/contract all
	$(".expandAll").click(function(e) {
		$('#calendar .summary').filterEmptyDesc().toggleAll(1);
		e.preventDefault();
	});
	$(".contractAll").click(function(e) {
		$('#calendar .summary').filterEmptyDesc().toggleAll(0);
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
	
});

// Function to toggle all descriptions at once
jQuery.fn.toggleAll = function(show,speed) {
	if (speed == undefined) speed = toggleAllSpeed;
	this.each(function() {
		$(this).toggleDesc(show);
	});
	return this;
};

// Function to toggle a single description, should be called on sibbling of div.desc (typically div.summary)
jQuery.fn.toggleDesc = function (show,speed) {
	// 'this' is .summary, .toggleMark or .desc
	if (speed == undefined) speed = toggleSpeed;
	var desc;
	var summary;
	var toggleMark;
	if (this.hasClass('.desc')) {
		desc = this;
	}
	else {
		desc = this.siblings('.desc');
	}

	if (this.hasClass('.summary')) {
		summary = this;
	}
	else {
		summary = this.siblings('.summary');
	}
	
	if (this.hasClass('.toggleMark')) {
		toggleMark = this.children('a');
	}
	else {
		toggleMark = this.siblings('.toggleMark').children('a'); 
	}

	tags = this.siblings('.col2').children('.tags'); 
	
			
	if (show==undefined && (desc.is(':hidden') && tags.is(':hidden')) || show) {
		toggleMark.html('-').addClass('ui-icon-minus').removeClass('ui-icon-plus');
		desc.slideDown(speed);
		tags.slideDown(speed);
	} else{
		toggleMark.html('+').addClass('ui-icon-plus').removeClass('ui-icon-minus');
		desc.slideUp(speed);
		tags.slideUp(speed);
	};
	return this;
}

// Filter to choose only .summary's with non-empty .desc-siblings (
jQuery.fn.filterEmptyDesc = function() {
	return this.filter(function(){
		var desc;
		if ($(this).hasClass('.desc')) {
			desc = $(this);
		} else {
			desc = $(this).siblings('.desc');
		}
		
		return ! (desc.is(':empty') && desc.siblings('.tags').is(':empty'));
	});
}