<?php
class GregorianController {
	private $config = NULL;
	private $modx;
	private $calendarLoaded = false;

	// Calendar xPDO-object
	private $xpdo;
	private $calendar; 
	
	// Configuration
	private $_dateFormat = '%a %e. %b.';
	private $_timeFormat = '%H:%M';

	/**
	 * Constructor
	 * @var $config Array of config variables
	 */
	public function __construct($config) {
		global $modx;
		$this->modx = &$modx;
		$this->config = $config;

		// Load xPDO
		$this->xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
			array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
		$this->xpdo->setPackage('Gregorian', XPDO_CORE_PATH . '../model/');
		
		// Add debug option on developer-server
		if ($_SERVER['HTTP_HOST'] == 'cal.aks-server') {
			switch ($_REQUEST['debug']) {
				case 1:
					$this->xpdo->setLoglevel(XPDO_LOG_LEVEL_DEBUG);
				case 2:
					$this->xpdo->setDebug();
					break;
				default:
			}	
		}
		
		$this->loadLang();
	}
	
	public function handle() {
		$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'view';
		switch ($action) {
			case 'view':
				switch ($this->config['view']) {
					case 'agenda':
						return $this->renderAgenda();
					default:
						return "View $this->config[view] has not been implemented";
				}
			default:
				return "Unknown action '$action'.";
		}
	}
	
	public function loadCalendar() {
		$this->calendar = $this->xpdo->getObject('Gregorian',array('id'=> $this->config['calId']));

		if (is_object($this->calendar)) {
			$this->calendarLoaded = true;
		}
		
		return $this->calendarLoaded;
	}
	
	public function renderAgenda() {
		$count = $this->config['count'];
		$offset = $this->config['offset'];
		
		if (!$this->calendarLoaded) $this->loadCalendar();
		
		// Load events for the current page
		$events = $this->calendar->getFutureEvents($count,$offset);
		
		if (!is_array($events) || count($events) == 0) {
			if ($offset>0) $offset = max($offset-$count,0);
			return $this->_lang['no_events_found']; 
		}

		$lastdate = '';
		$event_string = '';
		$days = '';
		$oddeven = 'even';
		// $calId = $this->config['calId'];

		foreach ($events as $e) {
			$f = $this->formatDateTime($e);

			// If new date and not first event, render day, (uses date-placeholder from above)
			if ($f['startdate'] != $lastdate && $lastdate != '') {
				$ph = array('dayclass' => $oddeven,
					'events' => $event_string,
					'date' => $lastdate);
				$days .= $this->replacePlaceholders($this->getTemplate('day'), $ph);
				$event_string = '';
				$oddeven = ($oddeven=='even')? 'odd' : 'even';
			}
			$lastdate = $f['startdate'];
			
			// Parse tags
			$tagArray = $e->getTags();
			$tags = '';
			if (is_array($tagArray)) {
				foreach ($tagArray as $tag) {
					$tags.= $this->replacePlaceholders($this->getTemplate('tag'),array('tag'=>$tag));
				}
			}
			
			// create event-placeholder
			$e_ph = $e->get(array('summary','description','id'));
			$e_ph['summary'] = nl2br(strip_tags($e_ph['summary']));
			$e_ph['description'] = nl2br(strip_tags($e_ph['description']));
			$e_ph = array_merge($e_ph,$f);
			$e_ph['tags'] = $tags;
			// Parse editor
			// if ($this->getConfig('isEditor')) {
			// 	$editUrl = $this->createUrl(array('action'=>'showform', 'eventId'=>$e->get('id')));
			// 	$deleteUrl = $this->createUrl(array('action'=>'delete', 'eventId'=>$e->get('id')));
			// 
			// 	$e_ph['editor'] = $this->replacePlaceholders($this->getTemplate('editor'), array('editUrl' => $editUrl,'deleteUrl' =>$deleteUrl));
			// }
			// else {
				$e_ph['editor'] = '';
			// }
			
			// Render event from template
			$event_string .= $this->replacePlaceholders($this->getTemplate('event'),$e_ph);
		}
		// Wrap last event(s) in day-template
		$days .= $this->replacePlaceholders($this->getTemplate('day'), 
			array('dayclass' => $oddeven,
				'events' => $event_string,
				'date' => $lastdate
			)
		);
		
		return $this->replacePlaceholders($this->getTemplate('wrap'), array('days'=>$days));
		
	}

	public function getTemplate($name) {
		if (!isset($this->template)) {
			$this->template = @include($this->config['template'].'.template.php');
		}
		if ($this->template == NULL) {
			return "Template '".$this->config[template]."' could not be loaded.";
		}
		if (isset($this->template[$name])) {
			return $this->template[$name];
		}
		else {
			return "Template '$name' isn't loaded.";
		}
	}

	public function replacePlaceholders($text,$ph) {
		$keys = array_keys($ph);
		$keys = explode('///','[+'.implode('+]///[+',$keys).'+]');
		return str_replace($keys,array_values($ph), $text);
	}
	
	private function loadLang() {
		$this->_lang = @include("lang.$this->config[lang].php");
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
	
}
