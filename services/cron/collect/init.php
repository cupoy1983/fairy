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

$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
if(!$config || (int)$config['is_auto_collect'] == 0 || empty($config['cate_ids']))
	exit;

if(empty($config['user_ids']) && (int)$config['user_gid'] == 0)
	exit;
	
//写入自动采集锁定，如果失败，则退出自动采集
if(!@file_put_contents(FANWE_ROOT."./public/taobao/auto_collect.php",'<?php return array("time"=>'.TIME_UTC.') ?>'))
	exit;

FDB::query('TRUNCATE TABLE '.FDB::table('taobao_collect'));
FDB::query('TRUNCATE TABLE '.FDB::table('taobao_shop_temp'));
FDB::query('TRUNCATE TABLE '.FDB::table('taobao_share'));
		
$args = array();
$args['m'] = 'collect';
$args['a'] = 'collect';
$args['collect_time'] = TIME_UTC;
$args['taobao_collect_cindex'] = 0;
$args['taobao_collect_page'] = 1;
$args['taobao_collect_errnum'] = 0;
FS("Cron")->createRequest($args);
?>
