<?php
class photolistMapi
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

		if(!isset($root['home_user']))
		{
			$root['home_user'] = FS("User")->getUserById($uid);
			unset($root['home_user']['user_name_match'],$root['home_user']['password'],$root['home_user']['active_hash'],$root['home_user']['reset_hash']);
			$root['home_user']['user_avatar'] = avatar($root['home_user']['avatar'],'m',true);
		}

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);
		$is_spare_flow = (int)$_FANWE['requestData']['is_spare_flow'];
		$img_size = 200;
		$scale = 2;
		if($is_spare_flow == 1)
		{
			$img_size = 100;
			$scale = 1;
		}

		$total = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('share_photo').' WHERE uid = '.$uid);
		$page_size = 20;//PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$photo_list = array();
		$img_ids = array();
		$res = FDB::query('SELECT id,share_id,img_id 
			FROM '.FDB::table('share_photo').' 
			WHERE uid = '.$uid.' ORDER BY id DESC LIMIT '.$limit);
		while($photo = FDB::fetch($res))
		{
			$photo['url'] = FU('note/m',array('sid'=>$photo['share_id'],'id'=>$photo['id']),true);
			$photo_list[$photo['id']] = $photo;
			$img_ids[$photo['img_id']][] = &$photo_list[$photo['id']];
		}
		FS('Image')->formatByIdKeys($img_ids);

		$list = array();
		foreach($photo_list as $photo_item)
		{
			$list[] = array(
				'photo_id'=>$photo_item['id'],
				'share_id'=>$photo_item['share_id'],
				'url'=>$photo_item['url'],
				'img'=>getImgName($photo_item['img'],$img_size,$img_size,1,true),
				'height'=>round($img_size / $scale),
			);
		}

		$root['return'] = 1;
		$root['item'] = $list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>