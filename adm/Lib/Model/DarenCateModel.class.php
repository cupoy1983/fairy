<?php
class DarenCateModel extends CommonModel
{
	public $_validate = array(
		array('name','require','{%NAME_REQUIRE}'),
	);

	protected $_auto = array( 
		array('status','1'),  // 新增的时候把status字段设置为1
	);
}
?>