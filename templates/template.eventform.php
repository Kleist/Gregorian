<?php
$t['formCheckbox'] = '<label for="[+name+]">[+label+]:</label><input type="checkbox" name="[+name+]" [+checked+] /> &nbsp;&nbsp;&nbsp;';

$t['eventForm'] = '
        <fieldset><legend>[+editEventText+]</legend><form action="[+formAction+]" method="post">
            <input type="hidden" name="eventId" value="[+eventId+]" />
            <input type="hidden" name="action" value="[+action+]" />
            <fieldset><legend>[+summaryText+]:</legend><input type="text" name="summary" value="[+summary+]" /></fieldset>
            <fieldset><legend>[+tagsText+]:</legend>[+tagCheckboxes+]</fieldset>
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