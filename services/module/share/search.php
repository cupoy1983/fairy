<?php
$keyword = trim(urldecode($_FANWE['request']['keyword']));
if(empty($keyword) || $keyword == lang('template','search_share'))
	exit;
else
{
	$match_key = FS('Words')->segmentToUnicode($keyword,'+');
	if(empty($match_key))
		exit;
}

$cache_key = md5(http_build_query($_FANWE['request']));
$cache_key = 'search/'.substr($cache_key,0,2).'/'.substr($cache_key,2,2).'/'.$cache_key;
$html = getCache($cache_key);
if($html == NULL)
{
	$pb_index = (int)$_FANWE['request']['pindex'];
	if($pb_index < 2 || $pb_index > (int)$_FANWE['setting']['share_pb_load_count'])
		exit;

	switch($_FANWE['request']['sort'])
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
	
	$action = $_FANWE['request']['act'];
	$match_table = 'share_goods_match';
	$share_table = 'share_goods_index';
	$book_type = 'goods';
	switch($action)
	{
		case 'photo':
			$match_table = 'share_photo_match';
			$share_table = 'share_photo_index';
			$book_type = 'photo';
		break;
		case 'all':
			$match_table = 'share_match';
			$share_table = 'share_images_index';
			$book_type = 'all';
		break;
	}
	
	$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
	$begin_count = ($_FANWE['page'] - 1) * $page_size + ($pb_index - 1) * (int)$_FANWE['setting']['share_pb_item_count'];
	$where = " WHERE match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE)";
	$append_sql = 'INNER JOIN '.FDB::table($share_table).' AS si ON si.share_id = sm.share_id ';
	$sql = 'SELECT si.share_id FROM '.FDB::table($match_table).' AS sm '.$append_sql.$where.$sort;
	$sql .= ' LIMIT '.$begin_count."," . (int)$_FANWE['setting']['share_pb_item_count'];
	
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

	$args = array('share_list'=>$share_list,"book_type"=>$book_type);	
	$html = trim(tplFetch("services/share/book",$args));
	setCache($cache_key,$html,SHARE_CACHE_TIME);
}
echo $html;
?>