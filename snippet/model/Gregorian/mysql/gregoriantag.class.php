<?php
require_once (dirname(dirname(__FILE__)) . '/gregoriantag.class.php');
class GregorianTag_mysql extends GregorianTag {
    function GregorianTag_mysql(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>