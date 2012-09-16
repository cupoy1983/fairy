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
 会员佣金列表
 +------------------------------------------------------------------------------
 */
class GoodsOrderAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$uname = trim($_REQUEST['uname']);
		$order_id = trim($_REQUEST['order_id']);
		
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND uid = ".$uid;
		}
		
		if (!empty($order_id) && preg_match("/^o\d+$/",$order_id))
		{
			$this->assign("order_id",$order_id);
			$parameter['order_id'] = $order_id;
			$order_id = (float)substr($order_id,1);
			if($order_id > 0)
				$where .= " AND order_id = ".$order_id;
		}
		
		if(!$is_empty)
		{
			$model = M();

			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);

			$sql = 'SELECT COUNT(DISTINCT order_id) AS tcount
				FROM '.C("DB_PREFIX").'goods_order '.$where;

			$count = $model->query($sql);
			$count = $count[0]['tcount'];
			
			if($count > 0)
			{
				$sql = 'SELECT DISTINCT(order_id),status,settlement_time,goods_id,create_time 
					FROM '.C("DB_PREFIX").'goods_order '.$where;
				$this->_sqlList($model,$sql,$count,$parameter,'order_id');
				$list = $this->list;
				
				$orders = array();
				$users = array();
				$goods_ids = array();
				foreach($list as $k=>$v)
				{
					$goods_ids[$v['goods_id']] = '';
					$list[$k]['status'] = L('STATUS_'.$v['status']);
					$list[$k]['settlement_time'] = '&nbsp;';
					if($v['settlement_time'] > 0)
						$list[$k]['settlement_time'] = toDate($v['settlement_time'],'Y-m-d').'<br/>'.toDate($v['settlement_time'],'H:i:s');
					$list[$k]['create_time'] = toDate($v['create_time'],'Y-m-d').'<br/>'.toDate($v['create_time'],'H:i:s');
					$list[$k]['goods_name'] = &$goods_ids[$v['goods_id']];
					$orders[$v['order_id']] = &$list[$k];
				}
				$where = array();
				$where['order_id'] = array('in',array_keys($orders));
				$temps = D('GoodsOrder')->where($where)->order('order_id DESC,type ASC')->select();
				foreach($temps as $temp)
				{
					$users[$temp['uid']] = '';
					$commission = (float)$temp['commission'] > 0 ? (float)$temp['commission'] : L('GET_COMMISSION');
					$pay_time = L('NO_PAY');
					if($temp['pay_time'] > 0)
						$pay_time = L('PAY_TIME_'.$temp['is_pay']).':'.toDate($temp['pay_time']);
					$type = L('TYPE_'.$temp['type']);

					if($temp['type'] == 0)
					{
						$orders[$temp['order_id']]['user']['uid'] = $temp['uid'];
						$orders[$temp['order_id']]['user']['name'] = &$users[$temp['uid']];
						$orders[$temp['order_id']]['user']['commission'] = $commission;
						$orders[$temp['order_id']]['user']['is_commission'] = (float)$temp['commission'] > 0 ? true : false;
						$orders[$temp['order_id']]['user']['is_pay'] = $temp['is_pay'];
						$orders[$temp['order_id']]['user']['commission_rate'] = (float)$temp['commission_rate'].'%';
						$orders[$temp['order_id']]['user']['pay_time'] = $pay_time;
						$orders[$temp['order_id']]['user']['type'] = $type;
					}
					else
					{
						$orders[$temp['order_id']]['cuser']['uid'] = $temp['uid'];
						$orders[$temp['order_id']]['cuser']['name'] = &$users[$temp['uid']];
						$orders[$temp['order_id']]['cuser']['commission'] = $commission;
						$orders[$temp['order_id']]['cuser']['is_commission'] = (float)$temp['commission'] > 0 ? true : false;
						$orders[$temp['order_id']]['cuser']['is_pay'] = $temp['is_pay'];
						$orders[$temp['order_id']]['cuser']['commission_rate'] = (float)$temp['commission_rate'].'%';
						$orders[$temp['order_id']]['cuser']['pay_time'] = $pay_time;
						$orders[$temp['order_id']]['cuser']['type'] = $type;
					}
				}
				
				$where = array();
				$where['id'] = array('in',array_keys($goods_ids));
				$temps = D('Goods')->where($where)->field('id,name,url')->select();
				foreach($temps as $temp)
				{
					$goods_ids[$temp['id']] = '<a href="'.$temp['url'].'" target="_blank">'.$temp['name'].'</a>';
				}

				$where = array();
				$where['uid'] = array('in',array_keys($users));
				$temps = D('User')->where($where)->field('uid,user_name')->select();
				foreach($temps as $temp)
				{
					$users[$temp['uid']] = $temp['user_name'];
				}
			}
		}
		else
			$list = array();

		$this->assign('list',$list);
		$this->display();
		return;
	}

	public function remove() {
		//删除指定记录
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$condition = array ('order_id' => array ('in', explode ( ',', $id ) ) );
			if(false !== D('GoodsOrder')->where ( $condition )->delete ())
			{
				D('GoodsOrderIndex')->where($condition)->delete();
				$this->saveLog(1,$id);
			}
			else
			{
				$this->saveLog(0,$id);
				$result['isErr'] = 1;
				$result['content'] = L('REMOVE_ERROR');
			}
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}
		
		die(json_encode($result));
	}

	public function toggleStatus()
	{
		$id = (float)$_REQUEST['id'];
		$uid = (float)$_REQUEST['uid'];
		if($id == 0 || $uid == 0)
			exit;
		
		$val = intval($_REQUEST['val']) == 0 ? 1 : 0;
		
		$field = trim($_REQUEST['field']);
		if(empty($field))
			exit;
		
		$result = array('isErr'=>0,'content'=>'');
		if(false !== D('GoodsOrder')->where('order_id = '.$id.' AND uid = '.$uid)->setField($field,$val))
		{
			$this->saveLog(1,$id,$field);
			$result['content'] = $val;
			if($field == 'is_pay')
			{
				D('GoodsOrder')->where('order_id = '.$id.' AND uid = '.$uid)->setField('pay_time',gmtTime());
				$order = D('GoodsOrder')->where('order_id = '.$id.' AND uid = '.$uid)->find();
				$msg = L('PAY_'.$val);
				$money = (float)$order['commission'];
				if($val == 0)
					$money = -$money;

				if($order['type'] == 0)
					$action = 'commission';
				else
					$action = 'buy';

				$goods = D('Goods')->where('id = '.$order['goods_id'])->field('id,name,url')->find();
				$msg .= '&nbsp;'.L('GOODS').':'.'<a href="'.$goods['url'].'" target="_blank">'.$goods['name'].'</a>&nbsp;'.L('TYPE_'.$order['type']);
				
				vendor('common');
				FS('User')->updateUserMoney($uid,'GoodsOrder',$action,$msg,$id,$money);
			}
		}
		else
		{
			$this->saveLog(0,$id,$field);
			$result['isErr'] = 1;
		}
		
		die(json_encode($result));
	}
}
?>