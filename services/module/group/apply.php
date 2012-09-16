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

$type = (int)$_FANWE['request']['type'];
$is_admin = FS('Group')->isAdminFromGroup($fid,$_FANWE['uid']);
if($is_admin < 1)
	exit;

$is_apply = FS('Group')->groupUserApplyHandle($fid,$uid,$type);
if($is_apply == -1)
	exit;
	
$result['status'] = $is_apply;
outputJson($result);
?>