<?php
require_once "GregorianView.class.php";

class GregorianEventFormView extends GregorianView {
    public function __construct(&$modx, &$xpdo) {
        parent::__construct(&$modx, &$xpdo);
    }
    
    public function render() {
    	return 'EventForm goes here';
    }    
}