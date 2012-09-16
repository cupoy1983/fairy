<?php 
define('MODULE_NAME','Book');
$actions = array('cate','shopping','dapei','look');
$action = 'shopping';

if(isset($_REQUEST['action']))
{
	$action = strtolower($_REQUEST['action']);
	if(!in_array($action,$actions))
		$action = 'shopping';
}

define('ACTION_NAME',$action);

require dirname(__FILE__).'/core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->initialize();

require fimport('module/book');

switch(ACTION_NAME)
{
	case 'cate':
		BookModule::cate();
	break;
	case 'shopping':
		BookModule::shopping();
	break;
	case 'dapei':
		BookModule::dapei();
	break;
	case 'look':
		BookModule::look();
	break;
}
?>