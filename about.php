<?php
define('MODULE_NAME', 'About');

$actions = array(
		'about',
		'contact',
		'link',
		'help',
		'iphone',
		'android'
);
$action = 'about';
if(isset($_REQUEST['action'])){
	$action = strtolower($_REQUEST['action']);
	if(! in_array($action, $actions)){
		$action = 'about';
	}
}

define('Action_NAME', $action);

require dirname(__FILE__).'/core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->initialize();

require fimport('module/about');

switch(Action_NAME) {
	
	case 'about' :
		AboutModule::about();
		break;
	case 'contact' :
		AboutModule::contact();
		break;
	case 'link' :
		AboutModule::link();
		break;
	case 'help' :
		AboutModule::help();
		break;
	case 'iphone' :
		AboutModule::iphone();
		break;
	case 'android' :
		AboutModule::android();
		break;
}

?>