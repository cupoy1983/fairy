<?php
function bindCacheUsergroup()
{
	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('user_group')." WHERE status = 1 ORDER BY gid ASC");
	while($data = FDB::fetch($res))
	{
		$list[$data['gid']] = $data;
	}
	FanweService::instance()->cache->saveCache('user_group', $list);
}
?>