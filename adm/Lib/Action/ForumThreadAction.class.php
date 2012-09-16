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
class ForumThreadAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		$uname = trim($_REQUEST['uname']);
		$gname = trim($_REQUEST['gname']);
		
		$is_empty = false;
		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid == 0)
				$is_empty = true;
			else
				$where.=" AND ft.uid = ".$uid;
		}

		if(!empty($gname) && !$is_empty)
		{
			$this->assign("gname",$gname);
			$parameter['gname'] = $gname;
			$fids = D('Forum')->getIdsByKey($gname);
			if(count($fids) > 0)
				$where.=" AND ft.fid IN (".implode(',',$fids).")";
			else
				$is_empty = true;
		}
		
		if(!$is_empty)
		{
			vendor("common");
			if(!empty($keyword))
			{
				$this->assign("keyword",$keyword);
				$parameter['keyword'] = $keyword;
				$match_key = FS('Words')->segmentToUnicode($keyword,'+');
				$is_match = true;
			}

			$model = M();

			$sql = 'SELECT COUNT(DISTINCT ft.tid) AS tcount
			FROM '.C("DB_PREFIX").'forum_thread AS ft 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = ft.uid 
			'.$where;

		$count = $model->query($sql);
		$count = $count[0]['tcount'];

		$sql = 'SELECT ft.tid,LEFT(ft.title,80) AS title,u.user_name,ft.create_time,ft.post_count,ft.is_top,ft.is_best,
			ft.is_event,f.name AS fname,ft.share_id  
			FROM '.C("DB_PREFIX").'forum_thread AS ft 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = ft.uid 
			LEFT JOIN '.C("DB_PREFIX").'forum AS f ON f.fid = ft.fid 
			'.$where.' GROUP BY ft.tid';
		$this->_sqlList($model,$sql,$count,$parameter,'ft.tid');
		
			
			$append_sql = '';
			$sql_count = 'SELECT COUNT(ft.tid) AS gcount FROM '.C("DB_PREFIX").'forum_thread AS ft ';
			$sql = 'SELECT ft.tid,LEFT(ft.title,80) AS title,u.user_name,ft.create_time,ft.post_count,ft.is_top,ft.is_best,
				f.name AS fname,ft.share_id FROM '.C("DB_PREFIX").'forum_thread AS ft ';
			if($is_match)
			{
				$sql_count = 'SELECT COUNT(ftm.tid) AS gcount FROM '.C("DB_PREFIX").'forum_thread_match AS ftm ';
				$sql = 'SELECT ft.tid,LEFT(ft.title,80) AS title,u.user_name,ft.create_time,ft.post_count,ft.is_top,ft.is_best,
					f.name AS fname,ft.share_id FROM '.C("DB_PREFIX").'forum_thread_match AS ftm ';
				$append_sql = 'INNER JOIN '.C("DB_PREFIX").'forum_thread AS ft ON ft.tid = ftm.tid ';
				$sql_count .= $append_sql;
				$sql .= $append_sql;
				$where.=" AND match(ftm.content) against('".$match_key."' IN BOOLEAN MODE) ";
			}

			$sql .= ' LEFT JOIN '.C("DB_PREFIX").'forum AS f ON f.fid = ft.fid 
				INNER JOIN '.C("DB_PREFIX").'user AS u ON u.uid = ft.uid ';
		
			if(!empty($where))
				$where = str_replace('WHERE AND','WHERE','WHERE'.$where);

			$sql_count .= $where;
			$sql .= $where;

			$count = $model->query($sql_count);
			$count = $count[0]['gcount'];

			$this->_sqlList($model,$sql,$count,$parameter,'ft.tid');
		}
		
		$this->display ();
		return;
	}

	public function edit()
	{
		vendor("common");
		$id = intval($_REQUEST['tid']);

		$vo = D("ForumThread")->getById($id);
		$this->assign ('vo',$vo);

		$fname = D("Forum")->where('fid = '.$vo['fid'])->getField('name');
		$this->assign ('fname',$fname);
		
		$this->display();
	}

	public function update()
	{
        Vendor("common");
		$_POST['is_best'] = intval($_POST['is_best']);
		$_POST['is_top'] = intval($_POST['is_top']);
		$_POST['is_event'] = intval($_POST['is_event']);
        $tid = intval($_REQUEST['tid']);
        $topic = D('ForumThread')->where("tid = '$tid'")->find();
		
        if($topic['share_id'] > 0)
        {
			$share_id = $topic['share_id'];
            $title = trim($_REQUEST['title']);
            $content = trim($_REQUEST['content']);
			if($topic['title'] != $title || $topic['content'] != $content)
            	FS("Share")->updateShare($share_id,$title,$content);
        }
		parent::update();
	}

	public function remove()
	{
		//删除指定记录
		Vendor("common");
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$name=$this->getActionName();
			$model = D($name);
			$pk = $model->getPk ();
			$condition = array ($pk => array ('in', explode ( ',', $id ) ) );
            $ids = explode (',',$id);
            foreach($ids as $tid)
            {
                FS("Topic")->deleteTopic($tid);
            }

			$this->saveLog(1,$id);
		}
		else
		{
			$result['isErr'] = 1;
			$result['content'] = L('ACCESS_DENIED');
		}

		die(json_encode($result));
	}
}


function getForumName($fid)
{
	return M("Forum")->where("fid=".$fid)->getField("name");
}

function getPostCount($count,$tid)
{
	if($count>0)
		return "(".$count.")&nbsp;&nbsp; <a href='".u("ForumPost/index",array("tid"=>$tid))."'>".l("CHECK_REPLY")."</a>";
	else
		return $count;
}

?>