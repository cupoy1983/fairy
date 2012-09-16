<?php
require ROOT_PATH.'core/fanwe.php';
$fanwe = &FanweService::instance();
$fanwe->is_session = false;
$fanwe->is_user = false;
$fanwe->is_cron = false;
$fanwe->is_misc = false;
$fanwe->cache_list = array('goods_category','index_cate_group');
$fanwe->initialize();

$_FANWE['request'] = unserialize(REQUEST_ARGS);
$index_cids = $_FANWE['cache']['goods_category']['index'];
$cate_list = array();
if(count($index_cids) > 0)
{
	foreach($index_cids as $cid)
	{
		$cate_list[$cid] = false;
	}

	$imgs_ids = array();
	$sql = 'SELECT * FROM '.FDB::table('index_cate_share').' 
		WHERE cid IN ('.implode(',',$index_cids).') AND gid > 0 ORDER BY sort ASC,share_id DESC';
	
	$res = FDB::query($sql);
	while($data = FDB::fetch($res))
	{
		if(empty($data['url']))
			$data['url'] = FU('note/index',array('sid'=>$data['share_id']));
		
		$cate_list[$data['cid']]['groups'][$data['gid']]['share_list'][$data['share_id']] = $data;
		if($data['cimg_id'] > 0)
			$imgs_ids[$data['cimg_id']][] = &$cate_list[$data['cid']]['groups'][$data['gid']]['share_list'][$data['share_id']];
		else
			$imgs_ids[$data['img_id']][] = &$cate_list[$data['cid']]['groups'][$data['gid']]['share_list'][$data['share_id']];
		$cate_list[$data['cid']]['groups'][$data['gid']]['today_count'] = 0;
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
		if(isset($cate_item['groups']))
		{
			foreach($cate_item['groups'] as $key => $gid)
			{
				$group = $_FANWE['cache']['index_cate_group'][$gid];
				if(!isset($cate_list[$cid]['groups'][$gid]) || !$group)
					unset($cate_item['groups'][$key]);
				else
				{
					$cate_item['groups'][$key] = $group;
					$tags = "'".implode("','",$group['tags'])."'";
					$cate_item['groups'][$key]['share_list'] = $cate_list[$cid]['groups'][$gid]['share_list'];
					$sql = 'SELECT COUNT(DISTINCT sgi.share_id) FROM '.FDB::table('share_tags').' AS st 
						INNER JOIN '.FDB::table('share_category').' AS sc ON sc.share_id = st.share_id AND sc.cate_id = '.$cid.' 
						INNER JOIN '.FDB::table('share_goods_index').' AS sgi ON sgi.share_id = sc.share_id 
						WHERE st.tag_name IN ('.$tags.')';
					$cate_item['groups'][$key]['count'] = FDB::resultFirst($sql);
				}
			}
			if(count($cate_item['groups']) > 0)
				$list[] = $cate_item;
		}
	}
	$args['cate_list'] = $list;
}
$html = tplFetch('inc/index/index_cate_share',$args);
setCache('index/cate_share',$html,SHARE_CACHE_TIME);
?>
