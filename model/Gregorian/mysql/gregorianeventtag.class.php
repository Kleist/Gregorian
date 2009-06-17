<?php
require_once (dirname(dirname(__FILE__)) . '/gregorianeventtag.class.php');
class GregorianEventTag_mysql extends GregorianEventTag {
    function GregorianEventTag_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>