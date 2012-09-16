<?php
require ROOT_PATH.'core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->is_session = false;
$fanwe->is_user = false;
$fanwe->is_cron = false;
$fanwe->is_misc = false;
$fanwe->cache_list = array();
$fanwe->initialize();

$_FANWE['request'] = unserialize(REQUEST_ARGS);

@ignore_user_abort(true);
@ob_start();
@ob_end_flush(); 
@ob_implicit_flush(true);
echo str_repeat(' ',4096);

$taobao_collect_cindex = $_FANWE['request']['taobao_collect_cindex'];
$taobao_collect_page = $_FANWE['request']['taobao_collect_page'];
$taobao_collect_errnum = $_FANWE['request']['taobao_collect_errnum'];

$args = array();
$args['m'] = 'collect';
$args['collect_time'] = $_FANWE['request']['collect_time'];

$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
if($taobao_collect_page > $config['page_num'])
{
	$taobao_collect_page = 1;
	$taobao_collect_cindex++;
}

$cids = explode(',',$config['cate_ids']);
if($taobao_collect_cindex >= count($cids))
{
	//采集成功
	FDB::query('INSERT INTO '.FDB::table('taobao_shop_temp').' SELECT NULL,nick FROM '.FDB::table('taobao_collect').' GROUP BY nick');
	$args['a'] = 'shop';
	sleep(1);
	FS("Cron")->createRequest($args);
	exit;
}

$args['a'] = 'collect';

$cate_id = $cids[$taobao_collect_cindex];
$keywords = trim($config['keywords'][$cate_id]);
		
$result = FS('Goods')->collect($cate_id,$keywords,$config['sort_order'],$taobao_collect_page);
if($result['status'] == 1)
{
	$taobao_collect_errnum = 0;
	if($taobao_collect_page >= $result['max_page'])
	{
		$taobao_collect_page = 0;
		$taobao_collect_cindex++;
	}
}
else
{
	$taobao_collect_errnum++;
	if($taobao_collect_errnum < 10)
	{
		$args['taobao_collect_cindex'] = $taobao_collect_cindex;
		$args['taobao_collect_page'] = $taobao_collect_page;
		$args['taobao_collect_errnum'] = $taobao_collect_errnum;
		sleep(1);
		FS("Cron")->createRequest($args);
		exit;
	}
	else
		$taobao_collect_errnum = 0;
}

$taobao_collect_page++;
$args['taobao_collect_cindex'] = $taobao_collect_cindex;
$args['taobao_collect_page'] = $taobao_collect_page;
$args['taobao_collect_errnum'] = $taobao_collect_errnum;
sleep(1);
FS("Cron")->createRequest($args);
?>
