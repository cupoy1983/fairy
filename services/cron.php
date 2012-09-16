<?php
define('ROOT_PATH', str_replace('services/cron.php', '', str_replace('\\', '/', __FILE__)));
$args = rawurldecode($_REQUEST['args']);
if(empty($args))
	exit;

include ROOT_PATH.'core/function/global.func.php';
include ROOT_PATH.'public/config.global.php';

$args = authcode($args,'DECODE',md5($config['security']['authkey']));
if(empty($args))
	exit;

define('REQUEST_ARGS',$args);
$args = unserialize($args);
//if(!$args || ($args['request_time'] + 10) < time())
	//exit;

unset($_POST['args']);

define('SUB_DIR','/services');
define('MODULE_NAME','Cron');

if(isset($args['m']) && isset($args['a']))
{
	$module = strtolower($args['m']);
	$action = strtolower($args['a']);

    if(preg_match('/[^a-z0-9_]/',$module) || preg_match('/[^a-z0-9_]/',$action))
        exit;

	define('ACTION_NAME',$action);

	define('HANDLER_FILE',ROOT_PATH.'services/cron/'.$module.'/'.$action.'.php');
	if(!file_exists(HANDLER_FILE))
		exit;
}
else
	exit;
	
if($module == 'collect')
{
	
	if(file_exists(ROOT_PATH."./public/taobao/collect.lock") || 
		file_exists(ROOT_PATH."./public/taobao/collect_setting.lock"))
	{
		@unlink(ROOT_PATH."./public/taobao/collect_setting.lock");
		@unlink(ROOT_PATH."./public/taobao/auto_collect.php");
		exit;
	}
	
	if($action == 'init')
	{
		//如果已经存在自动采集锁定，则退出自动采集
		if(file_exists(ROOT_PATH."./public/taobao/auto_collect.php"))
			exit;
	}
	else
	{
		//如果不存在自动采集锁定，则退出自动采集
		if(!file_exists(ROOT_PATH."./public/taobao/auto_collect.php"))
			exit;
			
		$auto_collect = @include ROOT_PATH."./public/taobao/auto_collect.php";
		
		//如果解析缓存锁定文件失败或者自采集时间对应不上，则退出自动采集
		if(!$auto_collect || $auto_collect['time'] != $args['collect_time'])
			exit;
	}
}

include HANDLER_FILE;
?>