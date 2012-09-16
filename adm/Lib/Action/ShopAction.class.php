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
class ShopAction extends CommonAction
{
	public function index()
	{
		$where = '';
		$parameter = array();
		$keyword = trim($_REQUEST['keyword']);
		
		$is_match = false;
		if(!empty($keyword))
		{
			vendor("common");
			$this->assign("keyword",$keyword);
			$parameter['keyword'] = $keyword;
			$match_key = FS('Words')->segmentToUnicode($keyword,'+');
			$is_match = true;
		}
		
		$sql_count = 'SELECT COUNT(s.shop_id) AS scount FROM '.C("DB_PREFIX").'shop AS s ';
		$sql = 'SELECT s.* FROM '.C("DB_PREFIX").'shop AS s ';
		if($is_match)
		{
			$where = " WHERE match(sm.shop_name) against('".$match_key."' IN BOOLEAN MODE) ";
			$sql_count = 'SELECT COUNT(sm.id) AS scount FROM '.C("DB_PREFIX").'shop_match AS sm '.$where;
			
			$sql = 'SELECT s.* FROM '.C("DB_PREFIX").'shop_match AS sm 
				INNER JOIN '.C("DB_PREFIX").'shop AS s ON s.shop_id = sm.id '.$where;
			
		}
		
		$model = M();
		$count = $model->query($sql_count);
		$count = $count[0]['scount'];

		$this->_sqlList($model,$sql,$count,$parameter,'shop_id');
		$this->display();
	}
	
	public function add()
	{	
		$cate_tree = M("ShopCategory")->select();
		$cate_tree = D("ShopCategory")->toFormatTree($cate_tree,'name','id','parent_id');
		$this->assign("cate_tree",$cate_tree);
		parent::add();
	}
	
	public function insert()
	{
		vendor("common");
		if(isset($_FILES['shop_logo']))
		{
			$img = FS('Image')->save('shop_logo','temp',false);
			if($img)
			{
				$image = array();
				$image['src'] = $img['path'];
				$image['type'] = 'default';
				$image = FS('Image')->addImage($image);
				$_POST['shop_logo'] = $image['id'];
			}
		}
		parent::insert();
	}
	
	
	public function edit()
	{
		vendor("common");
		$cate_tree = M("ShopCategory")->select();
		$cate_tree = D("ShopCategory")->toFormatTree($cate_tree,'name','id','parent_id');
		$this->assign("cate_tree",$cate_tree);
		parent::edit();
	}
	
	public function update()
	{
		$id = intval($_REQUEST['shop_id']);
		$model = D("Shop");
		if (false === $data = $model->create ()) {
			$this->error ($model->getError());
		}
		
		// 更新数据
		$list=$model->save($data);
		if (false !== $list)
		{
			if(isset($_FILES['shop_logo']))
			{
				vendor("common");
				$img = FS('Image')->save('shop_logo','temp',false);
				if($img)
				{
					$image = array();
					$image['src'] = $img['path'];
					$image['type'] = 'default';
					$old = D("Shop")->getById($id);
					if($old['shop_logo'] > 0)
					{
						$image['id'] = $old['shop_logo'];
						FS('Image')->updateImage($image,true);
					}
					else
					{
						$image = FS('Image')->addImage($image);
						D("Shop")->where('shop_id = '.$id)->setField('shop_logo',$image['id']);
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
		$_SESSION['shop_remove_ids'] = '';
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D("Shop");
			$condition = array('shop_id' => array('in',explode (',',$id)));
			$condition1 = array('id' => array('in',explode (',',$id)));
			$list = $model->where ($condition)->select();
			if(false !== $model->where ( $condition )->delete())
			{
				D('ShopMatch')->where($condition1)->delete();
				D('ShopCheck')->where($condition)->delete();
				D('ShopShare')->where($condition)->delete();
				
				$img_ids = array();
				$shop_ids = array();
				foreach($list as $item)
				{
					$shop_ids[] = $item['shop_id'];
					if($item['shop_logo'] > 0)
						$img_ids[] = $item['shop_logo'];
				}
				$_SESSION['shop_remove_ids'] = $shop_ids;
				vendor("common");
				FS('Image')->deleteImages($img_ids);
				$this->saveLog(1,$id);
				$this->redirect('Shop/removeShare');
			}
			else
			{
				$this->error(L('REMOVE_ERROR'));
			}
		}
		else
		{
			$this->error(L('ACCESS_DENIED'));
		}
	}
	
	public function getShop()
	{
		vendor("common");
		$key = trim($_REQUEST['key']);
		$where = '';
		if(!empty($key))
        {
			$match_key = FS('Words')->segmentToUnicode($key,'+');
			$where.=" match(sm.shop_name) against('".$match_key."' IN BOOLEAN MODE) ";
        }
		
		if(empty($where))
			$sql = 'SELECT shop_id,shop_name FROM '.C("DB_PREFIX").'shop ORDER BY shop_id DESC limit 0,30';
		else
			$sql = 'SELECT s.shop_id,s.shop_name FROM '.C("DB_PREFIX").'shop_match as sm 
				INNER JOIN '.C("DB_PREFIX").'shop as s ON s.shop_id = sm.id 
				WHERE '.$where.' ORDER BY sm.id DESC limit 0,30';

		$userList = M()->query($sql);
		echo json_encode($userList);
	}

	public function check()
	{
		$model = M();
		$sql_count = 'SELECT COUNT(shop_id) AS gcount FROM '.C("DB_PREFIX").'shop_check ';
		$sql = 'SELECT s.* FROM '.C("DB_PREFIX").'shop_check AS sc 
			INNER JOIN '.C("DB_PREFIX").'shop AS s ON s.shop_id = sc.shop_id ';
		
		$count = $model->query($sql_count);
		$count = $count[0]['gcount'];

		$this->_sqlList($model,$sql,$count,$parameter,'sc.shop_id',true);
		$this->display();
	}
	
	public function checkOk()
	{
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ShopCheck');
			$condition = array ('shop_id' => array ('in', explode ( ',', $id ) ) );
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

	public function disable()
	{
		$id = $_REQUEST['id'];
		$_SESSION['shop_remove_ids'] = '';
		if(!empty($id))
		{
			$model = D("Shop");
			$condition = array('shop_id' => array('in',explode(',',$id)));
			$condition1 = array('id' => array('in',explode (',',$id)));
			$list = $model->where ($condition)->select();
			$time = gmtTime();
			$shop_ids = array();
			$img_ids = array();
			foreach($list as $item)
			{
				$shop_ids[] = $item['shop_id'];
				if($item['shop_logo'] > 0)
					$img_ids[] = $item['shop_logo'];
			}
			$_SESSION['shop_remove_ids'] = $shop_ids;
			$shop_ids = implode(',',$shop_ids);
			$sql = 'REPLACE INTO '.C("DB_PREFIX")."shop_disable(shop_name,shop_url) 
				SELECT shop_name,shop_url FROM ".C("DB_PREFIX")."shop 
				WHERE shop_id IN (".$shop_ids.")";
			M()->query($sql);
			
			if(false !== $model->where ( $condition )->delete())
			{
				D('ShopMatch')->where($condition1)->delete();
				D('ShopCheck')->where($condition)->delete();
				D('ShopShare')->where($condition)->delete();
				
				vendor("common");
				FS('Image')->deleteImages($img_ids);
				$this->saveLog(1,$id);
				$this->redirect('Shop/removeShare');
			}
			else
			{
				$this->error(L('REMOVE_ERROR'));
			}
		}
		else
		{
			$this->error(L('ACCESS_DENIED'));
		}
	}
	
	public function disables()
	{
		$where = '';
		$parameter = array();
		$url = trim($_REQUEST['url']);
		
		if(!empty($url))
		{
			$this->assign("url",$url);
			$parameter['url'] = $url;
			$where = " WHERE shop_url = '".$url."' ";
		}
		
		$model = M();
		$sql_count = 'SELECT COUNT(shop_id) AS gcount FROM '.C("DB_PREFIX").'shop_disable '.$where;
		$sql = 'SELECT * FROM '.C("DB_PREFIX").'shop_disable '.$where;
		
		$count = $model->query($sql_count);
		$count = $count[0]['gcount'];

		$this->_sqlList($model,$sql,$count,$parameter,'shop_id');
		$this->display();
	}
	
	public function removeDisables()
	{
		$result = array('isErr'=>0,'content'=>'');
		$id = $_REQUEST['id'];
		if(!empty($id))
		{
			$model = D('ShopDisable');
			$condition = array ('shop_id' => array ('in', explode ( ',', $id ) ) );
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
	
	public function removeShare()
	{
		$this->display('remove');
		$shop_remove_ids = $_SESSION['shop_remove_ids'];
		$index = (int)$_REQUEST['index'];
		$count = 100;
		$min = $index * $count;
		$max = $min + $count;
		$sql = 'SELECT share_id FROM '.C("DB_PREFIX").'share_goods 
			WHERE shop_id IN ('.implode(',',$shop_remove_ids).') GROUP BY share_id 
			ORDER BY share_id ASC LIMIT 0,'.$count;
		$list = M()->query($sql);
		
		if(count($list) > 0)
		{
			echoFlush('<script type="text/javascript">showmessage(\''.sprintf(L('DELETE_TIPS_1'),$min,$max).'\',1);</script>');
			vendor("common");
			@set_time_limit(0);
			if(function_exists('ini_set'))
				ini_set('max_execution_time',0);
			
			foreach($list as $item)
			{
				D("Share")->removeHandler($item['share_id']);
				usleep(10);
			}
			usleep(100);
			$index++;
			echoFlush('<script type="text/javascript">showmessage(\''.U('Shop/removeShare',array('index'=>$index)).'\',2);</script>');
			exit;
		}
		usleep(500);
		
		echoFlush('<script type="text/javascript">showmessage(\''.L('DELETE_TIPS_2').'\',1);</script>');
		echoFlush('<script type="text/javascript">showmessage(\''.U('Shop/removeGoods',array('index'=>0)).'\',2);</script>');
	}
	
	public function removeGoods()
	{
		$this->display('remove');
		$shop_remove_ids = $_SESSION['shop_remove_ids'];
		$index = (int)$_REQUEST['index'];
		$count = 100;
		$min = $index * $count;
		$max = $min + $count;
		$sql = 'SELECT * FROM '.C("DB_PREFIX").'goods 
			WHERE shop_id IN ('.implode(',',$shop_remove_ids).') ORDER BY id ASC LIMIT 0,'.$count;
		$list = M()->query($sql);
		
		if(count($list) > 0)
		{
			echoFlush('<script type="text/javascript">showmessage(\''.sprintf(L('DELETE_TIPS_3'),$min,$max).'\',1);</script>');
			@set_time_limit(0);
			if(function_exists('ini_set'))
				ini_set('max_execution_time',0);
			
			$goods_ids = array();
			$img_ids = array();
			foreach($list as $item)
			{
				$goods_ids[] = $item['id'];
				if($item['img_id'] > 0)
					$img_ids[] = $item['img_id'];
				$index++;
			}
			
			vendor("common");
			FS('Image')->deleteImages($img_ids);
			
			$condition = array('id' => array('in',$goods_ids));
			D('GoodsMatch')->where($condition)->delete();
			D('GoodsCheck')->where($condition)->delete();
			D('Goods')->where($condition)->delete();
			usleep(100);
			echoFlush('<script type="text/javascript">showmessage(\''.U('Shop/removeGoods',array('index'=>$index)).'\',2);</script>');
			exit;
		}
		usleep(500);
		$_SESSION['shop_remove_ids'] = '';
		echoFlush('<script type="text/javascript">showmessage(\''.L('DELETE_TIPS_4').'\',3);</script>');
	}
}

?>