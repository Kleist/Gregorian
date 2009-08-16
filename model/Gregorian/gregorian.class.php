<?php
// TODO Consider using built in setOption/getOption instead of *Config

class Gregorian extends xPDOSimpleObject {

	public $keepers = array('startdate','enddate','count','offset');
	/**
	 * @var array Array of fetched GregorianEvent objects
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
		'dieOnError' => true,
        'snippetDir' => '',
        'filter' => ''
	);

	public $_requestableConfigs = array('eventId','count','offset');

	public $_template = NULL;

	// Configuration
	private $_dateFormat = '%a %e. %b.';
	private $_timeFormat = '%H:%M';
	
	// I18n
	private $_lang = array();
	
	// Misc
	private $_oddeven = 'even';

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
			$this->error('error_summary_dtstart_required',__FILE__,__LINE__);
			return false;
		}

		$event = $this->xpdo->newObject('GregorianEvent');

		// Set fields
		foreach ($fields as $field => $value) {
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
			$this->error('error_couldnt_add_event',__FILE__,__LINE__);
			return false;
		}

		// Save changes to database
		if (!($saved = $this->save()))	{
			$this->error('error_couldnt_save_calendar',__FILE__,__LINE__);
			return false;
		}
		else {
			return $event;
		}
	}

    public function getEvent($id) {
    	// TODO Shouldn't this be done with getMany?
        return $this->xpdo->getObject('GregorianEvent',array('id'=>$id,'calendar'=>$this->get('id')));
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

    private function getEventCount() {
    	
        return (sizeof($this->_events));
    }
        
    /**
	 * Fetch future events (from now, or custom date)
	 * @param $timestamp Unix timestamp defining "now" (Default: NOW - 24 hrs)
	 * @return array Array of GregorianEvent objects
	 */
	public function getFutureEvents($timestamp = '') {
		if ($timestamp == '') $timestamp = time()-24*3600;
		$this->setConfig('startdate', strftime('%Y-%m-%d',$timestamp));
		$filter = $this->getConfig('filter');
		if (!empty($filter)) {
            if (!is_array($filter)) $filter = array($filter);
            $filterString = "'".implode("','",$filter)."'";
            $filterCondition = "AND tag.tag IN ($filterString)";
		}
		else {
            $filterCondition = '';
		}
        $eventTbl = $this->xpdo->getTableName('GregorianEvent');
        $tagTbl = $this->xpdo->getTableName('GregorianTag');
        $eventTagTbl = $this->xpdo->getTableName('GregorianEventTag');
        
        $count = $this->getConfig('count');
        $offset = $this->getConfig('offset');
        $query = new xPDOCriteria($this->xpdo,"
            SELECT event.* FROM $eventTbl as event 
            LEFT JOIN $eventTagTbl as eventtag ON event.id = eventtag.event 
            LEFT JOIN $tagTbl as tag ON eventtag.tag = tag.id  
            WHERE (`dtstart` > DATE_SUB(NOW(),INTERVAL 1 DAY) 
            OR `dtend` > DATE_SUB(NOW(),INTERVAL 1 DAY)) 
            $filterCondition            
            ORDER BY dtstart ASC"
		);
		return $this->getEvents($query);
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
		// TODO Show multi-date events better (On all days? Or show date range with description)
		if ($this->getConfig('isEditor')) {
			$createUrl = $this->createUrl(array('action'=>'showform','eventId'=>NULL));
			$createLink = $this->replacePlaceholders($this->_template['createLink'], array('createUrl'=> $createUrl,'createEntryText'=>$this->lang('create_entry')));;
			$addTagUrl = $this->createUrl(array('action'=>'tagform','eventId'=>NULL));
			$addTagLink = $this->replacePlaceholders($this->_template['addTagLink'],array('addTagUrl'=>$addTagUrl,'addTagText'=>$this->lang('add_tag')));;
		}
		else {
			$createLink = '';
			$addTagLink = '';
		}

		if ($this->_events === NULL || sizeof($this->_events) == 0) {
			return $this->replacePlaceholders($this->_template['wrap'],array(
                'createLink' => $createLink, 'addTagLink' => $addTagLink, 'days' => $this->lang('no_events_found'))
			);
		}

		if ($this->_template === NULL) {
			$this->loadTemplate();
		}
			
		$events = '';
		$days = '';
		$calId = $this->get('id');

		$offset = $this->getConfig('offset');
		$count = $this->getConfig('count');
		$eventCount = 0;
		$eventQueue = array(); // Events that last more than one day

		$baseDate = strtotime($this->getConfig('startdate'))+$offset*24*3600;
        $maxDays = $count;
		
		/**
		 * Render events that:
		 * - start before $baseDate and end after
		 * - start after $baseDate but before $baseDate + $maxDays + 1 day
		 */
        foreach ($this->_events as $event) {
//			$eventCount++;
			// Skip events not on current page
//			if ($eventCount <= $offset) continue;
//			if ($eventCount > $offset+$count) break;

			// Add events to queue
			$eventQueue[] = $this->renderEvent($event);
		}

		//echo "<pre>Event Queue\n:".print_r($eventQueue,1)."</pre>";
		for ($day = 0; $day < $maxDays; $day++) {
			$events = '';
			$active_date = $baseDate + $day*24*3600;
			$active_date_end = $active_date+24*3600;
			//echo strftime($this->_dateFormat,$active_date)."<br />\n";
			
			foreach ($eventQueue as $key=>$e) {
				if ($e['startdate']>=$active_date_end) break;
				//echo "$key - ";
				if ($e['multi']) {
					// Starts on or before $active_date
					if ($e['startdate']>=$active_date) {
						$events .= $e['first'];
					}
					// Starts before $active_date
					elseif ($e['enddate'] >= $active_date_end) {
						$events .= $e['between'];
					}
					// Starts before $active_date and ends before $active_date_end
					elseif ($e['enddate'] >= $active_date) {
						$events .= $e['last'];
						unset($eventQueue[$key]);
					}
					// Starts and ends before $active_date => not interesting
					else {
						unset($eventQueue[$key]);
					}
				}
				else { // Single day event
					if ($e['startdate']>=$active_date) {
						$events .= $e['single'];
                        unset($eventQueue[$key]);
					}
				}
			}	
			if ($events != '') {
				$days .= $this->renderDay(strftime($this->_dateFormat,$active_date),$events);
				$events = '';
			}
		}

		// Render navigation
		$navigation = $this->renderNavigation();

		// Wrap days in overall template
		return $this->replacePlaceholders($this->_template['wrap'],
		array('days'=>$days, 'navigation' => $navigation, 'createLink' => $createLink, 'addTagLink'=>$addTagLink,'deleteCalendarEntryText' => $this->lang('delete_calendar_entry'), 'reallyDeleteText' => $this->lang('really_delete')));
	}


	/**
	 * Renders a day
	 * @param $date string The pretty printed date
	 * @param $events string The formatted events happening that day
	 * @return string The formatted day
	 */
	private function renderDay($date,$events) {
		$ph = array('dayclass' => $this->_oddeven,
                    'events' => $events,
                    'date' => $date);
        $this->_oddeven = ($this->_oddeven=='even')? 'odd' : 'even';
		return $this->replacePlaceholders($this->_template['day'], $ph);
	}
	
	/**
	 * Renders a single or multi-day event
	 * @param $event GregorianEvent object
	 * @return array array('first' => Rendered first event occurrence, 'inbetween' => Rendered inbetween occurrences, 'last' => rendered last occurence)
	 */
	private function renderEvent($event) {

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
		$f = $this->formatDateTime($event);
		$multi = $event->isMultiDay();
		$e_ph = array_merge($e_ph,$f);
		$e_ph['tags'] = $tags;

		// Parse editor
		if ($this->getConfig('isEditor')) {
			$editUrl = $this->createUrl(array('action'=>'showform', 'eventId'=>$event->get('id')));
			$deleteUrl = $this->createUrl(array('action'=>'delete', 'eventId'=>$event->get('id')));

			$e_ph['admin'] = $this->replacePlaceholders($this->_template['admin'], array('editUrl' => $editUrl,'deleteUrl' =>$deleteUrl, 'editText'=>$this->lang('edit'), 'deleteText'=>$this->lang('delete')));
		}
		else {
			$e_ph['admin'] = '';
		}

		// Add language strings
		$e_ph['toggleText'] = $this->lang('toggle');
        
		// Render event from template
		$e['startdate'] = strtotime(substr($event->get('dtstart'),0,10));
        $e['multi'] = $multi;
        if ($multi) {
		    $e['first'] = $this->replacePlaceholders($this->_template['eventFirst'],$e_ph); 
            $e['between'] = $this->replacePlaceholders($this->_template['eventBetween'],$e_ph); 
		    $e['last'] = $this->replacePlaceholders($this->_template['eventLast'],$e_ph); 
            $e['enddate'] = strtotime(substr($event->get('dtend'),0,10));
        }
		else {
			$e['single'] = $this->replacePlaceholders($this->_template['eventSingle'],$e_ph);
		}
		return $e;

	}
	
	/**
	 * Render the navigation
	 * @return string The rendered navigation
	 */
	public function renderNavigation() {
		$offset = $this->getConfig('offset');
		$count = $this->getConfig('count');
		$nextOffset = $offset+$count;

		$nextUrl = $this->createUrl(array('offset' => $nextOffset));
        // Check if there are more pages
		if ($this->getEventCount() > $nextOffset) $nextTpl = 'nextNavigation';
		else $nextTpl = 'noNextNavigation';
		$next = $this->replacePlaceholders($this->_template[$nextTpl], array('nextUrl' => $nextUrl, 'nextText' => $this->lang('next')));

		// If this is not the first page
		$prevUrl = $this->createUrl(array('offset' => max($offset - $count,0)));
		if ($offset > 0) $prevTpl = 'prevNavigation';
		else $prevTpl = 'noPrevNavigation';
		$prev = $this->replacePlaceholders($this->_template[$prevTpl], array('prevUrl' => $prevUrl, 'prevText' => $this->lang('prev')));

        $numNav = '';
        $prePage = true;
		for ($i=0; $i*$count < $this->getEventCount(); $i++) 
        {
        	$page = $i+1;
        	$pageUrl = $this->createUrl(array('offset' => $i*$count));
        	
        	// Check for current page until past it
        	if ($prePage && $page*$count > $offset) { 
        		$prePage = false;
        		$numNav .= $this->replacePlaceholders($this->_template['activePage'],array('page' => $page, 'pageUrl' => $pageUrl));
        	}
        	else {
                $numNav .= $this->replacePlaceholders($this->_template['page'],array('page' => $page, 'pageUrl' => $pageUrl));
        	}
        }
		
		$output = $this->replacePlaceholders($this->_template['navigation'],
		array('next'=>$next,'prev'=>$prev, 'numNav' => $numNav, 'expandAllText' => $this->lang('expand_all'), 'contractAllText' => $this->lang('contract_all')));
		return $output;
	}

	public function formatDateTime($event,$type = 'both') {
		$dtstart = $event->get('dtstart');
		$f['startdate'] = $this->formatDate($dtstart);
		$dtend = $event->get('dtend');
		$f['enddate'] = $this->formatDate($dtend);
		// Don't show enddate if == startdate
		if ($f['startdate']==$f['enddate'])
		$f['enddate'] = '';

		if ($event->get('allday')) {
			$f['starttime'] = '';
			$f['endtime'] = '';
			$f['timedelimiter'] = '';
		} else {
			if ($type == 'start' || $type == 'both') $f['starttime'] = $this->formatTime($dtstart);

			if ($type == 'end' || $type == 'both')   $f['endtime'] = $this->formatTime($dtend);

			if ($f['endtime'] != '' || $type == 'end' || $type == 'start' )   $f['timedelimiter'] = ' - ';
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

	public function setTemplate($template) {
		$this->_template = $template;
	}

    public function error($msg, $file, $line) {
        $msg = $this->lang($msg);
        $file = str_ireplace(array($_SERVER['DOCUMENT_ROOT'],$_SERVER['PWD']),array('',''),$file);
        if ($this->getConfig('dieOnError')) die("$file:$line --- $msg\n");
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

	public function loadLang($langCode) {
    	$loaded = false;
    	$fullpath = $this->getConfig('snippetDir').'lang/'.$langCode.'.lang.php';
        if (file_exists($fullpath)) {
        	$l = include($fullpath);
        	if (is_array($l)) {
        	   $this->_lang = $l;
        	   $loaded = true;
        	}
        }
        
        if (!$loaded) 
            $this->error("Couldn't load language '$fullpath'!",__FILE__,__LINE__);
        else 
            return true;
    }
	
	public function lang($lang) {
		// TODO Do some encoding to avoid invalid array keys
        if (func_num_args() == 1) {
            if (array_key_exists($lang,$this->_lang))
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

	public static function cleanTagName($name){
		$a = array('Æ', 'æ', 'Ø', 'ø', 'Å', 'å', ' ');
		$b = array('AE','ae','OE','oe','AA','aa','_');
		return str_replace($a,$b,$name);
	}
}