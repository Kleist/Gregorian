<?php
require_once "gregorian_mockup.class.php";

class xpdo_mockup {
    private $_mgrValidated = false;
    
    public function __construct() {
    }
    
    public function setPackage($package,$path) {
    	return true;
    }
    
    public function getObject($a,$b) {
    	return new gregorian_mockup(); 
    }
}
