<?php
function bindCacheForum()
{
	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('forum_category')." WHERE status = 1 ORDER BY sort ASC,id ASC");
	while($data = FDB::fetch($res))
	{
		$list[$data['id']] = $data;
	}
	FanweService::instance()->cache->saveCache('forum_category', $list);
}
?>