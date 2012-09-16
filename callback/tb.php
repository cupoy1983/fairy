<?php 
include "base.php";
require_once FANWE_ROOT."sdks/taobao/taobao.func.php";
$top_appkey = $_FANWE['cache']['logins']['taobao']['app_key'];
$top_secret = $_FANWE['cache']['logins']['taobao']['app_secret'];

$top_parameters = $_REQUEST['top_parameters'];
$top_sign = $_REQUEST['top_sign'];

if($callback_type == 'session')
{
	$top_session = $_REQUEST['top_session'];
	$parameters = GetTaoBaoParameters($top_parameters);
	echo 'SessionKey：<span style="color:#f00;">'.$top_session.'</span><br/>&nbsp;&nbsp;过期时间：<span style="color:#00f;">'.fToDate((int)$parameters['expires_in'] + TIME_UTC).'</span>';
	exit;
}

if(!CheckTaoBaoSign($top_secret,$top_parameters,$top_sign))
	exit;

require_once FANWE_ROOT."core/class/user/taobao.class.php";
$parameters = GetTaoBaoParameters($top_parameters);

$tu = new TaobaoUser();
switch($callback_type)
{
	case 'login':
		$tu->loginHandler($parameters);
		$url = FU('u/index');
	break;
	
	case 'bind':
		$tu->bindHandler($parameters);
		$url = FU('settings/bind');
	break;
	
	case 'buyer':
		$tu->buyerHandler($parameters);
		$url = FU('settings/buyerverifier');
	break;
}

fSetCookie('callback_type','');
fHeader("location:".$url);
?>