<?php
class syncloginMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;
		
		$login_type = trim($_FANWE['requestData']['login_type']);//类型
		$access_token = trim($_FANWE['requestData']['access_token']);
		$access_secret = trim($_FANWE['requestData']['access_secret']);

		if($_FANWE['requestData']['type'] == 'android')
		{
			$_FANWE['requestData']['user_info'] = json_decode($_FANWE['requestData']['user_info'],true);
		}
		
		Cache::getInstance()->loadCache('logins');
		$login_type1 = '';
		switch($login_type)
		{
			case 'USTqq':
				include fimport('class/tqq','user');
				$user_server = new TqqUser();
				$login_type1 = 'tqq';
				if($_FANWE['requestData']['type'] != 'android')
					$user = $_FANWE['requestData']['user_info'];
				else
					$user = $_FANWE['requestData']['user_info']['data'];

				$user['token'] = array();
				$user['token']['t_access_token'] = $access_token;
				$user['token']['t_openid'] = $_FANWE['requestData']['openid'];
				$user['token']['t_openkey'] = $_FANWE['requestData']['openkey'];
				if($_FANWE['requestData']['type'] != 'android')
				{
					$key_id = trim($user['name1']);//会员标识
					$user['token']['t_expire_in'] = (int)$_FANWE['requestData']['refresh_time'] - TIME_UTC;
					$user['head'] = $user['avatar'];
					$user['name'] = $user['name1'];
				}
				else
				{
					$key_id = trim($user['name']);//会员标识
					$user['token']['t_expire_in'] = (int)$_FANWE['requestData']['expires_in'];;
				}
			break;
			
			case 'USSina':
				include fimport('class/sina','user');
				$key_id = trim($_FANWE['requestData']['key_id']);//会员标识
				$user_server = new SinaUser();
				$login_type1 = 'sina';
				$user = $_FANWE['requestData']['user_info'];
				if($_FANWE['requestData']['type'] != 'android')
				{
					$user['id'] = $key_id;
					$user['profile_image_url'] = $user['avatar'];
				}
				$user['token']['expires_in'] = (int)$_FANWE['requestData']['refresh_time'] - TIME_UTC;
				$user['token']['access_token'] = $access_token;
			break;
			
			default:
				$root['info'] =	'接口不存在';
				m_display($root);
			break;
		}
		
		$bind_user = $user_server->getUserByTypeKeyId($login_type1,$key_id);
		
		if($bind_user)
		{
			$err = '';
			if($bind_user['status'] == 0)
			{
				$root['return'] = 0;
				$root['info'] = "帐号已禁用";
				$root['uid'] = 0;
			}
			else
			{
				$uid = $bind_user['uid'];
				$_FANWE['uid'] = $uid;
				$user_server->updateBindInfo($user);

				$user = FS('User')->getUserById($uid);
				$root['uid'] = $uid;
				$root['email'] = $user['user_name'];
				$root['name'] = $user['user_name'];
				$root['pwd'] = $user['password'];
				$root['user_avatar'] = avatar($user['avatar'],'b',true);
				$root['return'] = 1;

				$deviceuid = addslashes(trim($_FANWE['requestData']['deviceuid']));
				$sql = "update ".FDB::table('apns_devices')." set clientid = ".$uid." where clientid = 0 and deviceuid = '".$deviceuid."'";
				FDB::query($sql);
			}
		}
		else
		{
			switch($login_type)
			{
				case 'USTqq':
					if($_FANWE['requestData']['type'] == 'android')
					{
						$root = $_FANWE['requestData'];
						$root['return'] = -1;
						$root['uid'] = 0;
						$root['sex'] = $user['sex'] == 1 ? '男':'女';
						$root['user_name'] = getSyncUserName($user['nick']);
						$root['email'] = $user['email'];
					}
					else
					{
						$root['return'] = -1;
						$root['uid'] = 0;
						$root['sex'] = $user['sex'] == '1' ? '男':'女';
						$root['user_name'] = getSyncUserName($user['name']);
						$root['email'] = $user['email'];
					}
				break;
				
				case 'USSina':
					if($_FANWE['requestData']['type'] == 'android')
					{
						$root = $_FANWE['requestData'];
						$root['return'] = -1;
						$root['uid'] = 0;
						$root['sex'] = $user['sex'] == 1 ? '男':'女';
						$root['user_name'] = getSyncUserName($user['nick']);
						$root['email'] = '';
					}
					else
					{
						$root['return'] = -1;
						$root['uid'] = 0;
						$root['sex'] = $user['sex'] == 'm' ? '男':'女';
						$root['user_name'] = getSyncUserName($user['name']);
						$root['email'] = '';
					}
				break;
			}
			
			if(!empty($root['email']) && FS('User')->getEmailExists($root['email']))
				$root['email'] = '';
		}
		$root['act'] = 'synclogin';
		m_display($root);
	}
}

function getSyncUserName($user_name)
{
	$old_name = $user_name;
	do
	{
		$max_count = FDB::resultFirst('SELECT COUNT(*) FROM '.FDB::table("user")." WHERE user_name = '".$user_name."'");
		if($max_count > 0)
			$user_name = $old_name.'_'.random(3);
	}
	while($max_count > 0);
	return $user_name;
}
?>