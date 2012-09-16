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
 * 管理员
 +------------------------------------------------------------------------------
 */
class UpYunAction extends CommonAction
{
	public function index()
	{
		$is_has=0;
		$UpYun=array();
		$is_has=0;
		if(file_exists(FANWE_ROOT."./public/yun/UpYun.php"))
		{
			$is_has=1;
			$UpYun=@require(FANWE_ROOT."./public/yun/UpYun.php");
		}
		else
			$UpYun['url'] = 'http://';

		$this->assign('UpYun',$UpYun);
		$this->assign('is_has',$is_has);
		$this->display();
	}

	public function test()
	{
		@require(FANWE_ROOT."./core/class/upyun.class.php");
		$space_name=$_REQUEST['space_name'];
		$user=urlencode($_REQUEST['user']);
		$password=$_REQUEST['password'];
		$upyun = new UpYun($space_name, $user,$password);
		//$upyun->debug = true;
		//$space_num = $upyun->getBucketUsage();
		$result=array();
		if($upyun->getBucketUsage() === NULL)
		{
			$result['status']=0;
		}
		else
		{
			$result['status']=1;
		}
		echo $result['status'];

	}

	public function update()
	{
		$appkey=UpYunKEY;
		$space_name=$_REQUEST['space_name'];
		$user=urlencode($_REQUEST['user']);
		$password=$_REQUEST['password'];
		$status=$_REQUEST['status'];
		$url=$_REQUEST['url'];
		$UpYun_str='<?php return  array("space_name" =>\''.$space_name.'\' ,"user" =>"'.$user.'" 
			,"password" =>"'.$password.'" ,"status" =>"'.$status.'" ,"url" =>"'.$url.'" , ); ?> ';
		$re= file_put_contents(FANWE_ROOT."./public/yun/UpYun.php", $UpYun_str);

		if($re>0)
		{
			$this->assign("jumpUrl",u("UpYun/index"));
			$this->success (L('EDIT_SUCCESS'));
		}
		else
		{
			$this->error (L('EDIT_ERROR'));
		}
	}
}
?>