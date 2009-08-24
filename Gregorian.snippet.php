<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Snippet parameters:
 * calId        - Database id of calendar (set if more than one on site)  default: 1
 * 
 * adminGroup   - Name of webgroup that can edit calendar               default: ''
 * mgrIsAdmin   - All users logged in to the manager can edit calendar  default: 1
 * allowAddTag  - Should Editors be able to add new tags?               default: 1
 * 
 * template     - Name of the template to use                           default: 'default'
 * lang         - Language code                                         default: 'en'
 * formatForICal- Format dates for iCal                                 default: 0
 * 
 * count        - Number of calendar items to show per page             default: 10
 * TODO view    - (option to show items in other ways than 'agenda' aka 'list')
 * 
 * view         - Currenty only 'agenda' is available                     default: 'agenda'
 * 
 * AJAX-related. (Not implemented!)
 * ajaxId      - Id of the ajax processor document (the document with ajax=`1` snippet call) (default: 0)
 * ajax		   - This is the ajax-processor snippet call, (default: 0)
 * calId       - Id of the calendar-document, used in the ajax-processor snippet call. (default: 0)
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
if (!is_object($modx)) die("You shouldn't be here!");

require_once ('config.php');
require_once('GregorianController.class.php');

// Load xPDO
$xpdo = new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
    array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));

$adminGroup =   (isset($adminGroup))    ? $adminGroup       : '';
$mgrIsAdmin =   (isset($mgrIsAdmin))    ?  $mgrIsAdmin      : true;

// Init controller
$gc =  new GregorianController(&$modx, &$xpdo);

// Set snippet configuration
if (is_integer($calId)) $gc->set('calId',       $calId);
if (isset($adminGroup)) $gc->set('adminGroup',  $adminGroup);
if (isset($mgrIsAdmin)) $gc->set('mgrIsAdmin',  $mgrIsAdmin);
if (isset($template))   $gc->set('template',    $template);
if (isset($lang))       $gc->set('lang',        $lang);
if (isset($view))       $gc->set('view',        $view);
if (isset($count))      $gc->set('count',       $count);
if (isset($offset))     $gc->set('offset',      $offset);
if (isset($filter))     $gc->set('filter',      $filter);
if (isset($snippetUrl)) $gc->set('snippetUrl',  $snippetUrl);
if ($debug)             $gc->setDebug();

//$allowAddTag =  (isset($allowAddTag))   ?  $allowAddTag     : false;
//$calendar->setConfig('allowAddTag',$allowAddTag);
//$calendar->setConfig('formatForICal',$formatForICal);

return $gc->handle();

// Load event if eventId is set and action is not 'view', handling depends of action
$event = NULL;
$eventId = $calendar->getConfig('eventId');
if ($eventId != NULL && $action != 'view') {
	$event = $calendar->getEvent($eventId);
}

// Fields with direct object<=>from relation
$fields = array('summary','description','location','allday');


if ($action == 'save') {
	//	TODO infoMessage("Why is '->save()' not done with CreateEvent()?");
	//	TODO Validate input (not empty summary, not empty start date)
	
	$e_fields = array();
	
	$saved = false;
	$valid = true;

	// Set event-values from $_POST
	foreach($fields as $field) {
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
