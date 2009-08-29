<?php
$t['tagform'] = '
        <fieldset><legend>[+addTagText+]</legend><form action="[+formAction+]" method="post">
            <input type="hidden" name="action" value="[+action+]" />
            <fieldset><legend>[+tagNameText+]:</legend><input type="text" id="tag" name="tag" value="[+tagValue+]" /></fieldset>
            <input type="submit" name="submit" value="[+saveText+]" />
            <input type="reset" name="reset" value="[+resetText+]" />
            </fieldset>
        </form>';
return $t;