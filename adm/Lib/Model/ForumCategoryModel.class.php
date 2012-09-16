<?php
class ForumCategoryModel extends CommonModel
{
	protected $_validate = array(
		array('cate_name','require','{%CATE_NAME_EMPTY}'),
	);

	protected $_auto = array( 
		array('status','1'),  // 新增的时候把status字段设置为1	
	);
}
?>