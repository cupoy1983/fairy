<?php
/**
 *  当前模板用到的相关函数
*/
function getLoginScroll()
{
	return tplFetch('inc/common/login_scroll');
}

/**
 * 首页分类分推荐分享
 */
function getIndexCateGroupShare()
{
	$result = FS('Delay')->get('index/cate_share');

	$is_create = true;
	if($result['status'] == 1)
		$is_create = false;

	if($is_create)
		FS('Delay')->create(array('m'=>'index','a'=>'cate_group'));
		
	return $result['data'];
}
?>