<?php
class ShopModule
{
	public function index()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/shop/shop_index',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$where = ' WHERE recommend_count > 0';
			$page_args = array();
			$cid = (int)$_FANWE['request']['cid'];
			if($cid > 0 && isset($_FANWE['cache']['shops']['all'][$cid]))
			{
				$cate = $_FANWE['cache']['shops']['all'][$cid];
				$_FANWE['nav_title'] = $cate['name'].$_FANWE['nav_title'];
				if(isset($cate['childs']))
				{
					$cate['childs'][] = $cid;
					$where .= ' AND cate_id IN ('.implode(',',$cate['childs']).')';
				}
				else
					$where .= ' AND cate_id = '.$cid;
				$page_args['cid'] = $cid;
			}
			else
				$cid = 0;
	
			$sql = 'SELECT COUNT(shop_id) FROM '.FDB::table('shop').$where;
			$goods_count = FDB::resultFirst($sql);
			
			$page_size = 20;
			$pager = buildPage('shop/index',$page_args,$goods_count,$_FANWE['page'],$page_size);
			
			$goods_ids = array();
			$sql = 'SELECT * FROM '.FDB::table('shop').$where.' ORDER BY sort ASC,recommend_count DESC,shop_id DESC LIMIT '.$pager['limit'];
			$shop_list = array();
	
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$cache_data = fStripslashes(unserialize($data['data']));
				$data['tags'] = array();
				if($cache_data)
				{
					if($cache_data['tags'] && is_array($cache_data['tags']))
						$data['tags'] = array_slice($cache_data['tags'],0,5);
					
					if($cache_data['goods'] && is_array($cache_data['goods']))
						$data['goods'] = array_slice($cache_data['goods'],0,3);
				}
				unset($data['data']);
				$data['url'] = FU('shop/show',array('id'=>$data['shop_id']));
				$shop_list[$data['shop_id']] = $data;
			}
			
			include template('page/shop/shop_index');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}
	
	public function show()
	{
		global $_FANWE;
		$id = (int)$_FANWE['request']['id'];
		if(!$id)
			exit;
		
		$shop = FDB::fetchFirst('SELECT * FROM '.FDB::table('shop').' WHERE shop_id = '.$id);
		if(!$shop)
			fHeader("location: ".FU('shop/index'));
		
		$_FANWE['nav_title'] = $shop['shop_name'].' - '.$_FANWE['nav_title'];
		
		$cache_data = fStripslashes(unserialize($shop['data']));
		$shop['tags'] = $cache_data['tags'];
		
		if(empty($shop['taoke_url']))
			$shop['to_url'] = FU('tgo',array('url'=>$shop['shop_url']));
		else
			$shop['to_url'] = FU('tgo',array('url'=>$shop['taoke_url']));
		
		$page_args['id'] = $id;
		$sql = 'SELECT COUNT(share_id) FROM '.FDB::table('shop_share').' WHERE shop_id = '.$id;
		$share_count = FDB::resultFirst($sql);
		
		$page_size = 20;
		$pager = buildPage('shop/show',$page_args,$share_count,$_FANWE['page'],$page_size);
		
		$share_list = array();
		if($share_count > 0)
		{
			$sql = 'SELECT s.share_id,s.uid,s.content,s.collect_count,s.comment_count,s.create_time,s.cache_data 
				FROM '.FDB::table('shop_share').' AS ss 
				INNER JOIN '.FDB::table('share').' AS s ON s.share_id = ss.share_id 
				WHERE ss.shop_id = '.$id.' ORDER BY ss.share_id DESC LIMIT '.$pager['limit'];
			$share_list = FDB::fetchAll($sql);
			$share_list = FS('Share')->getShareDetailList($share_list,false,true,true,false,0,10);
		}
		
		$shops_tags = FS('Shop')->getUserOtherShopAndTags($id);
		include template('page/shop/shop_show');
		display();
	}
}
?>