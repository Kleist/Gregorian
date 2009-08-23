<?php
// TODO Consider using built in setOption/getOption instead of *Config

define('TS_ONE_DAY',24*3600);

class Gregorian extends xPDOSimpleObject {

	
	/**
	 * @var array Array of fetched GregorianEvent objects, indexed by event id.
	 */
	private $_events = NULL;

	private $_config = array(
		'startdate' => NULL, 
		'enddate' => NULL, 
		'count' => 10, 
		'page' => 1, 
		'eventId' => NULL,
		'isEditor' => false,
	    'allowAddTag' => false,
		'mainUrl' => '',
		'ajaxUrl' => NULL,
		'dieOnError' => true,
        'snippetDir' => '',
        'filter' => '',
        'formatForICal' => 0,
        'debugLevel' => 0
	);

	// These should be made private when refactoring is done
	public $_requestableConfigs = array('eventId','count','page');
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
		if ($timestamp == '') $timestamp = time()-TS_ONE_DAY;
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
			if ($this->getConfig('allowAddTag')) {
				$addTagUrl = $this->createUrl(array('action'=>'tagform','eventId'=>NULL));
				$addTagLink = $this->replacePlaceholders($this->_template['addTagLink'],array('addTagUrl'=>$addTagUrl,'addTagText'=>$this->lang('add_tag')));;
			} 
			else {
				$addTagLink = '';
			}
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
			
        // Settings
        $baseDate = strtotime($this->getConfig('startdate'));
        $linesPerPage = $this->getConfig('count');
        $activePage = $this->getConfig('page');
        
        // State variable initialization
        $date = $baseDate;
        $linesOnPage = 0;
        $page=1;
        
        // Progress variables
        $multiLeft = array();
        $events = '';
        
        // Outputs
        $pageStart = array(1 => 0);
        $days = '';
        
        $maxRuns = 1000;
        reset($this->_events);
        while ((list($eventId, $event) = each($this->_events)) || !empty($multiLeft)) {
            if (is_object($event)) {
                $this->debug_print($eventId,'Handling event',5);            
                $nextStart = strtotime($startDate = $event->getMySQLDateStart());
	        }
        	
        	// Check for multi-event starting before today, subtract days up to today.
        	// Loop that continues until $nextStart is current date and the event has been counted (and shown if on $activePage).
        	do {
                $this->debug_print("Date: ".$this->formatDate($date)." --- Event $eventId, $startDate");
        		// Check if the current event starts on or before the current date. 
                if (is_object($event) && $nextStart<=$date) {
        			// Assign event to current page
        			$linesOnPage++;
                    
        			$this->debug_print((($activePage==$page)?"ActivePage: ":"PassivePage:")."$eventId is on page $page. ".strftime('%d-%m-%y',$date),'',3);
        			        			
        			// If multievent, save remaining daycount
        			if ($event->isMultiDay()) {
        				$multiLeft[$eventId] = $event->getDays();
        			}
        			
        			// If page is to be shown, save the eventId.
        			$e = $this->renderEvent($event);
                    $this->debug_print($e,'renderedEvent',5);
        			if ($page==$activePage) {
        				if (!$e['multi']) {
                            $this->debug_print('SingleEvent','',5);
                            $events .= $e['single'];
        				}
        				elseif ($nextStart==$date) {
        					$events .= $e['first'];
        				}
        				else {
        					$nextEnd = strtotime($event->getMySQLDateEnd());
        					if ($nextEnd > $date) {
                                $events .= $e['between'];
        					}
        					elseif ($nextEnd == $date) {
                                $events .= $e['end'];
        				    }
        					else {
        						// TODO: This should never be reached... log it or something if it does.
        						$this->debug_print("Start: $nextStart, End: $nextEnd, Date: $date",1);
        					}
        				}
        			} 
        			// Add to repeated events if applicable.
        			if ($e['multi']) {
        				$this->debug_print($e,"Adding: ");
        				$re[$eventId] = $e;
        			}
        			// Clear date for current event, since it has been added to the output and queue if not over as of $date.
                    $nextStart = NULL;
                    unset($event);
        		}
        		// If the current event does not start on this page, and the linecount has reached the limit,
        		// it's time for the next page.
        		elseif ($linesOnPage >= $linesPerPage) {
        			$page++;	
                    $pageStart[$page] = $pageStart[$page-1]+$linesOnPage;
                    $linesOnPage = 0;
        		}
        		// If $nextStart is not yet and page is not filled increment $date and show all multi-events in the queue
        		else {
                    if ($events != '') {
                        $days .= $this->renderDay($this->formatDate($date),$events);
                    	$events = '';
                    }
        			$date += TS_ONE_DAY;
        			
                    // echo "<pre>".print_r($multiLeft,1)."</pre>\n";
                    
                    // Decrement all multiLeft-counters, increment $linesOnPage and purge done multi-events.
                    foreach($multiLeft as $id => $daysLeft) {
                        $this->debug_print((($activePage==$page)?"ActivePage: ":"PassivePage:")."$id is on page $page. ".strftime('%d-%m-%y',$date),'',3);
                        $multiLeft[$id]--;
                    	$linesOnPage++;

                    	// If page is to be shown, show the event.
                    	if ($page==$activePage) {
                    		if ($multiLeft[$id]==0) {
                    			$events .= $re[$id]['last'];
                    			unset($re[$id]);
                    		}
                    		else {
                    			$this->debug_print($re,'$re[$id]');
                                $events .= $re[$id]['between'];
                    		}
                    	}
                    	
                    	// Purge done multi-events
                    	if ($multiLeft[$id] == 0) {
                    		unset($multiLeft[$id]);
                    	}
                    }
        		}
        		$runs++;
        		if ($runs>=$maxRuns) break;
        		
        	}
        	while (is_object($event));
        	$this->debug_print($multiLeft,'MultiLeft:',5);
            $this->debug_print(empty($multiLeft),'empty($multiLeft):',5);

            if ($runs>=$maxRuns) {
            	echo "Calendar rendering stopped after $maxRuns iterations.";
            	break;
            }
        }
        
        if ($events != '') {
            $days .= $this->renderDay($this->formatDate($date),$events);
        	$events = '';
        }
        
        $this->_pageStart = $pageStart;
        $this->debug_print($pageStart,'$pageStart');
        
		// Render navigation
		if (!empty($eventQueue)) {
			//var_dump($eventQueue);
			$moreEvents = true;
		}
		else $moreEvents = false;
		$navigation = $this->renderNavigation($moreEvents);

		// Wrap days in overall template
		$wrap = $this->replacePlaceholders($this->_template['wrap'],
		array('days'=>$days, 
		'navigation' => $navigation, 
		'createLink' => $createLink, 
		'addTagLink'=>$addTagLink,
		'deleteCalendarEntryText' => $this->lang('delete_calendar_entry'), 
		'reallyDeleteText' => $this->lang('really_delete')));
		return $wrap."<!-- render-loops: $runs -->";
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
		
		// Show newlines in description:
		$e_ph['description'] = nl2br($e_ph['description']);
		
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
		$e['id'] = $event->get('id');
		return $e;

	}
	
	/**
	 * Render the navigation
	 * @return string The rendered navigation
	 */
	public function renderNavigation() {
        $currentPage = $this->getConfig('page');
        
        // Create page number navigation
        $pageCount = sizeof($pageStart);
        $pages = '';
        $nextUrl = '';
        $nextTpl = 'noNextNavigation';
        $prevTpl = 'noPrevNavigation';
        $prevUrl = '';
        foreach ($this->_pageStart as $page => $start) {
        	$pageUrl = $this->createUrl(array('page' => $page));
        	$ph = array('pageNum' => $page, 'pageUrl' => $pageUrl);
        	
        	if ($page == $currentPage) {
        		$tpl = 'activePage'; 
        	}
        	else {
        		$tpl = 'page';
        	}
        	
        	$pages .= $this->replacePlaceholders($this->_template[$tpl], $ph);

            if ($page == $currentPage-1) {
            	$prevUrl = $pageUrl;
            	$prevTpl = 'prevNavigation';
            }
            elseif ($page == $currentPage+1) {
            	$nextTpl = 'nextNavigation';
            	$nextUrl = $pageUrl;
            }
        }
        
        $prev = $this->replacePlaceholders($this->_template[$prevTpl], array('prevUrl' => $prevUrl, 'prevText' => $this->lang('prev')));
        $next = $this->replacePlaceholders($this->_template[$nextTpl], array('nextUrl' => $nextUrl, 'nextText' => $this->lang('next')));
        
        
        $output = $this->replacePlaceholders($this->_template['navigation'],
		array('next'=>$next,'prev'=>$prev, 'numNav' => $pages, 'expandAllText' => $this->lang('expand_all'), 'contractAllText' => $this->lang('contract_all')));
		return $output;
	}

	public function formatDateTime($event,$type = 'both') {
		$dtstart = $event->get('dtstart');
		$f['startdate'] = $this->formatDate($dtstart);
		$dtend = $event->get('dtend');
		$f['enddate'] = $this->formatDate($dtend);
		if ($this->getConfig('formatForICal')) {
			$unixend = strtotime($dtend);
			if ($event->get('allday')) {
            	$format = ";VALUE=DATE:%Y%m%d";
            	$unixend += TS_ONE_DAY; // Needed to make iCal show the last day of multi-day events.
            }
            else {
            	$format = ":%Y%m%dT%H%M%S";
            }
            
            $f['iCal_dtstart'] = strftime("DTSTART".$format,strtotime($dtstart)); 
            $f['iCal_dtend'] = strftime("DTEND".$format,$unixend);
            $f['iCal_dtstamp'] = strftime("DTSTAMP".$format,strtotime($dtstart));
        }
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
		if ($date !== NULL) {
			if (isset($this->_lang['days'])) {
				$d = strtotime($date);
				$day = strftime('%u',$d);
                return $this->_lang['days'][$day].strftime(' %e. %b.', $d);
			}
			else {
                return strftime($this->_dateFormat,strtotime($date));
			}
		}
		else return '';
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
        $this->debug_print($langCode, 'Attempting to load lang',1);
        $loaded = false;
    	$fullpath = $this->getConfig('snippetDir').'lang/'.$langCode.'.lang.php';
        if (file_exists($fullpath)) {
        	$l = include($fullpath);
        	if (is_array($l)) {
        	   $this->_lang = $l;
        	   $loaded = true;
        	}
        }
        $this->debug_print($this->_lang,'Lang',6);
        
        if ($loaded && isset($l['setlocale'])) {
            $result = setlocale(LC_TIME,$l['setlocale']);
            $this->debug_print($result,'setlocale result',1);
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
		$a = array('®', '¾', '¯', '¿', '', 'Œ', ' ');
		$b = array('AE','ae','OE','oe','AA','aa','_');
		return str_replace($a,$b,$name);
	}
	
	/**
	 * Print debug information
	 * @param $var Information to print. If it's a string it's just printed, otherwise it's printed with print_r.
	 * @param $name Name of the information, '' results in no name shown, which is default.
	 * @param $level Integer Debug level, 0=only show errors, ... 5=spam the output with A LOT of debug info.
	 * @return none
	 */
	function debug_print($var,$name = '', $level = 1) {
		if ($this->getConfig('debugLevel')<$level) return;
		if ($name!='') echo "$name:<br />\n";
		if (is_string($var)) echo "$var<br />";
		else echo "<pre>".htmlspecialchars(print_r($var,1))."</pre>";
	}

}

