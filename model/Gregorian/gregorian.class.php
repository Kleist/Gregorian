<?php
// TODO Consider using built in setOption/getOption instead of *Config

define('TS_ONE_DAY',24*3600);

class Gregorian extends xPDOSimpleObject {


	/**
	 * @var array Array of fetched GregorianEvent objects, indexed by event id.
	 */
	private $_events = NULL;

	private $_config = array(
		'baseDate' => NULL,
		'count' => 10,
		'page' => 1,
		'eventId' => NULL,
		'isEditor' => false,
	    'allowAddTag' => true,
		'mainUrl' => '',
		'ajaxUrl' => NULL,
		'dieOnError' => true,
        'snippetDir' => '',
        'filter' => '',
        'formatForICal' => 0,
        'debugLevel' => 0
	);

	// Move to Controller
	public $_requestableConfigs = array('eventId','count','page');
    // Move to View
	public $_template = NULL;

	// I18n
	// Move to Controller
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

    private function _getEventCount() {
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
			if (!is_array($filter)) $filter = explode(',',$filter);
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
            WHERE (`dtstart` > DATE_SUB(NOW(),INTERVAL 1 WEEK)
            OR `dtend` > DATE_SUB(NOW(),INTERVAL 1 WEEK))
            $filterCondition
            ORDER BY dtstart ASC"
		);
		$query->prepare();
		//$sql = $query->toSql();
		return $this->getEvents($query);
	}

    public function getEventsMeta($timestamp = '') {
        if ($timestamp == '') $timestamp = time()-TS_ONE_DAY;
        $this->setConfig('baseDate', $timestamp);
        $filter = $this->getConfig('filter');
        if (!empty($filter)) {
            if (!is_array($filter)) $filter = explode(',',$filter);
            $filterString = "'".implode("','",$filter)."'";
            $filterCondition = "AND tag.tag IN ($filterString)";
        }
        else {
            $filterCondition = '';
        }

        $eventTbl = $this->xpdo->getTableName('GregorianEvent');
        $tagTbl = $this->xpdo->getTableName('GregorianTag');
        $eventTagTbl = $this->xpdo->getTableName('GregorianEventTag');

        $metaFields = array('id','dtstart','dtend','allday');

        $select = 'event.'.implode(', event.', $metaFields);
        $calId = $this->get('id');

        $query = new xPDOCriteria($this->xpdo,"
            SELECT $select FROM $eventTbl as event
            LEFT JOIN $eventTagTbl as eventtag ON event.id = eventtag.event
            LEFT JOIN $tagTbl as tag ON eventtag.tag = tag.id
            WHERE (`dtstart` > DATE_SUB(NOW(),INTERVAL 1 DAY)
            OR `dtend` > DATE_SUB(NOW(),INTERVAL 1 DAY)) AND event.calendar = $calId
            $filterCondition
            ORDER BY dtstart ASC"
        );
        $query->prepare();
        //$sql = $query->toSql();
        return $this->xpdo->getCollection('GregorianEvent',$query);
    }

	/**
	 * TODO Move to View
	 * @param $template
	 * @return unknown_type
	 */
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

    public function error($msg, $file, $line) {
        $msg = $this->lang($msg);
        $file = str_ireplace(array($_SERVER['DOCUMENT_ROOT'],$_SERVER['PWD']),array('',''),$file);
        if ($this->getConfig('dieOnError')) die("$file:$line --- $msg\n");
    }

	public static function cleanTagName($name){
		$a = array('®', '¾', '¯', '¿', '', 'Œ', ' ');
		$b = array('AE','ae','OE','oe','AA','aa','_');
		return str_replace($a,$b,$name);
	}
	
	/**
	 * Print debug information
	 * TODO Move to Controller
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

