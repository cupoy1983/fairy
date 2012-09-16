<?php
class BookModule
{
	public function cate()
	{
		global $_FANWE;
		$category = urldecode($_FANWE['request']['cate']);
		if(!isset($_FANWE['cache']['goods_category']['cate_code'][$category]))
		{
			$category = (int)$category;
			if($category == 0 || !isset($_FANWE['cache']['goods_category']['all'][$category]))
				fHeader('location: '.FU('book/shopping'));
		}

		BookModule::getList();
	}

	public function shopping()
	{
		BookModule::getList();
	}
	
	private function getList()
	{
		global $_FANWE;
		$_FANWE['user_click_share_id'] = (int)$_FANWE['request']['sid'];
		unset($_FANWE['request']['sid']);
		$cache_file = getTplCache('page/book/book_index',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$category = urldecode($_FANWE['request']['cate']);
			$is_root = false;
			$page_args = array();
			$cate_root_id = $_FANWE['cache']['goods_category']['root'];
			if(isset($_FANWE['cache']['goods_category']['cate_code'][$category]))
			{
				$page_args['cate'] = $_FANWE['request']['cate'];
				$cate_id = $_FANWE['cache']['goods_category']['cate_code'][$category];
			}
			else
			{
				$category = (int)$category;
				if($category > 0 && isset($_FANWE['cache']['goods_category']['all'][$category]))
				{
					$page_args['cate'] = $category;
					$cate_id = $category;
				}
				else
				{
					$is_root = true;
					$cate_id = $cate_root_id;
				}
			}
	
			$sort = $_FANWE['request']['sort'];
			$sort = !empty($sort) ? $sort : "hot1";
			
			$cate_root = $_FANWE['cache']['goods_category']['all'][$cate_root_id];
			$category_data = $current_cate = $_FANWE['cache']['goods_category']['all'][$cate_id];
			if(!$is_root && !isset($category_data['child']))
				$category_data = $_FANWE['cache']['goods_category']['all'][$category_data['parent_id']];
			
			$is_parent_cate = true;
			if(isset($category_data['parents']))
			{
				$is_parent_cate = false;
				$cate_nav_list = array();
				foreach($category_data['parents'] as $cid)
				{
					$cate_nav_list[] = $_FANWE['cache']['goods_category']['all'][$cid];
				}
				$cate_nav_list[] = &$category_data;
			}
			else
				$cate_nav_list = &$category_data;
			
			$book_cates = array();
			$book_advs = getAdvPosition('book_cate',0,'cate'.$category_data['cate_id']);
			
			if(!is_array($book_advs) || !isset($book_advs['adv_list']))
				$book_advs = array();
			else
				$book_advs = $book_advs['adv_list'];
				
			$cate_index = 0;
			$book_adv_num = 0;
			$cate_index = 1;
			if($is_root)
			{
				foreach($_FANWE['cache']['goods_category']['parent'] as $cid)
				{
					$book_cate = $_FANWE['cache']['goods_category']['all'][$cid];
					$tag_key = 'goods_category_tags_'.$cid;
					FanweService::instance()->cache->loadCache($tag_key);
					$book_cate['tags'] = array_slice($_FANWE['cache'][$tag_key],0,20);
					if($cate_index % 3 == 0)
					{
						if(isset($book_advs[$book_adv_num]))
						{
							$book_cates[$cate_index] = array(
								'type'=>'adv',
								'data'=>$book_advs[$book_adv_num]
							);
							$book_adv_num++;
							$cate_index++;
						}
					}
					
					$book_cates[$cate_index] = array(
						'type'=>'cate',
						'data'=>$book_cate
					);
					$cate_index++;
				}
			}
			else
			{
				foreach($category_data['child'] as $cid)
				{
					$book_cate = $_FANWE['cache']['goods_category']['all'][$cid];
					$tag_key = 'goods_category_tags_'.$cid;
					FanweService::instance()->cache->loadCache($tag_key);
					$book_cate['tags'] = array_slice($_FANWE['cache'][$tag_key],0,20);
					if($cate_index % 3 == 0)
					{
						if(isset($book_advs[$book_adv_num]))
						{
							$book_cates[$cate_index] = array(
								'type'=>'adv',
								'data'=>$book_advs[$book_adv_num]
							);
							$book_adv_num++;
							$cate_index++;
						}
					}
					
					$book_cates[$cate_index] = array(
						'type'=>'cate',
						'data'=>$book_cate
					);
					$cate_index++;
				}
			}
			
			if(count($book_cates) % 3 > 0)
			{
				$$book_cates_count = 3 - (count($book_cates) % 3);
				for($i = 0;$i < $$book_cates_count;$i++)
				{
					if(isset($book_advs[$book_adv_num]))
					{
						$book_cates[$cate_index] = array(
							'type'=>'adv',
							'data'=>$book_advs[$book_adv_num]
						);
						$book_adv_num++;
					}
					else
					{
						$book_cates[$cate_index] = array(
							'type'=>'empty',
						);
					}
					$cate_index++;
				}
			}
	
			$_FANWE['nav_title'] = $current_cate['cate_name'];
			
			if(!empty($current_cate['seo_keywords']))
			{
				$_FANWE['seo_keywords'] = $current_cate['seo_keywords'];
				$_FANWE['setting']['site_keywords'] = '';
			}
			
			if(!empty($current_cate['seo_desc']))
			{
				$_FANWE['seo_description'] = $current_cate['seo_desc'];
				$_FANWE['setting']['site_description'] = '';
			}

			$condition = '';
			
			$title = $current_cate['short_name'];
			$is_match = false;
			$tag = urldecode($_FANWE['request']['tag']);
			$gid = (int)$_FANWE['request']['gid'];
			$is_group = false;
			if(!$is_root && $gid > 0 && array_search($gid,$current_cate['groups']) !== FALSE)
			{
				FanweService::instance()->cache->loadCache('index_cate_group');
				$cate_group = $_FANWE['cache']['index_cate_group'][$gid];
				$group_tags = array();
				foreach($cate_group['tags'] as $gtag)
				{
					if(!empty($gtag))
					{
						$group_tags[] = "'".addslashes($gtag)."'";
					}
				}
				$group_tags = implode(',',$group_tags);
				if(!empty($group_tags))
				{
					$is_group = true;
					$_FANWE['nav_title'] = $cate_group['name'] .' - '. $_FANWE['nav_title'];
					$title = htmlspecialchars($cate_group['name']);
					$is_tag = true;
					$condition.=" AND st.tag_name IN ($group_tags)";
					$page_args['gid'] = $gid;
				}
			}
			
			if(!$is_group && !empty($tag))
			{
				$_FANWE['nav_title'] = $tag .' - '. $_FANWE['nav_title'];
				$title = htmlspecialchars($tag);
				$is_tag = true;
				$condition.=" AND st.tag_name = '".addslashes($tag)."'";
				$page_args['tag'] = urlencode($tag);
			}
	
			//输出排序URL
			$sort_page_args = $page_args;
			$sort_page_args['sort'] = 'hot1';
	
			$hot1_url['url'] = FU('book/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot1')
				$hot1_url['act'] = 1;
	
			$sort_page_args['sort'] = 'hot7';
			$hot7_url['url'] = FU('book/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot7')
				$hot7_url['act'] = 1;
	
			$sort_page_args['sort'] = 'new';
			$new_url['url'] = FU('book/'.ACTION_NAME,$sort_page_args);
			if($sort=='new')
				$new_url['act'] = 1;
	
			if(!empty($_FANWE['request']['sort']))
				$page_args['sort'] = $sort;
			else
				$page_args['sort'] = 'hot1';
			
			$field = '';
			$today_time = getTodayTime();
			switch($sort)
			{
				//24小时最热 24小时喜欢人数
				case 'hot1':
					$sort = " ORDER BY sgi.collect_1count DESC,sgi.share_id DESC";
				break;
				//1周天最热 1周喜欢人数
				case 'hot7':
					$sort = " ORDER BY sgi.collect_7count DESC,sgi.share_id DESC";
				break;
				//最新
				case 'new':
					$sort = " ORDER BY sgi.share_id DESC";
				break;
				
				default:
					$sort = '';
				break;
			}

			$sql = 'SELECT DISTINCT(sgi.share_id) FROM '.FDB::table('share_goods_index').' AS sgi ';
			$sql_count = 'SELECT COUNT(DISTINCT sgi.share_id) FROM '.FDB::table('share_goods_index').' AS sgi ';
			$sql_type = '';
			if($is_tag)
			{
				$sql = 'SELECT DISTINCT(sgi.share_id) FROM '.FDB::table('share_tags').' AS st ';
				$sql_count = 'SELECT COUNT(DISTINCT sgi.share_id) FROM '.FDB::table('share_tags').' AS st ';
				$sql_type = 'st';
			}
			
			if(!$is_root)
			{
				//$cids = array();
				//FS('Share')->getChildCids($cate_id,$cids);
				
				$sql_type = 'sc';
				if($is_tag)
				{
					$append_sql = 'INNER JOIN '.FDB::table('share_category').' AS sc 
						ON sc.share_id = st.share_id AND sc.cate_id = '.$cate_id.' ';
					$sql .= $append_sql;
					$sql_count .= $append_sql;
				}
				else
				{
					$sql = 'SELECT DISTINCT(sgi.share_id) FROM '.FDB::table('share_category').' AS sc ';
					$sql_count = 'SELECT COUNT(DISTINCT sgi.share_id) FROM '.FDB::table('share_category').' AS sc ';
					$condition .= " AND sc.cate_id = ".$cate_id.' ';
				}
			}

			if($sql_type != '')
			{
				$append_sql = 'INNER JOIN '.FDB::table('share_goods_index').' AS sgi 
					ON sgi.share_id = '.$sql_type.'.share_id ';
			}
			if(!empty($condition))
				$condition = str_replace('WHERE AND','WHERE ','WHERE'.$condition);

			$sql .= $append_sql.$condition.$sort;
			$sql_count .= $append_sql.$condition;
	
			$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
			$count = FDB::resultFirst($sql_count);
	
			$action = ACTION_NAME;
			if($action == 'search')
				$action = 'shopping';
	
			$pager = buildPage('book/'.$action,$page_args,$count,$_FANWE['page'],$page_size,'',3);
			$page_args['page'] = $_FANWE['page'];
			$page_args['pindex'] = '_pindex_';
			$pb_url = $_FANWE['site_root'].'services/service.php?m=share&a=book&'.http_build_query($page_args);
			$pb_list = array();
			if($count > $_FANWE['setting']['share_pb_item_count'])
			{
				for($i = 2;$i <= $_FANWE['setting']['share_pb_load_count'];$i++)
				{
					$pb_list[] = str_replace('_pindex_',$i,$pb_url);
				}
			}
			
			$sql  = $sql.' LIMIT '.($_FANWE['page'] - 1) * $pager['page_size'] . "," . $_FANWE['setting']['share_pb_item_count'];
			$share_list = array();
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$share_list[$data['share_id']] = false;
			}

			if(count($share_list) > 0)
			{
				$share_ids = array_keys($share_list);
				$sql = 'SELECT share_id,uid,content,collect_count,comment_count,create_time,cache_data 
					FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',$share_ids).')';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$share_list[$data['share_id']] = $data;
				}
				$share_list = FS('Share')->getShareDetailList($share_list,false,false,false,true,2);
			}
			
			include template('page/book/book_index');
			display($cache_file); 
			exit;
		}
		else
		{
			include $cache_file;
			display();
		}
	}
}
?>