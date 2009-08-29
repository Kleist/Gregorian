<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Snippet parameters:
 * calId        - Database id of calendar (set if more than one on site)  default: 1
 * 
 * adminGroup   - Name of webgroup that can edit calendar               default: ''
 * mgrIsAdmin   - All users logged in to the manager can edit calendar  default: 1
 * allowAddTag  - Should Editors be able to add new tags?               default: 1
 * 
 * template     - Name of the template to use                           default: 'default'
 * lang         - Language code                                         default: 'en'
 * formatForICal- Format dates for iCal                                 default: 0
 * 
 * count        - Number of calendar items to show per page             default: 10
 * TODO view    - (option to show items in other ways than 'agenda' aka 'list')
 * 
 * view         - Currenty only 'agenda' is available                     default: 'agenda'
 * 
 * AJAX-related. (Not implemented!)
 * ajaxId      - Id of the ajax processor document (the document with ajax=`1` snippet call) (default: 0)
 * ajax		   - This is the ajax-processor snippet call, (default: 0)
 * calId       - Id of the calendar-document, used in the ajax-processor snippet call. (default: 0)
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
if (!is_object($modx)) die("You shouldn't be here!");

require_once ('config.php');
require_once('GregorianController.class.php');

// Load xPDO
$xpdo = new xPDO(XPDO_DSN, XPDO_DB_USER, XPDO_DB_PASS, XPDO_TABLE_PREFIX, 
    array (PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING, PDO_ATTR_PERSISTENT => false, PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true));

// Init controller
$gc =  new GregorianController(&$modx, &$xpdo);
$gc->set($defaultConfig);

// Set snippet configuration
if (is_integer($calId))     $gc->set('calId',           $calId);
if (isset($adminGroup))     $gc->set('adminGroup',      $adminGroup);
if (isset($mgrIsAdmin))     $gc->set('mgrIsAdmin',      $mgrIsAdmin);
if (isset($allowAddTag))    $gc->set('allowAddTag',     $allowAddTag);

if (isset($template))       $gc->setTemplate($template);
if (isset($view))           $gc->setView($view);

if (isset($formatForICal))  $gc->set('formatForICal',   $formatForICal);
if (isset($lang))           $gc->set('lang',            $lang);
if (isset($count))          $gc->set('count',           $count);
if (isset($offset))         $gc->set('offset',          $offset);
if (isset($filter))         $gc->set('filter',          $filter);
if (isset($snippetUrl))     $gc->set('snippetUrl',      $snippetUrl);
if ($debug)                 $gc->setDebug();

// Load calendar
$gc->setCalendar($xpdo->getObject('Gregorian',$gc->get('calId')));

return $gc->handle();
