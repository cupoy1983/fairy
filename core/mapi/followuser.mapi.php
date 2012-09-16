<?php
class followuserMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		//未登陆直接退出
		if($_FANWE['uid'] == 0)
		{
			$root['status'] = -1;
			m_display($root);
		}

		$uid = (int)$_FANWE['requestData']['uid'];
		//没有关注的会员编号直接退出
		if($uid == 0)
		{
			$root['status'] = -2;
			m_display($root);
		}
		
		//没有会员直接退出
		if(!FS('User')->getUserExists($uid))
		{
			$root['status'] = -3;
			m_display($root);
		}

		if(FS('User')->followUser($uid))
			$root['is_follow'] = 1;
		else
			$root['is_follow'] = 0;
		
		$root['status'] = 1;
		m_display($root);
	}
}
?>