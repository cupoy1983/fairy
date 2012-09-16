<?php
function m_getMConfig(){
	
		global $_FANWE;
		
		//FanweService::instance()->cache->loadCache("m_config");
		$m_config = $_FANWE['cache']['m_config'];		
		if($m_config==false) //测试时，不取缓存
		{
			//init_config_data();//检查初始化数据	
			$m_config = array();			
			$list = FDB::fetchAll("select code,val from ".FDB::table("m_config"));
			foreach($list as $item){
				$m_config[$item['code']] = $item['val'];
			}	
			//新闻公告
			$sql = "select code as title, title as content from ".FDB::table("m_config_list")." where `group` = 4 and is_verify = 1 order by id desc";
			$list = FDB::fetchAll($sql);
			$newslist = array();
			foreach($list as $item){
				
				$newslist[] = array("title"=>$item['title'],"content"=>str_replace("/public/upload/images/",$_FANWE['site_url']."public/upload/images/",$item['content']));
			}
			$m_config['newslist'] = $newslist;
			
	
			//print_r($addrtlist);exit;
			FanweService::instance()->cache->saveCache("m_config",$m_config);			
		}		
		//print_r($m_config);
		return $m_config;
	}
		
function m_emptyTag($string)
{
		if(empty($string))
		return "";
			
		$string = strip_tags(trim($string));
		$string = preg_replace("|&.+?;|",'',$string);
	
		return $string;
}
	
function m_convertUrl($url)
{
		$url = str_replace("&","&amp;",$url);
		return $url;
}
	
function m_display($root)
{
	global $_FANWE;
	if($_FANWE['uid'] > 0)
	{
		$root['user'] = $_FANWE['user'];
		$root['user_notice'] = $_FANWE['user_notice'];
	}
	else
		$root['user'] = 0;
	header("Content-Type:text/html; charset=utf-8");
	$r_type = intval($_REQUEST['r_type']);//返回数据格式类型; 0:base64;1;json_encode;2:array
	if ($r_type == 0){
		echo base64_encode(json_encode($root));
	}else if ($r_type == 1){
		print_r(json_encode($root));
	}else if ($r_type == 2){
		print_r($root);
	};
	exit;
}

function m_express(&$obj,$content = '')
{
	global $_FANWE;
	$obj['parse_expres'] = array();
	$express = getCache('m_emotion_express_cache'); //缓存过的表情hash
	if($express === NULL)
	{
		$express = array();
		$res = FDB::query("select `emotion`,concat('".$_FANWE['site_url']."public/expression/',`type`,'/',`filename`) as fname from ".FDB::table('expression'));
		while($data = FDB::fetch($res))
		{
			$express[$data['emotion']] = $data['fname'];
		}
		setCache('m_emotion_express_cache',$express);
	}

	preg_match_all("/(\[[^\f\n\r\t\v\[\] ]{2,20}?\])/",$content,$exps);
	if(!empty($exps[1]))
	{
		$exps = array_unique($exps[1]);
		foreach($exps as $exp)
		{
			if(!empty($exp) && isset($express[$exp]))
			{
				//$obj['parse_expres'][$exp] = $express[$exp];
				$obj['parse_expres'][] = array('key'=> $exp, 'value'=> $express[$exp],'width'=>24,'height'=>24);
			}
		}
	}

	$obj['parse_user'] = array();
	preg_match_all("/@([^\f\n\r\t\v@<> ]{2,20}?)(?:\:| )/",$content,$users);
	if(!empty($users[1]))
	{
		$patterns = array();
		$replace = array();
		$users = array_unique($users[1]);
		$arr = array();
		foreach($users as $user)
		{
			if(!empty($user))
			{
				$arr[] = addslashes($user);
			}
		}

		$res = FDB::query('SELECT uid,user_name
			FROM '.FDB::table('user').'
			WHERE user_name '.FDB::createIN($arr));
		while($data = FDB::fetch($res))
		{
			//$obj['parse_user'][$data['user_name']] = $data['uid'];
			$obj['parse_user'][] = array('key'=> $data['user_name'], 'value'=> $data['uid']);
		}
	}
	
	$obj['parse_events'] = array();
	preg_match_all("/#([^\f\n\r\t\v]{1,80}?)#/",$content,$events);
	if(!empty($events[1]))
	{
		$patterns = array();
		$replace = array();
		$events = array_unique($events[1]);
		$arr = array();
		foreach($events as $event)
		{
			if(!empty($event))
			{
				$arr[] = addslashes($event);
			}
		}

		$res = FDB::query('SELECT id,title
			FROM '.FDB::table('event').'
			WHERE title '.FDB::createIN($arr));
		while($data = FDB::fetch($res))
		{
			//$obj['parse_events'][$data['title']] = $data['id'];
			$obj['parse_events'][] = array('key'=> $data['title'], 'value'=> $data['id']);
		}
	}
}

function m_youhuiItem($item){
	global $_FANWE;
	
	$is_sc = intval($item['is_sc']);
	if ($is_sc > 0) $is_sc = 1;//1:已收藏; 0:未收藏 
	
	if (intval($item['begin_time']) > 0 && intval($item['end_time'])){
		$days = round(($item['end_time']-$item['begin_time'])/3600/24);
		if ($days < 0){
			$ycq = fToDate($item['begin_time'],'Y-m-d').'至'.fToDate($item['end_time'],'Y-m-d').',已过期';
		}else{
			$ycq = fToDate($item['begin_time'],'Y-m-d').'至'.fToDate($item['end_time'],'Y-m-d').',还有'.$days.'天';
		}		
	}else{
		$ycq = '';
	}
	
	return array("id"=>$item['id'],
									"title"=>$item['title'],
									"logo"=> $_FANWE['site_url'].$item['image_1'],
									"logo_1"	=>	$_FANWE['site_url'].$item['image_2'],
									"logo_2"	=>	$_FANWE['site_url'].$item['image_3'],
											"merchant_logo"=> $_FANWE['site_url'].$item['merchant_logo'],
											"create_time"=>$item['create_time'],
											"create_time_format"=>getBeforeTimelag($item['create_time']),
											"xpoint"=>$item['merchant_xpoint'],
											"ypoint"=>$item['merchant_ypoint'],
											"address"=>$item['merchant_api_address'],
											"content"=>$item['content'],
									"is_sc"=>$is_sc,
									"comment_count"=>intval($item['comment_count']),
									"merchant_id"=>intval($item['merchant_id']),
									"begin_time_format"=>fToDate($item['begin_time'],'Y-m-d'),
									"end_time_format"=>fToDate($item['end_time'],'Y-m-d'),
									"ycq"=>$ycq,
									"url"=>$item['url'],
									"city_name"=>$item['city_name']
	
	);
}

/**
 * 分享列表详细数据
 * @param array $list 分享列表
 * @param bool $is_parent 是否获取转发信息
 * @param bool $is_collect 是否获取喜欢的会员
 * @param bool $is_parent 是否获取分享标签
 * @return array
 */
function mGetShareDetailList($list,$is_parent = false,$img_width = 160,$img_height=160)
{
	global $_FANWE;
	$shares = array();
	$share_ids = array();
	$rec_shares_ids = array();
	$share_users = array();
	
	foreach($list as $item)
	{
		$share_id = $item['share_id'];
		$share_ids[] = $share_id;
		$item['cache_data'] = fStripslashes(unserialize($item['cache_data']));
		$item['time'] = getBeforeTimelag($item['create_time']);
		$item['url'] = FU('note/index',array('sid'=>$share_id),true);
		if($item['source'] == 'web')
			$item['source'] = $_FANWE['setting']['site_name'].'网站';
		$item['imgs'] = array();
		if(!empty($item['cache_data']['imgs']))
		{
			foreach($item['cache_data']['imgs']['all'] as $img)
			{
				if($img['type'] == 'g')
				{
					$img['goods_url'] = $img['url'];
					$img['price_format'] = priceFormat($img['price']);
				}
				else
				{
					$img['name'] = '';
					$img['price'] = '';
					$img['goods_url'] = '';
					$img['taoke_url'] = '';
					$img['price_format'] = '';
				}
				unset($img['url']);
				$img['small_img'] = getImgName($img['img'],$img_width,$img_height,0,true);
				$img['img'] = FS("Image")->getImageUrl($img['img'],2);
				if($img['img_width'] > $img_width)
					$img['width'] = 160;
				else
					$img['width'] = $img['img_width'];

				$item['imgs'][] = $img;
			}
		}
		m_express($item,$item['content']);
		$shares[$share_id] = $item;
		unset($shares[$share_id]['cache_data']);
		
		$shares[$share_id]['user'] = &$share_users[$item['uid']];

		$shares[$share_id]['is_relay'] = false;
		$shares[$share_id]['is_parent'] = false;
		
		if($is_parent)
		{
			if($item['base_id'] > 0)
			{
				$shares[$share_id]['is_relay'] = true;
				$rec_shares_ids[$item['base_id']] = false;
				$shares[$share_id]['relay_share'] = &$rec_shares_ids[$item['base_id']];
			}
			elseif($item['parent_id'] > 0 && $item['parent_id'] != $item['base_id'])
			{
				$shares[$share_id]['is_parent'] = true;
				$rec_shares_ids[$item['parent_id']] = false;
				$shares[$share_id]['parent_share'] = &$rec_shares_ids[$item['parent_id']];
			}
		}
	}
	
	$rec_ids = array_keys($rec_shares_ids);
	if(count($rec_ids) > 0)
	{
		$intersects = array_intersect($share_ids,$rec_ids);
		$temp_ids = array();
		foreach($intersects as $share_id)
		{
			$rec_shares_ids[$share_id] = $shares[$share_id];
			$temp_ids[] = $share_id;
		}
		
		$diffs = array_diff($rec_ids,$temp_ids);
		if(count($diffs) > 0)
		{
			$res = FDB::query('SELECT * FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',$diffs).')');
			while($item = FDB::fetch($res))
			{
				$share_id = $item['share_id'];
				$share_ids[] = $share_id;
				$item['cache_data'] = fStripslashes(unserialize($item['cache_data']));
				$item['time'] = getBeforeTimelag($item['create_time']);
				$item['url'] = FU('note/index',array('sid'=>$share_id),true);
				if($item['source'] == 'web')
					$item['source'] = $_FANWE['setting']['site_name'].'网站';
				m_express($item,$item['content']);
				if(!empty($item['cache_data']['imgs']))
				{
					foreach($item['cache_data']['imgs']['all'] as $img)
					{
						if($img['type'] == 'g')
						{
							$img['goods_url'] = $img['url'];
							$img['price_format'] = priceFormat($img['price']);
						}
						else
						{
							$img['name'] = '';
							$img['price'] = '';
							$img['goods_url'] = '';
							$img['taoke_url'] = '';
							$img['price_format'] = '';
						}
						unset($img['url']);
						$img['small_img'] = getImgName($img['img'],$img_width,$img_height,0,true);
						$img['img'] = FS("Image")->getImageUrl($img['img'],2);
						if($img['img_width'] > $img_width)
							$img['width'] = 160;
						else
							$img['width'] = $img['img_width'];

						$item['imgs'][] = $img;
					}
				}
				$rec_shares_ids[$share_id] = $item;
				unset($rec_shares_ids[$share_id]['cache_data']);
				$rec_shares_ids[$share_id]['user'] = &$share_users[$item['uid']];
			}
		}
	}
	
	$user_ids = array_keys($share_users);
	if(count($user_ids) > 0)
	{
		$res = FDB::query("SELECT uid,user_name,avatar FROM ".FDB::table('user').' WHERE uid IN ('.implode(',',$user_ids).')');
		while($item = FDB::fetch($res))
		{
			$item['user_avatar'] = avatar($item['avatar'],'m',true);
			$share_users[$item['uid']] = $item;
		}
	}
	
	return $shares;
}
?>