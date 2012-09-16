<?php
class messageMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		if($_FANWE['uid'] == 0)
			exit;

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$total = FS('Message')->getMsgCount($_FANWE['uid']);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;

		if($page == 1)
		{
			$sys_msgs = FS('Message')->getSysMsgs($_FANWE['uid']);
			foreach($sys_msgs as $msg)
			{
				unset($msg['message']);
				$msg['time'] = getBeforeTimelag($msg['dateline']);
				$root['sys_msgs'][] = $msg;
			}

			$sys_notices = FS('Notice')->getList($_FANWE['uid']);
			foreach($sys_notices as $msg)
			{
				$msg['content'] = str_replace('href="/','href="'.$_FANWE['site_url'],$msg['content']);
				$msg['time'] = getBeforeTimelag($msg['create_time']);
				$root['sys_notices'][] = $msg;
			}
		}
		$msg_list = FS('Message')->getMsgList($_FANWE['uid'],$limit);
		//print_r($msg_list);exit;
		foreach($msg_list as $msg)
		{
			$data = array();
			$data['content'] = $msg['message'];
			if($msg['msg_fuid'] == $_FANWE['uid'])
			{
				$data['uid'] = $_FANWE['uid'];
				$data['user_name'] = '我';
				$data['user_avatar'] = $_FANWE['user']['user_avatar'];

				$data['tuid'] = $msg['msg_tuser']['uid'];
				$data['tuser_name'] = $msg['msg_tuser']['user_name'];
				$data['tuser_avatar'] = avatar($msg['msg_tuser']['avatar'],'m',true);
			}
			else
			{
				$data['uid'] = $msg['msg_tuser']['uid'];
				$data['user_name'] = $msg['msg_tuser']['user_name'];
				$data['user_avatar'] = avatar($msg['msg_tuser']['avatar'],'m',true);

				$data['tuid'] = $_FANWE['uid'];
				$data['tuser_name'] = '我';
				$data['tuser_avatar'] = $_FANWE['user']['user_avatar'];

			}
			$data['time'] = getBeforeTimelag($msg['last_update']);

			$data['mlid'] = $msg['mlid'];
			$data['msg_count'] = $msg['num'];
			m_express($data,$msg['message']);
			$root['msg_list'][] = $data;
		}

		FDB::query("DELETE FROM ".FDB::table('user_notice')." WHERE uid='".$_FANWE['uid']."' AND type=5");
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>