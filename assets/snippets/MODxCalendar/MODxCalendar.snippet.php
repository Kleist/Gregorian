<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * TODO - list
 *
 * Functionality
 * - Filter by tag
 * - Search
 * - Create event
 * - Copy event
 * - Add tagging to form
 * - Other means of authorizing editors (Special MODx-document for editing, by web-user, by web-group)
 * 
 * UI
 * - AJAX - inline edit
 * - Show by months
 * - Enhance form
 * - Date-picker
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Snippet parameters:
 *
 * allCanEdit - Allow any user to edit the calendar (default: 0)
 * mgrIsAdmin - All users logged in to the manager can edit calendar (default: 1)
 * count      - Number of calendar items to show per page (default: 5)
 * ajax		  - This is the ajax-processor snippet call
 * ajaxId     - Id of document with ajax=`1` snippet call
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
// Load configuration & xPDO
require_once ('config.php');

// Snippet parameters
$allCanEdit = (isset($allCanEdit)) ? $allCanEdit : false;
$mgrIsAdmin = (isset($mgrIsNotAdmin)) ? $mgrIsNotAdmin : 1;
$count = (isset($count)) ? $count : 5;
$ajax = (isset($ajax)) ? $ajax : false;
$ajaxId = (isset($ajaxId)) ? $ajaxId : NULL;
$calDoc = (isset($calDoc)) ? $calDoc : NULL;

$isAdmin = ($mgrIsAdmin && $_SESSION['mgrValidated']);

if (!isset($id)) return 'No calendar id supplied'	;

if ($ajax) {
	if ($calDoc === NULL) {
		return "Snippet call with &ajax=`1` requires &calDoc to point to main calendar document";
	}
}
else { // Not AJAX
	$calDoc = $modx->documentIdentifier;
}

if ($ajaxId !== NULL) { // this is the main doc
	$ajaxUrl = $modx->makeUrl($ajaxId);
} 
elseif ($ajax) { // this is the AJAX doc
	$ajaxUrl = $modx->makeUrl($modx->documentIdentifier);
} else { // AJAX is not enabled
	$ajaxUrl = NULL;
}

// Load xPDO
$xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
	array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('MODxCalendar', XPDO_CORE_PATH . '../model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

// Try to load calendar, if it fails, show error message.
$cal = $xpdo->getObject('MODxCalendar',$id);
if ($cal === NULL) {
	return BScal_lang('Could not load calendar');
}

// Set URLs
$cal->setConfig('mainUrl', $modx->makeUrl($calDoc));
$cal->setConfig('ajaxUrl', $ajaxUrl);

// View preferences
$cal->setConfig('count',   $count);

// Set privileges
if ($isAdmin) $cal->setConfig('isEditor');

/**
 * @todo Add required javascript (Could/should this be done by the class?)
 */
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
$modx->regClientStartupScript($modx->config['base_url'].'assets/snippets/bscal/bscal.js');
$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');

// Handle request
return $cal->handleRequest();