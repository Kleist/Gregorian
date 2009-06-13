<?php
require_once (dirname(dirname(__FILE__)) . '/modxcalendareventtag.class.php');
class MODxCalendarEventTag_mysql extends MODxCalendarEventTag {
    function MODxCalendarEventTag_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>