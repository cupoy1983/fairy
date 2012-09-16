<?php
$fid = (int)$_FANWE['request']['fid'];
if(!$fid)
	exit;
	
$uid = (int)$_FANWE['request']['uid'];
if(!$uid)
	exit;

if($_FANWE['uid'] == 0)
	exit;

$group = FS('Group')->getGroupById($fid,false,false);
if(empty($group))
	exit;
	
$is_admin = FS('Group')->isAdminFromGroup($fid,$_FANWE['uid']);
if($is_admin < 1)
	exit;

$is_join = FS('Group')->setUserToGroup($fid,$uid,'',1);
$result['status'] = $is_join;
outputJson($result);
?>