<?php
class GregorianConfig {
    private $_config = array();
    
	public function __construct($values=NULL) {
		if (is_array($values)) $this->fromArray($values);
	}
    
	public function set($key,$value = true) {
        if (is_array($key)) { // Not a key, but a complete _config-array
            $this->fromArray($key);
        }
        else {
            $this->_config[$key] = $value;
        }
    }

    public function get($key = '') {
        if ($key == '')
        return $this->_config;
        else
        return $this->_config[$key];
    }
    
    public function fromArray($array) {
    	$this->_config = array_merge($this->_config,$array);
    }
}