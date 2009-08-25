<?php
$t['wrap'] = 'BEGIN:VCALENDAR
PRODID:-//Apple Computer\, Inc//iCal 2.0//EN
VERSION:2.0
X-WR-CALNAME: [*pagetitle*]
X-WR-TIMEZONE:Europe/Copenhagen
BEGIN:VTIMEZONE
TZID:Europe/Copenhagen
X-LIC-LOCATION:Europe/Copenhagen
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
[+days+]
END:VCALENDAR';

$t['day'] = '[+events+]';

$t['eventSingle'] ='BEGIN:VEVENT
[+iCal_dtstart+]
[+iCal_dtend+]
SUMMARY:[+summary+]
[+iCal_dtstamp+]
END:VEVENT
';
    
$t['eventFirst'] = 'BEGIN:VEVENT
[+iCal_dtstart+]
[+iCal_dtend+]
SUMMARY:[+summary+]
[+iCal_dtstamp+]
END:VEVENT
';
    
$t['eventBetween'] = '';    
$t['eventLast'] = '';
$t['tag'] = '';
$t['admin'] = '';
$t['createLink'] = '';
$t['addTagLink'] = '';
$t['navigation'] = '';
$t['prevNavigation'] = '';
$t['noPrevNavigation'] = '';
$t['nextNavigation'] = '';
$t['noNextNavigation'] = '';
$t['page'] = '';
$t['activePage'] = '';
$t['form'] = '';
$t['tagform'] = '';
$t['formCheckbox'] = '';

return $t;