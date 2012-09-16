<?php
class msgviewMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		if($_FANWE['uid'] == 0)
			exit;
		//print_r($_FANWE['requestData']); exit;
		$mlid = (int)$_FANWE['requestData']['lid'];
		$mid = (int)$_FANWE['requestData']['mid'];

		if($mlid == 0 && $mid == 0)
			exit;

		if($mlid > 0)
		{
			$mlist = FS('Message')->getListByMlid($mlid,$_FANWE['uid']);
			if(empty($mlist))
				exit;

			$act2 = $_FANWE['requestData']['act_2'];
			if($act2 == 'reply')
			{
				$message = trim($_FANWE['requestData']['message']);
				if(!empty($message))
				{
					$message = cutStr($message,200);
					if(FS('Message')->replyMsg($mlid,$_FANWE['uid'],$_FANWE['user_name'],$message) > 0)
						$mlist['num']++;
				}
			}

			$root['lid'] = $mlid;
			$page = (int)$_FANWE['requestData']['page'];
			$page = max(1,$page);

			$total = $mlist['num'];
			$page_size = PAGE_SIZE;
			$page_total = max(1,ceil($total/$page_size));
			if($page > $page_total)
				$page = $page_total;
			$limit = (($page - 1) * $page_size).",".$page_size;
			$root['page'] = array("page"=>$page,"page_total"=>$page_total);


			$tuser = FS('User')->getUserById($mlist['tuid']);
			$tuser['user_avatar'] = avatar($tuser['avatar'],'m',true);

			//$root['title'] = "共有{$total}封与{$tuser['user_name']}的交流信件";
			$root['title'] = "与{$tuser['user_name']}的交流";
			$root['t_name'] = $tuser['user_name'];
			
			$msg_list = FS('Message')->getMsgsByMlid($mlid,$_FANWE['uid'],$limit);
			foreach($msg_list as $msg)
			{
				if($msg['uid'] == $_FANWE['uid'])
				{
					$user = $_FANWE['user'];
					$user['user_name'] = '我';
					$msg['tuid'] = $mlist['tuid'];
					$msg['tuser_name'] = $tuser['user_name'];
					$msg['tuser_avatar'] = $tuser['user_avatar'];
				}
				else
				{
					$user = $tuser;
					$msg['tuid'] = $_FANWE['uid'];
					$msg['tuser_name'] = '我';
					$msg['tuser_avatar'] = $_FANWE['user']['user_avatar'];
				}
				$msg['content'] = $msg['message'];
				$msg['user_name'] = $user['user_name'];
				$msg['user_avatar'] = $user['user_avatar'];
				m_express($msg,$msg['message']);
				$root['msg_list'][] = $msg;
			}
		}
		elseif($mid)
		{
			$msg = FS('Message')->getSysMsgByMid($_FANWE['uid'],$mid);
			$msg['time'] = getBeforeTimelag($msg['dateline']);
			$msg['message'] = str_replace('href="/','href="'.$_FANWE['site_url'],$msg['message']);
			$root['msg'] = $msg;
		}
		m_display($root);
	}
}
?>