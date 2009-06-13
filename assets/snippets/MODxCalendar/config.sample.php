<?php
// define the connection variables
define('XPDO_DSN', 'mysql:host=localhost;dbname=modx;charset=utf8');
define('XPDO_DB_USER', 'root');
define('XPDO_DB_PASS', '');
define('XPDO_TABLE_PREFIX', 'modx_');
require_once(dirname(dirname(dirname(dirname(__FILE__))))."/xpdo/xpdo.class.php");
