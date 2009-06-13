<?php
require_once (dirname(dirname(__FILE__)) . '/modxcalendartag.class.php');
class MODxCalendarTag_mysql extends MODxCalendarTag {
    function MODxCalendarTag_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>