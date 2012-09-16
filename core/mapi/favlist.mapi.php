<?php
class favlistMapi
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

		if($uid == $_FANWE['uid'])
			FDB::query("DELETE FROM ".FDB::table('user_notice')." WHERE uid='$uid' AND type=2");

		if(!isset($root['home_user']))
		{
			$root['home_user'] = FS("User")->getUserById($uid);
			unset($root['home_user']['user_name_match'],$root['home_user']['password'],$root['home_user']['active_hash'],$root['home_user']['reset_hash']);
			$root['home_user']['user_avatar'] = avatar($root['home_user']['avatar'],'m',true);
		}

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);
		$is_spare_flow = (int)$_FANWE['requestData']['is_spare_flow'];

		$img_width = 200;
		$max_height = 400;
		$scale = 2;
		if($is_spare_flow == 1)
		{
			$img_width = 100;
			$scale = 1;
		}

		$sql = 'SELECT COUNT(DISTINCT share_id) FROM '.FDB::table("user_collect").' 
			WHERE c_uid = '.$uid;

		$total = FDB::resultFirst($sql);
		$page_size = 20;//PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;

		$sql = 'SELECT DISTINCT(s.share_id),s.cache_data,s.collect_count,s.comment_count,s.content,u.user_name,u.avatar  
				FROM '.FDB::table("user_collect").' AS uc 
				INNER JOIN '.FDB::table("share").' as s on s.share_id=uc.share_id 
				INNER JOIN '.FDB::table("user").' as u on u.uid=uc.uid  
				WHERE uc.c_uid = '.$uid.' 
				ORDER BY s.share_id DESC LIMIT '.$limit;
		
		$res = FDB::query($sql);
		$share_list = array();
		while($data = FDB::fetch($res))
		{
			$cache_data = fStripslashes(unserialize($data['cache_data']));
			unset($data['cache_data']);
			$img = current($cache_data['imgs']['all']);
			$data['user_avatar'] = avatar($data['avatar'],'m',true);
			$data['big_img'] = getImgName($img['img'],468,468,1,true);
			$data['img'] = getImgName($img['img'],$img_width,$max_height,2,true);
			$data['height'] = $img['img_height'] * ($img_width / $img['img_width']);
			$data['height'] = $data['height'] > $max_height ? $max_height : $data['height'];
			$data['height'] = round($data['height'] / $scale);
			m_express($data,$data['content']);
			$share_list[] = $data;
		}
		
		$root['return'] = 1;
		$root['item'] = $share_list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>