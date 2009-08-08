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
$xpdo->getManager();
$classes = array('Gregorian','GregorianEvent','GregorianTag','GregorianEventTag');

foreach ($classes as $class) {
	$output.= "Trying to create container for class '$class'... ";
	$result = $xpdo->manager->createObjectContainer($class);
	$output .= (($result)?"[Ok]":"[Failed]")."<br />\n";
}
return $output;