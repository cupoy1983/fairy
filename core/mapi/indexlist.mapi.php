<?php
class indexlistMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		$id = (int)$_FANWE['requestData']['id'];
		if($id == 0)
			exit;

		$type = $_FANWE['requestData']['type'];
		switch($type)
		{
			case "index":
				FanweService::instance()->cache->loadCache('mindex');
				if(!isset($_FANWE['cache']['mindex'][$id]))
					exit;
				$rec_obj = $_FANWE['cache']['mindex'][$id];
			break;

			case "adv":
				FanweService::instance()->cache->loadCache('madv');
				if(!isset($_FANWE['cache']['madv']['all'][$id]))
					exit;
				$rec_obj = $_FANWE['cache']['madv']['all'][$id];
			break;

			default:
				exit;
			break;
		}
		
		$root['id'] = $id;
		$root['type'] = $type;

		$tags = array();
		foreach($rec_obj['data']['tags'] as $item){
			$tags[] = array('name'=>$item);
		}
		//$root['tags'] = $rec_obj['data']['tags'];
		$root['tags'] = $tags;

		$cid = $rec_obj['data']['cid'];
		$root['tag'] = $tag = trim($_FANWE['requestData']['tag']);
		$is_hot = (int)$_FANWE['requestData']['is_hot'];
		$is_new = (int)$_FANWE['requestData']['is_new'];
		$is_spare_flow = (int)$_FANWE['requestData']['is_spare_flow'];
		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$img_width = 200;
		$max_height = 400;
		$scale = 2;
		if($is_spare_flow == 1)
		{
			$img_width = 100;
			$scale = 1;
		}

		$today_time = getTodayTime();
		$field = '';
		$whrer = '';
		$joins = '';
		
		$book_photo_goods = (int)$_FANWE['setting']['book_photo_goods'];
		if($book_photo_goods == 0)
			$whrer = " WHERE s.share_data IN ('goods','photo','goods_photo')";
		elseif($book_photo_goods == 1)
			$whrer = " WHERE s.share_data IN ('photo','goods_photo')";
		elseif($book_photo_goods == 2)
			$whrer = " WHERE s.share_data IN ('goods','goods_photo')";

		if($cid > 0 && isset($_FANWE['cache']['goods_category']['all'][$cid]))
		{
			if($_FANWE['cache']['goods_category']['all'][$cid]['is_root'] == 0)
			{
				$is_cate = true;
				$cids = array();
				FS('Share')->getChildCids($cid,$cids);
				$whrer .= " AND sc.cate_id IN (".implode(",",$cids).")";
				$joins .= ' INNER JOIN '.FDB::table('share_category').' AS sc ON s.share_id = sc.share_id ';
			}
			else
				$cid = 0;
		}
		else
			$cid = 0;

		if(!empty($tag))
		{
			$is_match = true;
			$match_key = segmentToUnicode($tag,'+');
			$whrer .= " AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";
			$joins .= ' INNER JOIN '.FDB::table('share_match').' AS sm ON sm.share_id = s.share_id ';
		}

		if($is_hot == 1)
		{
			$day7_time = $today_time - 604800;
			$field = ",(s.create_time > $day7_time) AS time_sort ";
			$sort = " ORDER BY time_sort DESC,s.collect_count DESC";
		}

		if($is_new == 1)
			$sort = " ORDER BY s.share_id DESC";

		$args = md5($is_hot.'/'.$is_new.'/'.$cid.'/t'.$tag.'/'.$is_spare_flow.'/'.$page);
		$key = 'm/sharelist/'.substr($args,0,2).'/'.substr($args,2,2).'/'.$args;
		$cache_list = getCache($key);
		if($cache_list === NULL || (TIME_UTC - $cache_list['cache_time']) > 600)
		{
			$sql_count = "SELECT COUNT(DISTINCT s.share_id) FROM ".FDB::table("share").' AS s'.$joins.$whrer;
			$total = FDB::resultFirst($sql_count);
			$page_size = 20;//PAGE_SIZE;
			$max_page = 100;
			if($total > $max_page * $page_size)
				$total = $max_page * $page_size;

			if($page > $max_page)
				$page = $max_page;
			
			$page_total = max(ceil($total/$page_size),1);
			if($page > $page_total)
				$page = $page_total;

			$limit = (($page - 1) * $page_size).",".$page_size;
			$sql = 'SELECT DISTINCT(s.share_id),s.cache_data '.$field.'
						FROM '.FDB::table('share').' AS s'.$joins.$whrer.$sort.' LIMIT '.$limit;

			$res = FDB::query($sql);
			$share_list = array();
			while($item = FDB::fetch($res))
			{
				$cache_data = fStripslashes(unserialize($item['cache_data']));
				$img = current($cache_data['imgs']['all']);
				$data = array();
				$data['share_id'] = $item['share_id'];
				$data['img'] = getImgName($img['img'],$img_width,$max_height,2,true);
				$data['height'] = $img['img_height'] * ($img_width / $img['img_width']);
				$data['height'] = $data['height'] > $max_height ? $max_height : $data['height'];
				$data['height'] = round($data['height'] / $scale);
				$share_list[] = $data;
			}

			$cache_list = array();
			$cache_list['page_total'] = $page_total;
			$cache_list['share_list'] = $share_list;
			$cache_list['cache_time'] = TIME_UTC;
			setCache($key,$cache_list);
		}
		else
		{
			$page_total = $cache_list['page_total'];
			$share_list = $cache_list['share_list'];
		}

		$root['item'] = $share_list;
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>