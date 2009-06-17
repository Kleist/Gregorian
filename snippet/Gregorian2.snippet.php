<?php
require_once ('config.php');
require_once ('GregorianController.class.php');

// Handle snippet configuration
$c['calId'] = (isset($calId)) ? $calId : 1;
$c['template'] = (isset($template)) ? $template : 'default';
$c['view'] = (isset($view)) ? $view : 'agenda';
$c['lang'] = (isset($lang)) ? $lang : 'en';
// $c['allCanEdit'] = (isset($allCanEdit)) ? $allCanEdit : false;
// $c['mgrIsAdmin'] = (isset($mgrIsNotAdmin)) ? !$mgrIsNotAdmin : 1;
// $c['count'] = (isset($count)) ? $count : 5;
// $c['ajax'] = (isset($ajax)) ? $ajax : false;
// $c['ajaxId'] = (isset($ajaxId)) ? $ajaxId : NULL;
// $c['calDoc'] = (isset($calDoc)) ? $calDoc : NULL;

$snippetUrl = $modx->config['base_url'].'assets/snippets/Gregorian/';
$snippetDir = $_SERVER['DOCUMENT_ROOT'].$snippetUrl;

$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
$modx->regClientStartupScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js');
$modx->regClientStartupScript($snippetUrl.'Gregorian.js');
$modx->regClientCSS($snippetUrl.'layout.css');
$modx->regClientCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');

$controller = new GregorianController($c);

return $controller->handle();
