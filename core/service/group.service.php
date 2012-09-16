<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**  
 * group.service.php
 *
 * 小组服务
 *
 * @package service
 * @author awfigq <awfigq@qq.com>
 */
class GroupService
{
	public function getIsApplyGroup()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
			return false;
			
		if($_FANWE['setting']['group_is_open'] < 1)
			return false;
		
		/*$group_gids = explode(',',$_FANWE['setting']['group_group_ids']);
		if(!in_array($_FANWE['gid'],$group_gids))
			return false;*/
		
		if($_FANWE['user']['fans'] < $_FANWE['setting']['group_fans_count'] || $_FANWE['user']['shares'] < $_FANWE['setting']['group_share_count'])
        	return false;

		return true;
	}
	
	//创建小组申请
	public function createApply($data)
	{
		global $_FANWE;
		$data['name'] = trim($data['name']);
		$data['content'] = trim($data['content']);
		
		if($_FANWE['uid'] == 0)
			return '0';
		
		if(empty($data['name']))
			return '-2';
			
		if(empty($data['content']))
			return '-3';
		
		if(!GroupService::getIsApplyGroup())
			return '-1';
		
		if((int)$_FANWE['setting']['group_is_check'] == 0)
		{
			$data['uid'] = $_FANWE['uid'];
			return GroupService::createGroup($data);
		}
		
		$group = array();
		$group['uid'] = $_FANWE['uid'];
		$group['name'] = cutStr($data['name'],100,'');
		$group['data']['content'] = cutStr($data['content'],2000,'');
		$group['data']['tags'] = trim($data['tags']);
		$group['data']['join_way'] = (int)$data['join_way'];
		$group['reason'] = cutStr(trim($data['reason']),200,'');
		$group['create_time'] = TIME_UTC;
		$group['create_day'] = getTodayTime();
		$group['data'] = addslashes(serialize($group['data']));
		FDB::insert('forum_apply',$group);
		return 1;
	}
	
	//创建小组
	public function createGroup($data)
	{
		$data['name'] = trim($data['name']);
		$data['content'] = trim($data['content']);
		$group = array();
		$group['uid'] = (int)$data['uid'];
		$group['cid'] = (int)$data['cid'];
		$group['name'] = cutStr($data['name'],100,'');
		$group['content'] = cutStr($data['content'],2000,'');
		if(isset($_FILES['icon']) || isset($_FILES['img']))
		{
			include_once fimport('class/image');
			$image = new Image();
			if(intval($_FANWE['setting']['max_upload']) > 0)
				$image->max_size = intval($_FANWE['setting']['max_upload']);
			
			if(isset($_FILES['icon']))
			{
				$image->init($_FILES['icon'],'temp');
				if($image->save())
				{
					$img = array();
					$img['type'] = 'default';
					$img['src'] = $image->file['local_target'];
					$img = FS('Image')->addImage($img);
					if($img)
						$group['icon'] = $img['id'];
				}
			}
			
			if(isset($_FILES['img']))
			{
				$image->init($_FILES['img'],'temp');
				if($image->save())
				{
					$img = array();
					$img['type'] = 'default';
					$img['src'] = $image->file['local_target'];
					$img = FS('Image')->addImage($img);
					if($img)
						$group['img_id'] = $img['id'];
				}
			}
		}
		
		$group['join_way'] = (int)$data['join_way'] > 0 ? 1 : 0;
		$group['user_name'] = trim($data['user_name']);
		$group['create_time'] = TIME_UTC;
		$group['create_day'] = getTodayTime();
		$fid = FDB::insert('forum',$group,true);
		FDB::insert("forum_users",array('fid'=>$fid,'uid'=>$group['uid'],'type'=>1,'create_time'=>TIME_UTC));
		if($group['cid'] > 0)
		{
			GroupService::updateCateCacheGroups($group['cid']);
		}
		$tags = GroupService::updateGroupTags($fid,$data['tags']);
		$content_match = trim($group['name'].' '.$tags);
		$content_match = FS('Words')->segmentToUnicode($content_match);
		FDB::insert("forum_match",array('fid'=>$fid,'content'=>$content_match));
		FDB::query("UPDATE ".FDB::table("user_count")." SET groups = groups + 1 where uid = ".$group['uid']);
		$share = array();
		$share['share']['uid'] = $group['uid'];
		$share['share']['rec_id'] = $fid;
		$share['share']['content'] = sprintf(lang('group','group_share'),FU('group/detail',array('fid'=>$fid)),$group['name']);
		$share['share']['type'] = "group";
		$share = FS('Share')->save($share);
		if($share['status'])
		{
			FDB::query('UPDATE '.FDB::table('forum').' SET share_id = '.$share['share_id'].' WHERE fid = '.$fid);
		}
		return $fid;
	}
	
	public function groupApplyNotice($fid,$uid,$name,$msg = '')
	{
		$notice = array();
		$notice['uid'] = $uid;
		$notice['title'] = lang('group','group_notice');
		if($fid > 0)
		{
			$notice['content'] = sprintf(lang('group','group_apply_success'),FU('group/detail',array('fid'=>$fid)),$name);	
		}
		else
		{
			$notice['content'] = sprintf(lang('group','group_apply_failure'),$name);
			if(!empty($msg))
				$notice['content'] .= sprintf(lang('group','group_apply_failure_yy'),$msg);
		}
		FS('Notice')->send($notice);
	}
	
	//保存修改
	public function saveGroup($data,$is_admin = 0)
	{
		$data['name'] = trim($data['name']);
		$data['content'] = trim($data['content']);
		$fid = (int)$data['fid'];
		$group = array();
		if($is_admin == 0)
		{
			global $_FANWE;
			if($_FANWE['uid'] == 0)
				return '0';
			
			if(empty($data['name']))
				return '-2';
				
			if(empty($data['content']))
				return '-3';
				
			$old_group = GroupService::getGroupById($fid);
			if($old_group['uid'] != $_FANWE['uid'])
				return -1;

			$group['uid'] = $old_group['uid'];
		}
		else
		{
			$old_group = GroupService::getGroupById($fid);
			$group['sort'] = (int)$data['sort'];
			$group['uid'] = (int)$data['uid'];
		}
		
		
		$group['name'] = cutStr($data['name'],100,'');
		$group['content'] = cutStr($data['content'],2000,'');
		if(isset($_FILES['icon']) || isset($_FILES['img']))
		{
			include_once fimport('class/image');
			$image = new Image();
			if(intval($_FANWE['setting']['max_upload']) > 0)
				$image->max_size = intval($_FANWE['setting']['max_upload']);
			
			if(isset($_FILES['icon']))
			{
				$image->init($_FILES['icon'],'temp');
				if($image->save())
				{
					$img = array();
					$img['type'] = 'default';
					$img['src'] = $image->file['local_target'];
					if($old_group['icon'] > 0)
					{
						$img['id'] = $old_group['icon'];
						FS('Image')->updateImage($img,true);	
					}
					else
					{
						$img = FS('Image')->addImage($img);
						if($img)
							$group['icon'] = $img['id'];
					}
				}
			}
			
			if(isset($_FILES['img']))
			{
				$image->init($_FILES['img'],'temp');
				if($image->save())
				{
					$img = array();
					$img['type'] = 'default';
					$img['src'] = $image->file['local_target'];
					if($old_group['img_id'] > 0)
					{
						$img['id'] = $old_group['img_id'];
						FS('Image')->updateImage($img,true);	
					}
					else
					{
						$img = FS('Image')->addImage($img);
						if($img)
							$group['img_id'] = $img['id'];
					}
				}
			}
		}
		
		$group['join_way'] = (int)$data['join_way'] > 0 ? 1 : 0;
		$group['cid'] = (int)$data['cid'];
		$group['user_name'] = trim($data['user_name']);
		FDB::update('forum',$group,'fid = '.$fid);
		if($is_admin == 0)
		{
			if((int)$_FANWE['setting']['group_is_check'] == 1)
			{
				$update = array();
				$update['fid'] = $old_group['fid'];
				$update['uid'] = $old_group['uid'];
				$update['update_time'] = TIME_UTC;
				FDB::insert('forum_update',$update,false,true);
			}
		}
			
		$tags = GroupService::updateGroupTags($fid,$data['tags']);
		GroupService::updateCateCacheGroups($group['cid']);
		if($old_group['cid'] != $group['cid'])
		{
			GroupService::updateCateCacheGroups($old_group['cid']);
		}
		
		if($old_group['uid'] != $group['uid'])	
			FDB::update("forum_users",array('uid'=>$group['uid']),'fid = '.$fid.' AND type = 1');
		
		$content_match = trim($group['name'].' '.$tags);
		$content_match = FS('Words')->segmentToUnicode($content_match);
		FDB::insert("forum_match",array('fid'=>$fid,'content'=>$content_match),false,true);
		return 1;
	}
	
	//更新小组关联标签
	private function updateGroupTags($fid,$tag_str)
	{
		$tag_str = str_replace('　',' ',$tag_str);
		$tags = explode(' ',$tag_str);
		$tags = array_unique($tags);
		FDB::delete('forum_tags','fid = '.$fid);
		foreach($tags as $tag)
		{
			if(trim($tag) != '')
			{
				$tag_data = array();
				$tag_data['fid'] = $fid;
				$tag_data['tag_name'] = $tag;
				FDB::insert('forum_tags',$tag_data);
			}
		}
		$tags = implode(' ',$tags);
		FDB::update('forum',array('tags'=>$tags),'fid = '.$fid);
		return $tags;
	}
	
	//删除小组
	public function removeGroup($fid)
	{
		$group = GroupService::getGroupById($fid);
		$img_ids = array();
		if($group['icon'] > 0)
			$img_ids[] = $group['icon'];
		
		if($group['img_id'] > 0)
			$img_ids[] = $group['img_id'];
		
		if(count($img_ids) > 0)
			FS('Image')->deleteImages($img_ids);
		
		FDB::delete("forum",'fid = '.$fid);
		FDB::delete("forum_users",'fid = '.$fid);
		FDB::delete("forum_users_apply",'fid = '.$fid);
		FDB::delete("forum_tags",'fid = '.$fid);
		FDB::delete("forum_update",'fid = '.$fid);
		FDB::delete("forum_match",'fid = '.$fid);
		if($group['cid'] > 0)
		{
			GroupService::updateCateCacheGroups($group['cid']);
		}
		FS('Share')->deleteShare($group['share_id']);
	}
	
	//更新小组分类缓存
	public function updateCateCacheGroups($cid)
	{
		$cid = (int)$cid;
		if($cid == 0)
			return;
			
		$cache_data = array();
		$res = FDB::query('SELECT fid FROM '.FDB::table('forum').' WHERE cid = '.$cid.' ORDER BY is_index DESC,is_best DESC,sort ASC,fid DESC LIMIT 0,20');
		while($data = FDB::fetch($res))
		{
			$cache_data['fids'][] = $data['fid'];
		}
		$cache_data = addslashes(serialize($cache_data));

		$forum_count = (int)FDB::resultFirst('SELECT COUNT(fid) FROM '.FDB::table('forum').' WHERE cid = '.$cid);
		FDB::update('forum_category',array('cache_data'=>$cache_data,'forum_count'=>$forum_count),'id = '.$cid);
	}
	
	//获取小组详细
	public function getGroupById($fid,$is_static = true,$is_img = true)
	{
		$fid = (int)$fid;
		if($fid == 0)
			return false;
		
		static $groups = array();
		if(!isset($groups[$fid]) || !$is_static)
		{
			$groups[$fid] = FDB::fetchFirst('SELECT * FROM '.FDB::table('forum').' WHERE fid = '.$fid);
			if($is_img && $groups[$fid])
			{
				$image_ids = array();
				$groups[$fid]['img'] = '';
				if($groups[$fid]['img_id'] > 0)
					$image_ids[$groups[$fid]['img_id']][] = &$groups[$fid]['img'];
				
				if($groups[$fid]['icon'] > 0)
					$image_ids[$groups[$fid]['icon']][] = &$groups[$fid]['icon'];
					
				FS('Image')->formatByIdKeys($image_ids,true);
			}
		}
		return $groups[$fid];
	}
	
	public function formatByIdKeys(&$list,$is_img = true)
	{
		if(!is_array($list) || count($list) == 0)
			return;
		
		$ids = array_keys($list);
		
		$img_ids = array();
		$sql = 'SELECT fid,share_id,name,uid,thread_count,user_count,post_count,icon,create_time,content 
			FROM '.FDB::table('forum').' WHERE fid IN ('.implode(',',$ids).')';
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			foreach($list[$data['fid']] as $key => $val)
			{
				$list[$data['fid']][$key] = $data;
				if($is_img)
				{
					$list[$data['fid']][$key]['icon'] = array();
					$img_ids[$data['icon']][] = &$list[$data['fid']][$key]['icon'];
				}
			}
		}
		
		if($is_img)
			FS('Image')->formatByIdKeys($img_ids);
	}
	
	//获取小组管理员
	public function getGroupAdmins($fid)
	{
		$fid = (int)$fid;
		if($fid == 0)
			return array();
		$list = array();
		$res = FDB::query('SELECT uid FROM '.FDB::table('forum_users').' WHERE fid = '.$fid.' AND type = 2 ORDER BY create_time ASC');
		while($data = FDB::fetch($res))
		{
			$list[] = $data['uid'];
		}
		return $list;
	}
	
	public function getNewGroupUsers($fid,$num = 6)
	{
		$fid = (int)$fid;
		if($fid == 0)
			return array();
		
		$list = array();
		$res = FDB::query('SELECT uid FROM '.FDB::table('forum_users').' WHERE fid = '.$fid.' AND type = 0 ORDER BY create_time ASC LIMIT 0,'.$num);
		while($data = FDB::fetch($res))
		{
			$list[] = $data['uid'];
		}
		return $list;
	}
	
	//根据类型获取小组
	public function getGroupsByType($type,$num = 8)
	{
		$key = 'group/'.$type.'/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$list = array();
			$where = '';
			$sort = 'ORDER BY fid DESC';
			switch($type)
			{
				case 'index':
					$sort = ' ORDER BY is_index DESC,sort ASC,fid DESC';
				break;
				
				case 'best':
					$sort = ' ORDER BY is_best DESC,sort ASC,fid DESC';
				break;
				
				case 'new':
					$sort = ' ORDER BY is_new DESC,sort ASC,fid DESC';
				break;
				
				case 'users':
					$sort = ' ORDER BY user_count DESC,sort ASC,fid DESC';
				break;
			}
			
			$img_ids = array();
			$sql = 'SELECT fid,share_id,name,uid,thread_count,user_count,post_count,icon,create_time,content 
					FROM '.FDB::table('forum').$where.$sort.' LIMIT 0,'.$num;
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$list[$data['fid']] = $data;
				$list[$data['fid']]['icon'] = array();
				$img_ids[$data['icon']][] = &$list[$data['fid']]['icon'];
			}
			FS('Image')->formatByIdKeys($img_ids);
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}
	
	//获取今日发布小组
	public function getTodayGroups($page_size = 16,$page = 1)
	{
		$key = 'group/today/'.$page_size.'_'.$page;
		$groups = getCache($key);
		if($groups === NULL)
		{
			$today = getTodayTime();
			$groups = array('pager'=>array('total_count'=>0),'list'=>array());
			$count = FDB::resultFirst('SELECT COUNT(fid) FROM '.FDB::table('forum').' WHERE create_day = '.$today);
			if($count > 0)
			{
				$pager = buildPageMini($count,$page,$page_size);
				$groups['pager'] = $pager;
				
				$list = array();
				$img_ids = array();
				$sql = 'SELECT fid,share_id,name,uid,thread_count,user_count,post_count,icon,create_time   
					FROM '.FDB::table('forum').' WHERE create_day = '.$today.' ORDER BY fid DESC LIMIT '.$pager['limit'];
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$list[$data['fid']] = $data;
					$list[$data['fid']]['icon'] = array();
					$img_ids[$data['icon']][] = &$list[$data['fid']]['icon'];
				}
				FS('Image')->formatByIdKeys($img_ids);
				$groups['list'] = $list;
			}
			setCache($key,$groups,SHARE_CACHE_TIME);
		}
		return $groups;
	}
	
	//获取会员参加的小组
	public function getGroupsByUid($uid,$page_size = 8,$page = 1)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return array('pager'=>array('total_count'=>0),'list'=>array());
		
		$key = 'user/'.getDirsById($uid).'/group/'.$page_size.'_'.$page;
		$groups = getCache($key);
		if($groups === NULL)
		{
			$groups = array('pager'=>array('total_count'=>0),'list'=>array());
			$count = FDB::resultFirst('SELECT COUNT(fid) FROM '.FDB::table('forum_users').' WHERE uid = '.$uid);
			if($count > 0)
			{
				$pager = buildPageMini($count,$page,$page_size);
				$groups['pager'] = $pager;
				
				$list = array();
				$img_ids = array();
				$sql = 'SELECT f.fid,f.share_id,f.name,f.uid,f.thread_count,f.user_count,f.post_count,f.icon,f.create_time   
						FROM '.FDB::table('forum_users').' AS fu 
						INNER JOIN '.FDB::table('forum').' AS f ON f.fid = fu.fid 
						WHERE fu.uid = '.$uid.' ORDER BY fu.create_time DESC LIMIT '.$pager['limit'];
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$list[$data['fid']] = $data;
					$list[$data['fid']]['icon'] = array();
					$img_ids[$data['icon']][] = &$list[$data['fid']]['icon'];
				}
				FS('Image')->formatByIdKeys($img_ids);
				$groups['list'] = $list;
			}
			setCache($key,$groups,SHARE_CACHE_TIME);
		}
		return $groups;
	}
	
	public function getLikeGroupsById($fid,$num = 4)
	{
		$fid = (int)$fid;
		if($fid == 0)
			return array();
		
		$key = 'group/'.getDirsById($fid).'/like_'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			$img_ids = array();
			$groups = array();
			$sql = 'SELECT f.fid,f.name,f.user_count,f.icon,f.create_time 
				FROM '.FDB::table('forum_users').' AS fu1 
				INNER JOIN '.FDB::table('forum_users').' AS fu ON fu.uid = fu1.uid AND fu.fid <> fu1.fid 
				INNER JOIN '.FDB::table('forum').' AS f ON f.fid = fu.fid 
				WHERE fu1.fid = '.$fid.' ORDER BY f.user_count DESC LIMIT 0,'.$num;
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$list[$data['fid']] = $data;
				$list[$data['fid']]['icon'] = array();
				$img_ids[$data['icon']][] = &$list[$data['fid']]['icon'];
			}
			FS('Image')->formatByIdKeys($img_ids);
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}
	
	//获取分类下的小组
	public function getCateGroups($cid,$page_size = 16,$page = 1)
	{
		$cid = (int)$cid;
		if($cid == 0)
			return array('pager'=>array('total_count'=>0),'list'=>array());
			
		$key = 'group/'.getDirsById($cid).'/'.$page_size.'_'.$page;
		$groups = getCache($key);
		if($groups === NULL)
		{
			$groups = array('pager'=>array('total_count'=>0),'list'=>array());
			$count = FDB::resultFirst('SELECT COUNT(fid) FROM '.FDB::table('forum').' WHERE cid = '.$cid);
			if($count > 0)
			{
				$pager = buildPageMini($count,$page,$page_size);
				$groups['pager'] = $pager;
				
				$list = array();
				$img_ids = array();
				$sql = 'SELECT fid,share_id,name,uid,thread_count,user_count,post_count,icon,create_time 
						FROM '.FDB::table('forum').' WHERE cid = '.$cid.' ORDER BY fid DESC LIMIT '.$pager['limit'];
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$list[$data['fid']] = $data;
					$list[$data['fid']]['icon'] = array();
					$img_ids[$data['icon']][] = &$list[$data['fid']]['icon'];
				}
				FS('Image')->formatByIdKeys($img_ids);
				$groups['list'] = $list;
			}
			setCache($key,$groups,SHARE_CACHE_TIME);
		}
		return $groups;
	}
	
	//获取会员是否为组员
	public function isUserFromGroup($fid,$uid)
	{
		$fid = (int)$fid;
		$uid = (int)$uid;
		if($fid == 0 || $uid == 0)
			return -1;
			
		$count = (int)FDB::resultFirst('SELECT COUNT(uid) FROM '.FDB::table('forum_users').' WHERE fid = '.$fid.' AND uid = '.$uid);
		if($count > 0)
			return 1;
		else
		{
			$count = (int)FDB::resultFirst('SELECT COUNT(uid) FROM '.FDB::table('forum_users_apply').' WHERE fid = '.$fid.' AND uid = '.$uid);
			if($count > 0)
				return 2;
			else
				return 0;
		}
	}
	
	//获取会员是否为管理员
	public function isAdminFromGroup($fid,$uid)
	{
		$fid = (int)$fid;
		$uid = (int)$uid;
		if($fid == 0 || $uid == 0)
			return -1;
			
		$user = FDB::fetchFirst('SELECT * FROM '.FDB::table('forum_users').' WHERE fid = '.$fid.' AND uid = '.$uid);
		if(empty($user))
			return -1;
		
		if($user['type'] > 0)
			return 1;
		else
			return 0;
	}
	
	//添加会员进小组
	public function setUserToGroup($fid,$uid,$user_name = '',$is_remove = 0)
	{
		$fid = (int)$fid;
		$uid = (int)$uid;
		if($fid == 0 || $uid == 0)
			return -1;
			
		$group = GroupService::getGroupById($fid,false,false);
		if(empty($group))
			return -1;
			
		if($group['uid'] == $uid)
			return -1;
		
		$bln = GroupService::isUserFromGroup($fid,$uid);
		if($is_remove == 1 && $bln == 1)
		{
			FDB::delete('forum_users','fid = '.$fid.' AND uid = '.$uid);
			FDB::query('UPDATE '.FDB::table('forum').' SET user_count = user_count - 1 WHERE fid = '.$fid);
			return 0;
		}
		elseif($is_remove == 0 && $bln == 0)
		{
			if($group['join_way'] == 1)
			{
				FDB::insert('forum_users_apply',array('fid'=>$fid,'uid'=>$uid,'create_time'=>TIME_UTC));
				
				//通知组长
				$notice = array();
				$notice['uid'] = $group['uid'];
				$notice['title'] = lang('group','group_notice');
				$notice['content'] = sprintf(lang('group','user_join_apply_content'),FU('u/index',array('uid'=>$uid)),$user_name,$group['name'],FU('group/apply',array('fid'=>$fid)));
				FS('Notice')->send($notice);
				return 2;
			}
			else
			{
				FDB::insert('forum_users',array('fid'=>$fid,'uid'=>$uid,'create_time'=>TIME_UTC));
				FDB::query('UPDATE '.FDB::table('forum').' SET user_count = user_count + 1 WHERE fid = '.$fid);
				$share = array();
				$share['share']['uid'] = $uid;
				$share['share']['rec_id'] = $fid;
				$share['share']['content'] = sprintf(lang('group','user_join_apply_share'),FU('group/detail',array('fid'=>$fid)),$group['name']);
				$share['share']['type'] = "group_join";
				FS('Share')->save($share);
				return 1;
			}
		}
	}
	
	//会员申请加入小组处理
	public function groupUserApplyHandle($fid,$uid,$is_remove = 0)
	{
		$fid = (int)$fid;
		$uid = (int)$uid;
		if($fid == 0 || $uid == 0)
			return -1;
		
		$user = FDB::fetchFirst('SELECT uid,user_name FROM '.FDB::table('user').' WHERE uid = '.$uid);
		if(empty($user))
			return -1;
			
		$group = GroupService::getGroupById($fid,false,false);
		if(empty($group))
			return -1;
			
		if($group['uid'] == $uid)
			return -1;
		
		$apply = FDB::fetchFirst('SELECT * FROM '.FDB::table('forum_users_apply').' WHERE fid = '.$fid.' AND uid = '.$uid);
		if(empty($apply))
			return -1;
		
		//通知会员
		$notice = array();
		$notice['uid'] = $uid;
		$notice['title'] = lang('group','group_notice');
		if($is_remove == 0)
		{
			FDB::insert('forum_users',array('fid'=>$fid,'uid'=>$uid,'create_time'=>TIME_UTC));
			FDB::delete('forum_users_apply','fid = '.$fid.' AND uid = '.$uid);
			FDB::query('UPDATE '.FDB::table('forum').' SET user_count = user_count + 1 WHERE fid = '.$fid);
			$notice['content'] = sprintf(lang('group','user_join_apply_success'),FU('group/detail',array('fid'=>$fid)),$group['name']);	
			FS('Notice')->send($notice);
			return 1;
		}
		else
		{
			FDB::delete('forum_users_apply','fid = '.$fid.' AND uid = '.$uid);
			$notice['content'] = sprintf(lang('group','user_join_apply_failure'),FU('group/detail',array('fid'=>$fid)),$group['name']);	
			FS('Notice')->send($notice);
			return 0;
		}
	}
	
	//设为小组管理员
	public function setAdminToGroup($fid,$fuid,$auid,$is_remove = 0)
	{
		$fid = (int)$fid;
		$fuid = (int)$fuid;
		$auid = (int)$auid;
		if($fid == 0 || $fuid == 0 || $auid == 0)
			return -1;
		
		$group = GroupService::getGroupById($fid,false,false);
		if(empty($group))
			return -1;
		
		if($group['uid'] != $fuid || $group['uid'] == $auid)
			return -1;
		
		$bln = GroupService::isAdminFromGroup($fid,$auid);
		if($bln == -1)
			return -1;
		
		//通知会员
		$notice = array();
		$notice['uid'] = $auid;
		$notice['title'] = lang('group','group_notice');
		$status = 0;
		if($is_remove == 0)
		{
			FDB::update('forum_users',array('type'=>2),'fid = '.$fid.' AND uid = '.$auid);
			$notice['content'] = sprintf(lang('group','set_admin'),FU('group/detail',array('fid'=>$fid)),$group['name']);
			$status = 1;
		}
		else
		{
			FDB::update('forum_users',array('type'=>0),'fid = '.$fid.' AND uid = '.$auid);
			$notice['content'] = sprintf(lang('group','out_admin'),FU('group/detail',array('fid'=>$fid)),$group['name']);
			$status = 0;
		}
		FS('Notice')->send($notice);
		return $status;
	}

	public function setUserCache($uid)
	{
		$uid = (int)$uid;
		if($uid == 0)
			return;

		$user_cache = FDB::resultFirst('SELECT cache_data FROM '.FDB::table('user_status').' WHERE uid = '.$uid);
		$cache_data = fStripslashes(unserialize($user_cache));

		$groups = array();
		$img_ids = array();
		$sql = 'SELECT fid,icon FROM '.FDB::table('forum').' 
			WHERE uid = '.$uid.' ORDER BY fid DESC LIMIT 0,10';
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$groups[$data['fid']] = array(
				'fid'=>$data['fid']
			);
			$img_ids[$data['icon']][] = &$groups[$data['fid']];
		}
		FS('Image')->formatByIdKeys($img_ids);
		$cache_data['groups'] = $groups;
		$cache_data = addslashes(serialize($cache_data));
		FDB::update('user_status',array('cache_data'=>$cache_data),'uid = '.$uid);
	}
}
?>