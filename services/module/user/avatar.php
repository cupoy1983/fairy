<?php
if($_FANWE['uid'] == 0)
	exit;

$img = FS('Image')->save('avatar','temp',true,array('avatar'=>array(200,200,1)),true);
if($img['thumb']['avatar']['url'] === false)
	$img['thumb']['avatar']['url'] = "";
outputJson(array('src'=>$img['thumb']['avatar']['url']));
?>