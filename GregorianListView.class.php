<?php
require_once "GregorianView.class.php";

class GregorianListView extends GregorianView {

	
	private $_pages = array(); // Pagination info
	private $_events = array(); // Array of GregorianEvent objects
	
	private $_oddEvenCnt = 0; // For alternating line/day-colors.
	
    public function __construct(&$modx, &$xpdo) {
        parent::__construct(&$modx, &$xpdo);
    }
    	
	/**
	 * The overall method for showing a view.
	 * @return unknown_type
	 */
	public function render() {
		//// Steps:
		// Load EventsMeta
		// paginate
		// Load page-events
		// Fill out templates
		
		$this->_loadTemplate();
        $this->_loadLang($this->get('lang'));
        
		$this->_registerJS_CSS();
		
        //$events = $this->cal->getEventsMeta();
		$this->_events = $this->cal->getFutureEvents();
		
		$pageCount = $this->_paginate();
        
		$navigation = $this->_renderNavigation();
        
		$days = $this->_renderDays($navigation);
        
		$this->modx->toPlaceholders(array('days'=>$days, 'navigation' => $navigation,
            'deleteCalendarEntryText' => $this->lang('delete_calendar_entry'), 
            'reallyDeleteText' => $this->lang('really_delete')));
		return $this->modx->mergePlaceholderContent($this->_template['wrap']);
	}


	
    /**
     * Fetch metadata on all events to show, and decide how to split them into pages.
     * Each event takes up one line for each day it occurs. A page is the first $linesPerPage occurences, 
     * rounded up to avoid splitting a day over two pages. Thus each page does not span the same amount of
     * time, but rougly the same amount of event-occurences.
     * 
     * Sets the $this->_pages array.
     * 
     * @param $events Array of event objects
     * @return integer Pagecount
     */
    private function _paginate() {
        // Settings
        $baseDate = strtotime(strftime('%Y-%m-%d'));
        $linesPerPage = $this->get('count');
        $activePage = $this->get('page');
        
        // State variable initialization
        $date = $baseDate;
        $linesOnPage = 0;
        $eventsOnDay = array();
        $daysOnPage = array();
        $page=1;
        
        // Progress variables
        $multiLeft = array();
        
        // Outputs
        $pages = array(1 => array('pagestart' => 0));
        $dateId = 0;
        $days = array();
        
        $maxRuns = 1000;
        reset($this->_events);
        while ((list($i, $event) = each($this->_events)) || !empty($multiLeft)) {
            if (is_object($event)) {
                $nextStart = strtotime($startDate = $event->getMySQLDateStart());
                $eventId = $event->get('id');
            }
            
            // Check for multi-event starting before today, subtract days up to today.
            // Loop that continues until $nextStart is current date and the event has been counted (and shown if on $activePage).
            do {
                // Check if the current event starts on or before the current date. 
                if (is_object($event) && $nextStart<=$date) {
                    // Assign event to current page
                    $linesOnPage++;
                    
                    // If multievent, save remaining daycount
                    if ($event->isMultiDay()) {
                        $multiLeft[$eventId] = $event->getDays();
                    }
                    
                    // Register event on date and date on page
                    $eventsOnDay[] = $eventId;

                    // Clear date for current event, since it has been added to the output and queue if not over as of $date.
                    $nextStart = NULL;
                    unset($event);
                }
                // If the current event does not start on this page, and the linecount has reached the limit,
                // register the page, and reset counter and temp register.
                elseif ($linesOnPage >= $linesPerPage) {
                    // This page
                	$pages[$page]['lines'] = $linesOnPage;
                    $pages[$page]['days'][] = array('events' => $eventsOnDay, 'date' => $date);
                    $eventsOnDay = array();
                    
                    // Next page
                    $pages[$page+1]['pageStart'] = $pages[$page]['pageStart']+$linesOnPage;
                    
                    $linesOnPage = 0;
                    
                    $page++;
                }
                // If $nextStart is not yet and page is not filled increment $date and show all multi-events in the queue
                else {
                	if (!empty($eventsOnDay)) {
                		$pages[$page]['days'][] = array('events' => $eventsOnDay, 'date' => $date);
                		$eventsOnDay = array();
                	}

                	$date += TS_ONE_DAY;
                    
                    // Decrement all multiLeft-counters, increment $linesOnPage and purge done multi-events.
                    foreach($multiLeft as $id => $daysLeft) {
                        $multiLeft[$id]--;
                        $linesOnPage++;

                        // Add event to eventsOnDate if not already there
                        $eventsOnDay[] = $id;
                        
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
            
            if ($runs>=$maxRuns) {
                echo "Calendar rendering stopped after $maxRuns iterations.";
                break;
            }
        }
        
        if (!empty($eventsOnDay)) {
        	$pages[$page]['days'][] = array('events' => $eventsOnDay, 'date' => $date);
        	$pages[$page]['lines'] = $linesOnPage;
        }
        else { // Otherwise unregister empty page
            unset($pages[$page]);
            $page--;
        }
        
        $this->_pages = &$pages;
        return $page;
    }
    
	
    private function _renderNavigation() {
        $currentPage = $this->get('page');

        $pages = '';
        $nextUrl = '';
        $nextTpl = 'noNextNavigation';
        $prevUrl = '';
        $prevTpl = 'noPrevNavigation';
        foreach ($this->_pages as $page => $meta) {
            $pageUrl = $this->_createUrl(array('page' => $page));
            $ph = array('pageNum' => $page, 'pageUrl' => $pageUrl);

            if ($page == $currentPage) {
                $tpl = 'activePage';
            }
            else {
                $tpl = 'page';
            }

            // Add page-button
            $this->modx->toPlaceholders($ph);
            $pages .= $this->modx->mergePlaceholderContent($this->_template[$tpl]);

            if ($page == $currentPage-1) {
                $prevUrl = $pageUrl;
                $prevTpl = 'prevNavigation';
            }
            elseif ($page == $currentPage+1) {
                $nextTpl = 'nextNavigation';
                $nextUrl = $pageUrl;
            }
        }

        // Clean and reuse $ph array
        $ph = array('numNav' => $pages);

        // Prev/next links
        $this->modx->toPlaceholders(array('prevUrl' => $prevUrl, 'prevText' => $this->lang('prev')));
        $ph['prev'] = $this->modx->mergePlaceholderContent($this->_template[$prevTpl]);
        
        $this->modx->toPlaceholders(array('nextUrl' => $nextUrl, 'nextText' => $this->lang('next')));
        $ph['next'] = $this->modx->mergePlaceholderContent($this->_template[$nextTpl]);

        // Editor buttons
        if ($this->get('isEditor')) {
            $createUrl = $this->_createUrl(array('action' => 'show', 'view' => 'EventForm', 'eventId' => NULL));
            
            $this->modx->toPlaceholders(array('createUrl'=> $createUrl,'createEntryText'=>$this->lang('create_entry')));
            $ph['createLink'] = $this->modx->mergePlaceholderContent($this->_template['createLink']);
            if ($this->get('allowAddTag')) {
                $addTagUrl = $this->_createUrl(array('action'=>'show','view'=>'tagform','eventId'=>NULL));
                
                $this->modx->toPlaceholders(array('addTagUrl'=>$addTagUrl,'addTagText'=>$this->lang('add_tag')));
                $ph['addTagLink'] = $this->modx->mergePlaceholderContent($this->_template['addTagLink']);
            }
            else {
                $ph['addTagLink'] = '';
            }
        }
        else {
            $ph['createLink'] = '';
            $ph['addTagLink'] = '';
        }
        
        $ph['expandAllText']   = $this->lang('expand_all');
        $ph['contractAllText'] = $this->lang('contract_all');
        
        $this->modx->toPlaceholders($ph);
        return $this->modx->mergePlaceholderContent($this->_template['navigation']);
    }
    
    
    /**
     * Render the page defined by $this->_pages[$this->get('page')]
     * @return unknown_type
     */
    private function _renderDays() {
    	$days = '';
    	$page = $this->get('page');
    	foreach ($this->_pages[$page]['days'] as $dayNum => $day) {
    		$days .= $this->_renderDay($day);
    	}
    	return $days;
    }
    
    private function _renderDay($day) {
    	$events = '';
    	foreach($day['events'] as $id) {
    		$events .= $this->_renderEvent($id,$day['date']);
    	}

    	$this->modx->toPlaceholders(array('dayclass' => $this->_getOddEven(),
                    'events' => $events,
                    'date' => $this->_formatDate($day['date'])));
        return $this->modx->mergePlaceholderContent($this->_template['day'], $ph);
    }
    
    /**
     * TODO Move to Controller/View
     * Renders a single or multi-day event
     * @param $event GregorianEvent object
     * @return array array('first' => Rendered first event occurrence, 'inbetween' => Rendered inbetween occurrences, 'last' => rendered last occurence)
     */
    private function _renderEvent($id,$date) {
        $event = &$this->_events[$id];
        // Parse tags
        $tagArray = $event->getTags();
        $tags = '';
        if (is_array($tagArray)) {
            foreach ($tagArray as $tag) {
            	$this->modx->setPlaceholder('tag',$tag);
                $tags.= $this->modx->mergePlaceholderContent($this->_template['tag']);
            }
        }

        // create event-placeholder
        $e_ph = $event->get(array('summary','location','description','id'));
        
        // Show newlines in description:
        $e_ph['description'] = nl2br($e_ph['description']);
        
        $f = $this->_formatDateTime($event);
        $multi = $event->isMultiDay();
        $e_ph = array_merge($e_ph,$f);
        $e_ph['tags'] = $tags;

        // Parse editor
        if ($this->get('isEditor')) {
            $editUrl = $this->_createUrl(array('action' => 'show', 'view' => 'EventForm', 'eventId'=>$event->get('id')));
            $deleteUrl = $this->_createUrl(array('action'=>'delete', 'eventId'=>$event->get('id')));

            $this->modx->toPlaceholders(array('editUrl' => $editUrl,'deleteUrl' =>$deleteUrl, 'editText'=>$this->lang('edit'), 'deleteText'=>$this->lang('delete')));
            $e_ph['admin'] = $this->modx->mergePlaceholderContent($this->_template['admin']);
        }
        else {
            $e_ph['admin'] = '';
        }

        // Add language strings
        $e_ph['toggleText'] = $this->lang('toggle');
        
        // Render event from template
        $startdate = strtotime(substr($event->get('dtstart'),0,10));
        
        if ($multi) { // Select First/Between/Last template
        	// TODO Could be optimized by saving more info in _pagination(). 
        	if ($startdate<$date+24*3600) {
        		$tpl = $this->_template['eventFirst'];
        	} 
        	elseif ($date>=$enddate) {
        		$tpl = $this->_template['eventLast'];
        	}
        	else {                           
        	   $tpl = $this->_template['eventBetween'];
        	}
        }
        else {
            $tpl = $this->_template['eventSingle'];
        }
        
        $this->modx->toPlaceholders($e_ph);
        return $this->modx->mergePlaceholderContent($tpl);
    } // _renderEvent()    
    
    /**
     * Tell MODx to include js and css in the header
     * @return none
     */
    private function _registerJS_CSS() {
        $this->modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
        $this->modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
        $snippetUrl = $modx->config['base_url'].$this->get('snippetUrl');
        //          if ($ajaxEnabled) {
        //              $this->modx->regClientStartupScript('<script type="text/javascript">var ajaxUrl="'.$ajaxUrl.'"</script>',true);
        //              $this->modx->regClientStartupScript($snippetUrl.'Gregorian.ajax.js');
        //          }
        $this->modx->regClientStartupScript($snippetUrl.'Gregorian.view.js');
        $this->modx->regClientCSS($snippetUrl.'layout.css');
        $this->modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');
    }

    /**
     * Load the calendar with 'calId', set in config.
     * @return boolean True on success 
     */
    private function _loadCalendar() {
        $this->cal = $this->xpdo->getObject('Gregorian',$this->get('calId'));
        return ($this->cal !== NULL);
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
    
    /**
     * Returns 'even' or 'odd', alternating at every call
     * @return string 'even' or 'odd'
     */
    private function _getOddEven() {
    	if ($this->_oddEvenCnt++ % 2) return 'odd';
    	else return 'even';    	
    }
}