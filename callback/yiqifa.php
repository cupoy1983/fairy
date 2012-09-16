<?php 
define('ROOT_PATH', str_replace('callback/yiqifa.php', '', str_replace('\\', '/', __FILE__)));
define('SUB_DIR','/callback');
define('MODULE_NAME','Callback');

if(empty($_REQUEST['unique_id']))
	exit;

include ROOT_PATH.'public/data/caches/system/business.cache.php';
$data_secret = $data['business']['yiqifa']['data_secret'];
$action_id = urldecode(trim($_REQUEST['action_id']));
$order_no = urldecode(trim($_REQUEST['order_no']));
$prod_money = urldecode(trim($_REQUEST['prod_money']));
$order_time = urldecode(trim($_REQUEST['order_time']));
$chkcode = urldecode(trim($_REQUEST['chkcode']));

if(strtolower(md5($action_id.$order_no.$prod_money.$order_time.$data_secret)) != $chkcode)
	exit;

require ROOT_PATH.'core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->is_session = false;
$fanwe->is_user = false;
$fanwe->is_cron = false;
$fanwe->is_misc = false;
$fanwe->cache_list = array('business');
$fanwe->initialize();

$unique_id = trim($_FANWE['request']['unique_id']);

/*$is_report = (int)FDB::resultFirst('SELECT COUNT(unique_id) FROM '.FDB::table('yiqifa_report')." WHERE unique_id = '".$unique_id."'");
if($is_report > 0)
	die('0');*/

$report = array();
$report['unique_id'] = $unique_id;
$report['action_id'] = (int)$_FANWE['request']['action_id'];
$report['action_name'] = addslashes(urldecode(trim($_FANWE['request']['action_name'])));
$report['sid'] = (int)$_FANWE['request']['sid'];
$report['wid'] = (int)$_FANWE['request']['wid'];
$report['order_no'] = addslashes(urldecode(trim($_FANWE['request']['order_no'])));
$report['order_time'] = str2Time(urldecode(trim($_FANWE['request']['order_time'])));
$report['prod_id'] = (float)trim($_FANWE['request']['action_id']);
$report['prod_name'] = addslashes(urldecode(trim($_FANWE['request']['prod_name'])));
$report['prod_count'] = (int)$_FANWE['request']['prod_count'];
$report['prod_money'] = (float)$_FANWE['request']['prod_money'];
$report['comm_type'] = addslashes(urldecode(trim($_FANWE['request']['comm_type'])));
$report['commision'] = (float)$_FANWE['request']['commision'];
$report['feed_back'] = addslashes(urldecode(trim($_FANWE['request']['feed_back'])));
$report['status'] = trim($_FANWE['request']['status']);
$report['prod_type'] = addslashes(urldecode(trim($_FANWE['request']['prod_type'])));
$report['am'] = (float)$_FANWE['request']['am'];
$report['create_date'] = str2Time(urldecode(trim($_FANWE['request']['order_time'])));
$order_day = fToDate($report['order_time'],'Y-m-d 00:00:00');
$report['order_day'] = str2Time($order_day);

FDB::insert('yiqifa_report',$report,false,true);
if($report['status'] == 'A' && !empty($report['feed_back']) && preg_match("/^o\d+$/",$report['feed_back']))
{
	$order_id = (float)substr($report['feed_back'],1);
	if($order_id > 0)
	{
		$res = FDB::query('SELECT * FROM '.FDB::table('goods_order').' 
			WHERE order_id = '.$order_id.' AND status = 0');
		while($order = FDB::fetch($res))
		{
			$commission = $report['commision'] * (float)$order['commission_rate'] / 100; 
			FDB::query('UPDATE '.FDB::table('goods_order').' SET status = 1,settlement_time = '.TIME_UTC.',commission = '.$commission.' WHERE order_id = '.$order_id.' AND uid = '.(int)$order['uid']);
		}
	}
}
echo '1';
?>