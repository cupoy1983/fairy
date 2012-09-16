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
 * 分享设置
 +------------------------------------------------------------------------------
 */
class ShareSettingAction extends CommonAction
{
	public function index()
	{
		$settings = array();
		$list = D("SysConf")->where('group_id = 3')->select();
		foreach($list as $item)
		{
			$settings[$item['name']] = $item['val'];
		}
		$this->assign("settings",$settings);
		$this->display();
	}
	
	public function update()
	{
		$settings = $_REQUEST['settings'];
		foreach($settings as $key => $val)
		{
			D("SysConf")->where("name = '$key'")->setField('val',$val);
		}
		
		$this->saveLog(1);
		$this->success(L('EDIT_SUCCESS'));
	}
}
?>