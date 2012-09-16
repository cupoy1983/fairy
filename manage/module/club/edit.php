<?php
$id = intval($_FANWE['request']['id']);
if($id == 0)
	exit;

if(!checkAuthority('club','edit'))
	exit;

$manage_lock = checkIsManageLock('club',$id);
if($manage_lock === false)
	createManageLock('club',$id);
else
	exit;

$topic = FS('Topic')->getTopicById($id);
if(empty($topic))
{
	deleteManageLock('club',$id);
	exit;
}

$group_detail = FS('Group')->getGroupById($topic['fid']);

include template('manage/club/edit');
display();
?>