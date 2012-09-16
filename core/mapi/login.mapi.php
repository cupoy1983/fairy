<?php
class loginMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;

		$root['info'] = $_FANWE['requestData'];
		
		//print_r($_FANWE['requestData']);
		$user_name_or_email = addslashes($_FANWE['requestData']['email']);
		$password = addslashes(trim($_FANWE['requestData']['pwd']));
		if($password <> '' && strlen($password) != 32)
			$password = md5($password);

		if ($user_name_or_email == ''){
			$root['info'] = '登陆帐户不能为空';
			m_display($root);
		}	

		

		$user_field = $_FANWE['setting']['integrate_field_id'];
		$sql = "SELECT uid,status,{$user_field},avatar FROM ".FDB::table('user')." WHERE (email = '$user_name_or_email' OR user_name = '$user_name_or_email') AND password = '$password'";	
		//echo $sql;
		$user_info = FDB::fetchFirst($sql);		
		//print_r($user_info);exit;

		$uid = intval($user_info['uid']);
		$integrate_id = intval($user_info[$user_field]);
		
		//===========add by chenfq 2011-10-14==========================
		if ($uid <= 0){
			$uid = FS("Integrate")->addUserToLoacl($user_name_or_email,$password, 1);
			
			//重新取一下当前数据库的用户数据
			$sql = "SELECT uid,{$user_field},status,avatar FROM ".FDB::table('user')." WHERE uid = '$uid'";
			$user_info = FDB::fetchFirst($sql);
			$uid = intval($user_info['uid']);
			$integrate_id = intval($user_info[$user_field]);
		}
		//===========add by chenfq 2011-10-14==========================
		//echo $uid; exit;
		if ($uid > 0)
		{
			if($user_info['status']==0)
				m_display($root);
			
			$root['uid'] = $uid;
			$root['user_avatar'] = avatar($user_info['avatar'],'b',true);
			$root['home_user'] = FS("User")->getUserById($uid);
			$root['return'] = 1;

			$deviceuid = addslashes(trim($_FANWE['requestData']['deviceuid']));
			$sql = "update ".FDB::table('apns_devices')." set clientid = ".$uid." where clientid = 0 and deviceuid = '".$deviceuid."'";
			FDB::query($sql);
		}
		else
		{
			$root['info'] = '帐户不存在或密码错误';
		}
		$root['act'] = 'login';
		m_display($root);
	}
}
?>