<?php
$home_uid = $_FANWE['uid'];
$fid = $_FANWE['request']['fid'];
if($fid >0 && $home_uid >0){
    $result['is_add_forum']  = FS('Club')->join($fid,$home_uid,$_FANWE['user_name']);
}else{
    $result['is_add_forum'] = 0;
}

$result['fid'] = $fid;
outputJson($result);
?>
