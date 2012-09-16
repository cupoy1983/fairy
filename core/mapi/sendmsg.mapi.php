<?php
class sendmsgMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;

		if($_FANWE['uid'] == 0)
		{
			$root['info'] = "请先登陆";
			m_display($root);
		}

		$user_name = trim($_FANWE['requestData']['user_name']);
		if(empty($user_name))
		{
			$root['info'] = "请选择要发送信件的粉丝";
			m_display($root);
		}

		$message = trim($_FANWE['requestData']['message']);
		if(empty($message))
		{
			$root['info'] = "请输入要发送信息";
			m_display($root);
		}
		//echo $user_name; exit;
		$user = FS('User')->getUsersByName($user_name);
		if(empty($user))
		{
			$root['info'] = "请选择要发送信件的粉丝";
			m_display($root);
		}

		if(!FS('User')->getIsFollowUId2($user['uid'],$_FANWE['uid']))
		{
			$root['info'] = "只能给粉丝发送信件";
			m_display($root);
		}

		$message = cutStr($message,200);
		if(FS('Message')->sendMsg($_FANWE['uid'],$_FANWE['user_name'],array($user['uid']), '', $message) > 0)
		{
			$root['return'] = 1;
			$root['info'] = "发送信件成功";
		}
		else
			$root['info'] = "发送信件失败";
		
		m_display($root);
	}
}
?>