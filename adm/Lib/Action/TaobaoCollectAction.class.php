<?php

// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jobin.lin <jobin.lin@gmail.com>
// +----------------------------------------------------------------------
/**
  +------------------------------------------------------------------------------
 * 淘宝采集管理
  +------------------------------------------------------------------------------
 */
class TaobaoCollectAction extends CommonAction
{
	public function index()
	{
		$is_auto_collect = false;
		$_SESSION['taobao_collect_shop'] = 0;
		if(file_exists(FANWE_ROOT."./public/taobao/auto_collect.php"))
		{
			$is_auto_collect = true;
			$auto_collect = @include FANWE_ROOT."./public/taobao/auto_collect.php";
			$this->assign("auto_collect",$auto_collect);
		}
		$this->assign("is_auto_collect",$is_auto_collect);
		
		$_REQUEST ['listRows'] = 100;
		parent::index();
	}
	
	public function shop()
	{
		if(!isset($_SESSION['taobao_collect_shop']))
			exit;
			
		if($_SESSION['taobao_collect_shop'] == 0)
		{
			@set_time_limit(3600);
			if(function_exists('ini_set'))
			{
				ini_set('max_execution_time',3600);
				ini_set("memory_limit","256M");
			}
			M()->query('INSERT INTO '.C("DB_PREFIX").'taobao_shop_temp SELECT NULL,nick FROM '.C("DB_PREFIX").'taobao_collect GROUP BY nick');
			file_put_contents(FANWE_ROOT."./public/taobao/collect.lock","1");
		}
		
		$_SESSION['taobao_collect_shop']++;
		$begin = ($_SESSION['taobao_collect_shop'] - 1) * 20;
		$end = $begin + 20;
		
		$tips = '正在采集店铺 '.$cate_name.' 第 '.$begin.' 到 '.$end.' 行';
		$count = D('TaobaoShopTemp')->count();
		$tips .= '<br/>还有 '.$count.' 个店铺未采集';
		$this->assign("tips",$tips);
		
		ob_start();
		ob_end_flush(); 
		ob_implicit_flush(1);
		$this->display("collect");
		echoFlush('<script type="text/javascript"></script>');
		
		vendor('common');
		if(FS("Goods")->collectShop() == 0)
		{
			echoFlush('<script type="text/javascript">var func = function(){location.href="'.U('TaobaoCollect/shop',array('time'=>TIME_UTC)).'";};setTimeout(func,1000);</script>');
		}
		else
		{
			$_SESSION['taobao_collect_goods'] = 0;
			$this->redirect('TaobaoCollect/goods');
		}
	}
	
	public function goods()
	{
		if(!isset($_SESSION['taobao_collect_goods']))
			exit;
		
		$_SESSION['taobao_collect_goods']++;
		$begin = ($_SESSION['taobao_collect_goods'] - 1) * 20;
		$end = $begin + 20;
		
		$tips = '正在获取商品详细 '.$cate_name.' 第 '.$begin.' 到 '.$end.' 行';
		$count = D('TaobaoCollect')->count();
		$tips .= '<br/>还有 '.$count.' 个商品详细未获取';
		
		$this->assign("tips",$tips);
		
		ob_start();
		ob_end_flush(); 
		ob_implicit_flush(1);
		$this->display("collect");
		echoFlush('<script type="text/javascript"></script>');
		
		vendor('common');
		if(FS("Goods")->collectGoods() == 0)
		{
			echoFlush('<script type="text/javascript">var func = function(){location.href="'.U('TaobaoCollect/goods',array('time'=>TIME_UTC)).'";};setTimeout(func,1000);</script>');
		}
		else
		{
			$_SESSION['taobao_collect_share'] = 0;
			$this->redirect('TaobaoCollect/share');
		}
	}
	
	public function share()
	{
		if(!isset($_SESSION['taobao_collect_share']))
			exit;
		
		$_SESSION['taobao_collect_share']++;
		$begin = ($_SESSION['taobao_collect_share'] - 1) * 20;
		$end = $begin + 20;
		
		$tips = '正在发布 '.$cate_name.' 第 '.$_SESSION['taobao_collect_share'].' 个商品分享';
		$count = D('TaobaoShare')->count();
		$tips .= '<br/>还有 '.$count.' 个商品未分享';
		
		$this->assign("tips",$tips);
		
		ob_start();
		ob_end_flush(); 
		ob_implicit_flush(1);
		$this->display("collect");
		echoFlush('<script type="text/javascript"></script>');
		
		vendor('common');
		if(FS("Goods")->share() == 0)
		{
			echoFlush('<script type="text/javascript">var func = function(){location.href="'.U('TaobaoCollect/share',array('time'=>TIME_UTC)).'";};setTimeout(func,1000);</script>');
		}
		else
		{
			$this->assign("jumpUrl",u("TaobaoCollect/index"));
			@unlink(FANWE_ROOT."./public/taobao/collect.lock");
			$this->success ("采集成功");
		}
	}
	
    public function setting()
	{
		$is_auto_collect = false;
		if(file_exists(FANWE_ROOT."./public/taobao/auto_collect.php"))
		{
			$is_auto_collect = true;
			$auto_collect = @include FANWE_ROOT."./public/taobao/auto_collect.php";
			$this->assign("auto_collect",$auto_collect);
		}
		$this->assign("is_auto_collect",$is_auto_collect);
		
		$is_collect_lock = file_exists(FANWE_ROOT."./public/taobao/collect.lock");
		$this->assign("is_collect_lock",$is_collect_lock);
		$cate_keywords = array();
		$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
		if(!$config)
		{
			$config['is_auto_collect'] = 0;
			$config['collect_time'] =  12;
			$config['sort_order'] =  '';
			$config['page_num'] =  1;
			$config['user_ids'] =  '';
			$config['user_gid'] =  0;
			$config['cate_ids'] =  '';
			$config['keywords'] = array();
		}
		else
		{
			if(!empty($config['user_ids']))
			{
				$user_list = D('User')->where("uid IN (".$config['user_ids'].")")->field('uid,user_name')->select();
				$this->assign("user_list",$user_list);
			}
			
			if(!empty($config['cate_ids']))
			{
				$where['type'] = 'taobao';
				$where['id'] = array('in',$config['cate_ids']);
				$cate_slist = D('GoodsCates')->where($where)->select();
				$this->assign("cate_slist",$cate_slist);
			}
			
			foreach($cate_slist as $cate)
			{
				$keywords = '';
				if(isset($config['keywords'][$cate['id']]))
					$keywords = $config['keywords'][$cate['id']];
				
				$cate_keywords[] = array(
					'id'=>$cate['id'],
					'name'=>$cate['name'],
					'keywords'=>$keywords,
				);
			}
		}
		$this->assign("vo",$config);
		$this->assign('cate_keywords',$cate_keywords);
		
		$group_list = D("UserGroup")->where('gid <> 6')->getField('gid,name');
		$this->assign("group_list",$group_list);
		
		$cate_list = D('GoodsCates')->where("type='taobao' and pid = ''")->field('id,name')->order('sort asc')->select();
        $this->assign('cate_list',$cate_list);
        $this->display();
    }
	
	public function clearautolock()
	{
		@unlink(FANWE_ROOT."./public/taobao/auto_collect.php");
		if(file_exists(FANWE_ROOT."./public/taobao/auto_collect.php"))
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->error('删除锁定失败，您可以手动删除文件：'.FANWE_ROOT."./public/taobao/auto_collect.php");
		}
		else
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->success ('取消锁定成功');
		}
	}
	
	public function clearlock()
	{
		@unlink(FANWE_ROOT."./public/taobao/collect.lock");
		if(file_exists(FANWE_ROOT."./public/taobao/collect.lock"))
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->error('取消采集锁定失败，您可以手动删除文件：'.FANWE_ROOT."./public/taobao/collect.lock");
		}
		else
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->success ('取消采集锁定成功');
		}
	}
	
	public function update()
	{
		if(empty($_REQUEST['cate_ids']))
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->error ("请设置采集分类");
		}
		
		if(empty($_REQUEST['user_ids']) && (int)$_REQUEST['user_gid'] == 0)
		{
			$this->assign("jumpUrl",u("TaobaoCollect/setting"));
			$this->error ("请设置会员或者会员组");
		}
		
		if($this->save())
		{
			if((int)$_REQUEST['is_collect'] == 1)
			{
				M()->query('TRUNCATE TABLE '.C("DB_PREFIX").'taobao_collect');
				M()->query('TRUNCATE TABLE '.C("DB_PREFIX").'taobao_shop_temp');
				M()->query('TRUNCATE TABLE '.C("DB_PREFIX").'taobao_share');

				$_SESSION['taobao_collect_cindex'] = 0;
				$_SESSION['taobao_collect_page'] = 1;
				$_SESSION['taobao_collect_errnum'] = 0;
				file_put_contents(FANWE_ROOT."./public/taobao/collect.lock","1");
				$this->assign("jumpUrl",u("TaobaoCollect/collect"));
				$this->success (L('EDIT_SUCCESS'));
			}
			else
			{
				$this->assign("jumpUrl",u("TaobaoCollect/setting"));
				$this->success (L('EDIT_SUCCESS'));
			}
		}
		else
		{
			$this->error (L('EDIT_ERROR'));
		}
    }
	
	public function collect()
	{
		if(!isset($_SESSION['taobao_collect_cindex']) || !isset($_SESSION['taobao_collect_page']))
			exit;

		$config = @include FANWE_ROOT."./public/taobao/collect.config.php";
		
		if($_SESSION['taobao_collect_page'] > $config['page_num'])
		{
			$_SESSION['taobao_collect_page'] = 1;
			$_SESSION['taobao_collect_cindex']++;
		}

		$cids = explode(',',$config['cate_ids']);
		if($_SESSION['taobao_collect_cindex'] >= count($cids))
		{
			$this->assign("jumpUrl",u("TaobaoCollect/index"));
			@unlink(FANWE_ROOT."./public/taobao/collect.lock");
			$this->success ("采集成功");
			exit;
		}

		$cate_name = D('GoodsCates')->where("type='taobao' and id = '".$cids[$_SESSION['taobao_collect_cindex']]."'")->getField('name');

		$tips = '正在采集分类 '.$cate_name.' 第 '.$_SESSION['taobao_collect_page'].' 页';
		
		if($_SESSION['taobao_collect_errnum'] > 0)
			$tips .= '<br/>已经失败 '.$_SESSION['taobao_collect_errnum'].' 次，失败重采剩余次数 '.(10 - $_SESSION['taobao_collect_errnum']);
			
		$tips .= '<br/>该分类还有 '.($config['page_num'] - $_SESSION['taobao_collect_page']).' 页未采集';
		$tips .= '<br/>还有 '.(count($cids) - 1 - $_SESSION['taobao_collect_cindex']).' 个其他分类未采集';
		
		$this->assign("tips",$tips);
		
		ob_start();
		ob_end_flush(); 
		ob_implicit_flush(1);
		$this->display();
		echoFlush('<script type="text/javascript"></script>');
		
		@set_time_limit(3600);
		sleep(1);
		
		vendor('common');
		if(function_exists('ini_set'))
		{
			ini_set('max_execution_time',3600);
			ini_set("memory_limit","256M");
		}
		$cate_id = $cids[$_SESSION['taobao_collect_cindex']];
		$keywords = trim($config['keywords'][$cate_id]);
		
		$result = FS('Goods')->collect($cate_id,$keywords,$config['sort_order'],$_SESSION['taobao_collect_page']);
		if($result['status'] == 1)
		{
			$_SESSION['taobao_collect_errnum'] = 0;
			if($_SESSION['taobao_collect_page'] >= $result['max_page'])
			{
				$_SESSION['taobao_collect_page'] = 0;
				$_SESSION['taobao_collect_cindex']++;
			}
		}
		else
		{
			$_SESSION['taobao_collect_errnum']++;
			if($_SESSION['taobao_collect_errnum'] < 10)
			{
				echoFlush('<script type="text/javascript">var func = function(){location.href="'.U('TaobaoCollect/collect',array('time'=>TIME_UTC)).'";} setTimeout(func,500);</script>');
				exit;
			}
			else
				$_SESSION['taobao_collect_errnum'] = 0;
		}
		
		$_SESSION['taobao_collect_page']++;
		echoFlush('<script type="text/javascript">var func = function(){location.href="'.U('TaobaoCollect/collect',array('time'=>TIME_UTC)).'";}; setTimeout(func,1000);</script>');
    }
	
	private function save()
	{
		$config = array();
		$config['is_auto_collect'] = isset($_REQUEST['is_auto_collect']) ? (int)$_REQUEST['is_auto_collect'] : 0;
		$config['collect_time'] =  (int)$_REQUEST['collect_time'];
		if($config['collect_time'] < 1)
			$config['collect_time'] = 12;
		
		$config['sort_order'] =  trim($_REQUEST['sort_order']);
		$config['page_num'] =  (int)$_REQUEST['page_num'];
		if($config['page_num'] < 1)
			$config['page_num'] = 1;
			
		if($config['page_num'] > 99)
			$config['page_num'] = 99;
		
		$config['keywords'] = $_REQUEST['keywords'];
		
		$config['user_ids'] =  trim($_REQUEST['user_ids']);
		$config['user_gid'] =  (int)$_REQUEST['user_gid'];
		$config['cate_ids'] =  trim($_REQUEST['cate_ids']);
		file_put_contents(FANWE_ROOT."./public/taobao/collect_setting.lock","1");
		if(@file_put_contents(FANWE_ROOT."./public/taobao/collect.config.php","<?php\n".'return '.var_export($config, true).";\n\n?>"))
		{
			vendor('common');
			FDB::delete('cron',"server = 'collect'");
			$cron = array();
			$cron['server'] = 'collect';
			$cron['run_time'] = TIME_UTC;
			FDB::insert('cron',$cron);
			return true;
		}
		return false;
	}
}

function showGoods($title,$goods)
{
	return '<a href="'.$goods['click_url'].'" target="_blank">'.$title.'</a>';
}

function showShop($nick,$goods)
{
	return '<a href="'.$goods['shop_click_url'].'" target="_blank">'.$nick.'</a>';
}
?>
