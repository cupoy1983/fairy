<?php
$home_uid = $_FANWE['uid'];
$fid = $_FANWE['request']['fid'];

$result['is_out']  =FS('Club')->forumout($fid,$home_uid)?1:0;
$result['fid'] = $fid;
outputJson($result);
?>
