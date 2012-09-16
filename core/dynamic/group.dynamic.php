<?php
//小组动态内容的函数
function getNewBestTopics()
{
	global $_FANWE;
	$args = array();
	$cache_file = getTplCache('inc/group/new_topic',array(),1);
	if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
	{
		$list = FS('Topic')->getImgTopic('best',12,1,0,0,array(),true);
		$args['flash_list'] = array();
		if(count($list) > 0)
			$args['flash_list'] = array_slice($list,0,4);
		
		$args['best_pics'] = array();
		if(count($list) > 4)
			$args['best_pics'] = array_slice($list,4,2);
			
		$args['best_text'] = array();
		if(count($list) > 6)
			$args['best_text'] = array_slice($list,6,6);
	}
	return tplFetch('inc/group/new_topic',$args,'');
}

function getBestFlashs($fid)
{
	global $_FANWE;
	$args = array();
	$cache_file = getTplCache('inc/group/flash_topic',array($fid),1);
	if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
	{
		$args['best_list'] = FS('Topic')->getImgTopic('best',6,1,$fid);
	}
	return tplFetch('inc/group/flash_topic',$args,'',$cache_file);
}

function getMeGroups()
{
	global $_FANWE;
	$args = array();
	if($_FANWE['uid'] == 0)
	{
		return tplFetch('inc/group/empty_me_group',$args);
	}
	else
	{
		$args['uid'] = $_FANWE['uid'];
		$args['page_size'] = 8;
		$args['page'] = 1;
		$cache_file = getTplCache('inc/group/me_groups',$args,1);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			$args['groups'] = FS('Group')->getGroupsByUid($args['uid']);
		}
		return tplFetch('inc/group/me_groups',$args,'',$cache_file);
	}
}
?>