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
 手机端首页菜单
 +------------------------------------------------------------------------------
 */
class MIndexAction extends CommonAction
{
	public function add()
	{
		$cate_list = D("GoodsCategory")->where('status = 1')->field('cate_id,parent_id,cate_name')->order('sort ASC,cate_id ASC')->select();
		$cate_list = D("GoodsCategory")->toFormatTree($cate_list,array('cate_name'),'cate_id');
		$this->assign("cate_list",$cate_list);
		parent::add();
	}
	
	public function insert()
	{
		$_POST['data'] = "";
		switch($_POST['type'])
		{
			case 1:
				$adv_data['cid'] = (int)$_POST['cid'];
				$tags = str_replace('　',' ',$_POST['tags']);
				$tags = explode(' ',$tags);
				$adv_data['tags'] = array_unique($tags);
				$_POST['data'] = serialize($adv_data);
			break;

			case 2:
				$adv_data['url'] = trim($_POST['url']);
				$_POST['data'] = serialize($adv_data);
			break;

			case 8:
				$adv_data['share_id'] = (int)$_POST['share_id'];
				$_POST['data'] = serialize($adv_data);
			break;
		}
			
		$model = D("MIndex");
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
					D("MIndex")->where('id = '.$id)->setField('img',$img);
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
		$vo = D("MIndex")->getById($id);
		$vo['data'] = stripslashesDeep(unserialize($vo['data']));
		if(isset($vo['data']['tags']))
			$vo['data']['tags'] = implode(' ',$vo['data']['tags']);
		
		$this->assign ('vo', $vo);
		$cate_list = D("GoodsCategory")->where('status = 1')->field('cate_id,parent_id,cate_name')->order('sort ASC,cate_id ASC')->select();
		$cate_list = D("GoodsCategory")->toFormatTree($cate_list,array('cate_name'),'cate_id');
		$this->assign("cate_list",$cate_list);

		$this->display();
	}
	
	public function update()
	{
		$id = intval($_REQUEST['id']);
		$_POST['data'] = "";
		switch($_POST['type'])
		{
			case 1:
				$adv_data['cid'] = (int)$_POST['cid'];
				$tags = str_replace('　',' ',$_POST['tags']);
				$tags = explode(' ',$tags);
				$adv_data['tags'] = array_unique($tags);
				$_POST['data'] = serialize($adv_data);
			break;

			case 2:
				$adv_data['url'] = trim($_POST['url']);
				$_POST['data'] = serialize($adv_data);
			break;

			case 8:
				$adv_data['share_id'] = (int)$_POST['share_id'];
				$_POST['data'] = serialize($adv_data);
			break;
		}
		
		$model = D("MIndex");
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
					$old_img = D("MIndex")->where('id = '.$id)->getField('img');
					if(!empty($old_img))
						@unlink(FANWE_ROOT.$old_img);
					D("MIndex")->where('id = '.$id)->setField('img',$img);
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
					if(!empty($adv['img']))
						@unlink(FANWE_ROOT.$adv['img']);
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