<?php
return array(
	'wrap' => 
	'
<ul id="calendarPreNav">
[+navigation+]
[+createLink+]
[+addTagLink+]
</ul>
<div id="calendar">
[+days+]
</div>
<ul id="calendarPostNav">
[+navigation+]
[+createLink+]
[+addTagLink+]
</ul>',

	'day' => 
	"\t<div class='day ui-corner-tl ui-corner-br [+dayclass+]'>\n\t\t<div class='date'>[+date+]</div>\n[+events+]\n\t<div class='dayfooter'></div></div>\n",

	'event' => 
	"		<div class='event'>
		<div class='col2'>
			<div class='time'>
				<span class='starttime'>[+starttime+]</span>
				<span class='timedelimiter'>[+timedelimiter+]</span>
				<span class='endtime'>[+endtime+]</span>
			</div>
			<div class='tags'>[+tags+]</div>
		</div>
		<div class='summary'>[+summary+]</div>
		<div class='admin'>[+admin+]</div>
		<div class='desc'>[+description+]</div>
	</div>",
	
	'tag' => 
	"<div class='tag tag[+tag+]'>[+tag+]</div>",
	
	'admin' => 
	"		<a class='delete ui-icon ui-icon-trash' href='[+deleteUrl+]'>[ Delete ]</a>
		<a class='edit ui-icon ui-icon-pencil' href='[+editUrl+]'>[ Edit ]</a>",

	'createLink' =>
	'<li class="create ui-state-default ui-corner-all"><a class="create" href="[+createUrl+]" title="Create entry">Create entry</a></li>',

	'addTagLink' =>
	'<li class="addtag ui-state-default ui-corner-all"><a class="addtag" href="[+addTagUrl+]" title="Add tag">Add tag</a></li>',
		
	'navigation' => 
	'[+prev+][+delimiter+]<li class="numNav">[+numNav+]</li>[+delimiter+][+next+]',
	
    'prevNavigation' => 
    '<li class="ui-state-default ui-corner-all"><a class="prevNav ui-icon ui-icon-circle-triangle-w" href="[+prevUrl+]" title="[+prevText+]">[[+prevText+]]</a></li>',
    
    'noPrevNavigation' => 
    '<li class="ui-state-disabled ui-state-default ui-corner-all"><span class="prevNav ui-icon ui-icon-circle-triangle-w" title="[+prevText+]">[[+prevText+]]</span></li>',

    'nextNavigation' => 
	'<li class="ui-state-default ui-corner-all"><a class="nextNav ui-icon ui-icon-circle-triangle-e" href="[+nextUrl+]" title="[+nextText+]">[[+nextText+]]</a></li>',
	
	'noNextNavigation' => 
    '<li class="ui-state-disabled ui-state-default ui-corner-all"><span class="nextNav ui-icon ui-icon-circle-triangle-e" title="[+nextText+]">[[+nextText+]]</span></li>',
    
    'page' =>
    '<li class="ui-state-default ui-corner-all"><a href="[+pageUrl+]" class="pageNumber" title="[+page+]">[+page+]</a></li>',
    
    'activePage' =>
    '<li class="ui-state-default ui-state-disabled ui-corner-all"><a href="[+pageUrl+]" class="pageNumber" title="[+page+]">[+page+]</a></li>',
    
'form' => 
	'
		<fieldset><legend>Edit event</legend><form action="[+formAction+]" method="post">
			<input type="hidden" name="eventId" value="[+eventId+]" />
			<input type="hidden" name="action" value="[+action+]" />
			<fieldset><legend>Summary:</legend><input type="text" name="summary" value="[+summary+]" /></fieldset>
			<fieldset><legend>Tags:</legend>[+tagCheckboxes+]</fieldset>
			<fieldset><legend>Location:</legend><input type="text" id="location" name="location" value="[+location+]" /></fieldset>
			<fieldset><legend>Description:</legend><textarea id="description" name="description">[+description+]</textarea></fieldset>
			<fieldset><legend>Date & Time</legend>
			<div id="datetimestart"><label>Start:</label><input type="text" id="dtstart" name="dtstart" value="[+dtstart+]" /> <input type="text" id="tmstart" name="tmstart" value="[+tmstart+]" /><br /></div>
			<div id="datetimeend"><label>End:</label><input type="text" id="dtend" name="dtend" value="[+dtend+]" /> <input type="text" id="tmend" name="tmend" value="[+tmend+]" /></div>
			<label>All day:</label><input type="checkbox" id="allday" name="allday" value="allday" [+allday+] /></fieldset>
			<fieldset>
			<input type="submit" name="submit" value="Save" />
			<input type="reset" name="reset" value="Reset" />
			</fieldset>
		</form>',

	'tagform' => 
	'
		<fieldset><legend>Add tag</legend><form action="[+formAction+]" method="post">
			<input type="hidden" name="action" value="[+action+]" />
			<fieldset><legend>Tag name:</legend><input type="text" id="tag" name="tag" value="" /></fieldset>
			<input type="submit" name="submit" value="Save" />
			<input type="reset" name="reset" value="Reset" />
			</fieldset>
		</form>',

	'formCheckbox' => 
	'<label for="[+name+]">[+label+]:</label><input type="checkbox" name="[+name+]" [+checked+] /> &nbsp;&nbsp;&nbsp;'
);
