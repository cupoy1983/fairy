<?php
$page = $_FANWE['page'];
if($page == 0)
{
	echo '{"status":0}';
	exit;
}

if($page > 6)
{
	echo '{"status":0}';
	exit;
}

$size = intval($_FANWE['request']['size']);
if($size == 0 || $size > 50)
	$size = 8;

$begin = ($page - 1) * $size + 4;

$list = FS('Topic')->getImgTopic('best',8,1,0,$begin,array(),true);
if(count($list) == 0)
{
	outputJson(array('status'=>3,'html'=>''));
}
else
{
	$status = 1;
	if(count($list) < 8)
		$status = 2;
	
	$args['best_pics'] = array();
	if(count($list) > 0)
		$args['best_pics'] = array_slice($list,0,2);
		
	$args['best_text'] = array();
	if(count($list) > 2)
		$args['best_text'] = array_slice($list,2,6);
	
	$html = tplFetch('inc/group/new_topic_item',$args);
	outputJson(array('status'=>$status,'html'=>$html));
}
?>