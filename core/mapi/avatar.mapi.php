<?php
class avatarMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;
		
		if($_FANWE['uid'] == 0)
		{
			$root['info'] = "请先登陆";
			m_display($root);
		}

		if(isset($_FILES['image_1']))
		{
			if($_FANWE['user']['avatar'] > 0)
			{
				$uimg = array();
				$uimg['type'] = 'avatar';
				$uimg['src'] = $img['path'];
				$uimg['id'] = $_FANWE['user']['avatar'];
				FS('Image')->updateImage($uimg,true);
				$uimg = FS('Image')->getImageById($uimg['id']);
				$root['user']['user_avatar'] = $root['user_avatar'] = getImgName($uimg['src'],64,64,1,true);
			}
			else
			{
				FS('User')->saveAvatar($_FANWE['uid'],$img['path']);
				$avatar = FS('User')->getAvatar($_FANWE['uid']);
				$root['user']['user_avatar'] = $root['user_avatar'] = avatar($avatar,'m',true);
			}
		}
		else
		{
			$root['info'] = "请上传图片";
			m_display($root);
		}

		$root['return'] = 1;
		m_display($root);
	}
}
?>