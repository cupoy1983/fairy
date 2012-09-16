<?php
include_once FANWE_ROOT."sdks/yiqifa/YiqifaOpen.php";
class yiqifa_sharegoods implements interface_sharegoods
{
	public function fetch($url)
	{
        global $_FANWE;
		
		$key = '';
		$id = $this->getID($url);
		if(!empty($id))
		{
			$key = 'yiqifa_'.$_FANWE['yiqifa_shop_id'].'_'.$id;
		}
		
		if(!empty($key))
		{
			$disable_goods = FDB::fetchFirst('SELECT * FROM '.FDB::table('goods_disable')." WHERE keyid = '$key'");
			if($disable_goods)
			{
				$result['status'] = -2;
				return $result;
			}
			
			$share_goods = FDB::fetchFirst('SELECT * FROM '.FDB::table('goods')." WHERE keyid = '$key'");
			if($share_goods)
			{
				$user_goods = (int)FDB::fetchFirst('SELECT * FROM '.FDB::table('share_goods').' 
				WHERE uid = '.$_FANWE['uid'].' AND goods_id = '.$share_goods['id']);
				if($user_goods)
				{
					$result['status'] = -1;
					$result['share_id'] = $user_goods['share_id'];
					$result['goods_id'] = $user_goods['goods_id'];
					return $result;
				}
			}
		}
		
		$yiqifa = new YiqifaOpen(trim($_FANWE['cache']['business']['yiqifa']['app_key']),trim($_FANWE['cache']['business']['yiqifa']['app_secret']));
		$args = array();
		$args['merchantId'] = $_FANWE['yiqifa_shop_id'];
		$args['productUrl'] = $url;
		$args['productId'] = $id;
		$args['websiteId'] = trim($_FANWE['cache']['business']['yiqifa']['site_id']);
		$args['feedback'] = 'default';
		$goods = $yiqifa->singleProduct($args);
		if(empty($goods))
		{
			$server_url = "http://221.122.127.46:8092/Service1.asmx/getProduct?";
			$args = array();
			$args['merchantId'] = $_FANWE['yiqifa_shop_id'];
			$args['productId'] = $id;
			$args['productUrl'] = $url;
			$args['w'] = trim($_FANWE['cache']['business']['yiqifa']['site_id']);
			$args['u'] = trim($_FANWE['cache']['business']['yiqifa']['uid']);
			$args['e'] = 'default';
			$server_url .= http_build_query($args);
			$content = getUrlContent($server_url);
			$goods = array();
			$xml = @simplexml_load_string($content);
			$names = array(
				'price_1'=>'price',
				'ProductName'=>'productName',
				'BrowseNodeKeyword'=>'category',
			);
			foreach($xml->children() as $k => $val)
			{
				if(isset($names[$k]))
					$k = $names[$k];
				
				$goods[$k] = (string)$val;
				if($k == 'url')
					$goods['unionCode'] = (string)$val;
			}
			
			$parseurl = parse_url($goods['unionCode']);
			parse_str($parseurl['query'], $querys);
			$goods['url'] = $querys['t'];
		}
		else
		{
			$parseurl = parse_url($goods['unionCode']);
			parse_str($parseurl['query'], $querys);
			$goods['unionCode'] = 'http://g.yiqifa.com/gc?';
			$goods['unionCode'] .= 'w='.trim($_FANWE['cache']['business']['yiqifa']['site_id']);
			$goods['unionCode'] .= '&u='.trim($_FANWE['cache']['business']['yiqifa']['uid']);
			$goods['unionCode'] .= '&e=default';
			$goods['unionCode'] .= '&c='.$querys['c'];
			$goods['unionCode'] .= '&v='.$querys['v'];
			$goods['unionCode'] .= '&i='.$querys['i'];
			$goods['unionCode'] .= '&t='.$querys['t'];
			if(empty($goods['url']))
				$goods['url'] = $querys['t'];
		}
		
		if(empty($goods['price']) || empty($goods['picurl']) || empty($goods['url']) || empty($goods['productName']))
			return false;
		
		if(empty($key))
		{
			$key = 'yiqifa'.md5($goods['url']);
			$disable_goods = FDB::fetchFirst('SELECT * FROM '.FDB::table('goods_disable')." WHERE keyid = '$key'");
			if($disable_goods)
			{
				$result['status'] = -2;
				return $result;
			}
			
			$share_goods = FDB::fetchFirst('SELECT * FROM '.FDB::table('goods')." WHERE keyid = '$key'");
			if($share_goods)
			{
				$user_goods = (int)FDB::fetchFirst('SELECT * FROM '.FDB::table('share_goods').' 
					WHERE uid = '.$_FANWE['uid'].' AND goods_id = '.$share_goods['id']);
				if($user_goods)
				{
					$result['status'] = -1;
					$result['share_id'] = $user_goods['share_id'];
					$result['goods_id'] = $user_goods['goods_id'];
					return $result;
				}
			}
		}
		
		if(!$share_goods)
		{
			if(!IS_UPYUN && FS("Image")->getIsServer())
			{
				$args = array();
				$args['pic_url'] = $goods['picurl'];
				$server = FS("Image")->formatServer($_FANWE['request']['image_server'],'DE');
				$server = FS("Image")->getImageUrlToken($args,$server,1);
				$body = FS("Image")->sendRequest($server,'savetemp',true);
				if(empty($body))
					return false;
				$image = unserialize($body);
				$result['image_server'] = $server['image_server'];
			}
			else
			{
				$image = copyFile($goods['picurl'],"temp",false);
				if($image === false)
					return false;
				$image['server_code'] = '';
			}
			$share_goods['img'] = $image['path'];
			$share_goods['id'] = 0;
			$share_goods['name'] = $goods['productName'];
			$share_goods['cid'] = FS("Goods")->getCid('yiqifa',$goods['category']);
			$share_goods['price'] = (float)$goods['price'];
			$share_goods['delist_time'] = 0;
			$share_goods['server_code'] = $image['server_code'];
			$share_goods['pic_url'] = $goods['picurl'];
			$share_goods['url'] = $goods['url'];
			$share_goods['taoke_url'] = $goods['unionCode'];
		}
		else
		{
			$share_goods['pic_url'] = getImgById($share_goods['img_id'],100,100);
		}


		$result['item'] = $share_goods;
		$result['item']['key'] = $key;
		$result['item']['type'] = "yiqifa";
		return $result;
	}
	
	public function getID($url)
	{
		global $_FANWE;
		$id = '';
		switch($_FANWE['yiqifa_shop_id'])
		{
			case '100015':
				//亚马逊
				$parse = parse_url($url);
				if(isset($parse['path']))
				{
					if(strpos($parse['path'],'/product/') !== FALSE)
						$parse = explode('/product/',$parse['path']);
					elseif(strpos($parse['path'],'/dp/') !== FALSE)
						$parse = explode('/dp/',$parse['path']);
					else
						return $id;
					
					$parse = end($parse);
					$parse = explode('/',$parse);
					$id = current($parse);
				}
			break;
			case '100016':
				//京东商城
				$parse = parse_url($url);
				if(isset($parse['path']))
				{
					$parse = explode('/',$parse['path']);
					$parse = end($parse);
					$parse = explode('.',$parse);
					$id = current($parse);
				}
			break;
			case '100047':
				//凡客
				$parse = parse_url($url);
				if(isset($parse['path']))
				{
					$parse = explode('/',$parse['path']);
					$parse = end($parse);
					$parse = explode('.',$parse);
					$id = current($parse);
				}
			break;
			case '100049':
				//当当网
				$parse = parse_url($url);
				if(isset($parse['path']))
				{
					parse_str($parse['query'],$params);
					if(isset($params['product_id']))
						$id = $params['product_id'];
					elseif(isset($params['id']))
						$id = $params['id'];
				}
			break;
		}
		return $id;
	}
	
	public function getKey($url)
	{
		global $_FANWE;
		$id = $this->getID($url);
		if(empty($id))
			return '';
		else
			return 'yiqifa_'.$_FANWE['yiqifa_shop_id'].'_'.$id;
	}
}
?>