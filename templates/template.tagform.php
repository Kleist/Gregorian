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
    'addTagText' => 'add_tag',
    'tagNameText' => 'tag_name',
    'saveText'   => 'save',
    'resetText'  => 'reset'
);

$t['default_values'] = array('tag' => '');

$t['error_obj_doesnt_exist'] = 'error_tag_doesnt_exist';
$t['error_couldnt_create_obj'] = 'error_couldnt_create_tag';

$t['tagform'] = '
        <fieldset><legend>[+addTagText+]</legend><form action="[+formAction+]" method="post">
            <input type="hidden" name="action" value="[+action+]" />
            <fieldset><legend>[+tagNameText+]:</legend><input type="text" id="tag" name="tag" value="[+tag+]" /></fieldset>
            <input type="submit" name="submit" value="[+saveText+]" />
            <input type="reset" name="reset" value="[+resetText+]" />
            </fieldset>
        </form>';
return $t;