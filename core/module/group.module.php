<?php
class GroupModule
{
	function index()
	{
		global $_FANWE;
		$cache_file = getTplCache('page/group/group_index',array(),1);
		if(getCacheIsUpdate($cache_file,SHARE_CACHE_TIME,1))
		{
			Cache::getInstance()->loadCache('forum_category');
			$_FANWE['nav_title'] = lang('common','group');
			
			$base_groups = FS('Group')->getGroupsByType('best');
			$new_groups = FS('Group')->getGroupsByType('new',4);
			$admin_topics = FS('Topic')->getTopicsByType('top',$_FANWE['setting']['group_admin_fid'],5);
			$fids = array();
			$group_cates = array();
			$res = FDB::query('SELECT id,cate_name,cache_data,forum_count FROM '.FDB::table('forum_category').' 
				WHERE status = 1 ORDER BY sort ASC,id ASC');
			while($cate = FDB::fetch($res))
			{
				$group_cates[$cate['id']] = $cate;
				$cache_data = fStripslashes(unserialize($cate['cache_data']));
				unset($group_cates[$cate['id']]['cache_data']);
				$index = 0;
				foreach($cache_data['fids'] as $fid)
				{
					if($index > 15)
						continue;
					
					$fids[$fid] = false;
					$group_cates[$cate['id']]['groups'][$fid] = &$fids[$fid];
					$index++;
				}
			}
			
			if(count($fids) > 0)
			{
				$img_ids = array();
				$sql = 'SELECT fid,name,thread_count,user_count,icon 
						FROM '.FDB::table('forum').' WHERE fid IN ('.implode(',',array_keys($fids)).')';
				$res = FDB::query($sql);
				while($data = FDB::fetch($res))
				{
					$fids[$data['fid']] = $data;
					$fids[$data['fid']]['icon'] = array();
					$img_ids[$data['icon']][] = &$fids[$data['fid']]['icon'];
				}
				FS('Image')->formatByIdKeys($img_ids);
			}

			$group_darens = FS('Daren')->getDarensByType(4);
			$new_topics = FS('Topic')->getNewTopics();
			$hot_events = FS('Event')->getHotNewEvent(10);

			include template('page/group/group_index');
			display($cache_file); 
		}
		else
		{
			include $cache_file;
			display();
		}
	}

	function detail()
	{
		global $_FANWE;
		$fid = (int)$_FANWE['request']['fid'];
		if($fid == 0)
			fHeader('location: '.FU('group/index'));
				
		$group_detail = FS('Group')->getGroupById($fid);
		if(empty($group_detail))
			fHeader('location: '.FU('group/index'));
		
		$_FANWE['nav_title'] = $group_detail['name'].' - '.lang('common','group');
		
		$page_args = array();
		$page_args['fid'] = $fid;
		
		$is_join_group = FS('Group')->isUserFromGroup($fid,$_FANWE['uid']);
		if($is_join_group == 1)
		{
			$group_user_type = FDB::resultFirst('SELECT type FROM '.FDB::table('forum_users').' WHERE uid = '.$_FANWE['uid'].' AND fid = '.$fid);
			$group_user_type_name = lang('group','group_user_type_'.$group_user_type);
		}
		$page_type = 0;
		$where = ' WHERE fid = '.$fid;
		$type = $_FANWE['request']['type'];
		if($type == 'best')
		{
			$page_type = 1;
			$where .= ' AND ft.is_best = 1';
			$page_args['type'] = $type;
		}

		$sort = '';
		$order = ' ORDER BY ft.is_top DESC,ft.tid DESC';
		if($_FANWE['request']['sort'] == 'post')
		{
			$order = ' ORDER BY ft.lastpost DESC,ft.tid DESC';
			$sort = 'post';
			$page_args['sort'] = $sort;
		}
		
		$group_admins = FS('Group')->getGroupAdmins($fid);
		$group_users = FS('Group')->getNewGroupUsers($fid);
		$user_count = $group_detail['user_count'] - 1 - count($group_admins);
		$like_groups = FS('Group')->getLikeGroupsById($fid);
		$is_group_admin = FS('Group')->isAdminFromGroup($fid,$_FANWE['uid']);
		
		$count = FDB::resultFirst('SELECT COUNT(ft.tid) FROM '.FDB::table('forum_thread').' AS ft '.$where);
		$pager = buildPage('group/detail',$page_args,$count,$_FANWE['page'],20);
		$topic_list = array();

		$sql = 'SELECT ft.fid,ft.tid,ft.title,ft.create_time,ft.lastpost,ft.lastposter,
			ft.uid,ft.post_count,ft.share_id,s.cache_data,ft.is_top,ft.is_best
			FROM '.FDB::table('forum_thread').' AS ft
			INNER JOIN '.FDB::table('share').' AS s ON s.share_id = ft.share_id 
			'.$where.$order.' LIMIT '.$pager['limit'];
		$res = FDB::query($sql);
		while($data = FDB::fetch($res))
		{
			$data['cache_data'] = fStripslashes(unserialize($data['cache_data']));
			$data['time'] = getBeforeTimelag($data['create_time']);
			$data['last_time'] = getBeforeTimelag($data['lastpost']);
			$data['url'] = FU('topic/detail',array('tid'=>$data['tid']));
			FS('Share')->shareImageFormat($data,3);
			unset($data['cache_data']);
			$topic_list[$data['share_id']] = $data;
		}
		include template('page/group/group_detail');
		display();
	}
	
	function users()
	{
		global $_FANWE;
		$fid = (int)$_FANWE['request']['fid'];
		if($fid == 0)
			fHeader('location: '.FU('group/index'));
				
		$group_detail = FS('Group')->getGroupById($fid);
		if(empty($group_detail))
			fHeader('location: '.FU('group/index'));
		
		$_FANWE['nav_title'] = $group_detail['name'].' - '.lang('common','group_users');
		$group_admins = FS('Group')->getGroupAdmins($fid);
		$is_group_admin = FS('Group')->isAdminFromGroup($fid,$_FANWE['uid']);
		$user_count = $group_detail['user_count'] - 1 - count($group_admins);
		$page_args = array();
		$page_args['fid'] = $fid;
		$count = 0;
		$keywords = trim($_FANWE['request']['keywords']);
		if(!empty($keywords))
		{
			$uid = FDB::resultFirst('SELECT uid FROM '.FDB::table('user')." WHERE user_name = '".$keywords."'");
			if($uid > 0 && FS('Group')->isUserFromGroup($fid,$uid) == 1)
			{
				$count = 1;
				$user_list[] = array('uid'=>$uid);
			}
		}
		else
		{
			$count = $group_detail['user_count'] - 1 - count($group_admins);
			$pager = buildPage('group/users',$page_args,$count,$_FANWE['page'],54);
			$sql = 'SELECT uid FROM '.FDB::table('forum_users').' WHERE fid = '.$fid.' AND type = 0 LIMIT '.$pager['limit'];
			$user_list = FDB::fetchAll($sql);
		}
		include template('page/group/group_users');
		display();
	}
	
	function apply()
	{
		global $_FANWE;
		$fid = (int)$_FANWE['request']['fid'];
		if($fid == 0 || $_FANWE['uid'] == 0)
			fHeader('location: '.FU('group/index'));
				
		$group_detail = FS('Group')->getGroupById($fid);
		if(empty($group_detail))
			fHeader('location: '.FU('group/index'));
			
		if(FS('Group')->isAdminFromGroup($fid,$_FANWE['uid']) < 1)
			fHeader('location: '.FU('group/detail',array('fid'=>$fid)));
		
		$_FANWE['nav_title'] = $group_detail['name'].' - '.lang('common','group_apply_users');
		$group_admins = FS('Group')->getGroupAdmins($fid);
		
		$page_args = array();
		$page_args['fid'] = $fid;
		$count = 0;
		$keywords = trim($_FANWE['request']['keywords']);
		if(!empty($keywords))
		{
			$uid = FDB::resultFirst('SELECT uid FROM '.FDB::table('user')." WHERE user_name = '".$keywords."'");
			if($uid > 0 && FS('Group')->isUserFromGroup($fid,$uid) == 2)
			{
				$count = 1;
				$user_list[] = array('uid'=>$uid);
			}
		}
		else
		{
			$count = $group_detail['user_count'] - 1 - count($group_admins);
			$pager = buildPage('group/users',$page_args,$count,$_FANWE['page'],54);
			$sql = 'SELECT uid FROM '.FDB::table('forum_users_apply').' WHERE fid = '.$fid.' LIMIT '.$pager['limit'];
			$user_list = FDB::fetchAll($sql);
		}
		
		include template('page/group/group_apply');
		display();
	}
	
	function create()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
			fHeader('location: '.FU('user/login'));
		
		if($_FANWE['setting']['group_is_open'] < 1)
			fHeader('location: '.FU('group/index'));
		
		if($is_apply = FS('Group')->getIsApplyGroup())
		{
			Cache::getInstance()->loadCache('forum_category');
		}
		$_FANWE['nav_title'] = lang('group','group_apply');
		include template('page/group/group_create');
		display();
	}
	
	function edit()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
			fHeader('location: '.FU('user/login'));
		
		$fid = (int)$_FANWE['request']['fid'];
		$group = FS('Group')->getGroupById($fid);
		if(!$group || $group['uid'] != $group['uid'])
			fHeader('location: '.FU('group/index'));
			
		$_FANWE['nav_title'] = lang('group','group_edit');
		include template('page/group/group_edit');
		display();
	}
	
	function save()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
			fHeader('location: '.FU('user/login'));
		
		if($_FANWE['request']['agreement'] == 0)
			fHeader('location: '.FU('group/create'));
		
		$status = FS('Group')->createApply($_FANWE['request']);
		if($status > 1)
			fHeader('location: '.FU('group/detail',array('fid'=>$status)));
		elseif($status < 1)
			fHeader('location: '.FU('group/index'));
		else
			showSuccess(lang('group','group_apply'),lang('group','group_apply_status1'),FU('group/index'));
	}
	
	function update()
	{
		global $_FANWE;
		if($_FANWE['uid'] == 0)
			fHeader('location: '.FU('user/login'));
		
		$fid = (int)$_FANWE['request']['fid'];
		$status = FS('Group')->saveGroup($_FANWE['request']);
		if($status == 1)
			fHeader('location: '.FU('group/detail',array('fid'=>$fid)));
		else
			fHeader('location: '.FU('group/index'));
	}
	
	function agreement()
	{
		global $_FANWE;
		$title = sprintf(lang('group','agreement'),$_FANWE['setting']['site_name']);
		$_FANWE['nav_title'] = $title;
		$cache_file = getTplCache('page/group/group_agreement');
		if(!@include($cache_file))
		{
			include template('page/group/group_agreement');
		}
		display($cache_file);
	}
}
?>