<?php
class registerMapi
{
	public function run()
	{
		global $_FANWE;
		$root = array();
		$root['return'] = 0;

		$data = array(
			'email'            => $_FANWE['requestData']['email'],
			'user_name'        => $_FANWE['requestData']['user_name'],
			'password'         => $_FANWE['requestData']['password'],
			'gender'           => intval($_FANWE['requestData']['gender']),
		);
		
		$vservice = FS('Validate');
		$validate = array(
			array('email','required',lang('user','register_email_require')),
			array('email','email',lang('user','register_email_error')),
			array('user_name','required',lang('user','register_user_name_require')),
			array('user_name','range_length',lang('user','register_user_name_len'),2,20),
			array('user_name','/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u',lang('user','register_user_name_error')),
			array('password','range_length',lang('user','register_password_range'),6,20),
		);

		if(!$vservice->validation($validate,$data))
		{
			$root['info'] = "注册失败:".$vservice->getError();
			m_display($root);
		}

		$uservice = FS('User');
		if($uservice->getEmailExists($data['email']))
		{
			$root['info'] = "注册失败:".lang('user','register_email_exist');
			m_display($root);
		}

		if($uservice->getUserNameExists($data['user_name']))
		{
			$root['info'] = "注册失败:".lang('user','register_user_name_exist');
			m_display($root);
		}

		//================add by chenfq 2011-10-14 =======================
		$user_field = $_FANWE['setting']['integrate_field_id'];
		$integrate_id = FS("Integrate")->addUser($data['user_name'],$data['password'],$data['email']);
		if ($integrate_id < 0){
			$info = FS("Integrate")->getInfo();
			$root['info'] = "注册失败:".$info;
			m_display($root);
		};
		//================add by chenfq 2011-10-14=======================		
				
		$user = array(
			'email' => $data['email'],
			'user_name' => $data['user_name'],
			'password'  => $data['password'],
			'invite_id' => FS('User')->getReferrals(),
			$user_field => $integrate_id,
		);
		
		
		$uid = FS('User')->createUser($user);
		if($uid > 0)
		{
			$_FANWE['uid'] = $uid;
			$root['return'] = 1;
			$root['info'] = "用户注册成功";		
			$root['uid'] = $uid;
			$root['user_name'] = $data['user_name'];
			$root['user_avatar'] = avatar(0,'m',true);
			$root['user_email'] = $data['user_name'];

			$deviceuid = addslashes(trim($_FANWE['requestData']['deviceuid']));
			$sql = "update ".FDB::table('apns_devices')." set clientid = ".$uid." where clientid = 0 and deviceuid = '".$deviceuid."'";
			FDB::query($sql);

			$login_type = trim($_FANWE['requestData']['login_type']);//类型
			if(!empty($login_type))
			{
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
						$key_id = trim($_FANWE['requestData']['key_id']);//会员标识
						include fimport('class/sina','user');
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
						$root['return'] = 2;
						$root['info'] =	'接口不存在';
						m_display($root);
					break;
				}

				//$root['info'] =	$user;
				//m_display($root);

				FDB::delete('user_bind',"type = '".$login_type1."' AND keyid = '".$key_id."'");
				$user_server->bindUser($user);
				$root['user_avatar'] = avatar(FS('User')->getAvatar($uid),'m',true);
			}
		}
		else
		{
			$root['info']	= lang('user','register_error');
		}
		
		m_display($root);
	}
}
?>