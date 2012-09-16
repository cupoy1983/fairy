<?php
class addcommentMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		//未登陆直接退出
		if($_FANWE['uid'] == 0)
		{
			$root['status'] = -1;
			m_display($root);
		}

		$share_id = (int)$_FANWE['requestData']['share_id'];
		//没有分享ID直接退出
		if($share_id == 0)
		{
			$root['status'] = -2;
			m_display($root);
		}
		
		$share = FS('Share')->getShareById($share_id);
		//没有分享直接退出
		if(empty($share))
		{
			$root['status'] = -3;
			m_display($root);
		}

		$check_result = FS('Share')->checkWord($_FANWE['requestData']['content'],'content');
		if($check_result['error_code'] == 1)
		{
			$root['status'] = -4;
			$root['error'] = $check_result['error_msg'];
			m_display($root);
		}

		$comment_id = FS('Share')->saveComment($_FANWE['requestData']);
		$comment = FS('Share')->getShareComment($comment_id);
		$comment['time'] = getBeforeTimelag($comment['create_time']);
		$parses = m_express($item['content']);
		$comment['parse_users'] = $parses['users'];
		$comment['parse_events'] = $parses['events'];
		$comment['user_name'] = $_FANWE['user_name'];
		$comment['user_avatar'] = $_FANWE['user_avatar'];
		
		$root['status'] = 1;
		$root['item'] = $comment;
		m_display($root);
	}
}
?>