<?php
class delcommentMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;
		$root['act'] = 'delcomment';

		if($_FANWE['uid'] == 0)
			exit;
		
		$comment_id = intval($_FANWE['requestData']['id']);
		if($comment_id == 0)
			exit;

		$share = FDB::fetchFirst('SELECT s.uid,s.share_id  
			FROM '.FDB::table('share_comment').' AS sc 
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = sc.share_id 
			WHERE sc.comment_id = '.$comment_id);

		if(empty($share))
			exit;

		$uid = intval($share['uid']);
		if($uid != $_FANWE['uid'])
			exit;

		FS('Share')->deleteShareComment($comment_id);

		$root['return'] = 1;
		m_display($root);
	}
}
?>