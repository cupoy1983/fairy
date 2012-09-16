<?php
$cache_file = getTplCache('services/share/addgoods');
if(!@include($cache_file))
{
	FanweService::instance()->cache->loadCache('business');
	$business = $_FANWE['cache']['business'];
	unset($business['yiqifa']);
	include template('services/share/addgoods');
}			
display($cache_file);
?>