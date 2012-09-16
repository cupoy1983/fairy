<?php
$fid = $_FANWE['request']['fid'];
$uid = $_FANWE['request']['uid'];
$forum = FS('Club')->getClubById($fid);
if($forum['uid'] ==$_FANWE['uid'] ){
    $result['status']  = FS('Club')->setAdmin($fid,$uid,'0');
}else{
    $result['status'] = 0;
}
outputJson($result);

?>
