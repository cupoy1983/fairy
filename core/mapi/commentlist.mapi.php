<?php
class commentlistMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		$share_id = (int)$_FANWE['requestData']['share_id'];
		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);
		
		$sql_count = "SELECT COUNT(DISTINCT comment_id) FROM ".FDB::table("share_comment")." WHERE share_id = ".$share_id;
		$total = FDB::resultFirst($sql_count);
		$page_size = PAGE_SIZE;
		
		$page_total = ceil($total/$page_size);
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		$sql = 'SELECT c.*,u.user_name,u.avatar FROM '.FDB::table('share_comment').' AS c 
			INNER JOIN '.FDB::table('user').' AS u ON u.uid = c.uid 
			WHERE c.share_id = '.$share_id.' ORDER BY c.comment_id DESC LIMIT '.$limit;
		$res = FDB::query($sql);
		$list = array();
		while($item = FDB::fetch($res))
		{
			$item['user_avatar'] = avatar($item['avatar'],'m',true);
			$item['time'] = getBeforeTimelag($item['create_time']);
			m_express($item,$item['content']);
			$list[] = $item;
		}

		$root['item'] = $list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>