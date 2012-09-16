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
 首页分类分享设置
 +------------------------------------------------------------------------------
 */
class IndexCateShareAction extends CommonAction
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
		$where = 'ics.cid = '.$cate_id;
		$this->assign("cid",$cate_id);
		
		$model = M();
		
		$sql = 'SELECT COUNT(DISTINCT ics.share_id) AS tcount FROM '.C("DB_PREFIX").'index_cate_share as ics 
				WHERE '.$where;

		$count = $model->query($sql);
		$count = $count[0]['tcount'];
		
		$sql = 'SELECT ics.*,gc.cate_name,u.user_name,icg.name AS group_name FROM '.C("DB_PREFIX").'index_cate_share as ics 
				LEFT JOIN '.C("DB_PREFIX").'goods_category as gc ON gc.cate_id = ics.cid 
				LEFT JOIN '.C("DB_PREFIX").'index_cate_group as icg ON icg.id = ics.gid 
				LEFT JOIN '.C("DB_PREFIX").'user as u ON u.uid = ics.uid   
				WHERE '.$where;
		
		$this->_sqlList($model,$sql,$count,$parameter,'sort',true);
		$list = $this->list;
		
		$img_ids = array();
		foreach($list as $key => $item)
		{
			$list[$key]['cimg'] = '';
			if($item['cimg_id'] > 0)
				$img_ids[$item['cimg_id']][] = &$list[$key]['cimg'];
			
			$list[$key]['share_img'] = '';
			if($item['img_id'] > 0)
				$img_ids[$item['img_id']][] = &$list[$key]['share_img'];
		}
		
		FS('Image')->formatByIdKeys($img_ids,true);
		foreach($list as $key => $item)
		{
			if(!empty($list[$key]['cimg']))
			{
				$list[$key]['cimg'] = '<img width="30" height="30" src="'.FS('Image')->getImageUrl($list[$key]['cimg'],2).'" style="border:solid 1px #ccc;"/>';
			}
			
			if(!empty($list[$key]['share_img']))
			{
				$list[$key]['share_img'] = '<img width="30" height="30" src="'.FS('Image')->getImageUrl($list[$key]['share_img'],2).'" style="border:solid 1px #ccc;"/>';
			}
		}
		
		$this->assign("list",$list);
		$this->display ();
		return;
	}
	
	public function add()
	{
		vendor("common");
		$share_id = (int)$_REQUEST['share_id'];
		if($share_id == 0)
			$this->redirect('Share/image');
		
		$share = D("Share")->where('share_id = '.$share_id)->find();
		if(!isset($share['share_id']))
			$this->redirect('Share/image');
		
		$share['user_name'] = D('User')->where("uid = '".$share['uid']."'")->getField('user_name');
		
		$cache_data = stripslashesDeep(unserialize($share['cache_data']));
		unset($share['cache_data']);
		$share['tags'] = '';
		foreach($cache_data['tags']['user'] as $tag)
		{
			$share['tags'] .= '  '.$tag['tag_name'];
		}
		$share['tags'] = trim($share['tags']);
		$share['imgs'] = $cache_data['imgs']['all'];
		$this->assign("share",$share);
		
		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$cate_list = D('GoodsCategory')->where('status = 1 AND is_index = 1 AND cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$cate_list = D('GoodsCategory')->toFormatTree($cate_list,'cate_name','cate_id','parent_id');
		$this->assign("cate_list",$cate_list);
		parent::add();
	}
	
	public function insert()
	{
		$name = trim($_REQUEST['name']);
		$model = D("IndexCateShare");
		if(false === $data = $model->create())
		{
			$this->error($model->getError());
		}
		
		//保存当前数据对象
		$list=$model->add($data);
		if ($list !== false)
		{
			if(isset($_FILES['cimg']))
			{
				vendor("common");
				$img = FS('Image')->save('cimg','temp',false);
				if($img)
				{
					$image = array();
					$image['src'] = $img['path'];
					$image['type'] = 'default';
					$image = FS('Image')->addImage($image);
					D("IndexCateShare")->where('share_id = '.(int)$_REQUEST['share_id'])->setField('cimg_id',$image['id']);
				}
			}
			D("Share")->where('share_id = '.(int)$_POST['share_id'])->setField('is_index',1);
			$this->saveLog(1,$list);
			$this->assign('jumpUrl', Cookie::get('returnUrl'));
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
		$share_id = (int)$_REQUEST['share_id'];
		if($share_id == 0)
			$this->redirect('Share/image');
		
		$share = D("Share")->where('share_id = '.$share_id)->find();
		if(!isset($share['share_id']))
			$this->redirect('Share/image');
			
		$vo = D("IndexCateShare")->getById($share_id);
		$this->assign('vo',$vo);
		
		$share['user_name'] = D('User')->where("uid = '".$share['uid']."'")->getField('user_name');
		$cache_data = stripslashesDeep(unserialize($share['cache_data']));
		unset($share['cache_data']);
		$share['tags'] = '';
		foreach($cache_data['tags']['user'] as $tag)
		{
			$share['tags'] .= '  '.$tag['tag_name'];
		}
		$share['tags'] = trim($share['tags']);
		$share['imgs'] = $cache_data['imgs']['all'];
		$this->assign("share",$share);
		
		$cate_groups = D("IndexCateGroup")->where('cid = '.$vo['cid'])->select();
		$this->assign("cate_groups",$cate_groups);
		
		$root_id = D('GoodsCategory')->where('is_root = 1')->getField('cate_id');
		$root_id = intval($root_id);
		$root_ids = D('GoodsCategory')->getChildIds($root_id,'cate_id');
		$root_ids[] = $root_id;
		
		$cate_list = D('GoodsCategory')->where('status = 1 AND is_index = 1 AND cate_id not in ('.implode(',',$root_ids).')')->order('sort asc')->select();
		$cate_list = D('GoodsCategory')->toFormatTree($cate_list,'cate_name','cate_id','parent_id');
		$this->assign("cate_list",$cate_list);
		$this->display();
	}
	
	public function update()
	{
		$id = intval($_REQUEST['share_id']);
		$model = D("IndexCateShare");
		if (false === $data = $model->create ()) {
			$this->error ($model->getError());
		}
		
		// 更新数据
		$list=$model->save($data);
		if (false !== $list)
		{
			if(isset($_FILES['cimg']))
			{
				vendor("common");
				$img = FS('Image')->save('cimg','temp',false);
				if($img)
				{
					$image = array();
					$image['src'] = $img['path'];
					$image['type'] = 'default';
					$old = D("IndexCateShare")->getById($id);
					if($old['cimg_id'] > 0)
					{
						$image['id'] = $old['cimg_id'];
						FS('Image')->updateImage($image,true);
					}
					else
					{
						$image = FS('Image')->addImage($image);
						D("IndexCateShare")->where('share_id = '.$id)->setField('cimg_id',$image['id']);
					}
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
			$model = D("IndexCateShare");
			$condition = array('share_id' => array('in',explode (',',$id)));
			$list = $model->where ($condition)->select();
			if(false !== $model->where ( $condition )->delete())
			{
				D('Share')->where($condition)->setField('is_index',0);
				$img_ids = array();
				foreach($list as $item)
				{
					if($item['cimg_id'] > 0)
						$img_ids[] = $item['cimg_id'];
				}
				vendor("common");
				FS('Image')->deleteImages($img_ids);
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
}
?>