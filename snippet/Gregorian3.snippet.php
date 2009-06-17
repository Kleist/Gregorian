<?php
require_once ('config.php');

// Environment
$snippetUrl = $modx->config['base_url'].'assets/snippets/Gregorian/';
$snippetDir = $_SERVER['DOCUMENT_ROOT'].substr($snippetUrl,1);

// Handle snippet configuration
$calId = 		(is_integer($calId)) 	? $calId			: 1;
$template = 	(isset($template)) 		? $template 		: 'default';
$view = 		(isset($view)) 			? $view 			: 'list';
$lang = 		(isset($lang)) 			? $lang 			: 'en';
$allCanEdit = 	(isset($allCanEdit)) 	? $allCanEdit 		: false;
$mgrIsAdmin = 	(isset($mgrIsNotAdmin)) ? !$mgrIsNotAdmin 	: 1;
$showPerPage = 	(isset($showPerPage)) 	? $showPerPage 		: 10;
$ajax = 		(isset($ajax)) 			? $ajax 			: false;
$ajaxId = 		(isset($ajaxId)) 		? $ajaxId 			: NULL;
$calDoc = 		(isset($calDoc)) 		? $calDoc 			: NULL;

if (isset($_REQUEST['count'])) $count = $_REQUEST['count'];
elseif (!isset($count)) $count = 5;

// Load language file
if (is_string($lang) && strlen($lang)==2) {
	$langFile = $snippetDir.'lang.'.$lang.'.php';
	$l = @include($langFile);
	if (!is_array($l)) return "Couldn't load language file $langFile";
}
else {
	return '&lang should be a 2 letter language code i.e.: &lang=`en` for English (default)';
}

// Load template
// if (
	
// Parse & validate snippet configuration
if (!$ajax) {
	$calDoc = $modx->documentIdentifier;
}
elseif ($calDoc==NULL) {
	return $l['error_caldoc_ajax'];
}
	
// Load xPDO
$xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
	array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('Gregorian', XPDO_CORE_PATH . '../model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

// Try to load calendar, if it fails, show error message.
$cal = $xpdo->getObject('Gregorian',$calId);
if ($cal === NULL) {
	$cal = $xpdo->newObject('Gregorian',$calId);
	$saved = $cal->save();
	if ($cal === NULL) {
		return $l['Could not create calendar'];
	}
	if (!$saved) {
		return $l['Could not save newly created calendar!'];
	}
}

$action = (isset($_REQUEST['action'])) ? $action : 'view';

$output = '';

// Handle actions
if ($action == 'view') {
	// Add js and css to header if 'view' and not AJAX
	$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
	$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
	$modx->regClientStartupScript($snippetUrl.'Gregorian.view.js');
	$modx->regClientCSS($snippetUrl.'layout.css');
	$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');
}

return $output;
