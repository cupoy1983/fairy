<?php
class SearchModule
{
	public function share()
	{
		global $_FANWE;
		$cache_args = $_FANWE['request'];
		$cache_args['action'] = ACTION_NAME;
		$cache_file = getTplCache('page/search/search_share',$cache_args,2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$_FANWE['nav_title'] = lang('common','search_shares') . $_FANWE['nav_title'];
			$keyword = cutStr(trim(urldecode($_FANWE['request']['keyword'])),200,'');
			
			$share_list = array();
			$is_empty = false;
			if(empty($keyword) || $keyword == lang('template','search_share'))
				$is_empty = true;
			else
			{
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				if(empty($match_key))
					$is_empty = true;
			}
			
			$_FANWE['nav_title'] = $keyword.' - '. $_FANWE['nav_title'];
			$sort = $_FANWE['request']['sort'];
			$sort = !empty($sort) ? $sort : "hot1";
			$page_args['keyword'] = urlencode($keyword);
			
			//输出排序URL
			$sort_page_args = $page_args;
			$sort_page_args['sort'] = 'hot1';
			
			$hot1_url['url'] = FU('search/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot1')
				$hot1_url['act'] = 1;
	
			$sort_page_args['sort'] = 'hot7';
			$hot7_url['url'] = FU('search/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot7')
				$hot7_url['act'] = 1;
	
			$sort_page_args['sort'] = 'new';
			$new_url['url'] = FU('search/'.ACTION_NAME,$sort_page_args);
			if($sort=='new')
				$new_url['act'] = 1;
				
			$search_navs = array();
			SearchModule::formatSearchNavs($search_navs,$page_args['keyword']);
	
			if(!$is_empty)
			{
				$page_args['sort'] = $sort;
				
				switch($sort)
				{
					//24小时最热 24小时喜欢人数
					case 'hot1':
						$sort = " ORDER BY si.collect_1count DESC,si.share_id DESC";
					break;
					//1周天最热 1周喜欢人数
					case 'hot7':
						$sort = " ORDER BY si.collect_7count DESC,si.share_id DESC";
					break;
					//最新
					case 'new':
						$sort = " ORDER BY si.share_id DESC";
					break;
					
					default:
						$sort = '';
					break;
				}
				
				$match_table = 'share_goods_match';
				$share_table = 'share_goods_index';
				switch(ACTION_NAME)
				{
					case 'photo':
						$match_table = 'share_photo_match';
						$share_table = 'share_photo_index';
					break;
					case 'all':
						$match_table = 'share_match';
						$share_table = 'share_images_index';
					break;
				}
				
				$where = " WHERE match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE)";
				$sql_count = 'SELECT COUNT(sm.share_id) FROM '.FDB::table($match_table).' AS sm ';
				$append_sql = 'INNER JOIN '.FDB::table($share_table).' AS si ON si.share_id = sm.share_id ';
				
				$count = (int)FDB::resultFirst($sql_count.$where);
				if($count > 0)
				{
					$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
					$pager = buildPage('search/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],$page_size,'',3);
					$page_args['page'] = $_FANWE['page'];
					$page_args['act'] = ACTION_NAME;
					$page_args['pindex'] = '_pindex_';
					$pb_url = $_FANWE['site_root'].'services/service.php?m=share&a=search&'.http_build_query($page_args);
					$pb_list = array();
					if($count > $_FANWE['setting']['share_pb_item_count'])
					{
						for($i = 2;$i <= $_FANWE['setting']['share_pb_load_count'];$i++)
						{
							$pb_list[] = str_replace('_pindex_',$i,$pb_url);
						}
					}
					$sql = 'SELECT si.share_id FROM '.FDB::table($match_table).' AS sm '.$append_sql.$where.$sort;
					$sql .= ' LIMIT '.($_FANWE['page'] - 1) * $pager['page_size'] . "," . $_FANWE['setting']['share_pb_item_count'];
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
				}
			}
			
			$keyword = htmlspecialchars($keyword);
			include template('page/search/search_share');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}

	public function album()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/search/search_album',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$_FANWE['nav_title'] = lang('common','search_album') . $_FANWE['nav_title'];
			$keyword = trim(urldecode($_FANWE['request']['keyword']));
			$album_list = array();
			$is_empty = false;
			if(empty($keyword) || $keyword == lang('template','search_share'))
				$is_empty = true;
			else
			{
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				if(empty($match_key))
					$is_empty = true;
			}
			
			$_FANWE['nav_title'] = $keyword.' - '. $_FANWE['nav_title'];
			$sort = $_FANWE['request']['sort'];
			$sort = !empty($sort) ? $sort : "hot";
			$page_args['keyword'] = urlencode($keyword);
			
			//输出排序URL
			$sort_page_args = $page_args;
			$sort_page_args['sort'] = 'hot';
			
			$hot_url['url'] = FU('search/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot')
				$hot_url['act'] = 1;
	
			$sort_page_args['sort'] = 'new';
			$new_url['url'] = FU('search/'.ACTION_NAME,$sort_page_args);
			if($sort=='new')
				$new_url['act'] = 1;
				
			$search_navs = array();
			SearchModule::formatSearchNavs($search_navs,$page_args['keyword']);
	
			if(!$is_empty)
			{
				$page_args['sort'] = $sort;
				
				switch($sort)
				{
					//最热
					case 'hot':
						$sort = " ORDER BY a.collect_count DESC,a.id DESC";
					break;
					//最新
					case 'new':
						$sort = " ORDER BY a.id DESC";
					break;
					
					default:
						$sort = '';
					break;
				}
				
				$where = " WHERE match(am.content) against('".$match_key."' IN BOOLEAN MODE)";
				$sql_count = 'SELECT COUNT(am.id) FROM '.FDB::table('album_match').' AS am ';
				$append_sql = 'INNER JOIN '.FDB::table('album').' AS a ON a.id = am.id ';
				
				$count = (int)FDB::resultFirst($sql_count.$where);
				if($count > 0)
				{
					$pager = buildPage('search/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],30);
					$page_args['page'] = $_FANWE['page'];
					
					$sql = 'SELECT a.id,a.title,a.tags,a.img_count,a.cache_data,a.uid FROM '.FDB::table('album_match').' AS am '.$append_sql.$where.$sort.' LIMIT '.$pager['limit'];
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$data['imgs'] = array();
						if(!empty($data['cache_data']))
						{
							$cache_data = fStripslashes(unserialize($data['cache_data']));
							$data['imgs'] = $cache_data['imgs'];
							unset($data['cache_data']);
						}
						$data['url'] = FU('album/show',array('id'=>$data['id']));
						$tags = explode(' ',$data['tags']);
						$data['tags'] = array();
						foreach($tags as $tag)
						{
							$data['tags'][] = array(
								'name'=>$tag,
								'url'=>FU('search/'.ACTION_NAME,array('keyword'=>urlencode($tag)))
							);
						}
						$album_list[$data['id']] = $data;
					}
				}
			}
			
			$keyword = htmlspecialchars($keyword);
			include template('page/search/search_album');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}

	public function user()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/search/search_user',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$_FANWE['nav_title'] = lang('common','search_user') . $_FANWE['nav_title'];
			$keyword = trim(urldecode($_FANWE['request']['keyword']));
			$user_list = array();
			$is_empty = false;
			if(empty($keyword) || $keyword == lang('template','search_share'))
				$is_empty = true;
			else
			{
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				if(empty($match_key))
					$is_empty = true;
			}
			
			$_FANWE['nav_title'] = $keyword.' - '. $_FANWE['nav_title'];
			$page_args['keyword'] = urlencode($keyword);
	
				
			$search_navs = array();
			SearchModule::formatSearchNavs($search_navs,$page_args['keyword']);
	
			if(!$is_empty)
			{
				$where = " WHERE match(user_name) against('".$match_key."' IN BOOLEAN MODE)";
				$sql_count = 'SELECT COUNT(uid) FROM '.FDB::table('user_match');
				
				$count = (int)FDB::resultFirst($sql_count.$where);
				if($count > 0)
				{
					$pager = buildPage('search/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],20);
					$page_args['page'] = $_FANWE['page'];
					
					$sql = 'SELECT uid FROM '.FDB::table('user_match').$where.' ORDER BY uid DESC LIMIT '.$pager['limit'];
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$user_list[$data['uid']] = array();
					}
					
					if(count($user_list) > 0)
					{
						$share_ids = array();
						$res = FDB::query("SELECT u.uid,u.user_name,u.avatar,u.reg_time,uc.fans,up.introduce,us.last_share 
							FROM ".FDB::table('user').' AS u 
							INNER JOIN '.FDB::table('user_count').' uc ON uc.uid = u.uid 
							INNER JOIN '.FDB::table('user_profile').' up ON up.uid = u.uid 
							INNER JOIN '.FDB::table('user_status').' us ON us.uid = u.uid 
							WHERE u.uid IN ('.implode(',',array_keys($user_list)).')');
						while($data = FDB::fetch($res))
						{
							$user_list[$data['uid']] = $data;
							if($data['last_share'] > 0)
								$share_ids[$data['last_share']] = &$user_list[$data['uid']];
						}
						
						if(count($share_ids) > 0)
						{
							$sql = 'SELECT share_id,content,create_time FROM '.FDB::table('share').' 
								WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
							$res = FDB::query($sql);
							while($data = FDB::fetch($res))
							{
								$share_ids[$data['share_id']]['share'] = $data['content'];
								$share_ids[$data['share_id']]['share_time'] = getBeforeTimelag($data['create_time']);
							}
						}
					}
				}
			}
			
			$keyword = htmlspecialchars($keyword);
			include template('page/search/search_user');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}
	
	public function group()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/search/search_group',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$_FANWE['nav_title'] = lang('common','search_group') . $_FANWE['nav_title'];
			$keyword = trim(urldecode($_FANWE['request']['keyword']));
			$user_list = array();
			$is_empty = false;
			if(empty($keyword) || $keyword == lang('template','search_share'))
				$is_empty = true;
			else
			{
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				if(empty($match_key))
					$is_empty = true;
			}
			
			$_FANWE['nav_title'] = $keyword.' - '. $_FANWE['nav_title'];
			$page_args['keyword'] = urlencode($keyword);
	
				
			$search_navs = array();
			SearchModule::formatSearchNavs($search_navs,$page_args['keyword']);
	
			if(!$is_empty)
			{
				$where = " WHERE match(fm.content) against('".$match_key."' IN BOOLEAN MODE)";
				$sql_count = 'SELECT COUNT(fm.fid) FROM '.FDB::table('forum_match').' AS fm ';
				
				$count = (int)FDB::resultFirst($sql_count.$where);
				$group_list = array();
				if($count > 0)
				{
					$pager = buildPage('search/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],50);
					$page_args['page'] = $_FANWE['page'];
					$img_ids = array();
					$sql = 'SELECT f.fid,f.share_id,f.name,f.uid,f.thread_count,f.user_count,f.icon,f.create_time 
						FROM '.FDB::table('forum_match').' AS fm 
						INNER JOIN '.FDB::table('forum').' AS f ON f.fid = fm.fid '.$where.' ORDER BY fm.fid DESC LIMIT '.$pager['limit'];
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$group_list[$data['fid']] = $data;
						$group_list[$data['fid']]['icon'] = array();
						$img_ids[$data['icon']][] = &$group_list[$data['fid']]['icon'];
					}

					FS('Image')->formatByIdKeys($img_ids);
				}
			}
			
			$keyword = htmlspecialchars($keyword);
			include template('page/search/search_group');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}

	public function topic()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/search/search_topic',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$_FANWE['nav_title'] = lang('common','search_topic') . $_FANWE['nav_title'];
			$keyword = trim(urldecode($_FANWE['request']['keyword']));
			$user_list = array();
			$is_empty = false;
			if(empty($keyword) || $keyword == lang('template','search_share'))
				$is_empty = true;
			else
			{
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				if(empty($match_key))
					$is_empty = true;
			}
			
			$_FANWE['nav_title'] = $keyword.' - '. $_FANWE['nav_title'];
			$page_args['keyword'] = urlencode($keyword);
	
				
			$search_navs = array();
			SearchModule::formatSearchNavs($search_navs,$page_args['keyword']);
	
			if(!$is_empty)
			{
				$where = " WHERE match(ftm.content) against('".$match_key."' IN BOOLEAN MODE)";
				$sql_count = 'SELECT COUNT(ftm.tid) FROM '.FDB::table('forum_thread_match').' AS ftm ';
				$fid = (int)$_FANWE['request']['fid'];
				if($fid > 0)
				{
					$sql_count.= ' INNER JOIN '.FDB::table('forum_thread').' AS ft ON ft.tid = ftm.tid ';
					$where.= " AND ft.fid = ".$fid;
					$page_args['fid'] = $fid;
				}
				
				$count = (int)FDB::resultFirst($sql_count.$where);
				$topic_list = array();
				if($count > 0)
				{
					$pager = buildPage('search/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],20);
					$page_args['page'] = $_FANWE['page'];
					$img_ids = array();
					$sql = 'SELECT ft.*  
						FROM '.FDB::table('forum_thread_match').' AS ftm 
						INNER JOIN '.FDB::table('forum_thread').' AS ft ON ft.tid = ftm.tid '.$where.' 
						ORDER BY ft.tid DESC LIMIT '.$pager['limit'];
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$topic_list[$data['tid']] = $data;
					}
				}
			}
			
			$keyword = htmlspecialchars($keyword);
			include template('page/search/search_topic');
			display($cache_file);
		}
		else
		{
			include $cache_file;
			display();
		}
	}
	
	private function formatSearchNavs(&$navs,$keyword)
	{
		$args = array('keyword'=>$keyword);
		$navs['all'] = FU('search/all',$args);
		$navs['bao'] = FU('search/bao',$args);
		$navs['photo'] = FU('search/photo',$args);
		$navs['album'] = FU('search/album',$args);
		$navs['user'] = FU('search/user',$args);
		$navs['group'] = FU('search/group',$args);
		$navs['topic'] = FU('search/topic',$args);
	}
}
?>