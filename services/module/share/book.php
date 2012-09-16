<?php
$cache_key = md5(http_build_query($_FANWE['request']));
$cache_key = 'book/'.substr($cache_key,0,2).'/'.substr($cache_key,2,2).'/'.$cache_key;
$html = getCache($cache_key);
if($html == NULL)
{
	$pb_index = (int)$_FANWE['request']['pindex'];
	if($pb_index < 2 || $pb_index > (int)$_FANWE['setting']['share_pb_load_count'])
		exit;

	$category = urldecode($_FANWE['request']['cate']);
	$is_root = false;
	if(isset($_FANWE['cache']['goods_category']['cate_code'][$category]))
		$cate_id = $_FANWE['cache']['goods_category']['cate_code'][$category];
	else
	{
		$category = (int)$category;
		if($category > 0 && isset($_FANWE['cache']['goods_category']['all'][$category]))
		{
			$cate_id = (int)$category;
		}
		else
		{
			$is_root = true;
			$cate_id = $_FANWE['cache']['goods_category']['root'];
		}
	}

	$sort = $_FANWE['request']['sort'];
	$sort = !empty($sort) ? $sort : "hot1";

	$category_data = $_FANWE['cache']['goods_category']['all'][$cate_id];

	$child_ids = array();
	if(isset($category_data['child']))
		$child_ids = $category_data['child'];

	if(!$is_root)
		$child_ids[] = $cate_id;

	$condition = '';
	
	$is_match = false;
	$tag = urldecode($_FANWE['request']['tag']);
	
	$gid = (int)$_FANWE['request']['gid'];
	$is_group = false;
	if(!$is_root && $gid > 0 && array_search($gid,$category_data['groups']) !== FALSE)
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
			$is_tag = true;
			$condition.=" AND st.tag_name IN ($group_tags)";
		}
	}
	
	if(!$is_group && !empty($tag))
	{
		$is_tag = true;
		$condition.=' AND st.tag_name = \''.addslashes($tag).'\'';
	}

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
	$sql_type = '';
	if($is_tag)
	{
		$sql = 'SELECT DISTINCT(sgi.share_id) FROM '.FDB::table('share_tags').' AS st ';
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
		}
		else
		{
			$sql = 'SELECT DISTINCT(sgi.share_id) FROM '.FDB::table('share_category').' AS sc ';
			$condition .= " AND sc.cate_id = ".$cate_id." ";
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

	$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
	$begin_count = ($_FANWE['page'] - 1) * $page_size + ($pb_index - 1) * (int)$_FANWE['setting']['share_pb_item_count'];
	
	$sql  = $sql.' LIMIT '.$begin_count."," . (int)$_FANWE['setting']['share_pb_item_count'];
	
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

	$args = array('share_list'=>$share_list,'book_type'=>'goods');	
	$html = trim(tplFetch("services/share/book",$args));
	setCache($cache_key,$html,SHARE_CACHE_TIME);
}
echo $html;
?>