<?php
class indexMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 0;

		FanweService::instance()->cache->loadCache(array('mindex','madv'));
		$advs = $_FANWE['cache']['madv']['index'];
		if($advs)
		{
			foreach($advs as $adv)
			{
				$adv['img'] = FS("Image")->getImageUrl($adv['img'],2);
				if($adv['type'] == 1)
				{
					$tag_count = count($adv['data']['tags']);
					unset($adv['data']);
					$adv['data']['count'] = $tag_count;
				}
				elseif($adv['type'] != 2 && $adv['type'] != 8)
					unset($adv['data']);
				unset($adv['sort'],$adv['status'],$adv['page']);
				$root['advs'][] = $adv;
			}
		}

		foreach($_FANWE['cache']['mindex'] as $index)
		{
			$index['img'] = FS("Image")->getImageUrl($index['img'],2);
			if($index['type'] == 1)
			{
				$tag_count = count($index['data']['tags']);
				unset($index['data']);
				$index['data']['count'] = $tag_count;
			}
			elseif($index['type'] != 2 && $index['type'] != 8)
				unset($index['data']);
			unset($index['sort'],$index['status']);
			$root['indexs'][] = $index;
		}
		m_display($root);
	}
}
?>