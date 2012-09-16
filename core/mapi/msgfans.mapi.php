<?php
class msgfansMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;
		
		if($_FANWE['uid'] == 0)
			exit;
		
		$uid = $_FANWE['uid'];
		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$total = FDB::resultFirst('SELECT COUNT(f_uid) FROM '.FDB::table('user_follow').' WHERE uid = '.$uid);
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;

		Cache::getInstance()->loadCache('citys');
	
		$user_follows = array();
		$res = FDB::query('SELECT u.uid,u.user_name,u.avatar,uc.fans,up.reside_province,up.reside_city,up.introduce 
				FROM '.FDB::table('user_follow').' AS uf
				INNER JOIN '.FDB::table('user').' AS u ON u.uid = uf.f_uid
				INNER JOIN '.FDB::table('user_count').' AS uc ON uc.uid = uf.f_uid
				INNER JOIN '.FDB::table('user_profile').' AS up ON up.uid = uf.f_uid
				WHERE uf.uid = '.$uid.' ORDER BY uf.create_time DESC LIMIT '.$limit);
		while($data = FDB::fetch($res))
		{
			$data['reside_province'] = $_FANWE['cache']['citys']['all'][$data['reside_province']]['name'];
			$data['reside_city'] = $_FANWE['cache']['citys']['all'][$data['reside_city']]['name'];
			$data['user_avatar'] = avatar($data['avatar'],'m',true);
			unset($data['server_code']);
			$user_list[$data['uid']] = $data;
		}
		
		$root['item'] = array_slice($user_list,0);
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);
		m_display($root);
	}
}
?>