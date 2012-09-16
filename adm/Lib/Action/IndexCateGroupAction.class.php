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
 首页分类分组设置
 +------------------------------------------------------------------------------
 */
class IndexCateGroupAction extends CommonAction
{
	public function index()
	{
		vendor("common");
		if(isset($_REQUEST['cate_id']))
			$cate_id = intval($_REQUEST['cate_id']);
		else
			$cate_id = intval($_SESSION['index_cate_share_cate_id']);
		
		$_SESSION['index_cate_share_cate_id'] = $cate_id;
		
		$parameter = array();
		$parameter['cid'] = $cate_id;
		$where = 'icg.cid = '.$cate_id;
		$this->assign("cid",$cate_id);
		
		$model = M();
		
		$sql = 'SELECT COUNT(DISTINCT icg.id) AS tcount FROM '.C("DB_PREFIX").'index_cate_group as icg 
				WHERE '.$where;

		$count = $model->query($sql);
		$count = $count[0]['tcount'];
		
		$sql = 'SELECT icg.*,gc.cate_name FROM '.C("DB_PREFIX").'index_cate_group as icg 
				INNER JOIN '.C("DB_PREFIX").'goods_category as gc ON gc.cate_id = icg.cid 
				WHERE '.$where;
		
		$this->_sqlList($model,$sql,$count,$parameter,'sort',true);
		$list = $this->list;
		
		$ur_href = $this->ur_href;
		$cate_name = D('GoodsCategory')->where("cate_id = '".$cate_id."'")->getField('cate_name');
		$ur_href = $cate_name.$ur_href;
		$this->assign("ur_href",$ur_href);
		
		$this->assign("list",$list);
		$this->display ();
		return;
	}
	
	public function add()
	{
		$ur_href = $this->ur_href;
		if(isset($_REQUEST['cate_id']))
			$cate_id = intval($_REQUEST['cate_id']);
		else
			$cate_id = intval($_SESSION['index_cate_share_cate_id']);
		$_SESSION['index_cate_share_cate_id'] = $cate_id;
		
		$cate_name = D('GoodsCategory')->where("cate_id = '".$cate_id."'")->getField('cate_name');
		$ur_href = $cate_name.$ur_href;
		$this->assign("ur_href",$ur_href);
		$this->assign("cate_id",$cate_id);
		$this->assign("cate_name",$cate_name);
		parent::add();
	}
	
	public function insert()
	{
		$tags = str_replace("，",',',$_REQUEST['tags']);
		$tags = explode(',',$tags);
		$tags = array_unique($tags);
		$tags = array_unique($tags);
		if(count($tags) > 10)
			$tags = array_slice($tags,0,10);
		$_POST['tags'] = implode(',',$tags);
		parent::insert();
	}
	
	public function edit()
	{
		$id = (int)$_REQUEST['id'];
		$vo = D("IndexCateGroup")->getById($id);
		$this->assign ('vo', $vo);
		$ur_href = $this->ur_href;
		$cate_name = D('GoodsCategory')->where("cate_id = '".$vo['cid']."'")->getField('cate_name');
		$ur_href = $cate_name.$ur_href;
		$this->assign("ur_href",$ur_href);
		$this->assign("cate_id",$cate_id);
		$this->assign("cate_name",$cate_name);
		$this->display();
	}
	
	public function update()
	{
		$tags = str_replace("，",',',$_REQUEST['tags']);
		$tags = explode(',',$tags);
		$tags = array_unique($tags);
		if(count($tags) > 10)
			$tags = array_slice($tags,0,10);
		$_POST['tags'] = implode(',',$tags);
		
		parent::update();
	}
	
	public function remove()
	{
		//删除指定记录
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$condition = array('gid' => array('in',explode (',',$id)));
			if(D("IndexCateShare")->where($condition)->count() > 0)
			{
				$result['isErr'] = 1;
				$result['content'] = L('SHARE_EXIST');
				die(json_encode($result));
			}
			
			$model = D("IndexCateGroup");
			$condition = array('id' => array('in',explode (',',$id)));
			$list = $model->where($condition)->select();
			if(false !== $model->where ( $condition )->delete())
			{
				$this->saveLog(1,$id);
			}
			else
			{
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
	
	public function getGroups()
	{
		$cid = (int)$_REQUEST['cid'];
		$list = array();
		if($cid > 0)
			$list = D("IndexCateGroup")->where('cid = '.$cid)->select();
		echo json_encode($list);
	}
}
?>