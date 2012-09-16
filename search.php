<?php 
define('MODULE_NAME','Search');

$actions = array('all','bao','photo','album','user','group','topic');
$action = 'bao';

if(isset($_REQUEST['action']))
{
	$action = strtolower($_REQUEST['action']);
	if(!in_array($action,$actions))
		$action = 'bao';
}

define('ACTION_NAME',$action);

require dirname(__FILE__).'/core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->initialize();

require fimport('module/search');

switch(ACTION_NAME)
{
	case 'bao':
	case 'all':
	case 'photo':
		SearchModule::share();
	break;
	case 'album':
		SearchModule::album();
	break;
	case 'user':
		SearchModule::user();
	break;
	case 'group':
		SearchModule::group();
	break;
	case 'topic':
		SearchModule::topic();
	break;
}
?>