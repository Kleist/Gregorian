<?php

require_once 'GregorianConfig.class.php';

/**
 * The controller in the Gregorian MVC-implementation.
 * Handles all user input and decides which views to load and show.
 */
class GregorianController {
    // Default action
    private $_action = 'show';

    // Default view
    private $_viewConfigs = array('calId','snippetDir','formatForICal',
    'timeFormat','dateFormat','baseUrl','templatePath','lang',
    'count','page','isEditor','allowAddTag','snippetUrl','eventId');

    // Security configuration
    private $_postableActions = array('save','savetag');
    private $_requestableActions = array('show','showform','tagform','delete');
    
    /**
     * @var array Array of configuration-variables that can be set through GET/POST,
     * and their corresponding $rule, see safeSet for more info.
     */
    private $_requestableConfigs = array(
        'view' => array('List','EventForm','TagForm'), // From file: 'Gregorian'.$view.'View.class.php'
        's' => 'integer',
        'offset' => 'integer',
        'filter' => 'special',
        'count' => 'integer',
        'page' => 'integer',
        'lang' => array('en','da')
    );

    // Fields with direct object <=> form relation:
    // TODO Move to form-handler
    private $fields = array('summary','description','location','allday');

    private $debug = 0;

    // Messages for the user
    private $error_messages   = '';
    private $warning_messages = '';
    private $info_messages    = '';

    // Objects
    private $modx       = NULL;
    private $xpdo       = NULL;
    private $cal        = NULL; // Gregorian object
    private $vh         = NULL; // GregorianView or descendant.
    private $config     = NULL; // GregorianConfig or descendant.

    /**
     * Constructor, sets up xPDO and modx.
     * @param object MODx object.
     * @param object xPDO object.
     * @param array Optional config array
     */
    public function __construct(&$modx, &$xpdo,$configArray=NULL) {
        $this->modx = &$modx;
		$this->xpdo= &$xpdo;

		$this->config = new GregorianConfig($configArray);

		$this->set('snippetDir', dirname(__FILE__).'/');

        if (!($result = $this->xpdo->setPackage('Gregorian', $this->get('snippetDir') . 'model/')))
            $this->error('admin',"Failed setPackage('Gregorian',...), returned $result.");
    }

    /**
     * Get config variable
     * @param string Config name
     * @return mixed Config value
     */
    public function get($name) {
    	return $this->config->get($name);
    }

    /**
     * Safe wrapper for the set-function, used for user-requestable configuration-values, validates the input.
     * @param string Configuration name
     * @param mixed Configuration value
     * @return boolean
     */
    public function safeSet($name,$value = true, $rule) {
    	if (!array_key_exists($name,$this->_requestableConfigs)) {
            $this->_warning('debug',"$name is not a requestable config and should not be set with safeSet()");
            return false;
    	}

    	$safe = false;

    	if (is_array($rule)) {
    		if (in_array($value,$rule)) $safe = true;
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
    						$this->error('user','"'.htmlspecialchars($tag).'" in filter is not a valid tag');
    						break;
    					}
    				}
    				$safe = true;
    				break;
    		}
    	}

    	if ($safe)  $this->set($name,$value);
    	else        $this->warning('debug',"safeSet() for '$name' => '".htmlspecialchars($value)."' failed.");

    	return $safe;
    }

    /**
     * Set config variable
     * @param string Config name
     * @param mixed Config value (default: true)
     * @return none
     */
    public function set($name,$value = true) {
    	return $this->config->set($name,$value);
    }

    public function setCalendar(&$cal) {
    	$this->cal = &$cal;
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
    	$output = '';

        // GET/POST parsing
    	$this->_parseRequestAction();
    	$this->_parseRequestConfigs();

    	$this->_checkPrivileges(); // Check privileges and change _action accordingly
    	$this->set('isEditor',$this->isEditor()); // TODO This should be done smarter...

    	if ($this->debug) {
    		echo "<pre>Resulting config... action=$this->_action\n";
    		foreach ($this->config as $key => $value) {
    			echo "$key = ";
    			if (is_array($value)) echo "(".implode(', ',$value).")\n";
    			else echo "$value\n";
    		}
    		echo "</pre>";
    	}

    	switch ($this->_action) {
    		case 'show':
    			$output .= $this->_getView();
    			//die($this->get('view'));
    			break;
            case 'tagform':
            	$output .= $this->_getTagForm();
            	break;
            case 'savetag':
            	$output .= $this->_saveTag();
            	break;
            case 'delete':
            	$output .= $this->_deleteEvent((int) $_REQUEST['eventId']);
            	break;
            case 'save':
            	$output .= $this->_saveEvent((int) $_REQUEST['eventId']);
            	break;
    	}

        return $output;
    }

    public function setDebug($debug=true) {
        $this->debug = $debug;
        $this->xpdo->setDebug($debug);
        $this->xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);
    }

    /**
     * Parse $_REQUEST and $_POST and select an action based on allowed actions for each type.
     * @return none
     */
    private function _parseRequestAction() {
        if (isset($_POST['action']) && in_array($_POST['action'], $this->_postableActions)) {
            $this->_action = $_POST['action'];
        }
        elseif (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->_requestableActions)) {
            $this->_action = $_REQUEST['action'];
        }
        elseif ($this->debug) {
            $this->warning('admin',"Dumping action $_REQUEST[action].");
        }
    }

    /**
     * Parse $_REQUEST and $_POST for requestable configs, and set them accordingly.
     * @return none
     */
    private function _parseRequestConfigs() {
        foreach ($this->_requestableConfigs as $config => $rule) {
            if (isset($_REQUEST[$config])) $this->safeSet($config,$_REQUEST[$config], $rule);
        }

        if ($this->get('mgrIsAdmin') && $this->modx->checkSession() && isset($_REQUEST['debug'])) $this->setDebug($_REQUEST['debug']);
    }

    /**
     * Check that the current user has required privileges for _action, if not change
     * _action to 'show' and show error message.
     * @return none
     */
    private function _checkPrivileges() {
        // Only editors can do other actions than 'show'
        if ($this->_action != 'show' && !$this->isEditor()) {
            $this->error('user','error_admin_priv_required', htmlspecialchars($action));
            $this->_action = 'show';
        }
    }


    /**
     * Load the view handler $vh
     * @return none
     */
    private function _loadView() {
        $loaded = false;
    	$class = "Gregorian".$this->get('view')."View";

    	if (!class_exists($class)) {
            // Attempt to load class file
    		$file = dirname(__FILE__).'/'.$class.".class.php";
            if (file_exists($file)) {
	        	include $file;
            }
        }

    	if (class_exists($class)) {
            $this->vh = new $class(&$this->modx,&$this->xpdo);
            $loaded = true;
    	}
    	return $loaded;
    }

    private function _getView() {
    	if ($this->_loadView()) {
	    	$this->vh->setCalendar($this->cal);
	    	$this->_setViewConfig();
	    	return $this->vh->render();
    	}
    	else return '';
    }

    /**
     * Copy configs in $_viewConfigs from Controller to view.
     * @return none
     */
    private function _setViewConfig() {
    	foreach($this->_viewConfigs as $key) {
    		$value = $this->get($key);
        	$this->vh->set($key, $this->get($key));
        }
    }

    /**
     * Shows the edit form.
     * @param $event mixed Event object, event id, or array of event fields.
     * @return string Rendered form.
     */
    private function _getEventForm($event = NULL) {
    	$gridLoaded = false;

    	// Event placeholders
    	$e_ph = array_flip($this->fields);

    	if ($event == NULL) {
    		// Start with default form if new event
            foreach($this->fields as $field)
            $e_ph[$field] = '';
            $e_ph['allday']=true;
            $gridLoaded = true;
    	}
    	if (!$gridLoaded && is_array($event)) {
    		$e_ph = $event;
    		$gridLoaded = true;
    		// TODO Export this to a function
    		$e_ph['dtstart'] = substr($event['dtstart'],0,10);
    		$e_ph['dtend'] = substr($event['dtend'],0,10);
    		if ($e_ph['allday']) {
    			$e_ph['tmstart'] = '';
    			$e_ph['tmend'] = '';
    		}
    		else {
    			// Time but not seconds
    			$e_ph['tmstart'] = substr($event['dtstart'],11,5);
    			$e_ph['tmend'] = substr($event['dtend'],11,5);
    		}
    	}

    	if (!$gridLoaded && is_integer($event)) {
    		$eventObj = $this->cal->getEvent($event);
    		if (!is_object($eventObj)){
    			$this->error('user','error_event_doesnt_exist',$event);
    			return $this->handleView();
    		}
    		$e_ph['eventId'] = $event;
    	}

    	if (!$gridLoaded && is_object($eventObj)) {
    		// Populate placeholders if editing event
    		$e_ph = $eventObj->get($this->fields);
    		$e_ph['eventId'] = $eventObj->get('id');
    		foreach ($e_ph as $key => $value) $e_ph[$key] = $value;

    		// TODO Export this to a function
    		$e_ph['dtstart'] = substr($eventObj->get('dtstart'),0,10);
    		$e_ph['dtend'] = substr($eventObj->get('dtend'),0,10);
    		if ($e_ph['allday']) {
    			$e_ph['tmstart'] = '';
    			$e_ph['tmend'] = '';
    		}
    		else {
    			// Time but not seconds
    			$e_ph['tmstart'] = substr($eventObj->get('dtstart'),11,5);
    			$e_ph['tmend'] = substr($eventObj->get('dtend'),11,5);
    		}

    		$e_tags = $eventObj->getMany('Tags');
    		$e_tag_ids = array();
    		foreach ($e_tags as $tag) {
    			$e_tag_ids[] = $tag->get('tag');
    		}
    		$gridLoaded = true;
    	}

    	// Show form if new event, or event loaded successfully.
    	if ($gridLoaded) {
    		$this->modx->regClientStartupScript($this->get('snippetUrl').'Gregorian.form.js');
    		// If any $field is set in $_POST, set it in form
    		foreach($this->fields as $field) {
    			if (isset($_POST[$field])) {
    				if (get_magic_quotes_gpc()) {
    					$e_ph[$field] = stripslashes($_POST[$field]);
    				}
    				else {
    					$e_ph[$field] = $_POST[$field];
    				}
    			}
    		}

    		$e_ph['action'] = 'save';
    		$e_ph['formAction'] = $this->_createUrl();
    		$e_ph = array_merge($e_ph, $this->_getRequestPlaceholders());

    		$e_ph['allday'] = ($e_ph['allday']) ? 'checked="yes"' : '';
    		$tags = $this->xpdo->getCollection('GregorianTag');
    		$e_ph['tagCheckboxes'] = '';
    		foreach ($tags as $tag) {
    			$tagName = $tag->get('tag');
    			if (!empty($e_tag_ids) && in_array($tag->get('id'),$e_tag_ids)) {
    				$checked = 'checked="yes"';
    			}
    			else {
    				$checked = '';
    			}

    			// Clean up tag name
    			$cleanTagName = $this->cal->cleanTagName($tagName);

    			$this->modx->toPlaceholders(array('name'=>$cleanTagName,'label'=>$tagName,'checked'=>$checked));
    			$e_ph['tagCheckboxes'] .= $this->modx->mergePlaceholderContent($this->cal->_template['formCheckbox']);
    		}

    		$langPhs = array(
            'editEventText'     =>  'edit_event',
            'summaryText'       =>  'summary',
            'tagsText'          =>  'tags',
            'locationText'      =>  'location',
            'descriptionText'   =>  'description',
            'dateAndTimeText'   =>  'date_and_time',
            'startText'         =>  'start',
            'endText'           =>  'end',
            'allDayText'        =>  'all_day',
            'saveText'          =>  'save',
            'resetText'         =>  'reset'
            );
            foreach($langPhs as $key => $val) {
            	$e_ph[$key] = $this->cal->_lang($val);
            }
            
            $this->modx->toPlaceholders($e_ph);
            return $this->modx->mergePlaceholderContent($this->cal->_template['form']);
    	}
    	 
    }
    
    
    private function _getTagForm($tag = '') {
    	$this->modx->regClientStartupScript($snippetUrl.'Gregorian.form.js');

    	$this->modx->toPlaceholders(array(
            'action'=>'savetag',
            'formAction' => $this->_createUrl(),
            'addTagText' => $this->_lang('add_tag'),
            'tagNameText' => $this->_lang('tag_name'),
            'saveText'   => $this->_lang('save'),
            'resetText'  => $this->_lang('reset'),
            'tagValue'   => $tag
    	));

            return $this->modx->mergePlaceholderContent(
	    	$this->cal->_template['tagform']
	    );
    }

    private function _saveTag() {
    	// Check if tag exists
    	$tag = $this->xpdo->getObject('GregorianTag',array('tag'=>$_POST['tag']));

    	if ($tag != NULL) {
    		$this->info('tag_exists',$tag->get('tag'));
    		return $this->_getView();
    	} else {
    		// If not, create it
    		$tag = $this->xpdo->newObject('GregorianTag',array('tag'=>$_POST['tag']));
    		if ($tag == NULL) {
    			$this->error('user','error_couldnt_create_tag',$_POST['tag']);
    			return $this->_getTagForm($_POST['tag']);
    		} else {
    			$tag->save();
    			$this->info('tag_created', $tag->get('tag'));
                return $this->_getView();
    		}
    	}
    }

    private function _deleteEvent($eventId) {
    	$event = $this->cal->getEvent($eventId);
    	// TODO this should be _POST-based. (Content should never be changed on GET.)
    	if (!isset($_REQUEST['confirmed']) || !$_REQUEST['confirmed']) {
    		$deleteUrl = $this->_createUrl(array('action' => 'delete','confirmed'=>1,'eventId' => $eventId));
    		$cancelUrl = $this->_createUrl(array('action' => 'show'));

    		$output = $this->_lang('really_delete_event',htmlspecialchars($event->get('summary')))."<br />";
    		$output .= "<a href='$deleteUrl'>[".$this->_lang('yes_delete_event', htmlspecialchars($event->get('summary'))).']</a> ';
    		$output .= "<a href='$cancelUrl'>[".$this->_lang('no_cancel').']</a>';
    	}
    	else {
    		$deleted = false;
    		if (is_object($event)) {
    			$summary = $event->get('summary');
    			$deleted = $event->remove();
    		}

    		if ($deleted) {
    			$this->info('event_deleted',$summary);
    		}
    		else
    		{
    			$this->error('user','error_delete_failed');
    		}
            $output = $this->_getView();
    	}
    	return $output;
    }

    private function _saveEvent($eventId = NULL) {
    	//  TODO infoMessage("Why is '->save()' not done with CreateEvent()?");
    	//  TODO Validate input summary
        $event = NULL;
    	if ($eventId) $event = $this->cal->getEvent($eventId);

    	$e_fields = array();

    	$saved = false;
    	$valid = true;

    	// Set event-values from $_POST
    	foreach($this->fields as $field) {
    		if (isset($_POST[$field])){
    			if (get_magic_quotes_gpc()) {
    				$e_fields[$field] = stripslashes($_POST[$field]);
    			}
    			else {
    				$e_fields[$field] = $_POST[$field];
    			}
    		}
    	}
    	// Make allday boolean
    	$e_fields['allday'] = isset($e_fields['allday']);

    	// Check required fields, stop 'save' if they are not adequate
    	// TODO Better date-validation
    	// TODO Make validation on all fields, not just required, perhaps with xPDO's built in validation-features
    	if (!isset($_POST['dtstart']) || $_POST['dtstart'] == '') {
    		$this->error('user','error_startdate_required');
    		$valid = false;
    	}
    	if (!isset($_POST['summary']) || $_POST['summary'] == '') {
    		$this->error('user','error_summary_required');
    		$valid = false;
    	}

    	// Create datetime for start and end, append time if not allday
    	$e_fields['dtstart'] = $_POST['dtstart'];
    	$e_fields['dtend'] = $_POST['dtend'];
    	if (!$e_fields['allday']) {
    		$e_fields['dtstart'] .= ' '. $_POST['tmstart'];
    		$e_fields['dtend'] .= ' '. $_POST['tmend'];
    	}

    	if ($valid && (strtotime($e_fields['dtstart']) > strtotime($e_fields['dtend']) && $e_field['dtend'] != '')) {
    		$this->error('user','error_start_date_after_end_date');
    		$valid = false;
    	}

    	// Add/remove tags
    	// echo "<pre>".print_r($_POST,1)."</pre>";
    	$all_tags = $this->xpdo->getCollection('GregorianTag');

    	if (is_object($event))  $tags = $event->getTags();
    	else                    $tags = array();

        // TODO Add tag validation to allow force of i.e. atleast one tag.
    	foreach ($all_tags as $tag) {
    		$tagName = $tag->get('tag');
    		$cleanTagName = $this->cal->cleanTagName($tagName);

    		if ($_POST[$cleanTagName]) {
    			if (!in_array($tagName,$tags)) $addTags[] = $tagName;
    		}
    		else
    		{
    			if (in_array($tagName,$tags)) {
    				$tagId = $tag->get('id');
    				$this->xpdo->removeObject('GregorianEventTag',array('tag'=>$tagId,'event'=>$eventId));
    			}
    		}
    	}

    	if ($valid) {
    		// Save edited event / Create event
    		if (is_object($event)) {
    			$event->fromArray($e_fields);
    			$event->addTag($addTags);
    			$saved = $event->save();
    		}
    		else {
    			$event = $this->cal->createEvent($e_fields,$addTags);
    			$saved = ($event!== false);
    		}


    		if ($saved) {
    			$this->info('saved_event',$event->get('summary'));
                return $this->_getView();
    		}
    		else {
    			$this->error('user','error_save_failed');
                return $this->_getEventForm($e_fields);
    		}
    	}
    	else { // Not valid
    		return $this->_getEventForm($e_fields);
    	}
    }

    private function _getRequestPlaceholders() {
        $ph = array();
        foreach ($this->_requestableConfigs as $key=>$value) {
            if (($value = $this->get($key)) !== NULL) {
                $ph[$key] = $value;
            }
        }
        return $ph;
    }

    /**
     * Create URL with parameters. Adds ? if not already there.
     */
    private function _createUrl($params = array()) {
        $url = $this->get('baseUrl');
        if (strpos($url,'?')===false) $url .= '?';
        foreach($params as $k => $v) {
            if ($v !== NULL)
            $url .= "&amp;$k=".urlencode($v);
        }
        return $url;
    }

    private function _error() {
    	// TODO implement proper error handling
    	$args = func_get_args();

    	// Error level is first argument
    	$level = array_shift($args);
    	$msg = call_user_func_array(array(&$this, '_lang'),$args);
    	if ($level == 'user') {
    		$this->error_messages = $msg;
    	}
    	else {
    		die("Error($level): $msg ");
    	}

    }

    private function _warning() {
    	// TODO implement proper warning handling
    	if (!$this->get('showWarnings')) return;
    	$args = func_get_args();

        // Warning level is first argument
        $level = array_shift($args);
        $msg = call_user_func_array(array(&$this, '_lang'),$args);

    	$this->warning_messages .= "Warning($level): $msg <br />\n";
    }

    private function _info() {
        // TODO implement proper warning handling
        $args = func_get_args();

        // Warning level is first argument
        $msg = call_user_func_array(array(&$this, '_lang'),$args);

        $this->info_messages .= "$msg <br />\n";
    }


    /**
     * Create URL with parameters. Adds ? if not already there.
     */
    private function _lang($lang) {
        if (func_num_args() == 1) {
        	if (is_array($this->_lang) && array_key_exists($lang,$this->_lang))
                return $this->_lang[$lang];
            else
                return $lang;
        }
        else {
            $args = func_get_args();
            if (array_key_exists($args[0],$this->_lang)) $args[0] = $this->_lang[$args[0]];
            return call_user_func_array("sprintf",$args);
        }
    }

}
