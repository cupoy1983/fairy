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

if(FS("Goods")->collectShop() == 0)
	$args = array('m'=>'collect','a'=>'shop','collect_time'=>$_FANWE['request']['collect_time']);
else
	$args = array('m'=>'collect','a'=>'goods','collect_time'=>$_FANWE['request']['collect_time']);

sleep(1);
FS("Cron")->createRequest($args);
?>
