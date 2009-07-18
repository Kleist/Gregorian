<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * TODO - list
 *
 * Functionality
 * - Filter by tag
 * - Search
 * - Create event
 * - Copy event
 * - Add tagging to form
 * - Other means of authorizing editors (Special MODx-document for editing, by web-user, by web-group)
 * 
 * UI
 * - AJAX - inline edit
 * - Show by months
 * - Enhance form
 * - Date-picker
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Snippet parameters:
 *
 * allCanEdit - Allow any user to edit the calendar (default: 0)
 * mgrIsAdmin - All users logged in to the manager can edit calendar (default: 1)
 * count      - Number of calendar items to show per page (default: 5)
 * ajax		  - This is the ajax-processor snippet call
 * ajaxId     - Id of document with ajax=`1` snippet call
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
if (!is_object($modx)) die("You shouldn't be here!");

// Load configuration
require_once ('config.php');

// Handle snippet configuration
$calId = 		(is_integer($calId)) 	? $calId			: 1;
$template = 	(isset($template)) 		? $template 		: 'default';
$view = 		(isset($view)) 			? $view 			: 'list';
$lang = 		(isset($lang)) 			? $lang 			: 'en';
$allCanEdit = 	(isset($allCanEdit)) 	? $allCanEdit 		: false;
$mgrIsAdmin = 	(isset($mgrIsNotAdmin)) ? !$mgrIsNotAdmin 	: 1;
$showPerPage = 	(isset($showPerPage)) 	? $showPerPage 		: 10;
$ajax = 		(isset($ajax)) 			? $ajax 			: false;
$ajaxId = 		(isset($ajaxId)) 		? $ajaxId 			: NULL;
$calDoc = 		(isset($calDoc)) 		? $calDoc 			: NULL;

$isAdmin = ($mgrIsAdmin && $_SESSION['mgrValidated']);

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
	if ($ajaxId !== NULL) {  // ajax-processor doc id
		$ajaxUrl = $modx->makeUrl($ajaxId);
	}
	else { // Ajax is off
		$ajaxUrl = NULL;
	}
	$calDoc = $modx->documentIdentifier;
}
$ajaxEnabled = ($ajaxUrl!==NULL);

// Load xPDO
$xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
	array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('Gregorian', XPDO_CORE_PATH . '../model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

// Try to load or create calendar, if it fails, show error message.
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

// Load template
$calendar->loadTemplate($snippetDir.'template.'.$template.'.php');
// View preferences
$calendar->setConfig('count',   $count);

// Set privileges
if ($isAdmin) $calendar->setConfig('isEditor');

/**
 * @todo Add required javascript (Could/should this be done by the class?)
 */
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
$modx->regClientStartupScript($snippetUrl.'Gregorian.view.js');
$modx->regClientCSS($snippetUrl.'layout.css');
$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');

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
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'view';

// Check privileges
if (!$calendar->getConfig('isEditor') && $action != 'view') {
	errorMessage("Admin priveleges required for action: ".htmlspecialchars($action));
	$action = 'view';
}

// Load event if eventId is set and action is not 'view', handling depends of action
$event = NULL;
$eventId = $calendar->getConfig('eventId');
if ($eventId != NULL && $action != 'view') {
	$event = $calendar->getEventById($eventId);
	if ($event== NULL) infoMessage("Event=null, eventId=$eventId");
}

// Fields with direct object<=>from relation
$fields = array('summary','description','location','allday');


// Action handling (Controller)
if ($action == 'save') {
	infoMessage("Why is '->save()' not done with CreateEvent()?");
	$saved = false;
	// If no event is loaded
	if (!is_object($event)) {
		$event = $calendar->xpdo->newObject('GregorianEvent');
		$calendar->addMany($event);
	}

	// Set event-values from $_REQUEST
	foreach($fields as $field) {
		if ($field == 'allday') {
			$event->set('allday',$_REQUEST['allday'] == 'allday');
		}
		else {
			if (isset($_REQUEST[$field])) {
				$event->set($field, $_REQUEST[$field]);
			}
		}
	}
	
	// Create datetime for start and end, append time if not allday
	$dtstart = $_REQUEST['dtstart'];
	if ($_REQUEST['dtend'] != '')  $dtend = $_REQUEST['dtend'];
	if (!$event->get('allday')) {
		$dtstart = $dtstart .' '. $_REQUEST['tmstart'];
		$dtend = $dtend .' '. $_REQUEST['tmend'];
	}

	$event->set('dtstart', $dtstart);
	$event->set('dtend', $dtend);

	// Add/remove tags
	$all_tags = $calendar->xpdo->getCollection('GregorianTag');
	$tags = $event->getTags();
	foreach ($all_tags as $tag) {
		$tagName = $tag->get('tag');
		$cleanTagName = $calendar->cleanTagName($tagName);
		$tagId = $tag->get('id');
		// echo "tagName: $tagName, tagId: $tagId<br />";
		if ($_REQUEST[$cleanTagName] == $cleanTagName) {
			if (!in_array($tagName,$tags)) $event->addTag($tagName);
		}
		else
		{
			if (in_array($tagName,$tags)) {
				$calendar->xpdo->removeObject('GregorianEventTag',array('tag'=>$tagId,'event'=>$eventId));
			}
		}
	}
	
	/**
	 * @todo Add validation here
	 */

	$saved = $event->save();
	if ($saved) {
		infoMessage($calendar->lang('Saved event '.$event->get('id').' in calendar '.$calendar->get('id')));
		$reloadCal = true;
		$action = 'view';
	}
	else {
		errorMessage($calendar->lang('Save failed'));
		$action = 'showform';
	}
} // action 'save'

if ($action == 'showform') {
	$e_ph = array_flip($fields);
	$gridLoaded = false;

	if (is_object($event)) {
		// Populate placeholders if editing event
		$e_ph = $event->get($fields);
		$e_ph['dtstart'] = substr($event->get('dtstart'),0,10);
		$e_ph['dtend'] = substr($event->get('dtend'),0,10);
		if ($e_ph['allday']) {
			$e_ph['tmstart'] = '';
			$e_ph['tmend'] = '';
		}
		else {
			// Time but not seconds
			$e_ph['tmstart'] = substr($event->get('dtstart'),11,-3);
			$e_ph['tmend'] = substr($event->get('dtend'),11,-3);

		}
		
		$e_tags = $event->getMany('Tags');
		$e_tag_ids = array();
		foreach ($e_tags as $tag) {
			$e_tag_ids[] = $tag->get('tag');
		}
		$gridLoaded = true;
	}
	elseif ($eventId) {
		errorMessage($calendar->lang("The event with id %d, that you're trying to edit does not exist.",$eventId));
		$reloadCal = true;
		$action = 'view';
	}					
	else {	
		// Start with empty form if new event
		foreach($fields as $field) 
			$e_ph[$field] = '';
		$gridLoaded = true;
	}

	// Show form if new event, or event loaded successfully.
	if ($gridLoaded) {
		// If any $field is set in $_REQUEST, set it in form
		foreach($fields as $field) {
			if (isset($_REQUEST[$field])) {
				$e_ph[$field] = $_REQUEST[$field];
			}
		}

		$e_ph['action'] = 'save';
		$e_ph['formAction'] = $calendar->createUrl();
		$e_ph = array_merge($e_ph, $calendar->getPlaceholdersFromConfig());

		$e_ph['allday'] = ($e_ph['allday']==1) ? 'checked="yes"' : '';
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

		$output = $calendar->replacePlaceholders($calendar->_template['form'], $e_ph);
	}
} // action 'showform'

if ($action == 'delete') {
	if ($calendar->getConfig('confirmDelete') && (!isset($_REQUEST['confirmed']) || !$_REQUEST['confirmed'])) {
		$deleteUrl = $calendar->createUrl(array('action' => 'delete','confirmed'=>1));
		$cancelUrl = $calendar->createUrl(array('action' => 'view'));

		/**
		 * @todo This should be templateable
		 */
		$output = $calendar->lang('Do you really want to delete the event "'.htmlspecialchars($event->get('summary')).'"?<br />');
		$output .= "<a href='$deleteUrl'>[".$calendar->lang('Yes, delete event"', htmlspecialchars($event->get('summary'))).']</a> ';
		$output .= "<a href='$cancelUrl'>[".$calendar->lang('No, cancel').']</a>';
	}
	else {
		$deleted = false;
		if (is_object($event)) {
			$summary = $event->get('summary');
			$deleted = $event->remove();
		} 

		if ($deleted) {
			infoMessage($calendar->lang('Event "%s" deleted',$summary));
		}
		else
		{
			errorMessage($calendar->lang('Delete failed'));
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
			errorMessage($calendar->lang('Could not load calendar with id '.$calendar->get('id')));
		}
		else {
			// Copy config & template
			$cal->setConfig($calendar->getConfig());
			$cal->loadTemplate($calendar->_template);
		}
	}
	else {
		$cal = &$calendar;
	}

	if ($cal != NULL) {
		// Default event view if startdate not set
		if ($cal->getConfig('startdate')==NULL)
			$cal->getFutureEvents();
		
		else { // Get events using config
			$cal->getEventsByTimeInterval();
		}

		// Render calender
		$output .= $cal->renderCalendar();
	}
} // action 'view'
if ($action != 'view') $cal = &$calendar;
return implode('<br />', $errorMessages).'<br />'.implode('<br />', $infoMessages).$output;

function errorMessage($message) {
	global $errorMessages;
	if (is_array($message)) {
		array_merge($errorMessages, $message);
	}
	else {
		$errorMessages[] = $message;
	}
}

function infoMessage($message) {
	global $infoMessages;
	if (is_array($message)) {
		array_merge($infoMessages, $message);
	}
	else {
		$infoMessages[] = $message;
	}
}
