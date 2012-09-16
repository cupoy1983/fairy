<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**  
 * goods.service.php
 *
 * 商品服务
 *
 * @package service
 * @author awfigq <awfigq@qq.com>
 */
class GoodsService
{
	/**  
	 * 获取商品所在分享分类
	 * @param string $goods_cid 商品分类
	 * @return int
	 */
	public function getCid($type,$goods_cid)
	{
		global $_FANWE;
		$key = 'goods_cate_related_'.$type;
		Cache::getInstance()->loadCache($key);
		$ids = GoodsService::getParentIds($type,$goods_cid);
		foreach($ids as $id)
		{
			if(isset($_FANWE['cache'][$key][$id]))
			{
				return $_FANWE['cache'][$key][$id];
			}
		}
		return 0;
	}
	
	public function getParentIds($type,$id)
	{
		$ids = array();
		if(empty($type) || empty($id))
			return $ids;
		
		$key = 'goodsCate/'.$type.'/'.substr(md5($type.'_'.$id),0,2).'/'.$id;
		$ids = getCache($key);
		if($ids === NULL)
		{
			$ids = array();
			$ids[] = $id;
			GoodsService::getParentId($type,$id,$ids);
			setCache($key,$ids);
		}
		return $ids;
	}
	
	private function getParentId($type,$id,&$ids)
	{
		$pid = FDB::resultFirst("SELECT pid FROM ".FDB::table('goods_cates')." WHERE type='$type' AND id='$id'");
		if(!empty($pid))
		{
			$ids[] = $pid;
			GoodsService::getParentId($type,$pid,$ids);
		}
	}

	public function formatByIdKeys(&$list,$is_img = true)
	{
		if(!is_array($list) || count($list) == 0)
			return;
		
		$goods_ids = array_keys($list);
		$img_ids = array();
		$sql = 'SELECT id,keyid,type,img_id,name,url,taoke_url,price,delist_time,shop_id 
			FROM '.FDB::table('goods').' WHERE id IN ('.implode(',',$goods_ids).')';
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			foreach($list[$data['id']] as $key => $val)
			{
				$list[$data['id']][$key]['goods_id'] = $data['id'];
				$list[$data['id']][$key]['keyid'] = $data['keyid'];
				$list[$data['id']][$key]['name'] = $data['name'];
				$list[$data['id']][$key]['url'] = $data['url'];
				$list[$data['id']][$key]['taoke_url'] = $data['taoke_url'];
				$list[$data['id']][$key]['price'] = $data['price'];
				$list[$data['id']][$key]['delist_time'] = $data['delist_time'];
				$list[$data['id']][$key]['shop_id'] = $data['shop_id'];
				if($is_img)
					$img_ids[$data['img_id']][] = &$list[$data['id']][$key];
			}
		}
		
		if($is_img)
			FS('Image')->formatByIdKeys($img_ids);
	}
	
	public function collect($cid,$keywords,$sort,$page)
	{
		setTimeLimit(3600);
		global $_FANWE;
		include_once FANWE_ROOT.'sdks/taobao/TopClient.php';
        include_once FANWE_ROOT.'sdks/taobao/request/TaobaokeItemsGetRequest.php';
		Cache::getInstance()->loadCache('business');
		
		$client = new TopClient;
        $client->appkey = trim($_FANWE['cache']['business']['taobao']['app_key']);
        $client->secretKey = trim($_FANWE['cache']['business']['taobao']['app_secret']);
		
		$req = new TaobaokeItemsGetRequest;
        $req->setFields("num_iid,nick,title,price,click_url,shop_click_url,pic_url,item_location,volume,commission_rate,commission,commission_num,commission_volume");
        $req->setPid($_FANWE['cache']['business']['taobao']['tk_pid']);
		$req->setCid($cid);
		$req->setSort($sort);
		$req->setPageSize(40);
		$req->setPageNo($page);
		if(!empty($keywords))
			$req->setKeyword($keywords);
		
		$resp = $client->execute($req);
		
		$result = array('status'=>0,'count'=>0,'max_page'=>0);
		
		if(isset($resp->taobaoke_items) && isset($resp->taobaoke_items->taobaoke_item))
		{
			$max_page = ceil(((int)$resp->total_results) / 40);
			$list = (array)$resp->taobaoke_items;
			$list = $list['taobaoke_item'];
			foreach($list as $item)
			{
				usleep(10);
				$item = (array)$item;
				$item['num_iid'] = (float)$item['num_iid'];
				$item['keyid'] = 'taobao_'.$item['num_iid'];
				
				$bln = (int)FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('goods')." WHERE keyid='".$item['keyid']."'");
				if($bln)
					continue;
					
				$bln = (int)FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('goods_disable')." WHERE keyid='".$item['keyid']."'");
				if($bln)
					continue;
				
				$item['nick'] = addslashes($item['nick']);
				$item['title'] = addslashes(strip_tags($item['title']));
				$item['price'] = (float)$item['price'];
				$item['click_url'] = addslashes($item['click_url']);
				$item['shop_click_url'] = addslashes($item['shop_click_url']);
				$item['pic_url'] = addslashes($item['pic_url']);
				$item['item_location'] = addslashes($item['item_location']);
				$item['volume'] = (int)$item['volume'];
				$item['commission_rate'] = (float)$item['commission_rate'] / 100;
				$item['commission'] = (float)$item['commission'];
				$item['commission_num'] = (int)$item['commission_num'];
				$item['commission_volume'] = (float)$item['commission_volume'];
				FDB::insert('taobao_collect',$item,false,true);
			}
			
			$result['status'] = 1;
			$result['count'] = count($list);
			$result['max_page'] = $max_page;
		}
		return $result;
	}
	
	public function collectShop()
	{
		setTimeLimit(3600);
		$shop_list = array();
		$res = FDB::query('SELECT id,nick FROM '.FDB::table('taobao_shop_temp').' ORDER BY id ASC LIMIT 0,20');
		while($data = FDB::fetch($res))
		{
			$shop_list[$data['id']] = $data['nick'];
		}
		
		if(count($shop_list) == 0)
			return 1;
		
		$shop_ids = implode(',',array_keys($shop_list));
		FDB::delete('taobao_shop_temp','id IN ('.$shop_ids.')');
		
		global $_FANWE;
		include_once FANWE_ROOT.'sdks/taobao/TopClient.php';
		include_once FANWE_ROOT.'sdks/taobao/request/ShopGetRequest.php';
		Cache::getInstance()->loadCache('business');
		
		$client = new TopClient;
        $client->appkey = trim($_FANWE['cache']['business']['taobao']['app_key']);
        $client->secretKey = trim($_FANWE['cache']['business']['taobao']['app_secret']);
		
		$req = new ShopGetRequest;
		$req->setFields("sid,pic_path");
		
		foreach($shop_list as $nick)
		{
			if(!empty($nick))
			{
				$bln = (int)FDB::resultFirst('SELECT COUNT(nick) FROM '.FDB::table('taobao_shop')." WHERE nick='".addslashes($nick)."'");
				if($bln == 0)
				{
					$req->setNick($nick);
					$resp = $client->execute($req);
					if(isset($resp->shop))
					{
						$shop = (array)$resp->shop;
						$data = array();
						$data['nick'] = $nick;
						$data['sid'] = $shop['sid'];
						$data['url'] = 'http://shop'.$shop['sid'].'.taobao.com';
						$data['logo'] = 'http://logo.taobao.com/shop-logo'.$shop['pic_path'];
						FDB::insert('taobao_shop',$data,false,true);
					}
					sleep(1);
				}
				else
					usleep(10);
			}
		}
		return 0;
	}
	
	public function collectGoods()
	{
		setTimeLimit(3600);
		$goods_list = array();
		$shop_list = array();
		$res = FDB::query('SELECT num_iid,keyid,nick,title,price,click_url,shop_click_url,pic_url FROM '.FDB::table('taobao_collect').' ORDER BY num_iid ASC LIMIT 0,20');
		while($data = FDB::fetch($res))
		{
			if(!empty($data['nick']))
				$shop_list[$data['nick']] = array();
			
			$goods_list[$data['num_iid']] = $data;
		}
		
		if(count($goods_list) == 0)
			return 1;
		
		$goods_ids = implode(',',array_keys($goods_list));
		FDB::delete('taobao_collect','num_iid IN ('.$goods_ids.')');
		
		global $_FANWE;
		include_once FANWE_ROOT.'sdks/taobao/TopClient.php';
        include_once FANWE_ROOT.'sdks/taobao/request/ItemsListGetRequest.php';
		Cache::getInstance()->loadCache('business');
		
		$client = new TopClient;
        $client->appkey = trim($_FANWE['cache']['business']['taobao']['app_key']);
        $client->secretKey = trim($_FANWE['cache']['business']['taobao']['app_secret']);
		
		$req = new ItemsListGetRequest;
        $req->setFields("num_iid,detail_url,cid,delist_time");
		$req->setNumIids($goods_ids);
		$resp = $client->execute($req);
		
		if(isset($resp->items) && isset($resp->items->item))
		{
			$items = (array)$resp->items;
			$items = $items['item'];
			
			foreach($items as $item)
			{
				$item = (array)$item;
				$goods = $goods_list[$item['num_iid']];
				$goods['nick'] = addslashes($goods['nick']);
				$goods['title'] = addslashes($goods['title']);
				$goods['click_url'] = addslashes($goods['click_url']);
				$goods['shop_click_url'] = addslashes($goods['shop_click_url']);
				$goods['pic_url'] = addslashes($goods['pic_url']);
				$goods['cid'] = (float)$item['cid'];
				$goods['detail_url'] = addslashes($item['detail_url']);
				$goods['delist_time'] = str2Time($item['delist_time']);
				$goods['create_time'] = TIME_UTC;
				FDB::insert('taobao_share',$goods,false,true);
				usleep(10);
			}
		}
		return 0;
	}
	
	public function share()
	{
		setTimeLimit(3600);
		global $_FANWE;
		define('IS_NO_SQL_ERROR',true);
		define('IS_COLLECT_GOODS',true);
		
		$goods_data = FDB::fetchFirst('SELECT * FROM '.FDB::table('taobao_share').' ORDER BY num_iid ASC LIMIT 0,1');
		if(!$goods_data || !isset($goods_data['num_iid']))
			return 1;
		
		FDB::delete('taobao_share','num_iid = '.$goods_data['num_iid']);
		
		$bln = (int)FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('goods')." WHERE keyid='".$goods_data['keyid']."'");
		if($bln)
			return 0;
			
		$bln = (int)FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('goods_disable')." WHERE keyid='".$goods_data['keyid']."'");
		if($bln)
			return 0;
		
		//生成商品图片
		if(!IS_UPYUN && FS("Image")->getIsServer())
		{
			$args = array();
			$args['pic_url'] = $goods_data['pic_url'];
			$server = FS("Image")->formatServer($_FANWE['request']['image_server'],'DE');
			$server = FS("Image")->getImageUrlToken($args,$server,1);
			$body = FS("Image")->sendRequest($server,'savetemp',true);
			if(empty($body))
				return 0;
			
			$image = unserialize($body);
		}
		else
		{
			$image = copyFile($goods_data['pic_url'],"temp",false);
			if($image === false)
				return 0;
			
			$image['server_code'] = '';
		}
		$goods_data['img'] = $image['path'];
		$goods_data['server_code'] = $image['server_code'];
		
		$shop = FDB::fetchFirst('SELECT * FROM '.FDB::table('taobao_shop')." WHERE nick='".addslashes($goods_data['nick'])."'");
		if($shop)
		{
			$disable_shop = FDB::fetchFirst('SELECT * FROM '.FDB::table('shop_disable')." WHERE shop_url = '".$shop['url']."'");
			if($disable_shop)
				return 0;
			
			if(!FS("Shop")->getShopExistsByUrl($shop['url']))
			{
				if(!empty($shop['logo']))
				{
					if(!IS_UPYUN && FS("Image")->getIsServer())
					{
						$args = array();
						$args['pic_url'] = $shop['logo'];
						$server = FS("Image")->getImageUrlToken($args,'',1);
						$body = FS("Image")->sendRequest($server,'savetemp',true);
						if(!empty($body))
							$shop_image = unserialize($body);
						else
							$shop_image = false;
					}
					else
					{
						$shop_image = copyFile($shop['logo'],"temp",false);
						if($shop_image === false)
							$shop_image['server_code'] = '';
					}
		
					if($shop_image !== false)
					{
						$shop['logo'] = $shop_image['path'];
						$shop['server_code'] = $shop_image['server_code'];
					}
				}
			}
		}
		
		$uid = GoodsService::getCollectUser();
		$user = FS('user')->getUserById($uid);
		if(!$user)
			return 0;
		
		$_FANWE['uid'] = $uid;
		$_FANWE['user_name'] = addslashes($user['user_name']);
		$_FANWE['gid'] = $user['gid'];
		$_FANWE['user_group'] = $_FANWE['cache']['user_group'][$user['gid']];
		
		$share = array();
		$share['uid'] = $_FANWE['uid'];
		$share['parent_id'] = 0;
		$share['content'] = addslashes(htmlspecialchars($goods_data['title']));
		$share['type'] = 'default';
		$share['title'] = '';
		$share['base_id'] = 0;
		
		$share_goods = array();
		$share_goods['goods_id'] = 0;
		$share_goods['type'] = 'taobao';
		$share_goods['delist_time'] = $goods_data['delist_time'];
		$share_goods['img'] = $goods_data['img'];
		$share_goods['server_code'] = $goods_data['server_code'];
		$share_goods['goods_key'] = $goods_data['keyid'];
		$share_goods['name'] = addslashes(htmlspecialchars($goods_data['title']));
		$share_goods['url'] = $goods_data['detail_url'];
		$share_goods['taoke_url'] = $goods_data['click_url'];
		$share_goods['price'] = $goods_data['price'];
		if($shop)
		{
			$share_goods['shop_name'] = addslashes($shop['nick']);
			$share_goods['shop_logo'] = $shop['logo'];
			$share_goods['shop_server_code'] = $shop['server_code'];
			$share_goods['shop_url'] = $shop['url'];
			$share_goods['shop_taoke_url'] = $goods_data['shop_click_url'];
		}
		$share_goods['cid'] = GoodsService::getCid('taobao',$goods_data['cid']);
		$share_goods['sort'] = 1;
		
		$data = array();
		$data['share'] = $share;
		$data['share_goods'] = array($share_goods);
		
		FS("Share")->save($data,false);
		sleep(1);
		return 0;	
	}
	
	public function getCollectUser()
	{
		$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
		$bln = true;
		if(!$config || empty($config['cate_ids']))
			$bln = false;
		elseif(empty($config['user_ids']) && (int)$config['user_gid'] == 0)
			$bln = false;
			
		if($bln)
		{
			$where = ' WHERE status = 1';
			if((int)$config['user_gid'] > 0 && !empty($config['user_ids']))
				$where .= ' AND (gid = '.(int)$config['user_gid'].' OR uid IN ('.$config['user_ids'].'))';
			elseif((int)$config['user_gid'] > 0)
				$where .= ' AND gid = '.(int)$config['user_gid'];
			elseif(!empty($config['user_ids']))
				$where .= ' AND uid IN ('.$config['user_ids'].')';
				
			$uid = (int)FDB::resultFirst('SELECT uid FROM '.FDB::table('user').$where." ORDER BY RAND() ASC LIMIT 0,1");
			if($uid > 0)
				return $uid;
		}
		
		return FDB::resultFirst('SELECT uid FROM '.FDB::table('user')." WHERE status = 1 ORDER BY uid ASC LIMIT 0,1");
	}
}
?>