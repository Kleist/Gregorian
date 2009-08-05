<?php
require_once (dirname(dirname(__FILE__)) . '/gregorian.class.php');
class Gregorian_mysql extends Gregorian {
    function Gregorian_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>