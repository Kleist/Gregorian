<?php
// define the connection variables
define('XPDO_DSN', 'mysql:host=localhost;dbname=modx;charset=utf8');
define('XPDO_DB_USER', 'root');
define('XPDO_DB_PASS', '');
define('XPDO_TABLE_PREFIX', 'modx_');
require_once(dirname(__FILE__)."/lib/xpdo/xpdo.class.php");

$defaultConfig = array(
'calId'         => 1,
'view'          => 'list',
'count'         => 10,
'filter'        => array(),
'lang'          => 'en',
'adminGroups'   => array(),
'mgrIsAdmin'    => true,
'allowAddTag'   => true,
'showWarnings'  => false,
'snippetUrl'    => '/assets/snippets/Gregorian/',
'dateFormat'    => '%a %e. %b.',
'timeFormat'    => '%H:%M',
'debugLevel'    => 0
);