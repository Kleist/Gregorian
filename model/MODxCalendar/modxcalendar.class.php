<?php

class MODxCalendar extends xPDOSimpleObject {
	
	public $keepers = array('startdate','enddate','count','offset');
	/**
	 * @var array Array of fetched event-objects
	 */
	private $_events = NULL;
	
	private $_config = array(
		'startdate' => NULL, 
		'enddate' => NULL, 
		'count' => 10, 
		'offset' => 0, 
		'eventId' => NULL,
		'isEditor' => false,
		'mainUrl' => '',
		'ajaxUrl' => NULL
	);
	
	private $_requestableConfigs = array('eventId','startdate','enddate','count','offset');
	
	private $_template = NULL;
	
	/**
	 * Messages for the user, typically shown in a popup, or in a designated message area.
	 */
	private $_infoMessages = array();
	private $_errorMessages = array();
	
	
	// Configuration
	private $_dateFormat = '%a %e. %b.';
	private $_timeFormat = '%H:%M';
	
	function MODxCalendar(& $xpdo) {
		$this->__construct($xpdo);
	}
	
	function __construct(& $xpdo) {
		parent :: __construct($xpdo);
	}

	/**
  	 * Adds event to calendar, and saves changes to database.
	 * @param array $fields An array of fields to set in the event. i.e. array('summary'=>'Christmas Eve','dtstart'=> '2009-12-24 24:00')
	 * @param array $tags An array of tags to set on event. i.e. array('Tag1','Tag2')
	 * @return Returns true on success, false on failure.
	 */	
	public function createEvent($fields=array(),$tagnames=array()) {
		$created = false;

		if (!is_array($fields) || !isset($fields['summary']) || !isset($fields['dtstart'])) {
			$this->error("'summary' and 'dtstart' required!\n",__FILE__,__LINE__);
			return false;
		}
		
		$event = $this->xpdo->newObject('MODxCalendarEvent');
		
		// Set fields
		foreach ($fields as $field => $value) {
			// @todo Perhaps I should add some input-handling here, or does it belong on a higher level?
			$event->set($field,$value);
		}

		// Add tags
		if (is_array($tagnames) && !empty($tagnames)) {
			foreach ($tagnames as $name) {
				$event->addTag($name);
			}
		}
		$this->xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

		// Add event object to calendar object
		if (!($added = $this->addMany($event))) {	
			$this->error("Couldn't add event to calendar!\n",__FILE__,__LINE__);
			return false;
		}
		
		// Save changes to database
		if (!($saved = $this->save()))
			$this->error("Couldn't save calendar to database!\n",__FILE__,__LINE__);

		return $saved;
	}
	
	/**
	 * Fetches all events from db using $criteria, returns _events if it has already been fetched, regardless of $criteria.
	 *
	 * @param string $criteria Criteria for selecting events
	 * @return array Array of MODxCalendarEvent-objects
	 */
	public function getEvents($criteria=NULL) {
		$this->_events = $this->getMany('Events',$criteria);
		return $this->_events;
	}
	
	public function getEventById($id) {
		if (is_array($events = $this->getMany('Events',$id)))
			return $events[$id];
		else
			return $events;
			
	}
	
	/**
	 * Fetch events in a time interval
	 *
	 * @todo Handle negative $offsets better
	 * @param string|integer $start Start of interval in either unixtime or MySQL date format
	 * @param string $end End of interval in same format as $start
	 * @param integer $count Number of events to fetch
	 * @param integer $offset Number of events to offset SQL-results (events to skip from beginning of result without limit)
	 * @return array Array of Event-objects
	 */
	public function getEventsByTimeInterval($start = '', $end = '',$count = '', $offset = '') {
		if (is_numeric($start)) {
			$this->setConfig('startdate', date('Y-m-d H:i',$start));
		}
		elseif ($start != '') {
			 $this->setConfig('startdate', $start);
		}

		if (is_numeric($end)) {
			$this->setConfig('enddate', date('Y-m-d H:i',$end));
		}
		elseif ($end != '') {
			$this->setConfig('enddate', $end);
		}

		if (is_numeric($offset) && $offset > 0) $this->setConfig('offset',$offset);
		else $offset = $this->getConfig('offset');
		
		if (is_numeric($count) && $count > 0) $this->setConfig('count',$count);
		else $count = $this->getConfig('count');

		// Build query
		$query = $this->xpdo->newQuery('MODxCalendarEvent');
		$query->where(array("MODxCalendarEvent.dtstart:>" => $this->getConfig('startdate')),NULL,0);
		if ($this->getConfig('enddate') !== NULL) 
			$query->andCondition(array('MODxCalendarEvent.dtstart:<'=>$this->getConfig('enddate')),NULL,0);

		$query->orCondition(array('MODxCalendarEvent.dtend:>' => $this->getConfig('startdate')));
		if ($this->getConfig('enddate') !== NULL) 
			$query->andCondition(array('MODxCalendarEvent.dtstart:<' => $this->getConfig('enddate')));

		$query->sortBy('MODxCalendarEvent.dtstart');
		
		$this->totalCount = $this->xpdo->getCount('MODxCalendarEvent',$query);
		
		// Check if current page if empty
		if ($this->totalCount <= $offset) {
			$offset = $offset - $count;
			$this->setConfig('offset', $offset);
			// $this->infoMessage('Moved you back to previous page since totalcount '.$this->totalCount." is smaller than $offset");
		}
		

		if (is_numeric($this->getConfig('count')))
			$query->limit($this->getConfig('count'),$this->getConfig('offset'));
		
		$query->prepare();
		// echo $query->toSQL()."<br /\n>";
		return $this->getEvents($query);
	}
	
	public function getFutureEvents($count = '', $offset = '') {
		return $this->getEventsByTimeInterval(date('Y-m-d H:i'), '', $count, $offset);
	}

	/**
	 * The calendars main entry-point, handles requests for view, save, delete, etc.
	 */
	public function handleRequest() {		
		$output = '';
		// Set config variables from $_REQUEST
		foreach ($this->_requestableConfigs as $key) {
			if (isset($_REQUEST[$key]))    $this->setConfig($key,    $_REQUEST[$key]);
		}
		$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'view';
		
		// Check privileges
		if (!$this->getConfig('isEditor') && $action != 'view') {
			$this->errorMessage($this->lang("Admin priviveleges required for action: %s",htmlspecialchars($action)));
			$action = 'view';
		}
		
		// Load event if eventId is set and action is not 'view', handling depends of action
		$event = NULL;
		$eventId = $this->getConfig('eventId');
		if ($eventId != NULL && $action != 'view') {
			$event = $this->getEventById($eventId);
		}
		
		// Form & object fields
		$fields = array('summary','description','dtstart','dtend','location','allday');

		if ($action == 'save') {
			$saved = false;
			// If no event is loaded
			if (!is_object($event)) {
				$event = $this->xpdo->newObject('MODxCalendarEvent');
				$this->addMany($event);
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

			// Set tags
			$all_tags = $this->xpdo->getCollection('MODxCalendarTag');
			$tags = $event->getTags();
			// echo "<pre>".print_r($_REQUEST,1)."</pre>";
			foreach ($all_tags as $tag) {
				$tagName = $tag->get('tag');
				$cleanTagName = $this->cleanTagName($tagName);
				$tagId = $tag->get('id');
				// echo "tagName: $tagName, tagId: $tagId<br />";
				if ($_REQUEST[$cleanTagName] == $cleanTagName) {
					if (!in_array($tagName,$tags)) $event->addTag($tagName);
				}
				else
				{
					if (in_array($tagName,$tags)) {
						$this->xpdo->removeObject('MODxCalendarEventTag',array('tag'=>$tagId,'event'=>$eventId));
					}
				}
			}
			
			/**
			 * @todo Add validation here
			 */

			$saved = $event->save();
			if ($saved) {
				$this->infoMessage($this->lang('Saved event '.$event->get('id').' in calendar '.$this->get('id')));
				$reloadCal = true;
				$action = 'view';
			}
			else {
				$this->errorMessage($this->lang('Save failed'));
				$action = 'showform';
			}
		} // action 'save'
		
		if ($action == 'showform') {
			$e_ph = array_flip($fields);
			$gridLoaded = false;

			if (is_object($event)) {
				// Populate placeholders if editing event
				$e_ph = $event->get($fields);
				$e_tags = $event->getMany('Tags');
				$e_tag_ids = array();
				foreach ($e_tags as $tag) {
					$e_tag_ids[] = $tag->get('tag');
				}
				$gridLoaded = true;
			}
			elseif ($eventId) {
				$this->errorMessage($this->lang("The event with id %d, that you're trying to edit does not exist.",$eventId));
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
				$e_ph['formAction'] = $this->createUrl();
				$e_ph = array_merge($e_ph, $this->getPlaceholdersFromConfig());

				$e_ph['allday'] = ($e_ph['allday']==1) ? 'checked="yes"' : '';
				$tags = $this->xpdo->getCollection('MODxCalendarTag');
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
					$cleanTagName = $this->cleanTagName($tagName);

					$e_ph['tagCheckboxes'] .= $this->replacePlaceholders($this->_template['formCheckbox'], array('name'=>$cleanTagName,'label'=>$tagName,'checked'=>$checked));
				}

				$output = $this->replacePlaceholders($this->_template['form'], $e_ph);
				// foreach ($e_tags as $tag) {
				// 	$output .= $tag->getOne('Tag')->get('tag');
				// }
			}
		} // action 'showform'
		
		if ($action == 'delete') {
			if ($this->getConfig('confirmDelete') && (!isset($_REQUEST['confirmed']) || !$_REQUEST['confirmed'])) {
				$deleteUrl = $this->createUrl(array('action' => 'delete','confirmed'=>1));
				$cancelUrl = $this->createUrl(array('action' => 'view'));

				/**
				 * @todo This should be templateable
				 */
				$output = $this->lang('Do you really want to delete the event "'.htmlspecialchars($event->get('summary')).'"?<br />');
				$output .= "<a href='$deleteUrl'>[".$this->lang('Yes, delete event"', htmlspecialchars($event->get('summary'))).']</a> ';
				$output .= "<a href='$cancelUrl'>[".$this->lang('No, cancel').']</a>';
			}
			else {
				$deleted = false;
				if (is_object($event)) {
					$summary = $event->get('summary');
					$deleted = $event->remove();
				} 

				if ($deleted) {
					$this->infoMessage($this->lang('Event "%s" deleted',$summary));
				}
				else
				{
					$this->errorMessage($this->lang('Delete failed'));
				}

				$reloadCal = true;
				$action = 'view';
			}
		} // action 'delete'
		
		if ($action == 'view') {
			$cal = NULL;
			if (isset($reloadCal) && $reloadCal === true) {
				$cal = $this->xpdo->getObject('MODxCalendar',$this->get('id'));
				if ($cal === NULL) {						
					$this->errorMessage($this->lang('Could not load calendar with id '.$this->get('id')));
				}
				else {
					// Copy config & template
					$cal->setConfig($this->getConfig());
					$cal->loadTemplate($this->_template);
					$cal->infoMessage($this->_infoMessages);
					$cal->errorMessage($this->_infoMessages);
				}
			}
			else {
				$cal = &$this;
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
		if ($action != 'view') $cal = &$this;
		return implode('<br />', $cal->_errorMessages).implode('<br />', $cal->_infoMessages).$output;

	}

	public function loadTemplate($template = '') {
		if ($template=='') {
			die('Default template loading has not been implemented yet');
		}
		elseif (is_string($template)) {
			$this->_template = require($template);
		}
		else {
			$this->_template = $template;
		}
	}

	public function renderCalendar() {
		if ($this->_events === NULL || sizeof($this->_events) == 0) {
			return $this->lang('No calendar entries found');
		}
		
		if ($this->_template === NULL) {
			$this->loadTemplate();			
		}
			
		$lastdate = '';
		$events = '';
		$days = '';
		$oddeven = 'even';
		$calId = $this->get('id');

		if ($this->getConfig('isEditor')) {
			$createUrl = $this->createUrl(array('action'=>'showform','eventId'=>NULL));
			$createLink = $this->replacePlaceholders($this->_template['createLink'], array('createUrl'=> $createUrl));
		}
		else {
			$createLink = '';
		}

		foreach ($this->_events as $event) {
			$f = $this->formatDateTime($event);

			// If new date and not first event, render day, (uses date-placeholder from above)
			if ($f['startdate'] != $lastdate && $lastdate != '') {
				$ph = array('dayclass' => $oddeven,
					'events' => $events,
					'date' => $lastdate);
				$days .= $this->replacePlaceholders($this->_template['day'], $ph);
				$events = '';
				$oddeven = ($oddeven=='even')? 'odd' : 'even';
			}
			$lastdate = $f['startdate'];
			
			// Parse tags
			$tagArray = $event->getTags();
			$tags = '';
			if (is_array($tagArray)) {
				foreach ($tagArray as $tag) {
					$tags.= $this->replacePlaceholders($this->_template['tag'],array('tag'=>$tag));
				}
			}
			
			// create event-placeholder
			$e_ph = $event->get(array('summary','description','id'));
			$e_ph = array_merge($e_ph,$f);
			$e_ph['tags'] = $tags;

			// Parse editor
			if ($this->getConfig('isEditor')) {
				$editUrl = $this->createUrl(array('action'=>'showform', 'eventId'=>$event->get('id')));
				$deleteUrl = $this->createUrl(array('action'=>'delete', 'eventId'=>$event->get('id')));

				$e_ph['editor'] = $this->replacePlaceholders($this->_template['editor'], array('editUrl' => $editUrl,'deleteUrl' =>$deleteUrl));
			}
			else {
				$e_ph['editor'] = '';
			}
			
			// Render event from template
			$events .= $this->replacePlaceholders($this->_template['event'],$e_ph);
		}
		// Wrap last event(s) in day-template
		$days .= $this->replacePlaceholders($this->_template['day'], 
			array('dayclass' => $oddeven,
				'events' => $events,
				'date' => $lastdate
			)
		);
		
		// Render navigation
		$navigation = $this->renderNavigation();

		// Wrap days in overall template
		return $this->replacePlaceholders($this->_template['wrap'],array('days'=>$days, 'navigation' => $navigation, 'createLink' => $createLink));
	}
	
	public function renderNavigation() {
		$offset = $this->getConfig('offset');
		$count = $this->getConfig('count');
		$nextOffset = $offset+$count;
		if ($this->totalCount > $nextOffset) {
			$nextUrl = $this->createUrl(array('offset' => $nextOffset));
						
			$next = $this->replacePlaceholders($this->_template['nextNavigation'], 
				array('nextUrl' => $nextUrl, 'nextText' => $this->lang('Next')));
		}
		else {
			$next = $this->_template['noNextNavigation'];
		}
		
		if ($offset > 0) {
			$prevUrl = $this->createUrl(array('offset' => max($offset - $count,0)));
				
			$prev = $this->replacePlaceholders($this->_template['prevNavigation'], 
				array('prevUrl' => $prevUrl, 'prevText' => $this->lang('Prev')));
		}
		else {
			$prev = $this->_template['noPrevNavigation'];
		}
		
		if ($prev != '' && $next != '') {
			$delimiter = $this->_template['navigationDelimiter'];
		}
		else {
			$delimiter = '';
		}
		
		$output = $this->replacePlaceholders($this->_template['navigation'],
			array('next'=>$next,'delimiter'=>$delimiter,'prev'=>$prev));
		return $output;
	}
	
	private function formatDateTime($event) {
		$dtstart = $event->get('dtstart');
		$dtend = $event->get('dtend');
		$f['startdate'] = $this->formatDate($dtstart);
		$f['enddate'] = $this->formatDate($dtend);

		// Don't show enddate if == startdate
		if ($f['startdate']==$f['enddate'])
			$f['enddate'] = '';
			
		
		if ($event->get('allday')) {
			$f['starttime'] = '';
			$f['endtime'] = '';
			$f['timedelimiter'] = '';
		} else {
			$f['starttime'] = $this->formatTime($dtstart);
			$f['endtime'] = $this->formatTime($dtend);
			if ($f['endtime'] != '')
				$f['timedelimiter'] = ' - ';
		}
		return $f;
	}
	
	private function formatTime($date) {
		if ($date !== NULL) 
			return strftime($this->_timeFormat, strtotime($date));
		else
			return '';
	}
	
	private function formatDate($date) {
		if ($date !== NULL) 
			return strftime($this->_dateFormat, strtotime($date));
		else
			return '';
	}
	
	public function replacePlaceholders($c,$ph='') {
		if ($ph == '') $ph = $this->_placeholders;
		$keys = array_keys($ph);
		$keys = explode('///','[+'.implode('+]///[+',$keys).'+]');
		return str_replace($keys,array_values($ph), $c);
	}
	
	public function setConfig($key,$value = true) {
		if (is_array($key)) { // Not a key, but a complete _config-array
			$this->_config = $key;
		}
		else {
			$this->_config[$key] = $value;
		}
	}
	
	public function getConfig($key = '') {
		if ($key == '')
			return $this->_config;
		else
			return $this->_config[$key];
	}

	private function getPlaceholdersFromConfig() {
		$ph = array();
		foreach ($this->_requestableConfigs as $key) {
			if (($value = $this->getConfig($key)) !== NULL) {
				$ph[$key] = $value;
			}
		}
		return $ph;
	}
	
	public function setTemplateWrap($wrapper) {
		$this->_template['wrap'] = $wrapper;
	}
	
	public function setTemplateDay($day) {
		$this->_template['day'] = $day;
	}
	
	public function setTemplateEvent($event) {
		$this->_template['event'] = $event;
	}
	
	public function setTemplate($template) {
		$this->_template = $template;
	}
	
	private function errorMessage($message) {
		if (is_array($message)) {
			array_merge($this->_errorMessages, $message);
		}
		else {
			$this->_errorMessages[] = $message;
		}
	}

	private function infoMessage($message) {
		if (is_array($message)) {
			array_merge($this->_infoMessages, $message);
		}
		else {
			$this->_infoMessages[] = $message;
		}
	}

	private function error($msg, $file, $line) {
		$file = str_ireplace(array($_SERVER['DOCUMENT_ROOT'],$_SERVER['PWD']),array('',''),$file);
		die("$file:$line --- $msg\n");
	}

	/**
	 * Create URL with parameters. Adds ? if not already there.
	 */
	public function createUrl($params = array()) { 
		$url = $this->getConfig('mainUrl');
		$params = array_merge($this->getPlaceholdersFromConfig(), $params);
		if (strpos($url,'?')===false) $url .= '?';
		foreach($params as $k => $v) {
			if ($v !== NULL)
				$url .= "&amp;$k=".urlencode($v);
		}
		return $url;
	}
	
	private function lang($lang) {
		if (func_num_args() == 1) {
			return $lang;
		}
		else {
			$args = func_get_args();
			return call_user_func_array("sprintf",$args);
		}
	}

	public static function cleanTagName($name){
		$a = array('Æ', 'æ', 'Ø', 'ø', 'Å', 'å', ' ');
		$b = array('AE','ae','OE','oe','AA','aa','_');
		return str_replace($a,$b,$name);
	}
}