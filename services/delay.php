<?php
define('ROOT_PATH', str_replace('services/delay.php', '', str_replace('\\', '/', __FILE__)));
$args = rawurldecode($_REQUEST['args']);
if(empty($args))
	exit;

include ROOT_PATH.'core/function/global.func.php';
include ROOT_PATH.'public/config.global.php';

$args = authcode($args,'DECODE',md5($config['security']['authkey']));
if(empty($args))
	exit;

@ignore_user_abort(true);
@ob_start();
@ob_end_flush(); 
@ob_implicit_flush(true);
echo str_repeat(' ',4096);
@ob_flush();

define('REQUEST_ARGS',$args);
$args = unserialize($args);
unset($_POST['args']);

define('SUB_DIR','/services');
define('MODULE_NAME','Delay');

if(isset($args['m']) && isset($args['a']))
{
	$module = strtolower($args['m']);
	$action = strtolower($args['a']);

    if(preg_match('/[^a-z0-9_]/',$module) || preg_match('/[^a-z0-9_]/',$action))
        exit;

	define('ACTION_NAME',$action);

	define('HANDLER_FILE',ROOT_PATH.'services/delay/'.$module.'/'.$action.'.php');
	if(!file_exists(HANDLER_FILE))
		exit;
}
else
	exit;

include HANDLER_FILE;
?>