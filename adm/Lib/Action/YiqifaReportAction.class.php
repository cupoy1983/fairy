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
 亿起发报表
 +------------------------------------------------------------------------------
 */
class YiqifaReportAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$outer_code = trim($_REQUEST['outer_code']);
		$day_time_str = trim($_REQUEST['day_time']);
		$status = trim($_REQUEST['status']);
		$day_time = !empty($day_time_str) ? strZTime($day_time_str) : 0;

		if(!empty($outer_code))
		{
			$this->assign("outer_code",$outer_code);
			$parameter['outer_code'] = $outer_code;
            $where .= " AND feed_back = '$outer_code'";
		}
		
		if ($day_time > 0)
		{
			$this->assign("day_time",$day_time_str);
			$parameter['day_time'] = $day_time_str;
			$where .= " AND order_day = '".$day_time."'";
		}
		
		if(!empty($status))
		{
			$this->assign("status",$status);
			$parameter['status'] = $status;
            $where .= " AND status = '$status'";
		}
	
		$model = M();

		if(!empty($where))
			$where = str_replace('WHERE AND','WHERE','WHERE'.$where);

		$sql = 'SELECT COUNT(unique_id) AS tcount FROM '.C("DB_PREFIX").'yiqifa_report '.$where;
		$count = $model->query($sql);
		$count = $count[0]['tcount'];

		$sql = 'SELECT unique_id,prod_name,prod_count,prod_money,am,commision,status,feed_back,order_time,order_no FROM '.C("DB_PREFIX").'yiqifa_report '.$where;
		$this->_sqlList($model,$sql,$count,$parameter,'unique_id');
		$this->display();
		return;
	}
}
?>