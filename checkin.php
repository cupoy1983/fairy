<?php 
define('MODULE_NAME','Checkin');

$actions = array('index','checkin_ajax','checkintimes');
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
$fanwe->cache_list[] = 'checkin';
$fanwe->initialize();
require fimport('module/checkin');

$_FANWE['nav_title'] = lang('common','checkin');

switch(ACTION_NAME)
{
    case 'index':
        CheckinModule::index();
    break;
    case 'checkin_ajax':
        CheckinModule::checkin_ajax();
    break;
    case 'checkintimes':
        CheckinModule::checkintimes();
    break;
}
?>