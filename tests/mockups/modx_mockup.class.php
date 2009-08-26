<?php
class modx_mockup {
	private $_mgrValidated = false;
	
	public function __construct() {
	}
	
    public function isMemberOfWebGroup($groupNames= array ()) {
    	if (!is_array($groupNames)) $groupNames = array($groupNames);
    	foreach ($groupNames as $group) {
    		if ($group == "isMember") return true; // isMember represents a group the user is member of
    	}
    	return false;
	}
	
	public function setMgrValidated($val = true) {
        $this->_mgrValidated = $val;
	}
	
	// Mockup of wrapper for $_SESSION['mgrValidated']
	public function checkSession() {
		return $this->_mgrValidated;
	}
}