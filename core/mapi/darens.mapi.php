<?php
class darensMapi
{
	public function run()
	{
		global $_FANWE;	
		
		$root = array();
		$root['return'] = 1;

		$page = (int)$_FANWE['requestData']['page'];
		$page = max(1,$page);

		$total = FDB::resultFirst('SELECT COUNT(id) FROM '.FDB::table('user_daren').' WHERE status = 1');
		$page_size = PAGE_SIZE;
		$page_total = max(1,ceil($total/$page_size));
		if($page > $page_total)
			$page = $page_total;
		$limit = (($page - 1) * $page_size).",".$page_size;
		
		$user_follows = array();
		$res = FDB::query('SELECT u.uid,u.user_name,u.avatar,uc.fans,ud.reason,up.introduce 
				FROM '.FDB::table('user_daren').' AS ud
				INNER JOIN '.FDB::table('user').' AS u ON u.uid = ud.uid
				INNER JOIN '.FDB::table('user_count').' AS uc ON uc.uid = ud.uid
				INNER JOIN '.FDB::table('user_profile').' AS up ON up.uid = ud.uid 
				WHERE ud.status = 1 ORDER BY ud.id DESC LIMIT '.$limit);
		while($data = FDB::fetch($res))
		{
			$data['user_avatar'] = avatar($data['avatar'],'m',true);
			$data['desc'] = $data['introduce'];
			if(!empty($data['reason']))
				$data['desc'] = $data['reason'];
			
			unset($data['server_code'],$data['introduce'],$data['reason']);
			if($data['uid'] == $_FANWE['uid'])
				$data['is_follow'] = -1;
			else
			{
				$user_follows[$data['uid']] = 0;
				$data['is_follow'] = 0; 
			}
			$user_list[$data['uid']] = $data;
		}

		$uids = array_keys($user_follows);
		$uids = implode(',',$uids);
		$uids = str_replace(',,',',',$uids);
		if(!empty($uids))
		{
			$res = FDB::query("SELECT uid FROM ".FDB::table('user_follow').' 
				WHERE f_uid = '.$_FANWE['uid'].' AND uid IN ('.$uids.')');
			while($item = FDB::fetch($res))
			{
				$user_list[$item['uid']]['is_follow'] = 1;
			}
		}
		

		$root['item'] = array_slice($user_list,0);
		$root['page'] = array("page"=>$page,"page_total"=>$page_total);

		m_display($root);
	}
}
?>