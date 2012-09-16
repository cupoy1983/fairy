<?php
$fid = (int)$_FANWE['request']['fid'];
if(!$fid)
	exit;

if($_FANWE['uid'] == 0)
	exit;

$group = FS('Group')->getGroupById($fid,false,false);
if(empty($group))
	exit;

$type = (int)$_FANWE['request']['type'];
$is_join = FS('Group')->isUserFromGroup($fid,$_FANWE['uid']);
if($type == 0)
{
	if($is_join == 0)
		$is_join = FS('Group')->setUserToGroup($fid,$_FANWE['uid'],$_FANWE['user_name']);
}
elseif($type == 1)
{
	if($is_join == 1)
		$is_join = FS('Group')->setUserToGroup($fid,$_FANWE['uid'],'',1);
}

if($is_join == -1)
	exit;

$result['status'] = $is_join;
$args = array(
	'is_join_group'=>$is_join,
	'group'=>FS('Group')->getGroupById($fid,false,false),
);
$result['html'] = tplFetch('services/group/user_apply',$args);
outputJson($result);
?>