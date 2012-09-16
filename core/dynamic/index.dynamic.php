<?php
//首页动态内容的函数


function getIndexUserInfo(){
	return tplFetch('inc/index/user_info');
}

/**
 * 热门杂志列表
 */
function getIndexShops(){
	global $_FANWE;
	$args = array();
	$cache_file = getTplCache('inc/index/shop', array(), 1);
	if(getCacheIsUpdate($cache_file, SHARE_CACHE_TIME, 1)){
		$args['shop_list'] = FDB::fetchAll('SELECT * FROM ' . FDB::table('shop') . ' WHERE shop_logo > 0 ORDER BY sort ASC, shop_id asc limit 0,30');
	}
	return tplFetch('inc/index/shop', $args, '', $cache_file);
}

/**
 * 首页分类推荐分享
 */
function getIndexCateShare(){
	$args = array();
	$cache_file = getTplCache('inc/index/index_cate_share', array(), 1);
	//FIXME getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1)
	if(true){
		global $_FANWE;
		FanweService::instance()->cache->loadCache('goods_category');
		$index_cids = $_FANWE['cache']['goods_category']['index'];
		$cate_list = array();
		if(count($index_cids) > 0){
			foreach($index_cids as $cid){
				$cate_list[$cid] = false;
			}
			
			$user_ids = array();
			$imgs_ids = array();
			$sql = 'SELECT ics.*,s.collect_count FROM ' . FDB::table('index_cate_share') . ' AS ics 
				LEFT JOIN ' . FDB::table('share') . ' AS s ON s.share_id = ics.share_id 
				WHERE cid IN (' . implode(',', $index_cids) . ') ORDER BY sort ASC,share_id DESC';
			$res = FDB::query($sql);
			while($data = FDB::fetch($res)){
				if(empty($data['url']))
					$data['url'] = FU('note/index', array(
							'sid' => $data['share_id'] 
					));
				
				$data['collect_count'] = (int)$data['collect_count'];
				$cate_list[$data['cid']]['share_list'][$data['share_id']] = $data;
				if($data['cimg_id'] > 0)
					$imgs_ids[$data['cimg_id']][] = &$cate_list[$data['cid']]['share_list'][$data['share_id']];
				else
					$imgs_ids[$data['img_id']][] = &$cate_list[$data['cid']]['share_list'][$data['share_id']];
				$cate_list[$data['cid']]['users'][$data['uid']] = $data['uid'];
				$cate_list[$data['cid']]['today_count'] = 0;
			}
			FS('Image')->formatByIdKeys($imgs_ids);
			
			foreach($index_cids as $cid){
				if($cate_list[$cid] === false)
					unset($cate_list[$cid]);
			}
			
			if(count($cate_list) > 0){
				$today = getTodayTime();
				$cids = array();
				foreach($cate_list as $cid => $cate){
					$cids[] = $cid;
					if(isset($_FANWE['cache']['goods_category']['all'][$cid]['childs']))
						$cids = array_merge($cids, $_FANWE['cache']['goods_category']['all'][$cid]['childs']);
				}
				$cids = array_unique($cids);
				
				$sql = 'SELECT COUNT(DISTINCT id) AS gcount,cid FROM ' . FDB::table('goods') . ' 
					WHERE create_day = ' . $today . ' AND cid IN (' . implode(',', $cids) . ') GROUP BY cid';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res)){
					if(isset($cate_list[$data['cid']]))
						$cate_list[$data['cid']]['today_count'] += $data['gcount'];
					else{
						if(isset($_FANWE['cache']['goods_category']['all'][$data['cid']]['parents'])){
							foreach($_FANWE['cache']['goods_category']['all'][$data['cid']]['parents'] as $cid){
								if(isset($cate_list[$cid]))
									$cate_list[$cid]['today_count'] += $data['gcount'];
							}
						}
					}
				}
			}
			
			foreach($cate_list as $cid => $cate){
				$cate_list[$cid]['cate'] = $_FANWE['cache']['goods_category']['all'][$cid];
			}
			$args['cate_list'] = $cate_list;
		}
	}
	return tplFetch('inc/index/index_cate_share', $args, '', $cache_file);
}
/**
 * 首页广告栏
 */
function getIndexAdv(){
	
	$args = array();
	$cache_file = getTplCache('module/home_adv', array(), 1);
	//FIXME getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1)
	
	if(true){
		global $_FANWE;
		
		// 1.获取最新的产品分享
		$new_list = array();
		$sql = "SELECT s.*, min(s.share_id) FROM " . FDB::table("share") . " as s
			WHERE s.share_data='goods' AND s.status=1 GROUP BY uid ORDER BY s.day_time desc limit 15";
		$new_list = FDB::fetchAll($sql);
		$new_list = FS("Share")->getShareDetailList($new_list);
		
		$args['new_list'] = $new_list;
	}
	
	return tplFetch('module/home_adv', $args, '', $cache_file);
}
?>