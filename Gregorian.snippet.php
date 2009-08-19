<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * TODO Save button in edit form doesn't work as desired.
 * TODO Create manager module
 * TODO Translate to danish (and make translateable)
 * TODO Update parameter list
 *  
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Snippet parameters:
 * calId        - Id number of calendar (set if more than one on site)  default: 1
 * 
 * adminGroup   - Name of webgroup that can edit calendar               default: ''
 * mgrIsAdmin   - All users logged in to the manager can edit calendar  default: 1
 * 
 * template     - Name of the template to use                           default: 'default'
 * lang         - Language code                                         default: 'en'
 * formatForICal- Format dates for iCal                                 default: 0
 * 
 * count        - Number of calendar items to show per page             default: 10
 * TODO view    - (option to show items in other ways than 'agenda' aka 'list')
 * 
 * AJAX-related. (Not fully implemented yet!)
 * ajaxId      - Id of the ajax processor document (the document with ajax=`1` snippet call) (default: 0)
 * ajax		   - This is the ajax-processor snippet call, (default: 0)
 * calId       - Id of the calendar-document, used in the ajax-processor snippet call. (default: 0)
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
if (!is_object($modx)) die("You shouldn't be here!");

// Load configuration
require_once ('config.php');

// Handle snippet configuration
$calId = 		(is_integer($calId)) 	? $calId			: 1;

$adminGroup =   (isset($adminGroup))    ? $adminGroup       : '';
$mgrIsAdmin =   (isset($mgrIsAdmin))    ?  $mgrIsAdmin      : true;

$template =     (isset($template)) 		? $template         : 'default';
$lang =         (isset($lang)) 			? $lang             : 'en';
if (isset($_REQUEST['lang']) && in_array($_REQUEST['lang'], array('da','en'))) 
    $lang = $_REQUEST['lang'];
$formatForICal =(isset($formatForICal)) ? $formatForICal    : 0;

$view =         (isset($view))          ? $view             : 'list';
$count =        (isset($count))         ? $count            : 10;

$ajax =         (isset($ajax))          ? $ajax             : false;
$ajaxId =       (isset($ajaxId))        ? $ajaxId           : NULL;
$calDoc =       (isset($calDoc))        ? $calDoc           : NULL;
$filter =       (isset($filter))        ? $filter           : array();

$isAdmin = ($mgrIsAdmin && $_SESSION['mgrValidated']) || ($adminGroup!='' && $modx->isMemberOfWebGroup(array($adminGroup)));

$snippetUrl = $modx->config['base_url'].'assets/snippets/Gregorian/';
$snippetDir = $_SERVER['DOCUMENT_ROOT'].$snippetUrl;

// Parse AJAX config and set $ajaxEnabled
if ($ajax) { // This is the ajax-processor snippet call
	// Check that the full calendar-doc is known
	if ($calDoc === NULL) {
		return "Snippet call with &ajax=`1` requires &calDoc to point to main calendar document";
	}

	// This is the ajax-processor
	$ajaxUrl = $modx->makeUrl($modx->documentIdentifier);
}
else {// this is the main doc snippet call
	if ($ajaxId > 0) {  // ajax-processor doc id
		$ajaxUrl = $modx->makeUrl($ajaxId);
	}
	else { // Ajax is off
		$ajaxUrl = NULL;
	}
	$calDoc = $modx->documentIdentifier;
}
$ajaxEnabled = ($ajaxUrl!==NULL);

// Load xPDO
$xpdo = new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
	array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('Gregorian', $snippetDir . 'model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

// Try to load or create calendar, if it fails, show error message.
global $calendar;
$calendar = $xpdo->getObject('Gregorian',$calId);
if ($calendar === NULL) {
	$calendar = $xpdo->newObject('Gregorian',$calId);
	$saved = $calendar->save();
	if ($calendar === NULL) {
		return 'Could not load or create calendar';
	}
	if (!$saved) {
		return 'Could not save newly created calendar!';
	}
}

// Set URLs
$calendar->setConfig('mainUrl', $modx->makeUrl($calDoc));
$calendar->setConfig('ajaxUrl', $ajaxUrl);
$calendar->setConfig('snippetDir',$snippetDir);

$calendar->setConfig('filter',$filter);
$calendar->loadLang($lang);

// Load template
$calendar->loadTemplate($snippetDir.'templates/template.'.$template.'.php');
// Set view preferences
$calendar->setConfig('count', $count);
$calendar->setConfig('formatForICal',$formatForICal);
// Set privileges
if ($isAdmin) $calendar->setConfig('isEditor');

/**
 * @todo Add required javascript (Could/should this be done by the class?)
 */
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
if ($ajaxEnabled) {
	$modx->regClientStartupScript('<script type="text/javascript">var ajaxUrl="'.$ajaxUrl.'"</script>',true);
	$modx->regClientStartupScript($snippetUrl.'Gregorian.ajax.js');
}
$modx->regClientStartupScript($snippetUrl.'Gregorian.view.js');
$modx->regClientCSS($snippetUrl.'layout.css');
$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');
// If ajax, add ajaxUrl to javascript namespace

// Handle request
$output = '';
global $errorMessages;
$errorMessages = array();
global $infoMessages;
$infoMessages = array();

// Set config variables from $_REQUEST
foreach ($calendar->_requestableConfigs as $key) {
	if (isset($_REQUEST[$key]))    $calendar->setConfig($key,    $_REQUEST[$key]);
}
$post_actions = array('save','savetag');
$request_actions = array('view','showform','tagform','delete');
$action = '';
if (isset($_POST['action']) && in_array($_POST['action'],$post_actions)) {
    $action = $_POST['action'];
}
elseif (isset($_REQUEST['action']) && in_array($_REQUEST['action'],$request_actions)) {
	$action = $_REQUEST['action'];
}
else {
	$action = 'view';
}

// Check privileges
if (!$calendar->getConfig('isEditor') && $action != 'view') {
    errorMessage('error_admin_priv_required', htmlspecialchars($action));
	$action = 'view';
}

// Load event if eventId is set and action is not 'view', handling depends of action
$event = NULL;
$eventId = $calendar->getConfig('eventId');
if ($eventId != NULL && $action != 'view') {
	$event = $calendar->getEvent($eventId);
}

// Fields with direct object<=>from relation
$fields = array('summary','description','location','allday');


// Action handling (Controller)
if ($action == 'savetag') {
	// Check if tag exists
	$tag = $xpdo->getObject('GregorianTag',array('tag'=>$_POST['tag']));
	
	if ($tag != NULL) { 
		infoMessage('tag_exists',$tag->get('tag'));
		$action = 'view';
	} else {
		// If not, create it
		$tag = $xpdo->newObject('GregorianTag',array('tag'=>$_POST['tag']));
		if ($tag == NULL) {
			errorMessage('error_couldnt_create_tag',$_POST[tag]);
			$action = 'tagform';
		} else {
			$tag->save();
			infoMessage('tag_created', $tag->get('tag'));
			$action = 'view';
		}
	}
}

if ($action == 'tagform') {
    $modx->regClientStartupScript($snippetUrl.'Gregorian.form.js');
    $output .= $calendar->replacePlaceholders(
        $calendar->_template['tagform'],
        array(
            'action'=>'savetag', 
            'formAction' => $calendar->createUrl(),
            'addTagText' => $calendar->lang('add_tag'),
            'tagNameText' => $calendar->lang('tag_name'),
            'saveText'   => $calendar->lang('save'),
            'resetText'  => $calendar->lang('reset')
        )
    );
}

if ($action == 'save') {
	//	TODO infoMessage("Why is '->save()' not done with CreateEvent()?");
	//	TODO Validate input (not empty summary, not empty start date)
	
	$e_fields = array();
	
	$saved = false;
	$valid = true;

	// Set event-values from $_POST
	foreach($fields as $field) {
		if (isset($_POST[$field])) 	$e_fields[$field] = $_POST[$field];
	}
	// Make allday boolean
	$e_fields['allday'] = isset($e_fields['allday']);
	
	// Check required fields, stop 'save' if they are not adequate
	// TODO Better date-validation
	// TODO Make validation on all fields, not just required, perhaps with xPDO's built in validation-features
	if (!isset($_POST['dtstart']) || $_POST['dtstart'] == '') {
		errorMessage('error_startdate_required');
		$valid = false;
	}
	if (!isset($_POST['summary']) || $_POST['summary'] == '') {
		errorMessage('error_summary_required');
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
		errorMessage('error_start_date_after_end_date');
		$valid = false;
	}	
	
	// Add/remove tags
	// echo "<pre>".print_r($_POST,1)."</pre>";
	$all_tags = $calendar->xpdo->getCollection('GregorianTag');
	
	if (is_object($event))	$tags = $event->getTags();
	else					$tags = array();
	
	foreach ($all_tags as $tag) {
		$tagName = $tag->get('tag');
		$cleanTagName = $calendar->cleanTagName($tagName);

		if ($_POST[$cleanTagName]) {
			if (!in_array($tagName,$tags)) $addTags[] = $tagName;
		}
		else
		{
			if (in_array($tagName,$tags)) {
				$tagId = $tag->get('id');
				$calendar->xpdo->removeObject('GregorianEventTag',array('tag'=>$tagId,'event'=>$eventId));
			}
		}
	}
	
	// TODO Add more validation here (dtstart < dtend, ???
	if ($valid) {
		// Save edited event / Create event
		if (is_object($event)) {
			$event->fromArray($e_fields);
			$event->addTag($addTags);
			$saved = $event->save();
		}
		else {
			$event = $calendar->createEvent($e_fields,$addTags);
			$saved = ($event!== false);
		}


		if ($saved) {
			infoMessage('saved_event',$event->get('summary'));
			$reloadCal = true;
			$action = 'view';
		}
		else {
			errorMessage('error_save_failed');
			$action = 'showform';
		}
	}
	else { // Not valid
		$action = 'showform';	
	}
} // action 'save'

if ($action == 'showform') {
	$modx->regClientStartupScript($snippetUrl.'Gregorian.form.js');
	$gridLoaded = false;
	$e_ph = array_flip($fields);

	if (isset($e_fields)) { 	
		$e_ph = $e_fields;
		$gridLoaded = true;
		// TODO Export this to a function
        $e_ph['dtstart'] = substr($e_fields['dtstart'],0,10);
        $e_ph['dtend'] = substr($e_fields['dtend'],0,10);
        if ($e_ph['allday']) {
            $e_ph['tmstart'] = '';
            $e_ph['tmend'] = '';
        }
        else {
            // Time but not seconds
            $e_ph['tmstart'] = substr($e_fields['dtstart'],11,5);
            $e_ph['tmend'] = substr($e_fields['dtend'],11,5);
        }
	}
	elseif (is_object($event)) {
		// Populate placeholders if editing event
		$e_ph = $event->get($fields);
		foreach ($e_ph as $key => $value) $e_ph[$key] = $value;
		
        // TODO Export this to a function
		$e_ph['dtstart'] = substr($event->get('dtstart'),0,10);
		$e_ph['dtend'] = substr($event->get('dtend'),0,10);
		if ($e_ph['allday']) {
			$e_ph['tmstart'] = '';
			$e_ph['tmend'] = '';
		}
		else {
			// Time but not seconds
			$e_ph['tmstart'] = substr($event->get('dtstart'),11,5);
			$e_ph['tmend'] = substr($event->get('dtend'),11,5);
		}
		
		$e_tags = $event->getMany('Tags');
		$e_tag_ids = array();
		foreach ($e_tags as $tag) {
			$e_tag_ids[] = $tag->get('tag');
		}
		$gridLoaded = true;
	}
	elseif ($eventId) {
		errorMessage('error_event_doesnt_exist',$eventId);
		$reloadCal = true;
		$action = 'view';
	}					
	else {	
		// Start with default form if new event
		foreach($fields as $field) 
			$e_ph[$field] = '';
		$e_ph['allday']=true;
		$gridLoaded = true;
	}

	// Show form if new event, or event loaded successfully.
	if ($gridLoaded) {
		// If any $field is set in $_POST, set it in form
		foreach($fields as $field) {
			if (isset($_POST[$field])) {
				$e_ph[$field] = $_POST[$field];
			}
		}

		$e_ph['action'] = 'save';
		$e_ph['formAction'] = $calendar->createUrl();
		$e_ph = array_merge($e_ph, $calendar->getPlaceholdersFromConfig());

		$e_ph['allday'] = ($e_ph['allday']) ? 'checked="yes"' : '';
		$tags = $calendar->xpdo->getCollection('GregorianTag');
		$e_ph['tagCheckboxes'] = '';
		foreach ($tags as $tag) {
			$tagName = $tag->get('tag');
			if ($e_tag_ids != NULL && in_array($tag->get('id'),$e_tag_ids)) {
				$checked = 'checked="yes"';
			}
			else {
				$checked = '';
			}

			// Clean up tag name
			$cleanTagName = $calendar->cleanTagName($tagName);

			$e_ph['tagCheckboxes'] .= $calendar->replacePlaceholders($calendar->_template['formCheckbox'], array('name'=>$cleanTagName,'label'=>$tagName,'checked'=>$checked));
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
        	$e_ph[$key] = $calendar->lang($val);
        }

		$output = $calendar->replacePlaceholders($calendar->_template['form'], $e_ph);
	}
} // action 'showform'

if ($action == 'delete') {
	if ($calendar->getConfig('confirmDelete') && (!isset($_POST['confirmed']) || !$_POST['confirmed'])) {
		$deleteUrl = $calendar->createUrl(array('action' => 'delete','confirmed'=>1));
		$cancelUrl = $calendar->createUrl(array('action' => 'view'));

		/**
		 * @todo This should be templateable
		 */
		$output = $calendar->lang('really_delete_event',htmlspecialchars($event->get('summary')))."<br />";
		$output .= "<a href='$deleteUrl'>[".$calendar->lang('yes_delete_event', htmlspecialchars($event->get('summary'))).']</a> ';
		$output .= "<a href='$cancelUrl'>[".$calendar->lang('no_cancel').']</a>';
	}
	else {
		$deleted = false;
		if (is_object($event)) {
			$summary = $event->get('summary');
			$deleted = $event->remove();
		} 

		if ($deleted) {
			infoMessage('event_deleted',$summary);
		}
		else
		{
			errorMessage('error_delete_failed');
		}

		$reloadCal = true;
		$action = 'view';
	}
} // action 'delete'

if ($action == 'view') {
	$cal = NULL;
	if (isset($reloadCal) && $reloadCal === true) {
		$cal = $calendar->xpdo->getObject('Gregorian',$calendar->get('id'));
		if ($cal === NULL) {						
			errorMessage('Could not load calendar with id '.$calendar->get('id'));
		}
		else {
			// Copy config & template
			$cal->setConfig($calendar->getConfig());
			$cal->loadTemplate($calendar->_template);
			$cal->loadLang($lang);
		}
	}
	else {
		$cal = &$calendar;
	}

	if ($cal != NULL) {
		// Default event view if startdate not set
		$cal->getFutureEvents();

		// Render calender
		$output .= $cal->renderCalendar();
	}
} // action 'view'

if ($action != 'view') $cal = &$calendar;

// Format messages
// TODO Use templates for this
// TODO Perhaps errors should be handled by a separate class? Or by xPDO's error handling?
$messages = '';
if (sizeof($errorMessages) > 0) {
	$messages .= '<div class="ui-state-error">'.implode('<br />', $errorMessages).'</div>';
}
if (sizeof($infoMessages) > 0) {
	$messages .= '<div id="ui-state-highlight">'.implode('<br />', $infoMessages).'</div>';
}

return $messages.$output;

function errorMessage() {
	global $errorMessages,$calendar;
    $args = func_get_args();
    $errorMessages[] = call_user_func_array(array(&$calendar, 'lang'),$args);
}

function infoMessage() {
    global $infoMessages,$calendar;
    $args = func_get_args();
    $infoMessages[] = call_user_func_array(array(&$calendar, 'lang'),$args);
}

