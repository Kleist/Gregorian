<?php

/**
 * GregorianController is the C in the Gregorian MVC-implementation.
 * Handles all user input and decides which views to load and show.
 */
class GregorianController {
    // Configuration defaults
    private $config = array(
        'calId'         => 1,
        'view'          => 'list',
        'page' => 1,
        'count'       => 10,
        'filter'        => array(),
        'lang'          => 'en',
        'template'      => 'default',
        'adminGroups'   => array(),
        'mgrIsAdmin'    => true,
        'snippetUrl'    => '/assets/snippets/Gregorian/'
    );

    // Default action
    private $action = 'view';
    
    // Security configuration
    private $allowedPostActions = array('save','savetag');
    private $allowedRequestActions = array('view','showform','tagform','delete');
    
    /**
     * Configuration-variables that can be accessed through GET/POST.
     * @var array 
     */
    private $allowedRequestConfigs = array(
        'view' => array('list'),
        's' => 'integer',
        'offset' => 'integer',
        'filter' => 'special',
        'count' => 'integer',
        'page' => 'integer',
        'lang' => array('en','da')
    );
    
    // Fields with direct object <=> form relation:
    private $fields = array('summary','description','location','allday');
    
    
    // Output buffer
    private $output = '';

    // Messages for the user
    private $error_messages   = '';
    private $warning_messages = '';
    private $info_messages    = '';
    
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
        
		$this->snippetDir = dirname(__FILE__).'/';
		
        if (!($result = $this->xpdo->setPackage('Gregorian', $this->snippetDir . 'model/')))
            $this->error('admin',"Failed setPackage('Gregorian',...), returned $result.");
    }
        
    
    /**
     * Reload calendar
     */
    private function reLoadCalendar() {
    	unset($this->calendar);
    	$this->loadCalendar();
    }
    
    /**
     * Load calendar and load lang and template
     */
    private function loadCalendar() {
        $this->_loadCalendar();
        $this->calendar->setConfig('snippetDir',$this->snippetDir);
        $this->calendar->loadTemplate($this->snippetDir.'templates/template.'.$this->get('template').'.php');
        $this->calendar->loadLang($this->get('lang'));
    }
    
    /**
     * Load the calendar with 'calId' set in config. If it doesn't exist, create it.
     * @return int 0 on failure, 1 on load, 2 on create 
     */
    private function _loadCalendar() {
    	$this->calendar = $this->xpdo->getObject('Gregorian',$this->get('calId'));
		if ($this->calendar !== NULL) {
			return 1;
		}
		else {
		    $this->calendar = $this->xpdo->newObject('Gregorian',$this->get('calId'));
		    if ($this->calendar === NULL) {
		    	$this->error('editor',"Couldn't create calendar object");
		        return 0;
		    }
            $saved = $this->calendar->save();
		    if (!$saved) {
                $this->error('editor',"Couldn't save calendar object");
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
    	if (array_key_exists($name,$this->config))    return $this->config[$name];
    	else return NULL;
    }
    
    /**
     * Safe wrapper for the set-function, used for user-requestable configuration-values, validates the input.
     * @param string Configuration name
     * @param mixed Configuration value
     * @return boolean 
     */
    public function safeSet($name,$value = true, $rule) {
    	if (!array_key_exists($name,$this->allowedRequestConfigs)) {
            $this->warning('debug',"$name is not a requestable config and should not be set with safeSet()");
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
    	// _config-values that are arrays should also be set as arrays
    	if (is_array($this->config[$name]) && !is_array(value)) $value = array($value);
        $this->config[$name] = $value;
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
     * Parse $_REQUEST and $_POST and select an action based on allowed actions for each type.
     * @return none
     */
    private function parseRequestAction() {
        if (isset($_POST['action']) && in_array($_POST['action'], $this->allowedPostActions)) {
            $this->action = $_POST['action'];
        }
        elseif (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->allowedRequestActions)) {
            $this->action = $_REQUEST['action'];
        }
        elseif ($this->debug) {
            $this->warning('admin',"Dumping action $_REQUEST[action].");
        }
    }
    
    /**
     * Parse $_REQUEST and $_POST for requestable configs, and set them accordingly.
     * @return none
     */
    private function parseRequestConfigs() {
        foreach ($this->allowedRequestConfigs as $config => $rule) {
            if (isset($_REQUEST[$config])) $this->safeSet($config,$_REQUEST[$config], $rule);
        }
        
        if ($this->get('mgrIsAdmin') && $this->modx->checkSession() && isset($_REQUEST['debug'])) $this->setDebug($_REQUEST['debug']);
    }
    
    /**
     * Check that the current user has required privileges for _action, if not change 
     * _action to 'view' and show error message.
     * @return none
     */
    private function checkPrivileges() {
        // Only editors can do other actions than 'view'
        if ($this->action != 'view' && !$this->isEditor()) {
            $this->error('user','error_admin_priv_required', htmlspecialchars($action));
            $this->action = 'view';
        }
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
    	
    	$this->loadCalendar();
    	$this->calendar->setConfig('snippetDir',$this->snippetDir);
    	$this->calendar->loadTemplate($this->snippetDir.'templates/template.'.$this->get('template').'.php');
        $this->calendar->loadLang($this->get('lang'));
        
        $this->checkPrivileges(); // Check privileges and change _action accordingly
        
    	if ($this->debug) {
    		echo "<pre>Resulting config... action=$this->action\n";
    		foreach ($this->config as $key => $value) {
    			echo "$key = ";
    			if (is_array($value)) echo "(".implode(', ',$value).")\n";
    			else echo "$value\n";
    		}
    		echo "</pre>";
    	}

        switch ($this->action) {
    		case 'view':
    			$this->output .= $this->handleView();
    			break;
    		case 'showform':
    			if ((int)($_REQUEST['eventId'])) {
    				$this->output .= $this->handleShowForm((int) $_REQUEST['eventId']);
    			}
    			else {
    				$this->output .= $this->handleShowForm();
    			}
    			break;
            case 'tagform':
            	$this->output .= $this->handleShowTagForm();
            	break;
            case 'savetag':
            	$this->output .= $this->handleSaveTag();
            	break;
            case 'delete':
            	$this->output .= $this->handleDelete((int) $_REQUEST['eventId']);
            	break;            	
            case 'save':
            	$this->output .= $this->handleSave((int) $_REQUEST['eventId']);
            	break;
    	}
    	
        return $this->showOutput();
    }

    private function handleView() {
    	if ($this->get('view') == 'list') {
            $this->calendar->setConfig('count',$this->get('count'));
            $this->calendar->setConfig('page',$this->get('page'));
            $this->calendar->setConfig('isEditor',$this->isEditor());
        
            $this->calendar->getFutureEvents();
            
    		$this->modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
    		$this->modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
    		$snippetUrl = $modx->config['base_url'].$this->get('snippetUrl');
//    		if ($ajaxEnabled) {
//    			$this->modx->regClientStartupScript('<script type="text/javascript">var ajaxUrl="'.$ajaxUrl.'"</script>',true);
//    			$this->modx->regClientStartupScript($snippetUrl.'Gregorian.ajax.js');
//    		}
    		$this->modx->regClientStartupScript($snippetUrl.'Gregorian.view.js');
    		$this->modx->regClientCSS($snippetUrl.'layout.css');
    		$this->modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');

    		return $this->calendar->renderCalendar();
    	}
    	else {
    		$this->error('admin','list is currently the only implemented view');
    	}
    }

    /**
     * Shows the edit form.
     * @param $event mixed Event object, event id, or array of event fields.
     * @return string Rendered form.
     */
    private function handleShowForm($event = NULL) {
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
    		$eventObj = $this->calendar->getEvent($event);
    		if (!is_object($eventObj)){
    			$this->error('user','error_event_doesnt_exist',$event);
    			$this->reLoadCalendar();
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
    		$e_ph['formAction'] = $this->createUrl();
    		$e_ph = array_merge($e_ph, $this->calendar->getPlaceholdersFromConfig());

    		$e_ph['allday'] = ($e_ph['allday']) ? 'checked="yes"' : '';
    		$tags = $this->calendar->xpdo->getCollection('GregorianTag');
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
    			$cleanTagName = $this->calendar->cleanTagName($tagName);

    			$e_ph['tagCheckboxes'] .= $this->calendar->replacePlaceholders($this->calendar->_template['formCheckbox'], array('name'=>$cleanTagName,'label'=>$tagName,'checked'=>$checked));
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
            	$e_ph[$key] = $this->calendar->lang($val);
            }
            
            return $this->calendar->replacePlaceholders($this->calendar->_template['form'], $e_ph);
    	}
    	 
    }
    
    
    private function handleShowTagForm($tag = '') {
    	$this->modx->regClientStartupScript($snippetUrl.'Gregorian.form.js');
    	return $this->calendar->replacePlaceholders(
	    	$this->calendar->_template['tagform'],
	    	array(
	            'action'=>'savetag', 
	            'formAction' => $this->createUrl(),
	            'addTagText' => $this->lang('add_tag'),
	            'tagNameText' => $this->lang('tag_name'),
	            'saveText'   => $this->lang('save'),
	            'resetText'  => $this->lang('reset'),
                'tagValue'   => $tag
	    	)
    	);
    }
    
    private function handleSaveTag() {
    	// Check if tag exists
    	$tag = $this->xpdo->getObject('GregorianTag',array('tag'=>$_POST['tag']));

    	if ($tag != NULL) {
    		$this->info('tag_exists',$tag->get('tag'));
    		$this->reLoadCalendar();
    		return $this->handleView();
    	} else {
    		// If not, create it
    		$tag = $this->xpdo->newObject('GregorianTag',array('tag'=>$_POST['tag']));
    		if ($tag == NULL) {
    			$this->error('user','error_couldnt_create_tag',$_POST['tag']);
    			return $this->showTagForm($_POST['tag']);
    		} else {
    			$tag->save();
    			$this->info('tag_created', $tag->get('tag'));
                $this->reLoadCalendar();
                return $this->handleView();
    		}
    	}
    }
    
    private function handleDelete($eventId) {
    	$event = $this->calendar->getEvent($eventId);
    	// TODO this should be _POST-based. (Content should never be changed on GET.)
    	if (!isset($_REQUEST['confirmed']) || !$_REQUEST['confirmed']) {
    		$deleteUrl = $this->createUrl(array('action' => 'delete','confirmed'=>1,'eventId' => $eventId));
    		$cancelUrl = $this->createUrl(array('action' => 'view'));

    		$output = $this->lang('really_delete_event',htmlspecialchars($event->get('summary')))."<br />";
    		$output .= "<a href='$deleteUrl'>[".$this->lang('yes_delete_event', htmlspecialchars($event->get('summary'))).']</a> ';
    		$output .= "<a href='$cancelUrl'>[".$this->lang('no_cancel').']</a>';
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
    		$this->reLoadCalendar();
            $output = $this->handleView();
    	}
    	return $output;
    }
    
    private function handleSave($eventId = NULL) {
    	//  TODO infoMessage("Why is '->save()' not done with CreateEvent()?");
    	//  TODO Validate input summary
        $event = NULL;
    	if ($eventId) $event = $this->calendar->getEvent($eventId); 
    	
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
    		$cleanTagName = $this->calendar->cleanTagName($tagName);

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
    			$event = $this->calendar->createEvent($e_fields,$addTags);
    			$saved = ($event!== false);
    		}


    		if ($saved) {
    			$this->info('saved_event',$event->get('summary'));
                $this->reLoadCalendar();
                return $this->handleView();
    		}
    		else {
    			$this->error('user','error_save_failed');
                return $this->handleShowForm($e_fields);
    		}
    	}
    	else { // Not valid
    		return $this->handleShowForm($e_fields);
    	}
    }

    private function showOutput() {
    	return $this->error_messages.$this->warning_messages.$this->info_messages.$this->output;
    }

    private function lang() {
    	// TODO Copy function from Gregorian class
    	$args = func_get_args();
    	// TODO Improve speed by only calling sprintf if there are some parameters to insert.
    	return call_user_func_array(array($this->calendar, 'lang'),$args);
    }

    private function error() {
    	// TODO implement proper error handling
    	$args = func_get_args();

    	// Error level is first argument
    	$level = array_shift($args);
    	$msg = call_user_func_array(array(&$this, 'lang'),$args);
    	if ($level == 'user') {
    		$this->error_messages = $msg;
    	}
    	else {
    		die("Error($level): $msg ");
    	}
    	 
    }

    private function warning() {
    	// TODO implement proper warning handling
    	$args = func_get_args();

        // Warning level is first argument
        $level = array_shift($args);
        $msg = call_user_func_array(array(&$this, 'lang'),$args);
        
    	$this->warning_messages .= "Warning($level): $msg <br />\n";
    }

    private function info() {
        // TODO implement proper warning handling
        $args = func_get_args();

        // Warning level is first argument
        $msg = call_user_func_array(array(&$this, 'lang'),$args);
        
        $this->info_messages .= "$msg <br />\n";
    }
    
    public function setDebug($debug=true) {
    	$this->debug = $debug;
    	$this->xpdo->setDebug($debug);
    	$this->xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);
    }
    
    /**
     * Create URL with parameters. Adds ? if not already there.
     */
    public function createUrl($params = array()) {
        $url = $this->calendar->getConfig('mainUrl');
        if ($this->debug) $params['debug'] = $this->debug;
        $params = array_merge($this->calendar->getPlaceholdersFromConfig(), $params);
        if (strpos($url,'?')===false) $url .= '?';
        foreach($params as $k => $v) {
            if ($v !== NULL)
            $url .= "&amp;$k=".urlencode($v);
        }
        return $url;
    }
}
