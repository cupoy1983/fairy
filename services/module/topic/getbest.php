<?php
$tid = (int)$_FANWE['request']['id'];
if($tid == 0)
	exit;

if($_FANWE['uid'] == 0)
	exit;

$topic = FS("Topic")->getTopicById($tid);
if(!$topic)
	exit;

$topic['user_name'] = FDB::resultFirst('SELECT user_name FROM '.FDB::table('user').' WHERE uid = '.$topic['uid']);
include template('services/topic/getbest');		
display();
?>