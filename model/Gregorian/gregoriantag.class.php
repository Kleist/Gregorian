<?php
class GregorianTag extends xPDOSimpleObject {
    function GregorianTag(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }

    public function getCleanTagName() {
        return $this->cleanTagName($this->get('tag'));
    }

    public static function cleanTagName($name){
        $a = array('®', '¾', '¯', '¿', '', 'Œ', ' ');
        $b = array('AE','ae','OE','oe','AA','aa','_');
        return str_replace($a,$b,$name);
    }
}
?>