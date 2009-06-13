<?php
$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tstart= $mtime;

require_once ('build.config.php');
include_once ('../../xpdo/xpdo.class.php');

//Set some valid connection properties here
$xpdo= new xPDO(
    XPDO_DSN,
    XPDO_DB_USER,
    XPDO_DB_PASS,
    XPDO_TABLE_PREFIX,
    array (
        PDO_ATTR_ERRMODE => PDO_ERRMODE_WARNING,
        PDO_ATTR_PERSISTENT => false,
        PDO_MYSQL_ATTR_USE_BUFFERED_QUERY => true
    )
);

$xpdo->setPackage('xpdocal', dirname(dirname(__FILE__)) . '/');
$xpdo->setDebug(true);

$manager= $xpdo->getManager();

$classes= array (
    'xpdoCalendar',
    'xpdoCalendarEvent',
    'xpdoCalendarTag',
    'xpdoCalendarEventTag',
);
foreach ($classes as $class) {
    $manager->createObjectContainer($class);
}

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

echo "\nExecution time: {$totalTime}\n";

exit ();
