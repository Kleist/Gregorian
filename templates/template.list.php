<?php
$t['js'] = array(
    'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js',
    'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js',
    'Gregorian.view.js'
);

$t['css'] = array(
    'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css',
    'layout.css'
);

$t['wrap'] = '<div id="delete_dialog" title="[+deleteCalendarEntryText+]"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>[+reallyDeleteText+]</p></div>
<div id="#GregorianMessages">[+GregorianMessages+]</div>
<ul id="calendarPreNav">
[+navigation+]
</ul>
<div id="calendar">
[+days+]
</div>
<ul id="calendarPostNav">
[+navigation+]
</ul>
';

$t['day'] = '    <div class="day ui-corner-tl ui-corner-br [+dayclass+]">
    <div class="date">[+date+]</div>
[+events+]
    <div class="dayfooter"></div></div>
';

$t['eventSingle'] = "        <div class='event eventSingle'>
            <div class='col2'>
                <div class='time'>
                    <span class='starttime'>[+starttime+]</span>
                    <span class='timedelimiter'>[+timedelimiter+]</span>
                    <span class='endtime'>[+endtime+]</span>
                </div>
                <div class='tags'>[+tags+]</div>
            </div>
            <div class='toggleMark' style='display:none;'><a class='ui-icon ui-icon-plus' title='[+toggleText+]' href='#'>+</a></div>
            <div class='summary'>[+summary+]</div>
            <div class='admin'>[+admin+]</div>
            <div class='desc'><div class='location'>[+location+]</div>[+description+]</div>
        </div>
";

$t['eventFirst'] = "        <div class='event eventFirst'>
            <div class='col2'>
                <div class='time'>
                    <span class='starttime'>[+starttime+]</span>
                    <span class='timedelimiter'>[+timedelimiter+]</span>
                    <span class='endtime'>&nbsp;</span>
                </div>
                <div class='tags'>[+tags+]</div>
            </div>
            <div class='toggleMark' style='display:none;'><a class='ui-icon ui-icon-plus' title='[+toggleText+]' href='#'>+</a></div>
            <div class='summary'>[+summary+]</div>
            <div class='admin'>[+admin+]</div>
            <div class='desc'><div class='desc_date'>[+startdate+] - [+enddate+]</div><div class='location'>[+location+]</div>[+description+]</div>
        </div>
";

$t['eventBetween'] =  "        <div class='event eventBetween'>
            <div class='col2'>
                <div class='time'>
                    <span class='starttime'></span>
                    <span class='timedelimiter'></span>
                    <span class='endtime'></span>
	            </div>
	            <div class='tags'>[+tags+]</div>
	        </div>
	        <div class='toggleMark' style='display:none;'><a class='ui-icon ui-icon-plus' title='[+toggleText+]' href='#'>+</a></div>
	        <div class='summary'>[+summary+]</div>
	        <div class='admin'>[+admin+]</div>
	        <div class='desc'><div class='desc_date'>[+startdate+] - [+enddate+]</div><div class='location'>[+location+]</div>[+description+]</div>
	    </div>
";

$t['eventLast'] = "        <div class='event eventLast'>
	        <div class='col2'>
	            <div class='time'>
	                <span class='starttime'>&nbsp;</span>
	                <span class='timedelimiter'>[+timedelimiter+]</span>
	                <span class='endtime'>[+endtime+]</span>
	            </div>
	            <div class='tags'>[+tags+]</div>
	        </div>
	        <div class='toggleMark' style='display:none;'><a class='ui-icon ui-icon-plus' title='[+toggleText+]' href='#'>+</a></div>
	        <div class='summary'>[+summary+]</div>
	        <div class='admin'>[+admin+]</div>
	        <div class='desc'><div class='desc_date'>[+startdate+] - [+enddate+]</div><div class='location'>[+location+]</div>[+description+]</div>
	    </div>
";

$t['admin'] = '     <a class="delete ui-icon ui-icon-trash" title="[+deleteText+]" href="[+deleteUrl+]">[ [+deleteText+] ]</a>
        <a class="edit ui-icon ui-icon-pencil" title="[+editText+]" href="[+editUrl+]">[ [+editText+] ]</a>
';

$t['tag'] = '<div class="tag tag[+tag+]">[+tag+]</div>';
$t['createLink'] = '<li class="create ui-state-default ui-corner-all"><a class="create" href="[+createUrl+]" title="[+createEntryText+]">[+createEntryText+]</a></li>';
$t['addTagLink'] = '<li class="addtag ui-state-default ui-corner-all"><a class="addtag" href="[+addTagUrl+]" title="[+addTagText+]">[+addTagText+]</a></li>';
$t['navigation'] = '<li class="ui-state-default ui-corner-all expandAllLi" style="display: none;"><a class="expandAll ui-icon ui-icon-plus" title="[+expandAllText+]" href="#">[+]</a></li>
<li class="ui-state-default ui-corner-all contractAllLi" style="display: none;"><a class="contractAll ui-icon ui-icon-minus" title="[+contractAllText+]" href="#">[-]</a></li>
[+prev+][+delimiter+][+numNav+][+delimiter+][+next+][+createLink+][+addTagLink+]';
$t['prevNavigation'] = '<li class="ui-state-default ui-corner-all"><a class="prevNav ui-icon ui-icon-circle-triangle-w" href="[+prevUrl+]" title="[+prevText+]">[[+prevText+]]</a></li>';
$t['noPrevNavigation'] = '<li class="ui-state-disabled ui-state-default ui-corner-all"><span class="prevNav ui-icon ui-icon-circle-triangle-w" title="[+prevText+]">[[+prevText+]]</span></li>';
$t['nextNavigation'] = '<li class="ui-state-default ui-corner-all"><a class="nextNav ui-icon ui-icon-circle-triangle-e" href="[+nextUrl+]" title="[+nextText+]">[[+nextText+]]</a></li>';
$t['noNextNavigation'] = '<li class="ui-state-disabled ui-state-default ui-corner-all"><span class="nextNav ui-icon ui-icon-circle-triangle-e" title="[+nextText+]">[[+nextText+]]</span></li>';
$t['page'] = '<li class="ui-state-default ui-corner-all"><a href="[+pageUrl+]" class="pageNumber" title="[+pageNum+]">[+pageNum+]</a></li>';
$t['activePage'] = '<li class="ui-state-default ui-state-disabled ui-corner-all"><a href="[+pageUrl+]" class="pageNumber" title="[+pageNum+]">[+pageNum+]</a></li>';

return $t;