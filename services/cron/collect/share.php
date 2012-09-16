<?php
require ROOT_PATH.'core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->is_session = false;
$fanwe->is_user = false;
$fanwe->is_cron = false;
$fanwe->is_misc = false;
$fanwe->cache_list = array('user_group','goods_category','image_servers');
$fanwe->initialize();

$_FANWE['request'] = unserialize(REQUEST_ARGS);

@ignore_user_abort(true);
@ob_start();
@ob_end_flush(); 
@ob_implicit_flush(true);
echo str_repeat(' ',4096);

$result = FS("Goods")->share();
if($result == 1)
{
	@unlink(FANWE_ROOT."./public/taobao/auto_collect.php");
	@file_put_contents(FANWE_ROOT."./public/taobao/collect_complete.php",'<?php return array("time"=>'.TIME_UTC.') ?>');
	
	$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
	if(!$config || (int)$config['is_auto_collect'] == 0 || empty($config['cate_ids']))
		exit;
	
	$collect_time = (int)$config['collect_time'];
	if($collect_time < 1)
		$collect_time = 12;
	
	$cron = array();
	$cron['server'] = 'collect';
	$cron['run_time'] = TIME_UTC + ($collect_time * 3600);
	FDB::insert('cron',$cron);
	exit;
}

$args = array('m'=>'collect','a'=>'share','collect_time'=>$_FANWE['request']['collect_time']);
FS("Cron")->createRequest($args);
?>
