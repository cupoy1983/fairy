<?php
function bindCacheGoodscate()
{
	$categorys = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('goods_category')." WHERE status = 1 ORDER BY sort ASC,cate_id ASC");
	while($data = FDB::fetch($res))
	{
		$tags = array();
		$tag_names = array();
		$tres = FDB::query('SELECT gt.tag_id,gt.tag_name,gct.is_hot,gct.is_index 
			FROM '.FDB::table('goods_category_tags').' AS gct 
			INNER JOIN '.FDB::table('goods_tags').' AS gt ON gt.tag_id = gct.tag_id 
			WHERE gct.cate_id = '.$data['cate_id'].' ORDER BY gct.sort ASC');
		while($tag = FDB::fetch($tres))
		{
			$tag['url_tag'] = urlencode($tag['tag_name']);
			$tags[] = $tag;
			$tag_names[] = $tag['tag_name'];
			if($tag['is_index'] == 1)
				$data['index_tags'][] = $tag;
		}
		FanweService::instance()->cache->saveCache('goods_category_tags_'.$data['cate_id'], $tags);
		FanweService::instance()->cache->saveCache('goods_category_tagnames_'.$data['cate_id'], $tag_names);
		if(!empty($data['cate_code']))
			$categorys['cate_code'][$data['cate_code']] = $data['cate_id'];
		else
			$data['cate_code'] = $data['cate_id'];
		
		$categorys['all'][$data['cate_id']] = $data;
		
		if($data['is_root'] == 1)
			$categorys['root'] = $data['cate_id'];
		elseif($data['parent_id'] == 0)
			$categorys['parent'][] = $data['cate_id'];
		
		if($data['is_index'] == 1)
			$categorys['index'][] = $data['cate_id'];
	}
	
	foreach($categorys['all'] as $key => $val)
	{
		if($val['parent_id'] > 0)
			$categorys['all'][$val['parent_id']]['child'][] = $key;
		getGoodsCategoryChilds($key,$categorys['all'][$key],$categorys['all']);
	}
	
	foreach($categorys['all'] as $key => $val)
	{
		if(isset($val['childs']))
		{
			foreach($val['childs'] as $cid)
			{
				$categorys['all'][$cid]['parents'][] = $key;
			}
		}
	}
	
	$groups = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('index_cate_group')." WHERE status = 1 ORDER BY sort ASC,id ASC");
	while($data = FDB::fetch($res))
	{
		if(isset($categorys['all'][$data['cid']]))
			$categorys['all'][$data['cid']]['groups'][] = $data['id'];
		
		$data['tags'] = explode(',',addslashes($data['tags']));
		$groups[$data['id']] = $data;
	}
	
	FanweService::instance()->cache->saveCache('goods_category', $categorys);
	FanweService::instance()->cache->saveCache('index_cate_group', $groups);
	
	$categorys = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('goods_cates_gl'));
	while($data = FDB::fetch($res))
	{
		$categorys[$data['type']][$data['f_cate_id']] = $data['cate_id'];
	}
	
	foreach($categorys as $key => $val)
	{
		FanweService::instance()->cache->saveCache('goods_cate_related_'.$key, $val);
	}
}

function getGoodsCategoryChilds($cid,&$cate,$list)
{
	foreach($list as $item)
	{
		$cate_id = $item['cate_id'];
		if($item['parent_id'] == $cid)
		{
			$cate['childs'][] = $cate_id;
			unlink($list[$cate_id]);
			getGoodsCategoryChilds($cate_id,$cate,$list);
		}
		else
			unlink($list[$cate_id]);
	}
}
?>