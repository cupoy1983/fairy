<?php
$cache_key = md5(http_build_query($_FANWE['request']));
$cache_key = 'look/'.substr($cache_key,0,2).'/'.substr($cache_key,2,2).'/'.$cache_key;
$html = getCache($cache_key);
if($html == NULL)
{
	$pb_index = (int)$_FANWE['request']['pindex'];
	if($pb_index < 2 || $pb_index > (int)$_FANWE['setting']['share_pb_load_count'])
		exit;

	$condition = '';

	$sort = $_FANWE['request']['sort'];
	$sort = !empty($sort) ? $sort : "hot1";
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

	$type = $_FANWE['request']['type'];
	if($type == 'goods')
	{
		$sql = 'SELECT DISTINCT(share_id) FROM '.FDB::table('share_look_goods').$sort;
	}
	else
	{
		$sql = 'SELECT DISTINCT(share_id) FROM '.FDB::table('share_look_index').$sort;
	}

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

	$args = array('share_list'=>$share_list);	
	$html = trim(tplFetch("services/share/look",$args));
	setCache($cache_key,$html,SHARE_CACHE_TIME);
}
echo $html;
?>