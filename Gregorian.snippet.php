<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Snippet parameters:
 * calId        - Database id of calendar (set if more than one on site)  default: 1
 * 
 * adminGroup   - Name of webgroup that can edit calendar                 default: ''
 * mgrIsAdmin   - All users logged in to the manager can edit calendar    default: 1
 * 
 * template     - Name of the template to use                             default: 'default'
 * lang         - 2 letter language code                                  default: 'en'
 * 
 * perPage      - Number of calendar items to show per page               default: 10
 * offset       - Number of items to skip from the beginning              default: 0
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


// Init controller
$gc =  new GregorianController(&$modx, &$xpdo);

// Set snippet configuration
if (is_integer($calId)) $gc->set('calId',       $calId);
if (isset($adminGroup)) $gc->set('adminGroup',  $adminGroup);
if (isset($mgrIsAdmin)) $gc->set('mgrIsAdmin',  $mgrIsAdmin);
if (isset($template))   $gc->set('template',    $template);
if (isset($lang))       $gc->set('lang',        $lang);
if (isset($view))       $gc->set('view',        $view);
if (isset($perPage))    $gc->set('perPage',     $perPage);
if (isset($offset))     $gc->set('offset',      $offset);
if (isset($filter))     $gc->set('filter',      $filter);
if ($debug)             $gc->set('debug');

return $gc->handle();











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
	if ($_POST['dtend'] == '')  $e_fields['dtend'] = $e_fields['dtstart'];
	else                           $e_fields['dtend'] = $_POST['dtend'];
	
	if (!$e_fields['allday']) {
		$e_fields['dtstart'] .= ' '. $_POST['tmstart'];
		$e_fields['dtend'] .= ' '. $_POST['tmend'];
	}
	
	if ($valid && strtotime($e_fields['dtstart']) > strtotime($e_fields['dtend'])) {
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
		if ($cal->getConfig('startdate')==NULL)	$cal->getFutureEvents();
		
		else 									$cal->getEventsByTimeInterval(); // Get events using config

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
