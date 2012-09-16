<?php
class goodslistMapi
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

		$total = FDB::resultFirst('SELECT COUNT(DISTINCT goods_id) FROM '.FDB::table('share_goods').' WHERE uid = '.$uid);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$goods_list = array();
		$goods_ids = array();
		$img_ids = array();

		$res = FDB::query('SELECT sg.id,sg.goods_id,sg.img_id,sg.share_id  
			FROM '.FDB::table('share_goods').' AS sg  
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = sg.share_id 
			WHERE sg.uid = '.$uid.' GROUP BY goods_id ORDER BY sg.goods_id DESC LIMIT '.$limit);
		while($goods = FDB::fetch($res))
		{
			$goods['share_url'] = FU('note/g',array('sid'=>$goods['share_id'],'id'=>$goods['id']),true);
			$goods_list[$goods['goods_id']] = $goods;
			$goods_ids[$goods['goods_id']][] = &$goods_list[$goods['goods_id']];
			$img_ids[$goods['img_id']][] = &$goods_list[$goods['goods_id']];
		}

		FS('Image')->formatByIdKeys($img_ids);
		FS('Goods')->formatByIDKeys($goods_ids,false);
		
		$list = array();
		foreach($goods_list as $goods_item)
		{
			$list[] = array(
				'goods_id'=>$goods_item['id'],
				'share_id'=>$goods_item['share_id'],
				'name'=>$goods_item['name'],
				'url'=>$goods_item['share_url'],
				'img'=>getImgName($goods_item['img'],$img_size,$img_size,1,true),
				'height' => round($img_size / $scale)
			);
		}

		$root['return'] = 1;
		$root['item'] = $list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>