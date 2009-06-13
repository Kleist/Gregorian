<?php
require_once (dirname(dirname(__FILE__)) . '/modxcalendarevent.class.php');
class MODxCalendarEvent_mysql extends MODxCalendarEvent {
    function MODxCalendarEvent_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>