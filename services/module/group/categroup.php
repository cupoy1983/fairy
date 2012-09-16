<?php
$cid = (int)$_FANWE['request']['cid'];
if($cid == 0)
	exit;

$key = 'group/'.$cid.'/16_'.$_FANWE['page'];
$groups = getCache($key);
if($groups === NULL)
{
	$groups = FS('Group')->getCateGroups($cid,16,$_FANWE['page']);
	$args['groups'] = $groups;
	$groups['list'] = tplFetch('services/group/categroup',$args);
	setCache($key,$groups,SHARE_CACHE_TIME);
}

outputJson($groups);
?>
