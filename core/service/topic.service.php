<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/**
 * topic.service.php
 *
 * 主题服务类
 *
 * @package service
 * @author awfigq <awfigq@qq.com>
 */
class TopicService
{
	public function getIsEdit($tid)
	{
		global $_FANWE;
		$is_edit = false;
		$topic = TopicService::getTopicById($tid);
		if($topic['uid'] == $_FANWE['uid'])
			$is_edit = true;
		return $is_edit;
	}

	/**
	 * 获取带图片或商品分享的主题
	 * @return array
	 */
	public function getImgTopic($type,$num,$pic_num,$fid = 0,$begin = 0,$ids = array(),$is_group = false)
	{
		$key = 'topic/'.$type.'/'.$fid.'/'.$num.'/'.$pic_num.'_'.$begin.'_'.implode('_',$ids);
		$list = getCache($key);
		if($list === NULL)
		{
			global $_FANWE;
			$where = '';
			if($fid > 0)
				$where .= ' AND ft.fid = '.$fid;
			
			if(!empty($ids))
			{
				$ids = implode(',',$ids);
				if(!empty($ids))
					$where .= ' AND ft.tid NOT IN ('.$ids.')';
			}
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
	
			$order = 'ft.tid DESC';
	
			switch($type)
			{
				case 'top';
					$order = 'ft.is_top DESC,ft.sort ASC,ft.tid DESC';
				break;
				case 'best';
					$order = 'ft.is_best DESC,ft.sort ASC,ft.tid DESC';
				break;
				case 'hot';
					$order = 'ft.post_count DESC,ft.sort ASC,ft.tid DESC';
				break;
			}
	
			$list = array();
			$share_ids = array();
			$group_ids = array();
			$sql = 'SELECT ft.fid,ft.tid,ft.title,ft.content,ft.create_time,ft.lastpost,ft.lastposter,ft.uid,ft.post_count,ft.share_id  
				FROM '.FDB::table('forum_thread').' AS ft
				INNER JOIN '.FDB::table('share_images_index').' AS si ON si.share_id = ft.share_id 
				'.$where.' ORDER BY '.$order.' LIMIT '.$begin.','.$num;
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$data['time'] = getBeforeTimelag($data['create_time']);
				$data['last_time'] = getBeforeTimelag($data['lastpost']);
				$data['url'] = FU('topic/detail',array('tid'=>$data['tid']));
				$list[$data['tid']] = $data;
				$share_ids[$data['share_id']] = &$list[$data['tid']];
				if($is_group)
				{
					$list[$data['tid']]['group'] = array();
					$group_ids[$data['fid']][] = &$list[$data['tid']]['group'];
				}
			}
			
			if(count($share_ids) > 0)
			{
				$sql = 'SELECT share_id,cache_data FROM '.FDB::table('share').' WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$share_ids[$data['share_id']]['cache_data'] = fStripslashes(unserialize($data['cache_data']));
					FS('Share')->shareImageFormat($share_ids[$data['share_id']],$pic_num);
					unset($share_ids[$data['share_id']]['cache_data']);
				}
			}
			
			if($is_group)
			{
				FS('Group')->formatByIdKeys($group_ids,false);
			}
			
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		
		return $list;
	}

	public function getTopicsByType($type,$fid = 0,$num = 5,$is_group = false)
	{
		$fid = (int)$fid;
		$num = (int)$num;

		$key = 'topic/'.$type.'/'.$fid.'/'.$num;
		$list = getCache($key);
		if($list === NULL)
		{
			global $_FANWE;
			$where = '';
			if($fid > 0)
				$where = ' WHERE ft.fid = '.$fid;
	
			$order = 'ft.tid DESC';
	
			switch($type)
			{
				case 'top';
					$order = 'ft.is_top DESC,ft.sort ASC,ft.tid DESC';
				break;
				case 'best';
					$order = 'ft.is_best DESC,ft.sort ASC,ft.tid DESC';
				break;
				case 'hot';
					$order = 'ft.post_count DESC,ft.sort ASC,ft.tid DESC';
				break;
			}
	
			$list = array();
			$group_ids = array();
			$sql = 'SELECT ft.fid,ft.tid,ft.title,f.name AS group_name,ft.create_time,ft.lastpost,ft.lastposter,ft.uid,ft.post_count    
				FROM '.FDB::table('forum_thread').' AS ft 
				INNER JOIN '.FDB::table('forum').' AS f ON f.fid = ft.fid '.$where.' ORDER BY '.$order.' LIMIT 0,'.$num;
				
			$res = FDB::query($sql);
			while($data = FDB::fetch($res))
			{
				$data['time'] = getBeforeTimelag($data['create_time']);
				$data['last_time'] = getBeforeTimelag($data['lastpost']);
				$data['url'] = FU('topic/detail',array('tid'=>$data['tid']));
				$list[$data['tid']] = $data;
				if($is_group)
				{
					$list[$data['tid']]['group'] = array();
					$group_ids[$data['fid']][] = &$list[$data['tid']]['group'];
				}
			}
			
			if($is_group)
			{
				FS('Group')->formatByIdKeys($group_ids,true);
			}
			
			setCache($key,$list,SHARE_CACHE_TIME);
		}
		return $list;
	}

	public function getNewTopics($num = 20)
	{
		$list = array();
		$sql = 'SELECT ft.fid,ft.tid,ft.title,f.name AS group_name,ft.create_time,ft.lastpost,ft.lastposter,ft.uid,ft.post_count    
			FROM '.FDB::table('forum_thread').' AS ft 
			INNER JOIN '.FDB::table('forum').' AS f ON f.fid = ft.fid  
			ORDER BY ft.tid DESC LIMIT 0,'.(int)$num;
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$data['time'] = getBeforeTimelag($data['create_time']);
			$data['last_time'] = getBeforeTimelag($data['lastpost']);
			$data['url'] = FU('topic/detail',array('tid'=>$data['tid']));
			$list[$data['tid']] = $data;
		}
		return $list;
	}

	public function getTopicById($tid,$is_static = true)
	{
		$tid = (int)$tid;
		if(!$tid)
			return false;
		
		static $list = array();
		if(!isset($list[$tid]) || !$is_static)
		{
			$list[$tid] = FDB::fetchFirst('SELECT * FROM '.FDB::table('forum_thread').' WHERE tid = '.$tid);
		}
		return $list[$tid];
	}
	
	/**
	 * 获取主题回应列表
	 * @return array
	 */
	public function getTopicPostList($tid,$limit)
	{
		$tid = (int)$tid;
		if(!$tid)
			return array();
		
		$sql = 'SELECT s.* 
			FROM '.FDB::table('forum_post').' AS fp 
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = fp.share_id  
			WHERE fp.tid = '.$tid.' ORDER BY pid DESC LIMIT '.$limit;
		$list = FDB::fetchAll($sql);
		return FS('Share')->getShareDetailList($list,true,true,true);
	}

	public function saveTopicPost($tid,$content,$share_id = 0)
	{
		global $_FANWE;
		$post = array();
		$post['tid'] = $tid;
		$post['share_id'] = $share_id;
		$post['uid'] = $_FANWE['uid'];
		$post['content'] = $content;
		$post['create_time'] = TIME_UTC;
		$id = FDB::insert('forum_post',$post,true);
		if($id > 0)
		{
			FDB::query('UPDATE '.FDB::table('forum_thread').'
				SET post_count = post_count + 1,lastpost = '.TIME_UTC.',lastposter = '.$_FANWE['uid'].'
				WHERE tid = '.$tid);

			FDB::query("update ".FDB::table("user_count")." set forum_posts = forum_posts + 1 where uid = ".$_FANWE['uid']);
			FS('Medal')->runAuto($_FANWE['uid'],'forum_posts');
		}

		return $id;
	}

	/**
	 * 获取登陆会员是否已推荐此主题编号
	 * @param int $tid 主题编号
	 * @return bool
	 */
	public function getIsBestTid($tid)
	{
		global $_FANWE;
		$tid = (int)$tid;
		if($_FANWE['uid'] == 0 || $tid == 0)
			return false;
		
		$is_best = (int)FDB::resultFirst('SELECT COUNT(uid) FROM '.FDB::table('forum_thread_best').' WHERE tid = '.$tid.' AND uid = '.$_FANWE['uid']);
		if($is_best > 0)
			return true;
		else
			return false;
	}
	
	/**
	 * 获取关注主题的会员编号集合
	 */
	public function getBestUsers($tid,$num = 9)
	{
		$list = array();
		$tid = (int)$tid;
		if($tid == 0)
			return $list;
		
		$sql = 'SELECT uid FROM '.FDB::table('forum_thread_best').' WHERE tid = '.$tid.' ORDER BY create_time DESC LIMIT 0,'.$num;
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$list[$data['uid']] = 1;
		}
		return $list;
	}

	/**
	 * 推荐主题
	 如果已经推荐此主题，则删除推荐，返回false
	 如果没有推荐此主题，则添加推荐，返回true
	 * @param int $tid 主题编号
	 * @return bool
	 */
	public function bestTopic($tid,$uid,$content = '',$is_pub = 0)
	{
		global $_FANWE;
		$tid = (int)$tid;
		$uid = (int)$uid;
		if($uid == 0 || $tid == 0)
			return -1;
			
		$topic = TopicService::getTopicById($tid);
		if(empty($topic) || $uid == $topic['uid'])
			return -1;
		
		$is_best = (int)FDB::resultFirst('SELECT COUNT(uid) FROM '.FDB::table('forum_thread_best').' WHERE tid = '.$tid.' AND uid = '.$uid);
		if($is_best > 0)
		{
			FDB::delete('forum_thread_best','tid = '.$tid.' AND uid = '.$uid);
			FDB::query("update ".FDB::table("forum_thread")." set best_count = best_count - 1 where tid = ".$tid);
			return 0;
		}
		else
		{
			$best = array(
				'uid'      => $uid,
				'tid'      => $tid,
				'share_id'    => $topic['share_id'],
				'create_time' => TIME_UTC,
			);
			FDB::insert('forum_thread_best',$best);
			FDB::query("update ".FDB::table("forum_thread")." set best_count = best_count + 1 where tid = ".$tid);

			$data = array();
			$data['share']['uid'] = $uid;
			$data['share']['rec_id'] = $tid;
			$data['share']['title'] = addslashes($topic['title']);
			$data['share']['content'] = $content;
			$data['share']['type'] = "bar_best";
			$data['pub_out_check'] = $is_pub;
			
			$share = FS("Share")->save($data);
			//添加推荐消息提示
			if($share['status'])
			{		
				FS("User")->setUserTips($topic['uid'],4,$share['share_id']);
			}
			return 1;
		}
	}

	/**
	 * 获取浏览主题的会员编号集合
	 * @return array(1,2,...)
	 */
	public function getTopicLooks($tid,$num)
	{
		global $_FANWE;
		$uid = intval($_FANWE['uid']);
		$uids = TopicService::getTopicLooksCache($tid);
		$list = array_slice($uids,-$num,$num,true);

		if(isset($uids[$uid]))
		{
			if(!isset($list[$uid]))
			{
				array_shift($list);
				$list[$uid] = 1;
			}
			else
			{
				unset($list[$uid]);
				$list[$uid] = 1;
			}
		}

		return array_reverse($list,true);
	}

	/**
	 * 获取浏览主题的会员编号集合缓存
	 * @return array(1,2,...)
	 */
	public function getTopicLooksCache($tid)
	{
		$key = 'topic/thread/'.getDirsById($tid).'/looks';
		$data = getCache($key);
		if($data === NULL)
			$data = array();
		return $data;
	}

	/**
	 * 更新浏览主题的会员编号集合缓存
	 */
	public function updateTopicLooksCache($tid,$uid)
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0 || $_FANWE['uid'] == $uid)
			return;

		$uids = TopicService::getTopicLooksCache($tid);

		if(!isset($uids[$_FANWE['uid']]))
		{
			if(count($uids) > 100)
				array_shift($uids);
			$uids[$_FANWE['uid']] = 1;
			setCache('topic/thread/'.getDirsById($tid).'/looks',$uids);
		}
	}

	/**
	 * 获取会员最近发表的主题
	 */
	public function getUserNewTopicList($tid,$uid,$num)
	{
		global $_FANWE;
		$list = array();
		$sql = 'SELECT fid,tid,title,create_time,lastpost,lastposter,
			uid,post_count,share_id
			FROM '.FDB::table('forum_thread').'
			WHERE uid = '.$uid.' AND tid <> '.$tid.' ORDER BY tid DESC LIMIT 0,'.$num;
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$data['time'] = getBeforeTimelag($data['create_time']);
			$data['last_time'] = getBeforeTimelag($data['lastpost']);
			$data['url'] = FU('topic/detail',array('tid'=>$data['tid']));
			$list[$data['share_id']] = $data;
		}
		return $list;
	}

	public function deletePost($share_id,$is_score = true)
	{
		if(intval($share_id) == 0)
			return false;

		$post = FDB::fetchFirst('SELECT * FROM '.FDB::table('forum_post').' WHERE share_id = '.$share_id);
		if(empty($post))
			return true;

		FDB::delete('forum_post','share_id = '.$share_id);

		FDB::query('UPDATE '.FDB::table('forum_thread').' SET
				post_count = post_count - 1
				WHERE tid = '.$post['tid']);

		FDB::query('UPDATE '.FDB::table('user_count').' SET
				forum_posts = forum_posts - 1
				WHERE uid = '.$post['uid']);
				
		FS('Share')->deleteShare($share_id,$is_score);
		FS('Medal')->runAuto($post['uid'],'ask_posts');
	}

    public function updateTopicCache($tid)
	{
		$key = 'topic/thread/'.getDirsById($tid).'/detail';
		deleteCache($key);
	}
	
	public function updateTopicRec($tid,$title)
	{
		$sql = 'SELECT share_id,content
            FROM '.FDB::table('share')."
            WHERE type='bar_post' AND rec_id = '$tid'";
        $res = FDB::query($sql);
        while($data = FDB::fetch($res))
        {
            FS("Share")->updateShare($data['share_id'],$title,$data['content']);
        }
	}
	
	public function updateTopic($tid,$title,$content)
	{
		$data = array('title'=>$title,'content'=>$content);
		FDB::update('forum_thread', $data, 'tid='.$tid);
		
		$content_match = FS('Words')->segmentToUnicode($title);
		FDB::insert("forum_thread_match",array('tid'=>$tid,'content'=>$content_match),false,true);
	}

	public function deleteTopic($tid,$is_score = true)
	{
		global $_FANWE;
		$topic = TopicService::getTopicById($tid);
		if(empty($topic))
			return;
		
		setTimeLimit(600);
		$forum_id= $topic['fid'];
		$forum = $_FANWE['cache']['forums']['all'][$forum_id];

		$share_id = $topic['share_id'];
		$share = FS('Share')->getShareById($share_id);
		FS('Share')->deleteShare($share_id,$is_score);
		
		//FDB::query('UPDATE '.FDB::table('share')." SET rec_id = 0,parent_id = 0,type = 'default' WHERE rec_id = ".$tid." AND type = 'bar_post'");
		$res = FDB::query('SELECT * FROM '.FDB::table('forum_post').' WHERE tid = '.$tid);
		while($data = FDB::fetch($res))
		{
            TopicService::deletePost($data['share_id'],false);
		}
		FDB::query('DELETE FROM '.FDB::table('forum_post').' WHERE tid = '.$tid);
		FDB::query('DELETE FROM '.FDB::table('forum_thread_best').' WHERE tid = '.$tid);
		FDB::query('DELETE FROM '.FDB::table('forum_thread_match').' WHERE tid = '.$tid);
		FDB::query('DELETE FROM '.FDB::table('forum_thread').' WHERE tid = '.$tid);

		FDB::query('UPDATE '.FDB::table('forum').' SET thread_count = thread_count - 1 WHERE fid = '.$forum_id);
		FS('Medal')->runAuto($share['uid'],'forums');
	}
}
?>