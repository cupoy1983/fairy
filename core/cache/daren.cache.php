<?php
function bindCacheDaren()
{
	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('daren_cate')." WHERE status = 1 ORDER BY sort ASC,id ASC");
	while($data = FDB::fetch($res))
	{
		$list[$data['id']] = $data;
	}
	FanweService::instance()->cache->saveCache('daren_cate', $list);
}
?>