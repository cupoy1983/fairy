<?php
class ForumModel extends TreeModel
{
	protected $_validate = array(
		array('name','require','{%NAME_EMPTY_TIP}'),
	);

	protected $_auto = array( 
		array('create_time','gmtTime',1,'function'),
		array('status','1'),  // 新增的时候把status字段设置为1	
	);

	public function getIdsByKey($key)
	{
		$ids = array();
		vendor("common");
		$match_key = FS('Words')->segmentToUnicode($key,'+');
		$sql = 'SELECT fid FROM '.C("DB_PREFIX").'forum_match 
			WHERE match(content) against(\''.$match_key."' IN BOOLEAN MODE) ORDER BY fid DESC LIMIT 0,1000";
		$list = M()->query($sql);
		foreach($list as $item)
		{
			$ids[] = $item['fid'];
		}
		unset($list);
		return $ids;
	}
}
?>