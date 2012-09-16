<?php
class DapeiModule
{
	public function index()
	{
		global $_FANWE;
		include fimport('dynamic/book');
		$_FANWE['user_click_share_id'] = (int)$_FANWE['request']['sid'];
		unset($_FANWE['request']['sid']);
		$cache_file = getTplCache('page/book/book_dapei',$_FANWE['request'],2);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$page_args = array();
			$sort = $_FANWE['request']['sort'];
			$sort = !empty($sort) ? $sort : "hot1";
	
			$_FANWE['nav_title'] = lang('common','dapei');
			
			$type = $_FANWE['request']['type'];
			if($type == 'goods')
				$page_args['type'] = 'goods';

			//输出排序URL
			$sort_page_args = $page_args;
			$sort_page_args['sort'] = 'hot1';
	
			$hot1_url['url'] = FU('dapei/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot1')
				$hot1_url['act'] = 1;
	
			$sort_page_args['sort'] = 'hot7';
			$hot7_url['url'] = FU('dapei/'.ACTION_NAME,$sort_page_args);
			if($sort=='hot7')
				$hot7_url['act'] = 1;
	
			$sort_page_args['sort'] = 'new';
			$new_url['url'] = FU('dapei/'.ACTION_NAME,$sort_page_args);
			if($sort=='new')
				$new_url['act'] = 1;
	
			if(!empty($_FANWE['request']['sort']))
				$page_args['sort'] = $sort;
			else
				$page_args['sort'] = 'hot1';
			
			switch($sort)
			{
				//24小时最热 24小时喜欢人数
				case 'hot1':
					$sort = " ORDER BY collect_1count DESC,share_id DESC";
				break;
				//1周天最热 1周喜欢人数
				case 'hot7':
					$sort = " ORDER BY collect_7count DESC,share_id DESC";
				break;
				//最新
				case 'new':
					$sort = " ORDER BY share_id DESC";
				break;
				
				default:
					$sort = '';
				break;
			}
			
			if($type == 'goods')
			{
				$sql = 'SELECT DISTINCT(share_id) FROM '.FDB::table('share_dapei_goods').$sort;
				$sql_count = 'SELECT COUNT(DISTINCT share_id) FROM '.FDB::table('share_dapei_goods');
			}
			else
			{
				$sql = 'SELECT DISTINCT(share_id) FROM '.FDB::table('share_dapei_index').$sort;
				$sql_count = 'SELECT COUNT(DISTINCT share_id) FROM '.FDB::table('share_dapei_index');
			}
	
			$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
			$count = FDB::resultFirst($sql_count);
	
			$pager = buildPage('dapei/'.ACTION_NAME,$page_args,$count,$_FANWE['page'],$page_size,'',3);
			$page_args['page'] = $_FANWE['page'];
			$page_args['pindex'] = '_pindex_';
			$pb_url = $_FANWE['site_root'].'services/service.php?m=share&a=dapei&'.http_build_query($page_args);
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

			$dapei_bests = FS('Dapei')->getBest();
			$dapei_darens = FS('Daren')->getDarensByType(2);
	
			include template('page/book/book_dapei');
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