<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**  
 * delay.service.php
 *
 * 延时处理服务
 *
 * @package class
 * @author awfigq <awfigq@qq.com>
 */
//FIXME remove this service 
class DelayService
{
	public function get($key)
	{
		$result = array('status'=>-1,'data'=>'');
		if(!file_exists(PUBLIC_ROOT.'./data/caches/custom/'.$key.'.cache.php'))
			return $result;
		else
		{
			include(PUBLIC_ROOT.'./data/caches/custom/'.$key.'.cache.php');
			$list = explode('/',$key);
			$key = end($list);
			$cache_data = $data[$key];
			$result['data'] = $cache_data['data'];

			if((int)$cache_data['expired_time'] > 0 && TIMESTAMP > (int)$cache_data['expired_time'])
			{
				$result['status'] = 0;
				return $result;
			}
			
			$time_clear = 0;
			$clear_path = FANWE_ROOT.'./public/data/is_clear.lock';
			if(file_exists($clear_path))
				$time_clear = (int)@file_get_contents($clear_path);

			if(($time_clear > 0 && $cache_data['time'] < $time_clear))
			{
				$result['status'] = 0;
				return $result;
			}
			
			$result['status'] = 1;
			return $result;
		}
	}

	public function create($args = array())
	{
		$crlf = '';
		if (strtoupper(substr(PHP_OS, 0, 3) === 'WIN'))
			$crlf = "\r\n";
		elseif (strtoupper(substr(PHP_OS, 0, 3) === 'MAC'))
			$crlf = "\r";
		else
			$crlf = "\n";

		if(function_exists('fsockopen'))
			$fp=fsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,1);
		elseif(function_exists('pfsockopen'))
			$fp=pfsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,1);

		if($fp)
		{
			global $_FANWE;
			$args['request_time'] = TIMESTAMP;
			$args = serialize($args);
			$authkey = md5($_FANWE['config']['security']['authkey']);
			$args = rawurlencode(authcode($args,'ENCODE',$authkey));
			$args = 'args='.$args;
			$request = "POST ".SITE_URL."services/delay.php HTTP/1.0".$crlf;
			$request .= "Host: ".$_SERVER['HTTP_HOST'].$crlf;
			$request .= "Content-Type: application/x-www-form-urlencoded".$crlf;
			$request .= 'Content-Length: '.strlen($args).$crlf;
			$request .= "Connection: Close".$crlf.$crlf;
			$request .= $args;
			var_dump($fp);
			var_dump($request);
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