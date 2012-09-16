<?php
class uMapi
{
	public function run()
	{
		global $_FANWE;			
		$root = array();
		$root['return'] = 0;
		
		$uid = (int)$_FANWE['requestData']['uid'];
		if($uid > 0)
		{
			if(!FS('User')->getUserExists($uid))
				$uid = 0;
		}

		if($uid == 0)
		{
			$uid = $_FANWE['uid'];
			$root['home_user'] = $_FANWE['user'];
		}

		if($uid == 0)
		{
			$root['info'] = "请选择要查看的会员";
			m_display($root);
		}


		$act2 = $_FANWE['requestData']['act_2'];
		if ($act2 == 'follow' && $_FANWE['uid'] > 0 && $uid <> $_FANWE['uid']){
			FS('User')->followUser($uid);
			//$root['home_user'] = FS("User")->getUserById($uid);
		}

		if(!isset($root['home_user']))
		{
			$root['home_user'] = FS("User")->getUserById($uid);
			unset($root['home_user']['user_name_match'],$root['home_user']['password'],$root['home_user']['active_hash'],$root['home_user']['reset_hash']);
			$root['home_user']['user_avatar'] = avatar($root['home_user']['avatar'],'m',true);
			if($uid == $_FANWE['uid'])
				$root['home_user']['is_follow'] = -1;
			else
			{
				$root['home_user']['is_follow'] = 0;
				if(FS("User")->getIsFollowUId($uid,false))
					$root['home_user']['is_follow'] = 1;
			}
		}

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$sql = 'SELECT COUNT(share_id) FROM '.FDB::table("share").' WHERE uid = '.$uid;
		$total = FDB::resultFirst($sql);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$share_list = array();
		$sql = 'SELECT *
			FROM '.FDB::table("share").' 
			WHERE uid = '.$uid.' ORDER BY share_id DESC LIMIT '.$limit;
		$share_list = FDB::fetchAll($sql);
		$share_list = mGetShareDetailList($share_list,true);

		$root['return'] = 1;
		if(count($share_list) > 0)
			$root['item'] = array_slice($share_list,0);
		else
			$root['item'] = array();
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>