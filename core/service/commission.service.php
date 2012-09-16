<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**  
 * commission.service.php
 *
 * 佣金服务
 *
 * @package class
 * @author awfigq <awfigq@qq.com>
 */
class CommissionService
{
	public function runCron($crons)
	{
		FS('Cron')->createRequest(array('m'=>'goods_order','a'=>'taobao'));
			
		$cron = array();
		$cron['server'] = 'commission';
		$cron['run_time'] = getTodayTime() + 86400;
		FDB::insert('cron',$cron);
	}
}
?>