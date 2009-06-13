<?php
require_once (dirname(dirname(__FILE__)) . '/modxcalendar.class.php');
class MODxCalendar_mysql extends MODxCalendar {
    function MODxCalendar_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>