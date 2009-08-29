<?php
/**
 * Class implementing the basic functionality for outputing views, and defining an interface for all views to extend.
 * @author andreas
 *
 */
abstract class GregorianView {
	/**
	 * @var Array of template strings
	 */
	protected $_template;

	/**
	 * @var GregorianConfig object
	 */
	private $config = NULL;


	/**
	 * @param $config object GregorianConfig
	 * @return none
	 */
    public function __construct(&$modx, &$xpdo) {
        $this->modx = &$modx;
        $this->xpdo= &$xpdo;

        $this->config = new GregorianConfig();
	}

    abstract public function render();

    protected function _loadLang($langCode) {
        $loaded = false;
        $fullpath = $this->get('snippetDir').'lang/'.$langCode.'.lang.php';
        if (file_exists($fullpath)) {
            $l = include($fullpath);
            if (is_array($l)) {
               $this->_lang = $l;
               $loaded = true;
            }
        }

        if ($loaded && isset($l['setlocale'])) {
            $result = setlocale(LC_TIME,$l['setlocale']);
        }

        if (!$loaded)
            throw new Exception($this->lang("Couldn't load language '$fullpath'!",__FILE__,__LINE__));
        else
            return true;
    }

    /**
     * Loads the template set in config. Either by name or as an array.
     * @return bool True on success
     */
    protected function _loadTemplate() {
        $loaded = false;
    	$template = $this->get('template');
    	if (is_string($template)) {
        	$templatePath = $this->get('snippetDir').'templates/template.'.$template.'.php';
        	if (file_exists($templatePath)) {
        	   $this->_template = require($templatePath);
        	   $loaded = true;
        	}
        }
        elseif (is_array($template)) {
            $this->_template = $template;
            $loaded = true;
        }
        return $loaded;
    }

    /**
     * Set config variable
     * @param string Config name
     * @param mixed Config value (default: true)
     * @return none
     */
    public function set($name,$value = true) {
        return $this->config->set($name,$value);
    }

    /**
     * Get config variable
     * @param string Config name
     * @return mixed Config value
     */
    public function get($name) {
        return $this->config->get($name);
    }

    public function setCalendar(&$cal) {
    	$this->cal = &$cal;
    }

    public function lang($lang) {
        if (!is_array($this->_lang)) die('You should loadLang() before lang().');
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

    protected function _renderTemplate($template,$ph = array()) {
    	$this->modx->toPlaceholders($ph);
        return $this->modx->mergePlaceholderContent($this->_template[$template]);
    }
    
    protected function _formatDateTime($event,$type = 'both') {
        $dtstart = $event->get('dtstart');
        $f['startdate'] = $this->_formatDate($dtstart,0);
        $dtend = $event->get('dtend');
        $f['enddate'] = $this->_formatDate($dtend,0);
        if ($this->get('formatForICal')) {
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
            if ($type == 'start' || $type == 'both') $f['starttime'] = $this->_formatTime($dtstart);

            if ($type == 'end' || $type == 'both')   $f['endtime'] = $this->_formatTime($dtend);

            if ($f['endtime'] != '' || $type == 'end' || $type == 'start' )   $f['timedelimiter'] = ' - ';
        }
        return $f;
    }

    protected function _formatTime($date) {
        if ($date !== NULL)
        return strftime($this->get('timeFormat'), strtotime($date));
        else
        return '';
    }

    protected function _formatDate($date,$timestamp=true) {
        if ($date !== NULL) {
            if (!$timestamp) $date = strtotime($date);

            if (isset($this->lang['days']) && isset($this->_lang['months'])) {
                $day = $this->_lang['days'][(int) strftime('%u',$date)];
                $month = $this->_lang['months'][(int) strftime('%m',$date)];
                return "$day. ".strftime('%e.', $date)." $month.";
            }
            else {
                return strftime($this->get('dateFormat'),$date);
            }
        }
        else return '';
    }

}
