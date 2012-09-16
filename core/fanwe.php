<?php
error_reporting(E_ERROR);
if(!defined('FANWE_ROOT'))
	define('FANWE_ROOT', str_replace('core/fanwe.php', '', str_replace('\\', '/', __FILE__)));

if(!include_once(FANWE_ROOT.'./core/function/global.func.php'))
	exit('not found global.func.php');

include_once fimport("function/time");
require FANWE_ROOT.'core/service/fanwe.service.php';

class FanweService
{
	public $db = NULL;
	public $cache = NULL;
	public $session = NULL;
	public $memory = NULL;
	public $is_init = false;
	public $is_memory = true;
	public $is_session = true;
	public $is_admin = false;
	public $is_user = true;
	public $is_cron = true;
	public $is_setting = true;
	public $is_misc = true;
	public $config = array();
	public $var = array();
	public $cache_list = array('user_group','goods_category','image_servers','links','navs');

	public $allow_global = array(
		'GLOBALS' => 1,
		'_GET' => 1,
		'_POST' => 1,
		'_REQUEST' => 1,
		'_COOKIE' => 1,
		'_SERVER' => 1,
		'_ENV' => 1,
		'_FILES' => 1,
		'_FANWE' => 1,
	);

	public function &instance()
	{
		static $_instance = NULL;
		if($_instance === NULL)
			$_instance = new FanweService();
		return $_instance;
	}

	public function FanweService()
	{
		initFanwe($this);
	}

	public function initialize()
	{
		initializeFanwe($this);
	}
}

?>