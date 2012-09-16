<?php
class sharecateMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;
		
		$key = 'm/sharecate';
		$cache_list = getCache($key);
		if($cache_list === NULL || (TIME_UTC - $cache_list['cache_time']) > 600)
		{
			$min_time = $this->getQuarterMinTime();
			$max_time = getTodayTime();
			FanweService::instance()->cache->loadCache('goods_category');
			$index_cids = $_FANWE['cache']['goods_category']['index'];
			$cate_list = array();
			if(count($index_cids) > 0)
			{
				foreach($index_cids as $cid)
				{
					$cate_list[$cid] = false;
				}

				$imgs_ids = array();
				$sql = 'SELECT ics.*,s.collect_count FROM '.FDB::table('index_cate_share').' AS ics 
					LEFT JOIN '.FDB::table('share').' AS s ON s.share_id = ics.share_id 
					WHERE cid IN ('.implode(',',$index_cids).') ORDER BY sort ASC,share_id DESC';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					if(empty($data['url']))
						$data['url'] = FU('note/index',array('sid'=>$data['share_id']),1);
					
					$data['collect_count'] = (int)$data['collect_count'];
					$cate_list[$data['cid']]['share_list'][$data['share_id']] = $data;
					if($data['cimg_id'] > 0)
						$imgs_ids[$data['cimg_id']][] = &$cate_list[$data['cid']]['share_list'][$data['share_id']];
					else
						$imgs_ids[$data['img_id']][] = &$cate_list[$data['cid']]['share_list'][$data['share_id']];
				}
				FS('Image')->formatByIdKeys($imgs_ids);

				foreach($index_cids as $cid)
				{
					if($cate_list[$cid] === false)
						unset($cate_list[$cid]);
				}
				
				$list = array();
				foreach($cate_list as $cid => $cate)
				{
					$cate_item = $_FANWE['cache']['goods_category']['all'][$cid];
					$cate_item['cate_icon'] = FS("Image")->getImageUrl($cate_item['cate_icon'],2);
					$cate_item['txt_tags'] = array();
					$index = 0;
					foreach($cate_item['index_tags'] as $tag)
					{
						if($index >= 9)
							break;
						
						$index++;
						unset($tag['tag_id'],$tag['is_index']);
						$cate_item['txt_tags'][] = $tag;
					}
					unset($cate_item['parent_id'],$cate_item['seo_keywords'],$cate_item['seo_desc'],$cate_item['sort'],$cate_item['status'],$cate_item['is_index'],$cate_item['is_root'],$cate_item['child'],$cate_item['index_tags'],$cate_item['childs'],$cate_item['parents']);
					
					$cate_item['img_tags'] = array();
					$index = 0;
					$img_size = 320;
					foreach($cate['share_list'] as $share)
					{
						if($index >= 5)
							break;
						$index++;
						$data = array();
						$data['share_id'] = $share['share_id'];
						$data['tag_name'] = $share['name'];
						$data['url_tag'] = urlencode($data['tag_name']);
						$data['tag_name'] = $share['name'];
						$data['img'] = getImgName($share['img'],$img_size,$img_size,1,true);
						$cate_item['img_tags'][] = $data;
						$img_size = 160;
					}
					$list[] = $cate_item;
					$cate_item['share_count'] = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('goods')." 
						WHERE cid = ".$cid." AND create_day >= $min_time AND create_day <= $max_time");
				}
				$cate_list = $list;
			}

			$cache_list = array();
			$cache_list['cate_list'] = $cate_list;
			$cache_list['cache_time'] = TIME_UTC;
			setCache($key,$cache_list);
		}
		else
		{
			$cate_list = $cache_list['cate_list'];
		}

		$root['item'] = $cate_list;
		
		m_display($root);
	}

	public function getQuarterMinTime()
	{
		$now_year = fToDate(TIME_UTC,'Y');
		$now_month = fToDate(TIME_UTC,'n');
		$quarter = ceil($now_month / 3);
		$min_month = ($quarter - 1) * 3 + 1;
		return str2Time($now_year.'-'.$min_month.'-1 00:00:00');
	}
}
?>