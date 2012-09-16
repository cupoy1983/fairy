<?php 
$code = $_REQUEST['code'];
if(empty($code))
	exit;

include "base.php";
require_once FANWE_ROOT."core/class/user/sina.class.php";
$oauth= new SaeTOAuthV2($_FANWE['cache']['logins']['sina']['app_key'],$_FANWE['cache']['logins']['sina']['app_secret']);
$keys = array();
$keys['code'] = $_FANWE['request']['code'];
$keys['redirect_uri'] = $_FANWE['site_url']."callback/sina.php";
try
{
	$token = $oauth->getAccessToken('code',$keys);
}
catch (OAuthException $e)
{
	die($e->getMessage());
}

$sina = new SinaUser();
switch($callback_type)
{
	case 'login':
		$sina->loginHandler($token);
		$url = FU('u/index');
	break;
	
	case 'bind':
		$sina->bindHandler($token);
		$url = FU('settings/bind');
	break;
}

fSetCookie('callback_type','');
fHeader("location:".$url);
?>