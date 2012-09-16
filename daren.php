<?php 
define('MODULE_NAME','Daren');
$actions = array('index','look','dapei','group','album','apply','save');
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
$fanwe->cache_list[] = 'citys';
$fanwe->cache_list[] = 'daren_cate';
$fanwe->initialize();

require fimport('module/daren');
switch(ACTION_NAME)
{
	case 'index':
		DarenModule::index();
		break;
	case 'look':
		DarenModule::look();
		break;
	case 'dapei':
		DarenModule::dapei();
		break;
	case 'group':
		DarenModule::group();
		break;
	case 'album':
		DarenModule::album();
		break;
	case 'apply':
		DarenModule::apply();
		break;
	case 'save':
		DarenModule::save();
		break;
}
?>