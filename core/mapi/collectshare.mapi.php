<?php
class collectshareMapi
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

		$share_id = (int)$_FANWE['requestData']['share_id'];
		//没有分享ID直接退出
		if($share_id == 0)
		{
			$root['status'] = -2;
			m_display($root);
		}
		
		$share = FS('Share')->getShareById($share_id);
		//没有分享直接退出
		if(empty($share))
		{
			$root['status'] = -3;
			m_display($root);
		}

		//不能喜欢自己发布的分享
		if($share['uid'] == $_FANWE['uid'])
		{
			$root['status'] = -4;
			m_display($root);
		}
		
		if(FS('Share')->getIsCollectByUid($share_id,$_FANWE['uid']))
		{
			$root['is_collect'] = 0;
			FS('Share')->deleteShareCollectUser($share_id,$_FANWE['uid']);
		}
		else
		{
			$root['is_collect'] = 1;
			FS('Share')->saveFav($share);
		}
		
		$root['status'] = 1;
		m_display($root);
	}
}
?>