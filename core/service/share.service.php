<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**
 * share.service.php
 *
 * 分享服务类
 *
 * @package service
 * @author awfigq <awfigq@qq.com>
 */
class ShareService
{
	/*===========分享列表、详细 Begin==============*/
	/**
	 * 检测分享中的敏感词
	 * @param string $content 分享内容
	 * @param string $type content,title,tag 检测类型
	 * @return array(
	 *   'error_code' => '错误代码'
	 *   'error_msg' => 错误描述
	 * )
	 */
	public function checkWord(&$content,$type)
	{
		$result = array('error_code'=>0,'error_msg'=>'');
		$server = FS("ContentCheck");
		if($server->check($content) > 0)
		{
			$words_found = implode("，", $server->words_found);
			$tt_str = lang('share','word_'.$type);
			$result['error_code'] = $server->result;
			$result['error_msg'] = sprintf(lang('share','word_tips_'. $server->result),$tt_str,$words_found);
		}
		return $result;
	}

	/**
	 * 通过的分享提交表单的数据处理
	 * @param mix $_POST 为标准分享表单 $_POST['type'] default:默认,bar:主题,ershou:二手,ask:问答
	 * $_POST['share_data'] = photo 有图 goods 有产品 goods_photo:有图有商品 default:都没有
	 * 	* 返回
	 *  array(
	 *   'status' => xxx  状态  bool
	 *   'share_id' => share_id
	 *   'error_code' => '错误代码'
	 *   'error_msg' => 错误描述
	 * )
	 */
	public function submit($_POST,$is_check = true,$is_score = true)
	{
		//创建分享数据
		global $_FANWE;
		$share_content = htmlspecialchars(trim($_POST['content']));
		$share_data = array();
		$share_data['content'] = $share_content;
		$share_data['uid'] = intval($_FANWE['uid']);
		$share_data['cid'] = intval($_POST['cid']);
		$share_data['parent_id'] = intval($_POST['parent_id']); //分享的转发
		$share_data['rec_id'] = intval($_POST['rec_id']); //关联的编号
		$share_data['base_id'] = intval($_POST['base_id']);
		$share_data['albumid'] = intval($_POST['albumid']);
		
		if($is_check)
		{
			$check_result = ShareService::checkWord($share_data['content'],'content');
			if($check_result['error_code'] == 1)
			{
				$check_result['status'] = false;
				return $check_result;
			}
		}

		/*//当为转发的时候，获取原创ID
		if($share_data['parent_id'] > 0 && $share_data['base_id'] == 0)
		{
			$base_id = intval(FDB::resultFirst('SELECT base_id
				FROM '.FDB::table("share").'
				WHERE share_id = '.$share_data['parent_id']));

			$share_data['base_id'] = $base_id == 0 ? $share_data['parent_id'] : $base_id;
		}*/

		if(isset($_POST['type']))
			$share_data['type'] = $_POST['type'];

		$share_data['title'] = isset($_POST['title']) ? htmlspecialchars(trim($_POST['title'])) : '';
		if(!empty($share_data['title']) && $is_check)
		{
			$check_result = ShareService::checkWord($share_data['title'],'title');
			if($check_result['error_code'] == 1)
			{
				$check_result['status'] = false;
				return $check_result;
			}
		}
		
		$data['share'] = $share_data;

		//创建分享商品数据
		$share_goods_data = array();
		if(isset($_POST['goods']) && is_array($_POST['goods']) && count($_POST['goods']) > 0)
		{
			$share_goods = $_POST['goods'];
			foreach($share_goods as $goods)
			{
				$goods = unserialize(authcode($goods,'DECODE'));
				$gkey = $goods['item']['key'];
				$c_data = array();
				$c_data['goods_id'] = $goods['item']['id'];
				if($goods['item']['id'] == 0)
				{
					$c_data['img'] = $goods['item']['img'];
					$c_data['server_code'] = $goods['item']['server_code'];
					$c_data['shop_name'] = addslashes(htmlspecialchars(strip_tags($goods['shop']['name'])));
					$c_data['shop_logo'] = $goods['shop']['logo'];
					$c_data['shop_server_code'] = $goods['shop']['server_code'];
					$c_data['shop_url'] = addslashes($goods['shop']['url']);
					$c_data['shop_taoke_url'] = $goods['shop']['taoke_url'];
				}
				else
				{
					$c_data['img_id'] = (int)$goods['item']['img_id'];
				}
				
				$c_data['shop_id'] = (int)$goods['item']['shop_id'];
				
				$c_data['taoke_url'] = $goods['item']['taoke_url'];
				$c_data['price'] = $goods['item']['price'];
				$c_data['type'] = $goods['item']['type'];
				$c_data['url'] = $goods['item']['url'];
				$c_data['goods_key'] = $gkey;
				$c_data['delist_time'] = $goods['item']['delist_time'];
				$c_data['name'] = addslashes(htmlspecialchars(strip_tags($goods['item']['name'])));
				$c_data['cid'] = $goods['item']['cid'];
				$c_data['sort'] = isset($_POST['goods_sort'][$gkey]) ? intval($_POST['goods_sort'][$gkey]) : 10;
				array_push($share_goods_data,$c_data);
			}
		}
		$data['share_goods'] = $share_goods_data;
		
		//创建图库数据
		$share_photos_data = array();
		if(isset($_POST['pics']) && is_array($_POST['pics']) && count($_POST['pics']) > 0)
		{
			$share_photos = $_POST['pics'];
			foreach($share_photos as $pkey => $photo)
			{
				$photo = authcode($photo,'DECODE');
				$photo = unserialize($photo);
				$c_data = array();
				$c_data['img'] = $photo['path'];
				$c_data['server_code'] = $photo['server_code'];

				$type = $photo['type'];
				if(empty($type) || !in_array($type,array('default', 'dapei', 'look')))
					$type = 'default';
				$c_data['type'] = $type;
				$c_data['sort'] = isset($_POST['pics_sort'][$pkey]) ? intval($_POST['pics_sort'][$pkey]) : 10;
				array_push($share_photos_data,$c_data);
			}
		}
		$data['share_photo'] = $share_photos_data;
		
		if($share_data['albumid'] > 0 && count($share_photos_data) == 0 && count($share_goods_data) == 0)
			exit;

		$data['share_tag'] = array();

		if(isset($_POST['tags']) && trim($_POST['tags']) != '')
		{
			$tags = htmlspecialchars(trim($_POST['tags']));
			if($is_check)
			{
				$check_result = ShareService::checkWord($tags,'tag');
				if($check_result['error_code'] == 1)
				{
					$check_result['status'] = false;
					return $check_result;
				}
			}
			$tags = str_replace('　',' ',$tags);
			$data['share_tag'] = explode(' ',$tags);
		}
		$data['pub_out_check'] = intval($_POST['pub_out_check']);  //发送到外部微博
		$result = ShareService::save($data,$is_score);
		return $result;
	}

	/**
	 * 保存分享数据
	 * 注：所有图片地址经处理过并转存过的临时图片或远程图片
	 * $data = array( //分享的基本数据
	 *  'share'=>array(
	 * 	  'uid'=> xxx, //分享的会员ID
	 * 	  'parent_id'	=>	xxx //转发的分享ID
	 * 	  'content'	=>	xxx //分享的内容
	 * 	  'type'=> xxx  //分享的来源，默认为default
	 *    'title' => xxx //分享的标题
	 *    'base_id' => xxx //原创ID
	 * 	),
	 *
	 *  'share_photo'=>array( //图库  #可选#
	 *    array(  //多张图
	 *    'img' => xxx //原图
	 *    )
	 *  ),
	 *  'share_goods'=>array( //分享的商品 #可选#
	 *    array(
	 *    'img' => xxx  //商品图
	 *    'name' => xxx //品名
	 *    'url'  => xxx //商品地址
	 *    'price' => xxx  //价格
	 *    'shop_name' => xxx //商户名称
	 *    'shop_logo' => xxx //商户的logo
	 *    'shop_url' => xxx //商户地址
	 *    ) //多个商品
	 *  ),
	 *  'share_tag' => array(xxx,xxx,xxx),  //该分享的标签
	 * );
	 *
	 * 返回
	 * array(
	 *   'status' => xxx  状态  bool
	 *   'share_id' => share_id
	 * )
	 */
	public function save($data,$is_score = true)
	{
		global $_FANWE;
		setTimeLimit(300);
		//保存分享数据
		$share_data = $data['share'];
		$share_album_id = (int)$share_data['albumid'];
		$share_cid = (int)$share_data['cid'];
		unset($share_data['albumid'],$share_data['cid']);
		$share_data['create_time'] = TIME_UTC;
		$share_id = FDB::insert('share',$share_data,true);
		if(intval($share_id)>0)
		{
			$share_data_now_type = $share_data['type'];
			$share_data_rec_id = $share_data['rec_id'];
			if(empty($share_data_now_type))
				$share_data_now_type = 'default';
						
			/*//是否是回复 是的 话 添加评论消息提示
			if(intval($share_data['parent_id']) > 0)
			{
				$base_share_id = FDB::resultFirst("select uid from ".FDB::table('share')." where share_id = ".$share_data['parent_id']);
				$result = FDB::query("INSERT INTO ".FDB::table('user_notice')."(uid, type, num, create_time) VALUES('$base_share_id',3,1,'".TIME_UTC."')", 'SILENT');
				if(!$result)
					FDB::query("UPDATE ".FDB::table('user_notice')." SET num = num + 1, create_time='".TIME_UTC."' WHERE uid='$base_share_id' AND type=3");
			}*/
			
			$share_cates = array();
			$result['status'] = true;
			$result['share_id'] = $share_id;
			
			/*$content_match = FS('Words')->segment(clearExpress($share_data['content']),100);
			$title_tags = FS('Words')->segment($share_data['title'],100);
            if(!empty($title_tags))
				$content_match = array_merge($content_match, $title_tags);*/

			$content_match = clearExpress($share_data['content']);
            $content_match .= ' '.$share_data['title'];
			
			$is_rel_share = false;
			$weibo_img = '';
			$weibo_img_url = '';
			$weibo_img_sort = 100;
			$photo_count = 0;
			$goods_count = 0;
			
			//保存引用图片
			if(isset($data['rel_photo']))
			{
				$share_photo = $data['rel_photo'];
				foreach($share_photo as $share_photo_data)
				{
					if($photo_count >= $_FANWE['setting']['share_pic_count'])
						break;
					
					$is_rel_share = true;
					if($data['pub_out_check'] && $share_photo_data['sort'] < $weibo_img_sort)
					{
						$rel_img = FS("Image")->getImageById($share_photo_data['img_id']);
						$weibo_img = FS("Image")->getImageUrl($rel_img['src'],1);
						$weibo_img_url = FS("Image")->getImageUrl($rel_img['src'],2);
						$weibo_img_sort = $share_photo_data['sort'];
					}
					
					$share_photo_data['uid'] = $_FANWE['uid'];
					$share_photo_data['share_id'] = $share_id;
					FDB::insert('share_photo',$share_photo_data,true);
					$photo_count++;
				}
			}
			
			//保存引用商品
			if(isset($data['rel_goods']))
			{
				$share_goods = $data['rel_goods'];
				foreach($share_goods as $share_goods_data)
				{
					if($goods_count >= $_FANWE['setting']['share_goods_count'])
						break;
					
					$is_rel_share = true;
					if($data['pub_out_check'] && $share_goods_data['sort'] < $weibo_img_sort)
					{
						$rel_img = FS("Image")->getImageById($share_goods_data['img_id']);
						$weibo_img = FS("Image")->getImageUrl($rel_img['src'],1);
						$weibo_img_url = FS("Image")->getImageUrl($rel_img['src'],2);
						$weibo_img_sort = $share_goods_data['sort'];
					}
					
					$share_goods_data['uid'] = $_FANWE['uid'];
					$share_goods_data['share_id'] = $share_id;
					FDB::insert('share_goods',$share_goods_data,true);
					$goods_count++;
				}
			}

			//保存分享图片
			$share_photo = $data['share_photo'];
			$photo_types = array();
			foreach($share_photo as $k=>$photo)
			{
				if($photo_count >= $_FANWE['setting']['share_pic_count'])
					break;
				
				if(!isset($photo['img_id']))
				{
					$o_img = array();
					$o_img['type'] = "share";
					$o_img['src'] = $photo['img'];
					$o_img['server_code'] = $photo['server_code'];
					$o_img = FS("Image")->addImage($o_img);
					$photo['img_id'] = (int)$o_img['id'];
				}

				if($photo['img_id'] > 0)
				{
					$is_rel_share = false;
					if($data['pub_out_check'] && $photo['sort'] < $weibo_img_sort)
					{
						$weibo_img = $o_img['path'];
						$weibo_img_url = $weibo_img;
						if(empty($o_img['server_code']))
							$weibo_img_url = FS("Image")->getImageUrl($o_img['src'],2);
						$weibo_img_sort = $photo['sort'];
					}
					
					$share_photo_data['uid'] = $_FANWE['uid'];
					$share_photo_data['share_id'] = $share_id;
					$share_photo_data['img_id'] =  $photo['img_id'];
					$share_photo_data['type'] =  $photo['type'];
					$photo_types[$photo['type']] = 1;
					$share_photo_data['sort'] =  $photo['sort'];
					FDB::insert('share_photo',$share_photo_data,true);
					$photo_count++;
				}
			}
			
			$shop_ids = array();
			$goods_prices = array();
			$share_cids = array();
			if($share_cid > 0)
				$share_cids[] = $share_cid;
			
			//保存分享的商品
			if(isset($data['share_goods']))
			{
				$share_goods = $data['share_goods'];
				foreach($share_goods as $goods)
				{
					if($goods_count >= $_FANWE['setting']['share_goods_count'])
						break;
						
					$shop_id = (int)$goods['shop_id'];
					
					if($goods['goods_id'] == 0)
					{
						if($shop_id == 0 && !empty($goods['shop_url']))
						{
							$shop_id = FDB::resultFirst('SELECT shop_id
								FROM '.FDB::table('shop').'
								WHERE shop_url = \''.$goods['shop_url'].'\'');
		
							if(intval($shop_id) == 0)
							{
								$content_match .= ' '.$goods['shop_name'];
								$shop_logo = 0;
								if(!empty($goods['shop_logo']))
								{
									$o_img = array();
									$o_img['type'] = "shop";
									$o_img['src'] = $goods['shop_logo'];
									$o_img['server_code'] = $goods['shop_server_code'];
									$o_img = FS("Image")->addImage($o_img);
									
									if(!empty($o_img))
										$shop_logo = $o_img['id'];
								}
	
								$shop_data['shop_name'] = $goods['shop_name'];
								$shop_data['shop_logo'] =  $shop_logo;
								$shop_data['shop_url'] = $goods['shop_url'];
								$shop_data['taoke_url'] = $goods['shop_taoke_url'];
								$shop_id = (int)FDB::insert('shop',$shop_data,true);
								$shop_match = FS('Words')->segmentToUnicode($goods['shop_name']);
								FDB::insert('shop_match',array('id'=>$shop_id,'shop_name'=>$shop_match));
								if(!defined('IS_COLLECT_GOODS'))
									FDB::insert('shop_check',array('shop_id'=>$shop_id));
							}
						}
						
						$o_img = array();
						$o_img['type'] = "share";
						$o_img['src'] = $goods['img'];
						$o_img['rel_count'] = 10000;
						$o_img['server_code'] = $goods['server_code'];
						$o_img = FS("Image")->addImage($o_img);
						
						if(!empty($o_img))
						{
							if($data['pub_out_check'] && $goods['sort'] < $weibo_img_sort)
							{
								$weibo_img = $o_img['path'];
								$weibo_img_url = $weibo_img;
								if(empty($o_img['server_code']))
									$weibo_img_url = FS("Image")->getImageUrl($o_img['src'],2);
								$weibo_img_sort = $goods['sort'];
							}
							
							$goods_data = array();
							$goods_data['type'] = $goods['type'];
							$goods_data['keyid'] = $goods['goods_key'];
							$goods_data['shop_id'] = $shop_id;
							$goods_data['cid'] = $goods['cid'];
							$goods_data['img_id'] = $o_img['id'];
							$goods_data['name'] = strip_tags($goods['name']);
							$goods_data['url'] = $goods['url'];
							$goods_data['taoke_url'] = $goods['taoke_url'];
							$goods_data['price'] = $goods['price'];
							$goods_data['delist_time'] = $goods['delist_time'];
							$goods_data['create_time'] = TIME_UTC;
							$create_day = fToDate($goods['create_time'],'Y-m-d 00:00:00');
							$goods['create_day'] = str2Time($create_day);
							
							$goods['goods_id'] = (int)FDB::insert('goods',$goods_data,true);
							if(!defined('IS_COLLECT_GOODS'))
								FDB::insert('goods_check',array('id'=>$goods['goods_id']));
							
							$goods_match = FS('Words')->segmentToUnicode($goods['name']);
							FDB::insert('goods_match',array('id'=>$goods['goods_id'],'goods_name'=>$goods_match));
							$goods['img_id'] = $o_img['id'];
							if($shop_id > 0)
								$shop_ids[] = $shop_id;
						}
					}
					else
					{
						if($shop_id > 0)
						{
							$content_match .= ' '.FS("Shop")->getShopName($shop_id);
							$shop_ids[] = $shop_id;
						}
						
						if($data['pub_out_check'] && $goods['sort'] < $weibo_img_sort)
						{
							$goods_img = FS("Image")->getImageById($goods['img_id']);
							$weibo_img = FS("Image")->getImageUrl($goods_img['src'],1);
							$weibo_img_url = FS("Image")->getImageUrl($goods_img['src'],2);
							$weibo_img_sort = $goods['sort'];
						}
					}
					
					if($goods['goods_id'] > 0)
					{
						$is_rel_share = false;
						$share_goods_data['uid'] = $_FANWE['uid'];
						$share_goods_data['share_id'] = $share_id;
						$share_goods_data['shop_id'] = $shop_id;
						$share_goods_data['goods_id'] =  $goods['goods_id'];
						$share_goods_data['img_id'] =  $goods['img_id'];
						$share_goods_data['price'] = $goods['price'];
						$share_goods_data['sort'] = $goods['sort'];
						FDB::insert('share_goods',$share_goods_data);
						FS('Image')->updateImageRel($goods['img_id']);
						$goods_count++;
						$content_match .= ' '.$goods['name'];
						$goods_prices[] = $goods['price'];
						if($goods['cid'] > 0)
							$share_cids[] = $goods['cid'];
					}
				}
			}

			if($goods_count > 0 && $photo_count > 0)
				$share_data_type = 'goods_photo';
			elseif($goods_count > 0)
				$share_data_type = 'goods';
			elseif($photo_count > 0)
				$share_data_type = 'photo';
			else
				$share_data_type = 'default';
			
			$update_share = array();
			$update_share['share_data'] = $share_data_type;
			$update_share['status'] = 0;
			
			//如果为采集，则默认为已审核
			if(defined('IS_COLLECT_GOODS'))
			{
				if(count($share_cids) > 0)
					$update_share['status'] = 1;
			}
			else
			{
				switch((int)$_FANWE['setting']['share_check_type'])
				{
					case 1:
						$update_share['status'] = 0;
					break;
					
					case 2:
						if($share_data_type == 'default')
							$update_share['status'] = 0;
						else
							$update_share['status'] = 1;
					break;
					
					case 3:
						if($share_data_type == 'default')
							$update_share['status'] = 1;
						else
							$update_share['status'] = 0;
					break;
					
					case 4:
						if($goods_count > 0)
							$update_share['status'] = 0;
						else
							$update_share['status'] = 1;
					break;
					
					case 5:
						if($photo_count > 0)
							$update_share['status'] = 0;
						else
							$update_share['status'] = 1;
					break;
					
					default:
						$update_share['status'] = 1;
					break;
				}
			}
			
			if(in_array($share_data_now_type,array('fav','album','group','group_join')))
				$update_share['status'] = 1;
			
			$share_data_status = $update_share['status'];
			
			if($share_album_id > 0 && in_array($share_data_type,array('goods','photo','goods_photo')))
			{
				$album = FDB::fetchFirst('SELECT cid,id,title FROM '.FDB::table('album').' WHERE id = '.$share_album_id);
				if($album)
				{
					$update_share['type'] = 'album_item';
					$share_data_now_type = 'album_item';
					$share_data_rec_id = $album['id'];
					$share_data_rec_cate = $album['cid'];
					$update_share['rec_id'] = $album['id'];
					if(empty($share_data['title']))
					{
						$update_share['title'] = addslashes($album['title']);
						$content_match .= ' '.$update_share['title'];
					}
				}
				else
				{
					$update_share['rec_id'] = 0;
					$share_data_rec_id = 0;
				}
			}
			else
				$share_data_rec_id = 0;
			
			FDB::update("share",$update_share,"share_id=".$share_id);

			//更新会员统计
			$sql = 'UPDATE '.FDB::table('user_count').' SET shares = shares + 1,goods = goods + '.$goods_count.',photos = photos + '.$photo_count;
			if(isset($photo_types['dapei']))
				$sql .= ',dapei = dapei + 1';
				
			if(isset($photo_types['look']))
				$sql .= ',looks = looks + 1';
			
			$sql .= ' WHERE uid = '.$share_data['uid'];
			FDB::query($sql);
			FDB::query('UPDATE '.FDB::table('user_status').' SET last_share = '.$share_id.' WHERE uid = '.$share_data['uid']);
			
			FS('Medal')->runAuto($share_data['uid'],'shares');
			FS('User')->medalBehavior($share_data['uid'],'continue_share');
			
			switch($share_data_type)
			{
				case 'goods_photo':
					FS('Medal')->runAuto($share_data['uid'],'goods');
					FS('User')->medalBehavior($share_data['uid'],'continue_goods');
					FS('Medal')->runAuto($share_data['uid'],'photos');
					FS('User')->medalBehavior($share_data['uid'],'continue_photo');
				break;
				case 'goods':
					FS('Medal')->runAuto($share_data['uid'],'goods');
					FS('User')->medalBehavior($share_data['uid'],'continue_goods');
				break;
				case 'photo':
					FS('Medal')->runAuto($share_data['uid'],'photos');
					FS('User')->medalBehavior($share_data['uid'],'continue_photo');
				break;
			}
			
			//如果未审核，放入需要审核分享表
			if($share_data_status == 1)
				FS('Event')->saveEvent($share_id);
			else
				FDB::insert("share_check",array('share_id'=>$share_id,'uid'=>$_FANWE['uid']));

			if(in_array($share_data_type,array('goods','photo','goods_photo')))
			{
				//保存标签
				$share_tags = array();
				foreach($data['share_tag'] as $tag)
				{
					if(trim($tag) != '' && !in_array($tag,$share_tags))
					{
						array_push($share_tags,$tag);
						$content_match.=' '.$tag;
						$tag_data = array();
						$tag_data['share_id'] = $share_id;
						$tag_data['tag_name'] = $tag;
						FDB::insert('share_tags',$tag_data);
					}
				}
				
				$imags_index = array();
				$imags_index['uid'] = $_FANWE['uid'];
				$imags_index['share_id'] = $share_id;
				$imags_index['status'] = $share_data_status;
				FDB::insert("share_user_images",$imags_index);
				
				if(!$is_rel_share)
				{
					Cache::getInstance()->loadCache('goods_category');
					$share_cids = array_unique($share_cids);
					$share_cates = array();
					foreach($share_cids as $cid)
					{
						$share_cates[] = $cid;
						if(isset($_FANWE['cache']['goods_category']['all'][$cid]['parents']) && 
							count($_FANWE['cache']['goods_category']['all'][$cid]['parents']) > 0)
						{
							$share_cates = array_merge($share_cates,$_FANWE['cache']['goods_category']['all'][$cid]['parents']);
						}
					}

					$share_cates = array_unique($share_cates);
					foreach($share_cates as $cid)
					{
						$cate_data = array();
						$cate_data['share_id'] = $share_id;
						$cate_data['cate_id'] = $cid;
						$cate_data['uid'] = $_FANWE['uid'];
						FDB::insert('share_category',$cate_data);
					}
					
					$cate_tags = ShareService::getCateTags($content_match,$share_cates);
					if(count($cate_tags) > 0)
					{
						foreach($cate_tags as $tag)
						{
							if(!in_array($tag,$share_tags))
							{
								array_push($share_tags,$tag);
								$content_match.=' '.$tag;
								$tag_data = array();
								$tag_data['share_id'] = $share_id;
								$tag_data['tag_name'] = $tag;
								FDB::insert('share_tags',$tag_data);
							}
						}
					}
					
					if($share_data_status == 1)
					{
						//保存匹配查询
						$share_match['share_id'] = $share_id;
						$share_match['content_match'] = FS('Words')->segmentToUnicode($content_match);
						FDB::insert("share_match",$share_match);
		
						$imags_index = array();
						$imags_index['uid'] = $_FANWE['uid'];
						$imags_index['share_id'] = $share_id;
						FDB::insert("share_images_index",$imags_index);
					}
				}

				if($goods_count > 0)
				{
					$goods_index = array();
					$goods_index['uid'] = $_FANWE['uid'];
					$goods_index['share_id'] = $share_id;
					$goods_index['status'] = $share_data_status;
					FDB::insert("share_user_goods",$goods_index);
					if($share_data_status == 1 && !$is_rel_share)
					{
						FDB::insert("share_goods_match",$share_match);
						sort($goods_prices);
						$goods_index = array();
						$goods_index['uid'] = $_FANWE['uid'];
						$goods_index['share_id'] = $share_id;
						$goods_index['min_price'] = current($goods_prices);
						$goods_index['max_price'] = end($goods_prices);
						FDB::insert("share_goods_index",$goods_index);
					}
				}
					
				if($photo_count > 0)
				{
					$photo_index = array();
					$photo_index['uid'] = $_FANWE['uid'];
					$photo_index['share_id'] = $share_id;
					$photo_index['status'] = $share_data_status;
					FDB::insert("share_user_photo",$photo_index);
					
					if(isset($photo_types['dapei']))
						FDB::insert("share_user_dapei",$photo_index);
						
					if(isset($photo_types['look']))
						FDB::insert("share_user_look",$photo_index);

					if($share_data_status == 1 && !$is_rel_share)
					{
						FDB::insert("share_photo_match",$share_match);
						
						unset($photo_index['status']);
						FDB::insert("share_photo_index",$photo_index);

						if(isset($photo_types['dapei']))
						{
							FDB::insert("share_dapei_index",$photo_index);
							if($goods_count > 0)
								FDB::insert("share_dapei_goods",$photo_index);
						}
							
						if(isset($photo_types['look']))
						{
							FDB::insert("share_look_index",$photo_index);
							if($goods_count > 0)
								FDB::insert("share_look_goods",$photo_index);
						}
					}
				}
			}
			elseif(in_array($share_data_now_type,array('default','comments','bar','bar_post')))
			{
				if($share_data_status == 1)
				{
					FDB::insert("share_text_index",array('share_id'=>$share_id,'uid'=>$_FANWE['uid']));
					
					//保存匹配查询
					$share_match['share_id'] = $share_id;
					$share_match['content_match'] = FS('Words')->segmentToUnicode($content_match);
					FDB::insert("share_text_match",$share_match);
				}
			}
			
			ShareService::updateShareCache($share_id);
			
			if($share_data_rec_id > 0)
			{
				$album_share = array();
				$album_share['album_id'] = $share_data_rec_id;
				$album_share['share_id'] = $share_id;
				$album_share['cid'] = $share_data_rec_cate;
				FDB::insert("album_share",$album_share);
				
				if($share_data_status == 1)
					FDB::insert("album_share_index",$album_share);

				FS('Album')->updateAlbumByShare($share_data_rec_id,$share_id);
				FS('Album')->updateAlbum($share_data_rec_id);
				FS('Album')->setUserCache($_FANWE['uid']);
			}
			
			if($share_data_status == 1 && !$is_rel_share)
			{
				if(isset($photo_types['look']))
					FS('Look')->setUserCache($_FANWE['uid']);

				if(isset($photo_types['dapei']))
					FS('Dapei')->setUserCache($_FANWE['uid']);
			}

			if(count($shop_ids) > 0 && $share_data_status == 1)
			{
				FS("Shop")->saveShopShare($shop_ids,$share_id,$_FANWE['uid']);
				FS("Shop")->updateShopStatistic($shop_ids);
			}
			
			//保存提到我的
			$atme_share_type = FDB::resultFirst("select `type` from ".FDB::table("share")." where `share_id`='".$share_id."'");
			if($share_data_now_type != "fav")
			{
				$atme_list = array();
				$pattern = "/@([^\f\n\r\t\v@ ]{2,20}?)(?:\:| )/";
				preg_match_all($pattern,$share_data['content'],$atme_list);
				if(!empty($atme_list[1]))
				{
					$atme_list[1] = array_unique($atme_list[1]);
					$users = array();
					foreach($atme_list[1] as $user)
					{
						if(!empty($user))
						{
							$users[] = $user;
						}
					}
					
					$res = FDB::query('SELECT uid 
						FROM '.FDB::table('user').'
						WHERE user_name '.FDB::createIN($users));
					while($data = FDB::fetch($res))
					{
						FS("User")->setUserTips($data['uid'],4,$share_id);
					}
				}
			}
			
			if($is_score && !in_array($share_data_now_type,array('fav','album_best','album_rec')))
			{
				if(!$is_rel_share && in_array($share_data_type,array('goods','photo','goods_photo')))
					FS("User")->updateUserScore($share_data['uid'],'share','image',$share_data['content'],$share_id);
				else
					FS("User")->updateUserScore($share_data['uid'],'share','default',$share_data['content'],$share_id);
			}
			
			if($data['pub_out_check'] && !defined('IS_COLLECT_GOODS'))
			{
				$weibo = array();
				$weibo['content'] = $share_data['content'];
				$weibo['img'] = $weibo_img;
				$weibo['img_url'] = $weibo_img_url;
				$weibo['ip'] = $_FANWE['client_ip'];
				$weibo['url'] = $_FANWE['site_url'].FU('note/index',array('sid'=>$share_id));
                $weibo['url'] = str_replace('//','/',$weibo['url']);
				$weibo['url'] = str_replace(':/','://',$weibo['url']);
				$weibo = base64_encode(serialize($weibo));
				if(empty($share_data['type']))
					$share_data['type'] = 'default';
				
				//转发到外部微博
				$uid = $_FANWE['uid'];
				$user_binds = FS("User")->getUserBindList($uid);
				$is_open = false;
				foreach($user_binds as $class => $bind)
				{
					if($bind['sync'] && file_exists(FANWE_ROOT."login/".$class.".php"))
					{
						$check_field = "";
						if(in_array($share_data['type'],array('bar','ask')))
							$check_field = "topic";
						elseif($share_data['type'] == 'default')
							$check_field = "weibo";
						
						if($bind['sync'][$check_field] == 1)
						{
							$is_open = true;
							//开始推送
							$schedule['uid'] = $uid;
							$schedule['type'] = $class;
							$schedule['data'] = $weibo;
							$schedule['pub_time'] = TIME_UTC;
							FDB::insert('pub_schedule',$schedule,true);
						}
					}
				}
				
				if($is_open)
				{
					if(function_exists('fsockopen'))
						$fp=fsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,5);
					elseif(function_exists('pfsockopen'))
						$fp=pfsockopen($_SERVER['HTTP_HOST'],80,$errno,$errstr,5);

					if($fp)
					{
						$request = "GET ".SITE_URL."login.php?loop=true&uid=".$uid." HTTP/1.0\r\n";
						$request .= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
						$request .= "Connection: Close\r\n\r\n";
						fwrite($fp, $request);
						while(!feof($fp))
						{
							fgets($fp, 128);
							break;
						}
						fclose($fp);
					}
				}
			}
		}
		else
		{
			$result['status'] = false;
		}
		return $result;
	}

	/*根据标签获取所属分类*/
	public function getCateTags($str,$cids)
	{
		global $_FANWE;
		if(count($cids) == 0)
			return array();
		
		$list = array();
		$str_tags = FS('Words')->segment($str,100);
		$cate_tags = array();
		
		foreach($cids as $cid)
		{
			$tag_key = 'goods_category_tagnames_'.$cid;
			FanweService::instance()->cache->loadCache($tag_key);
			$tags = $_FANWE['cache'][$tag_key];
			if(count($tags) > 0)
				$cate_tags = array_merge($cate_tags,$tags);
		}
		
		if(count($cate_tags) > 0)
			$cate_tags = array_unique($cate_tags);
		else
			return array();
		
		foreach($cate_tags as $tag)
		{
			if(strpos($str,$tag) !== FALSE)
			{
				$list[] = $tag;
			}
		}
		
		$cate_str = implode(',',str_replace(',','',$cate_tags));
		foreach($str_tags as $tag)
		{
			if(!empty($tag) && mb_strlen($tag,'UTF-8') > 1)
			{
				$index = strpos($cate_str,$tag);
				if($index !== FALSE)
				{
					if($index == 0)
						$list[] = $cate_tags[0];
					else
					{
						$index = substr_count(substr($cate_str,0,$index),',');
						$list[] = $cate_tags[$index];
					}
				}
			}
		}
		
		if(count($list) > 0)
			$list = array_unique($list);

		return $list;
	}

	public function deleteShare($share_id,$is_score = true,$update_user_cache = true)
	{
		$share = ShareService::getShareById($share_id);
		if(!empty($share))
		{
			$cache_data = fStripslashes(unserialize($share['cache_data']));
			$goods_count = 0;
			$photo_count = 0;
			switch($share['share_data'])
			{
				case 'goods':
					$goods_count = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('share_goods').' WHERE share_id = '.$share_id);
				break;
				
				case 'photo':
					$photo_count = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('share_photo').' WHERE share_id = '.$share_id);
				break;
				
				case 'goods_photo':
					$goods_count = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('share_goods').' WHERE share_id = '.$share_id);
					$photo_count = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('share_photo').' WHERE share_id = '.$share_id);
				break;
			}
			
			$collect_count = $share['collect_count'];

			$shop_list = array();
			if($goods_count > 0)
			{
				$res = FDB::query('SELECT shop_id FROM '.FDB::table('share_goods').' WHERE share_id = '.$share_id.' GROUP BY shop_id');
				while($shop = FDB::fetch($res))
				{
					$shop_list[] = $shop['shop_id'];
				}
			}
			
			if($goods_count > 0 || $photo_count > 0)
			{
				FDB::delete('share_user_images','share_id = '.$share_id);
				FDB::delete('share_tags','share_id = '.$share_id);
				FDB::delete('share_category','share_id = '.$share_id);
			}

			FDB::delete('share','share_id = '.$share_id);
			if($share['status'] == 0)
			{
				FDB::delete('share_cancel','share_id = '.$share_id);
				FDB::delete('share_check','share_id = '.$share_id);
			}
			else
			{
				if($goods_count > 0)
				{
					FDB::delete('share_goods','share_id = '.$share_id);
					FDB::delete('share_goods_index','share_id = '.$share_id);
					FDB::delete('share_goods_match','share_id = '.$share_id);
					FDB::delete('share_user_goods','share_id = '.$share_id);
					FDB::delete('shop_share','share_id = '.$share_id);
				}
				
				if($photo_count > 0)
				{
					FDB::delete('share_photo','share_id = '.$share_id);
					FDB::delete('share_photo_index','share_id = '.$share_id);
					FDB::delete('share_photo_match','share_id = '.$share_id);
					FDB::delete('share_user_photo','share_id = '.$share_id);
				}
					
				if(isset($cache_data['imgs']['dapei']))
				{
					FDB::delete('share_dapei_best','share_id = '.$share_id);
					FDB::delete('share_dapei_index','share_id = '.$share_id);
					FDB::delete('share_user_dapei','share_id = '.$share_id);
					if($goods_count > 0)
						FDB::delete('share_dapei_goods','share_id = '.$share_id);
					
					if($update_user_cache)
						FS('Dapei')->setUserCache($share['uid']);
				}
				
				if(isset($cache_data['imgs']['look']))
				{
					FDB::delete('share_look_best','share_id = '.$share_id);
					FDB::delete('share_look_index','share_id = '.$share_id);
					FDB::delete('share_user_look','share_id = '.$share_id);
					if($goods_count > 0)
						FDB::delete('share_look_goods','share_id = '.$share_id);
						
					if($update_user_cache)
						FS('Look')->setUserCache($share['uid']);
				}
				
				if($goods_count > 0 || $photo_count > 0)
				{
					FDB::delete('share_images_index','share_id = '.$share_id);
					FDB::delete('share_match','share_id = '.$share_id);
				}
				elseif(in_array($share['type'],array('default','comments','bar','bar_post')))
				{
					FDB::delete('share_text_index','share_id = '.$share_id);
					FDB::delete('share_text_match','share_id = '.$share_id);
				}
			}
			
			if($update_user_cache && ($share['type'] == 'album' || $share['type'] == 'album_item'))
				FS('Album')->setUserCache($share['uid']);
			
			if($share['type'] == 'fav')
			{
				FDB::delete('fav_me','share_id = '.$share_id);
			}
			
			if($share['comment_count'] > 0)
			{
				FDB::delete('share_comment','share_id = '.$share_id);
				FDB::delete('comment_me','share_id = '.$share_id);
			}
			
			if($collect_count > 0)
				FDB::delete('user_collect','share_id = '.$share_id);

			if(count($shop_list) > 0)
			{
				FS('Shop')->updateShopStatistic($shop_list);
			}
			
			$pattern = "/#([^\f\n\r\t\v]{1,80}?)#/";
			if(preg_match($pattern,$share['content']))
			{
				FS("Event")->deleteEvent($share_id);
			}
			
			$pattern = "/@([^\f\n\r\t\v@ ]{2,20}?)(?:\:| )/";
			if(preg_match($pattern,$share['content']))
			{
				FDB::delete('atme','share_id = '.$share_id);
			}
			
			if(defined('MANAGE_HANDLER') && MANAGE_HANDLER && $is_score)
			{
				if(!in_array($share['type'],array('fav','album_best','album_rec')))
				{
					if($share['rec_uid'] == 0 && in_array($share['share_data'],array('goods','photo','goods_photo')))
						FS("User")->updateUserScore($share['uid'],'delete_share','image',$share['content'],$share_id);
					else
						FS("User")->updateUserScore($share['uid'],'delete_share','default',$share['content'],$share_id);
				}	
			}
			
			FDB::query('UPDATE '.FDB::table('user_count').' SET
				shares = shares - 1,
				photos = photos - '.$photo_count.',
				goods = goods - '.$goods_count.',
				collects = collects - '.$collect_count.' WHERE uid = '.$share['uid'],'SILENT');

			ShareService::deleteShareCache();
			
			if(isset($cache_data['imgs']['all']))
			{
				foreach($cache_data['imgs']['all'] as $image)
				{
					FS('Image')->updateImageRel($image['img_id'],-1);
				}
			}
		}
	}

	public function deleteShareCache()
	{
		$key = getDirsById($share_id);
		clearCacheDir('share/'.$key);
	}

	/**
	 * 根据编号获取分享
	 * @param int $share_id 分享编号
	 * @return array
	 */
	public function getShareById($share_id,$is_static = true)
	{
		$share_id = (int)$share_id;
		if(!$share_id)
			return false;
		
		static $list = array();
		if(!isset($list[$share_id]) || !$is_static)
		{
			$share = FDB::fetchFirst('SELECT * FROM '.FDB::table('share').' WHERE share_id = '.$share_id);
			if($share)
				$share['url'] = FU('note/index',array('sid'=>$share_id));
			$list[$share_id] = $share;
		}
		return $list[$share_id];
	}

    /**
	 * 更新分享内容
	 * @param int $share_id 分享编号
	 * @return void
	 */
	public function updateShare($share_id,$title,$content)
	{
		if(empty($title) && empty($content))
			return;
		
		$data = array();
		if(!empty($title))
        	$data['title'] = $title;
			
		if(!empty($content))
        	$data['content'] = $content;
		
        FDB::update('share',$data,"share_id = '$share_id'");
        ShareService::updateShareMatch($share_id);
	}

	/**
	 * 获取分享详细
	 * @param int $share_id 分享编号
	 * @return array
	 */
	public function getShareDetail($share_id,$is_collect = false,$is_tag = false,$collect_count = 20)
	{
		$share = ShareService::getShareById($share_id);
		if($share)
		{
			$share['cache_data'] = fStripslashes(unserialize($share['cache_data']));
			$share['authoritys'] = ShareService::getIsEditShare($share);
			$share['time'] = getBeforeTimelag($share['create_time']);
			ShareService::shareImageFormat($share);
		}
		return $share;
	}

	/**
	 * 获取分享的动态数据
	 * @param int $share_id 分享编号
	 * @return array
	 */
	public function getShareDynamic($share_id)
	{
		$dynamic = FDB::fetchFirst('SELECT collect_count,comment_count,relay_count,click_count
				FROM '.FDB::table('share').'
				WHERE share_id = '.$share_id);
		return $dynamic;
	}

	/**
	 * 分享列表详细数据
	 * @param array $list 分享列表
	 * @param bool $is_parent 是否获取转发信息
	 * @param bool $is_collect 是否获取喜欢的会员
	 * @param bool $is_parent 是否获取分享标签
	 * @return array
	 */
	public function getShareDetailList($list,$is_parent = false,$is_collect = false,$is_tag = false,$is_comment = false,$comment_count = 10,$collect_count = 20,$is_user = false)
	{
		global $_FANWE;
		$shares = array();
		$share_ids = array();
		$rec_shares_ids = array();
		$share_users = array();
		$share_collects = array();
		$share_comments = array();
		$share_follows = array();
		
		foreach($list as $item)
		{
			$share_id = $item['share_id'];
			$share_ids[] = $share_id;
			$item['cache_data'] = fStripslashes(unserialize($item['cache_data']));
			$item['authoritys'] = ShareService::getIsEditShare($item);
			$item['time'] = getBeforeTimelag($item['create_time']);
			$item['url'] = FU('note/index',array('sid'=>$share_id));
			ShareService::shareImageFormat($item);
			$shares[$share_id] = $item;
			unset($shares[$share_id]['cache_data']);
			
			//分享会员
			if($is_user)
			{
				$shares[$share_id]['user'] = &$share_users[$item['uid']];
				if($item['rec_uid'] > 0)
					$shares[$share_id]['rec_user'] = &$share_users[$item['rec_uid']];
			}
			
			//分享评论
			if($is_comment)
			{
				$shares[$share_id]['comments'] = array();
				if(!empty($item['cache_data']['comments']))
				{
					$comment_ids = array_slice($item['cache_data']['comments'],0,$comment_count);
					foreach($comment_ids as $comment_id)
					{
						$shares[$share_id]['comments'][$comment_id] = &$share_comments[$comment_id];
					}
				}
			}
			
			//喜欢分享的会员
			if($is_collect)
			{
				$shares[$share_id]['collects'] = array();
				if(!empty($item['cache_data']['collects']))
				{
					$collect_ids = array_slice($item['cache_data']['collects'],0,$collect_count);
					foreach($collect_ids as $collect_uid)
					{
						if($is_user)
							$shares[$share_id]['collects'][$collect_uid] = &$share_users[$collect_uid];
						else
							$shares[$share_id]['collects'][$collect_uid] = $collect_uid;
					}
				}
			}

			if($is_tag)
			{
				$shares[$share_id]['is_eidt_tag'] = ShareService::getIsEditTag($item);
				$shares[$share_id]['tags'] = $item['cache_data']['tags'];
				ShareService::tagsFormat($shares[$share_id]['tags']['user']);
			}

			$shares[$share_id]['is_relay'] = false;
			$shares[$share_id]['is_parent'] = false;
			
			if($is_parent)
			{
				if($item['base_id'] > 0)
				{
					$shares[$share_id]['is_relay'] = true;
					$rec_shares_ids[$item['base_id']] = false;
					$shares[$share_id]['relay_share'] = &$rec_shares_ids[$item['base_id']];

					if($item['parent_id'] > 0 && $item['parent_id'] != $item['base_id'])
					{
						$shares[$share_id]['is_parent'] = true;
						$rec_shares_ids[$item['parent_id']] = false;
						$shares[$share_id]['parent_share'] = &$rec_shares_ids[$item['parent_id']];
					}
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
					$item['authoritys'] = ShareService::getIsEditShare($item);
					$item['time'] = getBeforeTimelag($item['create_time']);
					$item['url'] = FU('note/index',array('sid'=>$share_id));
					ShareService::shareImageFormat($item);
					$rec_shares_ids[$share_id] = $item;
					unset($rec_shares_ids[$share_id]['cache_data']);
					
					//分享会员
					if($is_user)
					{
						$rec_shares_ids[$share_id]['user'] = &$share_users[$item['uid']];
						if($item['rec_uid'] > 0)
							$rec_shares_ids[$share_id]['rec_user'] = &$share_users[$item['rec_uid']];
					}
					
					//分享评论
					if($is_comment)
					{
						$rec_shares_ids[$share_id]['comments'] = array();
						if(!empty($item['cache_data']['comments']))
						{
							$comment_ids = array_slice($item['cache_data']['comments'],0,$comment_count);
							foreach($comment_ids as $comment_id)
							{
								$rec_shares_ids[$share_id]['comments'][$comment_id] = &$share_comments[$comment_id];
							}
						}
					}
					
					//喜欢分享的会员
					if($is_collect)
					{
						$rec_shares_ids[$share_id]['collects'] = array();
						if(!empty($item['cache_data']['collects']))
						{
							$collect_ids = array_slice($item['cache_data']['collects'],0,$collect_count);
							foreach($collect_ids as $collect_uid)
							{
								if($is_user)
									$rec_shares_ids[$share_id]['collects'][$collect_uid] = &$share_users[$collect_uid];
								else
									$rec_shares_ids[$share_id]['collects'][$collect_uid] = $collect_uid;
							}
						}
					}
		
					if($is_tag)
					{
						$rec_shares_ids[$share_id]['is_eidt_tag'] = ShareService::getIsEditTag($item);
						$rec_shares_ids[$share_id]['tags'] = $item['cache_data']['tags'];
						ShareService::tagsFormat($rec_shares_ids[$share_id]['tags']['user']);
					}
				}
			}
		}
		
		$comment_ids = array_keys($share_comments);
		if(count($comment_ids) > 0)
		{
			$res = FDB::query("SELECT * FROM ".FDB::table('share_comment').' WHERE comment_id IN ('.implode(',',$comment_ids).')');
			while($item = FDB::fetch($res))
			{
				$item['time'] = getBeforeTimelag($item['create_time']);
				$share_comments[$item['comment_id']] = $item;
				if($is_user)
					$share_comments[$item['comment_id']]['user'] = &$share_users[$item['uid']];
			}
		}
		
		if($is_user)
			FS('User')->usersFormat($share_users);
		
		return $shares;
	}
	
	/**
	 * 获取分享的图片
	 */
	public function getShareImage($share_id,$data_type)
	{
		$share_id = (int)$share_id;
		$list = array();
		$img_ids = array();
		$goods_list = array();
		switch($data_type)
		{
			case 'goods':
				$sql = 'SELECT share_id,uid,id,goods_id,img_id,base_id,base_share  
					FROM '.FDB::table('share_goods').' 
					WHERE share_id = '.$share_id.' ORDER BY sort ASC';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					if($data['goods_id'] > 0)
					{
						$pkey = 'goods'.$data['id'];
						$list['all'][$pkey] = array();
						$list['all'][$pkey]['id'] = $data['id'];
						$list['all'][$pkey]['share_id'] = $data['share_id'];
						$list['all'][$pkey]['uid'] = $data['uid'];
						$list['all'][$pkey]['type'] = 'g';
						$list['all'][$pkey]['base_id'] = $data['base_id'];
						$list['all'][$pkey]['base_share'] = $data['base_share'];
						$goods_list[$data['goods_id']][] = &$list['all'][$pkey];
						$img_ids[$data['img_id']][] = &$list['all'][$pkey];
						$list['goods'][] = $pkey;
					}
				}
			break;
			case 'photo':
				$sql = 'SELECT share_id,uid,id,img_id,type,base_id,base_share 
					FROM '.FDB::table('share_photo').' 
					WHERE share_id = '.$share_id.' ORDER BY sort ASC';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$pkey = $data['type'].$data['id'];
					$list['all'][$pkey] = array();
					$list['all'][$pkey]['id'] = $data['id'];
					$list['all'][$pkey]['share_id'] = $data['share_id'];
					$list['all'][$pkey]['uid'] = $data['uid'];
					$list['all'][$pkey]['type'] = 'm';
					$list['all'][$pkey]['base_id'] = $data['base_id'];
					$list['all'][$pkey]['base_share'] = $data['base_share'];
					$img_ids[$data['img_id']][] = &$list['all'][$pkey];
					$list[$data['type']][] = $pkey;
				}
			break;
			case 'goods_photo':
				$sql = '(SELECT share_id,id,uid,img_id,goods_id,\'goods\' AS type,sort,base_id,base_share FROM '.FDB::table('share_goods').'
					WHERE share_id = '.$share_id.')
					UNION
					(SELECT share_id,id,uid,img_id,0 AS goods_id,type,sort,base_id,base_share FROM '.FDB::table('share_photo').'
					WHERE share_id = '.$share_id.')
					ORDER BY sort ASC';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$pkey = $data['type'].$data['id'];
					$list['all'][$pkey] = array();
					$list['all'][$pkey]['id'] = $data['id'];
					$list['all'][$pkey]['share_id'] = $data['share_id'];
					$list['all'][$pkey]['uid'] = $data['uid'];
					$list['all'][$pkey]['base_id'] = $data['base_id'];
					$list['all'][$pkey]['base_share'] = $data['base_share'];
					$img_ids[$data['img_id']][] = &$list['all'][$pkey];
					if($data['type'] == 'goods')
					{
						if($data['goods_id'] > 0)
						{
							$list['all'][$pkey]['type'] = 'g';
							$goods_list[$data['goods_id']][] = &$list['all'][$pkey];
							$list['goods'][] = $pkey;
						}
					}
					else
					{
						$list['all'][$pkey]['type'] = 'm';
						$list[$data['type']][] = $pkey;
					}
				}
			break;
		}
		
		FS('Image')->formatByIdKeys($img_ids);
		FS('Goods')->formatByIDKeys($goods_list,false);
		return $list;
	}

	/**
	 * 获取分享的图片集合
	 */
	public function getShareImages(&$share_datas)
	{
		foreach($share_datas as $share_data => $share_ids)
		{
			if($share_data == 'default' || count($share_ids) == 0)
				continue;
			
			$share_ids = array_keys($share_ids);
			$list = array();
			$goods_list = array();
			$img_ids = array();
			switch($share_data)
			{
				case 'goods':
					$sql = 'SELECT share_id,uid,id,goods_id,img_id,base_id,base_share 
						FROM '.FDB::table('share_goods').' 
						WHERE share_id IN ('.implode(',',$share_ids).') ORDER BY sort ASC';
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						if($data['goods_id'] > 0)
						{
							$pkey = 'goods'.$data['id'];
							$list[$data['share_id']]['all'][$pkey] = array();
							$list[$data['share_id']]['all'][$pkey]['id'] = $data['id'];
							$list[$data['share_id']]['all'][$pkey]['share_id'] = $data['share_id'];
							$list[$data['share_id']]['all'][$pkey]['uid'] = $data['uid'];
							$list[$data['share_id']]['all'][$pkey]['base_id'] = $data['base_id'];
							$list[$data['share_id']]['all'][$pkey]['base_share'] = $data['base_share'];
							$list[$data['share_id']]['all'][$pkey]['type'] = 'g';
							$goods_list[$data['goods_id']][] = &$list[$data['share_id']]['all'][$pkey];
							$img_ids[$data['img_id']][] = &$list[$data['share_id']]['all'][$pkey];
							$list[$data['share_id']]['goods'][] = $pkey;
						}
					}
				break;
				case 'photo':
					
					$sql = 'SELECT share_id,uid,id,img_id,type,base_id,base_share 
						FROM '.FDB::table('share_photo').' 
						WHERE share_id IN ('.implode(',',$share_ids).') ORDER BY sort ASC';
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$pkey = $data['type'].$data['id'];
						$list[$data['share_id']]['all'][$pkey] = array();
						$list[$data['share_id']]['all'][$pkey]['id'] = $data['id'];
						$list[$data['share_id']]['all'][$pkey]['share_id'] = $data['share_id'];
						$list[$data['share_id']]['all'][$pkey]['uid'] = $data['uid'];
						$list[$data['share_id']]['all'][$pkey]['base_id'] = $data['base_id'];
						$list[$data['share_id']]['all'][$pkey]['base_share'] = $data['base_share'];
						$list[$data['share_id']]['all'][$pkey]['type'] = 'm';
						$img_ids[$data['img_id']][] = &$list[$data['share_id']]['all'][$pkey];
						$list[$data['share_id']][$data['type']][] = $pkey;
					}
				break;
				case 'goods_photo':
					$img_ids = array();
					$goods_list = array();
					$sql = '(SELECT share_id,id,uid,img_id,goods_id,\'goods\' AS type,sort,base_id,base_share FROM '.FDB::table('share_goods').'
						WHERE share_id IN ('.implode(',',$share_ids).'))
						UNION
						(SELECT share_id,id,uid,img_id,0 AS goods_id,type,sort,base_id,base_share FROM '.FDB::table('share_photo').'
						WHERE share_id IN ('.implode(',',$share_ids).'))
						ORDER BY sort ASC';
						
					$res = FDB::query($sql);
					while($data = FDB::fetch($res))
					{
						$pkey = $data['type'].$data['id'];
						$list[$data['share_id']]['all'][$pkey] = array();
						$list[$data['share_id']]['all'][$pkey]['id'] = $data['id'];
						$list[$data['share_id']]['all'][$pkey]['share_id'] = $data['share_id'];
						$list[$data['share_id']]['all'][$pkey]['uid'] = $data['uid'];
						$list[$data['share_id']]['all'][$pkey]['base_id'] = $data['base_id'];
						$list[$data['share_id']]['all'][$pkey]['base_share'] = $data['base_share'];
						$img_ids[$data['img_id']][] = &$list[$data['share_id']]['all'][$pkey];
						if($data['type'] == 'goods')
						{
							if($data['goods_id'] > 0)
							{
								$list[$data['share_id']]['all'][$pkey]['type'] = 'g';
								$goods_list[$data['goods_id']][] = &$list[$data['share_id']]['all'][$pkey];
								$list[$data['share_id']]['goods'][] = $pkey;
							}
						}
						else
						{
							$list[$data['share_id']]['all'][$pkey]['type'] = 'm';
							$list[$data['share_id']][$data['type']][] = $pkey;
						}
					}
				break;
			}
			
			FS('Image')->formatByIdKeys($img_ids);
			FS('Goods')->formatByIDKeys($goods_list,false);
			
			foreach($list as $share_id => $item)
			{
				foreach($item['all'] as $ik => $img)
				{
					if($img['type'] == 'g')
					{
						$img['goods_url'] = $img['url'];
						if(empty($img['taoke_url']))
							$img['to_url'] = FU('tgo',array('url'=>$img['url']));
						else
							$img['to_url'] = FU('tgo',array('url'=>$img['taoke_url'],'uid'=>$img['uid'],'sid'=>$img['share_id'],'gid'=>$img['goods_id'],'kid'=>$img['keyid']));
	
						$img['price_format'] = priceFormat($img['price']);
					}
					
					$img['url'] = FU('note/'.$img['type'],array('sid'=>$img['share_id'],'id'=>$img['id']));
					$item['all'][$ik] = $img;
				}
				$share_datas[$share_data][$share_id] = $item;
			}
		}
	}
	
	public function shareImageFormat(&$share,$pic_num = 0)
	{		
		$images = $share['cache_data']['imgs'];
		if(isset($images['all']))
		{
			foreach($images['all'] as $ik => $img)
			{
				if($img['type'] == 'g')
				{
					$img['goods_url'] = $img['url'];
					if(empty($img['taoke_url']))
						$img['to_url'] = FU('tgo',array('url'=>$img['url']));
					else
						$img['to_url'] = FU('tgo',array('url'=>$img['taoke_url'],'uid'=>$img['uid'],'sid'=>$img['share_id'],'gid'=>$img['goods_id'],'kid'=>$img['keyid']));
	
					$img['price_format'] = priceFormat($img['price']);
				}
				
				$img['url'] = FU('note/'.$img['type'],array('sid'=>$img['share_id'],'id'=>$img['id']));
				$images['all'][$ik] = $img;
				$share['imgs'][] = $img;
				if($img['type'] != 'g')
					$share['photo_imgs'][] = $img;
			}
		}
		
		if(isset($images['goods']))
		{
			foreach($images['goods'] as $ik)
			{
				$share['goods_imgs'][] = $images['all'][$ik];
			}
		}
		
		if(isset($images['dapei']))
		{
			foreach($images['dapei'] as $ik)
			{
				$share['dapei_imgs'][] = $images['all'][$ik];
			}
		}
		
		if(isset($images['look']))
		{
			foreach($images['look'] as $ik)
			{
				$share['look_imgs'][] = $images['all'][$ik];
			}
		}
		
		if(isset($images['default']))
		{
			foreach($images['default'] as $ik)
			{
				$share['default_imgs'][] = $images['all'][$ik];
			}
		}
		
		if($pic_num > 0 && count($share['imgs']) > $pic_num)
			$share['imgs'] = array_slice($share['imgs'],0,$pic_num);
		unset($images);
	}

	/**
	 * 获取会员的上一个和下一个有图片分享
	 * @param array $_POST 提交的数据
	 * @return array(
			'prev'=>上一个分享,
			'next'=>下一个分享,
		)
	 */
	public function getPrevNextShares($uid,$share_id)
	{
		$arr = array('prev'=>0,'next'=>0);
		$uid = (int)$uid;
		$share_id = (int)$share_id;
		if(!$uid || !$share_id)
			return $arr;

		$arr['prev'] = (int)FDB::resultFirst('SELECT share_id FROM '.FDB::table('share_images_index').' 
			WHERE uid = '.$uid.' AND share_id < '.$share_id.' LIMIT 1');
		
		$arr['next'] = (int)FDB::resultFirst('SELECT share_id FROM '.FDB::table('share_images_index').' 
			WHERE uid = '.$uid.' AND share_id > '.$share_id.' LIMIT 1');
		return $arr;
	}
	
	public function updateShareCache($share_id,$type = 'all')
	{
		$share_id = (int)$share_id;
		if(!$share_id)
			return;
		
		$share = FDB::fetchFirst('SELECT cache_data,share_data FROM '.FDB::table('share').' WHERE share_id = '.$share_id);
		if(!$share)
			return;
			
		$cache_data = fStripslashes(unserialize($share['cache_data']));
		switch($type)
		{
			case 'tags':
				$cache_data['tags'] = ShareService::getShareTags($share_id,true);
			break;
			
			case 'collects':
				$cache_data['collects'] = ShareService::getShareCollectUser($share_id,50);
			break;
			
			case 'comments':
				$cache_data['comments'] = ShareService::getNewCommentIdsByShare($share_id,10);
			break;
			
			case 'imgs':
				$cache_data['imgs'] = ShareService::getShareImage($share_id,$share['share_data']);
			break;
			
			case 'all':
				$cache_data['tags'] = ShareService::getShareTags($share_id,true);
				$cache_data['collects'] = ShareService::getShareCollectUser($share_id,50);
				$cache_data['comments'] = ShareService::getNewCommentIdsByShare($share_id,10);
				$cache_data['imgs'] = ShareService::getShareImage($share_id,$share['share_data']);
			break;
		}
		unset($share['share_data']);
		$share['cache_data'] = addslashes(serialize($cache_data));
		FDB::update("share",$share,'share_id = '.$share_id);
	}

    public function updateShareMatch($share_id)
    {
        $share = ShareService::getShareById($share_id,false);
        if($share['status'] != 1 || (!in_array($share['share_data'],array('goods','photo','goods_photo')) && 
			!in_array($share['type'],array('default','comments','bar','bar_post'))))
            return;
		
		$share['cache_data'] = fStripslashes(unserialize($share['cache_data']));

		$content_match = clearExpress($share['content']);
        $content_match .= ' '.$share['title'];

        if(isset($share['cache_data']['tags']['user']))
        {
            foreach($share['cache_data']['tags']['user'] as $tag)
            {
				$content_match.=' '.$tag['tag_name'];
            }
        }

        if(isset($share['cache_data']['tags']['admin']))
        {
            foreach($share['cache_data']['tags']['admin'] as $tag)
            {
				$content_match.=' '.$tag['tag_name'];
            }
        }
		
		$is_rel_share = true;
        if(isset($share['cache_data']['imgs']['all']))
        {
            foreach($share['cache_data']['imgs']['all'] as $img)
            {
				if($img['base_id'] == 0)
				{
					$is_rel_share = false;
					if(!empty($img['name']))
						$content_match.=' '.$img['name'];
						
					if((int)$img['shop_id'] > 0)
					{
						$shop_name = FS('Shop')->getShopName($img['shop_id']);
						if(!empty($shop_name))
							$content_match.=' '.$shop_name;
					}
				}
            }
        }
		
        //保存匹配查询
        $share_match = array();
        $share_match['share_id'] = $share_id;
        $share_match['content_match'] = FS('Words')->segmentToUnicode($content_match);
		
		if(in_array($share['share_data'],array('goods','photo','goods_photo')))
		{
			if(!$is_rel_share)
			{
				FDB::insert("share_match",$share_match,false,true);
				switch($share['share_data'])
				{
					case 'goods':
						FDB::insert("share_goods_match",$share_match,false,true);
					break;
					
					case 'photo':
						FDB::insert("share_photo_match",$share_match,false,true);
					break;
					
					case 'goods_photo':
						FDB::insert("share_goods_match",$share_match,false,true);
						FDB::insert("share_photo_match",$share_match,false,true);
					break;
				}
			}
		}
		elseif(in_array($share['type'],array('default','comments','bar','bar_post')))
		{
			FDB::insert("share_text_match",$share_match,false,true);
		}
    }
	
	/**
	 * 改变分享审核状态
	 * @param int $share_id 分享编号
	 * @param int $status 分享状态
	 * @return array
	 */
	public function updateShareStatus($share_id,$status)
	{
		$share_id = (int)$share_id;
		$status = (int)$status;
		if($share_id == 0)
			return;
		
		$share = ShareService::getShareById($share_id);
		if(!$share)
			return;
		
		$type = $share['type'];
		$share_data = $share['share_data'];
		$is_rel_share = false;
		$goods_prices = array();
		$shop_ids = array();
		if(in_array($share_data,array('goods','photo','goods_photo')))
		{
			$is_rel_share = true;
			$cache_data = fStripslashes(unserialize($share['cache_data']));
			foreach($cache_data['imgs']['all'] as $image)
			{
				if($image['base_id'] == 0)
				{
					$is_rel_share = false;
					if(isset($image['price']) && (float)$image['price'] > 0)
						$goods_prices[] = (float)$image['price'];
						
					if(isset($image['shop_id']) && (float)$image['shop_id'] > 0)
						$shop_ids[] = $image['shop_id'];
				}
			}
		}
	
		$status = $status == 1 ? 1 : 0;
		
		FDB::query("UPDATE ".FDB::table("share")." SET status = $status WHERE share_id = $share_id");
		if($status == 1)
		{
			FS('Share')->updateShareMatch($share_id);
			if(in_array($share_data,array('goods','photo','goods_photo')))
			{
				if(!$is_rel_share)
				{
					$share_index = array();
					$share_index['share_id'] = $share_id;
					$share_index['uid'] = $share['uid'];
					$share_index['collect_count'] = $share['collect_count'];
					$share_index['collect_1count'] = $share['collect_1count'];
					$share_index['collect_7count'] = $share['collect_7count'];
					FDB::insert("share_images_index",$share_index);
	
					if(in_array($share_data,array('goods','goods_photo')))
					{
						$share_index['min_price'] = current($goods_prices);
						$share_index['max_price'] = end($goods_prices);
						FDB::insert("share_goods_index",$share_index);
						unset($share_index['min_price'],$share_index['max_price']);
					}
	
					if(in_array($share_data,array('photo','goods_photo')))
					{
						FDB::insert("share_photo_index",$share_index);
						if(isset($cache_data['imgs']['dapei']))
						{
							FDB::insert("share_dapei_index",$share_index);
							FS('Dapei')->setUserCache($share['uid']);
							if(isset($cache_data['imgs']['goods']))
								FDB::insert("share_dapei_goods",$share_index);
						}
	
						if(isset($cache_data['imgs']['look']))
						{
							FDB::insert("share_look_index",$share_index);
							FS('Look')->setUserCache($share['uid']);
							if(isset($cache_data['imgs']['goods']))
								FDB::insert("share_look_goods",$share_index);
						}
					}
				}
	
				if($type == 'album_item')
				{
					$album = FDB::fetchFirst('SELECT * FROM '.FDB::table('album_share').' WHERE share_id = '.$share_id);
					if($album)
					{
						$album_share = array();
						$album_share['album_id'] = $album['album_id'];
						$album_share['share_id'] = $share_id;
						$album_share['cid'] = $album['cid'];
						$album_share['collect_count'] = $share['collect_count'];
						$album_share['collect_1count'] = $share['collect_1count'];
						$album_share['collect_7count'] = $share['collect_7count'];
						FDB::insert("album_share_index",$album_share);
					}
				}
			}
			elseif(in_array($type,array('default','ask','comments','ask_post','bar','bar_post')))
			{
				$share_index = array();
				$share_index['share_id'] = $share_id;
				$share_index['uid'] = $share['uid'];
				FDB::insert("share_text_index",$share_index);
			}
	
			FS('Event')->saveEvent($share_id);
			FDB::delete('share_check','share_id = '.$share_id);
			FDB::delete('share_cancel','share_id = '.$share_id);
			
			if(count($shop_ids) > 0)
			{
				FS("Shop")->saveShopShare($shop_ids,$share_id,$share['uid']);
				FS("Shop")->updateShopStatistic($shop_ids);
			}
		}
		else
		{
			if(in_array($share_data,array('goods','photo','goods_photo')))
			{
				if(!$is_rel_share)
				{
					FDB::delete('share_match','share_id = '.$share_id);
					FDB::delete('share_images_index','share_id = '.$share_id);
	
					if(in_array($share_data,array('goods','goods_photo')))
					{
						FDB::delete('share_goods_match','share_id = '.$share_id);
						FDB::delete('share_goods_index','share_id = '.$share_id);
					}
	
					if(in_array($share_data,array('photo','goods_photo')))
					{
						FDB::delete('share_photo_match','share_id = '.$share_id);
						FDB::delete('share_photo_index','share_id = '.$share_id);
						if(isset($cache_data['imgs']['dapei']))
						{
							FDB::delete('share_dapei_index','share_id = '.$share_id);
							FDB::delete('share_dapei_best','share_id = '.$share_id);
							FS('Dapei')->setUserCache($share['uid']);
							if(isset($cache_data['imgs']['goods']))
								FDB::delete('share_dapei_goods','share_id = '.$share_id);
						}
	
						if(isset($cache_data['imgs']['look']))
						{
							FDB::delete('share_look_index','share_id = '.$share_id);
							FDB::delete('share_look_best','share_id = '.$share_id);
							FS('Look')->setUserCache($share['uid']);
							if(isset($cache_data['imgs']['goods']))
								FDB::delete('share_look_goods','share_id = '.$share_id);
						}
					}
				}
	
				if($type == 'album_item')
					FDB::delete('album_share_index','share_id = '.$share_id);
			}
			elseif(in_array($type,array('default','ask','comments','ask_post','bar','bar_post')))
			{
				FDB::delete('share_text_index','share_id = '.$share_id);
				FDB::delete('share_text_match','share_id = '.$share_id);
			}
	
			FS('Event')->deleteEvent($share_id);
			FDB::insert("share_cancel",array('share_id'=>$share_id,'uid'=>$share['uid']));
			
			if(count($shop_ids) > 0)
			{
				FS("Shop")->deleteShopShare($share_id);
				FS("Shop")->updateShopStatistic($shop_ids);
			}
		}
	}

	public function updateShareCate($share_id,$cids = array())
	{
		global $_FANWE;
		$share_id = (int)$share_id;
		if($share_id == 0)
			return;

		$share = ShareService::getShareById($share_id);
		if(!$share)
			return;

		FDB::query("delete from ".FDB::table("share_category")." where share_id = ".$share_id);
		if(count($cids) == 0)
			return;
		
		Cache::getInstance()->loadCache('goods_category');
		$cids = array_unique($cids);
		$share_cates = array();
		foreach($cids as $cid)
		{
			if((int)$cid > 0)
			{
				$cid = (int)$cid;
				if(isset($_FANWE['cache']['goods_category']['all'][$cid]))
				{
					$share_cates[] = $cid;
					if(isset($_FANWE['cache']['goods_category']['all'][$cid]['parents']) && 
						count($_FANWE['cache']['goods_category']['all'][$cid]['parents']) > 0)
					{
						$share_cates = array_merge($share_cates,$_FANWE['cache']['goods_category']['all'][$cid]['parents']);
					}
				}
			}
		}
		
		if(count($share_cates) == 0)
			return;
		
		$share_cates = array_unique($share_cates);
		foreach($share_cates as $cid)
		{
			$cate_data = array();
			$cate_data['share_id'] = $share_id;
			$cate_data['cate_id'] = $cid;
			$cate_data['uid'] = $share['uid'];
			FDB::insert('share_category',$cate_data);
		}
	}

	/**
	 * 获取是否可编辑分享
	 * @param int $share 分享
	 * @return array
	 */
	public function getIsEditShare(&$share)
	{
		static $edits = array();
		if(!isset($edits[$share['share_id']]))
		{
			global $_FANWE;
			$type = array('ask','bar');
			$is_edit = 0;
			$post = array('ask_post','bar_post');
			if(in_array($share['type'],$post))
			{
				if($share['uid'] == $_FANWE['uid'])
					$is_edit = 1;

				if($share['type'] == 'ask_post')
					$thread = FS('ask')->getTopicById($share['rec_id']);
				else
					$thread = FS('Topic')->getTopicById($share['rec_id']);

				if($thread['uid'] == $_FANWE['uid'])
					$is_edit = 2;
			}
			else
			{
				if(!in_array($share['type'],$type) && $share['uid'] == $_FANWE['uid'])
					$is_edit = 1;
			}

			$edits[$share['share_id']] = $is_edit;
		}

		return $edits[$share['share_id']];
	}
	/*===========分享列表、详细 END  ==============*/

	/*===========分享标签 BEGIN  ==============*/
	/**
	 * 获取是否可编辑分享标签
	 * @param int $share 分享
	 * @return array
	 */
	public function getIsEditTag(&$share)
	{
		global $_FANWE;
		if($_FANWE['setting']['share_is_tag'] == 0)
			return false;

		$_img_data = array('goods','photo','goods_photo');
		$is_edit_tag = false;
		if(in_array($share['share_data'],$_img_data) && $share['uid'] == $_FANWE['uid'])
			$is_edit_tag = true;
		return $is_edit_tag;
	}

	/**
	 * 获取分享标签
	 * @param int $share_id 分享编号
	 * @return array
	 */
	public function getShareTags($share_id,$is_update = false)
	{
		$share_id = (int)$share_id;
		if(!$share_id)
			return array();
		
		static $list = array();
		if(!isset($list[$share_id]) || $is_update)
		{
			$res = FDB::query('SELECT tag_name,is_admin
				FROM '.FDB::table('share_tags').'
				WHERE share_id = '.$share_id);
			while($data = FDB::fetch($res))
			{
				$data['tag_name'] = addslashes($data['tag_name']);
				if($data['is_admin'] == 0)
					$list[$share_id]['user'][] = $data;
				else
					$list[$share_id]['admin'][] = $data;
			}
		}
		
		return $list[$share_id];
	}
	
	public function tagsFormat(&$tags)
	{
		if(isset($tags))
		{
			foreach($tags as $tk => $tag)
			{
				$tags[$tk]['url'] = FU('book/shopping',array('tag'=>urlencode($tag['tag_name'])));
			}
		}
	}

	/**
	 * 更新分享标签缓存
	 * @param int $share_id 分享编号
	 * @param array $tags = array(
	 		'user'=>会员设置标签,
			'admin'=>管理员设置标签,(如果不存在admin键名，则不删除会员设置标签)
	 	);
	 * @return array
	 */
	public function updateShareTags($share_id,$tags)
	{
		global $_FANWE;
		//更新分享的会员标签
		FDB::delete('share_tags','share_id = '.$share_id.' AND is_admin = 0');
		if(isset($tags['user']))
		{
			$tags['user'] = str_replace('　',' ',$tags['user']);
			$tags['user'] = explode(' ',htmlspecialchars(trim($tags['user'])));
            $tags['user'] = array_unique($tags['user']);
            $tags['user'] = array_slice($tags['user'],0,$_FANWE['setting']['share_tag_count']);

			$share_tags = array();
			foreach($tags['user'] as $tag)
			{
				if(trim($tag) != '' && !in_array($tag,$share_tags))
				{
					array_push($share_tags,$tag);

					/*//为已存在的tags更新统计
					FDB::query('UPDATE '.FDB::table('goods_tags').'
						SET count = count + 1
						WHERE tag_name = \''.$tag.'\'');

					//数量大于100时为热门标签
					FDB::query('UPDATE '.FDB::table('goods_tags').'
						SET is_hot = 1
						WHERE tag_name = \''.$tag.'\' AND count >= 100');*/

					$tag_data = array();
					$tag_data['share_id'] = $share_id;
					$tag_data['tag_name'] = $tag;
					FDB::insert('share_tags',$tag_data);
				}
			}
			ShareService::updateShareCache($share_id,'tags');
		}

		//更新分享的管理员标签
		if(isset($tags['admin']))
		{
			FDB::delete('share_tags','share_id = '.$share_id.' AND is_admin = 1');

			$tags['admin'] = str_replace('　',' ',$tags['admin']);
			$tags['admin'] = explode(' ',htmlspecialchars(trim($tags['admin'])));
            $tags['admin'] = array_unique($tags['admin']);

			$share_tags = array();
			foreach($tags['admin'] as $tag)
			{
				if(trim($tag) != '' && !in_array($tag,$share_tags))
				{
					array_push($share_tags,$tag);

					/*//为已存在的tags更新统计
					FDB::query('UPDATE '.FDB::table('goods_tags').'
						SET count = count + 1
						WHERE tag_name = \''.$tag.'\'');

					//数量大于100时为热门标签
					FDB::query('UPDATE '.FDB::table('goods_tags').'
						SET is_hot = 1
						WHERE tag_name = \''.$tag.'\' AND count >= 100');*/

					$tag_data = array();
					$tag_data['share_id'] = $share_id;
					$tag_data['tag_name'] = $tag;
					$tag_data['is_admin'] = 1;
					FDB::insert('share_tags',$tag_data);
				}
			}
		}
        ShareService::updateShareMatch($share_id);
	}
	/*===========分享标签 END  ==============*/

	/*===========分享转发 BEGIN  ==============*/
	/**
	 * 转发分享
	 * @param array $_POST 提交的数据
	 * @return array(
			'share_id'=>分享编号,
			'pc_id'=>评论编号(如果勾选评论给转发分享),
			'bc_id'=>原文评论编号(如果勾选评论给原文分享),
		)
	 */
	public function saveRelay($_POST)
	{
		global $_FANWE;
		$share_id = intval($_POST['share_id']);
		$share = ShareService::getShareById($share_id);
		if(empty($share))
			return false;

		$data = array();
		$data['share']['uid'] = $_FANWE['uid'];
		$data['share']['parent_id'] = $share_id;
		$content = htmlspecialchars(trim($_POST['content']));
		$data['share']['content'] = $content;
		$type = 'default';
		$base_id = $share['base_id'];
		if($base_id > 0)
		{
			$base = ShareService::getShareById($share['base_id']);
			if(!empty($base))
				$base_id = $base['share_id'];
			else
				$base_id = 0;
		}

		$rec_id = $share['rec_id'];

		if($share['type'] == 'bar' || $share['type'] == 'bar_post')
			$type = 'bar_post';

		$data['share']['rec_id'] = $share['rec_id'];
		$data['share']['title'] = addslashes($share['title']);
		$data['share']['base_id'] = $base_id > 0 ? $base_id : $share_id;
		$data['share']['type'] = $type;

		$relay_share = ShareService::save($data);
		if(!$relay_share['status'])
			return false;

		FDB::query('UPDATE '.FDB::table('share').'
			SET relay_count = relay_count + 1
			WHERE share_id = '.$share_id);

		if($base_id > 0 && $share_id != $base_id)
		{
			FDB::query('UPDATE '.FDB::table('share').'
				SET relay_count = relay_count + 1
				WHERE share_id = '.$base_id);
		}

		$is_no_post = isset($_POST['is_no_post']) ? intval($_POST['is_no_post']) : 0;
		$share_id = $relay_share['share_id'];
		if($rec_id > 0 && $is_no_post == 0)
		{
			if($type == 'bar_post')
				FS('Topic')->saveTopicPost($rec_id,$content,$share_id);
		}

		$is_comment_parent = isset($_POST['is_comment_parent']) ? intval($_POST['is_comment_parent']) : 0;
		$is_comment_base = isset($_POST['is_comment_base']) ? intval($_POST['is_comment_base']) : 0;

		//评论给分享
		$parent_comment_id = 0;
		if($is_comment_parent == 1)
		{
			$data = array();
			$data['content'] = 	$_POST['content'];
			$data['share_id'] = $share['share_id'];
			$parent_comment_id = ShareService::saveComment($data);
		}

		//评论给原创分享
		$base_comment_id = 0;
		if($is_comment_base == 1 && $base_id > 0)
		{
			$data = array();
			$data['content'] = 	$_POST['content'];
			$data['share_id'] = $base_id;
			$base_comment_id = ShareService::saveComment($data);
		}

		return array(
			'share_id'=>$share_id,
			'pc_id'=>$parent_comment_id,
			'bc_id'=>$base_comment_id,
		);
	}

	/*===========分享转发 END  ==============*/

	/*===========喜欢收藏分享 BEGIN  ==============*/
	/**
	 * 保存喜欢分享
	 * @param int $share 分享
	 * @return void
	 */
	public function saveFav($share)
	{
		if($share['type'] == 'fav')
			return false;

		global $_FANWE;
		ShareService::setShareCollectUser($share['share_id'],$share['uid']);

		$base_id = $share['base_id'];
		if($base_id > 0)
		{
			$base = ShareService::getShareById($share['base_id']);
			if(!empty($base))
			{
				ShareService::setShareCollectUser($base['share_id'],$base['uid']);
				$base_id = $base['share_id'];
			}
			else
				$base_id = 0;
		}

		$share_user = FS('User')->getUserCache($share['uid']);
		$data = array();
		$data['share']['uid'] = $_FANWE['uid'];
		$data['share']['rec_id'] = $share['rec_id'];
		$data['share']['parent_id'] = $share['share_id'];
		$data['share']['content'] = lang('share','fav_share').'//@'.$share_user['user_name'].':'.$share['content'];
		$data['share']['type'] = "fav";
		$data['share']['base_id'] = $base_id > 0 ? $base_id : $share['share_id'];
		
		//添加关注消息提示
		FS("User")->setUserTips($share['uid'],2);
		$favshare = ShareService::save($data);
		if($favshare['status'])
		{
			$data = array();
			$data['share_id'] = $favshare['share_id'];
			$data['uid'] = $share['uid'];
			$data['parent_id'] = $share['share_id'];
			$data['cuid'] = $_FANWE['uid'];
			FDB::insert('fav_me',$data);
		}
	}

	/**
	 * 获取喜欢这个分享的会员
	 * @param int $share_id 分享编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getShareCollectUser($share_id,$num = 12)
	{
		$num = (int)$num;
		$share_id = (int)$share_id;
		if($num == 0)
			$num = 500;
			
		if($share_id == 0)
			return array();
		
		$uids = array();
		$res = FDB::query('SELECT c_uid FROM '.FDB::table('user_collect').'
			WHERE share_id = '.$share_id.'
			ORDER BY create_time DESC LIMIT 0,'.$num);

		while($data = FDB::fetch($res))
		{
			$uids[$data['c_uid']] = $data['c_uid'];
		}
		
		return $uids;
	}

	/**
	 * 添加喜欢这个分享的会员
	 * @param int $share_id 分享编号
	 * @param int $uid 会员会员数量
	 * @return array
	 */
	public function setShareCollectUser($share_id,$uid)
	{
		$share_id = (int)$share_id;
		$uid = (int)$uid;
		
		if(!$share_id || !$uid)
			return false;
			
		global $_FANWE;
		
		$share = ShareService::getShareById($share_id);
		if(empty($share))
			return false;
		
		$c_uid = $_FANWE['uid'];
		$data = array();
		$data['uid'] = $uid;
		$data['c_uid'] = $c_uid;
		$data['share_id'] = $share_id;
		$data['create_time'] = TIME_UTC;
		FDB::insert('user_collect',$data);
		
		FDB::insert('share_collect1',array('share_id'=>$share_id),false,true);
		FDB::insert('share_collect7',array('share_id'=>$share_id),false,true);

		//分享被喜欢数加1
		ShareService::updateShareCollectTable($share_id);
		
		//分享会员被喜欢数加1
		FDB::query('UPDATE '.FDB::table('user_count').'
			SET collects = collects + 1
			WHERE uid = '.$uid);
		
		FS('Medal')->runAuto($uid,'collects');
		ShareService::updateShareCache($share_id,'collects');
	}

	/**
	 * 获取会员是否已喜欢这个分享
	 * @param int $share_id 分享编号
	 * @return array
	 */
	public function getIsCollectByUid($share_id,$uid)
	{
		$share_id = (int)$share_id;
		$uid = (int)$uid;
		
		if(!$share_id || !$uid)
			return false;
		
		$count = FDB::resultFirst('SELECT COUNT(*) FROM '.FDB::table('user_collect').'
				WHERE share_id = '.$share_id.' AND c_uid = '.$uid);
		
		if((int)$count == 0)
			return false;
		else
			return true;
	}

	public function deleteShareCollectUser($share_id,$uid)
	{
		$share_id = (int)$share_id;
		$uid = (int)$uid;
		
		if(!$share_id || !$uid)
			return false;
			
		$share = ShareService::getShareById($share_id,false);
		if(empty($share))
			return false;

		FDB::query('DELETE FROM '.FDB::table('user_collect').'
			WHERE c_uid = '.$uid.' AND share_id = '.$share_id);

		//分享被喜欢数减1
		ShareService::updateShareCollectTable($share_id,false);

		//分享会员被喜欢数减1
		FDB::query('UPDATE '.FDB::table('user_count').'
			SET collects = collects - 1
			WHERE uid = '.$share['uid'].' and collects >=1 ');
		
		FS('Medal')->runAuto($share['uid'],'collects');
		ShareService::updateShareCache($share_id,'collects');
		return true;
	}
	
	public function updateShareCollectTable($share_id,$is_add = true)
	{
		$share = ShareService::getShareById($share_id);
		$cache_data = fStripslashes(unserialize($share['cache_data']));
		
		if($is_add)
			FDB::query('UPDATE '.FDB::table('share').' SET collect_count = collect_count + 1,
				collect_1count = collect_1count + 1,
				collect_7count = collect_7count + 1
				WHERE share_id = '.$share_id);
		else
		{
			$collect_count = (int)$share['collect_count'] - 1;
			if($collect_count < 0)
				$collect_count = 0;
				
			$collect_1count = (int)$share['collect_1count'] - 1;
			if($collect_1count < 0)
				$collect_1count = 0;
				
			$collect_7count = (int)$share['collect_7count'] - 1;
			if($collect_7count < 0)
				$collect_7count = 0;
			
			FDB::query('UPDATE '.FDB::table('share').' SET collect_count = '.$collect_count.',
				collect_1count = '.$collect_1count.',
				collect_7count = '.$collect_7count.' WHERE share_id = '.$share_id,'SILENT');
		}
		
		$tables1 = array();
		$tables = array();
		
		if(in_array($share['share_data'],array('goods','photo','goods_photo')))
			$tables1[] = 'share_user_images';
				
		if(in_array($share['share_data'],array('goods','goods_photo')))
			$tables1[] = 'share_user_goods';
		
		if(in_array($share['share_data'],array('photo','goods_photo')))
			$tables1[] = 'share_user_photo';
			
		if(isset($cache_data['imgs']['dapei']))
			$tables1[] = 'share_user_dapei';
			
		if(isset($cache_data['imgs']['look']))
			$tables1[] = 'share_user_look';
		
		if($share['status'] == 1)
		{
			if(in_array($share['share_data'],array('goods','photo','goods_photo')))
				$tables[] = 'share_images_index';
			
			if(in_array($share['share_data'],array('goods','goods_photo')))
				$tables[] = 'share_goods_index';
			
			if(in_array($share['share_data'],array('photo','goods_photo')))
				$tables[] = 'share_photo_index';
			
			if(isset($cache_data['imgs']['dapei']))
			{
				$tables[] = 'share_dapei_index';
				if(in_array($share['share_data'],array('goods','goods_photo')))
					$tables[] = 'share_dapei_goods';
			}
			
			if(isset($cache_data['imgs']['look']))
			{
				$tables[] = 'share_look_index';
				if(in_array($share['share_data'],array('goods','goods_photo')))
					$tables[] = 'share_look_goods';
			}
			
			if($share['type'] == 'album_item')
				$tables[] = 'album_share_index';
		}
		
		//为专辑添加喜欢
		if($share['type'] == 'album_item')
		{
			$album_share = (int)FDB::resultFirst('SELECT share_id FROM '.FDB::table('album').' WHERE id = '.$share['rec_id']);
			if($is_add)
			{
				FDB::query('UPDATE '.FDB::table('share').' SET collect_count = collect_count + 1 WHERE share_id = '.$album_share);
				FDB::query('UPDATE '.FDB::table('album').' SET collect_count = collect_count + 1 WHERE id = '.$share['rec_id']);
			}
			else
			{
				FDB::query('UPDATE '.FDB::table('share').' SET collect_count = collect_count - 1 WHERE share_id = '.$album_share);
				FDB::query('UPDATE '.FDB::table('album').' SET collect_count = collect_count - 1 WHERE id = '.$share['rec_id']);
			}
		}
		
		if($share['type'] == 'album')
		{
			if($is_add)
				FDB::query('UPDATE '.FDB::table('album').' SET collect_count = collect_count + 1 WHERE share_id = '.$share_id);
			else
				FDB::query('UPDATE '.FDB::table('album').' SET collect_count = collect_count - 1 WHERE share_id = '.$share_id);
		}
		
		foreach($tables1 as $table)
		{
			if($is_add)
				FDB::query('UPDATE '.FDB::table($table).' SET collect_count = collect_count + 1 WHERE share_id = '.$share_id);
			else
				FDB::query('UPDATE '.FDB::table($table).' SET collect_count = '.$collect_count.' 
					WHERE share_id = '.$share_id);
		}
		
		foreach($tables as $table)
		{
			if($is_add)
				FDB::query('UPDATE '.FDB::table($table).' SET collect_count = collect_count + 1,collect_1count = collect_1count + 1,
					collect_7count = collect_7count + 1  WHERE share_id = '.$share_id);
			else
				FDB::query('UPDATE '.FDB::table($table).' SET collect_count = '.$collect_count.',collect_1count = '.$collect_1count.',
					collect_7count = '.$collect_7count.' WHERE share_id = '.$share_id);
		}
	}
	/*===========喜欢收藏分享 END  ==============*/

	/*===========分享评论 BEGIN  ==============*/
	/**
	 * 获取是否可删除评论
	 * @param int $share 分享
	 * @return array
	 */
	public function getIsRemoveComment(&$share)
	{
		global $_FANWE;
		$is_bln = false;
		if($share['uid'] == $_FANWE['uid'])
			$is_bln = true;
		return $is_bln;
	}

	/**
	 * 保存分享的评论
	 * @param array $_POST 提交的数据
	 * @return int 评论编号
	 */
	public function saveComment($_POST)
	{
		global $_FANWE;
		$share_id = intval($_POST['share_id']);
		$share = ShareService::getShareById($share_id);
		$data = array();
		$data['content'] = 	htmlspecialchars(trim($_POST['content']));
		$data['uid'] = $_FANWE['uid'];
		$data['parent_id'] = intval($_POST['parent_id']);
		$data['share_id'] = $share_id;
		$data['create_time'] = TIME_UTC;
		$comment_id = FDB::insert('share_comment',$data,true);

		$is_relay = isset($_POST['is_relay']) ? intval($_POST['is_relay']) : 0;
		//转发分享
		if($is_relay == 1)
		{
			if($share['base_id'] > 0)
			{
				$share_user = FS('User')->getUserCache($share['uid']);
				$_POST['content'] = trim($_POST['content']).'//@'.$share_user['user_name'].':'.$share['content'];
			}
			//添加评论消息提示
			$result = FDB::query("INSERT INTO ".FDB::table('user_notice')."(uid, type, num, create_time) VALUES('$share[uid]',3,1,'".TIME_UTC."')", 'SILENT');
			if(!$result)
				FDB::query("UPDATE ".FDB::table('user_notice')." SET num = num + 1, create_time='".TIME_UTC."' WHERE uid='$share[uid]' AND type=3");
			
			ShareService::saveRelay($_POST);
		}
		
		$comment_me = array();
		$comment_me['comment_id'] = $comment_id;
		$comment_me['uid'] = $share['uid'];
		$comment_me['share_id'] = $share_id;
		FDB::insert('comment_me',$comment_me);

		//分享评论数量加1
		FDB::query('UPDATE '.FDB::table('share').'
			SET comment_count = comment_count + 1
			WHERE share_id = '.$share_id);

		//清除分享评论列表缓存
		ShareService::updateShareCache($share_id,'comments');
		return $comment_id;
	}

	/**
	 * 获取评论
	 * @param int $comment_id
	 * @return array
	 */
	public function getShareComment($comment_id)
	{
		return FDB::fetchFirst('SELECT *
			FROM '.FDB::table("share_comment").'
			WHERE comment_id = '.$comment_id);
	}

	/**
	 * 删除分享评论
	 * @param int $comment_id 评论编号
	 * @return void
	 */
	public function deleteShareComment($comment_id)
	{
		$comment = ShareService::getShareComment($comment_id);
		if(empty($comment))
			return;

		FDB::delete('share_comment','comment_id = '.$comment_id);
		FDB::delete('comment_me','comment_id = '.$comment_id);
		$share_id = $comment['share_id'];
		//分享评论数量减1
		FDB::query('UPDATE '.FDB::table('share').'
			SET comment_count = comment_count - 1
			WHERE share_id = '.$share_id.' and comment_count >=1 ');

		//清除分享评论列表缓存
		ShareService::updateShareCache($share_id,'comments');
	}
	
	public function getShareComments($share_id,$count = 10)
	{
		return ShareService::getShareCommentList($share_id,'0,'.(int)$count);
	}

	/**
	 * 获取分享的最新评论列表
	 * @param int $share_id 分享编号
	 * @param int $count 数量
	 * @return array
	 */
	public function getNewCommentIdsByShare($share_id,$count = 10)
	{
		$list = array();
		$res = FDB::query('SELECT comment_id 
			FROM '.FDB::table("share_comment").'
			WHERE share_id = '.$share_id.'
			ORDER BY comment_id DESC LIMIT 0,'.$count);
		while($data = FDB::fetch($res))
		{
			$list[] = $data['comment_id'];
		}
		return $list;
	}
	
	public function commentsFormat(&$comments)
	{
		if($comments)
		{
			$comment_uids = array();
			foreach($comments as $key => $comment)
			{
				$comment['user'] = &$comment_uids[$comment['uid']];
				$comment['time'] = getBeforeTimelag($comment['create_time']);
				$comments[$key] = $comment;
			}
			FS('User')->usersFormat($comment_uids);
		}
	}

	/**
	 * 获取分享的分页评论列表
	 * @param int $share_id 分享编号
	 * @param int $count 分页
	 * @return array
	 */
	public function getShareCommentList($share_id,$limit = '0,10')
	{
		$comments = FDB::fetchAll('SELECT *
			FROM '.FDB::table("share_comment").'
			WHERE share_id = '.$share_id.'
			ORDER BY comment_id DESC LIMIT '.$limit);
		
		if($comments)
		{
			ShareService::commentsFormat($comments);
			return $comments;
		}
		else
			return array();
	}
	/*===========分享评论 END  ==============*/
	
	/**
	 * 获取喜欢这个分享的会员还喜欢的分享（商品分享）
	 * @param int $share_id 分享编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getCollectShareByShare($share_id,$num = 20)
	{
		$share_id = (int)$share_id;
		if(!$share_id)
			return array();
		
		$key = 'share/'.getDirsById($share_id).'/csbs/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$uids = ShareService::getShareCollectUser($share_id,0);

			if(count($uids) > 0)
			{
				$share_ids = array();
				$res = FDB::query('SELECT GROUP_CONCAT(DISTINCT sgi.share_id
						ORDER BY sgi.share_id DESC SEPARATOR \',\') AS share_ids,sgi.uid
					FROM '.FDB::table('user_collect').' AS uc
					INNER JOIN '.FDB::table('share_goods_index').' AS sgi ON sgi.share_id = uc.share_id AND sgi.share_id <> '.$share_id.' 
					WHERE uc.c_uid IN ('.implode(',',$uids).') GROUP BY sgi.uid LIMIT 0,'.$num);
				while($data = FDB::fetch($res))
				{
					$ids = explode(',',$data['share_ids']);
					$id = (int)current($ids);
					if($id > 0)
						$share_ids[] = $id;
				}
				
				$share_ids = array_unique($share_ids);
				if(count($share_ids) > 0)
				{
					$list = FDB::fetchAll('SELECT share_id,uid,content,collect_count,comment_count,create_time,cache_data FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',$share_ids).') LIMIT 0,'.$num);
					$list = ShareService::getShareDetailList($list);
				}
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取会员喜欢的分享（商品分享）
	 * @param int $uid 会员编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getCollectShareByUser($uid,$num = 10)
	{
		$uid = (int)$uid;
		if(!$uid)
			return array();
		
		$key = 'user/'.getDirsById($uid).'/csbu/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$share_ids = array();
			$res = FDB::query('SELECT GROUP_CONCAT(DISTINCT sgi.share_id
					ORDER BY sgi.share_id DESC SEPARATOR \',\') AS share_ids,sgi.uid
				FROM '.FDB::table('user_collect').' AS uc
				INNER JOIN '.FDB::table('share_goods_index').' AS sgi ON sgi.share_id = uc.share_id  
				WHERE uc.c_uid = '.$uid.' GROUP BY sgi.uid LIMIT 0,'.$num);
			while($data = FDB::fetch($res))
			{
				$ids = explode(',',$data['share_ids']);
				$id = (int)current($ids);
				if($id > 0)
					$share_ids[] = $id;
			}
			
			$share_ids = array_unique($share_ids);
			if(count($share_ids) > 0)
			{
				$list = FDB::fetchAll('SELECT share_id,uid,content,collect_count,comment_count,create_time,cache_data FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',$share_ids).')');
				$list = ShareService::getShareDetailList($list);
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取会员最被喜欢的宝贝分享
	 * @param int $uid 会员编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getBestCollectGoodsShareByUser($uid,$num = 9)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return array();

		$key = 'user/'.getDirsById($uid).'/bcgs/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$res = FDB::query('SELECT sgi.share_id,s.cache_data FROM '.FDB::table('share_goods_index').' AS sgi 
				INNER JOIN '.FDB::table('share').' AS s ON s.share_id = sgi.share_id 
				WHERE sgi.uid ='.$uid.' ORDER BY sgi.collect_count DESC,sgi.share_id DESC LIMIT 0,'.$num);
			while($data = FDB::fetch($res))
			{
				$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
				ShareService::shareImageFormat($data);
				$share = array();
				$share['share_id'] = $data['share_id'];
				$img = current($data['goods_imgs']);
				$share['img'] = $img['img'];
				$share['url'] = $img['url'];
				$list[] = $share;
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取会员喜欢的宝贝分享
	 * @param int $uid 会员编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getUserFavGoodsShare($uid,$num = 9)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return array();

		$key = 'user/'.getDirsById($uid).'/ufgs/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$res = FDB::query('SELECT sgi.share_id,s.cache_data 
				FROM '.FDB::table('user_collect').' AS uc 
				INNER JOIN '.FDB::table('share_goods_index').' AS sgi ON sgi.share_id = uc.share_id 
				INNER JOIN '.FDB::table('share').' AS s ON s.share_id = sgi.share_id 
				WHERE uc.c_uid = '.$uid.' ORDER BY sgi.share_id DESC LIMIT 0,'.$num);
			while($data = FDB::fetch($res))
			{
				$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
				ShareService::shareImageFormat($data);
				$share = array();
				$share['share_id'] = $data['share_id'];
				$img = current($data['goods_imgs']);
				$share['img'] = $img['img'];
				$share['url'] = $img['url'];
				$list[] = $share;
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取会员最被喜欢的照片分享
	 * @param int $uid 会员编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getBestCollectPhotoShareByUser($uid,$num = 9)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return array();

		$key = 'user/'.getDirsById($uid).'/bcps/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$res = FDB::query('SELECT spi.share_id,s.cache_data FROM '.FDB::table('share_photo_index').' AS spi 
				INNER JOIN '.FDB::table('share').' AS s ON s.share_id = spi.share_id 
				WHERE spi.uid ='.$uid.' ORDER BY spi.collect_count DESC,spi.share_id DESC LIMIT 0,'.$num);
			while($data = FDB::fetch($res))
			{
				$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
				ShareService::shareImageFormat($data);
				$share = array();
				$share['share_id'] = $data['share_id'];
				$img = current($data['photo_imgs']);
				$share['img'] = $img['img'];
				$share['url'] = $img['url'];
				$list[] = $share;
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取会员喜欢的照片分享
	 * @param int $uid 会员编号
	 * @param int $num 获取数量
	 * @return array
	 */
	public function getUserFavPhotoShare($uid,$num = 9)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return array();

		$key = 'user/'.getDirsById($uid).'/ufps/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$res = FDB::query('SELECT spi.share_id,s.cache_data 
				FROM '.FDB::table('user_collect').' AS uc 
				INNER JOIN '.FDB::table('share_photo_index').' AS spi ON spi.share_id = uc.share_id 
				INNER JOIN '.FDB::table('share').' AS s ON s.share_id = spi.share_id 
				WHERE uc.c_uid = '.$uid.' ORDER BY spi.share_id DESC LIMIT 0,'.$num);
			while($data = FDB::fetch($res))
			{
				$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
				ShareService::shareImageFormat($data);
				$share = array();
				$share['share_id'] = $data['share_id'];
				$img = current($data['photo_imgs']);
				$share['img'] = $img['img'];
				$share['url'] = $img['url'];
				$list[] = $share;
			}
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	/**
	 * 获取当前的最新商品分享
	 * @param int $num 获取数量
	 * @param int $pic_num 获取图片数量
	 * @return int
	 */
	public function getNewShare($num = 20)
	{
        $sql = 'SELECT s.share_id,s.uid,s.content,s.collect_count,s.comment_count,s.create_time,s.cache_data 
			FROM '.FDB::table('share_goods_index').' AS sgi 
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = sgi.share_id 
			ORDER BY sgi.share_id DESC LIMIT 0,'.$num;
        
        $list = FDB::fetchAll($sql);
		$list = ShareService::getShareDetailList($list);
		return $list;
	}

	public function getChildCids($rid,&$cids)
	{
		global $_FANWE;
		$root_cate = $_FANWE['cache']['goods_category']['all'][$rid];
		$cids = $root_cate['childs'];
		$cids[] = $rid;
	}
	
	public function getPhotoListByType($type,$num = 6)
	{
		$table = 'share_dapei_index';
		$btable = 'share_look_best';
		if($type == 'look')
		{
			$table = 'share_dapei_index';
			$btable = 'share_dapei_best';
		}
		
		$list = array();
		$sql = 'SELECT si.share_id,si.uid,s.cache_data FROM '.FDB::table($table).' AS si  
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = si.share_id 
			LEFT JOIN '.FDB::table($btable).' AS b ON b.share_id = si.share_id 
			ORDER BY b.share_id DESC,si.share_id DESC LIMIT 0,'.$num;
        $res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
			ShareService::shareImageFormat($data);
			$img = current($data[$type.'_imgs']);
			$data['img'] = $img['img'];
			$data['url'] = FU('dapie/'.$type,array('sid'=>$data['share_id']));
			$list[] = $data;
		}
		return $list;
	}
	
	public function runCron($crons)
	{
		$w = (int)fToDate(NULL,'w');
		
		if($w == 1)
			FS('Cron')->createRequest(array('m'=>'share','a'=>'collect_count','type'=>'w'));
		else
			FS('Cron')->createRequest(array('m'=>'share','a'=>'collect_count','type'=>'d'));
			
		$cron = array();
		$cron['server'] = 'share';
		$cron['run_time'] = getTodayTime() + 86400;
		FDB::insert('cron',$cron);
	}
}
?>