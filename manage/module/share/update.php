<?php
$share_id = intval($_FANWE['request']['share_id']);
if($share_id == 0)
	exit;

if(!checkAuthority('share','edit'))
	exit;

$manage_lock = checkIsManageLock('share',$share_id);
if($manage_lock !== false)
	exit;


$old_share = FS("Share")->getShareById($share_id);
if(empty($old_share))
{
	deleteManageLock('share',$share_id);
	exit;
}

$data['title'] = $_FANWE['request']['title'];
$data['content'] = $_FANWE['request']['content'];

$data['sort'] = (int)$_FANWE['request']['sort'];

if(FDB::update("share",$data,"share_id=".$share_id))
{
	$rec_data['title'] = $data['title'];
	$rec_data['content'] = $data['content'];
	switch($old_share['type'])
	{
		case 'bar':
			FDB::update('forum_thread',$rec_data,"share_id = '$share_id'");
			if($old_share['title'] !=  $data['title'] || $old_share['content'] !=  $data['content'])
				FS("Topic")->updateTopic($data['rec_id'],$data['title'],$data['content']);
		break;

		case 'bar_post':
			if($old_share['content'] !=  $data['content'])
				FDB::update('forum_post',$rec_data,"share_id = '$share_id'");
		break;
	}
	
	$tags = $_FANWE['request']['tags'];
	$tags = explode(" ",$tags);

    FS('Share')->updateShareTags($share_id,array('user'=>implode(' ',$tags)));
    
    //更新喜欢统计
	FDB::query("UPDATE ".FDB::table("share")." set collect_count = (select count(*) from ".FDB::table("user_collect")." where share_id = '".$share_id."' ) where share_id = '".$share_id."'");
	//更新评论统计
	FDB::query("UPDATE ".FDB::table("share")." set comment_count = (select count(*) from ".FDB::table("share_comment")." where share_id = '".$share_id."' ) where share_id = '".$share_id."'");
	
	//更新分类
	$cates_arr = explode(",",$_FANWE['request']['share_cates']);
	foreach($cates_arr as $k=>$v)
	{
		$cates[] = intval($v);
	}

	FS('Share')->updateShareCate($share_id,$cates);
    FS('Share')->deleteShareCache($share_id);
    createManageLog('share','edit',$share_id,lang('manage','manage_edit_success'));
	deleteManageLock('share',$share_id);
	$msg = lang('manage','manage_edit_success');
	include template('manage/tooltip');
	display();
}

?>
