<?php
if (!is_object($modx)) die("You shouldn't be here!");

// Load configuration
require_once ('config.php');

$snippetUrl = $modx->config['base_url'].'assets/snippets/Gregorian/';
$snippetDir = $_SERVER['DOCUMENT_ROOT'].$snippetUrl;
// Load xPDO
$xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
    array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('Gregorian', $snippetDir . 'model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

$moduleUrl = "$_SERVER[SCRIPT_URI]?a=$_REQUEST[a]&id=$_REQUEST[id]";
$output .= "<a href='$moduleUrl&action=createContent'>Create random content</a><br />";
$output .= "<a href='$moduleUrl&action=createTables'>Create database tables</a><br />";

switch ($_REQUEST['action']) {
    case 'createContent':
    	// Generate 10 random events in the future
        $calendar = $xpdo->getObject('Gregorian',1);
    	for ($i=0;$i<10;$i++) {
    		$start = time() + rand(1,10)*3600*24;
    		$fields = array('summary' => "Test event number $i",
                'dtstart' => date('Y-m-d H:i',$start));
    		if (rand(0,10)>5) $fields['dtend'] = $start+rand(1,48)*3600;
    		$fields['allday'] = (rand(0,10)>5);
    		if (rand(0,10)>5) $fields['description'] = "An event with a description!";
    		if (rand(0,10)>5) $fields['location'] = "Somewhere, over the rainbow";
    		
    		$calendar->createEvent($fields);
    		
    		echo "Event '$fields[summary]' on '$fields[dtstart]' created<br />\n";
    	}
    	break;
	case 'createTables':
	   $xpdo->getManager();
		$classes = array('Gregorian','GregorianEvent','GregorianTag','GregorianEventTag');
		
		foreach ($classes as $class) {
			$output.= "Trying to create container for class '$class'... ";
			$result = $xpdo->manager->createObjectContainer($class);
			$output .= (($result)?"[Ok]":"[Failed]")."<br />\n";
		}
		break;
}
return $output;