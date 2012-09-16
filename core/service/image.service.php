<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**
 * image.service.php
 *
 * 图片服务类
 *
 * @package service
 * @author awfigq <awfigq@qq.com>
 */

class ImageService
{
	public function getIsServer()
	{
		static $is_server = NULL;
		if($is_server === NULL)
		{
			global $_FANWE;
			if(!isset($_FANWE['cache']['image_servers']))
				FanweService::instance()->cache->loadCache('image_servers');
			
			if(count($_FANWE['cache']['image_servers']['active']) == 0)
				$is_server = false;
			else
				$is_server = true;
		}
		return $is_server;
	}

	public function formatServer($server,$type = 'EN')
	{
		if(empty($server))
			return false;

		if($type == 'DE')
			return unserialize(authcode(base64_decode($server),'DECODE'));
		else
			return base64_encode(authcode(serialize($server),'ENCODE'));
	}

	public function getImageArgs(&$args,$type = 'share')
	{
		global $_FANWE;
		$args['waters'] = false;

		switch($type)
		{
			case 'share':
				if(!isset($_FANWE['cache']['image_sizes']))
					FanweService::instance()->cache->loadCache('image_sizes');

				$args['sizes'] = $_FANWE['cache']['image_sizes'];
				$args['waters'] = false;
				$water_image = $_FANWE['setting']['water_image'];
				if(!empty($water_image) && file_exists(FANWE_ROOT.$water_image))
				{
					$args['waters'] = array();
					$args['waters']['image'] = $_FANWE['site_url'].$water_image;
					$args['waters']['mark'] = (int)$_FANWE['setting']['water_mark'];
					$args['waters']['alpha'] = (int)$_FANWE['setting']['water_alpha'];
					$args['waters']['position'] = (int)$_FANWE['setting']['water_position'];
				}
			break;

			case 'avatar':
				$args['sizes'] = array(
					array(32,32,1,0),
					array(64,64,1,0),
					array(160,160,1,0)
				);
				
				$args['types'] = array(
					's'=>32,
					'm'=>64,
					'b'=>180,
				);
			break;

			default:
				$args['sizes'] = false;
			break;
		}
	}

	public function getServerUrl($url)
	{
		global $_FANWE;
		if(!isset($_FANWE['cache']['image_servers']))
			FanweService::instance()->cache->loadCache('image_servers');

		foreach($_FANWE['cache']['image_servers']['all'] as $server)
		{
			if(strpos($url,'./'.$server['code'].'/') !== FALSE)
			{
				return $server['url'];
			}
		}
		return false;
	}

	public function getImageUrl($url,$is_path = 0)
	{
		if(empty($url))
			return '';

		global $_FANWE;
		static $patterns = NULL,$replace = NULL;
			
		if(ImageService::getIsServer() && $patternss === NULL)
		{
			if(!isset($_FANWE['cache']['image_servers']))
				FanweService::instance()->cache->loadCache('image_servers');
			foreach($_FANWE['cache']['image_servers']['all'] as $server)
			{
				$patterns[] = './'.$server['code'].'/';
				$replace[] = $server['url'];
			}
		}

		if(isset($_FANWE['UPYUN_SETTING']))
		{
			$patterns[] = './upyun/';
			$replace[] = $_FANWE['UPYUN_SETTING']['url'].'/';
		}

		if(strpos($url,'./public/') === FALSE)
			$url = str_replace($patterns,$replace,$url);
		elseif($is_path == 1)
			$url = str_replace('/./','/',FANWE_ROOT.$url);
		elseif($is_path == 2)
			$url = str_replace('/./','/',$_FANWE['site_url'].$url);
		elseif($is_path == 3)
			$url = str_replace(array('/./',FANWE_ROOT),array('/',$_FANWE['site_url']),$url);
		return $url;
	}

	public function getServer($code = '')
	{
		global $_FANWE;
		if(!isset($_FANWE['cache']['image_servers']))
			FanweService::instance()->cache->loadCache('image_servers');
		
		if(count($_FANWE['cache']['image_servers']['all']) == 0)
			return false;	
		
		if(!empty($code))
			return $_FANWE['cache']['image_servers']['all'][$code];

		if(count($_FANWE['cache']['image_servers']['active']) > 1)
		{
			$server_code = FDB::resultFirst('SELECT code FROM '.FDB::table('image_servers').' WHERE status = 1 ORDER BY upload_count ASC');
			if($server_code)
				return $_FANWE['cache']['image_servers']['active'][$server_code];
			else
				return false;
		}
		else
		{
			return current($_FANWE['cache']['image_servers']['active']);
		}
		return false;
	}

	public function setServerUploadCount($code)
	{
		FDB::query('UPDATE '.FDB::table('image_servers').' SET upload_count = upload_count + 1 WHERE code = \''.$code."'");
	}
	
	public function getImageUrlToken($args = array(),$server = '',$is_system = 0)
	{
		global $_FANWE;
		if($_FANWE['uid'] > 0 || $is_system)
		{
			if(empty($server))
				$server = ImageService::getServer();
				
			if(empty($server))
				return false;
			
			$token = array(
				'code' => $server['code'],
				'uid' => $_FANWE['uid'],
				'max_upload'=>(int)$_FANWE['setting']['max_upload'],
				'saltkey' => $_FANWE['cookie']['saltkey'],
				'system' => $is_system,
				'ip' => $_FANWE['client_ip'],
				'time' => TIME_UTC,
				'args' => $args
			);
			
			$token = serialize($token);
			$authkey = md5($_FANWE['config']['security']['authkey'].$server['code']);
			$result = array();
			$result['code'] = $server['code'];
			$result['url'] = $server['url'];
			$result['host'] = $server['host'];
			$result['host_port'] = $server['host_port'];
			$result['port'] = $server['port'];
			$result['path'] = $server['path'];
			$result['token'] = rawurlencode(authcode($token,'ENCODE',$authkey));
			$result['image_server'] = ImageService::formatServer($server);
			return $result;
		}
		else
			return false;
	}
	
	public function sendRequest($server,$type,$is_sync = false)
	{
		if(empty($server))
			return false;
			
		$crlf = '';
        if (strtoupper(substr(PHP_OS, 0, 3) === 'WIN'))
            $crlf = "\r\n";
        elseif (strtoupper(substr(PHP_OS, 0, 3) === 'MAC'))
            $crlf = "\r";
        else
            $crlf = "\n";
			
		$params = "token=".rawurlencode($server['token']);
		$timeout = 5;
		if($is_sync)
			$timeout = 60;
		
		if(function_exists("fsockopen"))
			$fp=fsockopen($server['host'],$server['port'],$errno,$errstr,$timeout);
		else
			$fp=pfsockopen($server['host'],$server['port'],$errno,$errstr,$timeout);
			
		if($fp)
		{
			$request = "POST ".$server['path'].$type.".php HTTP/1.0".$crlf;
			$request .= "Host: ".$server['host_port'].$crlf;
			$request .= "Content-Type: application/x-www-form-urlencoded".$crlf;
			$request .= 'Content-Length: '.strlen($params).$crlf;
			$request .= "Connection: Close".$crlf.$crlf;

			$request .= $params;

			if(!@fwrite($fp,$request))
				return false;
			
			$http_response = '';
			while(!feof($fp))
			{
				if(!$is_sync)
				{
					fgets($fp,128);
					break;
				}
				else
				{
					$http_response .= fgets($fp);
				}
			}
			fclose($fp);
			
			if($is_sync)
			{
				$separator = '/\r\n\r\n|\n\n|\r\r/';
				list($http_header,$http_body) = preg_split($separator,$http_response,2);
				return $http_body;
			}

			return true;
		}
		else
			return false;
	}
	/**
	 * 保存图片
	 * @param array $key $_FILES 中的键名 为空则保存 $_FILES 中的所有图片
	 * @param string $dir 保存的目录 为空则保存到临时目录
	 * @param bool $is_thumb 是否缩略图片
	 * @param array $whs 缩略图大小信息 为空则取后台设置,并返回 大图键名big 小图键名small
	 	可生成多个缩略图
		数组 参数1 为宽度，
			 参数2为高度，
			 参数3为处理方式:0(缩放,默认)，1(剪裁)，
			 参数4为是否水印 默认为 0(不生成水印)
	 	array(
			'thumb1'=>array(300,300,0,0),
			'thumb2'=>array(100,100,0,0),
			...
		)，
	 * @param bool $is_delete_origin 是否删除原图(当有缩略图时，此设置才生效)
	 * @param bool $is_water 是否水印
	 * @return array
	 	如果只有一个图片，则返回
		array(
			'name'=>图片名称，
			'url'=>原图web路径，
			'path'=>原图物理路径，
			有略图时
			'thumb'=>array(
				'thumb1'=>array('url'=>web路径,'path'=>物理路径),
				'thumb2'=>array('url'=>web路径,'path'=>物理路径),
				...
			)
		)
		如果有多个图片，则返回(key 为 $_FILES 中的键名)
		array(
			'key'=>array(
				'name'=>图片名称，
				'url'=>原图web路径，
				'path'=>原图物理路径，
				有略图时
				'thumb'=>array(
					'thumb1'=>array('url'=>web路径,'path'=>物理路径),
					'thumb2'=>array('url'=>web路径,'path'=>物理路径),
					...
				)
			)
			....
		)
	 */
	public function save($key='',$dir='temp',$is_thumb=false,$whs=array(),$is_delete_origin = false,$is_water = false)
	{
		global $_FANWE;
		include_once fimport('class/image');
		$image = new Image();
		if(intval($_FANWE['setting']['max_upload']) > 0)
			$image->max_size = intval($_FANWE['setting']['max_upload']);

		$list = array();

		if(empty($key))
		{
			foreach($_FILES as $fkey=>$file)
			{
				$list[$fkey] = false;
				$image->init($file,$dir);
				if($image->save())
				{
					$list[$fkey] = array();
					$list[$fkey]['url'] = $image->file['target'];
					$list[$fkey]['path'] = $image->file['local_target'];
					$list[$fkey]['name'] = $image->file['prefix'];
				}
			}
		}
		else
		{
			$list[$key] = false;
			$image->init($_FILES[$key],$dir);
			if($image->save())
			{
				$list[$key] = array();
				$list[$key]['url'] = $image->file['target'];
				$list[$key]['path'] = $image->file['local_target'];
				$list[$key]['name'] = $image->file['prefix'];
			}
		}

		$water_image = FANWE_ROOT . $_FANWE['setting']['water_image'];
		$water_mark = intval($_FANWE['setting']['water_mark']);
		$alpha = intval($_FANWE['setting']['water_alpha']);
		$place = intval($_FANWE['setting']['water_position']);

		if($is_thumb)
		{
			if(empty($whs))
			{
				$big_width = intval($_FANWE['setting']['big_width']);
				$big_height = intval($_FANWE['setting']['big_height']);
				$small_width = intval($_FANWE['setting']['small_width']);
				$small_height = intval($_FANWE['setting']['small_height']);
				$thumb_type = intval($_FANWE['setting']['auto_gen_image']);

				$whs = array(
					'big'=>array($big_width,$big_height,$thumb_type,$water_mark),
					'small'=>array($small_width,$small_height,1,0),
				);
			}
		}

		foreach($list as $lkey=>$item)
		{
			if($is_thumb)
			{
				foreach($whs as $tkey=>$wh)
				{
					$list[$lkey]['thumb'][$tkey]['url'] = false;
					$list[$lkey]['thumb'][$tkey]['path'] = false;

					if($wh[0] > 0 || $wh[1] > 0)
					{
						$thumb_bln = false;
						$thumb_type = isset($wh[2]) ? intval($wh[2]) : 0;
						if($thumb = $image->thumb($item['path'],$wh[0],$wh[1],$thumb_type))
						{
							$thumb_bln = true;
							$list[$lkey]['thumb'][$tkey]['url'] = $thumb['url'];
							$list[$lkey]['thumb'][$tkey]['path'] = $thumb['path'];
							if(isset($wh[3]) && intval($wh[3]) > 0)
								$image->water($list[$lkey]['thumb'][$tkey]['path'],$water_image,$alpha, $place);
						}
					}
				}

				if($is_delete_origin && $thumb_bln)
				{
					@unlink($item['path']);
					$list[$lkey]['url'] = false;
					$list[$lkey]['path'] = false;
				}
			}

			if($is_water)
			{
				$image->water($item['path'],$water_image,$alpha, $place);
			}
		}
		
		if($key != '')
			return $list[$key];
		else
			return $list;
	}
	
	/**
	 * 添加图片
	 */
	public function addImage($image)
	{
		FDB::query('INSERT INTO '.FDB::table('images_index').'(id) VALUES(NULL)');
		$id = FDB::insertId();
		$table = ImageService::getTablaName($id,false);

		$server_img = ImageService::sendImageToServer($image,$id);
		if($server_img)
		{
			$image['src'] = $server_img['url'];
			$image['id'] = $id;
			$image['width'] = $server_img['width'];
			$image['height'] = $server_img['height'];
			$image['server_code'] = $server_img['server_code'];
			if(FDB::insert($table,$image,false,false,true))
			{
				$image['id'] = $id;
				$image['path'] = ImageService::getImageUrl($server_img['url'],1);
				return $image;
			}
			else
				ImageService::deleteServerImg($image);
		}

		FDB::query('DELETE FROM '.FDB::table('images_index').' WHERE id = '.$id);
		return false;
	}
	
	//将图片发送到图片服务器
	public function sendImageToServer($image,$id)
	{
		setTimeLimit(600);
		global $_FANWE;
		$server_img = false;
		$server_args = array();
		ImageService::getImageArgs($server_args,$image['type']);

		if(!empty($image['server_code']))
		{
			$server_args['id'] = $id;
			$server_args['img_path'] = $image['src'];
			$server = ImageService::getServer($image['server_code']);
			$server = ImageService::getImageUrlToken($server_args,$server,1);
			$body = ImageService::sendRequest($server,'saveimg',true);
			if(!empty($body))
			{
				$server_img = unserialize($body);
				ImageService::setServerUploadCount($server_img['server_code']);
				$server_img['url'] = str_replace('./','./'.$server_img['server_code'].'/',$server_img['url']);
			}
		}
		elseif(IS_UPYUN)
		{
			include_once fimport('class/upyun');
			$dir = '/photos/'.getDirsById($id);
			$file_name = md5(microtime(true)).random('6').'.jpg';
			$upyun = new UpYun($_FANWE['UPYUN_SETTING']['space_name'],$_FANWE['UPYUN_SETTING']['user'],$_FANWE['UPYUN_SETTING']['password']);
			$upload = $upyun->writeFile($dir.'/'.$file_name,file_get_contents($image['src']),true) ;
			if($upload)
			{
				$server_img = array(
					'path' => $_FANWE['UPYUN_SETTING']['url'].$dir."/".$file_name,
					'url' => "./upyun".$dir."/".$file_name,
					'width' => $upyun->getWritedFileInfo('x-upyun-width'),
					'height' => $upyun->getWritedFileInfo('x-upyun-height'),
					'server_code'=>'upyun',
				);
			}
		}
		elseif(ImageService::getIsServer())
		{
			if(!empty($image['old_server_code']))
				$server = ImageService::getServer($image['old_server_code']);
			else
				$server = ImageService::getServer();
			
			$server_args['img_path'] = $image['src'];
			if(!parseUrl($image['src']))
				$server_args['img_path'] = ImageService::getImageUrl($image['src'],3);
				
			$server_args['id'] = $id;

			$server = ImageService::getImageUrlToken($server_args,$server,1);
			$body = ImageService::sendRequest($server,'saveimg',true);
			
			if(!empty($body))
			{
				$server_img = unserialize($body);
				ImageService::setServerUploadCount($server_img['server_code']);
				$server_img['url'] = str_replace('./','./'.$server_img['server_code'].'/',$server_img['url']);
			}
		}
		else
		{
			$server_img = copyImage($image['src'],$server_args['sizes'],'photos',true,$id);
			$server_img['server_code'] = '';
		}

		return $server_img;
	}

	/**
	 * 更新图片
	   如果需要更新图片，可设置 $is_update_img = true
	 */
	public function updateImage($image,$is_update_img = false)
	{
		$id = (int)$image['id'];
		if($id == 0)
			return false;
		
		if($is_update_img)
		{
			$old_img = ImageService::getImageById($id);
			ImageService::deleteServerImg($old_img);
			if(!empty($old_img['server_code']))
				$image['old_server_code'] = $old_img['server_code'];

			$server_img = ImageService::sendImageToServer($image,$id);

			if(isset($image['old_server_code']))
				unset($image['old_server_code']);

			if($server_img)
			{
				$image['src'] = $server_img['url'];
				$image['width'] = $server_img['width'];
				$image['height'] = $server_img['height'];
				$image['server_code'] = $server_img['server_code'];
			}
			else
				return false;
		}

		$table = ImageService::getTablaName($id,false);
		FDB::update($table,$image,'id = '.$id);
		return true;
	}
	
	/**
	 * 更新图片引用
	 */
	public function updateImageRel($id,$count = 1)
	{
		$img = ImageService::getImageById($id);
		if(!$img)
			return -1;
		
		$rel_count = $img['rel_count'] + $count;
		$table = ImageService::getTablaName($id);
		
		if($rel_count < 1)
		{
			ImageService::deleteServerImg($img);
			FDB::query('DELETE FROM '.$table.' WHERE id = '.$id);
			FDB::query('DELETE FROM '.FDB::table('images_index').' WHERE id = '.$id);
			return 0;
		}
		else
			FDB::query('UPDATE '.$table.' SET rel_count = '.$rel_count.' WHERE id = '.$id);
		
		return 1;
	}
	
	/**
	 * 获取图片
	 */
	public function getImageById($id)
	{
		$id = (int)$id;
		if($id == 0)
			return false;
		
		$table = ImageService::getTablaName($id);
		return FDB::fetchFirst('SELECT id,type,src,width,height,server_code,rel_count FROM '.$table.' WHERE id = '.$id);
	}

	/**
	 * 获取图片列表
	 * @param array $images 图片编号索引数组
		array(
			'123' => false,
			'234' => false,
		)
	 */
	public function getImageList(&$images)
	{
		$image_tables = array();
		foreach($images as $id => $val)
		{
			$id = (int)$id;
			if($id > 0)
			{
				$table = ImageService::getTablaName($id);
				$image_tables[$table][] = $id;
			}
		}

		foreach($image_tables as $table => $ids)
		{
			$res = FDB::query('SELECT id,type,src,width,height,server_code FROM '.$table.' WHERE id IN ('.implode(',',$ids).')');
			while($data = FDB::fetch($res))
			{
				$images[$data['id']] = $data;
			}
		}
	}

	/**
	 * 获取图片列表
	 * @param array $ids 图片编号数组
	 */
	public function getImageListByIds($ids)
	{
		$list = array();
		$image_tables = array();
		static $image_list = array();
		foreach($ids as $id)
		{
			$id = (int)$id;
			if($id > 0)
			{
				if(isset($image_list[$id]))
					$list[$id] = $image_list[$id];
				else
				{
					$table = ImageService::getTablaName($id);
					$image_tables[$table][] = $id;
				}
			}
		}

		foreach($image_tables as $table => $ids)
		{
			$res = FDB::query('SELECT id,type,src,width,height,server_code FROM '.$table.' WHERE id IN ('.implode(',',$ids).')');
			while($data = FDB::fetch($res))
			{
				$list[$data['id']] = $data;
				$image_list[$data['id']] = $data;
			}
		}
		return $list;
	}
	
	/**
	 * 获取图片
	 */
	public function formatById($id)
	{
		$id = (int)$id;
		if($id == 0)
			return '';
		
		$img = '';
		$list[$id][] = &$img;
		ImageService::formatByIdKeys($list);
		return $img;
	}

	/**
	 * 获取图片列表
	 * @param array $list 图片编号索引数组
	 */
	public function formatByIdKeys(&$list,$is_return_src = false)
	{
		static $image_list = array();
		$image_tables = array();
		foreach($list as $id => $val)
		{
			$id = (int)$id;
			if($id > 0)
			{
				if(isset($image_list[$id]))
				{
					foreach($list[$id] as $key => $val)
					{
						if($is_return_src)
							$list[$id][$key] = $image_list[$id]['src'];
						else
						{
							$list[$id][$key]['img_id'] = $image_list[$id]['id'];
							$list[$id][$key]['img'] = $image_list[$id]['src'];
							$list[$id][$key]['img_width'] = $image_list[$id]['width'];
							$list[$id][$key]['img_height'] = $image_list[$id]['height'];
							$list[$id][$key]['server_code'] = $image_list[$id]['server_code'];
						}
					}
				}
				else
				{
					$table = ImageService::getTablaName($id);
					$image_tables[$table][] = $id;
				}
			}
		}

		foreach($image_tables as $table => $ids)
		{
			$res = FDB::query('SELECT id,src,width,height,server_code FROM '.$table.' WHERE id IN ('.implode(',',$ids).')');
			while($data = FDB::fetch($res))
			{
				$image_list[$data['id']] = $data;
				foreach($list[$data['id']] as $key => $val)
				{
					if($is_return_src)
						$list[$data['id']][$key] = $data['src'];
					else
					{
						$list[$data['id']][$key]['img_id'] = $data['id'];
						$list[$data['id']][$key]['img'] = $data['src'];
						$list[$data['id']][$key]['img_width'] = $data['width'];
						$list[$data['id']][$key]['img_height'] = $data['height'];
						$list[$data['id']][$key]['server_code'] = $data['server_code'];
					}
				}
			}
		}
	}
	
	//删除图片
	public function deleteImages($ids)
	{
		setTimeLimit(600);
		if(!is_array($ids))
		{
			$ids = array($ids);
		}
		
		$id_list = array();
		$image_tables = array();
		foreach($ids as $id)
		{
			$id = (int)$id;
			if($id > 0)
			{
				$id_list[] = $id;
				$table = ImageService::getTablaName($id);
				$image_tables[$table][] = $id;
			}
		}
		$id_list = array_unique($id_list);
		
		if(count($id_list) > 0)
		{
			foreach($image_tables as $table => $ids)
			{
				$res = FDB::query('SELECT id,type,src,width,height,server_code FROM '.$table.' WHERE id IN ('.implode(',',$ids).')');
				while($data = FDB::fetch($res))
				{
					ImageService::deleteServerImg($data);
				}
				FDB::query('DELETE FROM '.$table.' WHERE id IN ('.implode(',',$ids).')');
			}
			
			FDB::query('DELETE FROM '.FDB::table('images_index').' WHERE id IN ('.implode(',',$id_list).')');
		}
	}

	public function deleteServerImg($image)
	{
		global $_FANWE;
		if(strpos($image['src'],'/photos/') === FALSE)
		{
			ImageService::deleteRelImg($image['src'],$image['server_code']);
		}
		else
		{
			if(empty($image['server_code']))
			{
				$key = getDirsById($image['id']);
				clearDir(FANWE_ROOT.'./public/upload/photos/'.$key,true);
			}
			elseif($image['server_code'] == 'upyun')
			{
				include_once fimport('class/upyun');
				$upyun = new UpYun($_FANWE['UPYUN_SETTING']['space_name'],$_FANWE['UPYUN_SETTING']['user'],$_FANWE['UPYUN_SETTING']['password']);
				$upyun->readFile(str_replace('./upyun/','/',$image['src']));
			}
			else
			{
				$server = ImageService::getServer($image['server_code']);
				if($server)
				{
					$args = array();
					$args['id'] = $image['id'];
					$args['src'] = $image['src'];
					$server = ImageService::getImageUrlToken($args,$server,1);
					ImageService::sendRequest($server,'removeimg');
				}
			}
		}
	}
	
	public function deleteRelImg($img_path,$server_code = '')
	{
		global $_FANWE;
		if(empty($server_code))
		{
			$img_path = FANWE_ROOT.str_replace('./','',$img_path);
			$paths = pathinfo($img_path);
			@unlink($img_path);
			$old_img = explode('.',$img_path);
			$old_img = $old_img[0];
			if($dirhandle = opendir($paths['dirname']))
			{
				while(($file = readdir($dirhandle)) !== FALSE)
				{
					if(($file!=".") && ($file!=".."))
					{
						$filename = $paths['dirname'].'/'.$file;
						if(strpos($filename,$old_img) !== FALSE)
						{
							@unlink($filename);
						}
					}
				}
				@closedir($dirhandle);
			}
		}
		elseif($server_code == 'upyun')
		{
			include_once fimport('class/upyun');
			$upyun = new UpYun($_FANWE['UPYUN_SETTING']['space_name'],$_FANWE['UPYUN_SETTING']['user'],$_FANWE['UPYUN_SETTING']['password']);
			$upyun->readFile(str_replace('./upyun/','/',$img_path));
		}
		else
		{
			$server = ImageService::getServer($server_code);
			if($server)
			{
				$args = array();
				$args['img_path'] = $img_path;
				$server = ImageService::getImageUrlToken($args,$server,1);
				ImageService::sendRequest($server,'deleterelimg');
			}
		}
	}

	public function getTablaName($id,$is_full = true)
	{
		$id = substr((string)$id, -1, 1);
		if($is_full)
			return FDB::table('images_'.$id);
		else
			return 'images_'.$id;
	}
}
?>