<?php
require_once (dirname(dirname(__FILE__)) . '/gregorianevent.class.php');
class GregorianEvent_mysql extends GregorianEvent {
    function GregorianEvent_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>