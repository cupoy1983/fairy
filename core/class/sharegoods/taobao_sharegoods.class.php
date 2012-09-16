<?php
include_once FANWE_ROOT.'sdks/taobao/TopClient.php';
include_once FANWE_ROOT.'sdks/taobao/request/ItemGetRequest.php';
include_once FANWE_ROOT.'sdks/taobao/request/ShopGetRequest.php';
include_once FANWE_ROOT.'sdks/taobao/request/TaobaokeItemsDetailGetRequest.php';
class taobao_sharegoods implements interface_sharegoods
{
	public function fetch($url)
	{
        global $_FANWE;

		$id = $this->getID($url);
		if($id == 0)
			return false;

		$key = 'taobao_'.$id;
		
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
		
		$client = new TopClient;
		$client->appkey = trim($_FANWE['cache']['business']['taobao']['app_key']);
		$client->secretKey = trim($_FANWE['cache']['business']['taobao']['app_secret']);
		$tao_ke_pid = trim($_FANWE['cache']['business']['taobao']['tk_pid']);
		
		if(!$share_goods)
		{
			$share_goods = array();
			$shop_click_url = '';
			$isGetGoods = true;
			if(!empty($tao_ke_pid))
			{
				$req = new TaobaokeItemsDetailGetRequest;
				$req->setFields("click_url,shop_click_url,detail_url,title,nick,pic_url,price,cid,delist_time");
				$req->setNumIids($id);
				$req->setPid($tao_ke_pid);
				$resp = $client->execute($req);
				
				if(isset($resp->taobaoke_item_details))
				{
					$taoke = (array)$resp->taobaoke_item_details->taobaoke_item_detail;
					if(!empty($taoke['item']))
					{
						$goods = (array)$taoke['item'];
						if(!empty($goods['detail_url']) && !empty($goods['pic_url']))
							$isGetGoods = false;
					}
						
					if(!empty($taoke['click_url']))
						$share_goods['taoke_url'] = $taoke['click_url'];
	
					if(!empty($taoke['shop_click_url']))
						$shop_click_url = $taoke['shop_click_url'];
				}
			}
			
			if($isGetGoods)
			{
				$req = new ItemGetRequest;
				$req->setFields("detail_url,title,nick,pic_url,price,cid,delist_time");
				$req->setNumIid($id);
				$resp = $client->execute($req);
		
				if(!isset($resp->item))
					return false;
		
				$result = array();
				$goods = (array)$resp->item;
		
				if(empty($goods['detail_url']) || empty($goods['pic_url']))
					return false;
			}
			
			if(!IS_UPYUN && FS("Image")->getIsServer())
			{
				$args = array();
				$args['pic_url'] = $goods['pic_url'];
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
				$image = copyFile($goods['pic_url'],"temp",false);
				if($image === false)
					return false;
				$image['server_code'] = '';
			}
			$share_goods['img'] = $image['path'];
			
			if(!empty($goods['nick']))
			{
				$req = new ShopGetRequest;
				$req->setFields("sid,nick,pic_path");
				$req->setNick($goods['nick']);
				$resp = $client->execute($req);
	
				if(isset($resp->shop))
				{
					$shop = (array)$resp->shop;
					$result['shop']['name'] = $shop['nick'];
					$result['shop']['shop_id'] = $shop['sid'];
					$result['shop']['url'] = 'http://shop'.$shop['sid'].'.taobao.com';
					$disable_shop = FDB::fetchFirst('SELECT * FROM '.FDB::table('shop_disable')." WHERE shop_url = '".$result['shop']['url']."'");
					if($disable_shop)
					{
						$result['status'] = -3;
						return $result;
					}
					
					$shop_id = FS("Shop")->getShopIdByUrl($result['shop']['url']);
					if($shop_id == 0)
					{
						if(!empty($shop['pic_path']))
						{
							if(!IS_UPYUN && FS("Image")->getIsServer())
							{
								$args = array();
								$args['pic_url'] = 'http://logo.taobao.com/shop-logo'.$shop['pic_path'];
								$server = FS("Image")->getImageUrlToken($args,'',1);
								$body = FS("Image")->sendRequest($server,'savetemp',true);
								if(!empty($body))
									$image = unserialize($body);
								else
									$image = false;
							}
							else
							{
								$image = copyFile('http://logo.taobao.com/shop-logo'.$shop['pic_path'],"temp",false);
								if($image === false)
									$image['server_code'] = '';
							}
	
							if($image !== false)
							{
								$result['shop']['logo'] = $image['path'];
								$result['shop']['server_code'] = $image['server_code'];
							}
						}
						
						if(!empty($shop_click_url))
							$result['shop']['taoke_url'] = $shop_click_url;
					}
					else
					{
						$share_goods['shop_id'] = $shop_id;
						unset($result['shop']);
					}
				}
			}
			
			$share_goods['id'] = 0;
			$share_goods['name'] = $goods['title'];
			$share_goods['cid'] = FS("Goods")->getCid('taobao',$goods['cid']);
			$share_goods['price'] = $goods['price'];
			$share_goods['delist_time'] = str2Time($goods['delist_time']);
			$share_goods['server_code'] = $image['server_code'];
			$share_goods['pic_url'] = $goods['pic_url'].'_100x100.jpg';
			$share_goods['url'] = $goods['detail_url'];
		}
		else
		{
			$share_goods['pic_url'] = getImgById($share_goods['img_id'],100,100);
			if($share_goods['cid'] == 0)
			{
				$req = new ItemGetRequest;
				$req->setFields("cid,delist_time");
				$req->setNumIid($id);
				$resp = $client->execute($req);
				if(isset($resp->item) && !empty($resp->item->cid))
				{
					$update_goods = array();
					$share_goods['cid'] = $update_goods['cid'] = FS("Goods")->getCid('taobao',$resp->item->cid);
					$share_goods['delist_time'] = $update_goods['delist_time'] = str2Time($resp->item->delist_time);
					FDB::update('goods',$update_goods,'id = '.$share_goods['id']);
				}
			}
		}
		
		$result['item'] = $share_goods;
		$result['item']['key'] = $key;
		$result['item']['type'] = "taobao";
		return $result;
	}

	public function getID($url)
	{
		$id = 0;
		$parse = parse_url($url);
		if(isset($parse['query']))
		{
            parse_str($parse['query'],$params);
			if(isset($params['id']))
				$id = $params['id'];
            elseif(isset($params['item_id']))
                $id = $params['item_id'];
			elseif(isset($params['default_item_id']))
                $id = $params['default_item_id'];
        }
		return $id;
	}

	public function getKey($url)
	{
		$id = $this->getID($url);
		return 'taobao_'.$id;
	}
}
?>