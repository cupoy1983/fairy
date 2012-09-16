<?php
class ufollowMapi
{
	public function run()
	{
		
		global $_FANWE;	
		//print_r($_FANWE['requestData']);exit;
		$root = array();
		$root['return'] = 0;
		
		$uid = $_FANWE['uid'];
		if($uid == 0)
		{
			$root['info'] = "请先登陆";
			m_display($root);
		}

		$root['home_user'] = $_FANWE['user'];
		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$uids = array();
		//获取我关注的会员编号
		$sql = 'SELECT uid
			FROM '.FDB::table('user_follow').'
			WHERE f_uid = '.$uid;
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$uids[] = (int)$data['uid'];
		}
	
		if(count($uids) > 0)
		{
			$sql = 'SELECT COUNT(share_id) FROM '.FDB::table("share").' WHERE uid IN ('.implode(',',$uids).')';
			$total = FDB::resultFirst($sql);
			$page_size = PAGE_SIZE;
			$page_total = max(1,ceil($total/$page_size));
			if($page > $page_total)
				$page = $page_total;
			$limit = (($page - 1) * $page_size).",".$page_size;
			
			$share_list = array();
			$sql = 'SELECT * FROM '.FDB::table("share").' WHERE uid IN ('.implode(',',$uids).') ORDER BY share_id DESC LIMIT '.$limit;
			$share_list = FDB::fetchAll($sql);
			$share_list = mGetShareDetailList($share_list,true);
		}
		else
		{
			$page_total = 0;
			$share_list = array();
		}

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