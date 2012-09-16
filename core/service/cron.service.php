<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**  
 * cron.service.php
 *
 * 计划任务处理服务
 *
 * @package class
 * @author awfigq <awfigq@qq.com>
 */
class CronService
{
	public function run()
	{
		$crons = array();
		$res = FDB::query("SELECT * FROM ".FDB::table('cron')." WHERE run_time <= '".TIME_UTC."' ORDER BY run_time DESC");
		while($data = FDB::fetch($res))
		{
			$crons[$data['server']][] = $data;
		}
		
		if(count($crons) > 0)
		{
			$query = FDB::query("DELETE FROM ".FDB::table('cron')." WHERE run_time <= '".TIME_UTC."'");
			if($query !== FALSE && FDB::affectedRows() > 0)
			{
				foreach($crons as $cserver => $cron_list)
				{
					if($cserver == 'collect')
						CronService::createRequest(array('m'=>'collect','a'=>'init'));
					else
						FS($cserver)->runCron($cron_list);
				}
			}
		}
	}
	
	public function createRequest($args = array())
	{
		$crlf = '';
		if (strtoupper(substr(PHP_OS, 0, 3) === 'WIN'))
			$crlf = "\r\n";
		elseif (strtoupper(substr(PHP_OS, 0, 3) === 'MAC'))
			$crlf = "\r";
		else
			$crlf = "\n";

		if(function_exists('fsockopen'))
			$fp=fsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,5);
		elseif(function_exists('pfsockopen'))
			$fp=pfsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,5);

		if($fp)
		{
			global $_FANWE;
			$args['request_time'] = TIMESTAMP;
			$args = serialize($args);
			$authkey = md5($_FANWE['config']['security']['authkey']);
			$args = rawurlencode(authcode($args,'ENCODE',$authkey));
			$args = 'args='.$args;
			$request = "POST ".SITE_URL."services/cron.php HTTP/1.0".$crlf;
			$request .= "Host: ".$_SERVER['HTTP_HOST'].$crlf;
			$request .= "Content-Type: application/x-www-form-urlencoded".$crlf;
			$request .= 'Content-Length: '.strlen($args).$crlf;
			$request .= "Connection: Close".$crlf.$crlf;
			$request .= $args;

			if(!@fwrite($fp,$request))
				return false;

			fclose($fp);
			return true;
		}
		else
			return false;
	}
}
?>