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
class ForumPostAction extends CommonAction
{
	public function index()
	{
		if(isset($_REQUEST['tid']))
			$tid = intval($_REQUEST['tid']);
		else
			$tid = intval($_SESSION['forum_post_tid']);
		
		$_SESSION['forum_post_tid'] = $tid;
		
		$where = 'WHERE fp.tid = ' . $tid;
		$parameter = array();
		$uname = trim($_REQUEST['uname']);

		if(!empty($uname))
		{
			$this->assign("uname",$uname);
			$parameter['uname'] = $uname;
			$uid = (int)D('User')->where("user_name = '".$uname."'")->getField('uid');
			if($uid > 0)
				$where.=" AND fp.uid = ".$uid;
		}

		$model = M();
		
		$sql = 'SELECT COUNT(DISTINCT fp.pid) AS pcount 
			FROM '.C("DB_PREFIX").'forum_post AS fp 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = fp.uid 
			'.$where;

		$count = $model->query($sql);
		$count = $count[0]['pcount'];

		$sql = 'SELECT fp.pid,LEFT(fp.content,80) AS content,u.user_name,fp.create_time,fp.share_id  
			FROM '.C("DB_PREFIX").'forum_post AS fp 
			LEFT JOIN '.C("DB_PREFIX").'user AS u ON u.uid = fp.uid 
			'.$where.' GROUP BY fp.pid';
		$this->_sqlList($model,$sql,$count,$parameter,'fp.pid',false,'returnUrl1');
		
		$this->display ();
		return;
	}

    public function update()
    {
        Vendor("common");
        $pid = intval($_REQUEST['pid']);
        $share_id = D('ForumPost')->where("pid = '$pid'")->getField('share_id');
        if($share_id > 0)
        {
            $content = trim($_REQUEST['content']);
            FS("Share")->updateShare($share_id,'',$content);
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
			$count = $model->where ( $condition )->count();
			$res = $model->where ( $condition )->select();

			foreach($res as $item)
			{
				$share_id = intval($item['share_id']);
                FS("Topic")->deletePost($share_id);
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

	public function edit()
	{
		Cookie::set ( '_currentUrl_',NULL );
		parent::edit();
	}

}

?>