<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: awfigq <awfigq@qq.com>
// +----------------------------------------------------------------------
/**
 +------------------------------------------------------------------------------
 手机端信息推送
 +------------------------------------------------------------------------------
 */
class MApnsAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$begin_time = trim($_REQUEST['begin_time']);
		$end_time = trim($_REQUEST['end_time']);
		$status = trim($_REQUEST['status']);
		$inner_sql = '';
		
		if(!empty($begin_time))
		{
			$this->assign("begin_time",$begin_time);
			$parameter['begin_time'] = $begin_time;
			$begin_time = strZTime($begin_time);
			if($begin_time > 0)
			{
				$begin_time1 = toDate($begin_time);
				$where .= " AND am.created >= '$begin_time1'";
			}
		}
		else
			$begin_time = 0;
		
		if(!empty($end_time) && strZTime($end_time) > $begin_time)
		{
			$this->assign("end_time",$end_time);
			$parameter['end_time'] = $end_time;
			$end_time = strZTime($end_time);
			if($end_time > 0)
			{
				$end_time1 = toDate($end_time);
				$where .= " AND am.created <= '$end_time1'";
			}
		}
		
		if(!empty($status))
		{
			$this->assign("status",$status);
			$parameter['status'] = $status;
			$where .= " AND am.status = '$status'";
		}

		if(!empty($where))
		{
			$where = 'WHERE' . $where;
			$where = str_replace('WHERE AND','WHERE',$where);
		}

		$model = M();

		$sql = 'SELECT COUNT(am.pid) AS scount
			FROM '.C("DB_PREFIX").'apns_messages AS am '.$where;

		$count = $model->query($sql);
		$count = $count[0]['scount'];

		$sql = 'SELECT am.*,u.user_name 
			FROM '.C("DB_PREFIX").'apns_messages AS am 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = am.clientid '.$where.' GROUP BY am.pid';
		$this->_sqlList($model,$sql,$count,$parameter,'am.pid');
		$this->display ();
	}

	public function clear()
	{
		$where = '';
		$parameter = array();
		$begin_time = trim($_REQUEST['begin_time']);
		$end_time = trim($_REQUEST['end_time']);
		$status = trim($_REQUEST['status']);
		
		if(!empty($begin_time))
		{
			$this->assign("begin_time",$begin_time);
			$parameter['begin_time'] = $begin_time;
			$begin_time = strZTime($begin_time);
			if($begin_time > 0)
			{
				$begin_time1 = toDate($begin_time);
				$where .= " AND created >= '$begin_time1'";
			}
		}
		else
			$begin_time = 0;
		
		if(!empty($end_time) && strZTime($end_time) > $begin_time)
		{
			$this->assign("end_time",$end_time);
			$parameter['end_time'] = $end_time;
			$end_time = strZTime($end_time);
			if($end_time > 0)
			{
				$end_time1 = toDate($end_time);
				$where .= " AND created <= '$end_time1'";
			}
		}
		
		if(!empty($status))
		{
			$this->assign("status",$status);
			$parameter['status'] = $status;
			$where .= " AND status = '$status'";
		}

		if(!empty($where))
		{
			$where = 'WHERE' . $where;
			$where = str_replace('WHERE AND','WHERE',$where);
		}

		$model = M();

		$sql = 'DELETE FROM '.C("DB_PREFIX").'apns_messages '.$where;
		M()->query($sql);
		$this->redirect('MApns/index');
	}

	public function add()
	{
		$this->display();
	}
	
	public function send()
	{
		$content = trim($_REQUEST['content']);
		$user_names = trim($_REQUEST['user_names']);
		if(empty($content))
			$this->error(L('CONTENT_REQUIRE'));
		
		$pids = NULL;

		if(!empty($user_names))
		{
			$user_names = explode(',',$user_names);
			$user_names = array_unique($user_names);
			$condition = array('user_name' => array('in',$user_names),'status'=>1);
			$users = D('User')->where($condition)->field('uid')->select();
			$uids = array();
			foreach($users as $user)
			{
				$uids[] = (int)$user['uid'];
			}

			if(count($uids) > 0)
			{
				$pids = array();
				$condition = array('clientid' => array('in',$uids),'status'=>'active');
				$devices = D('ApnsDevices')->where($condition)->field('pid')->select();
				foreach($devices as $device)
				{
					$pids[] = (int)$device['pid'];
				}

				if(count($pids) == 0)
					$this->error(L('UIDS_ERROR2'));
			}
			else
				$this->error(L('UIDS_ERROR1'));
		}

		Vendor('common');
		require fimport('class/apns');
		$apns = new APNS();
		$apns->newMessage($pids);
		$apns->addMessageAlert($content);
		//$apns->addMessageBadge(2);
		$apns->addMessageSound('bingbong.aiff');
		$apns->queueMessage();

		$fp=fsockopen($_SERVER['HTTP_HOST'],80,&$errno,&$errstr,5);
		if($fp)
		{
			$request = "GET ".SITE_URL."apns.php?process=1 HTTP/1.0\r\n";
			$request .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
			$request .= "Connection: Close\r\n\r\n";
			fwrite($fp, $request);
			while(!feof($fp))
			{
				fgets($fp, 128);
				break;
			}
			fclose($fp);
		}
		$this->success (L('SEND_SUCCESS'));
	}
}

function getContent($message)
{
	$message = json_decode($message);
	return $message->aps->alert->body;
}

function getStatusName($status)
{
	return L('SEND_STATUS_'.$status);
}
?>