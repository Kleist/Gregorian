<?php
// TODO Handle slashes better (Here or in snippet?)

class Gregorian extends xPDOSimpleObject {

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
		'ajaxUrl' => NULL,
		'dieOnError' => false
	);

	public $_requestableConfigs = array('eventId','startdate','enddate','count','offset');

	public $_template = NULL;

	// Configuration
	private $_dateFormat = '%a %e. %b.';
	private $_timeFormat = '%H:%M';

	function Gregorian(& $xpdo) {
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

		$event = $this->xpdo->newObject('GregorianEvent');

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
		if (!($saved = $this->save()))	{
			$this->error("Couldn't save calendar to database!\n",__FILE__,__LINE__);
			return false;
		}
		else {
			return $event;
		}
	}

	/**
	 * Fetches all events from db using $criteria, returns _events if it has already been fetched, regardless of $criteria.
	 *
	 * @param string $criteria Criteria for selecting events
	 * @return array Array of GregorianEvent-objects
	 */
	public function getEvents($criteria=NULL) {
		$this->_events = $this->getMany('Events',$criteria);
		return $this->_events;
	}

	public function getEventById($id) {
		return $this->xpdo->getObject('GregorianEvent',array('id'=>$id));
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
		$query = $this->xpdo->newQuery('GregorianEvent');
		$query->where(array("GregorianEvent.dtstart:>" => $this->getConfig('startdate')),NULL,0);
		if ($this->getConfig('enddate') !== NULL)
		$query->andCondition(array('GregorianEvent.dtstart:<'=>$this->getConfig('enddate')),NULL,0);

		$query->orCondition(array('GregorianEvent.dtend:>' => $this->getConfig('startdate')));
		if ($this->getConfig('enddate') !== NULL)
		$query->andCondition(array('GregorianEvent.dtstart:<' => $this->getConfig('enddate')));

		$query->sortBy('GregorianEvent.dtstart');

		$this->totalCount = $this->xpdo->getCount('GregorianEvent',$query);

		// Check if current page if empty
		if ($this->totalCount <= $offset) {
			$offset = max($offset - $count,0);
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
		if ($this->getConfig('isEditor')) {
			$createUrl = $this->createUrl(array('action'=>'showform','eventId'=>NULL));
			$createLink = $this->replacePlaceholders($this->_template['createLink'], array('createUrl'=> $createUrl));
			$addTagUrl = $this->createUrl(array('action'=>'tagform','eventId'=>NULL));
			$addTagLink = $this->replacePlaceholders($this->_template['addTagLink'],array('addTagUrl'=>$addTagUrl));	
		}
		else {
			$createLink = '';
			$addTagLink = '';
		}
		
		if ($this->_events === NULL || sizeof($this->_events) == 0) {
			return $createLink.$addTagLink.$this->lang('No calendar entries found');
		}

		if ($this->_template === NULL) {
			$this->loadTemplate();
		}
			
		$lastdate = '';
		$events = '';
		$days = '';
		$oddeven = 'even';
		$calId = $this->get('id');

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

				$e_ph['admin'] = $this->replacePlaceholders($this->_template['admin'], array('editUrl' => $editUrl,'deleteUrl' =>$deleteUrl));
			}
			else {
				$e_ph['admin'] = '';
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
		return $this->replacePlaceholders($this->_template['wrap'],array('days'=>$days, 'navigation' => $navigation, 'createLink' => $createLink, 'addTagLink'=>$addTagLink));
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

	public function formatDateTime($event) {
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

	public function formatTime($date) {
		if ($date !== NULL)
		return strftime($this->_timeFormat, strtotime($date));
		else
		return '';
	}

	public function formatDate($date) {
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

	public function getPlaceholdersFromConfig() {
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

	public function error($msg, $file, $line) {
		$file = str_ireplace(array($_SERVER['DOCUMENT_ROOT'],$_SERVER['PWD']),array('',''),$file);
		if ($this->_config['dieOnError']) die("$file:$line --- $msg\n");
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

	public function lang($lang) {
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