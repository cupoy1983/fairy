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

$index = (int)$_FANWE['request']['index'];
$type = $_FANWE['request']['type'];
if(!in_array($type,array('w','d')))
	exit;

@ignore_user_abort(true);
setTimeLimit(0);
@ob_start();
@ob_end_flush(); 
@ob_implicit_flush(true);
echo str_repeat(' ',4096);

if($index == 0)
{
	FDB::query('TRUNCATE TABLE '.FDB::table('share_collect_temp'));
	if($type == 'w')
	{
		FDB::query('TRUNCATE TABLE '.FDB::table('share_collect1'));
		FDB::query('INSERT INTO '.FDB::table('share_collect_temp').'(share_id) SELECT share_id FROM '.FDB::table('share_collect7'));
		FDB::query('TRUNCATE TABLE '.FDB::table('share_collect7'));
	}
	else
	{
		FDB::query('INSERT INTO '.FDB::table('share_collect_temp').'(share_id) SELECT share_id FROM '.FDB::table('share_collect1'));
		FDB::query('TRUNCATE TABLE '.FDB::table('share_collect1'));
	}
}

$tables = array(
	'share',
	'share_images_index',
	'share_goods_index',
	'share_photo_index',
	'share_dapei_index',
	'share_dapei_goods',
	'share_look_index',
	'share_look_goods',
	'album_share_index',
);

if($index >= count($tables))
	exit;

$table = $tables[$index];
if($type == 'w')
{
	FDB::query('UPDATE '.FDB::table($table).' SET collect_1count = 0,collect_7count = 0 
		WHERE share_id IN (SELECT share_id FROM '.FDB::table('share_collect_temp').')');
}
else
{
	FDB::query('UPDATE '.FDB::table($table).' SET collect_1count = 0 
		WHERE share_id IN (SELECT share_id FROM '.FDB::table('share_collect_temp').')');
}

sleep(1);
$index++;
$args = array('m'=>'share','a'=>'collect_count','type'=>$type,'index'=>$index);
FS("Cron")->createRequest($args);
?>
