<?php
class searchcateMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;
		
		$list = array();
		FanweService::instance()->cache->loadCache('msearchcate');
		foreach($_FANWE['cache']['msearchcate'] as $cate)
		{
			$cate['bg'] = FS("Image")->getImageUrl($cate['bg'],2);
			$list[] = $cate;
		}
		$root['item'] = $list;
		m_display($root);
	}
}
?>