<?php
// 腾讯微博的api登录接口
require_once FANWE_ROOT."sdks/tqq/Tencent.php";
class tqq
{
	private $config;
	public function __construct()
	{
		global $_FANWE;
		$this->config = $_FANWE['cache']['logins']['tqq'];
	}

	public function getInfo()
	{
		global $_FANWE;
		$data['name'] = $this->config['name'];
		$data['short_name'] = $this->config['short_name'];
		$data['is_syn'] = $this->config['is_syn'];
		$data['bind_img'] = SITE_URL.'login/tqq/bind_tqq.png';
		$data['icon_img'] = SITE_URL.'login/tqq/icon_tqq.png';
		$data['login_img'] = SITE_URL.'login/tqq/login_tqq.png';
		$data['login_url'] = SITE_URL."login.php?mod=tqq";
		$data['bind_url'] = SITE_URL."login.php?bind=tqq";
		$data['unbind_url'] = SITE_URL."login.php?unbind=tqq";
		return $data;
	}

	public function loginJump()
	{
		global $_FANWE;
		if($_FANWE['uid'] > 0)
		{
			$this->bindJump();
			exit;
		}
		
		fSetCookie('callback_type','login');
		$this->jump();
	}

	public function bindJump()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
		{
			$this->loginJump();
			exit;
		}
		
		fSetCookie('callback_type','bind');
		$this->jump();
	}
	
	private function jump()
	{
		global $_FANWE;
		TqqOAuth::init($this->config['app_key'],$this->config['app_secret']);
		$url = TqqOAuth::getAuthorizeURL($_FANWE['site_url']."callback/tqq.php");
		$url = FU('tgo',array('url'=>$url));
		fHeader("location:".$url);
	}

	public function unBind()
	{
		global $_FANWE;
		if($_FANWE['uid'] > 0)
		{
			FDB::delete('user_bind',"uid = ".$_FANWE['uid']." AND type = 'tqq'");
		}
		fHeader("location: ".FU('settings/bind'));
	}
	
	public function sendMessage($data)
	{
		global $_FANWE;
		$uid = $_FANWE['uid'];

		static $tqq = NULL;
		if($tqq === NULL)
		{
			require_once FANWE_ROOT."core/class/user/tqq.class.php";
			$tqq = new TqqUser();
		}
		$tqq->sentShare($uid,$data);
	}
}
?>