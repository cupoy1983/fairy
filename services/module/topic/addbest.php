<?php
$tid = (int)$_FANWE['request']['tid'];
if(!$tid)
	exit;

if($_FANWE['uid'] == 0)
	exit;
	
if(empty($_FANWE['request']['content']))
	exit;

$check_result = FS('Share')->checkWord($_FANWE['request']['content'],'content');
if($check_result['error_code'] == 1)
{
	$result['status'] = -1;
	$result['msg_error'] = $check_result['error_msg'];
	outputJson($result);
}

$is_pub = (int)$_FANWE['request']['pub_out_check'];

$result['status'] = FS("Topic")->bestTopic($tid,$_FANWE['uid'],htmlspecialchars($_FANWE['request']['content']),$is_pub);
if($result['status'] == -1)
	exit;

$best_count = (int)FDB::resultFirst('SELECT best_count FROM '.FDB::table('forum_thread').' WHERE tid = '.$tid);
$best_users =  FS("Topic")->getBestUsers($tid,9);

$args = array(
	'tid'=>$tid,
	'topic'=>FS('Topic')->getTopicById($tid),
	'is_best'=>$result['status'],
	'best_count'=>$best_count,
	'best_users'=>$best_users,
);

$result['html'] = tplFetch('inc/group/best_user',$args);
outputJson($result);
?>