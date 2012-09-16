<?php 
$code = $_REQUEST['code'];
if(empty($code))
	exit;

include "base.php";
require_once FANWE_ROOT."core/class/user/tqq.class.php";

$code = $_FANWE['request']['code'];
$openid = $_FANWE['request']['openid'];
$openkey = $_FANWE['request']['openkey'];
TqqOAuth::init($_FANWE['cache']['logins']['tqq']['app_key'],$_FANWE['cache']['logins']['tqq']['app_secret']);
$url = TqqOAuth::getAccessToken($code,$_FANWE['site_url']."callback/tqq.php");
$result = TqqHttp::request($url);
parse_str($result,$args);
if($args['access_token'])
{
	$_FANWE['login_oauth']['tqq'] = array();
	$_FANWE['login_oauth']['tqq']['t_access_token'] = $args['access_token'];
	$_FANWE['login_oauth']['tqq']['t_expire_in'] = $args['expires_in'];
	$_FANWE['login_oauth']['tqq']['t_code'] = $code;
	$_FANWE['login_oauth']['tqq']['t_openid'] = $openid;
	$_FANWE['login_oauth']['tqq']['t_openkey'] = $openkey;
	
	//验证授权
	$result = TqqOAuth::checkOAuthValid();
	if(!$result)
	{
		exit('<h3>授权失败,请重试</h3>');
	}
}
else 
{
	exit($result);
}

$tqq = new TqqUser();
switch($callback_type)
{
	case 'login':
		$tqq->loginHandler();
		$url = FU('u/index');
	break;
	
	case 'bind':
		$tqq->bindHandler();
		$url = FU('settings/bind');
	break;
}

fSetCookie('callback_type','');
fHeader("location:".$url);
?>