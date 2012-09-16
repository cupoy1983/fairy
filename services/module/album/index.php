<?php
$cache_key = md5(http_build_query($_FANWE['request']));
$cache_key = 'album/'.substr($cache_key,0,2).'/'.substr($cache_key,2,2).'/'.$cache_key;
$html = getCache($cache_key);
if($html == NULL)
{
	$pb_index = (int)$_FANWE['request']['pindex'];
	if($pb_index < 2 || $pb_index > (int)$_FANWE['setting']['share_pb_load_count'])
		exit;

	$sort = $_FANWE['request']['sort'];
	switch($sort)
	{
		case 'new':
			$order = " ORDER BY share_id DESC";
		break;
		default:
			$order = " ORDER BY collect_count DESC,share_id DESC";
		break;
	}
	
	$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
	$begin_count = ($_FANWE['page'] - 1) * $page_size + ($pb_index - 1) * (int)$_FANWE['setting']['share_pb_item_count'];
	
	$limit = ' LIMIT '.$begin_count."," . (int)$_FANWE['setting']['share_pb_item_count'];
	
	$share_list = array();
	$res = FDB::query('SELECT DISTINCT share_id FROM '.FDB::table('album_share_index').$order.$limit);
	while($data = FDB::fetch($res))
	{
		$share_list[$data['share_id']] = false;
	}

	if(count($share_list) > 0)
	{
		$share_ids = array_keys($share_list);
		$sql = 'SELECT share_id,title,rec_id,uid,content,collect_count,comment_count,create_time,cache_data 
			FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',$share_ids).')';
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$share_list[$data['share_id']] = $data;
		}
		$share_list = FS('Share')->getShareDetailList($share_list,false,false,false,true,2);
	}

	$args = array('share_list'=>$share_list);	
	$html = trim(tplFetch("services/album/index",$args));
	setCache($cache_key,$html,SHARE_CACHE_TIME);
}
echo $html;
?>