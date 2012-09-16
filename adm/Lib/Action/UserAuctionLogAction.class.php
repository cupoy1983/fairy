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
 会员资金提现
 +------------------------------------------------------------------------------
 */
class UserAuctionLogAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$uname = trim($_REQUEST['uname']);
		$begin_time_str = trim($_REQUEST['begin_time']);
		$end_time_str = trim($_REQUEST['end_time']);
		
		$begin_time = !empty($begin_time_str) ? strZTime($begin_time_str) : 0;
		$end_time = !empty($end_time_str) ? strZTime($end_time_str) : 0;
		$status = trim($_REQUEST['status']);
		
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid > 0)
				$where.=" AND ual.uid = ".$uid;
		}
		
		if ($begin_time > 0)
		{
			$this->assign("begin_time",$begin_time_str);
			$parameter['begin_time'] = $begin_time_str;
			$where .= " AND ual.create_day >= '".$begin_time."'";
		}
		
		if ($end_time > 0)
		{
			$this->assign("end_time",$end_time_str);
			$parameter['end_time'] = $end_time_str;
			$where .= " AND ual.create_day < '".($end_time + 86400)."'";
		}
		
		if($status != "" && $status >= 0)
		{
			$this->assign("status",$status);
			$parameter['status'] = $status;
            $where .= ' AND ual.status = '.$status;
		}
		else
			$this->assign("status",-1);

		$model = M();

		if(!empty($where))
			$where = str_replace('WHERE AND','WHERE','WHERE'.$where);

		$sql = 'SELECT COUNT(ual.id) AS tcount
			FROM '.C("DB_PREFIX").'user_auction_log AS ual '.$where;

		$count = $model->query($sql);
		$count = $count[0]['tcount'];

		$sql = 'SELECT ual.*,u.user_name,u.money as user_money 
			FROM '.C("DB_PREFIX").'user_auction_log AS ual 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = ual.uid '.$where;
		$this->_sqlList($model,$sql,$count,$parameter,'ual.id');
		$list = $this->list;

		$this->assign('list',$list);
		$this->display();
		return;
	}
	
	public function show()
	{
		$id = intval($_REQUEST['id']);
		$order = D("UserAuctionLog")->where("id = $id")->find();
		$order['status_name'] = L("STATUS_".$order['status']);
		$order['pay_name'] = L("IS_PAY_".$order['is_pay']);
		if($order['is_pay'] == 0 && $order['pay_time'] > 0)
			$order['pay_name'] = L("IS_PAY_2");
			
		$this->assign ( 'order', $order );
		$user = D("User")->where("uid = ".$order['uid'])->find();
		$this->assign ('user',$user );
		$this->display ('show');
	}
	
	public function update()
	{
		$id = intval($_REQUEST['id']);
		$status = intval($_REQUEST['status']);
		$order = D("UserAuctionLog")->where("id = $id")->find();
		$order['adm'] = trim($_REQUEST['adm']);
		$order['status'] = $status;
		D("UserAuctionLog")->save($order);
		$this->success (L('EDIT_SUCCESS'));
	}
	
	public function toggleStatus()
	{
		$id = (float)$_REQUEST['id'];
		if($id == 0)
			exit;
		
		$val = intval($_REQUEST['val']) == 0 ? 1 : 0;
		
		$field = trim($_REQUEST['field']);
		if(empty($field) || $field != 'is_pay')
			exit;
		
		$result = array('isErr'=>0,'content'=>'');
		if(false !== D('UserAuctionLog')->where('id = '.$id)->setField($field,$val))
		{
			$this->saveLog(1,$id,$field);
			$result['content'] = $val;
			
			D('UserAuctionLog')->where('id = '.$id)->setField('pay_time',gmtTime());
			$order = D('UserAuctionLog')->where('id = '.$id)->find();
			$msg = L('PAY_'.$val);
			$money = (float)$order['money'];
			if($val == 1)
				$money = -$money;
			vendor('common');
			FS('User')->updateUserMoney($order['uid'],'UserAuctionLog','pay',$msg,$id,$money);
		}
		else
		{
			$this->saveLog(0,$id,$field);
			$result['isErr'] = 1;
		}
		
		die(json_encode($result));
	}
}

function getHandlerStatus($status)
{
	return L("STATUS_".$status);
}

function getIsEdit($id,$status)
{
	if($status == 0)
		return "<a href='javascript:showMoneyLog(".$id.");'>". L('EDIT')."</a>&nbsp;&nbsp;<a href=\"javascript:;\" onclick=\"removeData(this,'$id','id')\">". L('REMOVE')."</a>";
	elseif ($status == 1)
		return "<a href='javascript:showMoneyLog(".$id.");'>". L('VIEW')."</a>";
	else
		return "<a href='javascript:showMoneyLog(".$id.");'>". L('VIEW')."</a>&nbsp;&nbsp;<a href=\"javascript:;\" onclick=\"removeData(this,'$id','id')\">". L('REMOVE')."</a>";
}
?>