<?php
class addshareMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;

		if($_FANWE['uid'] == 0)
			exit;
		
		$_FANWE['requestData']['uid'] = $_FANWE['uid'];
		if(isset($_FILES['image_1']))
		{
			if($img = FS("Image")->save('image_1'))
			{
				$info = array('path'=>$img['path'],'type'=>$_FANWE['requestData']['cate_type'],'server_code'=>'');
				$info = authcode(serialize($info), 'ENCODE');
				$_FANWE['requestData']['pics'][] = $info;
			}
			else
			{
				$root['info'] = "上传图片失败";
				m_display($root);
			}
		}
		
		$share = FS('Share')->submit($_FANWE['requestData'],true,true);
		if($share['status'])
		{
			$root['return'] = 1;
			$root['info'] = "发表分享成功";
		}
		else
		{
			$root['info'] = "发表分享失败";
			if(!empty($share['error_msg']))
				$root['info'] = $share['error_msg'];
		}
		m_display($root);
	}
}
?>