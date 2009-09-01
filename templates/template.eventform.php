<?php
$t['js'] = array(
    'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js',
    'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js',
    'Gregorian.form.js'
);
$t['css'] = array(
    'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css',
);
$t['lang_placeholders'] = array(
    'editEventText'     =>  'edit_event',
    'summaryText'       =>  'summary',
    'tagsText'          =>  'tags',
    'locationText'      =>  'location',
    'descriptionText'   =>  'description',
    'dateAndTimeText'   =>  'date_and_time',
    'startText'         =>  'start',
    'endText'           =>  'end',
    'allDayText'        =>  'all_day',
    'saveText'          =>  'save',
    'resetText'         =>  'reset'
);

$t['default_values'] = array(
    'allday'            =>  true
);

$t['error_obj_doesnt_exist'] = 'error_event_doesnt_exist';
$t['error_couldnt_create_obj'] = 'error_couldnt_create_event';

$t['tag'] = '<label for="[+name+]">[+label+]:</label><input type="checkbox" name="[+name+]" [+checked+] /> &nbsp;&nbsp;&nbsp;';

$t['eventForm'] = '
        <fieldset><legend>[+editEventText+]</legend><form action="[+formAction+]" method="post">
            <input type="hidden" name="eventId" value="[+eventId+]" />
            <input type="hidden" name="action" value="[+action+]" />
            <fieldset><legend>[+summaryText+]:</legend><input type="text" name="summary" value="[+summary+]" /></fieldset>
            <fieldset><legend>[+tagsText+]:</legend>[+tags+]</fieldset>
            <fieldset><legend>[+locationText+]:</legend><input type="text" id="location" name="location" value="[+location+]" /></fieldset>
            <fieldset><legend>[+descriptionText+]:</legend><textarea id="description" name="description">[+description+]</textarea></fieldset>
            <fieldset><legend>[+dateAndTimeText+]</legend>
            <div id="datetimestart"><label>[+startText+]:</label><input type="text" id="dtstart" name="dtstart" value="[+dtstart+]" /> <input type="text" id="tmstart" name="tmstart" value="[+tmstart+]" /><br /></div>
            <div id="datetimeend"><label>[+endText+]:</label><input type="text" id="dtend" name="dtend" value="[+dtend+]" /> <input type="text" id="tmend" name="tmend" value="[+tmend+]" /></div>
            <label>[+allDayText+]:</label><input type="checkbox" id="allday" name="allday" value="allday" [+allday+] /></fieldset>
            <fieldset>
            <input type="submit" name="submit" value="[+saveText+]" />
            <input type="reset" name="reset" value="[+resetText+]" />
            </fieldset>
        </form>';
return $t;