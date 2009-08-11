<?php

class GregorianController {
    // Configuration defaults
    private $_config = array(
        'calId'         => 1,
        'view'          => 'list',
        'perPage'       => 10,
        'offset'        => 0,
        'filter'        => array(),
        'lang'          => 'en',
        'template'      => 'default',
        'adminGroups'   => array(),
        'mgrIsAdmin'    => true,
        'debug'         => false
    );

    private $_action = 'view';
    
    // Security configuration
    private $_allowedPostActions = array('save','savetag');
    private $_allowedRequestActions = array('view','showform','tagform','delete');
    
    /**
     * Configuration-variables that can be accessed through GET/POST.
     * @var array 
     */
    private $_allowedRequestConfigs = array(
        'view' => array('agenda'),
        'perPage' => 'integer',
        'offset' => 'integer',
        'filter' => 'special',
        'lang' => array('en','da')
    );
    
    private $_output = ''; // "Output buffer" 
    
    // Objects
    private $modx       = NULL;
    private $xpdo       = NULL;
    private $calendar   = NULL; // Gregorian object
    
    
    /**
     * Constructor, sets op xPDO and modx.
     * @param object $modx object.
     */
    public function __construct(&$modx, &$xpdo) {
        $this->modx = &$modx;
		$this->xpdo= &$xpdo;
        
		$this->_snippetDir = dirname(__FILE__);
		
        $this->xpdo->setPackage('Gregorian', $this->_snippetDir . 'model/');
    }
        
    /**
     * Load the calendar with 'calId' set in config. If it doesn't exist, create it.
     * @return int 0 on failure, 1 on load, 2 on create 
     */
    private function loadCalendar() {
		$this->calendar = $this->xpdo->getObject('Gregorian',$calId);
		if ($this->calendar !== NULL) {
			return 1;
		}
		else {
		    $this->calendar = $this->xpdo->newObject('Gregorian',$calId);
		    $saved = $calendar->save();
		    if ($calendar === NULL) {
		    	$this->error("Couldn't create calendar object",'editor');
		        return 0;
		    }
		    if (!$saved) {
                $this->error("Couldn't save calendar object",'editor');
                return 0;
		    }
		    return 2;
		}
    }
    
    /**
     * Get config variable
     * @param string Config name
     * @return mixed Config value
     */
    public function get($name) {
    	if (array_key_exists($name,$this->_config))    return $this->_config[$name];
    	else return NULL;
    }
    
    /**
     * Safe wrapper for the set-function, used for user-requestable configuration-values, validates the input.
     * @param string Configuration name
     * @param mixed Configuration value
     * @return boolean 
     */
    public function safeSet($name,$value = true, $rule) {
    	if (!in_array($name,$this->_allowedRequestConfig)) {
            $this->warning("$name is not a requestable config and should not be set with safeSet()",'debug');
            return false;	
    	}

        $safe = false;

    	if (is_array($rule)) {
    		if (in_array($value,$this->_views)) $safe = true;
    	}
        elseif (is_string($rule)) {
        	switch ($rule) {
        		case 'integer':
        			$value = intval($value);
        			$safe = true;
        			break;
        		
        		case 'filter':
        			$tags = explode(',', $value);
        			foreach ($tags as $tag) {
        				if (!preg_match('^[a-zA-Z¾¿Œ®¯._- ]*$',$tag)) {
        					$this->error('"'.htmlspecialchars($tag).'" in filter is not a valid tag','user');
        					break;
        				}
        			}
        			$safe = true;
        			break;
    	}

    	    	break;
        }
        
        if ($safe)  $this->set($name,$value);
        else        $this->warning("safeSet() for '$name' => '".htmlspecialchars($value)."' failed.",'debug');
        
        return $safe;
    }
    
    /**
     * Set config variable
     * @param string Config name
     * @param mixed Config value (default: true)
     * @return none
     */
    public function set($name,$value = true) {
    	// _config-values that are arrays should also be set as arrays
    	if (is_array($this->_config[$name]) && !is_array(value)) $value = array($value);
        $this->_config[$name] = $value;
    }
    
    /**
     * Checks if user has edit rights
     * 
     * @return boolean True if user has edit rights, false if not.
     */
    public function isEditor() {
    	$groups = $this->get('adminGroups');
    	if ($this->get('mgrIsAdmin') && $this->modx->checkSession()) { 
    	   return true;
    	}
    	elseif ($groups != NULL && !empty($groups)) {
    		return $this->modx->isMemberOfWebGroup($groups);
    	}
    	return false;
    }
    
    /**
     * Handles request and returns processed page
     * @return Processed page
     */
    public function handle() {
    	// TODO load lang and template
    	
    	// GET/POST parsing
    	$this->parseRequestAction();
    	$this->parseRequestConfigs();
    	
    	$this->checkPrivileges(); // Check privileges and change _action.

    	switch ($this->_action) {
    		case 'view':
    			$result = $this->renderView();
    			break;
    	}
    	
        return $this->_output;
    }

    /**
     * Parse $_REQUEST and $_POST and select an action based on allowed actions for each type.
     * @return none
     */
    private function parseRequestAction() {
    	if (isset($_POST['action']) && in_array($_POST['action'], $this->_allowedRequestActions)) {
    		$this->_action = $_POST['action'];
    	}
    	elseif (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->_allowedRequestActions)) {
    		$this->_action = $_REQUEST['action'];
    	}
    }
    
    /**
     * Parse $_REQUEST and $_POST for requestable configs, and set them accordingly.
     * @return none
     */
    private function parseRequestConfigs() {
    	foreach ($this->_allowedRequestConfigs as $config => $rule) {
    		if (isset($_REQUEST[$config])) $this->safeSet($config,$_REQUEST[$config], $rule);
    	}
    }
    
    
    private function checkPrivileges() {
        // Only editors can do other actions than 'view'
    	if ($this->_action != 'view' && !$this->isEditor()) {
            $this->error('user','error_admin_priv_required', htmlspecialchars($action));
            $this->_action = 'view';
        }
    }
    
    private function renderView() {
    	$this->_output .= "View:\n";    	
    }

    private function error() {
    	// TODO implement proper error handling
        $args = func_get_args();

        // Error level is first argument
        $level = array_shift($args);
        $msg = call_user_func_array(array(&$this, 'lang'),$args);
    	
    	die("Error($level): $msg ");
    }
    
    private function warning() {
        // TODO implement proper warning handling
        $args = func_get_args();

        // Warning level is first argument
        $level = array_shift($args);
        $msg = call_user_func_array(array(&$this, 'lang'),$args);
        
    	echo "Warning($level): $msg <br />\n";
    }

    private function lang() {
    	// TODO Copy function from Gregorian class
    	$args = func_get_args();
    	// TODO Improve speed by only calling sprintf if there are some parameters to insert.
        return call_user_func_array('sprintf',$args);
    }
}