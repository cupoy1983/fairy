<?php
require ROOT_PATH.'core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->is_session = false;
$fanwe->is_user = false;
$fanwe->is_cron = false;
$fanwe->is_misc = false;
$fanwe->cache_list = array('business');
$fanwe->initialize();

$_FANWE['request'] = unserialize(REQUEST_ARGS);

@ignore_user_abort(true);
setTimeLimit(0);
@ob_start();
@ob_end_flush(); 
@ob_implicit_flush(true);
echo str_repeat(' ',4096);

include_once FANWE_ROOT.'sdks/taobao/TopClient.php';
include_once FANWE_ROOT.'sdks/taobao/request/TaobaokeReportGetRequest.php';

$count = (int)$_FANWE['request']['count'];
$page = $_FANWE['page'];
if($page == 1)
{
	@unlink(FANWE_ROOT.'public/taobao/last.report.php');
	$last = FDB::fetchFirst('SELECT * FROM '.FDB::table('taobaoke_report').' ORDER BY id DESC');
	file_put_contents(FANWE_ROOT.'public/taobao/last.report.php',"<?php\n return ".var_export($last, true).";\n?>");
	FDB::query('TRUNCATE TABLE '.FDB::table('taobaoke_report_temp'));
}
else
	$last = include FANWE_ROOT.'public/taobao/last.report.php';

$page_size = 100;
$client = new TopClient();
$client->appkey = trim($_FANWE['cache']['business']['taobao']['app_key']);
$client->secretKey = trim($_FANWE['cache']['business']['taobao']['app_secret']);

$req = new TaobaokeReportGetRequest();
$req->setFields("num_iid,outer_code,commission_rate,real_pay_fee,app_key,outer_code,pay_time,pay_price,commission,item_title,item_num,trade_id");

$today_time = getTodayTime();
$report_time = (int)@file_get_contents(FANWE_ROOT.'public/taobao/report.time.php');
if($report_time == 0)
	$report_time = $today_time - 86400;
elseif($today_time == $report_time)
	exit;

$time = fToDate($report_time,'Ymd');
$req->setDate($time);
$req->setPageNo($page);
$req->setPageSize($page_size);
$resp = (array)$client->execute($req,trim($_FANWE["cache"]["business"]["taobao"]["session_key"]));
$is_complete = false;
$total_results = 0;

if(isset($resp['taobaoke_report']))
{
	$count = 0;
	$taobaoke_report = (array)$resp['taobaoke_report'];
	$total_results = (int)$taobaoke_report['total_results'];
	
	if($total_results > 0)
	{
		$taobaoke_report_members = $taobaoke_report['taobaoke_report_members'];
		foreach($taobaoke_report_members->taobaoke_report_member as $item)
		{
			$item = (array)$item;
			$item['pay_time'] = str2Time($item['pay_time']);
			$item['outer_code'] = isset($item['outer_code']) ? $item['outer_code'] : '';
			if($last)
			{
				if($last['pay_time'] == $item['pay_time'] 
					&& $last['trade_id'] == $item['trade_id'] 
					&& $last['num_iid'] == $item['num_iid'] 
					&& $last['item_num'] == $item['item_num'] 
					&& $last['app_key'] == $item['app_key'] 
					&& $last['outer_code'] == $item['outer_code'])
				{
					$is_complete = true;
					break;
				}
			}
			
			$pay_day = fToDate($item['pay_time'],'Y-m-d 00:00:00');
			$item['pay_day'] = str2Time($pay_day);
			$item['commission_rate'] = $item['commission_rate'] * 100;
			$item['item_title'] = addslashes($item['item_title']);

			FDB::insert('taobaoke_report_temp',$item);
			if(!empty($item['outer_code']) && preg_match("/^o\d+$/",$item['outer_code']))
			{
				$order_id = (float)substr($item['outer_code'],1);
				if($order_id == 0)
					continue;
				
				$res = FDB::query('SELECT * FROM '.FDB::table('goods_order').' 
					WHERE order_id = '.$order_id.' AND keyid = \'taobao_'.$item['num_iid'].'\' AND status = 0');
				while($order = FDB::fetch($res))
				{
					$commission = ((float)$item['commission']) * ((float)$order['commission_rate'] / 100); 
					FDB::query('UPDATE '.FDB::table('goods_order').' SET status = 1,settlement_time = '.TIME_UTC.',commission = '.$commission.' WHERE order_id = '.$order_id.' AND uid = '.(int)$order['uid']);
				}
			}
		}

		if($page * $page_size >= $total_results)
			$is_complete = true;
	}
	else
	{
		checkReportTime($report_time);
		exit;
	}
}
else
{
	$count++;
	if($count < 10)
		getTaobaokeReport($page,$count);
	else
		checkReportTime($report_time);
	exit;
}

if($is_complete)
{
	FDB::query('INSERT INTO '.FDB::table('taobaoke_report').'(id,trade_id,num_iid,item_title,item_num,pay_price,real_pay_fee,commission_rate,commission,outer_code,app_key,pay_time,pay_day) SELECT NULL AS id,trade_id,num_iid,item_title,item_num,pay_price,real_pay_fee,commission_rate,commission,outer_code,app_key,pay_time,pay_day FROM '.FDB::table('taobaoke_report_temp').' ORDER BY pay_time ASC,trade_id ASC');
	
	checkReportTime($report_time);
	exit;
}
else
{
	$page++;
	getTaobaokeReport($page,0);
	exit;
}

function checkReportTime($report_time)
{
	$get_time = $report_time + 86400;
	@file_put_contents(FANWE_ROOT.'public/taobao/report.time.php',$get_time);
	if($get_time < getTodayTime())
		getTaobaokeReport(1,0);
}

function getTaobaokeReport($page,$count)
{
	sleep(1);
	$args = array('m'=>'goods_order','a'=>'taobao','page'=>$page,'count'=>$count);
	FS("Cron")->createRequest($args);
}
?>