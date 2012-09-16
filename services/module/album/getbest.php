<?php
$aid = (int)$_FANWE['request']['id'];
if($aid == 0)
	exit;

$album = FS("Album")->getAlbumById($aid,false);
if(!$album)
	exit;

$album['user_name'] = FDB::resultFirst('SELECT user_name FROM '.FDB::table('user').' WHERE uid = '.$album['uid']);
include template('services/album/getbest');		
display();
?>