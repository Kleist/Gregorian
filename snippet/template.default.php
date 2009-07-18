<?php
return array(
	'wrap' => 
	'
[+createLink+]
[+navigation+]
<div id="calendar">
[+days+]
</div>
[+navigation+]',

	'day' => 
	"\t<div class='day [+dayclass+]'>\n\t\t<div class='date'>[+date+]</div>\n[+events+]\n\t</div>\n",

	'event' => 
	"		<div class='event'>
		<div class='time'>
			<span class='starttime'>[+starttime+]</span>
			<span class='timedelimiter'>[+timedelimiter+]</span>
			<span class='endtime'>[+endtime+]</span>
		</div>
		<div class='summary'>[+summary+]</div>
		<div class='tags'>[+tags+]</div>
		<div class='desc'>[+description+]</div>
	</div>
	[+editor+]",
	
	'tag' => 
	"<div class='tag tag[+tag+]'>[+tag+]</div>",
	
	'editor' => 
	"		<div class='editor'>
		<a class='edit ui-icon ui-icon-pencil' href='[+editUrl+]'>[ Edit ]</a>
		<a class='delete ui-icon ui-icon-trash' href='[+deleteUrl+]'>[ Delete ]</a>
	</div>",

	'createLink' =>
	'<a class="create ui-icon ui-icon-circle-plus" href="[+createUrl+]">[ Create entry ]</a>',
		
	'navigation' => 
	'[+prev+][+delimiter+][+next+]',
	
	'nextNavigation' => 
	"<a class='ui-icon ui-icon-circle-triangle-e' href='[+nextUrl+]'>[[+nextText+]]</a>",
	
	'noNextNavigation' => 
	"",
	
	'prevNavigation' => 
	"<a class='ui-icon ui-icon-circle-triangle-w' href='[+prevUrl+]'>[[+prevText+]]</a>",
	
	'noPrevNavigation' => 
	"",
	
	'navigationDelimiter' => 
	"<span class='ui-icon ui-icon-grip-dotted-horizontal'> - </span>",
	
	'form' => 
	'
		<fieldset><legend>Edit event</legend><form action="[+formAction+]" method="post">
			<input type="hidden" name="eventId" value="[+eventId+]" />
			<input type="hidden" name="action" value="[+action+]" />
			<fieldset><legend>Summary:</legend><input type="text" name="summary" value="[+summary+]" /></fieldset>
			<fieldset><legend>Tags:</legend>[+tagCheckboxes+]</fieldset>
			<fieldset><legend>Location:</legend><input type="text" name="location" value="[+location+]" /></fieldset>
			<fieldset><legend>Description:</legend><textarea cols="60" rows="10" name="description">[+description+]</textarea></fieldset>
			<fieldset><legend>Date & Time</legend>
			<div id="datetimestart"><label>Start:</label><input type="text" id="dtstart" name="dtstart" value="[+dtstart+]" /> <input type="text" id="tmstart" name="tmstart" value="[+tmstart+]" /><br /></div>
			<div id="datetimeend"><label>End:</label><input type="text" id="dtend" name="dtend" value="[+dtend+]" /> <input type="text" id="tmend" name="tmend" value="[+tmend+]" /></div>
			<label>All day:</label><input type="checkbox" id="allday" name="allday" value="allday" [+allday+] /></fieldset>
			<fieldset>
			<input type="submit" name="submit" value="Save" />
			<input type="reset" name="reset" value="Reset" />
			</fieldset>
		</form>',
	
	'formCheckbox' => 
	'<label for="[+name+]">[+label+]:</label><input type="checkbox" name="[+name+]" [+checked+] /> &nbsp;&nbsp;&nbsp;'
);
