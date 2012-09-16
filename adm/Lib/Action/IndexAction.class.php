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
 * 后台首页
 +------------------------------------------------------------------------------
 */
class IndexAction extends FanweAction
{
	public function index()
	{
		if (isset($_SESSION[C('USER_AUTH_KEY')]))
			$this->display();
		else
			$this->redirect('Public/login');
	}

	public function top()
	{
		$this->check();
		$list = D('RoleNav')->where('status=1')->field('id,name')->order("sort")->select();
		$this->assign('role_navs',$list);
		$this->display();
	}

	public function left()
	{
		$this->check();
		$id	= intval($_REQUEST['id']);
        $menus  = array();
        //if(isset($_SESSION['menu_'.$id.'_'.$_SESSION[C('USER_AUTH_KEY')]]))
		if(false)
        	$menus = $_SESSION['menu_'.$id.'_'.$_SESSION[C('USER_AUTH_KEY')]];
        else
		{
			if($id == 0)
				$id = D("RoleNav")->where('status=1')->order("sort ASC,id ASC")->getField('id');

			if($id == 0)
				return;

			$where = array();
			$where['status']    = 1;
			$where['nav_id']    = $id;
			$where['is_show']   = 1;
			$where['auth_type'] = 0;

			$no_modules = explode(',',strtoupper(C('NOT_AUTH_MODULE')));

			$access_list = $_SESSION['_ACCESS_LIST'];
			$node_list = D("RoleNode")->where($where)->field('id,action,action_name,module,module_name')->order('sort ASC,id ASC')->select();
			foreach($node_list as $key=>$node)
			{
				if((isset($access_list[strtoupper($node['module'])]['MODULE']) || isset($access_list[strtoupper($node['module'])][strtoupper($node['action'])])) || $_SESSION['administrator'] || in_array(strtoupper($node['module']),$no_modules))
				{
					$menus[$node['module']]['nodes'][] = $node;
					$menus[$node['module']]['name']	= $node['module_name'];
				}
            }

			$_SESSION['menu_'.$id.'_'.$_SESSION[C('USER_AUTH_KEY')]] = $menus;
		}

		$this->assign('menus',$menus);
		$this->display();
	}

	public function main()
	{
		$this->check();
		$systems = array();
		$systems['is_install_dir'] = file_exists(FANWE_ROOT.'install');
		$systems['is_update_dir'] = file_exists(FANWE_ROOT.'update');
		//$systems['user_count'] = D('User')->count();
		//$systems['share_count'] = D('Share')->count();
		$systems['share_check'] = D('ShareCheck')->count();
		//$systems['goods_count'] = D('Goods')->count();
		$systems['group_check'] = D('ForumApply')->count();
		$systems['share_check'] = D('ShareCheck')->count();
		$systems['goods_check'] = D('GoodsCheck')->count();
		$systems['shop_check'] = D('ShopCheck')->count();
		//$systems['shop_count'] = D('Shop')->count();
		$systems['order_count'] = D('Order')->where('status = 0')->count();
		$systems['tx_count'] = D('UserAuctionLog')->where('status = 0')->count();
		$systems['yj_count'] = D('GoodsOrder')->where('status = 1 AND is_pay = 0')->count();
		$systems['xz_count'] = D('MedalApply')->count();
		$this->assign('systems',$systems);
		$this->display();
	}

	public function password()
	{
		$this->check();
		$id = $_SESSION[C('USER_AUTH_KEY')];
		$admin = D('Admin')->getById($id);
		$this->assign('admin',$admin);
		$this->display();
	}

	public function changePwd()
	{
		$this->check();
		$old_pwd = $_REQUEST['old_pwd'];
		$new_pwd = $_REQUEST['new_pwd'];
		$confirm_pwd = $_REQUEST['confirm_pwd'];

		if($old_pwd == '')
			$this->error(L('OLD_PWD_REQUIRE'));

		if($new_pwd == '')
			$this->error(L('NEW_PWD_REQUIRE'));

		if($new_pwd != $confirm_pwd)
			$this->error(L('CONFIRM_ERROR'));

		$id = $_SESSION[C('USER_AUTH_KEY')];
		$admin = D('Admin')->getById($id);

		$old_pwd = md5($old_pwd);
		if($old_pwd != $admin['admin_pwd'])
			$this->error(L('OLD_PWD_ERROR'));

		D("Admin")->where('id = '.$id)->setField('admin_pwd',md5($new_pwd));
		$this->assign('jumpUrl',U('Index/password'));
		$this->success (L('EDIT_SUCCESS'));
	}

	public function footer()
	{
		$this->check();
		$this->display();
	}
	
	private function check()
	{
		if (!isset($_SESSION[C('USER_AUTH_KEY')]))
			exit;
	}
}
?>