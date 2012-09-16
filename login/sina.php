<?php
// 新浪的api登录接口
require_once FANWE_ROOT."sdks/sina/saetv2.ex.class.php";
class sina
{
	private $config;
	public function __construct()
	{
		global $_FANWE;
		$this->config = $_FANWE['cache']['logins']['sina'];
	}

	public function getInfo()
	{
		global $_FANWE;
		$data['name'] = $this->config['name'];
		$data['short_name'] = $this->config['short_name'];
		$data['is_syn'] = $this->config['is_syn'];
		$data['bind_img'] = SITE_URL.'login/sina/bind_sina.png';
		$data['icon_img'] = SITE_URL.'login/sina/icon_sina.png';
		$data['login_img'] = SITE_URL.'login/sina/login_sina.png';
		$data['login_url'] = SITE_URL."login.php?mod=sina";
		$data['bind_url'] = SITE_URL."login.php?bind=sina";
		$data['unbind_url'] = SITE_URL."login.php?unbind=sina";
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
		
		$oauth = new SaeTOAuthV2($this->config['app_key'],$this->config['app_secret']);
		$url = $oauth->getAuthorizeURL($_FANWE['site_url']."callback/sina.php");
		$url = FU('tgo',array('url'=>$url));
		fHeader("location:".$url);
	}

	public function unBind()
	{
		global $_FANWE;
		if($_FANWE['uid'] > 0)
		{
			FDB::delete('user_bind',"uid = ".$_FANWE['uid']." AND type = 'sina'");
		}
		fHeader("location: ".FU('settings/bind'));
	}
	
	//同步发表到新浪微博
	public function sendMessage($data)
	{
		global $_FANWE;
		$uid = $_FANWE['uid'];

		static $sina = NULL;
		if($sina === NULL)
		{
			require_once FANWE_ROOT."core/class/user/sina.class.php";
			$sina = new SinaUser();
		}
		$sina->sentShare($uid,$data);
	}
}
?>