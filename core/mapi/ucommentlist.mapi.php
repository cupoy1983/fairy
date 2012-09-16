<?php
class ucommentlistMapi
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

		FDB::query("DELETE FROM ".FDB::table('user_notice')." WHERE uid='$uid' AND type=3");

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$sql = 'SELECT COUNT(sc.comment_id)
			FROM '.FDB::table("share").' AS s
			INNER JOIN '.FDB::table("share_comment").' AS sc ON sc.share_id = s.share_id
			WHERE  s.uid = '.$uid;

		$total = FDB::resultFirst($sql);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$comment_list = array();
		$sql = 'SELECT sc.*,s.content as scontent,u.user_name,u.avatar 
			FROM '.FDB::table("share").' AS s
			INNER JOIN '.FDB::table("share_comment").' AS sc ON sc.share_id = s.share_id 
			INNER JOIN '.FDB::table("user").' AS u ON u.uid = sc.uid 
			WHERE s.uid = '.$uid.'
			ORDER BY comment_id DESC LIMIT '.$limit;
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$data['user_avatar'] = avatar($data['avatar'],'m',true);
			$data['time'] = getBeforeTimelag($data['create_time']);
			$data['url'] = FU('note/index',array('sid'=>$data['share_id']),true);
			m_express($data,$data['content'].$data['scontent']);
			$comment_list[] = $data;
		}

		$root['return'] = 1;
		$root['item'] = $comment_list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>