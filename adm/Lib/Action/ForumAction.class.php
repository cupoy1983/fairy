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

 +------------------------------------------------------------------------------
 */
class ForumAction extends CommonAction
{
	public function index()
	{
		vendor("common");
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$cate_id = intval($_REQUEST['cate_id']);
		$uname = trim($_REQUEST['uname']);
		
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND f.uid = ".$uid;
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

			if($cate_id > 0)
			{
				$is_cate = true;
				$this->assign("cate_id",$cate_id);
				$parameter['cate_id'] = $cate_id;
				$where .= " AND f.cid = ".$cate_id;
			}

			$model = M();
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(f.fid) AS gcount FROM '.C("DB_PREFIX").'forum AS f ';
			$sql = 'SELECT f.*,fc.cate_name,u.user_name FROM '.C("DB_PREFIX").'forum AS f ';
			if($is_match)
			{
				$sql_count = 'SELECT COUNT(fm.fid) AS gcount FROM '.C("DB_PREFIX").'forum_match AS fm ';
				$sql = 'SELECT f.*,fc.cate_name,u.user_name FROM '.C("DB_PREFIX").'forum_match AS fm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").'forum AS f ON f.fid = fm.fid ';
				$sql_count .= $append_sql;
				$sql .= $append_sql;
				$where.=" AND match(fm.content) against('".$match_key."' IN BOOLEAN MODE) ";
			}

			$sql .= ' LEFT JOIN '.C("DB_PREFIX").'forum_category AS fc ON fc.id = f.cid 
				LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = f.uid ';
		
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);

			$sql_count .= $where;
			$sql .= $where;

			$count = $model->query($sql_count);
			$count = $count[0]['gcount'];

			$this->_sqlList($model,$sql,$count,$parameter,'f.fid');
		}
		
		$cate_list = D('ForumCategory')->order('sort asc')->select();
		$this->assign("cate_list",$cate_list);
		$this->display();
	}

	public function check()
	{
		$where = '';
		$parameter = array();
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
				$where.=" WHERE f.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$model = M();
			
			$sql_count = 'SELECT COUNT(f.id) AS scount FROM '.C("DB_PREFIX").'forum_apply AS f ';
			$sql = 'SELECT f.*,u.user_name FROM '.C("DB_PREFIX").'forum_apply AS f 
				INNER JOIN '.C("DB_PREFIX").'user AS u ON u.uid = f.uid ';
			

			$count = $model->query($sql_count.$where);
			$count = $count[0]['scount'];
			
			$this->_sqlList($model,$sql.$where,$count,$parameter,'f.id',true);
		}
		
		$this->display();
	}
	
	public function show()
	{	
		$id = intval($_REQUEST['id']);
		$vo = D("ForumApply")->getById($id);
		if($vo)
		{
			vendor("common");
			$vo['user_name'] = D("User")->where('uid = '.$vo['uid'])->getField('user_name');
			$cache_data = fStripslashes(unserialize($vo['data']));
			$vo = array_merge($vo,$cache_data);
			$this->assign('vo',$vo);
			$this->display();
		}
	}

	public function checkOk()
	{
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ForumApply');
			$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
			$list = $model->where($condition)->select();
			if(false !== $model->where ( $condition )->delete ())
			{
				vendor("common");
				foreach($list as $apply)
				{
					$group = fStripslashes(unserialize($apply['data']));
					$group['uid'] = $apply['uid'];
					$group['name'] = addslashes($apply['name']);
					$group['content'] = addslashes($group['content']);
					$group['create_time'] = $apply['create_time'];
					$fid = FS('Group')->createGroup($group);
					if($fid > 0)
						FS('Group')->groupApplyNotice($fid,$group['uid'],$group['name']);
				}
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

	public function noCheckOk()
	{
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ForumApply');
			$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
			$list = $model->where($condition)->select();
			if(false !== $model->where ( $condition )->delete ())
			{
				vendor("common");
				foreach($list as $apply)
				{
					FS('Group')->groupApplyNotice(0,$apply['uid'],$apply['name']);
				}
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
	
	public function showUpdate()
	{
		$where = '';
		$parameter = array();
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
				$where.=" WHERE f.uid = ".$uid;
		}
		
		if(!$is_empty)
		{
			$model = M();
			
			$sql_count = 'SELECT COUNT(f.fid) AS scount FROM '.C("DB_PREFIX").'forum_update AS f ';
			$sql = 'SELECT f.*,f1.name,u.user_name FROM '.C("DB_PREFIX").'forum_update AS f 
				INNER JOIN '.C("DB_PREFIX").'forum AS f1 ON f1.fid = f.fid 
				INNER JOIN '.C("DB_PREFIX").'user AS u ON u.uid = f.uid ';
			

			$count = $model->query($sql_count.$where);
			$count = $count[0]['scount'];
			
			$this->_sqlList($model,$sql.$where,$count,$parameter,'f.update_time',true);
		}
		
		$this->display();
	}
	
	public function removeUpdate()
	{
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ForumUpdate');
			$condition = array ('fid' => array ('in', explode ( ',', $id ) ) );
			$list = $model->where($condition)->select();
			if(false !== $model->where ( $condition )->delete ())
			{
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

	public function add()
	{	
		$cate_list = D('ForumCategory')->where('status = 1')->order('sort asc')->select();
		$this->assign("cate_list",$cate_list);
		parent::add();
	}

	public function insert()
	{
		$desc = trim($_REQUEST['desc']);
		$model = D("Forum");
		if(false === $data = $model->create())
		{
			$this->error($model->getError());
		}
		
		//保存当前数据对象
		vendor("common");
		if (FS('Group')->createGroup($_POST) > 0)
		{
			$this->saveLog(1,$list);
			$this->success (L('ADD_SUCCESS'));

		}
		else
		{
			$this->saveLog(0,$list);
			$this->error (L('ADD_ERROR'));
		}
	}
	
	public function edit()
	{
		vendor("common");
		$id = intval($_REQUEST['fid']);

		$vo = D("Forum")->getById($id);
		$this->assign ('vo',$vo);

		$user_name = D("User")->where('uid = '.$vo['uid'])->getField('user_name');
		$this->assign ('user_name',$user_name);

		//小组分类
		$cate_list = D('ForumCategory')->where('status = 1')->order('sort asc')->select();
		$this->assign("cate_list",$cate_list);
		
		$this->display();
	}
	
	public function update()
	{
		$id = intval($_REQUEST['fid']);
		$model = D("Forum");
		if (false === $data = $model->create ()) {
			$this->error ( $model->getError () );
		}
		
		// 更新数据
		vendor("common");
		if (FS('Group')->saveGroup($_POST,1) == 1)
		{
			$this->saveLog(1,$id);
			$this->assign('jumpUrl', Cookie::get ( '_currentUrl_' ) );
			$this->success (L('EDIT_SUCCESS'));
		}
		else
		{
			//错误提示
			$this->saveLog(0,$id);
			$this->error (L('EDIT_ERROR'));
		}
	}
	
	public function remove()
	{
		//删除指定记录
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$name=$this->getActionName();
			$model = D($name);
			$pk = $model->getPk ();
			$condition = array ($pk => array ('in', explode ( ',', $id ) ) );
			
			if(M("ForumThread")->where(array ("fid" => array ('in', explode ( ',', $id ) ) ))->count()>0)
			{
				$result['isErr'] = 1;
				$result['content'] = L('THREAD_EXIST');
				die(json_encode($result));
			}

			vendor("common");
			$ids = explode (',',$id );
			foreach($ids as $fid)
			{
				FS('Group')->removeGroup($fid);
			}
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}
		
		die(json_encode($result));
	}

	public function search()
	{
		Vendor("common");
		$key = trim($_REQUEST['key']);
		$where = '';
		if(!empty($key))
        {
			$match_key = FS('Words')->segmentToUnicode($key,'+');
			$where.=" match(fm.content) against('".$match_key."' IN BOOLEAN MODE) ";
        }
		
		if(empty($where))
			$sql = 'SELECT fid,name FROM '.C("DB_PREFIX").'forum ORDER BY fid DESC limit 0,30';
		else
			$sql = 'SELECT f.fid,f.name FROM '.C("DB_PREFIX").'forum_match as fm 
				INNER JOIN '.C("DB_PREFIX").'forum as f ON f.fid = fm.fid 
				WHERE '.$where.' ORDER BY fm.fid DESC limit 0,30';

		$userList = M()->query($sql);
		echo json_encode($userList);
	}
}
?>