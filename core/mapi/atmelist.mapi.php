<?php
class atmelistMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;
		
		$uid = $_FANWE['uid'];
		if($uid == 0)
		{
			$root['info'] = "请先登陆";
			m_display($root);
		}

		$root['home_user'] = $_FANWE['user'];

		FDB::query("DELETE FROM ".FDB::table('user_notice')." WHERE uid='$uid' AND type=4");

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);
		
		$sql = 'SELECT COUNT(a.share_id) 
			FROM '.FDB::table("atme").' AS a
			INNER JOIN '.FDB::table("share").' as s on s.share_id = a.share_id and s.uid <> '.$uid.' 
			WHERE a.uid = '.$uid;

		$total = FDB::resultFirst($sql);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$share_list = array();
		$sql = 'SELECT s.* 
			FROM '.FDB::table("atme").' AS a
			INNER JOIN '.FDB::table("share").' as s on s.share_id = a.share_id and s.uid <> '.$uid.' 
			WHERE a.uid = '.$uid.' ORDER BY s.share_id DESC LIMIT '.$limit;

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