<?php 
define('MODULE_NAME','Look');
define('ACTION_NAME','index');
define('IS_LOOK',true);

require dirname(__FILE__).'/core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->initialize();

require fimport('module/look');
LookModule::index();
?>