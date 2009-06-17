<?php
class GregorianTag extends xPDOSimpleObject {
    function GregorianTag(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }
}
?>