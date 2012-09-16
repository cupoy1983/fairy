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
 * 小组设置
 +------------------------------------------------------------------------------
 */
class GroupSettingAction extends CommonAction
{
	public function index()
	{
		$settings = array();
		$list = D("SysConf")->where('group_id = 9')->select();
		foreach($list as $item)
		{
			$settings[$item['name']] = $item['val'];
		}
		$this->assign("settings",$settings);
		
		$gids = explode(',',$settings['GROUP_GROUP_IDS']);
		$group_list = array();
		$list = D("UserGroup")->where('status = 1 AND gid <> 6')->select();
		foreach($list as $item)
		{
			$item['check'] = 0;
			if(in_array($item['gid'],$gids))
				$item['check'] = 1;
			$group_list[] = $item;
		}
		$this->assign("group_list",$group_list);
		
		$user_name = D("User")->where('uid = '.$settings['GROUP_ADMIN_UID'])->getField('user_name');
		$this->assign("user_name",$user_name);
		
		$this->display();
	}

	public function update()
	{
		$settings = $_REQUEST['settings'];
		$settings['GROUP_GROUP_IDS'] = implode(',',$settings['GROUP_GROUP_IDS']);
		$settings['GROUP_ADMIN_UID'] = (int)$settings['GROUP_ADMIN_UID'];
		foreach($settings as $key => $val)
		{
			D("SysConf")->where("name = '$key'")->setField('val',$val);
		}

		$this->saveLog(1);
		$this->success(L('EDIT_SUCCESS'));
	}
}
?>