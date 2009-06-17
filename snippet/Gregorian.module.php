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

if (!is_object($modx)) die("You shouldn't be here!");

// Load configuration & xPDO
require_once ('config.php');

// Module parameters
$count = (isset($count)) ? $count : 5;

$snippetUrl = $modx->config['base_url'].'assets/snippets/Gregorian/';
$snippetDir = $_SERVER['DOCUMENT_ROOT'].$snippetUrl;

if (!isset($id)) return 'No calendar id supplied'	;

// Load xPDO
$xpdo= new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
	array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));
$xpdo->setPackage('Gregorian', XPDO_CORE_PATH . '../model/');
// $xpdo->setDebug();
// $xpdo->setLoglevel(XPDO_LOG_LEVEL_INFO);

// Try to load calendar, if it fails, show error message.
$cal = $xpdo->getObject('Gregorian',$id);
if ($cal === NULL) {
	$cal = $xpdo->newObject('Gregorian',$id);
	$saved = $cal->save();
	if ($cal === NULL) {
		return 'Could not load or create calendar';
	}
	if (!$saved) {
		return 'Could not save newly created calendar!';
	}
}

// Set URLs
$cal->setConfig('mainUrl', $_SERVER['REQUEST_URI']);
$cal->setConfig('ajaxUrl', $ajaxUrl);

// Load template
$cal->loadTemplate($snippetDir.'default.template.php');
// View preferences
$cal->setConfig('count',   $count);

// Set privileges
$cal->setConfig('isEditor');

/**
 * @todo Add required javascript (Could/should this be done by the class?)
 */
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
$modx->regClientStartupScript($snippetUrl.'Gregorian.js');
$modx->regClientCSS($snippetUrl.'layout.css');
$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');

// Handle request
$header = "<html><head><title>Gregorian Module</title>";
$header .= $modx->getRegisteredClientStartupScripts();
$header .= "</head><body>";
$footer = $modx->getRegisteredClientScripts();
$footer .= "</body></html>";
return $header.$cal->handleRequest().$footer;