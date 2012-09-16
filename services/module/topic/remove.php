<?php
$tid = (int)$_FANWE['request']['tid'];
if(!$tid)
	exit;

if($_FANWE['uid'] == 0)
	exit;

$topic = FS("Topic")->getTopicById($tid);
if(empty($topic))
	exit;

$is_group_admin = 0;
if($topic['fid'] > 0)
	$is_group_admin = FS('Group')->isAdminFromGroup($topic['fid'],$_FANWE['uid']);

if($is_group_admin == 0 && $topic['uid'] != $_FANWE['uid'])
	exit;

FS("Topic")->deleteTopic($tid,false);
$result['status'] = 1;
outputJson($result);
?>