<?php
define('IN_FANWE', true);
function initFanwe(&$server)
{
	global $_FANWE;
	if(!_fanweChecker())
		die("domain not authorized");

	if(!file_exists(FANWE_ROOT.'./public/install.lock'))
	{
		header('Location: install/index.php');
		exit;
	}
	
	if(phpversion() < '5.3.0')
		set_magic_quotes_runtime(0);
	
	define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
	define('ICONV_ENABLE', function_exists('iconv'));
	define('MB_ENABLE', function_exists('mb_convert_encoding'));
	define('EXT_OBGZIP', function_exists('ob_gzhandler'));
	define('TIMESTAMP', time());

	define('IS_ROBOT', checkRobot());
	if(function_exists('ini_get'))
	{
		$memory_limit = @ini_get('memory_limit');
		if($memory_limit && getBytes($memory_limit) < 33554432 && function_exists('ini_set'))
		{
			ini_set('memory_limit', '128M');
		}
	}
	
	if(defined('IS_ADMIN_REL'))
		$server->is_admin = true;
	else
	{
		foreach ($GLOBALS as $key => $value)
		{
			if (!isset($server->allow_global[$key]))
			{
				$GLOBALS[$key] = NULL;
				unset($GLOBALS[$key]);
			}
		}
	}
	
	$_FANWE = array();

	if(!defined('IS_UPYUN') && file_exists(FANWE_ROOT."public/yun/UpYun.php"))
	{
		$upyun = @include(FANWE_ROOT."public/yun/UpYun.php");
		if($upyun = @include(FANWE_ROOT."public/yun/UpYun.php"))
		{
			$_FANWE['UPYUN_SETTING'] = $upyun;
			if((int)$upyun['status'] == 1)
				define('IS_UPYUN',TRUE);
			else
				define('IS_UPYUN',FALSE);
		}
		else
		{
			define('IS_UPYUN',FALSE);
		}
	}
	else
	{
		define('IS_UPYUN',FALSE);
	}


	$_FANWE['uid'] = 0;
	$_FANWE['user_name'] = '';
	$_FANWE['gid'] = 0;
	$_FANWE['sid'] = '';
	$_FANWE['form_hash'] = '';
	$_FANWE['client_ip'] = getFClientIp();
	$_FANWE['referer'] = '';

	$_FANWE['php_self'] = htmlspecialchars(getPhpSelf());
	if($_FANWE['php_self'] === false)
		systemError('request_tainting');
	
	$_FANWE['module_name'] = MODULE_NAME;
	$_FANWE['module_filename'] = basename($_FANWE['php_self']);
	$_FANWE['site_url'] = '';
	$_FANWE['site_root'] = '';
	$_FANWE['site_port'] = '';

	$_FANWE['config'] = array();
	$_FANWE['setting'] = array();
	$_FANWE['user'] = array();
	$_FANWE['group'] = array();
	$_FANWE['cookie'] = array();
	$_FANWE['cache'] = array();
	$_FANWE['session'] = array();
	$_FANWE['lang'] = array();
	$_FANWE['tpl_user_formats'] = array();

	$site_path = substr($_FANWE['php_self'], 0, strrpos($_FANWE['php_self'], '/'));
	$_FANWE['site_url'] = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$site_path.'/');

	$url = parse_url($_FANWE['site_url']);
	$_FANWE['site_root'] = isset($url['path']) ? $url['path'] : '';
	$_FANWE['site_port'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' ? '' : ':'.$_SERVER['SERVER_PORT'];

	if(defined('SUB_DIR'))
	{
		$_FANWE['site_url'] = str_replace(SUB_DIR, '', $_FANWE['site_url']);
		$_FANWE['site_root'] = str_replace(SUB_DIR, '', $_FANWE['site_root']);
	}
	
	define('PUBLIC_ROOT', FANWE_ROOT.'./public/');
	define('PUBLIC_PATH', $_FANWE['site_root'].'public/');
	
	define('SITE_URL', $_FANWE['site_root']);

	require fimport("class/cache");
	$server->cache = call_user_func(array('Cache','getInstance'));
	$server->var = &$_FANWE;

	buildFanweConfig($server);
	buildFanweInput($server);
	buildFanweOutput($server);
}

function initializeFanwe(&$server)
{
	if(!$server->is_init)
	{
		buildFanweDb($server);
		buildFanweMemory($server);
		buildFanweSetting($server);
		buildFanweCache($server);
		buildFanweUser($server);
		buildFanweSession($server);
		buildFanweCron($server);
		buildFanweMisc($server);
	}
	$server->is_init = true;

	define('TPL_PATH', $server->var['site_root'].'tpl/'.$server->var['setting']['site_tmpl'].'/');
	define('TMPL', $server->var['setting']['site_tmpl']);
	@include(FANWE_ROOT.'./tpl/'.$server->var['setting']['site_tmpl'].'/functions.php');

	if($server->var['setting']['shop_closed'] == 1 && !$server->is_admin)
	{
		showError(lang('common','site_close'),lang('common','site_close_content'),'',0,true);
	}
}

function buildFanweConfig(&$server)
{
	$config = array();
	@include FANWE_ROOT.'./public/config.global.php';
	
	if(empty($config))
	{
		if(!file_exists(FANWE_ROOT.'./public/install.lock'))
		{
			header('Location: install');
			exit;
		}
		else
		{
			systemError('config_not_found');
		}
	}
	
	if(empty($config['security']['authkey']))
	{
		$config['security']['authkey'] = md5($config['cookie']['cookie_pre'].$config['db'][1]['dbname']);
	}

	if(empty($config['debug']) || !file_exists(fimport('function/debug')))
	{
		define('SYS_DEBUG', false);
	}
	elseif($config['debug'] === 1 || $config['debug'] === 2 || !empty($_REQUEST['debug']) && $_REQUEST['debug'] === $config['debug'])
	{
		define('SYS_DEBUG', true);
		if($config['debug'] == 2)
			error_reporting(E_ALL);
	}
	else
	{
		define('SYS_DEBUG', false);
	}

	timezoneSet($config['time_zone']);
	define('TIME_UTC', fGmtTime());

	$server->config = & $config;
	$server->var['config'] = & $config;

	if(substr($config['cookie']['cookie_path'], 0, 1) != '/')
		$server->var['config']['cookie']['cookie_path'] = '/'.$server->var['config']['cookie']['cookie_path'];

	$server->var['config']['cookie']['cookie_pre'] = $server->var['config']['cookie']['cookie_pre'].substr(md5($server->var['config']['cookie']['cookie_path'].'|'.$server->var['config']['cookie']['cookie_domain']), 0, 4).'_';
}

function buildFanweInput(&$server)
{
	if (isset($_GET['GLOBALS']) || isset($_POST['GLOBALS']) || isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS']))
	{
		systemError('request_tainting');
	}

	if(!MAGIC_QUOTES_GPC && !$server->is_admin)
	{
		$_GET = fAddslashes($_GET);
		$_POST = fAddslashes($_POST);
		$_COOKIE = fAddslashes($_COOKIE);
		$_FILES = fAddslashes($_FILES);
	}

	$pre_length = strlen($server->config['cookie']['cookie_pre']);
	foreach($_COOKIE as $key => $val)
	{
		if(substr($key, 0, $pre_length) == $server->config['cookie']['cookie_pre'])
		{
			$server->var['cookie'][substr($key, $pre_length)] = $val;
		}
	}

	if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
		$_GET = array_merge($_GET, $_POST);

	foreach($_GET as $k => $v)
	{
		$server->var['request'][$k] = $v;
	}
	
	$server->var['isajax'] = empty($server->var['request']['isajax']) ? 0 : 1;
	$server->var['page'] = empty($server->var['request']['page']) ? 1 : max(1, intval($server->var['request']['page']));
	$server->var['sid'] = $server->var['cookie']['sid'] = isset($server->var['cookie']['sid']) ? htmlspecialchars($server->var['cookie']['sid']) : '';
	if(empty($server->var['cookie']['saltkey']))
	{
		$server->var['cookie']['saltkey'] = random(8);
		fSetCookie('saltkey', $server->var['cookie']['saltkey'], 86400 * 30, 1, 1);
	}
	$server->var['authkey'] = md5($server->var['config']['security']['authkey'].$server->var['cookie']['saltkey']);
}

function buildFanweOutput(&$server)
{
	if($server->config['security']['url_xss_defend'] && $_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_SERVER['REQUEST_URI']))
	{
		_xssFanweCheck();
	}
	
	$attack_evasive = true;
	if(!empty($server->var['cookie']['from_header']))
	{
		$from_header_time = (int)authcode($server->var['cookie']['from_header'], 'DECODE');
		$attack_evasive = (TIME_UTC - $from_header_time < 10) ? false : true;
		fSetCookie('from_header','');
	}
	
	/*$module_action = strtolower(MODULE_NAME.'/'.ACTION_NAME);
	if($server->config['security']['attack_evasive'] && $attack_evasive && !in_array($module_action, $server->config['security']['attack_ignore']))
	{
		require_once fimport('include/security');
	}*/

	if(!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false)
	{
		$server->config['output']['gzip'] = false;
	}

	$allow_gzip = $server->config['output']['gzip'] && empty($server->var['isajax']) && EXT_OBGZIP;
	$server->config['gzip_compress'] = $allow_gzip;
	ob_start($allow_gzip ? 'ob_gzhandler' : NULL);
	$server->config['charset'] = $server->config['output']['charset'];
	define('CHARSET', $server->config['output']['charset']);
	if($server->config['output']['forceheader'])
		@header('Content-Type: text/html; charset='.CHARSET);
}

function buildFanweDb(&$server)
{
	require fimport('class/db');
	require fimport('class/mysql');
	$class = 'FDbMySql';
	if(count($server->var['config']['db']['slave']))
	{
		require fimport('class/mysqlslave');
		$class = 'FDbMysqlSlave';
	}

	$server->db = &call_user_func(array('FDB','object'),$class);
	call_user_func(array($server->db,'setConfig'),$server->config["db"]);
	call_user_func(array($server->db,'connect'));
}

function buildFanweMemory(&$server)
{
	require fimport('class/memory');
	$server->memory = new Memory();
	if($server->is_memory)
	{
		call_user_func(array($server->memory,'init'),$server->config["memory"]);
	}
	$server->var['memory'] = $server->memory->type;
}

function buildFanweUser(&$server)
{
	$userServer = FS("User");
	if($server->is_user)
	{
		if($auth = $server->var['cookie']['auth'])
		{
			$auth = fAddslashes(explode("\t", authcode($auth, 'DECODE')));
		}
		
		list($password, $uid) = empty($auth) || count($auth) < 2 ? array('','') : $auth;

		if($uid)
		{
			$user = call_user_func(array($userServer,'getUserById'),$uid);
		}
		
		if(!empty($user) && $user['password'] == $password)
		{
			$server->var['user'] = $user;
			$server->var["authoritys"] = call_user_func(array($userServer,'getAuthoritys'),$uid);
			call_user_func(array($userServer,'init'),$user);
		}
		else
		{
			$server->var['user'] = array( 'uid' => 0, 'user_name' => '', 'email' => '', 'gid' => 6);
		}
	}
	else
	{
		$server->var['user'] = array( 'uid' => 0, 'user_name' => '', 'email' => '', 'gid' => 6);
	}
	if(empty($server->var['cookie']['last_visit']))
	{
		$server->var['user']['last_visit'] = TIME_UTC - 3600;
		fSetCookie('last_visit', TIME_UTC - 3600, 86400 * 30);
	}
	else
	{
		$server->var['user']['last_visit'] = $server->var['cookie']['last_visit'];
	}
	
	$server->var['uid'] = $server->var['user']['uid'];
	$server->var['user_name'] = addslashes($server->var['user']['user_name']);
	$server->var['gid'] = $server->var['user']['gid'];
	$server->var['user_group'] = $server->var['cache']['user_group'][$server->var['gid']];
	call_user_func(array($userServer,'setReferrals'));
	call_user_func(array($userServer,'updateUserLevel'));
}

function buildFanweSession(&$server)
{
	if($server->is_session)
	{
		require fimport('class/session');
		$server->session = new Session();
		call_user_func(array($server->session,'init'),$server->var["cookie"]["sid"],$server->var["client_ip"],$server->var["uid"]);
		$server->var['sid'] = $server->session->sid;
		$server->var['session'] = $server->session->var;

		if($server->var['sid'] != $server->var['cookie']['sid'])
		{
			fSetCookie('sid', $server->var['sid'], 86400);
		}

		if($server->session->is_new)
		{
			if(ipBanned($server->var['client_ip']))
			{
				$server->session->set("gid", 6);
			}
		}
		
		$last_activity = call_user_func(array($server->session,'get'),"last_activity");
		if($server->var['uid'] && ($server->session->isnew || ($last_activity + 600) < TIME_UTC))
		{
			call_user_func(array($server->session,'set'),"last_activity",TIME_UTC);
		}
	}
}

function buildFanweCron(&$server)
{
	if($server->is_cron)
	{
		$cronServer = FS("Cron");
		call_user_func(array($cronServer,'run'));
	}
}

function buildFanweMisc(&$server)
{
	if(!$server->is_misc)
		return false;

	$server->var['form_hash'] = formHash();
	define('FORM_HASH', $server->var['form_hash']);

	if($server->init_user)
	{
		if($server->var['user']['status'] == -1)
		{
			systemError('user_banned',null);
		}
	}

	if($server->var['setting']['ip_access'] && !ipAccess($server->var['client_ip'], $server->var['setting']['ip_access']))
	{
		systemError('user_banned', null);
	}

	if($server->var['setting']['nocacheheaders'])
	{
		@header("Expires: -1");
		@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
		@header("Pragma: no-cache");
	}
}

function buildFanweSetting(&$server)
{
	if($server->is_setting)
	{
		call_user_func(array($server->cache,'loadCache'),"setting");
	}

	if(!is_array($server->var['setting']))
		$server->var['setting'] = array();

	/* 发布分享、回复、评论间隔时间（秒） */
	define('SHARE_INTERVAL_TIME',(int)$server->var['setting']['share_interval_time']);

	/* 分享缓存删除间隔时间（秒） */
	define('SHARE_CACHE_TIME',(int)$server->var['setting']['share_cache_time']);

	/* 图片生成质量 */
	define('IMAGE_CREATE_QUALITY',(int)$server->var['setting']['image_create_quality']);
}

function buildFanweCache(&$server)
{
	!empty($server->cache_list) && call_user_func(array($server->cache,'loadCache'),$server->cache_list);
}

function buildFanweRewriteArgs(&$server)
{
	if(intval($server->var['setting']['url_route']) > 0)
	{
		switch(MODULE_NAME.'/'.ACTION_NAME)
		{
			case 'index/index':
			case 'index/search':
			case 'index/today':
			case 'index/custom':
				getRewriteArgs(array('cat','city_py','sort','prices','keyword','page'));
			break;

			case 'goods/index':
			case 'goods/search':
				getRewriteArgs(array('site','cat','date','city_py','sort','prices','keyword','page'));
			break;
		}
	}
}

function _xssFanweCheck()
{
	$temp = strtoupper(urldecode(urldecode($_SERVER['REQUEST_URI'])));
	if(strpos($temp, '<') !== false || strpos($temp, '"') !== false || strpos($temp, 'CONTENT-TRANSFER-ENCODING') !== false)
	{
		systemError('request_tainting');
	}
	return true;
}

function _fanweChecker()
{	
	$domain_array = array(
		base64_encode(base64_encode('127.0.0.1')),
		base64_encode(base64_encode('localhost')),
		base64_encode(base64_encode('demo.yaojingmao.com'))
	);
	$str = base64_encode(base64_encode(serialize($domain_array))."|".serialize($domain_array));

	$arr = explode("|",base64_decode($str));		
	$arr = unserialize($arr[1]);
	foreach($arr as $k=>$v)
	{
		$arr[$k] = base64_decode(base64_decode($v));
	}	
	$host = $_SERVER['HTTP_HOST'];
	$host = explode(":",$host);
	$host = $host[0];
	$passed = false;
	foreach($arr as $k=>$v)
	{
		if(substr($v,0,2)=='*.')
		{
			$preg_str = substr($v,2);
			if(preg_match("/".$preg_str."$/",$host)>0)
			{
				$passed = true;
				break;
			}
		}
	}
	if(!$passed)
	{
		if(!in_array($host,$arr))
		{
			return false;
		}
	}
	return true;
}
?>