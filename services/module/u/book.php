<?php
$uid = (int)$_FANWE['request']['uid'];
if(!$uid)
	exit;

$cache_key = md5(http_build_query($_FANWE['request']));
$cache_key = 'ubook/'.substr($cache_key,0,2).'/'.substr($cache_key,2,2).'/'.$cache_key;
$html = getCache($cache_key);
if($html == NULL)
{
	$pb_index = (int)$_FANWE['request']['pindex'];
	if($pb_index < 2 || $pb_index > (int)$_FANWE['setting']['share_pb_load_count'])
		exit;
	
	$sql = 'SELECT share_id FROM '.FDB::table('share_user_images').' WHERE uid = '.$uid.' ORDER BY share_id DESC';
	$page_size = (int)$_FANWE['setting']['share_pb_item_count'] * (int)$_FANWE['setting']['share_pb_load_count'];
	$begin_count = ($_FANWE['page'] - 1) * $page_size + ($pb_index - 1) * (int)$_FANWE['setting']['share_pb_item_count'];
	$sql .= ' LIMIT '.$begin_count."," . (int)$_FANWE['setting']['share_pb_item_count'];
	
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

	$args = array('share_list'=>$share_list);	
	$html = trim(tplFetch("services/u/book",$args));
	setCache($cache_key,$html,SHARE_CACHE_TIME);
}
echo $html;
?>