<?php 
define('MODULE_NAME','Group');

$actions = array('index','detail','create','edit','save','update','agreement','users','apply');
$action = 'index';

if(isset($_REQUEST['action']))
{
	$action = strtolower($_REQUEST['action']);
	if(!in_array($action,$actions))
		$action = 'index';
}

define('ACTION_NAME',$action);
require dirname(__FILE__).'/core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->cache_list[] = 'forum_category';
$fanwe->initialize();

require fimport('module/group');

switch(ACTION_NAME)
{
	case 'index':
		GroupModule::index();
	break;
	case 'detail':
		GroupModule::detail();
	break;
	case 'create':
		GroupModule::create();
	break;
	case 'edit':
		GroupModule::edit();
	break;
	case 'save':
		GroupModule::save();
	break;
	case 'update':
		GroupModule::update();
	break;
	case 'agreement':
		GroupModule::agreement();
	break;
	case 'users':
		GroupModule::users();
	break;
	case 'apply':
		GroupModule::apply();
	break;
}
?>