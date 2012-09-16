<?php
function bindCacheM()
{
	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('m_searchcate')." 
		WHERE status = 1 ORDER BY sort ASC");
	while($data = FDB::fetch($res))
	{
		$tags = fStripslashes(unserialize($data['tags']));
		$data['tags'] = array();
		foreach($tags as $tag)
		{
			$tag['tag_name'] = $tag['tag'];
			$tag['url_tag'] = urlencode($tag['tag']);
			unset($tag['tag']);
			$data['tags'][] = $tag;
		}
		$list[] = $data;
	}
	FanweService::instance()->cache->saveCache('msearchcate', $list);

	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('m_adv')." 
		WHERE status = 1 ORDER BY sort ASC");
	while($data = FDB::fetch($res))
	{
		$data['data'] = fStripslashes(unserialize($data['data']));
		$list[$data['page']][] = $data;
		$list['all'][$data['id']] = $data;
	}
	FanweService::instance()->cache->saveCache('madv', $list);

	$list = array();
	$res = FDB::query("SELECT * FROM ".FDB::table('m_index')." 
		WHERE status = 1 ORDER BY sort ASC");
	while($data = FDB::fetch($res))
	{
		$data['data'] = fStripslashes(unserialize($data['data']));
		$list[$data['id']] = $data;
	}
	FanweService::instance()->cache->saveCache('mindex', $list);
}
?>