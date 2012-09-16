<?php
@set_include_path(FANWE_ROOT.'sdks/paipai/');
require_once 'config.inc.php';
class paipai_sharegoods implements interface_sharegoods
{
	public function fetch($url)
	{
        global $_FANWE;
		
		//QQ号
		define('PAIPAI_API_UIN',trim($_FANWE['cache']['business']['paipai']['uin']));
		//令牌
		define('PAIPAI_API_TOKEN',trim($_FANWE['cache']['business']['paipai']['token']));
		//APP_KEY
		define('PAIPAI_API_SECRETKEY',trim($_FANWE['cache']['business']['paipai']['seckey']));
		define('PAIPAI_API_SPID',trim($_FANWE['cache']['business']['paipai']['spid']));

		$id = $this->getID($url);

		if(empty($id))
			return false;

		$key = 'paipai_'.$id;
		
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
		
		if(!$share_goods)
		{
			$paipaiParamArr = array(
				'uin' => PAIPAI_API_UIN,
				'token' => PAIPAI_API_TOKEN,
				'spid' => PAIPAI_API_SPID,
			);
			
			//API用户参数
			$userParamArr = array(
				'charset' => 'utf-8',
				'format' => 'xml',
				'itemCode' => $id,
			);
			
			$paramArr = $paipaiParamArr + $userParamArr;
			//请求数据
			$goods = Util::getResult($paramArr,'/item/getItem.xhtml');
			
			//解析xml结果
			$goods = Util::getXmlData($goods);
			
			if($goods['errorCode'] > 0)
				return false;
	
			if(empty($goods['picLink']))
				return false;
				
			if(!IS_UPYUN && FS("Image")->getIsServer())
			{
				$args = array();
				$args['pic_url'] = $goods['picLink'];
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
				$image = copyFile($goods['picLink'],"temp",false);
				if($image === false)
					return false;
				$image['server_code'] = '';
			}
			$share_goods['img'] = $image['path'];
			
			if(!empty($goods['sellerUin']))
			{
				//API用户参数
				$userParamArr = array(
					'charset' => 'utf-8',
					'format' => 'xml',
					'sellerUin' => $goods['sellerUin'],
				);
				
				$paramArr = $paipaiParamArr + $userParamArr;
				//请求数据
				$shop = Util::getResult($paramArr,'/shop/getShopInfo.xhtml');
				
				//解析xml结果
				$shop = Util::getXmlData($shop);
				if($shop['errorCode'] == 0)
				{
					$result['shop']['name'] = $shop['shopName'];
					$result['shop']['shop_id'] = $shop['sellerUin'];
					$result['shop']['url'] = 'http://shop.paipai.com/'.$shop['sellerUin'];

					$disable_shop = FDB::fetchFirst('SELECT * FROM '.FDB::table('shop_disable')." WHERE shop_url = '".$result['shop']['url']."'");
					if($disable_shop)
					{
						$result['status'] = -3;
						return $result;
					}
					
					$shop_id = FS("Shop")->getShopIdByUrl($result['shop']['url']);
					if($shop_id > 0)
					{
						$share_goods['shop_id'] = $shop_id;
						unset($result['shop']);
					}
				}
			}
			
			$share_goods['id'] = 0;
			$share_goods['name'] = $goods['itemName'];
			$share_goods['cid'] = FS("Goods")->getCid('paipai',$goods['classId']);
			$share_goods['price'] = $goods['itemPrice'] / 100;
			$share_goods['delist_time'] = (int)str2Time($goods['lastToSaleTime']) + (int)$goods['validDuration'];
			$share_goods['server_code'] = $image['server_code'];
			$share_goods['pic_url'] = $goods['picLink'];
			$share_goods['url'] = 'http://auction1.paipai.com/'.$goods['itemCode'];
		}
		else
		{
			$share_goods['pic_url'] = getImgById($share_goods['img_id'],100,100);
			if($share_goods['cid'] == 0)
			{
				$paipaiParamArr = array(
					'uin' => PAIPAI_API_UIN,
					'token' => PAIPAI_API_TOKEN,
					'spid' => PAIPAI_API_SPID,
				);
				
				//API用户参数
				$userParamArr = array(
					'charset' => 'utf-8',
					'format' => 'xml',
					'itemCode' => $id,
				);
				
				$paramArr = $paipaiParamArr + $userParamArr;
				$goods = Util::getResult($paramArr,'/item/getItem.xhtml');
				$goods = Util::getXmlData($goods);
				if($goods['errorCode'] == 0 && !empty($goods['classId']))
				{
					$update_goods = array();
					$share_goods['cid'] = $update_goods['cid'] = FS("Goods")->getCid('paipai',$goods['classId']);
					$share_goods['delist_time'] = $update_goods['delist_time'] = (int)str2Time($goods['lastToSaleTime']) + (int)$goods['validDuration'];
					FDB::update('goods',$update_goods,'id = '.$share_goods['id']);
				}
			}
		}
		
		$result['item'] = $share_goods;
		$result['item']['key'] = $key;
		$result['item']['type'] = "paipai";
		return $result;
	}

	public function getID($url)
	{
		$id = '';
		$parse = parse_url($url);
		if(isset($parse['path']))
		{
			$parse = explode('/',$parse['path']);
			$parse = end($parse);
			$parse = explode('-',$parse);
			$id = current($parse);
        }
		return $id;
	}

	public function getKey($url)
	{
		$id = $this->getID($url);
		return 'paipai_'.$id;
	}
}
?>