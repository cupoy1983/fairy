<?php
$tid = (int)$_FANWE['request']['tid'];
if(!$tid)
	exit;

if($_FANWE['uid'] == 0)
	exit;


$result['status'] = FS("Topic")->bestTopic($tid,$_FANWE['uid']);
if($result['status'] == -1)
	exit;

$best_count = (int)FDB::resultFirst('SELECT best_count FROM '.FDB::table('forum_thread').' WHERE tid = '.$tid);
$best_users =  FS("Topic")->getBestUsers($tid,9);

$args = array(
	'tid'=>&$tid,
	'topic'=>FS('Topic')->getTopicById($tid),
	'is_best'=>&$result['status'],
	'best_count'=>&$best_count,
	'best_users'=>&$best_users,
);
$result['html'] = tplFetch('inc/group/best_user',$args);
outputJson($result);
?>