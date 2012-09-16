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
 手机端搜索页配置
 +------------------------------------------------------------------------------
 */
class MSearchcateAction extends CommonAction
{
	public function index()
	{
		$model = M();
		$sql = 'SELECT COUNT(DISTINCT id) AS acount 
			FROM '.C("DB_PREFIX").'m_searchcate ';

		$count = $model->query($sql);
		$count = $count[0]['acount'];

		$sql = 'SELECT ms.*,gc.cate_name FROM '.C("DB_PREFIX").'m_searchcate AS ms 
			LEFT JOIN '.C("DB_PREFIX").'goods_category AS gc ON gc.cate_id = ms.cid ';
		$this->_sqlList($model,$sql,$count,$parameter,'id');
		
		$this->display ();
	}

	public function add()
	{
		$cate_list = D("GoodsCategory")->where('status = 1')->field('cate_id,parent_id,cate_name')->order('sort ASC,cate_id ASC')->select();
		$cate_list = D("GoodsCategory")->toFormatTree($cate_list,array('cate_name'),'cate_id');
		$this->assign("cate_list",$cate_list);
		parent::add();
	}
	
	public function insert()
	{
		$_POST['tags'] = '';
		if(is_array($_POST['tag']))
		{
			$tags = array();
			$tag_temp = array();

			foreach($_POST['tag'] as $tk => $tag)
			{
				if(empty($tag))
					continue;

				if(isset($tag_temp[$tag]))
					continue;
				
				$tags[] = array('tag'=>$tag,'color'=>$_POST['tagcolor'][$tk]);
				$tag_temp[$tag] = 1;
			}
			$_POST['tags'] = serialize($tags);
			unset($tag_temp,$tags);
		}
		
		$model = D("MSearchcate");
		if(false === $data = $model->create())
		{
			$this->error($model->getError());
		}
		
		//保存当前数据对象
		$id = $model->add($data);
		if ($id !== false)
		{
			$upload_list = $this->uploadImages(0,'m');
			if($upload_list)
			{
				$img = $upload_list[0]['recpath'].$upload_list[0]['savename'];
				if(!empty($img))
					D("MSearchcate")->where('id = '.$id)->setField('bg',$img);
			}
			
			$this->saveLog(1,$id);
			$this->success (L('ADD_SUCCESS'));

		}
		else
		{
			$this->saveLog(0);
			$this->error (L('ADD_ERROR'));
		}
	}
	
	public function edit()
	{
		$id = intval($_REQUEST['id']);
		$vo = D("MSearchcate")->getById($id);
		$vo['tags'] = stripslashesDeep(unserialize($vo['tags']));
		if(is_array($vo['tags']) && count($vo['tags']) > 0)
		{
			$vo['tags'] = array_chunk($vo['tags'],3);
		}
		
		$this->assign ('vo', $vo);
		$cate_list = D("GoodsCategory")->where('status = 1')->field('cate_id,parent_id,cate_name')->order('sort ASC,cate_id ASC')->select();
		$cate_list = D("GoodsCategory")->toFormatTree($cate_list,array('cate_name'),'cate_id');
		$this->assign("cate_list",$cate_list);

		$this->display();
	}
	
	public function update()
	{
		$id = intval($_REQUEST['id']);
		$_POST['tags'] = '';
		if(is_array($_POST['tag']))
		{
			$tags = array();
			$tag_temp = array();

			foreach($_POST['tag'] as $tk => $tag)
			{
				if(empty($tag))
					continue;

				if(isset($tag_temp[$tag]))
					continue;
				
				$tags[] = array('tag'=>$tag,'color'=>$_POST['tagcolor'][$tk]);
				$tag_temp[$tag] = 1;
			}
			$_POST['tags'] = serialize($tags);
			unset($tag_temp,$tags);
		}
		
		$model = D("MSearchcate");
		if(false === $data = $model->create())
		{
			$this->error($model->getError());
		}
		
		//保存当前数据对象
		$list=$model->save($data);
		if (false !== $list)
		{
			$upload_list = $this->uploadImages(0,'m');
			if($upload_list)
			{
				$img = $upload_list[0]['recpath'].$upload_list[0]['savename'];
				if(!empty($img))
				{
					$old_img = D("MSearchcate")->where('id = '.$id)->getField('bg');
					if(!empty($old_img))
						@unlink(FANWE_ROOT.$old_img);
					D("MSearchcate")->where('id = '.$id)->setField('bg',$img);
				}
			}
			
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
			$advs = $model->where($condition)->select();
			if(false !== $model->where ( $condition )->delete ())
			{
				foreach($advs as $adv)
				{
					if(!empty($adv['bg']))
						@unlink(FANWE_ROOT.$adv['bg']);
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
}

function getTypeName($type)
{
	return L('type_'.$type);
}
?>