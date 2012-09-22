<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: awfigq <awfigq@qq.com>
// +----------------------------------------------------------------------
/**
 +------------------------------------------------------------------------------
 * 分享
 +------------------------------------------------------------------------------
 */
class ShareAction extends CommonAction
{
	public function image()
	{
		vendor("common");
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$share_data = !isset($_REQUEST['share_data']) ? 'goods_photo' : trim($_REQUEST['share_data']);
		$cate_id = intval($_REQUEST['cate_id']);
		
		$list = array();
		$index_table = 'share_images_index';
		$match_table = 'share_match';
		switch($share_data)
		{
			case 'goods':
				$index_table = 'share_goods_index';
				$match_table = 'share_goods_match';
			break;
			
			case 'photo':
				$index_table = 'share_photo_index';
				$match_table = 'share_photo_match';
			break;
		}
		
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$is_match = false;
			if(!empty($keyword))
			{
				$this->assign("keyword",$keyword);
				$parameter['keyword'] = $keyword;
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				$is_match = true;
			}
			
			if($share_data != 'goods_photo')
			{
				$this->assign("share_data",$share_data);
				$parameter['share_data'] = $share_data;
			}
			
			$is_cate = false;
			$is_no_cate = false;
			if($cate_id != 0)
			{
				$is_cate = true;
				$this->assign("cate_id",$cate_id);
				$parameter['cate_id'] = $cate_id;
	
				if($cate_id > 0)
				{
					$where .= " AND sc.cate_id = ".$cate_id;
				}
				else
				{
					$is_no_cate = true;
				}
			}
			
			$model = M();
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").$index_table.' AS si ';
			$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").$index_table.' AS si ';
			if($is_cate && !$is_no_cate)
			{
				$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_category AS sc ';
				$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").'share_category AS sc ';
				if($is_match)
				{
					$append_sql = 'INNER JOIN '.C("DB_PREFIX").$match_table.' AS sm ON sm.share_id = sc.share_id '.
						" AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";;
				}
				$append_sql .= 'INNER JOIN '.C("DB_PREFIX").$index_table.' AS si ON si.share_id = sc.share_id ';
			}
			elseif($is_match)
			{
				$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").$match_table.' AS sm ';
				$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").$match_table.' AS sm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").$index_table.' AS si ON si.share_id = sm.share_id ';
				$where.=" AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";
			}
			
			if($is_no_cate)
			{
				$append_sql .= 'LEFT JOIN '.C("DB_PREFIX").'share_category AS sc ON sc.share_id = si.share_id ';
				$where .= " AND sc.cate_id IS NULL";
			}
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$sql_count .= $append_sql.$where;
			$sql .= $append_sql.$where;
			
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
	
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id');
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = '';
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status,is_index,cache_data 
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $k => $item)
			{
				$cacheData = stripslashesDeep(unserialize($item['cache_data']));
				foreach($cacheData['imgs']['all'] as $ik => $img){
					$img['img'] = substr($img['img'], 1, -4);
					$item['imgs'] = '<a target='.'"_blank'.'"'. 'href='.'"'.'/note/'.$item['share_id'].'">'.'<img src='.'"'.$img['img'].'_100x100.jpg'.'">'.'</img></a>' ;
				}
				$user_ids[$item['uid']] = '';
				$share_ids[$item['share_id']] = $item;
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
				$share_ids[$item['share_id']]['cate_name'] = '';
			}
			unset($share_list);
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
			
			$sql = 'SELECT sc.share_id,gc.cate_name FROM '.C("DB_PREFIX").'share_category AS sc 
				INNER JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = sc.cate_id 
				WHERE sc.share_id IN ('.implode(',',array_keys($share_ids)).')';
			$cate_list = M()->query($sql);
			foreach($cate_list as $item)
			{
				if(empty($share_ids[$item['share_id']]['cate_name']))
					$share_ids[$item['share_id']]['cate_name'] = $item['cate_name'];
				else
					$share_ids[$item['share_id']]['cate_name'] .= '<br/>'.$item['cate_name'];
			}
		}
		$this->assign("list",$list);
		
		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$cate_list = D('GoodsCategory')->where('cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$cate_list = D('GoodsCategory')->toFormatTree($cate_list,'cate_name','cate_id','parent_id');
		$this->assign("cate_list",$cate_list);
		$this->display();
	}
	
	public function dapei()
	{
		vendor("common");
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$list = array();
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$is_match = false;
			if(!empty($keyword))
			{
				$this->assign("keyword",$keyword);
				$parameter['keyword'] = $keyword;
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				$is_match = true;
			}
			
			$model = M();
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_dapei_index AS si ';
			$sql = 'SELECT si.share_id,IF(sdb.share_id,1,0) as is_dapei_best FROM '.C("DB_PREFIX").'share_dapei_index AS si ';
			if($is_match)
			{
				$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_photo_match AS sm ';
				$sql = 'SELECT si.share_id,IF(sdb.share_id,1,0) as is_dapei_best FROM '.C("DB_PREFIX").'share_photo_match AS sm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").'share_dapei_index AS si ON si.share_id = sm.share_id ';
				$where.=" AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";
			}
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$is_best = (int)$_REQUEST['is_best'];
			if($is_best == 1)
			{
				$parameter['is_best'] = $is_best;
				$append_sql .= 'INNER JOIN '.C("DB_PREFIX").'share_dapei_best AS sdb ON sdb.share_id = si.share_id ';
				$sql_count .= $append_sql.$where;
			}
			else
			{
				$sql_count .= $append_sql.$where;
				$append_sql .= 'LEFT JOIN '.C("DB_PREFIX").'share_dapei_best AS sdb ON sdb.share_id = si.share_id ';
			}

			$sql .= $append_sql.$where;
			
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
			
			if($_REQUEST ['_order'] == 'is_dapei_best')
				$_REQUEST ['_order'] = 'sdb.share_id';
			
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id');
			
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = $item;
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status,is_index,sort 
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $item)
			{
				$user_ids[$item['uid']] = '';
				if($share_ids[$item['share_id']]['is_dapei_best'] == 1)
				{
					$share_ids[$item['share_id']] = $item;
					$share_ids[$item['share_id']]['is_dapei_best'] = 1;
				}
				else
				{
					$share_ids[$item['share_id']] = $item;
					$share_ids[$item['share_id']]['is_dapei_best'] = 0;
				}
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
				$share_ids[$item['share_id']]['cate_name'] = '';
			}
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
			
			$sql = 'SELECT sc.share_id,gc.cate_name FROM '.C("DB_PREFIX").'share_category AS sc 
				INNER JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = sc.cate_id 
				WHERE sc.share_id IN ('.implode(',',array_keys($share_ids)).')';
			$cate_list = M()->query($sql);
			foreach($cate_list as $item)
			{
				if(empty($share_ids[$item['share_id']]['cate_name']))
					$share_ids[$item['share_id']]['cate_name'] = $item['cate_name'];
				else
					$share_ids[$item['share_id']]['cate_name'] .= '<br/>'.$item['cate_name'];
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
	
	public function look()
	{
		vendor("common");
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$list = array();
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$is_match = false;
			if(!empty($keyword))
			{
				$this->assign("keyword",$keyword);
				$parameter['keyword'] = $keyword;
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				$is_match = true;
			}
			
			$model = M();
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_look_index AS si ';
			$sql = 'SELECT si.share_id,IF(slb.share_id,1,0) as is_look_best FROM '.C("DB_PREFIX").'share_look_index AS si ';
			if($is_match)
			{
				$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_photo_match AS sm ';
				$sql = 'SELECT si.share_id,IF(slb.share_id,1,0) as is_look_best FROM '.C("DB_PREFIX").'share_photo_match AS sm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").'share_look_index AS si ON si.share_id = sm.share_id ';
				$where.=" AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";
			}
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$is_best = (int)$_REQUEST['is_best'];
			if($is_best == 1)
			{
				$parameter['is_best'] = $is_best;
				$append_sql .= 'INNER JOIN '.C("DB_PREFIX").'share_look_best AS slb ON slb.share_id = si.share_id ';
				$sql_count .= $append_sql.$where;
			}
			else
			{
				$sql_count .= $append_sql.$where;
				$append_sql .= 'LEFT JOIN '.C("DB_PREFIX").'share_look_best AS slb ON slb.share_id = si.share_id ';
			}

			$sql .= $append_sql.$where;
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
			
			if($_REQUEST ['_order'] == 'is_look_best')
				$_REQUEST ['_order'] = 'slb.share_id';
			
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id');
			
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = $item;
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status,is_index,sort  
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $item)
			{
				$user_ids[$item['uid']] = '';
				if($share_ids[$item['share_id']]['is_look_best'] == 1)
				{
					$share_ids[$item['share_id']] = $item;
					$share_ids[$item['share_id']]['is_look_best'] = 1;
				}
				else
				{
					$share_ids[$item['share_id']] = $item;
					$share_ids[$item['share_id']]['is_look_best'] = 0;
				}
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
				$share_ids[$item['share_id']]['cate_name'] = '';
			}
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
			
			$sql = 'SELECT sc.share_id,gc.cate_name FROM '.C("DB_PREFIX").'share_category AS sc 
				INNER JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = sc.cate_id 
				WHERE sc.share_id IN ('.implode(',',array_keys($share_ids)).')';
			$cate_list = M()->query($sql);
			foreach($cate_list as $item)
			{
				if(empty($share_ids[$item['share_id']]['cate_name']))
					$share_ids[$item['share_id']]['cate_name'] = $item['cate_name'];
				else
					$share_ids[$item['share_id']]['cate_name'] .= '<br/>'.$item['cate_name'];
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
	
	public function text()
	{
		vendor("common");
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$list = array();
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$is_match = false;
			if(!empty($keyword))
			{
				$this->assign("keyword",$keyword);
				$parameter['keyword'] = $keyword;
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				$is_match = true;
			}
			
			$model = M();
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_text_index AS si ';
			$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").'share_text_index AS si ';
			if($is_match)
			{
				$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_text_match AS sm ';
				$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").'share_text_match AS sm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").'share_text_index AS si ON si.share_id = sm.share_id ';
				$where.=" AND match(sm.content_match) against('".$match_key."' IN BOOLEAN MODE) ";
			}
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$sql_count .= $append_sql.$where;
			$sql .= $append_sql.$where;
			
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
			
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id');
			
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = '';
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status 
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $item)
			{
				$user_ids[$item['uid']] = '';
				$share_ids[$item['share_id']] = $item;
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
			}
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
	
	public function check()
	{
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$list = array();
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$model = M();
			
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_check AS si ';
			$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").'share_check AS si ';
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$sql_count .= $append_sql.$where;
			$sql .= $append_sql.$where;
			
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
			
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id',true);
			
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = '';
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status 
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $item)
			{
				$user_ids[$item['uid']] = '';
				$share_ids[$item['share_id']] = $item;
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
				$share_ids[$item['share_id']]['cate_name'] = '';
			}
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
			
			$sql = 'SELECT sc.share_id,gc.cate_name FROM '.C("DB_PREFIX").'share_category AS sc 
				INNER JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = sc.cate_id 
				WHERE sc.share_id IN ('.implode(',',array_keys($share_ids)).')';
			$cate_list = M()->query($sql);
			foreach($cate_list as $item)
			{
				if(empty($share_ids[$item['share_id']]['cate_name']))
					$share_ids[$item['share_id']]['cate_name'] = $item['cate_name'];
				else
					$share_ids[$item['share_id']]['cate_name'] .= '<br/>'.$item['cate_name'];
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
	
	public function cancel()
	{
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$list = array();
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND si.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$model = M();
			
			$sql_count = 'SELECT COUNT(si.share_id) AS scount FROM '.C("DB_PREFIX").'share_cancel AS si ';
			$sql = 'SELECT si.share_id FROM '.C("DB_PREFIX").'share_cancel AS si ';
			
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);
			
			$sql_count .= $append_sql.$where;
			$sql .= $append_sql.$where;
			
			$count = $model->query($sql_count);
			$count = $count[0]['scount'];
			
			$this->_sqlList($model,$sql,$count,$parameter,'si.share_id');
			
			$list = $this->list;
			$share_ids = array();
			$user_ids = array();
			foreach($list as $key => $item)
			{
				$share_ids[$item['share_id']] = '';
				$list[$key] = &$share_ids[$item['share_id']];
			}
			
			$sql = 'SELECT share_id,uid,content,create_time,collect_count,relay_count,comment_count,type,share_data,status 
				FROM '.C("DB_PREFIX").'share WHERE share_id IN ('.implode(',',array_keys($share_ids)).')';
			$share_list = M()->query($sql);
			foreach($share_list as $item)
			{
				$user_ids[$item['uid']] = '';
				$share_ids[$item['share_id']] = $item;
				$share_ids[$item['share_id']]['user_name'] = &$user_ids[$item['uid']];
				$share_ids[$item['share_id']]['cate_name'] = '';
			}
			
			$sql = 'SELECT uid,user_name FROM '.C("DB_PREFIX").'user WHERE uid IN ('.implode(',',array_keys($user_ids)).')';
			$user_list = M()->query($sql);
			foreach($user_list as $item)
			{
				$user_ids[$item['uid']] = $item['user_name'];
			}
			
			$sql = 'SELECT sc.share_id,gc.cate_name FROM '.C("DB_PREFIX").'share_category AS sc 
				INNER JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = sc.cate_id 
				WHERE sc.share_id IN ('.implode(',',array_keys($share_ids)).')';
			$cate_list = M()->query($sql);
			foreach($cate_list as $item)
			{
				if(empty($share_ids[$item['share_id']]['cate_name']))
					$share_ids[$item['share_id']]['cate_name'] = $item['cate_name'];
				else
					$share_ids[$item['share_id']]['cate_name'] .= '<br/>'.$item['cate_name'];
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
	
	function edit()
	{
		vendor('common');
		$id = $_REQUEST ['share_id'];
		$share = D ("Share")->getById ( $id );
		if(!$share)
		{
			$this->error(L("NO_SHARE"));
		}
		$cache_data = stripslashesDeep(unserialize($share['cache_data']));
		unset($share['cache_data']);
		$share['share_tags'] = '';
		foreach($cache_data['tags']['user'] as $tag)
		{
			$share['share_tags'] .= ' '.$tag['tag_name'];
		}
		$share['share_tags'] = trim($share['share_tags']);
		foreach($cache_data['imgs']['all'] as $img)
		{
			$img['img'] = FS('Image')->getImageUrl($img['img'],2);
			if($img['type'] == 'g')
				$share['share_goods'][] = $img;
			else
				$share['share_photo'][] = $img;
		}

		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$category = D('GoodsCategory')->where('cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$category = D('GoodsCategory')->toFormatTree($category,'cate_name','cate_id','parent_id');
		
		$share_category = FDB::fetchAll("select c.cate_id,c.cate_name from ".FDB::table("share_category")." as sc left join ".FDB::table("goods_category")." as c on sc.cate_id = c.cate_id where sc.share_id = ".$share['share_id']);

		$this->assign ( 'category', $category );
		$this->assign ( 'share_category', $share_category );
		$this->assign ( 'share', $share );
		$this->display ();
	}

	public function remove()
	{
		//删除指定记录
		Vendor('common');
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$share_ids = explode ( ',', $id );
			D('Share')->removeHandler($share_ids);
			$this->saveLog(1,$id);
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}
		die(json_encode($result));
	}

	public function update() {
		
		//B('FilterString');
		$name=$this->getActionName();
		$model = D ( $name );
		if (false === $data = $model->create ()) {
			$this->error ( $model->getError () );
		}
		
		$old_share = D ("Share")->getById($_REQUEST['share_id']);
		vendor("common");
		// 更新数据
		$list=$model->save($_REQUEST);
		$id = $data[$model->getPk()];
		if (false !== $list) {
			$share = D ("Share")->getById($id);
			$rec_data = array();
			$rec_data['title'] = $share['title'];
			$rec_data['content'] = $share['content'];
			
			switch($share['type'])
			{
				case 'bar':
					D('ForumThread')->where("share_id = '$id'")->save($rec_data);
					if($old_share['title'] !=  $share['title'] || $old_share['content'] !=  $share['content'])
						FS("Topic")->updateTopic($share['rec_id'],$share['title'],$share['content']);
				break;

				case 'bar_post':
					if($old_share['content'] !=  $share['content'])
						D('ForumPost')->where("share_id = '$id'")->save($rec_data);
				break;
			}
			
			$tags = ($_REQUEST['tags']);
			$tags = explode(" ",$tags);

            FS('Share')->updateShareTags($data['share_id'],array('user'=>implode(' ',$tags)));
            
            //更新喜欢统计
			FDB::query("UPDATE ".FDB::table("share")." set collect_count = (select count(*) from ".FDB::table("user_collect")." where share_id = '".$data['share_id']."' ) where share_id = '".$data['share_id']."'");
			//更新评论统计
			FDB::query("UPDATE ".FDB::table("share")." set comment_count = (select count(*) from ".FDB::table("share_comment")." where share_id = '".$data['share_id']."' ) where share_id = '".$data['share_id']."'");
            

			//更新分类
			$cates_arr = explode(",",$_REQUEST['share_cates']);
			foreach($cates_arr as $k=>$v)
			{
				if((int)$v > 0)
					$cates[] = (int)$v;
			}

			FS('Share')->updateShareCate($data['share_id'],$cates);
            FS('Share')->deleteShareCache($data['share_id']);
			//成功提示
			$this->saveLog(1,$id);
			//$this->assign ( 'jumpUrl', Cookie::get ( '_currentUrl_' ) );
			$this->success (L('EDIT_SUCCESS'));
		} else {
			//错误提示
			$this->saveLog(0,$id);
			$this->error (L('EDIT_ERROR'));
		}
	}
	
	public function comments()
	{
		if(isset($_REQUEST['share_id']))
			$share_id = intval($_REQUEST['share_id']);
		else
			$share_id = intval($_SESSION['share_comment_share_id']);
		
		$_SESSION['share_comment_share_id'] = $share_id;
		
		$this->assign ( 'share_id', $share_id );
		
		$where = 'WHERE sc.share_id = ' . $share_id;
		$parameter = array();
		$uname = trim($_REQUEST['uname']);

		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid > 0)
				$where.=" AND sc.uid = ".$uid;
		}

		$model = M();
		
		$sql = 'SELECT COUNT(DISTINCT sc.comment_id) AS pcount 
			FROM '.C("DB_PREFIX").'share_comment AS sc 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = sc.uid 
			'.$where;

		$count = $model->query($sql);
		$count = $count[0]['pcount'];

		$sql = 'SELECT sc.comment_id,LEFT(sc.content,80) AS content,u.user_name,sc.create_time,sc.share_id  
			FROM '.C("DB_PREFIX").'share_comment AS sc 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = sc.uid 
			'.$where.' GROUP BY sc.comment_id';
		$this->_sqlList($model,$sql,$count,$parameter,'sc.comment_id',false,'returnUrl1');
		
		$this->display ();
		return;
	}
	
	public function editComment()
	{
		$model = D('ShareComment');
		Cookie::set ( '_currentUrl_',NULL );
		$id = $_REQUEST [$model->getPk ()];
		$vo = $model->getById($id);

		$this->assign ( 'vo', $vo );
		$this->display ();
	}
	
	public function updateComment()
	{
		$model = D('ShareComment');
		if (false === $data = $model->create ()) {
			$this->error ( $model->getError () );
		}
		// 更新数据
		$list=$model->save ();
		$id = $data['comment_id'];
		if (false !== $list) {
			//成功提示
			Vendor("common");
			$share_id = D('ShareComment')->where("comment_id = '$id'")->getField('share_id');
			$key = getDirsById($share_id);
			clearCacheDir('share/'.$key.'/commentlist');
			$this->saveLog(1,$id);
			$this->assign ( 'jumpUrl', Cookie::get ( '_currentUrl_' ) );
			$this->success (L('EDIT_SUCCESS'));
		} else {
			//错误提示
			$this->saveLog(0,$id);
			$this->error (L('EDIT_ERROR'));
		}
	}
	
	public function removeComment()
	{
		//删除指定记录
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ShareComment');
			$pk = 'comment_id';
			$condition = array ($pk => array ('in', explode ( ',', $id ) ) );
			$count = $model->where( $condition )->count();
			$comments = $model->where($condition)->select();
			if(false !== $model->where($condition)->delete())
			{
				Vendor("common");
				$share_id = $_REQUEST['share_id'];
				$key = getDirsById($share_id);
				clearCacheDir('share/'.$key.'/commentlist');
				D('Share')->where("share_id = '$share_id'")->setDec('comment_count',$count);
				FS('Share')->updateShareCache($share_id,'comments');
				$this->saveLog(1,$id);
			}
			else
			{
				$this->saveLog(0,$id);
				$result['isErr'] = 1;
				$result['content'] = L('REMOVE_ERROR');
			}
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}

		die(json_encode($result));
	}
	
	public  function ToExamineSelect()
	{
		//审核所选
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			vendor("common");
			setTimeLimit(0);
			$ids = explode(',',$id);
			foreach($ids as $share_id)
			{
				if($share_id > 0)
					FS('Share')->updateShareStatus($share_id,1);
			}
			$result['isErr'] = 0;
			$this->saveLog(1,$id);
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}
		die(json_encode($result));
	}
	
	public function ToExamineAll(){
		//审核全部
		$result['isErr'] = 1;
		if(D("Share")->setField("status",1)){
			$result['isErr'] = 0;
		}
		die(json_encode($result));
	}
	
	public function ShiftClass(){
		$ids  = $_REQUEST['id'];
		if(empty($ids))
			$this->error (L('EDIT_ERROR'));
		
		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$category = D('GoodsCategory')->where('cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$category = D('GoodsCategory')->toFormatTree($category,'cate_name','cate_id','parent_id');
		
		$this->assign("category",$category);
		$this->assign("share_id",$ids);
		$this->display();
	}
	
	public function updateShiftClass(){
		//更新分类
		$share_ids  = $_REQUEST['share_id'];
		if(empty($share_ids))
			$this->error (L('EDIT_ERROR'));
			
		$cates_arr = explode(",",$_REQUEST['share_cates']);
		foreach($cates_arr as $k=>$v)
		{
			if((int)$v > 0)
				$cates[] = (int)$v;
		}
		
		vendor("common");
		
		$list = FDB::fetchAll("select share_id from ".FDB::table("share")." where share_id in($share_ids) and share_data <>'default' ");
		foreach($list as $k=>$share_data)
		{
			FS('Share')->updateShareCate($share_data['share_id'],$cates);
       		FS('Share')->deleteShareCache($share_data['share_id']);
			
			//成功提示
			$this->saveLog(1,$share_data['share_id']);
		}
		$this->success (L('EDIT_SUCCESS'));
	}
	
	public function BatchDelete(){
		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$category = D('GoodsCategory')->where('cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$category = D('GoodsCategory')->toFormatTree($category,'cate_name','cate_id','parent_id');
		
		$this->assign("category",$category);
		$this->display();
	}
	
	public function doBatchDelte(){
		@set_time_limit(0);
		if(function_exists('ini_set'))
		    ini_set('max_execution_time',0);
		vendor("common");
		$limit = 100;
		$extwhere ="";
		
		if(trim($_REQUEST['user_name']))
		{
			$uid = (int)D('User')->where("user_name = '".trim($_REQUEST['user_name'])."'")->getField('uid');
			$extwhere .= " and uid = ".$uid;
			$this->assign("user_name",trim($_REQUEST['user_name']));
		}
		
		if(trim($_REQUEST['start_time']) && trim($_REQUEST['end_time']))
		{
			$extwhere .= " and day_time between '".strZTime(trim($_REQUEST['start_time']))."' and '".strZTime(trim($_REQUEST['end_time']))."' ";
			$this->assign("start_time",trim($_REQUEST['start_time']));
			$this->assign("end_time",trim($_REQUEST['end_time']));
		}
		elseif(trim($_REQUEST['start_time']) && !trim($_REQUEST['end_time']))
		{
			$extwhere .= " and day_time >= '".strZTime(trim($_REQUEST['start_time']))."' ";
			$this->assign("start_time",trim($_REQUEST['start_time']));
		}
		elseif(!trim($_REQUEST['start_time']) && trim($_REQUEST['end_time']))
		{
			$extwhere .= " and day_time <= '".strZTime(trim($_REQUEST['end_time']))."' ";
			$this->assign("end_time",trim($_REQUEST['end_time']));
		}
		
		if(trim($_REQUEST['status'])!="")
		{
			$extwhere .= " and status = '".trim($_REQUEST['status'])."' ";
			$this->assign("status",trim($_REQUEST['status']));
		}
		
		if(trim($_REQUEST['share_cates'])!="")
		{
			$extwhere .= " and share_id in (select share_id from ".FDB::table('share_category')." where cate_id in (".trim($_REQUEST['share_cates']).") )";
			$this->assign("share_cates",trim($_REQUEST['share_cates']));
		}
		
		if(empty($extwhere))
		{
			$this->error (L('NEED_ONE_PARAMETER'));
			exit;
		}
		$sql = 'SELECT share_id FROM '.FDB::table('share').' where 1=1 '.$extwhere.'  ORDER BY share_id DESC LIMIT 0,'.$limit;
		$list = FDB::fetchAll($sql);
	   
		if(count($list) == 0)
		{
			$this->redirect('Share/BatchDelete');
			exit;
		}
		$this->display();
		
		flush();
		ob_flush();
	    usleep(2000);
	    $ids = array();
	    foreach($list as $k=>$v)
	    {
	    	$ids[] = $v['share_id'];
	    }
	    D('Share')->removeHandler($ids);
		$this->saveLog(1,implode(",",$ids));
		flush();
		ob_flush();
		usleep(100);
		echoFlush('<script type="text/javascript">submiform();</script>');
	}
	
	public function toggleStatus()
	{
		$id = intval($_REQUEST['id']);
		if($id == 0)
			exit;
		
		$val = intval($_REQUEST['val']) == 0 ? 1 : 0;
			
		$field = trim($_REQUEST['field']);
		if(empty($field))
			exit;
		
		$result = array('isErr'=>0,'content'=>'');
		$result['content'] = $val;
		if($field == 'is_look_best')
		{
			if($val == 1)
				D('ShareLookBest')->add(array('share_id'=>$id),array(),true);
			else
				D('ShareLookBest')->where('share_id = '.$id)->delete();
		}
		elseif($field == 'is_dapei_best')
		{
			if($val == 1)
				D('ShareDapeiBest')->add(array('share_id'=>$id),array(),true);
			else
				D('ShareDapeiBest')->where('share_id = '.$id)->delete();
		}
		elseif($field == 'is_index')
		{
			if($val == 0)
			{
				D('Share')->where('share_id = '.$id)->setField('is_index',0);
				$index_cate = D("IndexCateShare")->where('share_id = '.$id)->find();
				if(false !== D("IndexCateShare")->where('share_id = '.$id)->delete())
				{
					$img_ids = array();
					if($index_cate['cimg_id'] > 0)
						$img_ids[] = $index_cate['cimg_id'];

					vendor("common");
					FS('Image')->deleteImages($img_ids);
				}

			}
		}
		elseif($field == 'status')
		{
			vendor("common");
			FS('Share')->updateShareStatus($id,$val);
		}
		else
		{
			$name=$this->getActionName();
			$model = D($name);
			$pk = $model->getPk();
			if(false !== $model->where($pk.' = '.$id)->setField($field,$val))
			{
				$this->saveLog(1,$id,$field);
				$result['content'] = $val;
			}
			else
			{
				$this->saveLog(0,$id,$field);
				$result['isErr'] = 1;
			}
		}
		die(json_encode($result));
	}
	
	public function getCateTags()
	{
		$ids = $_REQUEST['ids'];
		if(empty($ids))
			die('');
		
		vendor("common");
		global $_FANWE;
		$list = array();
		Cache::getInstance()->loadCache('goods_category');
		$cates = explode(",",$ids);
		$cates = array_unique($cates);

		$tags = str_replace("　",' ',$_REQUEST['tags']);
		$tags = explode(' ',$tags);
		$tags = array_unique($tags);
		
		foreach($cates as $cid)
		{
			if($cid > 0)
			{
				if(isset($_FANWE['cache']['goods_category']['all'][$cid]['parents']))
				{
					$parents = $_FANWE['cache']['goods_category']['all'][$cid]['parents'];
					foreach($parents as $pid)
					{
						getCateInfo($pid,$list,$tags);
					}
				}
				getCateInfo($cid,$list,$tags);
			}
		}
		$this->assign("list",$list);
		$this->display();
	}
}

function getCateInfo($cid,&$list,$tags)
{
	if(isset($list[$cid]))
		return;
	
	global $_FANWE;
	$cate = array();
	$cate_name = '';
	if(isset($_FANWE['cache']['goods_category']['all'][$cid]['parents']))
	{
		$parents = $_FANWE['cache']['goods_category']['all'][$cid]['parents'];
		foreach($parents as $pid)
		{
			$cate_name .= $_FANWE['cache']['goods_category']['all'][$pid]['cate_name'].'&nbsp;&gt;&nbsp;';
		}
	}
	$cate_cache = $_FANWE['cache']['goods_category']['all'][$cid];
	$cate_name .= $cate_cache['cate_name'];
	
	$cate['id'] = $cid;
	$cate['name'] = $cate_name;
	$cate['tags'] = array();
	
	Cache::getInstance()->loadCache('goods_category_tags_'.$cid);
	if(isset($_FANWE['cache']['goods_category_tags_'.$cid]))
	{
		foreach($_FANWE['cache']['goods_category_tags_'.$cid] as $tag)
		{
			$checked = in_array($tag['tag_name'],$tags);
			$cate['tags'][] = array(
				'name'=>$tag['tag_name'],
				'checked'=>$checked,
			);
		}
	}
	$list[$cid] = $cate;
}

function getCommentCount($count,$share_id)
{
	if($count>0)
		return "(".$count.")&nbsp;&nbsp; <a href='".U("Share/comments",array("share_id"=>$share_id))."'>".l("CHECK_COMMENT")."</a>";
	else
		return $count;
}

function getTypeName($type)
{
	return L("SHARE_".strtoupper($type));
}

function getShareData($data)
{
	return L("SHARE_DATA_".strtoupper($data));
}

function getHandlerLink($is_index,$share)
{
	if($share['is_index'] == 1)
		return '<span id="is_index_'.$share['share_id'].'"><a href="javascript:;" onclick="toggleStatusOther(this,\''.$share['share_id'].'\',\'is_index\',1)" style="color:#ccc;" module="Share">'.L('IS_INDEX_1').'</a></span>';
	else
		return '<span id="is_index_'.$share['share_id'].'"><a href="'.U("IndexCateShare/add",array("share_id"=>$share['share_id'])).'">'.L('IS_INDEX_0').'</a></span>';
}
?>