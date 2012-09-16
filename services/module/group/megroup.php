<?php
if($_FANWE['uid'] == 0)
	exit;

$key = 'user/'.getDirsById($_FANWE['uid']).'/group/services_8_'.$_FANWE['page'];
$groups = getCache($key);
if($groups === NULL)
{
	$groups = FS('Group')->getGroupsByUid($_FANWE['uid'],8,$_FANWE['page']);
	$args['groups'] = $groups;
	$groups['list'] = tplFetch('services/group/me_groups',$args);
	setCache($key,$groups,SHARE_CACHE_TIME);
}

outputJson($groups);
?>
