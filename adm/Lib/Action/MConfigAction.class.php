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
 * 手机系统设置
 +------------------------------------------------------------------------------
 */
class MConfigAction extends CommonAction
{
	public function index()
	{
		$config = M("MConfig")->select();
		$this->assign("config",$config);
		$this->display();
	}

	public function update()
	{
		$upload_list = $this->uploadImages(0,'m');
		if($upload_list)
		{
			foreach($upload_list as $upload_item)
			{
				if($upload_item['key']=="index_logo")
				{
					$index_logo = $upload_item['recpath'].$upload_item['savename'];
				}
			}
		}

		$list = D('MConfig')->select();
		foreach($list as $k=>$v)
		{
			$v['val'] = isset($_REQUEST[$v['code']])?$_REQUEST[$v['code']]:$v['val'];
			if($v['code']=="index_logo" && !empty($index_logo))
			{
				if($index_logo != $v['val'])
				{
					@unlink(FANWE_ROOT.$v['val']);
					$v['val'] = $index_logo;
				}
			}

			D('MConfig')->save($v);
		}

		$this->saveLog(1);
		$this->success(L('EDIT_SUCCESS'));
	}
}
?>