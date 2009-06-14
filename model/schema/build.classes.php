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
$xpdo->setPackage('MODxCalendar', XPDO_CORE_PATH . '../model/');
$xpdo->setDebug(true);

$manager= $xpdo->getManager();
$generator= $manager->getGenerator();

//Use this to generate classes and maps from a schema
// NOTE: by default, only maps are overwritten; delete class files if you want to regenerate classes
$generator->classTemplate= <<<EOD
<?php
class [+class+] extends [+extends+] {
    function [+class+](& \$xpdo) {
        \$this->__construct(\$xpdo);
    }
    function __construct(& \$xpdo) {
        parent :: __construct(\$xpdo);
    }
}
?>
EOD;
$generator->platformTemplate= <<<EOD
<?php
require_once (dirname(dirname(__FILE__)) . '/[+class-lowercase+].class.php');
class [+class+]_[+platform+] extends [+class+] {
    function [+class+]_[+platform+](& \$xpdo) {
        \$this->__construct(\$xpdo);
    }
    function __construct(& \$xpdo) {
        parent :: __construct(\$xpdo);
    }
}
?>
EOD;
$generator->parseSchema('MODxCalendar.mysql.schema.xml', XPDO_CORE_PATH . '../model/');

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

echo "\nExecution time: {$totalTime}\n";

exit ();
